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
        Schema::create('has_licence', function (Blueprint $table) {
            $table->unsignedBigInteger('adh_id');
            $table->unsignedBigInteger('club_id');
            $table->foreign('adh_id')->references('adh_id')->on('members')->onDelete('cascade');
            $table->foreign('club_id')->references('club_id')->on('clubs')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('has_licence');
    }
};
