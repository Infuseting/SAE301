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
     * Makes adh_id nullable in teams table since user_id is now the primary reference.
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite doesn't support modifying columns, need to recreate the table
            // For SQLite (test environment), we'll use a workaround
            DB::statement('PRAGMA foreign_keys = OFF');
            
            // Create a temporary table with nullable adh_id
            Schema::create('teams_temp', function (Blueprint $table) {
                $table->id('equ_id');
                $table->string('equ_name', 32);
                $table->string('equ_image')->nullable();
                $table->unsignedBigInteger('adh_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamps();
            });
            
            // Copy data from the old table
            DB::statement('INSERT INTO teams_temp (equ_id, equ_name, equ_image, adh_id, user_id, created_at, updated_at) 
                          SELECT equ_id, equ_name, equ_image, adh_id, user_id, created_at, updated_at FROM teams');
            
            // Drop the old table
            Schema::dropIfExists('teams');
            
            // Rename the temp table
            Schema::rename('teams_temp', 'teams');
            
            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            // MySQL - check if column exists first
            if (!Schema::hasColumn('teams', 'adh_id')) {
                // Column doesn't exist, nothing to do
                return;
            }

            // Try to drop foreign key if it exists, modify column, re-add foreign key
            try {
                Schema::table('teams', function (Blueprint $table) {
                    $table->dropForeign(['adh_id']);
                });
            } catch (\Exception $e) {
                // Foreign key doesn't exist, continue
            }

            Schema::table('teams', function (Blueprint $table) {
                $table->unsignedBigInteger('adh_id')->nullable()->change();
            });

            // Re-add foreign key only if members table exists
            if (Schema::hasTable('members')) {
                try {
                    Schema::table('teams', function (Blueprint $table) {
                        $table->foreign('adh_id')->references('adh_id')->on('members')->onDelete('cascade');
                    });
                } catch (\Exception $e) {
                    // Foreign key already exists or can't be created
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite - recreate table with NOT NULL adh_id
            DB::statement('PRAGMA foreign_keys = OFF');
            
            // Create a temporary table with NOT NULL adh_id
            Schema::create('teams_temp', function (Blueprint $table) {
                $table->id('equ_id');
                $table->string('equ_name', 32);
                $table->string('equ_image')->nullable();
                $table->unsignedBigInteger('adh_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamps();
            });
            
            // Copy data from the old table (only rows with non-null adh_id)
            DB::statement('INSERT INTO teams_temp (equ_id, equ_name, equ_image, adh_id, user_id, created_at, updated_at) 
                          SELECT equ_id, equ_name, equ_image, adh_id, user_id, created_at, updated_at FROM teams WHERE adh_id IS NOT NULL');
            
            // Drop the old table
            Schema::dropIfExists('teams');
            
            // Rename the temp table
            Schema::rename('teams_temp', 'teams');
            
            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            // MySQL - drop foreign key first, modify column, re-add foreign key
            Schema::table('teams', function (Blueprint $table) {
                $table->dropForeign(['adh_id']);
            });

            Schema::table('teams', function (Blueprint $table) {
                $table->unsignedBigInteger('adh_id')->nullable(false)->change();
            });

            Schema::table('teams', function (Blueprint $table) {
                $table->foreign('adh_id')->references('adh_id')->on('members')->onDelete('cascade');
            });
        }
    }
};
