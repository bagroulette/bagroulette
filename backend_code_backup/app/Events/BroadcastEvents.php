<?php
// app/Events/JackpotUpdated.php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class JackpotUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly float  $amountSol,
        public readonly int    $holdersCount,
        public readonly string $nextDrawAt,
    ) {}

    public function broadcastOn(): array  { return [new Channel('roulette')]; }
    public function broadcastAs(): string { return 'jackpot.update'; }
    public function broadcastWith(): array
    {
        return [
            'amount_sol'    => $this->amountSol,
            'holders_count' => $this->holdersCount,
            'next_draw_at'  => $this->nextDrawAt,
        ];
    }
}

// ─────────────────────────────────────────────────────────────────────────────

<?php
// app/Events/DrawSpinning.php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class DrawSpinning implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly bool $spinning,
        public readonly int  $countdownSeconds,
    ) {}

    public function broadcastOn(): array  { return [new Channel('roulette')]; }
    public function broadcastAs(): string { return 'draw.spinning'; }
    public function broadcastWith(): array
    {
        return [
            'spinning'           => $this->spinning,
            'countdown_seconds'  => $this->countdownSeconds,
        ];
    }
}
