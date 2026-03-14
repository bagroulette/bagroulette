<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DrawResult extends Model
{
    protected $fillable = [
        'winner_wallet',
        'winner_twitter',
        'winner_avatar',
        'amount_sol',
        'pool_sol',
        'tx_hash',
        'block_hash',
        'seed_hash',
        'holders_count',
        'drawn_at',
    ];

    protected $casts = [
        'amount_sol'    => 'float',
        'pool_sol'      => 'float',
        'holders_count' => 'integer',
        'drawn_at'      => 'datetime',
    ];

    protected $dates = ['drawn_at'];

    // ─── Relations ──────────────────────────────────────────────────────────
    public function holders(): HasMany
    {
        return $this->hasMany(DrawHolder::class, 'draw_id');
    }

    // ─── Public API representation ───────────────────────────────────────────
    public function toPublicArray(): array
    {
        return [
            'id'               => $this->id,
            'wallet'           => $this->winner_wallet,
            'twitter_username' => $this->winner_twitter,
            'twitter_avatar'   => $this->winner_avatar,
            'amount_sol'       => round($this->amount_sol, 6),
            'total_pool_sol'   => round($this->pool_sol, 6),
            'tx_hash'          => $this->tx_hash,
            'holders_count'    => $this->holders_count,
            'seed_hash'        => $this->seed_hash,
            'drawn_at'         => $this->drawn_at?->toIso8601String(),
        ];
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('drawn_at', '>=', now()->subHours($hours));
    }
}
