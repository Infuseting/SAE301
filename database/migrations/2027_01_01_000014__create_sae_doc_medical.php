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
        Schema::create('medical_docs', function (Blueprint $table) {
            $table->id('doc_id');
            $table->string('doc_num_pps',32);
            $table->date('doc_end_validity');
            $table->date('doc_date_added');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_docs');
    }
};
