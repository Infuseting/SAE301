<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration is no longer needed as PPS is now managed
     * per race registration in the race_participants table.
     */
    public function up(): void
    {
        // Migration no longer needed - PPS moved to race_participants table
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to reverse
    }
};
