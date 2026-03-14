<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a single token whose creator routed fees → @BagRoulette.
 * One TokenPool = one Bags.fm token = one independent roulette game.
 */
class TokenPool extends Model
{
    protected $fillable = [
        'token_mint',
        'token_symbol',
        'token_name',
        'token_image',
        'creator_twitter',
        'pending_sol',
        'total_drawn',
        'active',
        'last_drawn_at',
    ];

    protected $casts = [
        'pending_sol'   => 'float',
        'total_drawn'   => 'float',
        'active'        => 'boolean',
        'last_drawn_at' => 'datetime',
    ];

    public function draws(): HasMany
    {
        return $this->hasMany(DrawResult::class, 'token_mint', 'token_mint');
    }

    public function getDrawCountAttribute(): int
    {
        return $this->draws()->count();
    }
}
