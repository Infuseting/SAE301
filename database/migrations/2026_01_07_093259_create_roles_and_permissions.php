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
            \Spatie\Permission\Models\Permission::create(['name' => $permission]);
        }

        // create roles and assign created permissions

        // User Role
        $userRole = \Spatie\Permission\Models\Role::create(['name' => 'user']);
        $userRole->givePermissionTo(['view-clubs', 'create-club']);

        // Club Manager Role
        $clubManagerRole = \Spatie\Permission\Models\Role::create(['name' => 'club-manager']);
        $clubManagerRole->givePermissionTo(['manage-own-club', 'view-clubs']);

        // Admin Role
        $adminRole = \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(\Spatie\Permission\Models\Permission::all());
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Admin Role
        $adminRole = \Spatie\Permission\Models\Role::findByName('admin');
        if($adminRole) $adminRole->delete();

        // Club Manager Role
        $clubManagerRole = \Spatie\Permission\Models\Role::findByName('club-manager');
        if($clubManagerRole) $clubManagerRole->delete();

        // User Role
        $userRole = \Spatie\Permission\Models\Role::findByName('user');
        if($userRole) $userRole->delete();

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
            $p = \Spatie\Permission\Models\Permission::findByName($permission);
            if($p) $p->delete();
        }
    }
};
