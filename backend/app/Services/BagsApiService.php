<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class BagsApiService
{
    private string $base = 'https://public-api-v2.bags.fm/api/v1';

    private function http()
    {
        return Http::withHeaders([
            'x-api-key'    => config('bags.api_key'),
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ])->timeout(15)->retry(3, 1000);
    }

    /**
     * Get all claimable fee positions for our treasury wallet.
     * Returns tokens that have routed fees to @BagRoulette.
     */
    public function getClaimablePositions(): array
    {
        $response = $this->http()->get("{$this->base}/token-launch/claimable-positions", [
            'wallet' => config('bags.treasury_wallet'),
        ]);

        $this->assertSuccess($response, 'getClaimablePositions');

        return collect($response->json('response') ?? [])
            ->map(fn($p) => [
                'token_mint'    => $p['baseMint'],
                'claimable_sol' => round(($p['totalClaimableLamportsUserShare'] ?? 0) / 1_000_000_000, 9),
                'program_id'    => $p['programId'],
                'virtual_pool'  => $p['virtualPool'] ?? null,
                'user_bps'      => $p['userBps'] ?? 0,
            ])
            ->filter(fn($p) => $p['claimable_sol'] > 0)
            ->values()
            ->toArray();
    }

    /**
     * Get fee share wallet for a Twitter username.
     */
    public function getFeeShareWallet(string $twitterUsername): ?string
    {
        $response = $this->http()->get("{$this->base}/token-launch/fee-share/wallet/v2", [
            'username' => ltrim($twitterUsername, '@'),
            'provider' => 'twitter',
        ]);

        if (!$response->successful() || !$response->json('success')) return null;
        return $response->json('response.wallet');
    }

    /**
     * Verify if a token mint is linked to BagRoulette by checking
     * claimable positions for our treasury wallet.
     */
    public function getPoolByMint(string $mint): ?array
    {
        $positions = $this->getClaimablePositionsRaw();

        $match = collect($positions)->first(fn($p) => $p['baseMint'] === $mint);

        if (!$match) return null;

        return [
            'token_mint'   => $match['baseMint'],
            'fee_recipients' => [[
                'wallet' => config('bags.treasury_wallet'),
                'bps'    => $match['userBps'],
            ]],
            'is_linked'    => true,
            'claimable_sol' => round(($match['totalClaimableLamportsUserShare'] ?? 0) / 1_000_000_000, 9),
        ];
    }

    /**
     * Raw claimable positions (unfiltered).
     */
    public function getClaimablePositionsRaw(): array
    {
        $response = $this->http()->get("{$this->base}/token-launch/claimable-positions", [
            'wallet' => config('bags.treasury_wallet'),
        ]);

        if (!$response->successful() || !$response->json('success')) return [];
        return $response->json('response') ?? [];
    }

    /**
     * Get partner config claim stats.
     */
    public function getPartnerStats(): array
    {
        $response = $this->http()->get("{$this->base}/fee-share/partner-config/stats", [
            'partner' => config('bags.treasury_wallet'),
        ]);

        if (!$response->successful() || !$response->json('success')) return [];
        return $response->json('response') ?? [];
    }

    public function getTokenLifetimeFees(string $mint): float
    {
        $positions = $this->getClaimablePositionsRaw();
        $match = collect($positions)->first(fn($p) => $p['baseMint'] === $mint);
        return $match ? round(($match['totalClaimableLamportsUserShare'] ?? 0) / 1_000_000_000, 9) : 0.0;
    }

    public function claimFeesForPoolViaNode(string $tokenMint): string
    {
        $positions = $this->getClaimablePositionsRaw();
        $p = collect($positions)->first(fn($p) => $p['baseMint'] === $tokenMint);
        if (!$p) return '';

        $cmd = sprintf(
            'node /root/bagroulette/sign_and_send.js %s %s %s %s %s %s %s %s 2>&1',
            escapeshellarg('https://mainnet.helius-rpc.com/?api-key=' . config('bags.helius_api_key')),
            escapeshellarg(config('bags.api_key')),
            escapeshellarg($p['baseMint']),
            escapeshellarg($p['virtualPool']),
            escapeshellarg($p['programId']),
            escapeshellarg($p['baseMint']),
            escapeshellarg($p['quoteMint']),
            escapeshellarg(config('bags.treasury_wallet')),
        );

        $output = shell_exec($cmd);
        \Illuminate\Support\Facades\Log::info('[BagsApi] claimViaNode: ' . $output);

        preg_match('/SUCCESS! TX: (\S+)/', $output, $matches);
        return $matches[1] ?? '';
    }

    public function claimFeesForPool(string $tokenMint): string
    {
        $response = $this->http()->post("{$this->base}/token-launch/claim-txs/v2", [
            "feeClaimer" => config("bags.treasury_wallet"),
            "tokenMint"  => $tokenMint,
            "claimVirtualPoolFees" => true,
        ]);

        if (!$response->successful() || !$response->json("success")) {
            Log::warning("[BagsApi] claimFeesForPool failed, may already be claimed");
            return "";
        }

        $transactions = $response->json("response") ?? [];
        $lastSig = "";

        foreach ($transactions as $txData) {
            $lastSig = app(SolanaService::class)->signAndSendTransaction($txData["tx"], config("bags.treasury_keypair"));
        }

        return $lastSig;
    }

    private function assertSuccess($response, string $method): void
    {
        if (!$response->successful() || !$response->json('success')) {
            $error = $response->json('error') ?? $response->status();
            Log::error("[BagsApi] {$method} failed: {$error}");
            throw new Exception("[BagsApi] {$method} failed: {$error}");
        }
    }
}
