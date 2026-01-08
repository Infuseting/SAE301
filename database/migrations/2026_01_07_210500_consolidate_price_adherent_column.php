<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Consolidates price_major_adherent and price_minor_adherent into a single price_adherent column.
     */
    public function up(): void
    {
        Schema::table('races', function (Blueprint $table) {
            // Add price_adherent column if it doesn't exist
            if (!Schema::hasColumn('races', 'price_adherent')) {
                $table->decimal('price_adherent', 10, 2)->nullable()->after('price_minor');
            }
            
            // Drop old columns if they exist
            if (Schema::hasColumn('races', 'price_major_adherent')) {
                $table->dropColumn('price_major_adherent');
            }
            if (Schema::hasColumn('races', 'price_minor_adherent')) {
                $table->dropColumn('price_minor_adherent');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('races', function (Blueprint $table) {
            // Add back the old columns
            if (!Schema::hasColumn('races', 'price_major_adherent')) {
                $table->decimal('price_major_adherent', 10, 2)->nullable()->after('price_minor');
            }
            if (!Schema::hasColumn('races', 'price_minor_adherent')) {
                $table->decimal('price_minor_adherent', 10, 2)->nullable()->after('price_major_adherent');
            }
            
            // Drop the consolidated column
            if (Schema::hasColumn('races', 'price_adherent')) {
                $table->dropColumn('price_adherent');
            }
        });
    }
};
