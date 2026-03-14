<?php
// database/migrations/2024_01_01_000001_create_draw_results_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('draw_results', function (Blueprint $table) {
            $table->id();
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
        });

        Schema::create('draw_holders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('draw_id')->constrained('draw_results')->cascadeOnDelete();
            $table->string('wallet', 44)->index();
            $table->decimal('balance', 28, 9);
            $table->decimal('weight',  10, 8); // 0.00000001 to 1.00000000
            $table->index(['draw_id', 'wallet']);
        });

        // Notification preferences
        Schema::create('subscriber_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('wallet', 44)->unique();
            $table->string('email')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('draw_holders');
        Schema::dropIfExists('draw_results');
        Schema::dropIfExists('subscriber_notifications');
    }
};
