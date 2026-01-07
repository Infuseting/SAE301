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
        // Skip if table already exists (created by the preemptive fix migration)
        if (Schema::hasTable('has_participate')) {
            return;
        }

        Schema::create('has_participate', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('equ_id');
            $table->unsignedBigInteger('reg_id')->nullable();
            $table->time('par_time')->nullable();
            $table->foreign('equ_id')->references('equ_id')->on('teams')->onDelete('cascade');
            $table->foreign('id')->references('adh_id')->on('members')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('has_participate');
    }
};
