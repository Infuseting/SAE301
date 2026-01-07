<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Preemptive fix for duplicate 'id' column issue in has_participate table
 * 
 * The migration 2026_01_01__000031___create_has_participate.php has a bug:
 * it creates both $table->id() and $table->unsignedBigInteger('id')
 * causing a duplicate column name error.
 * 
 * This migration runs BEFORE the buggy one and creates the table correctly,
 * so when the buggy migration runs, it will skip (table already exists).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create the table correctly BEFORE the buggy migration runs
        if (!Schema::hasTable('has_participate')) {
            Schema::create('has_participate', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('adh_id');
                $table->unsignedBigInteger('equ_id');
                $table->unsignedBigInteger('reg_id')->nullable();
                $table->time('par_time')->nullable();
                $table->foreign('equ_id')->references('equ_id')->on('teams')->onDelete('cascade');
                $table->foreign('adh_id')->references('adh_id')->on('members')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't drop the table here - let the original migration handle it
        // This prevents issues with migration order during rollback
    }
};
