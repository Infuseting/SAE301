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

        // Create admin page access permissions
        $permissions = [
            'access-admin-clubs',
            'access-admin-raids',
            'access-admin-races',
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign permissions to roles
        
        // Responsable Club - access to admin clubs page
        $responsableClubRole = \Spatie\Permission\Models\Role::findByName('responsable-club');
        if ($responsableClubRole) {
            $responsableClubRole->givePermissionTo([
                'access-admin',
                'access-admin-clubs',
            ]);
        }

        // Gestionnaire Raid - access to admin raids page
        $gestionnaireRaidRole = \Spatie\Permission\Models\Role::findByName('gestionnaire-raid');
        if ($gestionnaireRaidRole) {
            $gestionnaireRaidRole->givePermissionTo([
                'access-admin',
                'access-admin-raids',
            ]);
        }

        // Responsable Course - access to admin races page
        $responsableCourseRole = \Spatie\Permission\Models\Role::findByName('responsable-course');
        if ($responsableCourseRole) {
            $responsableCourseRole->givePermissionTo([
                'access-admin',
                'access-admin-races',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Remove permissions from roles
        $responsableClubRole = \Spatie\Permission\Models\Role::findByName('responsable-club');
        if ($responsableClubRole) {
            $responsableClubRole->revokePermissionTo([
                'access-admin',
                'access-admin-clubs',
            ]);
        }

        $gestionnaireRaidRole = \Spatie\Permission\Models\Role::findByName('gestionnaire-raid');
        if ($gestionnaireRaidRole) {
            $gestionnaireRaidRole->revokePermissionTo([
                'access-admin',
                'access-admin-raids',
            ]);
        }

        $responsableCourseRole = \Spatie\Permission\Models\Role::findByName('responsable-course');
        if ($responsableCourseRole) {
            $responsableCourseRole->revokePermissionTo([
                'access-admin',
                'access-admin-races',
            ]);
        }

        // Delete permissions
        $permissions = [
            'access-admin-clubs',
            'access-admin-raids',
            'access-admin-races',
        ];

        foreach ($permissions as $permissionName) {
            $permission = \Spatie\Permission\Models\Permission::findByName($permissionName);
            if ($permission) {
                $permission->delete();
            }
        }
    }
};
