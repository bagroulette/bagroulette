'use client'

import { useEffect, useState } from 'react'
import { useWallet } from '@solana/wallet-adapter-react'
import { WalletMultiButton } from '@solana/wallet-adapter-react-ui'
import { rouletteApi, OddsItem } from '../lib/api'

export function OddsPanel() {
  const { publicKey, connected } = useWallet()
  const [odds, setOdds]    = useState<OddsItem[] | null>(null)
  const [loading, setLoad] = useState(false)

  useEffect(() => {
    if (!connected || !publicKey) { setOdds(null); return }
    setLoad(true)
    rouletteApi.odds(publicKey.toBase58())
      .then(r => setOdds(r.data))
      .catch(() => setOdds(null))
      .finally(() => setLoad(false))
  }, [publicKey, connected])

  if (!connected) {
    return (
      <div className="glass p-5 flex flex-col items-center gap-3 text-center">
        <div className="text-2xl">🎰</div>
        <p className="text-sm text-white/60">Connect wallet to see your odds</p>
        <WalletMultiButton />
      </div>
    )
  }

  if (loading) {
    return (
      <div className="glass p-5 flex items-center justify-center gap-2">
        <div className="w-4 h-4 border-2 border-brand-gold/40 border-t-brand-gold rounded-full animate-spin" />
        <span className="text-sm text-white/40">Loading odds…</span>
      </div>
    )
  }

  if (!odds || odds.length === 0) {
    return (
      <div className="glass p-5 text-center">
        <WalletMultiButton />
        <p className="text-xs text-white/30 mt-3">No tokens found in active pools</p>
      </div>
    )
  }

  return (
    <div className="glass p-5 space-y-4">
      <div className="flex items-center justify-between">
        <h3 className="text-xs uppercase tracking-widest text-white/40 font-mono">Your Odds</h3>
        <WalletMultiButton />
      </div>

      {odds.map((item) => {
        const pct      = item.win_probability.toFixed(4)
        const barWidth = Math.min(item.win_probability * 2, 100)
        return (
          <div key={item.token_mint} className="bg-white/5 rounded-xl p-3 space-y-2">
            <div className="flex items-center justify-between">
              <span className="text-sm font-bold text-white">{item.token_symbol}</span>
              <span className="text-brand-gold font-mono font-bold text-sm">{pct}%</span>
            </div>
            <div className="grid grid-cols-2 gap-2 text-xs font-mono">
              <div>
                <p className="text-white/30">Holdings</p>
                <p className="text-white">{item.balance.toLocaleString()}</p>
              </div>
              <div>
                <p className="text-white/30">Jackpot</p>
                <p className="text-white">◎ {item.jackpot_sol.toFixed(4)}</p>
              </div>
            </div>
            <div className="w-full h-1.5 bg-white/10 rounded-full overflow-hidden">
              <div
                className="h-full bg-gradient-to-r from-brand-purple to-brand-gold rounded-full transition-all duration-700"
                style={{ width: `${barWidth}%` }}
              />
            </div>
          </div>
        )
      })}
    </div>
  )
}
