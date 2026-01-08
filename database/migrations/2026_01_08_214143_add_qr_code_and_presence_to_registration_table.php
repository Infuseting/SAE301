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
        Schema::table('registration', function (Blueprint $table) {
            $table->string('qr_code_path')->nullable()->after('reg_dossard');
            $table->boolean('is_present')->default(false)->after('qr_code_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registration', function (Blueprint $table) {
            $table->dropColumn(['qr_code_path', 'is_present']);
        });
    }
};
