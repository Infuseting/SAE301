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
        // Remove dif_id foreign key column from races table
        Schema::table('races', function (Blueprint $table) {
            // Drop foreign key constraint if it exists
            if (Schema::hasColumn('races', 'dif_id')) {
                $table->dropForeign(['dif_id']);
                $table->dropColumn('dif_id');
            }
        });

        // Drop param_difficulty table
        Schema::dropIfExists('param_difficulty');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate param_difficulty table
        Schema::create('param_difficulty', function (Blueprint $table) {
            $table->id('dif_id');
            $table->string('dif_level');
            $table->timestamps();
        });

        // Add dif_id column back to races
        Schema::table('races', function (Blueprint $table) {
            $table->unsignedBigInteger('dif_id')->nullable()->after('typ_id');
            $table->foreign('dif_id')->references('dif_id')->on('param_difficulty')->nullOnDelete();
        });
    }
};
