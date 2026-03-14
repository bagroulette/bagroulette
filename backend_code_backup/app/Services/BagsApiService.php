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
     * KEY METHOD: Get all claimable fee positions for our wallet.
     * This shows which tokens have routed fees to @BagRoulette
     * and how much SOL is claimable per token.
     */
    public function getClaimablePositions(): array
    {
        $response = $this->http()->get("{$this->base}/fee-claiming/claimable-positions", [
            'wallet' => config('bags.treasury_wallet'),
        ]);

        $this->assertSuccess($response, 'getClaimablePositions');

        return collect($response->json('response') ?? [])
            ->map(fn($p) => [
                'token_mint'    => $p['token_mint'],
                'token_symbol'  => $p['token_symbol'] ?? '???',
                'token_name'    => $p['token_name']   ?? 'Unknown',
                'claimable_sol' => (float) ($p['claimable_sol'] ?? $p['amount'] ?? 0),
            ])
            ->filter(fn($p) => $p['claimable_sol'] > 0)
            ->values()
            ->toArray();
    }

    /**
     * Claim fees for one specific token pool.
     * Returns the transaction signature.
     */
    public function claimFeesForPool(string $tokenMint): string
    {
        $response = $this->http()->post("{$this->base}/fee-claiming/claim-transactions/v3", [
            'wallet'     => config('bags.treasury_wallet'),
            'token_mint' => $tokenMint,
        ]);

        $this->assertSuccess($response, 'claimFeesForPool');

        $transactions = $response->json('response.transactions') ?? [];
        $lastSig = null;

        foreach ($transactions as $txData) {
            $lastSig = app(SolanaService::class)->signAndSendTransaction($txData);
        }

        return $lastSig ?? '';
    }

    /**
     * Check if a Twitter user has set @BagRoulette in their fee share.
     * Used for creator onboarding verification.
     */
    public function getFeeShareWallet(string $twitterUsername): ?string
    {
        $response = $this->http()->get("{$this->base}/token-launch/fee-share/wallet/v2", [
            'twitter_username' => ltrim($twitterUsername, '@'),
        ]);

        if (!$response->successful() || !$response->json('success')) return null;
        return $response->json('response.wallet');
    }

    public function getPoolByMint(string $mint): ?array
    {
        $response = $this->http()->get("{$this->base}/pools/by-token/{$mint}");
        return $response->successful() ? $response->json('response') : null;
    }

    public function getTokenLifetimeFees(string $mint): float
    {
        $response = $this->http()->get("{$this->base}/token-launch/lifetime-fees/{$mint}");
        return (float) ($response->json('response.total_sol') ?? 0);
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
