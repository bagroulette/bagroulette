<?php
// app/Events/WinnerAnnounced.php

namespace App\Events;

use App\Models\DrawResult;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WinnerAnnounced implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly DrawResult $draw) {}

    public function broadcastOn(): array
    {
        return [new Channel('roulette')];
    }

    public function broadcastAs(): string { return 'draw.winner'; }

    public function broadcastWith(): array
    {
        return [
            'winner_wallet'  => $this->draw->winner_wallet,
            'winner_twitter' => $this->draw->winner_twitter,
            'winner_avatar'  => $this->draw->winner_avatar,
            'amount_sol'     => round($this->draw->amount_sol, 6),
            'tx_hash'        => $this->draw->tx_hash,
            'drawn_at'       => $this->draw->drawn_at->toIso8601String(),
            'seed_hash'      => $this->draw->seed_hash,
        ];
    }
}
