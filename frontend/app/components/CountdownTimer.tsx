'use client'

import { useCountdown } from '../hooks/useCountdown'

interface CountdownTimerProps {
  targetIso: string | null
  isSpinning: boolean
}

export function CountdownTimer({ targetIso, isSpinning }: CountdownTimerProps) {
  const { hours, minutes, seconds } = useCountdown(targetIso)

  if (isSpinning) {
    return (
      <div className="flex items-center gap-2">
        <span className="relative flex h-3 w-3">
          <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-gold opacity-75" />
          <span className="relative inline-flex rounded-full h-3 w-3 bg-brand-gold" />
        </span>
        <span className="text-brand-gold font-mono text-sm font-bold tracking-widest animate-pulse">
          SPINNING NOW
        </span>
      </div>
    )
  }

  const pad = (n: number) => String(n).padStart(2, '0')

  return (
    <div className="flex flex-col items-center gap-1">
      <p className="text-xs text-white/40 uppercase tracking-widest font-mono">
        Next draw in
      </p>
      <div className="flex items-center gap-1 font-mono">
        {hours > 0 && (
          <>
            <Digit value={pad(hours)} label="hr" />
            <Colon />
          </>
        )}
        <Digit value={pad(minutes)} label="min" />
        <Colon />
        <Digit value={pad(seconds)} label="sec" />
      </div>
    </div>
  )
}

function Digit({ value, label }: { value: string; label: string }) {
  return (
    <div className="flex flex-col items-center">
      <span className="text-2xl font-bold text-white tabular-nums countdown-digit">{value}</span>
      <span className="text-[9px] text-white/30 uppercase tracking-widest">{label}</span>
    </div>
  )
}

function Colon() {
  return <span className="text-2xl font-bold text-brand-gold/50 mb-3 mx-0.5">:</span>
}
