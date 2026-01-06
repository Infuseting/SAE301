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
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // create roles and assign created permissions

        // User Role
        $role = Role::firstOrCreate(['name' => 'user']);
        // Users normally don't get admin perms by default

        // Admin Role
        $role = Role::firstOrCreate(['name' => 'admin']);
        $role->givePermissionTo(Permission::all());
    }
}
