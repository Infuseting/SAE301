<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Logic moved to migration: 2026_01_07_093259_create_roles_and_permissions.php
        // AND 2026_01_07_100515_ensure_all_roles_and_permissions_exist.php
        
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Used to have logic here, but now we should rely on migrations.
        // If we want to force re-running logic, we could call certain things,
        // but generally Seeders are for dummy data or initial setup, while Roles/Perms
        // are often better in Migrations for structural integrity across environments.
        
        // However, if the user WANTS to keep the seeder as a "repair" tool or for fresh installs where migrations
        // might not be enough (though they should be), we could leave it.
        // But the user asked to "migrate" which implies moving the logic.
        // So I will reduce this file to just call the migrations or do nothing if migrations handle it.
        
        // Actually, often Seeders invoke the same logic. But since we used firstOrCreate in migration,
        // we don't strictly need it here.
        // I'll leave a minimal comment.
    }
}
