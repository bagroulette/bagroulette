<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class SolanaService
{
    private string $rpcUrl;

    public function __construct()
    {
        $key          = config('bags.helius_api_key');
        $this->rpcUrl = "https://mainnet.helius-rpc.com/?api-key={$key}";
    }

    // ─── RPC call helper ──────────────────────────────────────────────────────
    private function rpc(string $method, array $params = []): mixed
    {
        $response = Http::timeout(15)->post($this->rpcUrl, [
            'jsonrpc' => '2.0',
            'id'      => uniqid(),
            'method'  => $method,
            'params'  => $params,
        ]);

        if (!$response->successful()) {
            throw new Exception("Solana RPC {$method} failed: {$response->status()}");
        }

        $result = $response->json();

        if (isset($result['error'])) {
            throw new Exception("Solana RPC error: " . json_encode($result['error']));
        }

        return $result['result'];
    }

    // ─── Get latest block hash (for provably fair seed) ───────────────────────
    public function getLatestBlockhash(): string
    {
        $result = $this->rpc('getLatestBlockhash', [['commitment' => 'finalized']]);
        return $result['value']['blockhash'];
    }

    // ─── Transfer SOL to winner via Bags Send Transaction API ─────────────────
    public function transferSol(string $from, string $to, float $amount): string
    {
        // Build transfer transaction via Bags API
        $lamports = (int) round($amount * 1_000_000_000);

        $response = Http::withHeaders([
            'x-api-key' => config('bags.api_key'),
        ])->post('https://public-api-v2.bags.fm/api/v1/solana/send-transaction', [
            'from'   => $from,
            'to'     => $to,
            'amount' => $lamports,
            'memo'   => 'BagRoulette prize payout',
        ]);

        if (!$response->successful()) {
            throw new Exception('Bags sendTransaction failed: ' . $response->body());
        }

        return $response->json('response.signature');
    }

    // ─── Send a signed transaction ────────────────────────────────────────────
    public function sendSignedTransaction(string $serializedTx): string
    {
        $result = $this->rpc('sendTransaction', [
            $serializedTx,
            ['encoding' => 'base64', 'preflightCommitment' => 'confirmed'],
        ]);

        return $result;
    }

    // ─── Confirm transaction ──────────────────────────────────────────────────
    public function confirmTransaction(string $signature): bool
    {
        $retries = 0;

        while ($retries < 30) {
            $result = $this->rpc('getSignatureStatuses', [[$signature]]);
            $status = $result['value'][0] ?? null;

            if ($status && in_array($status['confirmationStatus'], ['confirmed', 'finalized'])) {
                return true;
            }

            sleep(2);
            $retries++;
        }

        throw new Exception("Transaction {$signature} not confirmed after 60s");
    }

    // ─── Get SOL price in USD (CoinGecko) ────────────────────────────────────
    public function getSolPrice(): float
    {
        return Cache::remember('sol_price_usd', 60, function () {
            try {
                $r = Http::timeout(5)->get(
                    'https://api.coingecko.com/api/v3/simple/price',
                    ['ids' => 'solana', 'vs_currencies' => 'usd']
                );
                return (float) ($r->json('solana.usd') ?? 150.0);
            } catch (Exception $e) {
                return 150.0;
            }
        });
    }

    // ─── Get wallet SOL balance ───────────────────────────────────────────────
    public function getBalance(string $wallet): float
    {
        $result = $this->rpc('getBalance', [$wallet, ['commitment' => 'confirmed']]);
        return $result['value'] / 1_000_000_000;
    }
}
