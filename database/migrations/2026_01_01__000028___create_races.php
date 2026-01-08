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
        Schema::create('races', function (Blueprint $table) {
            $table->id('race_id');
            $table->string('race_name', 100);
            $table->datetime('race_date_start');
            $table->datetime('race_date_end');
            $table->float('race_reduction')->nullable();
            $table->float('race_meal_price')->nullable();
            $table->float('race_duration_minutes')->nullable();

            //attributs FK
            $table->unsignedBigInteger('raid_id');
            $table->foreign('raid_id')->references('raid_id')->on('raids')->onDelete('cascade');

            $table->unsignedBigInteger('adh_id');
            $table->foreign('adh_id')->references('adh_id')->on('members')->onDelete('cascade');

            $table->unsignedBigInteger('pac_id');
            $table->foreign('pac_id')->references('pac_id')->on('param_runners')->onDelete('cascade');
            $table->unsignedBigInteger('pae_id');
            $table->foreign('pae_id')->references('pae_id')->on('param_teams')->onDelete('cascade');
            $table->unsignedBigInteger('dif_id');
            $table->foreign('dif_id')->references('dif_id')->on('param_difficulty')->onDelete('cascade');
            $table->unsignedBigInteger('typ_id');
            $table->foreign('typ_id')->references('typ_id')->on('param_type')->onDelete('cascade');
            $table->string('image_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('races');
    }
};
