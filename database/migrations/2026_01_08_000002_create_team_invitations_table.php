<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for team invitations table.
 * Handles invitations for both permanent teams and registration-based temporary teams.
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('team_invitations', function (Blueprint $table) {
            $table->id();

            // For temporary team invitations (linked to a registration)
            $table->foreignId('registration_id')->nullable()->constrained('race_registrations')->onDelete('cascade');

            // For permanent team invitations
            $table->unsignedBigInteger('team_id')->nullable();
            $table->foreign('team_id')->references('equ_id')->on('teams')->onDelete('cascade');

            // Who sent the invitation
            $table->foreignId('inviter_id')->constrained('users')->onDelete('cascade');

            // Invited user (null if inviting by email)
            $table->foreignId('invitee_id')->nullable()->constrained('users')->onDelete('cascade');

            // Email for non-registered users
            $table->string('email')->nullable();

            // Unique token for invitation links
            $table->string('token', 64)->unique();

            // Invitation status
            $table->enum('status', ['pending', 'accepted', 'rejected', 'expired'])->default('pending');

            // Expiration timestamp
            $table->timestamp('expires_at');

            $table->timestamps();

            // Index for faster lookups
            $table->index(['invitee_id', 'status']);
            $table->index(['email', 'status']);
            $table->index('token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_invitations');
    }
};
