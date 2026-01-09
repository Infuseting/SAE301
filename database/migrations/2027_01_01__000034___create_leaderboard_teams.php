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
        Schema::create('leaderboard_teams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('equ_id');
            $table->foreign('equ_id')->references('equ_id')->on('teams')->onDelete('cascade');
            $table->unsignedBigInteger('race_id');
            $table->foreign('race_id')->references('race_id')->on('races')->onDelete('cascade');
            $table->decimal('average_temps', 10, 2);
            $table->decimal('average_malus', 10, 2)->default(0);
            $table->decimal('average_temps_final', 10, 2);
            $table->integer('member_count')->default(0);
            $table->integer('points')->nullable()->comment('Points earned based on ranking, null = calculate dynamically');
            $table->string('category', 50)->nullable()->comment('Category: Masculin, Féminin, Mixte');
            $table->string('puce', 50)->nullable()->comment('Chip/puce number from CSV import');
            $table->unique(['equ_id', 'race_id'], 'unique_team_race');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaderboard_teams');
    }
};
