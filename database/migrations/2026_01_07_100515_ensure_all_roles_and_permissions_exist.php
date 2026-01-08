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
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Missing Permissions
        $permissions = [
            // Club extras
            'edit-own-club',
            'delete-own-club',
            'manage-all-clubs',

            // Raid permissions
            'view-raids',
            'create-raid',
            'edit-own-raid',
            'delete-own-raid',
            'manage-all-raids',

            // Race permissions
            'view-races',
            'create-race',
            'edit-own-race',
            'delete-own-race',
            'manage-all-races',

            // Registration permissions
            'register-to-race',
            
            // User extras
            'access-admin', // Was in seeder, ensure it's here
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission]);
        }

        // 2. Roles and Assignments

        // Guest Role
        $guestRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'guest']);
        $guestRole->givePermissionTo(['view-clubs', 'view-raids', 'view-races']);

        // User Role (Already exists, but ensuring permissions)
        $userRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'user']);
        $userRole->givePermissionTo(['view-clubs', 'view-raids', 'view-races']);

        // Adherent Role
        $adherentRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'adherent']);
        $adherentRole->givePermissionTo([
            'view-clubs',
            'view-raids',
            'view-races',
            'register-to-race',
        ]);

        // Responsable Club Role
        $responsableClubRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'responsable-club']);
        $responsableClubRole->givePermissionTo([
            'view-clubs',
            'view-raids',
            'view-races',
            'register-to-race',
            'create-club',
            'edit-own-club',
            'delete-own-club',
        ]);

        // Gestionnaire Raid Role
        $gestionnaireRaidRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'gestionnaire-raid']);
        $gestionnaireRaidRole->givePermissionTo([
            'view-clubs',
            'view-raids',
            'view-races',
            'register-to-race',
            'create-raid',
            'edit-own-raid',
            'delete-own-raid',
        ]);

        // Responsable Course Role
        $responsableCourseRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'responsable-course']);
        $responsableCourseRole->givePermissionTo([
            'view-clubs',
            'view-raids',
            'view-races',
            'register-to-race',
            'create-race',
            'edit-own-race',
            'delete-own-race',
        ]);

        // Admin Role (Grant ALL)
        $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(\Spatie\Permission\Models\Permission::all());
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Delete ROLES created by THIS migration (not user/admin/club-manager which were in previous)
        $rolesToDelete = [
            'guest',
            'adherent',
            'responsable-club',
            'gestionnaire-raid',
            'responsable-course',
        ];

        foreach ($rolesToDelete as $roleName) {
            $role = \Spatie\Permission\Models\Role::findByName($roleName);
            if ($role) $role->delete();
        }

        // Delete PERMISSIONS created by THIS migration
        $permissionsToDelete = [
            'edit-own-club',
            'delete-own-club',
            'manage-all-clubs',
            'view-raids',
            'create-raid',
            'edit-own-raid',
            'delete-own-raid',
            'manage-all-raids',
            'view-races',
            'create-race',
            'edit-own-race',
            'delete-own-race',
            'manage-all-races',
            'register-to-race',
        ];

        foreach ($permissionsToDelete as $permission) {
            $p = \Spatie\Permission\Models\Permission::findByName($permission);
            if ($p) $p->delete();
        }
    }
};
