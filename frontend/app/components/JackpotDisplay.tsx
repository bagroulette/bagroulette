'use client'

import { useEffect, useRef } from 'react'

interface JackpotDisplayProps {
  amountSol: number
  amountUsd: number
  isAnimating?: boolean
}

export function JackpotDisplay({ amountSol, amountUsd, isAnimating }: JackpotDisplayProps) {
  const prevRef = useRef(amountSol)

  useEffect(() => {
    prevRef.current = amountSol
  }, [amountSol])

  return (
    <div className="text-center">
      <p className="text-xs font-mono uppercase tracking-widest text-brand-gold/60 mb-1">
        Current Jackpot
      </p>
      <div className={`jackpot-amount text-gradient-gold glow-gold ${isAnimating ? 'animate-count-up' : ''}`}>
        ◎ {amountSol.toFixed(4)}
      </div>
      <p className="text-sm text-white/40 font-mono mt-1">
        ≈ ${amountUsd.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} USD
      </p>
    </div>
  )
}
