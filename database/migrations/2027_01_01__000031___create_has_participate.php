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
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('equ_id');
        
            $table->foreign('id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('equ_id')->references('equ_id')->on('teams')->onDelete('cascade');
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
