<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TokenPool;
use App\Services\BagsApiService;
use Illuminate\Support\Facades\Log;

class SyncTokenPools extends Command
{
    protected $signature = 'bagroulette:sync-pools';
    protected $description = 'Sync token pools from Bags API claimable positions';

    public function handle(BagsApiService $bags): void
    {
        $positions = $bags->getClaimablePositionsRaw();

        if (empty($positions)) {
            $this->info('No positions found.');
            return;
        }

        foreach ($positions as $p) {
            $mint = $p['baseMint'];
            $sol  = round(($p['totalClaimableLamportsUserShare'] ?? 0) / 1_000_000_000, 9);

            try {
                $meta = $this->getTokenMeta($mint);
            } catch (\Exception $e) {
                $meta = ['symbol' => '???', 'name' => 'Unknown'];
            }

            TokenPool::updateOrCreate(
                ['token_mint' => $mint],
                [
                    'token_symbol' => $meta['symbol'],
                    'token_name'   => $meta['name'],
                    'active'       => true,
                    'pending_sol'  => $sol,
                ]
            );

            $this->info("Synced: {$mint} ({$meta['symbol']}) - {$sol} SOL pending");
            Log::info("[SyncPools] Synced {$mint}");
        }
    }

    private function getTokenMeta(string $mint): array
    {
        $response = \Illuminate\Support\Facades\Http::post(
            'https://mainnet.helius-rpc.com/?api-key=' . config('bags.helius_api_key'),
            ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'getAsset', 'params' => ['id' => $mint]]
        );

        $meta = $response->json('result.content.metadata');
        return [
            'symbol' => $meta['symbol'] ?? '???',
            'name'   => $meta['name']   ?? 'Unknown',
        ];
    }
}
