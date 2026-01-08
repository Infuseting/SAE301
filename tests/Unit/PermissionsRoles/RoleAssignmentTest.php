<?php

namespace Tests\Unit\PermissionsRoles;

use App\Models\Member;
use App\Models\MedicalDoc;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Unit tests for Role assignments
 * 
 * Tests the Spatie Permission package role functionality:
 * - Role assignment works correctly
 * - Multiple roles can be assigned
 * - Role checking methods work
 * - Role removal works
 */
class RoleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    /**
     * Setup test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureRolesExist();

        // Create a basic user
        $doc = MedicalDoc::factory()->create();
        $member = Member::factory()->create();
        $this->user = User::factory()->create([
            'adh_id' => $member->adh_id,
            'doc_id' => $doc->doc_id,
        ]);
    }

    /**
     * Ensure all required roles exist
     */
    private function ensureRolesExist(): void
    {
        $roles = ['guest', 'user', 'adherent', 'responsable-club', 'gestionnaire-raid', 'responsable-course', 'admin'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    /**
     * Test that a role can be assigned to a user
     */
    public function test_role_can_be_assigned_to_user(): void
    {
        $this->user->syncRoles(['adherent']);
        
        $this->assertTrue($this->user->hasRole('adherent'));
    }

    /**
     * Test that multiple roles can be assigned to a user
     */
    public function test_multiple_roles_can_be_assigned(): void
    {
        $this->user->syncRoles(['adherent', 'responsable-club']);
        
        $this->assertTrue($this->user->hasRole('adherent'));
        $this->assertTrue($this->user->hasRole('responsable-club'));
    }

    /**
     * Test hasAnyRole method works correctly
     */
    public function test_has_any_role_works(): void
    {
        $this->user->syncRoles(['adherent']);
        
        $this->assertTrue($this->user->hasAnyRole(['adherent', 'admin']));
        $this->assertFalse($this->user->hasAnyRole(['admin', 'guest']));
    }

    /**
     * Test hasAllRoles method works correctly
     */
    public function test_has_all_roles_works(): void
    {
        $this->user->syncRoles(['adherent', 'responsable-club']);
        
        $this->assertTrue($this->user->hasAllRoles(['adherent', 'responsable-club']));
        $this->assertFalse($this->user->hasAllRoles(['adherent', 'admin']));
    }

    /**
     * Test that a role can be removed from a user
     */
    public function test_role_can_be_removed(): void
    {
        $this->user->syncRoles(['adherent']);
        $this->assertTrue($this->user->hasRole('adherent'));
        
        $this->user->removeRole('adherent');
        
        // Refresh to clear cached roles
        $this->user->refresh();
        $this->assertFalse($this->user->hasRole('adherent'));
    }

    /**
     * Test that all roles can be synced
     */
    public function test_roles_can_be_synced(): void
    {
        $this->user->syncRoles(['adherent', 'responsable-club', 'admin']);
        
        $this->user->syncRoles(['guest', 'user']);
        
        $this->user->refresh();
        $this->assertTrue($this->user->hasRole('guest'));
        $this->assertTrue($this->user->hasRole('user'));
        $this->assertFalse($this->user->hasRole('admin'));
    }

    /**
     * Test getRoleNames method returns assigned roles
     */
    public function test_get_role_names(): void
    {
        $this->user->syncRoles(['adherent', 'responsable-club']);
        
        $roleNames = $this->user->getRoleNames();
        
        $this->assertContains('adherent', $roleNames);
        $this->assertContains('responsable-club', $roleNames);
    }

    /**
     * Test that admin role gives highest privileges
     */
    public function test_admin_role_is_highest_privilege(): void
    {
        $adminRole = Role::findByName('admin');
        $responsableRole = Role::findByName('responsable-club');
        
        // Admin should have more permissions than responsable-club
        $this->assertGreaterThanOrEqual(
            $responsableRole->permissions->count(),
            $adminRole->permissions->count()
        );
    }

    /**
     * Test guest role has minimal permissions
     */
    public function test_guest_role_has_minimal_permissions(): void
    {
        $guestRole = Role::findByName('guest');
        
        // Guest role should have very few or no permissions
        $this->assertLessThanOrEqual(5, $guestRole->permissions->count());
    }

    /**
     * Test that user without role has no special roles
     */
    public function test_user_without_roles_has_no_roles(): void
    {
        $this->user->syncRoles([]);
        
        $this->assertFalse($this->user->hasRole('admin'));
        $this->assertFalse($this->user->hasRole('responsable-club'));
    }
}
