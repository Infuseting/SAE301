<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('age_categories', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->integer('age_min');
            $table->integer('age_max')->nullable();
            $table->timestamps();
        });

        // Insert age categories
        DB::table('age_categories')->insert([
            ['nom' => 'Benjamins', 'age_min' => 0, 'age_max' => 12, 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Minimes', 'age_min' => 13, 'age_max' => 14, 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Cadets', 'age_min' => 15, 'age_max' => 16, 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Juniors', 'age_min' => 17, 'age_max' => 18, 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Espoirs', 'age_min' => 19, 'age_max' => 20, 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Seniors', 'age_min' => 21, 'age_max' => 39, 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Vétérans', 'age_min' => 70, 'age_max' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('age_categories');
    }
};
