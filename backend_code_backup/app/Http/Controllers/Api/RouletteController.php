<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DrawResult;
use App\Models\TokenPool;
use App\Services\RouletteService;
use App\Services\BagsApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class RouletteController extends Controller
{
    public function __construct(
        private readonly RouletteService $service,
        private readonly BagsApiService  $bags,
    ) {}

    // GET /api/pools — all active token pools
    public function pools(): JsonResponse
    {
        return response()->json($this->service->getAllPools());
    }

    // GET /api/pools/{mint}
    public function pool(string $mint): JsonResponse
    {
        $pool = TokenPool::where('token_mint', $mint)->firstOrFail();
        return response()->json([
            'token_mint'      => $pool->token_mint,
            'token_symbol'    => $pool->token_symbol,
            'token_name'      => $pool->token_name,
            'creator_twitter' => $pool->creator_twitter,
            'pending_sol'     => round($pool->pending_sol, 6),
            'total_drawn'     => round($pool->total_drawn, 6),
            'draw_count'      => $pool->draws()->count(),
            'next_draw_at'    => now()->addHour()->startOfHour()->toIso8601String(),
            'last_drawn_at'   => $pool->last_drawn_at?->toIso8601String(),
        ]);
    }

    // GET /api/history?token_mint=...&page=1
    public function history(Request $request): JsonResponse
    {
        $draws = DrawResult::orderByDesc('drawn_at')
            ->when($request->token_mint, fn($q) => $q->where('token_mint', $request->token_mint))
            ->paginate(20);

        return response()->json([
            'data'  => $draws->map(fn($d) => $d->toPublicArray()),
            'total' => $draws->total(),
            'page'  => $draws->currentPage(),
        ]);
    }

    // GET /api/odds/{wallet}
    public function odds(string $wallet): JsonResponse
    {
        if (!preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $wallet)) {
            return response()->json(['error' => 'Invalid wallet'], 422);
        }
        return response()->json(
            Cache::remember("odds_{$wallet}", 60, fn() => $this->service->getOdds($wallet))
        );
    }

    // GET /api/leaderboard
    public function leaderboard(): JsonResponse
    {
        return response()->json(Cache::remember('leaderboard', 300, fn() =>
            DrawResult::selectRaw('winner_wallet, winner_twitter, winner_avatar, COUNT(*) as wins, SUM(amount_sol) as total_sol')
                ->groupBy('winner_wallet', 'winner_twitter', 'winner_avatar')
                ->orderByDesc('total_sol')->limit(20)->get()
                ->map(fn($r) => [
                    'wallet'           => $r->winner_wallet,
                    'twitter_username' => $r->winner_twitter,
                    'twitter_avatar'   => $r->winner_avatar,
                    'wins'             => $r->wins,
                    'total_sol'        => round($r->total_sol, 6),
                ])
        ));
    }

    // GET /api/verify/{id}
    public function verify(int $id): JsonResponse
    {
        $d = DrawResult::findOrFail($id);
        return response()->json([
            'id'             => $d->id,
            'token_symbol'   => $d->token_symbol,
            'winner_wallet'  => $d->winner_wallet,
            'amount_sol'     => $d->amount_sol,
            'tx_hash'        => $d->tx_hash,
            'block_hash'     => $d->block_hash,
            'seed_hash'      => $d->seed_hash,
            'holders_count'  => $d->holders_count,
            'drawn_at'       => $d->drawn_at->toIso8601String(),
            'verify_url'     => "https://solscan.io/tx/{$d->tx_hash}",
            'algorithm'      => 'sha256(block_hash + unix_timestamp + sorted_wallet:balance|...)',
        ]);
    }

    // GET /api/stats
    public function stats(): JsonResponse
    {
        return response()->json(Cache::remember('global_stats', 300, fn() => [
            'total_pools'       => TokenPool::where('active', true)->count(),
            'total_draws'       => DrawResult::count(),
            'total_distributed' => DrawResult::sum('amount_sol'),
            'biggest_win'       => DrawResult::max('amount_sol'),
            'unique_winners'    => DrawResult::distinct('winner_wallet')->count('winner_wallet'),
        ]));
    }

    /**
     * POST /api/verify-creator
     * Creator checks if their token is linked to @BagRoulette.
     * This is the onboarding verification endpoint.
     */
    public function verifyCreator(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'token_mint' => ['required', 'regex:/^[1-9A-HJ-NP-Za-km-z]{32,44}$/'],
        ]);
        if ($v->fails()) return response()->json(['error' => $v->errors()], 422);

        $ourWallet    = $this->bags->getFeeShareWallet('BagRoulette');
        $poolData     = $this->bags->getPoolByMint($request->token_mint);
        $recipients   = $poolData['fee_recipients'] ?? [];
        $isLinked     = collect($recipients)->contains(fn($r) => $r['wallet'] === $ourWallet);

        return response()->json([
            'is_linked'          => $isLinked,
            'bagroulette_wallet' => $ourWallet,
            'token_mint'         => $request->token_mint,
            'fee_recipients'     => $recipients,
            'message'            => $isLinked
                ? '✅ Linked! Your token holders will automatically enter hourly draws.'
                : '❌ Not linked. Add @BagRoulette as a fee recipient on Bags.fm.',
            'how_to_link'        => 'When launching your token on Bags.fm, add @BagRoulette in the Fee Sharing section.',
        ]);
    }
}
