# 🎰 BagRoulette

> **A fee-sharing roulette platform built on Bags.fm + Solana**  
> Built for the [Bags.fm Hackathon 2026](https://bags.fm/hackathon)

[![Live Demo](https://img.shields.io/badge/Live-bagroulette.vercel.app-gold?style=for-the-badge)](https://bagroulette.vercel.app)
[![Solana](https://img.shields.io/badge/Solana-Mainnet-9945FF?style=for-the-badge&logo=solana)](https://solana.com)
[![Bags.fm](https://img.shields.io/badge/Bags.fm-Partner-orange?style=for-the-badge)](https://bags.fm)

---

## What is BagRoulette?

BagRoulette is a **zero-setup fee-sharing destination** for any token launched on Bags.fm.

Token creators simply add `@BagRoulette` as a fee recipient when launching their token. From that point on, every hour, one random holder of that token wins the accumulated SOL jackpot — automatically, on-chain, and provably fair.

**BagRoulette never launches its own token.** It is a service any Bags.fm creator can point fees to.

---

## How It Works

```
1. Creator launches token on Bags.fm
2. Creator adds @BagRoulette in Fee Sharing (any %)
3. Trading fees accumulate in BagRoulette treasury
4. Every hour — 1 random holder wins the jackpot
5. Prize sent on-chain automatically
```

### Provably Fair Algorithm

```
seed         = sha256(solana_block_hash + unix_timestamp + sorted_holders)
random_point = (seed_as_int / max_int) × total_token_supply
winner       = first holder where cumulative_balance ≥ random_point
```

> More tokens held = proportionally higher chance of winning.  
> Every draw can be independently verified at `/api/v1/verify/{id}`

---

## Features

- ✅ **Creator Verification** — instantly check if your token is linked to @BagRoulette
- ✅ **Auto Token Pool Detection** — new tokens synced every 5 minutes automatically
- ✅ **Hourly On-Chain Draws** — fully automated, no manual intervention required
- ✅ **Automatic Prize Payout** — SOL sent directly to winner's wallet
- ✅ **Provably Fair** — seed hash stored on-chain for every draw
- ✅ **Real-time Updates** — WebSocket broadcasting via Laravel Reverb
- ✅ **Leaderboard** — track top winners across all token pools
- ✅ **Wallet Odds** — connect wallet to see your win probability

---

## Tech Stack

| Layer | Technology |
|---|---|
| Frontend | Next.js 14 + Tailwind CSS + Solana Wallet Adapter |
| Backend | Laravel 12 + PHP 8.4 |
| Blockchain | Solana (Mainnet) via Helius RPC |
| Fee Data | Bags.fm Partner API |
| WebSocket | Laravel Reverb |
| Database | MySQL + Redis |
| Hosting | Vercel (frontend) + VPS (backend) |

---

## API Reference

Base URL: `http://136.243.19.223/api/v1`

| Method | Endpoint | Description |
|---|---|---|
| GET | `/pools` | All active token pools |
| GET | `/pools/{mint}` | Single pool details |
| GET | `/history` | Draw history (paginated) |
| GET | `/odds/{wallet}` | Win probability for a wallet |
| GET | `/leaderboard` | Top winners |
| GET | `/stats` | Global platform statistics |
| GET | `/verify/{id}` | Verify a specific draw |
| POST | `/verify-creator` | Check if token is linked to @BagRoulette |

### Example: Verify Creator

```bash
curl -X POST http://136.243.19.223/api/v1/verify-creator \
  -H "Content-Type: application/json" \
  -d '{"token_mint": "YOUR_TOKEN_MINT_ADDRESS"}'
```

Response:
```json
{
  "is_linked": true,
  "bagroulette_wallet": "2Aksz7vEVY3bDbHzrQsYVMMk6LwDjPdZmvn5HF4L1muJ",
  "message": "✅ Linked! Your token holders will automatically enter hourly draws."
}
```

---

## Project Structure

```
bagroulette/
├── frontend/                  # Next.js app (deployed on Vercel)
│   ├── app/
│   │   ├── lib/api.ts         # API client
│   │   ├── hooks/             # React hooks (realtime, wallet)
│   │   └── page.tsx           # Main page
│   └── next.config.js         # API proxy rewrites
│
└── backend/                   # Laravel API (deployed on VPS)
    ├── app/
    │   ├── Console/Commands/
    │   │   └── SyncTokenPools.php     # Auto-sync from Bags API
    │   ├── Events/
    │   │   ├── DrawSpinning.php       # WebSocket: draw animation
    │   │   ├── JackpotUpdated.php     # WebSocket: jackpot amount
    │   │   └── WinnerAnnounced.php    # WebSocket: winner reveal
    │   ├── Http/Controllers/Api/
    │   │   └── RouletteController.php # All API endpoints
    │   ├── Models/
    │   │   ├── TokenPool.php          # Token pool state
    │   │   ├── DrawResult.php         # Draw history
    │   │   └── DrawHolder.php         # Holder snapshots (audit)
    │   └── Services/
    │       ├── BagsApiService.php     # Bags.fm API integration
    │       ├── HeliusService.php      # Token holder data
    │       ├── RouletteService.php    # Core draw logic
    │       └── SolanaService.php      # On-chain transactions
    ├── routes/
    │   ├── api.php            # API routes
    │   └── console.php        # Scheduled jobs
    └── database/migrations/   # DB schema
```

---

## Automated Jobs

| Schedule | Job | Description |
|---|---|---|
| Every 5 min | `syncFeeBalances` | Sync pending SOL from Bags API |
| Every 5 min | `bagroulette:sync-pools` | Detect new tokens linked to @BagRoulette |
| Every hour | `executeHourlyDraw` | Run draw for all active pools |
| Daily 3AM | Cleanup | Remove old holder snapshots (7 day retention) |

---

## Local Development

### Backend (Laravel)

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate

# Fill in your .env:
# BAGS_API_KEY=
# BAGS_TREASURY_WALLET=
# BAGS_TREASURY_KEYPAIR=
# HELIUS_API_KEY=

php artisan migrate
php artisan serve
```

### Frontend (Next.js)

```bash
cd frontend
npm install
cp .env.example .env.local

# Fill in your .env.local:
# NEXT_PUBLIC_API_URL=http://localhost:8000
# NEXT_PUBLIC_SOLANA_RPC=https://mainnet.helius-rpc.com/?api-key=YOUR_KEY

npm run dev
```

---

## Environment Variables

### Backend `.env`

| Variable | Description |
|---|---|
| `BAGS_API_KEY` | Bags.fm Partner API key |
| `BAGS_TREASURY_WALLET` | BagRoulette treasury wallet pubkey |
| `BAGS_TREASURY_KEYPAIR` | Treasury wallet private key (base58) |
| `BAGS_MIN_JACKPOT_SOL` | Minimum SOL to trigger a draw (default: 0.001) |
| `BAGS_PROTOCOL_FEE_PCT` | Protocol fee % kept by treasury (default: 5) |
| `HELIUS_API_KEY` | Helius RPC API key |

### Frontend `.env.local`

| Variable | Description |
|---|---|
| `NEXT_PUBLIC_API_URL` | Backend API base URL |
| `NEXT_PUBLIC_SOLANA_RPC` | Solana RPC endpoint |
| `NEXT_PUBLIC_TOKEN_MINT` | (Optional) BagRoulette platform token mint |

---

## Live Stats

- 🌐 Frontend: [bagroulette.vercel.app](https://bagroulette.vercel.app)
- 🔗 API: [136.243.19.223/api/v1/stats](http://136.243.19.223/api/v1/stats)
- 🐦 Twitter: [@BagRoulette](https://x.com/BagRoulette)

---

## Built With ❤️ for Bags.fm Hackathon 2026

> "Route your token's trading fees to @BagRoulette.  
> Every hour, one random holder wins the jackpot.  
> Zero setup. Fully on-chain. Provably fair."
