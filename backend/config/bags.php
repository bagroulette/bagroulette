<?php
// config/bags.php

return [
    'api_key'          => env('BAGS_API_KEY'),
    'token_mint'       => env('BAGS_TOKEN_MINT'),
    'treasury_wallet'  => env('BAGS_TREASURY_WALLET'),
    'treasury_keypair' => env('BAGS_TREASURY_KEYPAIR'), // base58 private key, keep in .env ONLY
    'helius_api_key'   => env('HELIUS_API_KEY'),
    'partner_key'      => env('BAGS_PARTNER_KEY'),

    // Protocol fee: 5% stays in treasury
    'protocol_fee_pct' => (float) env('BAGS_PROTOCOL_FEE_PCT', 5.0),

    // Minimum jackpot to trigger draw (in SOL)
    'min_jackpot_sol'  => (float) env('BAGS_MIN_JACKPOT_SOL', 0.001),
];
