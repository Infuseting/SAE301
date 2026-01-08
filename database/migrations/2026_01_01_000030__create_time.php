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
        Schema::create('time', function (Blueprint $table) {

            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('race_id');

            $table->primary(['user_id', 'race_id']);

            $table->float('time_hours');
            $table->float('time_minutes');
            $table->float('time_seconds');
            $table->float('time_total_seconds');
            $table->integer('time_rank');
            $table->integer('time_rank_start');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('race_id')->references('race_id')->on('races')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time');
    }
};
