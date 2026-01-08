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
        Schema::create('raids', function (Blueprint $table) {
            $table->id('raid_id');
            $table->string('raid_name', 100);
            $table->text('raid_description');
            $table->unsignedBigInteger('adh_id');
            $table->foreign('adh_id')->references('adh_id')->on('members')->onDelete('cascade');
            $table->unsignedBigInteger('clu_id');
            $table->foreign('clu_id')->references('club_id')->on('clubs')->onDelete('cascade');
            $table->unsignedBigInteger('ins_id');
            $table->foreign('ins_id')->references('ins_id')->on('registration_period')->onDelete('cascade');
            $table->datetime('raid_date_start');
            $table->datetime('raid_date_end');
            $table->string('raid_contact', 100);
            $table->string('raid_site_url', 255)->nullable();
            $table->string('raid_image')->nullable();
            $table->string('raid_street', 100);
            $table->string('raid_city', 100);
            $table->string('raid_postal_code', 20);
            $table->integer('raid_number');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raids');
    }
};
