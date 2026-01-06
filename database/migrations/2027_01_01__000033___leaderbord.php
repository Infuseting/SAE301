<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leaderboard_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('race_id');
            $table->foreign('race_id')->references('race_id')->on('races')->onDelete('cascade');
            $table->decimal('temps', 10, 2);
            $table->decimal('malus', 10, 2)->default(0);
            $table->decimal('temps_final', 10, 2)->storedAs('temps + malus');
            $table->unique(['user_id', 'race_id'], 'unique_runner_race');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaderboard_results');
    }
};