<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrawHolder extends Model
{
    public $timestamps = false;

    protected $fillable = ['draw_id', 'wallet', 'balance', 'weight'];

    protected $casts = [
        'balance' => 'float',
        'weight'  => 'float',
    ];

    public function draw(): BelongsTo
    {
        return $this->belongsTo(DrawResult::class, 'draw_id');
    }
}
