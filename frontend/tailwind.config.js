/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./app/**/*.{js,ts,jsx,tsx}', './components/**/*.{js,ts,jsx,tsx}'],
  theme: {
    extend: {
      colors: {
        brand: {
          gold:   '#D4AF37',
          purple: '#4B2E83',
          dark:   '#0A0812',
          navy:   '#0D0B1A',
        },
      },
      fontFamily: {
        display: ['var(--font-display)', 'serif'],
        body:    ['var(--font-body)', 'sans-serif'],
        mono:    ['var(--font-mono)', 'monospace'],
      },
      animation: {
        'spin-slow':   'spin 8s linear infinite',
        'spin-fast':   'spin 0.3s linear infinite',
        'pulse-gold':  'pulseGold 2s ease-in-out infinite',
        'ticker':      'ticker 30s linear infinite',
        'float':       'float 3s ease-in-out infinite',
        'count-up':    'countUp 0.5s ease-out forwards',
      },
      keyframes: {
        pulseGold: {
          '0%, 100%': { boxShadow: '0 0 20px rgba(212,175,55,0.3)' },
          '50%':      { boxShadow: '0 0 40px rgba(212,175,55,0.7)' },
        },
        ticker: {
          '0%':   { transform: 'translateX(100%)' },
          '100%': { transform: 'translateX(-100%)' },
        },
        float: {
          '0%, 100%': { transform: 'translateY(0)' },
          '50%':      { transform: 'translateY(-8px)' },
        },
        countUp: {
          from: { opacity: '0', transform: 'translateY(10px)' },
          to:   { opacity: '1', transform: 'translateY(0)' },
        },
      },
    },
  },
  plugins: [],
}
