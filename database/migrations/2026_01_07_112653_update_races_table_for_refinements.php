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
        Schema::table('races', function (Blueprint $table) {
            $table->string('race_difficulty')->nullable()->after('dif_id');
            $table->decimal('price_major', 10, 2)->nullable()->after('race_meal_price');
            $table->decimal('price_minor', 10, 2)->nullable()->after('price_major');
            $table->decimal('price_major_adherent', 10, 2)->nullable()->after('price_minor');
            $table->decimal('price_minor_adherent', 10, 2)->nullable()->after('price_major_adherent');
            $table->unsignedBigInteger('dif_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->dropColumn(['race_difficulty', 'price_major', 'price_minor', 'price_major_adherent', 'price_minor_adherent']);
            $table->unsignedBigInteger('dif_id')->nullable(false)->change();
        });
    }
};
