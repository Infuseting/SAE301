<?php

namespace Tests\Unit;

use App\Models\Member;
use App\Models\MedicalDoc;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Unit tests for Permission assignments
 * 
 * Tests the Spatie Permission package permission functionality:
 * - Permission assignment works correctly
 * - Permissions via roles work
 * - Direct permission assignment works
 * - Permission checking methods work
 */
class PermissionAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    /**
     * Setup test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureRolesAndPermissionsExist();

        // Create a basic user
        $doc = MedicalDoc::factory()->create();
        $member = Member::factory()->create();
        $this->user = User::factory()->create([
            'adh_id' => $member->adh_id,
            'doc_id' => $doc->doc_id,
        ]);
    }

    /**
     * Ensure all required roles and permissions exist
     */
    private function ensureRolesAndPermissionsExist(): void
    {
        $roles = ['guest', 'user', 'adherent', 'responsable-club', 'gestionnaire-raid', 'responsable-course', 'admin'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $permissions = [
            'create-club', 'edit-own-club', 'delete-own-club', 'view-clubs',
            'create-raid', 'edit-own-raid', 'delete-own-raid', 'view-raids',
            'create-race', 'edit-own-race', 'delete-own-race', 'view-races',
            'manage-all-raids', 'manage-all-clubs', 'manage-all-races', 'access-admin',
            'register-to-race'
        ];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Assign permissions to roles
        $responsableClubRole = Role::findByName('responsable-club');
        $responsableClubRole->syncPermissions(['create-club', 'edit-own-club', 'delete-own-club', 'view-clubs', 'create-raid', 'edit-own-raid', 'delete-own-raid', 'view-raids']);

        $responsableCourseRole = Role::findByName('responsable-course');
        $responsableCourseRole->syncPermissions(['create-race', 'edit-own-race', 'delete-own-race', 'view-races', 'view-raids', 'view-clubs', 'register-to-race']);

        $gestionnaireRole = Role::findByName('gestionnaire-raid');
        $gestionnaireRole->syncPermissions(['view-raids', 'edit-own-raid', 'delete-own-raid', 'create-raid', 'view-races', 'view-clubs']);

        $adminRole = Role::findByName('admin');
        $adminRole->syncPermissions(Permission::all());
    }

    /**
     * Test that user can receive permissions via role assignment
     */
    public function test_user_gets_permissions_via_role(): void
    {
        $this->user->syncRoles(['responsable-club']);
        
        $this->assertTrue($this->user->hasPermissionTo('create-club'));
        $this->assertTrue($this->user->hasPermissionTo('edit-own-club'));
        $this->assertTrue($this->user->hasPermissionTo('create-raid'));
    }

    /**
     * Test that user can be assigned permissions directly
     */
    public function test_user_can_have_direct_permission(): void
    {
        $this->user->givePermissionTo('view-raids');
        
        $this->assertTrue($this->user->hasPermissionTo('view-raids'));
    }

    /**
     * Test hasAnyPermission method
     */
    public function test_has_any_permission_works(): void
    {
        $this->user->syncRoles(['responsable-club']);
        
        // User has create-club, so hasAnyPermission should return true
        $this->assertTrue($this->user->hasAnyPermission(['create-club', 'access-admin']));
    }

    /**
     * Test hasAllPermissions method
     */
    public function test_has_all_permissions_works(): void
    {
        $this->user->syncRoles(['responsable-club']);
        
        $this->assertTrue($this->user->hasAllPermissions(['create-club', 'edit-own-club']));
    }

    /**
     * Test that permission can be revoked
     */
    public function test_permission_can_be_revoked(): void
    {
        $this->user->givePermissionTo('view-raids');
        $this->assertTrue($this->user->hasPermissionTo('view-raids'));
        
        $this->user->revokePermissionTo('view-raids');
        
        $this->user->refresh();
        $this->assertFalse($this->user->hasDirectPermission('view-raids'));
    }

    /**
     * Test direct vs role-based permission checking
     */
    public function test_direct_vs_role_permission(): void
    {
        // Give permission directly
        $this->user->givePermissionTo('view-clubs');
        
        // User should have permission but not through a role
        $this->assertTrue($this->user->hasDirectPermission('view-clubs'));
    }

    /**
     * Test getAllPermissions method
     */
    public function test_get_all_permissions(): void
    {
        $this->user->syncRoles(['responsable-course']);
        
        // Get all permissions (direct + via roles)
        $permissions = $this->user->getAllPermissions()->pluck('name');
        
        // Responsable-course has these permissions
        $this->assertContains('create-race', $permissions);
        $this->assertContains('view-races', $permissions);
    }

    /**
     * Test that permissions are inherited properly across roles
     */
    public function test_permission_inheritance(): void
    {
        // Admin should have all permissions
        $this->user->syncRoles(['admin']);
        
        $allPermissions = Permission::all()->pluck('name');
        
        foreach ($allPermissions as $permission) {
            $this->assertTrue(
                $this->user->hasPermissionTo($permission),
                "User with admin role should have permission: {$permission}"
            );
        }
    }

    /**
     * Test gestionnaire-raid specific permissions
     */
    public function test_gestionnaire_raid_has_correct_permissions(): void
    {
        $this->user->syncRoles(['gestionnaire-raid']);
        
        $this->assertTrue($this->user->hasPermissionTo('view-raids'));
        $this->assertTrue($this->user->hasPermissionTo('edit-own-raid'));
        $this->assertTrue($this->user->hasPermissionTo('create-raid'));
    }

    /**
     * Test admin has all manage permissions
     */
    public function test_admin_has_manage_all_permissions(): void
    {
        $this->user->syncRoles(['admin']);
        
        $this->assertTrue($this->user->hasPermissionTo('manage-all-raids'));
        $this->assertTrue($this->user->hasPermissionTo('manage-all-clubs'));
        $this->assertTrue($this->user->hasPermissionTo('manage-all-races'));
        $this->assertTrue($this->user->hasPermissionTo('access-admin'));
    }
}
