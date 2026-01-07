<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Note: param_difficulty table was removed in migration
     * 2026_01_07_124201_change_difficulty_to_string_in_races.php
     */
    public function up(): void
    {
        // Insert race types
        DB::table('param_type')->insert([
            ['typ_name' => 'compétitif', 'created_at' => now(), 'updated_at' => now()],
            ['typ_name' => 'loisir', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('param_type')->whereIn('typ_name', ['compétitif', 'loisir'])->delete();
    }
};
