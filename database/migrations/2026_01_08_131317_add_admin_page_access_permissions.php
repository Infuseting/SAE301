<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Migration to add admin page access permissions for role-based admin panel access.
 * 
 * This migration creates permissions that allow users with specific roles to access
 * only their relevant admin pages:
 * - gestionnaire-raid → /admin/raids
 * - responsable-club → /admin/clubs
 * - responsable-course → /admin/races
 * 
 * These permissions are cumulative: a user with multiple roles can access multiple pages.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates admin page access permissions and assigns them to appropriate roles.
     */
    public function up(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create admin page access permissions
        $adminPermissions = [
            'access-admin-raids',
            'access-admin-clubs',
            'access-admin-races',
        ];

        foreach ($adminPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign permissions to roles
        
        // Gestionnaire Raid - can access /admin/raids
        $gestionnaireRaidRole = Role::findByName('gestionnaire-raid');
        if ($gestionnaireRaidRole) {
            $gestionnaireRaidRole->givePermissionTo(['access-admin', 'access-admin-raids']);
        }

        // Responsable Club - can access /admin/clubs
        $responsableClubRole = Role::findByName('responsable-club');
        if ($responsableClubRole) {
            $responsableClubRole->givePermissionTo(['access-admin', 'access-admin-clubs']);
        }

        // Responsable Course - can access /admin/races
        $responsableCourseRole = Role::findByName('responsable-course');
        if ($responsableCourseRole) {
            $responsableCourseRole->givePermissionTo(['access-admin', 'access-admin-races']);
        }

        // Admin has all permissions (already has them via givePermissionTo(all))
        $adminRole = Role::findByName('admin');
        if ($adminRole) {
            $adminRole->givePermissionTo($adminPermissions);
        }
    }

    /**
     * Reverse the migrations.
     * Removes admin page access permissions from roles and deletes the permissions.
     */
    public function down(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissionsToDelete = [
            'access-admin-raids',
            'access-admin-clubs',
            'access-admin-races',
        ];

        // Remove permissions from roles before deleting
        $roles = ['gestionnaire-raid', 'responsable-club', 'responsable-course', 'admin'];
        foreach ($roles as $roleName) {
            try {
                $role = Role::findByName($roleName);
                if ($role) {
                    $role->revokePermissionTo($permissionsToDelete);
                }
            } catch (\Exception $e) {
                // Role doesn't exist, skip
            }
        }

        // Delete permissions
        foreach ($permissionsToDelete as $permissionName) {
            try {
                $permission = Permission::findByName($permissionName);
                if ($permission) {
                    $permission->delete();
                }
            } catch (\Exception $e) {
                // Permission doesn't exist, skip
            }
        }
    }
};
