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
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            
            // Team to which the user is being invited
            $table->unsignedBigInteger('equ_id');
            $table->foreign('equ_id')->references('equ_id')->on('teams')->onDelete('cascade');
            
            // User who sent the invitation
            $table->unsignedBigInteger('inviter_id');
            $table->foreign('inviter_id')->references('id')->on('users')->onDelete('cascade');
            
            // User who will accept/reject (if already registered)
            $table->unsignedBigInteger('invitee_id')->nullable();
            $table->foreign('invitee_id')->references('id')->on('users')->onDelete('set null');
            
            // Email of the invitee (if not yet registered)
            $table->string('email')->nullable();
            
            // Unique token for secure link
            $table->string('token', 64)->unique();
            
            // Invitation status
            $table->enum('status', ['pending', 'accepted', 'rejected', 'expired'])->default('pending');
            
            // When invitation expires
            $table->timestamp('expires_at');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
