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
        Schema::create('price_age_category', function (Blueprint $table) {
            $table->id('catp_id');
            $table->string('catp_name', 100);
            $table->decimal('catp_price', 8, 2);
            $table->integer('age_min');
            $table->integer('age_max');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_age_category');
    }
};
