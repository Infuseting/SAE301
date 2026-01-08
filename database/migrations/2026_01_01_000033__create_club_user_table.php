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
        Schema::create('club_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('club_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('role', ['member', 'manager'])->default('member');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            // Foreign keys
            $table->foreign('club_id')->references('club_id')->on('clubs')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Ensure unique club-user combinations
            $table->unique(['club_id', 'user_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_user');
    }
};
