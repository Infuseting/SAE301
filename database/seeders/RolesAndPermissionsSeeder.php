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

        // Create permissions
        $permissions = [
            // User permissions
            'view users',
            'edit users',
            'delete users',
            'view logs',
            'grant role',
            'grant admin',
            'access-admin',
            
            // Club permissions
            'view-clubs',
            'create-club',
            'edit-own-club',
            'delete-own-club',
            'accept-club',
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
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Guest Role (for non-authenticated users)
        $guestRole = Role::firstOrCreate(['name' => 'guest']);
        $guestRole->givePermissionTo(['view-clubs', 'view-raids', 'view-races']);

        // User Role (authenticated users without licence)
        $userRole = Role::firstOrCreate(['name' => 'user']);
        $userRole->givePermissionTo(['view-clubs', 'view-raids', 'view-races']);

        // Adherent Role (users with valid licence)
        $adherentRole = Role::firstOrCreate(['name' => 'adherent']);
        $adherentRole->givePermissionTo([
            'view-clubs',
            'view-raids',
            'view-races',
            'register-to-race',
        ]);

        // Responsable Club Role (inherits from adherent)
        $responsableClubRole = Role::firstOrCreate(['name' => 'responsable-club']);
        $responsableClubRole->givePermissionTo([
            'view-clubs',
            'view-raids',
            'view-races',
            'register-to-race',
            'create-club',
            'edit-own-club',
            'delete-own-club',
        ]);

        // Gestionnaire Raid Role (inherits from adherent)
        $gestionnaireRaidRole = Role::firstOrCreate(['name' => 'gestionnaire-raid']);
        $gestionnaireRaidRole->givePermissionTo([
            'view-clubs',
            'view-raids',
            'view-races',
            'register-to-race',
            'create-raid',
            'edit-own-raid',
            'delete-own-raid',
        ]);

        // Responsable Course Role (inherits from adherent)
        $responsableCourseRole = Role::firstOrCreate(['name' => 'responsable-course']);
        $responsableCourseRole->givePermissionTo([
            'view-clubs',
            'view-raids',
            'view-races',
            'register-to-race',
            'create-race',
            'edit-own-race',
            'delete-own-race',
        ]);

        // Admin Role (all permissions)
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());
    }
}
