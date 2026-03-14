# 🎰 BagRoulette

> **A fee-sharing roulette platform built on Bags.fm + Solana**  
> Built for the [Bags.fm Hackathon 2026](https://bags.fm/hackathon)

[![Live Demo](https://img.shields.io/badge/Live-bagroulette.vercel.app-gold?style=for-the-badge)](https://bagroulette.vercel.app)
[![Solana](https://img.shields.io/badge/Solana-Mainnet-9945FF?style=for-the-badge&logo=solana)](https://solana.com)
[![Bags.fm](https://img.shields.io/badge/Bags.fm-Partner-orange?style=for-the-badge)](https://bags.fm)

## What is BagRoulette?

BagRoulette is a **zero-setup fee-sharing destination** for any token launched on Bags.fm.

Token creators simply add `@BagRoulette` as a fee recipient. Every hour, one random holder wins the accumulated SOL jackpot — automatically, on-chain, and provably fair.

## How It Works

\`\`\`
1. Creator launches token on Bags.fm
2. Creator adds @BagRoulette in Fee Sharing (any %)
3. Trading fees accumulate in BagRoulette treasury
4. Every hour — 1 random holder wins the jackpot
5. Prize sent on-chain automatically
\`\`\`

## Provably Fair Algorithm

\`\`\`
seed         = sha256(solana_block_hash + unix_timestamp + sorted_holders)
random_point = (seed_as_int / max_int) × total_token_supply
winner       = first holder where cumulative_balance ≥ random_point
\`\`\`

## Features

- ✅ Creator Verification — check if your token is linked
- ✅ Auto Token Pool Detection — new tokens synced every 5 min
- ✅ Hourly On-Chain Draws — fully automated
- ✅ Automatic SOL Prize Payout — sent directly to winner
- ✅ Provably Fair — seed hash stored for every draw
- ✅ Real-time WebSocket Updates
- ✅ Leaderboard & Win Probability

## Tech Stack

| Layer | Technology |
|---|---|
| Frontend | Next.js 14 + Tailwind + Solana Wallet Adapter → Vercel |
| Backend | Laravel 12 + PHP 8.4 → VPS |
| Blockchain | Solana Mainnet via Helius RPC |
| Fee Data | Bags.fm Partner API |
| WebSocket | Laravel Reverb |
| Database | MySQL + Redis |

## API Reference

Base URL: `http://136.243.19.223/api/v1`

| Method | Endpoint | Description |
|---|---|---|
| GET | `/pools` | All active token pools |
| GET | `/pools/{mint}` | Single pool details |
| GET | `/history` | Draw history |
| GET | `/odds/{wallet}` | Win probability for wallet |
| GET | `/leaderboard` | Top winners |
| GET | `/stats` | Global statistics |
| GET | `/verify/{id}` | Verify a draw |
| POST | `/verify-creator` | Check if token is linked |

## Automated Jobs

| Schedule | Job |
|---|---|
| Every 5 min | Sync pending fees from Bags API |
| Every 5 min | Detect new tokens linked to @BagRoulette |
| Every hour | Execute draw + payout winner |
| Daily 3AM | Cleanup old snapshots |

## Live

- 🌐 [bagroulette.vercel.app](https://bagroulette.vercel.app)
- 🐦 [@BagRoulette](https://x.com/BagRoulette)
- 🔗 [API Stats](http://136.243.19.223/api/v1/stats)

## Structure

\`\`\`
bagroulette/
├── frontend/   # Next.js app
└── backend/    # Laravel API
\`\`\`

---

Built with ❤️ for Bags.fm Hackathon 2026
