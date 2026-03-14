<?php
// database/migrations/2024_01_01_000001_create_bagroulette_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Token pools: one row per token whose creator routed fees to us ──
        Schema::create('token_pools', function (Blueprint $table) {
            $table->id();
            $table->string('token_mint', 44)->unique()->index();
            $table->string('token_symbol', 20);
            $table->string('token_name');
            $table->string('token_image')->nullable();
            $table->string('creator_twitter')->nullable();
            $table->decimal('pending_sol', 18, 9)->default(0);  // unclaimed fees
            $table->decimal('total_drawn', 18, 9)->default(0);  // lifetime paid out
            $table->boolean('active')->default(true)->index();
            $table->timestamp('last_drawn_at')->nullable();
            $table->timestamps();
        });

        // ── Draw results: one row per winning draw ───────────────────────────
        Schema::create('draw_results', function (Blueprint $table) {
            $table->id();
            $table->string('token_mint', 44)->index();
            $table->string('token_symbol', 20)->nullable();
            $table->string('winner_wallet', 44)->index();
            $table->string('winner_twitter')->nullable();
            $table->string('winner_avatar')->nullable();
            $table->decimal('amount_sol', 18, 9);
            $table->decimal('pool_sol',   18, 9);
            $table->string('tx_hash', 88)->unique()->nullable();
            $table->string('block_hash', 64);
            $table->string('seed_hash', 64)->unique();
            $table->integer('holders_count')->default(0);
            $table->timestamp('drawn_at')->index();
            $table->timestamps();

            $table->foreign('token_mint')
                  ->references('token_mint')
                  ->on('token_pools');
        });

        // ── Draw holders: snapshot of all holders at draw time (for audit) ───
        Schema::create('draw_holders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('draw_id')->constrained('draw_results')->cascadeOnDelete();
            $table->string('wallet', 44)->index();
            $table->decimal('balance', 28, 9);
            $table->decimal('weight',  10, 8);
            $table->index(['draw_id', 'wallet']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('draw_holders');
        Schema::dropIfExists('draw_results');
        Schema::dropIfExists('token_pools');
    }
};
