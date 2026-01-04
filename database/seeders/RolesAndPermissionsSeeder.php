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
        Permission::create(['name' => 'manage users']);
        Permission::create(['name' => 'view dashboard']);

        // create roles and assign created permissions

        // User Role
        $role = Role::create(['name' => 'user']);
        $role->givePermissionTo('view dashboard');

        // Admin Role
        $role = Role::create(['name' => 'admin']);
        $role->givePermissionTo(Permission::all());
    }
}
