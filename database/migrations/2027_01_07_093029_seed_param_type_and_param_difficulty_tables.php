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
        // Insert difficulty levels
        DB::table('param_difficulty')->insert([
            ['dif_level' => 'facile', 'created_at' => now(), 'updated_at' => now()],
            ['dif_level' => 'moyen', 'created_at' => now(), 'updated_at' => now()],
            ['dif_level' => 'difficile', 'created_at' => now(), 'updated_at' => now()],
        ]);

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
        DB::table('param_difficulty')->whereIn('dif_level', ['facile', 'moyen', 'difficile'])->delete();
        DB::table('param_type')->whereIn('typ_name', ['compétitif', 'loisir'])->delete();
    }
};
