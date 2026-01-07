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
        Schema::create('inscriptions_payment', function (Blueprint $table) {
            $table->id('pai_id');
            $table->date('pai_date');
            $table->boolean('pai_is_paid');
            $table->timestamps();
        });
    }

    /**s
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscriptions_payment');
    }
};
