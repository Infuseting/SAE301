<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for race registrations table.
 * Supports both permanent teams and temporary teams (stored as JSON).
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('race_registrations', function (Blueprint $table) {
            $table->id();

            // Race being registered for
            $table->unsignedBigInteger('race_id');
            $table->foreign('race_id')->references('race_id')->on('races')->onDelete('cascade');

            // Permanent team (nullable - only set if using existing team)
            $table->unsignedBigInteger('team_id')->nullable();
            $table->foreign('team_id')->references('equ_id')->on('teams')->onDelete('set null');

            // User who created the registration
            $table->foreignId('registered_by')->constrained('users')->onDelete('cascade');

            // Whether registration uses a temporary team
            $table->boolean('is_temporary_team')->default(false);

            // JSON data for temporary team members
            // Structure: [{ user_id: int|null, email: string, status: 'confirmed'|'pending'|'pending_account' }]
            $table->json('temporary_team_data')->nullable();

            // Whether the person registering is participating
            $table->boolean('is_creator_participating')->default(true);

            // Registration status
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');

            // Payment tracking for future use
            $table->enum('payment_status', ['pending', 'paid', 'refunded'])->default('pending');
            $table->decimal('total_amount', 10, 2)->nullable();

            $table->timestamps();

            // Prevent duplicate registrations
            $table->unique(['race_id', 'registered_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('race_registrations');
    }
};
