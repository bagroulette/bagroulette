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
        $lamports = (int) round($amount * 1_000_000_000);
        $rpcUrl   = "https://mainnet.helius-rpc.com/?api-key=" . config('bags.helius_api_key');

        $cmd = sprintf(
            'node /root/bagroulette/transfer_sol.js %s %s %s 2>&1',
            escapeshellarg($rpcUrl),
            escapeshellarg($to),
            escapeshellarg((string) $lamports),
        );

        $output = shell_exec($cmd);
        Log::info('[Solana] transferSol: ' . $output);

        if (str_contains((string)$output, 'ERROR:')) {
            throw new Exception('transferSol failed: ' . $output);
        }

        preg_match('/SUCCESS! TX: (\S+)/', (string)$output, $matches);
        return $matches[1] ?? '';
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

    public function signAndSendTransaction(string $base58Tx, string $privateKeyBase58): string
    {
        $base58 = new \StephenHill\Base58();
        $secretKeyBytes = $base58->decode($privateKeyBase58);
        $txBytes = $base58->decode($base58Tx);

        // Ed25519 sign via sodium
        $secretKey32 = substr($secretKeyBytes, 0, 32);
        $publicKey32 = substr($secretKeyBytes, 32, 32);
        $keypair = $secretKey32 . $publicKey32;
        $signature = sodium_crypto_sign_detached($txBytes, $keypair);

        // Insert signature into transaction (byte 1 onwards after num_signatures)
        $signedTx = $txBytes[0] . $signature . substr($txBytes, 1 + 64);
        $encoded = $base58->encode($signedTx);

        return $this->sendSignedTransaction($encoded);
    }

    // ─── Get wallet SOL balance ───────────────────────────────────────────────
    public function getBalance(string $wallet): float
    {
        $result = $this->rpc('getBalance', [$wallet, ['commitment' => 'confirmed']]);
        return $result['value'] / 1_000_000_000;
    }
}
