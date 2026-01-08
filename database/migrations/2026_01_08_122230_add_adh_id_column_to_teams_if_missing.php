<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration ensures the teams table has the adh_id column.
     * In production, the table may have user_id instead, so we:
     * 1. Add adh_id column if it doesn't exist (nullable first)
     * 2. Migrate data from user_id to adh_id if possible
     * 3. Keep adh_id nullable for backward compatibility with imported users
     */
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            // Check if adh_id column doesn't exist
            if (!Schema::hasColumn('teams', 'adh_id')) {
                // Add adh_id as nullable since we may have existing teams without members
                $table->unsignedBigInteger('adh_id')->nullable()->after('equ_image');
            }
        });

        // If adh_id was just added and user_id exists, try to migrate data
        if (Schema::hasColumn('teams', 'user_id') && Schema::hasColumn('teams', 'adh_id')) {
            // Copy user_id values to adh_id where possible
            // This assumes user_id was previously storing member IDs
            DB::statement('UPDATE teams SET adh_id = user_id WHERE adh_id IS NULL AND user_id IS NOT NULL');
        }

        // Add foreign key constraint only for MySQL (SQLite doesn't support adding FK after table creation in the same way)
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql' && Schema::hasTable('members') && Schema::hasColumn('teams', 'adh_id')) {
            // Check if foreign key already exists using MySQL INFORMATION_SCHEMA
            $foreignKeys = collect(DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'teams' AND REFERENCED_TABLE_NAME = 'members'"));
            
            if ($foreignKeys->isEmpty()) {
                // Only add constraint if we can - some existing data might not have valid member references
                // In that case, we leave adh_id nullable without a foreign key constraint
                try {
                    Schema::table('teams', function (Blueprint $table) {
                        $table->foreign('adh_id')->references('adh_id')->on('members')->onDelete('set null');
                    });
                } catch (\Exception $e) {
                    // Foreign key constraint failed - probably due to data integrity issues
                    // This is fine - we'll work without the constraint
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key if exists (MySQL only)
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            Schema::table('teams', function (Blueprint $table) {
                try {
                    $table->dropForeign(['adh_id']);
                } catch (\Exception $e) {
                    // Constraint might not exist
                }
            });
        }

        // Note: We don't drop the adh_id column on rollback
        // as it might cause data loss
    }
};
