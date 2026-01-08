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
        Schema::create('param_teams', function (Blueprint $table) {
            $table->id('pae_id');
            $table->integer('pae_nb_min');
            $table->integer('pae_nb_max');
            $table->integer('pae_team_count_max');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('param_teams');
    }
};
