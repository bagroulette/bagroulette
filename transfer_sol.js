const { Connection, Keypair, SystemProgram, Transaction, PublicKey, sendAndConfirmTransaction } = require('./frontend/node_modules/@solana/web3.js');
const bs58Module = require('./frontend/node_modules/bs58');
const fs = require('fs');

const bs58 = bs58Module.default || bs58Module;
const decode = bs58.decode ? bs58.decode.bind(bs58) : bs58Module.decode;

const privateKeyBase58 = fs.readFileSync('/tmp/treasury_key_clean.txt', 'utf8').trim();
const rpcUrl = process.argv[2];
const toWallet = process.argv[3];
const lamports = parseInt(process.argv[4]);
const memo = process.argv[5] || 'BagRoulette prize payout';

async function main() {
    const connection = new Connection(rpcUrl, 'confirmed');
    const secretKey = decode(privateKeyBase58);
    const keypair = Keypair.fromSecretKey(secretKey);

    console.log('From:', keypair.publicKey.toBase58());
    console.log('To:', toWallet);
    console.log('Lamports:', lamports);

    const tx = new Transaction().add(
        SystemProgram.transfer({
            fromPubkey: keypair.publicKey,
            toPubkey: new PublicKey(toWallet),
            lamports: lamports,
        })
    );

    const sig = await sendAndConfirmTransaction(connection, tx, [keypair]);
    console.log('SUCCESS! TX:', sig);
    console.log('Solscan: https://solscan.io/tx/' + sig);
}

main().catch(e => console.error('ERROR:', e.message));
