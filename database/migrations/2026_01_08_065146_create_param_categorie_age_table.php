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
        Schema::create('param_categorie_age', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('race_id');
            $table->unsignedBigInteger('age_categorie_id');
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('race_id')->references('race_id')->on('races')->cascadeOnDelete();
            $table->foreign('age_categorie_id')->references('id')->on('age_categories')->cascadeOnDelete();
            
            // Unique constraint pour éviter les doublons
            $table->unique(['race_id', 'age_categorie_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('param_categorie_age');
    }
};
