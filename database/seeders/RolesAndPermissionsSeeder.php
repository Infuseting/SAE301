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
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        $permissions = [
            'view users',
            'edit users',
            'delete users',
            'view logs',
            'grant role',
            'grant admin',
            'access-admin',
            // Club permissions
            'accept-club',
            'manage-own-club',
            'view-clubs',
            'create-club',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // create roles and assign created permissions

        // User Role
        $userRole = Role::firstOrCreate(['name' => 'user']);
        $userRole->givePermissionTo(['view-clubs', 'create-club']);

        // Club Manager Role
        $clubManagerRole = Role::firstOrCreate(['name' => 'club-manager']);
        $clubManagerRole->givePermissionTo(['manage-own-club', 'view-clubs']);

        // Admin Role
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());
    }
}
