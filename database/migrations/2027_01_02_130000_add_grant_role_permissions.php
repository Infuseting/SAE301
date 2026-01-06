<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration to add 'grant role' and 'grant admin' permissions
 * and assign them to the admin role.
 */
class AddGrantRolePermissions extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            'grant role',
            'grant admin',
        ];

        // Ensure permissions exist and collect their ids
        $permissionIds = [];
        foreach ($permissions as $perm) {
            $existing = DB::table('permissions')->where('name', $perm)->first();
            if (! $existing) {
                $id = DB::table('permissions')->insertGetId([
                    'name' => $perm,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $permissionIds[] = $id;
            } else {
                $permissionIds[] = $existing->id;
            }
        }

        // Find admin role
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

        // Assign each permission to the admin role
        foreach ($permissionIds as $pid) {
            $exists = DB::table('role_has_permissions')
                ->where('permission_id', $pid)
                ->where('role_id', $roleId)
                ->exists();

            if (! $exists) {
                DB::table('role_has_permissions')->insert([
                    'permission_id' => $pid,
                    'role_id' => $roleId,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissions = [
            'grant role',
            'grant admin',
        ];

        $role = DB::table('roles')->where('name', 'admin')->first();
        if (! $role) {
            return;
        }

        $permissionIds = DB::table('permissions')->whereIn('name', $permissions)->pluck('id')->toArray();

        if (! empty($permissionIds)) {
            DB::table('role_has_permissions')
                ->where('role_id', $role->id)
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }

        // Remove permissions if unused
        foreach ($permissionIds as $pid) {
            $count = DB::table('role_has_permissions')->where('permission_id', $pid)->count();
            $modelCount = DB::table('model_has_permissions')->where('permission_id', $pid)->count();
            if ($count === 0 && $modelCount === 0) {
                DB::table('permissions')->where('id', $pid)->delete();
            }
        }
    }
}
