<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the leaderboard_results table to store individual runner rankings
     * for each race with time, penalties, and final calculated time.
     */
    public function up(): void
    {
        Schema::create('leaderboard_results', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade'); 
            $table->unsignedBigInteger('race_id');
            $table->foreign('race_id')->references('race_id')->on('races')->onDelete('cascade');
            $table->decimal('temps', 10, 2)->comment('Time in seconds'); 
            $table->decimal('malus', 10, 2)->default(0)->comment('Penalty in seconds');
            $table->decimal('temps_final', 10, 2)->storedAs('temps + malus')->comment('Final time (time + penalty) in seconds');
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