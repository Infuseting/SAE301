<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates race_participants table to link runners to specific race registrations.
     * This allows each runner to have different PPS information per race.
     */
    public function up(): void
    {
        Schema::create('race_participants', function (Blueprint $table) {
            $table->id('rpa_id');
            
            // Link to registration (team registration for a specific race)
            $table->unsignedBigInteger('reg_id');
            $table->foreign('reg_id')->references('reg_id')->on('registration')->onDelete('cascade');
            
            // Link to user (the runner)
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // PPS information specific to this race registration
            $table->string('pps_number', 32)->nullable();
            $table->date('pps_expiry')->nullable();
            $table->enum('pps_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->timestamp('pps_verified_at')->nullable();
            
            // Runner's time for this race (if completed)
            $table->time('runner_time')->nullable();
            
            // Runner's bib number for this race
            $table->integer('bib_number')->nullable();
            
            $table->timestamps();
            
            // Ensure a user can only be registered once per registration
            $table->unique(['reg_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('race_participants');
    }
};
