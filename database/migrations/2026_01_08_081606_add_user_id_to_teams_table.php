<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds user_id column to teams table with foreign key to users.
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        Schema::table('teams', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('equ_image');
        });

        // Add foreign key constraint (MySQL only)
        if ($driver !== 'sqlite') {
            Schema::table('teams', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver !== 'sqlite') {
            Schema::table('teams', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }

        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
};
