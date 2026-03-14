const { Connection, Keypair, VersionedTransaction } = require('./frontend/node_modules/@solana/web3.js');
const bs58Module = require('./frontend/node_modules/bs58');
const fs = require('fs');
const https = require('https');

const bs58 = bs58Module.default || bs58Module;
const decode = bs58.decode ? bs58.decode.bind(bs58) : bs58Module.decode;

const privateKeyBase58 = fs.readFileSync('/tmp/treasury_key_clean.txt', 'utf8').trim();
const rpcUrl = process.argv[2];
const apiKey = process.argv[3];
const tokenMint = process.argv[4];
const virtualPool = process.argv[5];
const programId = process.argv[6];
const baseMint = process.argv[7];
const quoteMint = process.argv[8];
const treasuryWallet = process.argv[9];

async function main() {
    const connection = new Connection(rpcUrl, 'confirmed');
    const secretKey = decode(privateKeyBase58);
    const keypair = Keypair.fromSecretKey(secretKey);
    console.log('Wallet:', keypair.publicKey.toBase58());

    // Get fresh TX
    const payload = {
        feeClaimer: treasuryWallet,
        tokenMint: tokenMint,
        claimVirtualPoolFees: true,
        virtualPoolAddress: virtualPool,
        isCustomFeeVault: true,
        feeShareProgramId: programId,
        tokenAMint: baseMint,
        tokenBMint: quoteMint,
    };

    const res = await fetch('https://public-api-v2.bags.fm/api/v1/token-launch/claim-txs/v2', {
        method: 'POST',
        headers: { 'x-api-key': apiKey, 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
    });
    const data = await res.json();
    const txBase58 = data.response[0].tx;
    console.log('Got fresh TX');

    const txBytes = decode(txBase58);
    const tx = VersionedTransaction.deserialize(txBytes);
    tx.sign([keypair]);

    const sig = await connection.sendTransaction(tx, { preflightCommitment: 'confirmed' });
    console.log('SUCCESS! TX:', sig);
    console.log('Solscan: https://solscan.io/tx/' + sig);
}

main().catch(e => console.error('ERROR:', e.message));
