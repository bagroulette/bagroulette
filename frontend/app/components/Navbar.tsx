'use client'

import Link from 'next/link'
import Image from 'next/image'
import { WalletMultiButton } from '@solana/wallet-adapter-react-ui'

interface NavbarProps { isConnected: boolean }

export function Navbar({ isConnected }: NavbarProps) {
  return (
    <nav className="fixed top-0 left-0 right-0 z-50 border-b border-brand-gold/10 bg-brand-dark/80 backdrop-blur-xl">
      <div className="max-w-6xl mx-auto px-4 h-14 flex items-center justify-between">
        {/* Logo */}
        <Link href="/" className="flex items-center gap-2 group">
          <div className="w-8 h-8 rounded-full border border-brand-gold/40 flex items-center justify-center text-brand-gold font-bold text-sm group-hover:border-brand-gold transition-colors">
            B
          </div>
          <span className="text-sm font-bold tracking-wide text-white/90">
            Bag<span className="text-brand-gold">Roulette</span>
          </span>
        </Link>

        {/* Links */}
        <div className="hidden md:flex items-center gap-6 text-sm text-white/50">
          <Link href="#how-it-works" className="hover:text-brand-gold transition-colors">How It Works</Link>
          <Link href="#history" className="hover:text-brand-gold transition-colors">History</Link>
          <Link href="#verify" className="hover:text-brand-gold transition-colors">Verify</Link>
          <a
            href="https://x.com/BagRoulette"
            target="_blank"
            rel="noopener noreferrer"
            className="hover:text-brand-gold transition-colors"
          >
            Twitter
          </a>
        </div>

        {/* Status + Wallet */}
        <div className="flex items-center gap-3">
          {isConnected && (
            <div className="flex items-center gap-1.5 text-xs text-green-400/70 font-mono">
              <span className="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse" />
              Live
            </div>
          )}
          <WalletMultiButton />
        </div>
      </div>
    </nav>
  )
}
