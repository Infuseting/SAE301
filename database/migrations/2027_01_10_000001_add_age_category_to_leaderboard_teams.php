<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add age_category_id to leaderboard_teams for competitive race categorization.
     */
    public function up(): void
    {
        Schema::table('leaderboard_teams', function (Blueprint $table) {
            $table->unsignedBigInteger('age_category_id')->nullable()->after('category');
            $table->foreign('age_category_id')
                ->references('id')
                ->on('age_categories')
                ->onDelete('set null');
            
            // Drop unique constraint and create new one including age_category
            $table->dropUnique('unique_team_race');
            $table->unique(['equ_id', 'race_id', 'age_category_id'], 'unique_team_race_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leaderboard_teams', function (Blueprint $table) {
            $table->dropUnique('unique_team_race_category');
            $table->dropForeign(['age_category_id']);
            $table->dropColumn('age_category_id');
            $table->unique(['equ_id', 'race_id'], 'unique_team_race');
        });
    }
};
