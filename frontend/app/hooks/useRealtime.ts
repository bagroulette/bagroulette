'use client'

import { useEffect, useRef, useState, useCallback } from 'react'
import { io, Socket } from 'socket.io-client'

const WS_URL = process.env.NEXT_PUBLIC_WS_URL || 'http://localhost:6001'

type DrawEvent = {
  winner_wallet: string
  winner_twitter: string | null
  winner_avatar: string | null
  amount_sol: number
  tx_hash: string
  drawn_at: string
  seed_hash: string
}

type JackpotUpdateEvent = {
  amount_sol: number
  holders_count: number
  next_draw_at: string
}

type SpinningEvent = { spinning: boolean; countdown_seconds: number }

interface UseRealtimeReturn {
  isConnected:    boolean
  jackpot:        JackpotUpdateEvent | null
  lastWinner:     DrawEvent | null
  isSpinning:     boolean
  countdown:      number
  onWinner:       (cb: (e: DrawEvent) => void) => void
}

export function useRealtime(): UseRealtimeReturn {
  const socketRef  = useRef<Socket | null>(null)
  const winnerCbRef = useRef<((e: DrawEvent) => void) | null>(null)

  const [isConnected, setConnected] = useState(false)
  const [jackpot,     setJackpot]   = useState<JackpotUpdateEvent | null>(null)
  const [lastWinner,  setWinner]    = useState<DrawEvent | null>(null)
  const [isSpinning,  setSpinning]  = useState(false)
  const [countdown,   setCountdown] = useState(0)

  useEffect(() => {
    const socket = io(WS_URL, {
      transports: ['websocket'],
      reconnectionAttempts: 10,
      reconnectionDelay: 2000,
    })

    socket.on('connect',    () => setConnected(true))
    socket.on('disconnect', () => setConnected(false))

    socket.on('jackpot.update', (data: JackpotUpdateEvent) => setJackpot(data))

    socket.on('draw.spinning', (data: SpinningEvent) => {
      setSpinning(data.spinning)
      setCountdown(data.countdown_seconds)
    })

    socket.on('draw.winner', (data: DrawEvent) => {
      setWinner(data)
      setSpinning(false)
      winnerCbRef.current?.(data)
    })

    socketRef.current = socket
    return () => { socket.disconnect() }
  }, [])

  const onWinner = useCallback((cb: (e: DrawEvent) => void) => {
    winnerCbRef.current = cb
  }, [])

  return { isConnected, jackpot, lastWinner, isSpinning, countdown, onWinner }
}
