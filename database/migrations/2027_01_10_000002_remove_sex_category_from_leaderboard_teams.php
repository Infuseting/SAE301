<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to remove the sex-based category column from leaderboard_teams.
 * Age categories are now used instead for competitive races.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leaderboard_teams', function (Blueprint $table) {
            if (Schema::hasColumn('leaderboard_teams', 'category')) {
                $table->dropColumn('category');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leaderboard_teams', function (Blueprint $table) {
            if (!Schema::hasColumn('leaderboard_teams', 'category')) {
                $table->string('category', 50)->nullable()->comment('Category: Masculin, Féminin, Mixte');
            }
        });
    }
};
