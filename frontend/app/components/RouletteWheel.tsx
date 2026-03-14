'use client'

import { useRef, useEffect, useState } from 'react'

interface WheelProps {
  isSpinning: boolean
  winnerIndex?: number
  segmentCount?: number
  onSpinEnd?: () => void
}

const COLORS = [
  ['#4B2E83', '#6B4CA3'],  // purple pair
  ['#9A7B1C', '#D4AF37'],  // gold pair
]

export function RouletteWheel({ isSpinning, winnerIndex = 0, segmentCount = 16, onSpinEnd }: WheelProps) {
  const canvasRef = useRef<HTMLCanvasElement>(null)
  const rotRef    = useRef(0)
  const rafRef    = useRef<number>(0)
  const [finalAngle, setFinalAngle] = useState<number | null>(null)

  const SIZE    = 320
  const CX      = SIZE / 2
  const CY      = SIZE / 2
  const OUTER_R = 150
  const INNER_R = 55
  const SEG     = (2 * Math.PI) / segmentCount

  // Draw wheel at given rotation
  const draw = (ctx: CanvasRenderingContext2D, rotation: number) => {
    ctx.clearRect(0, 0, SIZE, SIZE)

    // Outer ring dots
    ctx.save()
    ctx.translate(CX, CY)
    for (let i = 0; i < 24; i++) {
      const angle = (i / 24) * 2 * Math.PI + rotation
      const r = OUTER_R + 12
      ctx.beginPath()
      ctx.arc(Math.cos(angle) * r, Math.sin(angle) * r, 2.5, 0, Math.PI * 2)
      ctx.fillStyle = i % 3 === 0 ? '#D4AF37' : 'rgba(212,175,55,0.35)'
      ctx.fill()
    }
    ctx.restore()

    // Outer ring border
    ctx.beginPath()
    ctx.arc(CX, CY, OUTER_R + 6, 0, Math.PI * 2)
    ctx.strokeStyle = '#D4AF37'
    ctx.lineWidth = 1.5
    ctx.stroke()

    // Segments
    for (let i = 0; i < segmentCount; i++) {
      const startAngle = i * SEG + rotation
      const endAngle   = startAngle + SEG

      const [c1, c2] = COLORS[i % 2]
      ctx.beginPath()
      ctx.moveTo(CX, CY)
      ctx.arc(CX, CY, OUTER_R, startAngle, endAngle)
      ctx.closePath()
      ctx.fillStyle = i === winnerIndex && finalAngle !== null ? '#F0D060' : c1
      ctx.fill()
      ctx.strokeStyle = 'rgba(212,175,55,0.25)'
      ctx.lineWidth = 0.8
      ctx.stroke()

      // Inner segment line
      ctx.beginPath()
      ctx.moveTo(CX, CY)
      ctx.arc(CX, CY, INNER_R + 20, startAngle, endAngle)
      ctx.closePath()
      ctx.fillStyle = c2
      ctx.fill()
    }

    // Inner circle rings
    ;[INNER_R + 22, INNER_R + 4].forEach(r => {
      ctx.beginPath()
      ctx.arc(CX, CY, r, 0, Math.PI * 2)
      ctx.strokeStyle = 'rgba(212,175,55,0.4)'
      ctx.lineWidth = 0.8
      ctx.stroke()
    })

    // Center circle
    ctx.beginPath()
    ctx.arc(CX, CY, INNER_R, 0, Math.PI * 2)
    ctx.fillStyle = '#0D0B1A'
    ctx.fill()
    ctx.strokeStyle = '#D4AF37'
    ctx.lineWidth = 1.5
    ctx.stroke()

    // Center B
    ctx.font = `bold 36px 'Cinzel Decorative', serif`
    ctx.fillStyle = '#D4AF37'
    ctx.textAlign = 'center'
    ctx.textBaseline = 'middle'
    ctx.shadowColor = 'rgba(212,175,55,0.8)'
    ctx.shadowBlur  = 16
    ctx.fillText('B', CX, CY + 1)
    ctx.shadowBlur  = 0
  }

  // Spin animation
  useEffect(() => {
    const canvas = canvasRef.current
    if (!canvas) return
    const ctx = canvas.getContext('2d')!

    if (!isSpinning) {
      draw(ctx, rotRef.current)
      return
    }

    setFinalAngle(null)
    const start     = performance.now()
    const duration  = 7000 + Math.random() * 2000
    const startRot  = rotRef.current
    const extraSpins = 8 + Math.random() * 4
    const target = startRot + extraSpins * 2 * Math.PI + (winnerIndex * SEG)

    const animate = (now: number) => {
      const elapsed  = now - start
      const progress = Math.min(elapsed / duration, 1)
      // Ease out cubic
      const ease   = 1 - Math.pow(1 - progress, 3)
      rotRef.current = startRot + (target - startRot) * ease
      draw(ctx, rotRef.current)

      if (progress < 1) {
        rafRef.current = requestAnimationFrame(animate)
      } else {
        rotRef.current = target % (2 * Math.PI)
        setFinalAngle(rotRef.current)
        onSpinEnd?.()
      }
    }

    rafRef.current = requestAnimationFrame(animate)
    return () => cancelAnimationFrame(rafRef.current)
  }, [isSpinning, winnerIndex])

  // Initial draw
  useEffect(() => {
    const canvas = canvasRef.current
    if (!canvas) return
    const ctx = canvas.getContext('2d')!
    draw(ctx, rotRef.current)
  }, [])

  return (
    <div className="relative inline-flex items-center justify-center">
      {/* Glow behind wheel */}
      <div className="absolute inset-0 rounded-full bg-brand-gold/10 blur-2xl scale-75" />

      {/* Pointer triangle */}
      <div className="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1 z-10">
        <div
          style={{
            width: 0, height: 0,
            borderLeft:  '10px solid transparent',
            borderRight: '10px solid transparent',
            borderTop:   '28px solid #D4AF37',
            filter: 'drop-shadow(0 0 6px rgba(212,175,55,0.9))',
          }}
        />
        <div
          style={{
            position: 'absolute', top: '-32px', left: '50%',
            transform: 'translateX(-50%)',
            fontFamily: "'Cinzel Decorative', serif",
            fontSize: '10px', color: '#D4AF37', letterSpacing: '0.15em',
            textShadow: '0 0 8px rgba(212,175,55,0.9)',
          }}
        >
          BAG
        </div>
      </div>

      <canvas
        ref={canvasRef}
        width={SIZE}
        height={SIZE}
        className={isSpinning ? 'drop-shadow-[0_0_30px_rgba(212,175,55,0.6)]' : ''}
      />
    </div>
  )
}
