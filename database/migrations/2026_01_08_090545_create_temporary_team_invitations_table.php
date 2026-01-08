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
        Schema::create('temporary_team_invitations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('registration_id');
            $table->unsignedBigInteger('inviter_id');
            $table->string('email');
            $table->string('token', 64)->unique();
            $table->enum('status', ['pending', 'accepted', 'rejected', 'expired'])->default('pending');
            $table->timestamp('expires_at');
            $table->timestamps();

            // Foreign keys
            $table->foreign('registration_id')
                ->references('reg_id')
                ->on('race_registrations')
                ->onDelete('cascade');

            $table->foreign('inviter_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // Unique constraint: one invitation per email per registration
            $table->unique(['registration_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temporary_team_invitations');
    }
};
