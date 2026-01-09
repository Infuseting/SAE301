<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to add leisure age rules (A, B, C values) to races table.
 * 
 * Leisure race age rules:
 * - A = minimum age for all participants
 * - B = intermediate age threshold  
 * - C = supervisor age requirement
 * 
 * Rule: All participants must be at least A years old.
 * If any participant is under B years old, the team must include
 * someone who is at least C years old (supervisor).
 * Alternatively, everyone must be at least B years old.
 * 
 * Example: A=12, B=16, C=18 means all participants must be 12+,
 * and teams with participants under 16 must have an adult (18+).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('races', function (Blueprint $table) {
            // Leisure age rule values: A <= B <= C
            $table->integer('leisure_age_min')->nullable()->after('price_adherent')
                ->comment('Minimum age (A) for all participants in leisure races');
            $table->integer('leisure_age_intermediate')->nullable()->after('leisure_age_min')
                ->comment('Intermediate age (B) threshold in leisure races');
            $table->integer('leisure_age_supervisor')->nullable()->after('leisure_age_intermediate')
                ->comment('Supervisor age (C) requirement when team has participants under B');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->dropColumn([
                'leisure_age_min',
                'leisure_age_intermediate', 
                'leisure_age_supervisor'
            ]);
        });
    }
};
