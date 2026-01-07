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
        Schema::create('registration', function (Blueprint $table) {
            $table->id('reg_id');
            $table->unsignedBigInteger('equ_id');
            $table->unsignedBigInteger('race_id');
            $table->foreign('equ_id')->references('equ_id')->on('teams')->onDelete('cascade');
            $table->foreign('race_id')->references('race_id')->on('races')->onDelete('cascade');
            $table->unsignedBigInteger('pay_id');
            $table->foreign('pay_id')->references('pai_id')->on('inscriptions_payment')->onDelete('cascade');
            $table->unsignedBigInteger('doc_id');
            $table->foreign('doc_id')->references('doc_id')->on('medical_docs')->onDelete('cascade');

            $table->float('reg_points')->default(0);
            $table->boolean('reg_validated')->default(false);
            $table->integer('reg_dossard')->nullable();
    
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registration');
    }
};
