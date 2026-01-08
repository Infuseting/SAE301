<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('club_invitations', function (Blueprint $table) {
            $table->id();

            // Club inviting the user
            $table->unsignedBigInteger('club_id');
            $table->foreign('club_id')->references('club_id')->on('clubs')->onDelete('cascade');

            // Manager/admin who sent the invitation
            $table->foreignId('inviter_id')->constrained('users')->onDelete('cascade');

            // Invited user (null if inviting by email)
            $table->foreignId('invitee_id')->nullable()->constrained('users')->onDelete('cascade');

            // Email for non-registered users
            $table->string('email')->nullable();

            // Unique token for invitation links
            $table->string('token', 64)->unique();

            // Invitation status
            $table->enum('status', ['pending', 'accepted', 'rejected', 'expired'])->default('pending');

            // Role to assign upon acceptance
            $table->string('role')->default('member');

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
        Schema::dropIfExists('club_invitations');
    }
};
