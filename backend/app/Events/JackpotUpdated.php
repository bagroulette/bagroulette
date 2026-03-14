<?php
namespace App\Events;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
class JackpotUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;
    public function __construct(
        public readonly string $tokenMint,
        public readonly float  $amountSol,
        public readonly int    $holdersCount,
        public readonly string $nextDrawAt,
    ) {}
    public function broadcastOn(): array  { return [new Channel('roulette')]; }
    public function broadcastAs(): string { return 'jackpot.update'; }
    public function broadcastWith(): array {
        return [
            'token_mint'    => $this->tokenMint,
            'amount_sol'    => $this->amountSol,
            'holders_count' => $this->holdersCount,
            'next_draw_at'  => $this->nextDrawAt,
        ];
    }
}
