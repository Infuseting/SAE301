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
        Schema::create('has_participate', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('race_id');
            $table->unsignedBigInteger('adh_id');
            $table->unsignedBigInteger('reg_id')->nullable();
            $table->time('par_time')->nullable();
            $table->foreign('race_id')->references('race_id')->on('races')->onDelete('cascade');
            $table->foreign('adh_id')->references('adh_id')->on('members')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('has_participate');
    }
};
