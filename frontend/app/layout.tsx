import type { Metadata } from 'next'
import './globals.css'
import { Providers } from './providers'
import { Toaster } from 'react-hot-toast'

export const metadata: Metadata = {
  title: 'BagRoulette — Every Hour · One Holder · One Win',
  description: 'The first provably fair on-chain roulette powered by Bags.fm. Hold tokens, earn a chance to win the jackpot every hour.',
  keywords: ['BagRoulette', 'Bags', 'Solana', 'DeFi', 'crypto roulette', 'fee sharing'],
  openGraph: {
    title: 'BagRoulette',
    description: 'Every Hour · One Holder · One Win',
    url: 'https://bagroulette.xyz',
    siteName: 'BagRoulette',
    images: [{ url: '/og-image.png', width: 1200, height: 630 }],
    type: 'website',
  },
  twitter: {
    card: 'summary_large_image',
    title: 'BagRoulette',
    description: 'Every Hour · One Holder · One Win',
    creator: '@BagRoulette',
    images: ['/og-image.png'],
  },
  icons: { icon: '/favicon.ico', apple: '/apple-touch-icon.png' },
  themeColor: '#0A0812',
}

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en" suppressHydrationWarning>
      <head />
      <body>
        <Providers>
          {children}
          <Toaster
            position="top-right"
            toastOptions={{
              style: {
                background: '#1A1830',
                color: '#fff',
                border: '1px solid rgba(212,175,55,0.3)',
                fontFamily: 'var(--font-body)',
              },
            }}
          />
        </Providers>
      </body>
    </html>
  )
}
