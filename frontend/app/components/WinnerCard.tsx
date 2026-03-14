'use client'

import Image from 'next/image'
import { formatDistanceToNow } from 'date-fns'
import type { WinnerRecord } from '../lib/api'

interface WinnerCardProps {
  winner: WinnerRecord
  isLatest?: boolean
}

function shortWallet(w: string) {
  return `${w.slice(0, 4)}...${w.slice(-4)}`
}

export function WinnerCard({ winner, isLatest }: WinnerCardProps) {
  return (
    <div className={`glass p-4 flex items-center gap-3 transition-all duration-300 ${
      isLatest ? 'border-brand-gold/50 glow-box' : 'hover:border-brand-gold/30'
    }`}>
      {/* Avatar */}
      <div className="relative flex-shrink-0">
        {winner.twitter_avatar ? (
          <Image
            src={winner.twitter_avatar}
            alt={winner.twitter_username ?? 'winner'}
            width={40}
            height={40}
            className="rounded-full border border-brand-gold/40"
          />
        ) : (
          <div className="w-10 h-10 rounded-full bg-brand-purple/60 border border-brand-gold/30 flex items-center justify-center text-brand-gold text-sm font-bold">
            {winner.wallet[0].toUpperCase()}
          </div>
        )}
        {isLatest && (
          <span className="absolute -top-1 -right-1 text-base leading-none">🏆</span>
        )}
      </div>

      {/* Info */}
      <div className="flex-1 min-w-0">
        <p className="text-sm font-semibold text-white truncate">
          {winner.twitter_username ? `@${winner.twitter_username}` : shortWallet(winner.wallet)}
        </p>
        <p className="text-xs text-white/40 font-mono">
          {formatDistanceToNow(new Date(winner.drawn_at), { addSuffix: true })}
        </p>
      </div>

      {/* Amount */}
      <div className="text-right flex-shrink-0">
        <p className="text-brand-gold font-mono font-bold text-sm">
          +◎ {winner.amount_sol.toFixed(4)}
        </p>
        <a
          href={`https://solscan.io/tx/${winner.tx_hash}`}
          target="_blank"
          rel="noopener noreferrer"
          className="text-xs text-white/30 hover:text-brand-gold/70 transition-colors font-mono"
        >
          verify ↗
        </a>
      </div>
    </div>
  )
}
