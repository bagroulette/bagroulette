'use client'

import { useEffect, useState } from 'react'
import { Navbar }         from './components/Navbar'
import { RouletteWheel }  from './components/RouletteWheel'
import { CountdownTimer } from './components/CountdownTimer'
import { WinnerCard }     from './components/WinnerCard'
import { OddsPanel }      from './components/OddsPanel'
import { useRealtime }    from './hooks/useRealtime'
import { rouletteApi }    from './lib/api'
import type { DrawHistoryItem } from './lib/api'
import toast from 'react-hot-toast'

// ─── Types ────────────────────────────────────────────────────────────────────
interface Pool {
  token_mint:     string
  token_symbol:   string
  token_name:     string
  pending_sol:    number
  pending_usd:    number
  total_drawn:    number
  next_draw_at:   string
  holders_count:  number
  last_drawn_at:  string | null
}

interface Stats {
  total_pools:       number
  total_draws:       number
  total_distributed: number
  biggest_win:       number
  unique_winners:    number
}

export default function Home() {
  const [pools,   setPools]   = useState<Pool[]>([])
  const [history, setHistory] = useState<DrawHistoryItem[]>([])
  const [stats,   setStats]   = useState<Stats | null>(null)
  const [spinning, setSpinning] = useState(false)
  const [verifyMint, setVerifyMint] = useState('')
  const [verifyResult, setVerifyResult] = useState<any>(null)
  const [verifyLoading, setVerifyLoading] = useState(false)

  const rt = useRealtime()

  useEffect(() => {
    rouletteApi.pools().then(r   => setPools(r.data))
    rouletteApi.history(1).then(r => setHistory(r.data.data))
    rouletteApi.stats().then(r   => setStats(r.data))
  }, [])

  useEffect(() => { setSpinning(rt.isSpinning) }, [rt.isSpinning])

  rt.onWinner((e) => {
    toast.custom(() => (
      <div className="glass p-4 flex items-center gap-3 animate-count-up">
        <span className="text-2xl">🏆</span>
        <div>
          <p className="font-bold text-brand-gold text-sm">
            {e.winner_twitter ? `@${e.winner_twitter}` : e.winner_wallet.slice(0,8)+'…'} won!
          </p>
          <p className="text-xs text-white/60 font-mono">◎ {e.amount_sol.toFixed(4)} SOL</p>
        </div>
      </div>
    ), { duration: 8000 })
    rouletteApi.pools().then(r   => setPools(r.data))
    rouletteApi.history(1).then(r => setHistory(r.data.data))
  })

  const handleVerify = async () => {
    if (!verifyMint.trim()) return
    setVerifyLoading(true)
    setVerifyResult(null)
    try {
      const r = await rouletteApi.verifyCreator(verifyMint.trim())
      setVerifyResult(r.data)
    } catch {
      setVerifyResult({ error: 'Token not found or invalid mint address.' })
    } finally {
      setVerifyLoading(false)
    }
  }

  const totalJackpot = pools.reduce((s, p) => s + p.pending_sol, 0)

  return (
    <div className="min-h-screen bg-brand-dark">
      <Navbar isConnected={rt.isConnected} />

      {/* Grid bg */}
      <div className="fixed inset-0 pointer-events-none opacity-[0.03]"
        style={{
          backgroundImage: `linear-gradient(rgba(212,175,55,0.5) 1px,transparent 1px),
                            linear-gradient(90deg,rgba(212,175,55,0.5) 1px,transparent 1px)`,
          backgroundSize: '60px 60px',
        }}
      />

      {/* Winner ticker */}
      {history.length > 0 && (
        <div className="fixed bottom-0 left-0 right-0 z-50 py-1.5 bg-brand-dark/95 ticker-wrap">
          <div className="ticker-inner text-xs font-mono text-brand-gold/50 space-x-12 px-4">
            {history.slice(0, 8).map((h, i) => (
              <span key={i}>
                🏆 [{h.token_symbol}] {h.twitter_username ? `@${h.twitter_username}` : h.wallet?.slice(0,8)+'…'}
                &nbsp;won ◎{h.amount_sol?.toFixed(4)}
              </span>
            ))}
          </div>
        </div>
      )}

      <main className="pt-20 pb-20 px-4">
        <div className="max-w-6xl mx-auto">

          {/* ── Hero ── */}
          <div className="text-center pt-8 pb-12">
            <p className="text-xs font-mono text-brand-gold/50 tracking-widest uppercase mb-3">
              A Bags.fm Fee-Sharing Platform · Solana
            </p>
            <h1 className="text-5xl md:text-7xl font-black tracking-tight text-white mb-4"
              style={{ fontFamily: 'var(--font-display)' }}>
              Bag<span className="text-gradient-gold">Roulette</span>
            </h1>
            <p className="text-white/50 text-base max-w-xl mx-auto leading-relaxed">
              Route your token's trading fees to <span className="text-brand-gold font-mono">@BagRoulette</span>.
              Every hour, one random holder of your token wins the accumulated jackpot.
              <span className="text-white/30"> Zero setup. Fully on-chain. Provably fair.</span>
            </p>

            {/* Stats row */}
            {stats && (
              <div className="flex items-center justify-center gap-8 mt-8 flex-wrap">
                {[
                  { label: 'Active Pools',    value: stats.total_pools },
                  { label: 'Total Draws',     value: stats.total_draws },
                  { label: 'SOL Distributed', value: `◎ ${stats.total_distributed?.toFixed(2)}` },
                  { label: 'Biggest Win',     value: `◎ ${stats.biggest_win?.toFixed(4)}` },
                  { label: 'Unique Winners',  value: stats.unique_winners },
                ].map(s => (
                  <div key={s.label} className="text-center">
                    <p className="text-xl font-bold font-mono text-brand-gold">{s.value}</p>
                    <p className="text-xs text-white/30 uppercase tracking-widest">{s.label}</p>
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* ── Main 3-col layout ── */}
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

            {/* LEFT: Active pools */}
            <div className="space-y-3">
              <h2 className="text-xs uppercase tracking-widest text-white/30 font-mono px-1">
                Active Token Pools ({pools.length})
              </h2>

              {pools.length === 0
                ? Array.from({length: 3}).map((_, i) => (
                    <div key={i} className="glass p-4 h-20 animate-pulse" />
                  ))
                : pools.map(pool => (
                    <PoolCard key={pool.token_mint} pool={pool} isSpinning={spinning} />
                  ))
              }

              {/* Total jackpot */}
              {pools.length > 1 && (
                <div className="glass p-4 border-brand-gold/30 text-center">
                  <p className="text-xs text-white/30 font-mono uppercase tracking-widest">All Pools Total</p>
                  <p className="text-2xl font-bold font-mono text-gradient-gold glow-gold">
                    ◎ {totalJackpot.toFixed(4)}
                  </p>
                </div>
              )}
            </div>

            {/* CENTER: Wheel */}
            <div className="flex flex-col items-center gap-6">
              <div className="glass w-full p-5 text-center">
                <p className="text-xs text-white/30 font-mono uppercase tracking-widest mb-1">
                  Next Draw
                </p>
                <CountdownTimer
                  targetIso={pools[0]?.next_draw_at ?? null}
                  isSpinning={spinning}
                />
              </div>

              <div className="relative flex justify-center animate-float">
                <RouletteWheel
                  isSpinning={spinning}
                  segmentCount={16}
                  onSpinEnd={() => setSpinning(false)}
                />
              </div>

              {/* How to join */}
              <div className="glass w-full p-5 space-y-3">
                <h3 className="text-xs uppercase tracking-widest text-brand-gold/60 font-mono">
                  For Token Creators
                </h3>
                {[
                  { n: '1', text: 'Launch your token on Bags.fm' },
                  { n: '2', text: 'Add @BagRoulette in Fee Sharing (any %)' },
                  { n: '3', text: 'That\'s it. Holders auto-enter every hour.' },
                ].map(s => (
                  <div key={s.n} className="flex items-start gap-3">
                    <span className="w-5 h-5 rounded-full bg-brand-gold/20 text-brand-gold text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">
                      {s.n}
                    </span>
                    <p className="text-sm text-white/60">{s.text}</p>
                  </div>
                ))}

                <a href="https://bags.fm" target="_blank" rel="noopener noreferrer"
                  className="block w-full py-2.5 text-center text-sm font-bold text-black bg-gradient-to-r from-brand-gold/80 to-brand-gold rounded-xl hover:opacity-90 transition-opacity mt-2">
                  Launch Token on Bags.fm ↗
                </a>
              </div>
            </div>

            {/* RIGHT: Odds + History */}
            <div className="space-y-4">
              <OddsPanel />

              <div id="history" className="space-y-2">
                <h3 className="text-xs uppercase tracking-widest text-white/30 font-mono px-1">
                  Recent Winners
                </h3>
                {history.slice(0, 6).map((h, i) => (
                  <WinnerCard key={h.id} winner={h} isLatest={i === 0} />
                ))}
              </div>
            </div>
          </div>

          {/* ── Creator Verification Tool ── */}
          <section id="verify" className="mt-16 glass p-6">
            <h2 className="text-xs uppercase tracking-widest text-white/30 font-mono mb-2">
              Creator Verification
            </h2>
            <p className="text-sm text-white/40 mb-4">
              Check if your token is correctly linked to @BagRoulette fee sharing.
            </p>
            <div className="flex gap-3 max-w-xl">
              <input
                type="text"
                value={verifyMint}
                onChange={e => setVerifyMint(e.target.value)}
                placeholder="Enter your token mint address..."
                className="flex-1 bg-white/5 border border-brand-gold/20 rounded-xl px-4 py-2.5 text-sm font-mono text-white placeholder-white/20 focus:outline-none focus:border-brand-gold/50"
              />
              <button
                onClick={handleVerify}
                disabled={verifyLoading}
                className="px-6 py-2.5 bg-brand-gold/20 border border-brand-gold/30 rounded-xl text-brand-gold text-sm font-bold hover:bg-brand-gold/30 transition-all disabled:opacity-50"
              >
                {verifyLoading ? '…' : 'Check'}
              </button>
            </div>

            {verifyResult && (
              <div className={`mt-4 p-4 rounded-xl font-mono text-sm ${
                verifyResult.error    ? 'bg-red-500/10 border border-red-500/30' :
                verifyResult.is_linked ? 'bg-green-500/10 border border-green-500/30' :
                                        'bg-yellow-500/10 border border-yellow-500/30'
              }`}>
                <p className="font-bold mb-2">{verifyResult.message ?? verifyResult.error}</p>
                {verifyResult.how_to_link && !verifyResult.is_linked && (
                  <p className="text-white/50 text-xs">{verifyResult.how_to_link}</p>
                )}
                {verifyResult.bagroulette_wallet && (
                  <p className="text-xs text-white/30 mt-2">
                    @BagRoulette wallet: <span className="text-brand-gold/70">{verifyResult.bagroulette_wallet}</span>
                  </p>
                )}
              </div>
            )}
          </section>

          {/* ── Provably Fair ── */}
          <section className="mt-8 glass p-6">
            <h2 className="text-xs uppercase tracking-widest text-white/30 font-mono mb-3">
              Provably Fair — How Winners Are Chosen
            </h2>
            <div className="font-mono text-xs text-white/50 space-y-1.5 bg-white/5 rounded-xl p-4">
              <p><span className="text-brand-gold">seed</span> = sha256(solana_block_hash + unix_timestamp + sorted_holders)</p>
              <p><span className="text-brand-gold">random_point</span> = (seed_as_int / max_int) × total_token_supply</p>
              <p><span className="text-brand-gold">winner</span> = first holder where cumulative_balance ≥ random_point</p>
              <p className="text-white/30 pt-1">→ More tokens held = proportionally higher chance. Verify any draw at /api/v1/verify/[id]</p>
            </div>
          </section>

        </div>
      </main>

      <footer className="border-t border-brand-gold/10 py-8 px-4 mb-8">
        <div className="max-w-6xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-white/20 font-mono">
          <span>© 2026 BagRoulette · Built for the Bags.fm Hackathon</span>
          <div className="flex gap-6">
            <a href="https://x.com/BagRoulette" target="_blank" rel="noopener noreferrer" className="hover:text-brand-gold transition-colors">@BagRoulette</a>
            <a href="https://bags.fm/hackathon" target="_blank" rel="noopener noreferrer" className="hover:text-brand-gold transition-colors">Bags Hackathon</a>
            <a href="/api/v1/stats" target="_blank" rel="noopener noreferrer" className="hover:text-brand-gold transition-colors">API</a>
          </div>
        </div>
      </footer>
    </div>
  )
}

// ─── Pool Card component ───────────────────────────────────────────────────────
function PoolCard({ pool, isSpinning }: { pool: Pool; isSpinning: boolean }) {
  return (
    <div className="glass p-4 hover:border-brand-gold/30 transition-all">
      <div className="flex items-center justify-between mb-2">
        <div className="flex items-center gap-2">
          <div className="w-8 h-8 rounded-full bg-brand-purple/60 border border-brand-gold/30 flex items-center justify-center text-brand-gold text-xs font-bold">
            {pool.token_symbol[0]}
          </div>
          <div>
            <p className="text-sm font-bold text-white">{pool.token_symbol}</p>
            <p className="text-xs text-white/30">{pool.holders_count} holders</p>
          </div>
        </div>
        <div className="text-right">
          <p className="text-brand-gold font-mono font-bold text-sm">◎ {pool.pending_sol.toFixed(4)}</p>
          <p className="text-xs text-white/30">${pool.pending_usd?.toFixed(2)} USD</p>
        </div>
      </div>
      {isSpinning && (
        <div className="flex items-center gap-1.5 text-xs text-brand-gold/70 font-mono">
          <span className="w-1.5 h-1.5 rounded-full bg-brand-gold animate-ping" />
          SPINNING NOW
        </div>
      )}
    </div>
  )
}
