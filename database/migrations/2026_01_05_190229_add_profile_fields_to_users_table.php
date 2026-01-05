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
        Schema::table('users', function (Blueprint $table) {
            $table->date('birth_date')->nullable()->after('email');
            $table->string('address')->nullable()->after('birth_date');
            $table->string('phone')->nullable()->after('address');
            $table->string('license_number')->nullable()->after('phone');
            $table->string('medical_certificate_path')->nullable()->after('license_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'birth_date',
                'address',
                'phone',
                'license_number',
                'medical_certificate_path',
            ]);
        });
    }
};
