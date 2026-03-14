'use client'

import { useState, useEffect } from 'react'

interface Countdown { hours: number; minutes: number; seconds: number; total: number }

export function useCountdown(targetIso: string | null): Countdown {
  const calc = () => {
    if (!targetIso) return { hours: 0, minutes: 0, seconds: 0, total: 0 }
    const diff = Math.max(0, Math.floor((new Date(targetIso).getTime() - Date.now()) / 1000))
    return {
      hours:   Math.floor(diff / 3600),
      minutes: Math.floor((diff % 3600) / 60),
      seconds: diff % 60,
      total:   diff,
    }
  }

  const [countdown, setCountdown] = useState<Countdown>(calc)

  useEffect(() => {
    const id = setInterval(() => setCountdown(calc()), 1000)
    return () => clearInterval(id)
  }, [targetIso])

  return countdown
}
