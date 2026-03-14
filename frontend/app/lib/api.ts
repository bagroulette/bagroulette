import axios from 'axios'

const API_URL = typeof window !== 'undefined' 
  ? '' // browser: pakai relative URL lewat Vercel proxy
  : (process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000')

export const api = axios.create({
  baseURL: `${API_URL}/api`,
  timeout: 10_000,
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
})

// ── Types ──────────────────────────────────────────────────────────────────────
export interface Pool {
  token_mint:    string
  token_symbol:  string
  token_name:    string
  token_image?:  string
  pending_sol:   number
  pending_usd:   number
  total_drawn:   number
  next_draw_at:  string
  holders_count: number
  last_drawn_at: string | null
}
export interface WinnerRecord {
  id:               number
  wallet:           string
  twitter_username: string | null
  twitter_avatar:   string | null
  amount_sol:       number
  tx_hash:          string
  drawn_at:         string
}
export interface DrawHistoryItem extends WinnerRecord {
  token_mint:   string
  token_symbol: string
  total_pool_sol: number
  holders_count:  number
  seed_hash:      string
}
export interface OddsItem {
  token_mint:      string
  token_symbol:    string
  balance:         number
  win_probability: number
  jackpot_sol:     number
  total_holders:   number
}
// ── API client ────────────────────────────────────────────────────────────────
export const rouletteApi = {
  pools:         ()             => api.get<Pool[]>('/v1/pools'),
  pool:          (mint: string) => api.get<Pool>(`/v1/pools/${mint}`),
  history:       (page = 1, mint?: string) =>
    api.get<{ data: DrawHistoryItem[]; total: number }>('/v1/history', {
      params: { page, ...(mint ? { token_mint: mint } : {}) },
    }),
  odds:          (wallet: string) => api.get<OddsItem[]>(`/v1/odds/${wallet}`),
  leaderboard:   ()             => api.get<WinnerRecord[]>('/v1/leaderboard'),
  verify:        (id: number)   => api.get(`/v1/verify/${id}`),
  stats:         ()             => api.get('/v1/stats'),
  verifyCreator: (mint: string) => api.post('/v1/verify-creator', { token_mint: mint }),
}
