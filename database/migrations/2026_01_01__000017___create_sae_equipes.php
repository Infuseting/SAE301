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
        Schema::create('teams', function (Blueprint $table) {
            $table->id('equ_id');
            $table->string('equ_name', 100);
            $table->string('equ_image')->nullable();
            $table->unsignedBigInteger('adh_id');
            $table->timestamps();

            $table->foreign('adh_id')->references('adh_id')->on('members')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
