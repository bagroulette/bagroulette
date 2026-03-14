<?php

namespace App\Services;

use App\Events\WinnerAnnounced;
use App\Events\JackpotUpdated;
use App\Events\DrawSpinning;
use App\Models\DrawResult;
use App\Models\DrawHolder;
use App\Models\TokenPool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * BagRoulette — Fee-sharing destination platform.
 *
 * Flow:
 *  1. Creator launches token on Bags.fm
 *  2. Creator adds @BagRoulette as fee-share recipient (any %)
 *  3. Bags routes trading fees → our wallet automatically
 *  4. Every hour: per-token pool draws 1 winner from that token's holders
 *  5. Winner receives the accumulated SOL prize on-chain
 *
 * BagRoulette never launches its own token.
 * It is a SERVICE any Bags.fm creator can point fees to.
 */
class RouletteService
{
    public function __construct(
        private readonly BagsApiService $bags,
        private readonly HeliusService  $helius,
        private readonly SolanaService  $solana,
    ) {}

    // ─── Main hourly draw — runs for ALL active token pools ──────────────────
    public function executeHourlyDraw(): void
    {
        Log::info('[BagRoulette] Starting hourly draw for all pools');

        $activePools = TokenPool::where('pending_sol', '>', config('bags.min_jackpot_sol'))
            ->where('active', true)
            ->get();

        if ($activePools->isEmpty()) {
            Log::info('[BagRoulette] No active pools with sufficient fees');
            return;
        }

        foreach ($activePools as $pool) {
            try {
                $this->drawForPool($pool);
            } catch (Exception $e) {
                Log::error("[BagRoulette] Draw failed for pool {$pool->token_mint}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    // ─── Execute draw for a single token pool ─────────────────────────────────
    private function drawForPool(TokenPool $pool): void
    {
        Log::info("[BagRoulette] Drawing for {$pool->token_symbol} ({$pool->token_mint})");

        broadcast(new DrawSpinning(true, 30, $pool->token_mint));

        // Snapshot holders for this specific token
        $holders = $this->helius->getTokenHolders($pool->token_mint);

        if ($holders->isEmpty()) {
            Log::warning("[BagRoulette] No holders for {$pool->token_mint}");
            broadcast(new DrawSpinning(false, 0, $pool->token_mint));
            return;
        }

        // Provably fair seed = block_hash + timestamp + sorted holder list
        $blockHash    = $this->solana->getLatestBlockhash();
        $timestamp    = now()->timestamp;
        $holderString = $holders->sortBy('wallet')
            ->map(fn($h) => $h['wallet'] . ':' . $h['balance'])
            ->implode('|');
        $seed = hash('sha256', $blockHash . $timestamp . $holderString);

        // Weighted random selection
        $winner      = $this->weightedRandomSelect($holders, $seed);
        $prize       = round($pool->pending_sol * 0.95, 9);
        $protocolFee = round($pool->pending_sol * 0.05, 9);

        // Claim fees then pay winner
        $this->bags->claimFeesForPool($pool->token_mint);
        $txHash = $this->solana->transferSol(to: $winner['wallet'], amount: $prize);

        // Persist
        $draw = DrawResult::create([
            'token_mint'    => $pool->token_mint,
            'token_symbol'  => $pool->token_symbol,
            'winner_wallet' => $winner['wallet'],
            'winner_twitter'=> $winner['twitter_username'] ?? null,
            'winner_avatar' => $winner['twitter_avatar']   ?? null,
            'amount_sol'    => $prize,
            'pool_sol'      => $pool->pending_sol,
            'tx_hash'       => $txHash,
            'block_hash'    => $blockHash,
            'seed_hash'     => $seed,
            'holders_count' => $holders->count(),
            'drawn_at'      => now(),
        ]);

        // Store holder snapshot for audit (top 500 only for DB efficiency)
        DrawHolder::insert(
            $holders->take(500)->map(fn($h) => [
                'draw_id' => $draw->id,
                'wallet'  => $h['wallet'],
                'balance' => $h['balance'],
                'weight'  => $holders->sum('balance') > 0
                    ? $h['balance'] / $holders->sum('balance')
                    : 0,
            ])->toArray()
        );

        $pool->update([
            'pending_sol'   => 0,
            'total_drawn'   => $pool->total_drawn + $prize,
            'last_drawn_at' => now(),
        ]);

        broadcast(new WinnerAnnounced($draw));
        broadcast(new DrawSpinning(false, 0, $pool->token_mint));
        Cache::forget("pool_{$pool->token_mint}");
        Cache::forget('all_pools');

        Log::info("[BagRoulette] Winner: {$winner['wallet']} got ◎{$prize} tx:{$txHash}");
    }

    // ─── Sync fee balances from Bags API (runs every 5 min) ──────────────────
    // Bags.fm automatically sends fees to our wallet.
    // We call getClaimablePositions() to see how much per token.
    public function syncFeeBalances(): void
    {
        $claimable = $this->bags->getClaimablePositions();

        foreach ($claimable as $position) {
            $mint = $position['token_mint'];

            $pool = TokenPool::firstOrCreate(
                ['token_mint' => $mint],
                [
                    'token_symbol' => $position['token_symbol'] ?? '???',
                    'token_name'   => $position['token_name']   ?? 'Unknown',
                    'active'       => true,
                    'total_drawn'  => 0,
                ]
            );

            $pool->update(['pending_sol' => $position['claimable_sol']]);

            broadcast(new JackpotUpdated(
                tokenMint:    $mint,
                amountSol:    $position['claimable_sol'],
                holdersCount: Cache::remember("holders_count_{$mint}", 300,
                    fn() => $this->helius->getHoldersCount($mint)
                ),
                nextDrawAt: now()->addHour()->startOfHour()->toIso8601String(),
            ));
        }

        Cache::forget('all_pools');
        Log::info('[BagRoulette] Synced ' . count($claimable) . ' pools');
    }

    // ─── Provably fair weighted random selection ──────────────────────────────
    private function weightedRandomSelect(Collection $holders, string $seed): array
    {
        $totalSupply = $holders->sum('balance');
        $seedInt     = hexdec(substr($seed, 0, 14));
        $maxHex      = hexdec('ffffffffffffff');
        $randomPoint = ($seedInt / $maxHex) * $totalSupply;

        $cumulative = 0;
        foreach ($holders as $holder) {
            $cumulative += $holder['balance'];
            if ($cumulative >= $randomPoint) return $holder;
        }
        return $holders->last();
    }

    // ─── Get all active pools for frontend ────────────────────────────────────
    public function getAllPools(): Collection
    {
        return Cache::remember('all_pools', 60, fn() =>
            TokenPool::where('active', true)
                ->orderByDesc('pending_sol')
                ->get()
                ->map(fn($p) => [
                    'token_mint'    => $p->token_mint,
                    'token_symbol'  => $p->token_symbol,
                    'token_name'    => $p->token_name,
                    'pending_sol'   => round($p->pending_sol, 6),
                    'pending_usd'   => round($p->pending_sol * $this->solana->getSolPrice(), 2),
                    'total_drawn'   => round($p->total_drawn, 6),
                    'next_draw_at'  => now()->addHour()->startOfHour()->toIso8601String(),
                    'holders_count' => Cache::get("holders_count_{$p->token_mint}", 0),
                    'last_drawn_at' => $p->last_drawn_at?->toIso8601String(),
                ])
        );
    }

    // ─── Get wallet odds across ALL pools they hold tokens in ─────────────────
    public function getOdds(string $wallet): array
    {
        $pools  = TokenPool::where('active', true)->get();
        $result = [];

        foreach ($pools as $pool) {
            $holders     = Cache::remember("holders_{$pool->token_mint}", 300,
                fn() => $this->helius->getTokenHolders($pool->token_mint)
            );
            $userBalance = $holders->firstWhere('wallet', $wallet)['balance'] ?? 0;
            $total       = $holders->sum('balance');

            if ($userBalance > 0) {
                $result[] = [
                    'token_mint'      => $pool->token_mint,
                    'token_symbol'    => $pool->token_symbol,
                    'balance'         => $userBalance,
                    'win_probability' => $total > 0
                        ? round(($userBalance / $total) * 100, 6)
                        : 0,
                    'jackpot_sol'     => round($pool->pending_sol, 6),
                    'total_holders'   => $holders->count(),
                ];
            }
        }

        return $result;
    }
}
