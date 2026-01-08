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
        Schema::table('param_teams', function (Blueprint $table) {
            $table->integer('pae_team_count_min')->default(1)->after('pae_team_count_max');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('param_teams', function (Blueprint $table) {
            $table->dropColumn('pae_team_count_min');
        });
    }
};
