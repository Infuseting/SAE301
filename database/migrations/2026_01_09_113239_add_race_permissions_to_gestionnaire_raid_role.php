<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Add race management permissions to the gestionnaire-raid role.
     * Raid managers can now create, edit, and delete races within their raids.
     */
    public function up(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $gestionnaireRaidRole = Role::findByName('gestionnaire-raid');
        
        if ($gestionnaireRaidRole) {
            // Add race management permissions
            $gestionnaireRaidRole->givePermissionTo([
                'create-race',
                'edit-own-race',
                'delete-own-race',
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

        $gestionnaireRaidRole = Role::findByName('gestionnaire-raid');
        
        if ($gestionnaireRaidRole) {
            // Remove race management permissions
            $gestionnaireRaidRole->revokePermissionTo([
                'create-race',
                'edit-own-race',
                'delete-own-race',
            ]);
        }
    }
};
