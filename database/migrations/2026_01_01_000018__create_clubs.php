<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clubs', function (Blueprint $table) {
            $table->id('club_id');
            $table->string('club_name', 100);
            $table->string('club_street', 100);
            $table->string('club_city', 100);
            $table->string('club_postal_code', 20);
            $table->string('club_number', 50)->nullable()->comment('FFSO club number');
            $table->string('ffso_id', 50)->nullable()->comment('FFSO club ID');
            $table->text('description')->nullable();
            $table->string('club_image')->nullable()->comment('Club logo/image path');

            // Approval workflow
            $table->boolean('is_approved')->default(false);
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            // Track who created the club
            $table->unsignedBigInteger('created_by');

            // Foreign keys
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clubs');
    }
};
