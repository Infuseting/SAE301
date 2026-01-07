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
        Schema::create('has_category', function (Blueprint $table) {
            $table->unsignedBigInteger('catpd_id');
            $table->unsignedBigInteger('race_id');
            $table->timestamps();
            $table->foreign('catpd_id')->references('catp_id')->on('price_age_category')->onDelete('cascade');
            $table->foreign('race_id')->references('race_id')->on('races')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('has_category');
    }
};
