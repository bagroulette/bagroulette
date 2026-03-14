<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class HeliusService
{
    private string $rpcUrl;
    private string $apiUrl;

    public function __construct()
    {
        $key          = config('bags.helius_api_key');
        $this->rpcUrl = "https://mainnet.helius-rpc.com/?api-key={$key}";
        $this->apiUrl = "https://api.helius.xyz/v0";
    }

    // ─── Get ALL token holders with balances ─────────────────────────────────
    public function getTokenHolders(string $mint): Collection
    {
        $holders = collect();
        $cursor  = null;

        do {
            $body = [
                'jsonrpc' => '2.0',
                'id'      => 'get-holders',
                'method'  => 'getTokenAccounts',
                'params'  => [
                    'mint'   => $mint,
                    'limit'  => 1000,
                    'cursor' => $cursor,
                    'options'=> ['showZeroBalance' => false],
                ],
            ];

            $response = Http::timeout(30)
                ->post($this->rpcUrl, $body);

            if (!$response->successful()) {
                Log::error('[Helius] getTokenHolders failed', ['status' => $response->status()]);
                break;
            }

            $data    = $response->json('result');
            $items   = $data['token_accounts'] ?? [];
            $cursor  = $data['cursor'] ?? null;

            foreach ($items as $account) {
                $balance = (float) ($account['amount'] ?? 0);
                if ($balance <= 0) continue;

                $holders->push([
                    'wallet'           => $account['owner'],
                    'balance'          => $balance,
                    'twitter_username' => null, // enriched separately if needed
                    'twitter_avatar'   => null,
                ]);
            }

        } while ($cursor !== null && $holders->count() < 50000);

        Log::info('[Helius] Holders snapshot', ['count' => $holders->count(), 'mint' => $mint]);

        return $holders->sortByDesc('balance')->values();
    }

    // ─── Get total holder count (cheap) ──────────────────────────────────────
    public function getHoldersCount(string $mint): int
    {
        return $this->getTokenHolders($mint)->count();
    }

    // ─── Enrich holder with Twitter info from Bags fee-share ─────────────────
    public function enrichWithTwitter(array $holder): array
    {
        try {
            $wallet   = $holder['wallet'];
            $response = Http::withHeaders([
                'x-api-key' => config('bags.api_key'),
            ])->get("https://public-api-v2.bags.fm/api/v1/token-launch/fee-share/wallet/v2", [
                'wallet' => $wallet,
            ]);

            if ($response->successful() && $response->json('success')) {
                $platform = $response->json('response.platformData');
                $holder['twitter_username'] = $platform['username']    ?? null;
                $holder['twitter_avatar']   = $platform['avatar_url']  ?? null;
            }
        } catch (Exception $e) {
            Log::debug('[Helius] enrichWithTwitter failed', ['wallet' => $holder['wallet']]);
        }

        return $holder;
    }

    // ─── Get token metadata ───────────────────────────────────────────────────
    public function getTokenMetadata(string $mint): ?array
    {
        $response = Http::timeout(15)
            ->withQueryParameters(['api-key' => config('bags.helius_api_key')])
            ->post("{$this->apiUrl}/token-metadata", [
                'mintAccounts' => [$mint],
            ]);

        return $response->successful() ? $response->json('0') : null;
    }
}
