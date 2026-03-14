<?php

use App\Http\Controllers\Api\RouletteController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/pools',              [RouletteController::class, 'pools']);
    Route::get('/pools/{mint}',       [RouletteController::class, 'pool']);
    Route::get('/history',            [RouletteController::class, 'history']);
    Route::get('/odds/{wallet}',      [RouletteController::class, 'odds']);
    Route::get('/leaderboard',        [RouletteController::class, 'leaderboard']);
    Route::get('/verify/{id}',        [RouletteController::class, 'verify']);
    Route::get('/stats',              [RouletteController::class, 'stats']);
    Route::post('/verify-creator',    [RouletteController::class, 'verifyCreator']);
});

Route::get('/health', fn() => response()->json(['status' => 'ok', 'app' => 'BagRoulette']));
