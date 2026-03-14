<?php
namespace App\Events;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
class DrawSpinning implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;
    public function __construct(
        public readonly bool   $spinning,
        public readonly int    $duration,
        public readonly string $tokenMint,
    ) {}
    public function broadcastOn(): array  { return [new Channel('roulette')]; }
    public function broadcastAs(): string { return 'draw.spinning'; }
    public function broadcastWith(): array {
        return [
            'spinning'   => $this->spinning,
            'duration'   => $this->duration,
            'token_mint' => $this->tokenMint,
        ];
    }
}
