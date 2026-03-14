<?php
// routes/console.php — Laravel 11 style scheduler

use App\Services\RouletteService;
use App\Services\BagsApiService;
use App\Events\JackpotUpdated;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

// ─── MAIN: Execute hourly draw at the top of every hour ──────────────────────
Schedule::call(function () {
    Log::info('[Scheduler] Hourly draw triggered');
    app(RouletteService::class)->executeHourlyDraw();
})->hourlyAt(0)
  ->name("hourly-draw")->withoutOverlapping(10)    // lock for max 10 min
  ->onFailure(function ()  { Log::error('[Scheduler] Draw job FAILED'); })
  ->onSuccess(function ()  { Log::info('[Scheduler] Draw job SUCCESS'); });

// ─── Every 5 min: Update jackpot amount & broadcast ──────────────────────────
Schedule::call(function () {
    try {
        $bags    = app(BagsApiService::class);
        $service = app(RouletteService::class);

        $fees     = $bags->getPendingPartnerFees();
        $state    = $service->getJackpotState();

        Cache::put('jackpot_state', $state, 30);
        Cache::put('jackpot_amount', $fees, 60);

        broadcast(new JackpotUpdated(
            amountSol:    $fees,
            holdersCount: $state['holders_count'],
            nextDrawAt:   $state['next_draw_at'],
        ));
    } catch (\Exception $e) {
        Log::warning('[Scheduler] jackpot update failed: ' . $e->getMessage());
    }
})->everyFiveMinutes()->name("jackpot-update")->withoutOverlapping(4);

// ─── Daily: Clean up old holder snapshots (keep 7 days) ──────────────────────
Schedule::call(function () {
    \App\Models\DrawHolder::whereHas('draw', function ($q) {
        $q->where('drawn_at', '<', now()->subDays(7));
    })->delete();
    Log::info('[Scheduler] Old holder snapshots cleaned');
})->dailyAt('03:00');

// ─── Every 5 min: Sync new token pools from Bags API ─────────────────────────
Schedule::command('bagroulette:sync-pools')
    ->everyFiveMinutes()
    ->name('sync-pools')
    ->withoutOverlapping(4);
