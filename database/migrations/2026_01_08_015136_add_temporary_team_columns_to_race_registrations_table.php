<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add temporary team support columns to existing race_registrations table.
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('race_registrations', function (Blueprint $table) {
            // Flag for temporary team
            if (!Schema::hasColumn('race_registrations', 'is_temporary_team')) {
                $table->boolean('is_temporary_team')->default(false)->after('equ_id');
            }

            // JSON data for temporary team members
            if (!Schema::hasColumn('race_registrations', 'temporary_team_data')) {
                $table->json('temporary_team_data')->nullable()->after('is_temporary_team');
            }

            // Whether creator is participating
            if (!Schema::hasColumn('race_registrations', 'is_creator_participating')) {
                $table->boolean('is_creator_participating')->default(true)->after('temporary_team_data');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('race_registrations', function (Blueprint $table) {
            $table->dropColumn(['is_temporary_team', 'temporary_team_data', 'is_creator_participating']);
        });
    }
};
