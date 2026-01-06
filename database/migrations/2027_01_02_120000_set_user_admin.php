<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SetUserAdmin extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ensure the 'admin' role exists in the roles table (Spatie)
        $role = DB::table('roles')->where('name', 'admin')->first();

        if (! $role) {
            $roleId = DB::table('roles')->insertGetId([
                'name' => 'admin',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $roleId = $role->id;
        }

        // Assign role to user id 1 if not already assigned
        $exists = DB::table('model_has_roles')
            ->where('role_id', $roleId)
            ->where('model_id', 1)
            ->where('model_type', 'App\\Models\\User')
            ->exists();

        if (! $exists) {
            DB::table('model_has_roles')->insert([
                'role_id' => $roleId,
                'model_type' => 'App\\Models\\User',
                'model_id' => 1,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove role assignment for user id 1
        DB::table('model_has_roles')
            ->where('model_type', 'App\\Models\\User')
            ->where('model_id', 1)
            ->delete();

        // If the 'admin' role is unused, remove it
        $role = DB::table('roles')->where('name', 'admin')->first();
        if ($role) {
            $count = DB::table('model_has_roles')->where('role_id', $role->id)->count();
            if ($count === 0) {
                DB::table('roles')->where('id', $role->id)->delete();
            }
        }
    }
}
