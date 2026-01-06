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
        Schema::create('clubs', function (Blueprint $table) {
            $table->id('club_id');
            $table->string('club_name', 100);
            $table->string('club_street', 100);
            $table->string('club_city', 100);
            $table->string('club_postal_code', 20);
            $table->integer('club_number');
            $table->unsignedBigInteger('adh_id');
            $table->unsignedBigInteger('adh_id_dirigeant');

            $table->foreign('adh_id')->references('adh_id')->on('members')->onDelete('cascade');
            $table->foreign('adh_id_dirigeant')->references('adh_id')->on('members')->onDelete('cascade');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clubs');
    }
};
