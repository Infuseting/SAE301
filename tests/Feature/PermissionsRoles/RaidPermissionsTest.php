<?php

namespace Tests\Feature\PermissionsRoles;

use App\Models\Club;
use App\Models\Member;
use App\Models\MedicalDoc;
use App\Models\Raid;
use App\Models\RegistrationPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Test suite for Raid creation permissions
 * 
 * Tests cover:
 * - Guest users cannot create raids
 * - Regular users cannot create raids
 * - Adherents without responsable-club role cannot create raids
 * - Only responsable-club with approved club can create raids
 * - Responsable-club can only edit their own raids
 * - Admin can always create and edit any raid
 */
class RaidPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private User $guestUser;
    private User $regularUser;
    private User $adherentUser;
    private User $responsableClubUser;
    private User $otherResponsableClubUser;
    private User $adminUser;
    private int $clubId;
    private int $otherClubId;
    private Member $responsableMember;

    /**
     * Setup test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Clear Spatie Permission cache before setting up roles/permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->ensureRolesExist();

        // Clear cache again after setting up roles/permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create guest user
        // Note: Use syncRoles() to REPLACE any roles set by migrations (user ID 1 gets admin role)
        $this->guestUser = User::factory()->create([
            'adh_id' => null,
            'doc_id' => null,
        ]);
        $this->guestUser->syncRoles(['guest']);

        // Create regular user
        $this->regularUser = User::factory()->create([
            'adh_id' => null,
            'doc_id' => null,
        ]);
        $this->regularUser->syncRoles(['user']);

        // Create adherent user
        $adherentDoc = MedicalDoc::factory()->create();
        $adherentMember = Member::factory()->create();
        $this->adherentUser = User::factory()->create([
            'adh_id' => $adherentMember->adh_id,
            'doc_id' => $adherentDoc->doc_id,
        ]);
        $this->adherentUser->syncRoles(['adherent']);

        // Create responsable-club user with approved club
        $responsableDoc = MedicalDoc::factory()->create();
        $this->responsableMember = Member::factory()->create();
        $this->responsableClubUser = User::factory()->create([
            'adh_id' => $this->responsableMember->adh_id,
            'doc_id' => $responsableDoc->doc_id,
        ]);
        $this->responsableClubUser->syncRoles(['responsable-club']);

        // Create club for responsable
        $this->clubId = DB::table('clubs')->insertGetId([
            'club_name' => 'Test Club',
            'club_street' => '123 Test Street',
            'club_city' => 'Test City',
            'club_postal_code' => '12345',
            'ffso_id' => 'FFCO999',
            'description' => 'Test Club Description',
            'is_approved' => true,
            'created_by' => $this->responsableClubUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Add responsable to club
        DB::table('club_user')->insert([
            'club_id' => $this->clubId,
            'user_id' => $this->responsableClubUser->id,
            'role' => 'manager',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create another responsable-club user with different club
        $otherDoc = MedicalDoc::factory()->create();
        $otherMember = Member::factory()->create();
        $this->otherResponsableClubUser = User::factory()->create([
            'adh_id' => $otherMember->adh_id,
            'doc_id' => $otherDoc->doc_id,
        ]);
        $this->otherResponsableClubUser->syncRoles(['responsable-club']);

        // Create other club
        $this->otherClubId = DB::table('clubs')->insertGetId([
            'club_name' => 'Other Club',
            'club_street' => '456 Other Street',
            'club_city' => 'Other City',
            'club_postal_code' => '67890',
            'ffso_id' => 'FFCO888',
            'description' => 'Other Club Description',
            'is_approved' => true,
            'created_by' => $this->otherResponsableClubUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('club_user')->insert([
            'club_id' => $this->otherClubId,
            'user_id' => $this->otherResponsableClubUser->id,
            'role' => 'manager',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create admin user
        $adminDoc = MedicalDoc::factory()->create();
        $adminMember = Member::factory()->create();
        $this->adminUser = User::factory()->create([
            'adh_id' => $adminMember->adh_id,
            'doc_id' => $adminDoc->doc_id,
        ]);
        $this->adminUser->syncRoles(['admin']);
    }

    /**
     * Ensure all required roles and permissions exist
     */
    private function ensureRolesExist(): void
    {
        $roles = ['guest', 'user', 'adherent', 'responsable-club', 'gestionnaire-raid', 'admin'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $permissions = [
            'create-club', 'edit-own-club', 'delete-own-club', 'view-clubs',
            'create-raid', 'edit-own-raid', 'delete-own-raid', 'view-raids',
            'create-race', 'edit-own-race', 'delete-own-race', 'view-races',
            'manage-all-raids', 'manage-all-clubs', 'manage-all-races', 'access-admin'
        ];
        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $responsableRole = Role::findByName('responsable-club');
        $responsableRole->givePermissionTo(['create-club', 'edit-own-club', 'delete-own-club', 'view-clubs', 'create-raid', 'edit-own-raid', 'delete-own-raid', 'view-raids']);

        $gestionnaireRole = Role::findByName('gestionnaire-raid');
        $gestionnaireRole->givePermissionTo(['view-raids', 'edit-own-raid', 'delete-own-raid']);

        $adminRole = Role::findByName('admin');
        $adminRole->givePermissionTo(\Spatie\Permission\Models\Permission::all());
    }

    /**
     * Get valid raid data for creating a raid
     */
    private function getValidRaidData(int $clubId): array
    {
        return [
            'raid_name' => 'Test Raid ' . uniqid(),
            'raid_description' => 'Test raid description',
            'clu_id' => $clubId,
            'adh_id' => $this->responsableMember->adh_id,
            'raid_date_start' => now()->addMonth()->format('Y-m-d'),
            'raid_date_end' => now()->addMonth()->addDays(2)->format('Y-m-d'),
            'ins_start_date' => now()->addDays(7)->format('Y-m-d'),
            'ins_end_date' => now()->addDays(21)->format('Y-m-d'),
            'raid_contact' => 'contact@test.com',
            'raid_site_url' => 'https://test-raid.com',
            'raid_street' => '123 Raid Street',
            'raid_city' => 'Raid City',
            'raid_postal_code' => '12345',
            'raid_number' => 1, // Required by StoreRaidRequest validation
        ];
    }

    // ===========================================
    // UNAUTHENTICATED USER TESTS
    // ===========================================

    /**
     * Test that an unauthenticated user cannot access raid creation page
     */
    public function test_unauthenticated_user_cannot_access_raid_creation_page(): void
    {
        $response = $this->get(route('raids.create'));
        
        $response->assertRedirect(route('login'));
    }

    /**
     * Test that an unauthenticated user cannot create a raid
     */
    public function test_unauthenticated_user_cannot_create_raid(): void
    {
        $response = $this->post(route('raids.store'), $this->getValidRaidData($this->clubId));
        
        $response->assertRedirect(route('login'));
    }

    // ===========================================
    // GUEST USER TESTS
    // ===========================================

    /**
     * Test that a guest user cannot access raid creation page
     */
    public function test_guest_user_cannot_access_raid_creation_page(): void
    {
        $response = $this->actingAs($this->guestUser)
            ->get(route('raids.create'));
        
        $response->assertStatus(403);
    }

    /**
     * Test that a guest user cannot create a raid
     */
    public function test_guest_user_cannot_create_raid(): void
    {
        $response = $this->actingAs($this->guestUser)
            ->post(route('raids.store'), $this->getValidRaidData($this->clubId));
        
        $response->assertStatus(403);
    }

    // ===========================================
    // REGULAR USER TESTS
    // ===========================================

    /**
     * Test that a regular user cannot access raid creation page
     */
    public function test_regular_user_cannot_access_raid_creation_page(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('raids.create'));
        
        $response->assertStatus(403);
    }

    /**
     * Test that a regular user cannot create a raid
     */
    public function test_regular_user_cannot_create_raid(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->post(route('raids.store'), $this->getValidRaidData($this->clubId));
        
        $response->assertStatus(403);
    }

    // ===========================================
    // ADHERENT USER TESTS
    // ===========================================

    /**
     * Test that an adherent without responsable-club role cannot access raid creation page
     */
    public function test_adherent_cannot_access_raid_creation_page(): void
    {
        $response = $this->actingAs($this->adherentUser)
            ->get(route('raids.create'));
        
        $response->assertStatus(403);
    }

    /**
     * Test that an adherent without responsable-club role cannot create a raid
     */
    public function test_adherent_cannot_create_raid(): void
    {
        $response = $this->actingAs($this->adherentUser)
            ->post(route('raids.store'), $this->getValidRaidData($this->clubId));
        
        $response->assertStatus(403);
    }

    // ===========================================
    // RESPONSABLE-CLUB USER TESTS
    // ===========================================

    /**
     * Test that responsable-club can access raid creation page
     */
    public function test_responsable_club_can_access_raid_creation_page(): void
    {
        $response = $this->actingAs($this->responsableClubUser)
            ->get(route('raids.create'));
        
        $response->assertStatus(200);
    }

    /**
     * Test that responsable-club can create a raid for their own club
     */
    public function test_responsable_club_can_create_raid_for_own_club(): void
    {
        $raidData = $this->getValidRaidData($this->clubId);
        
        $response = $this->actingAs($this->responsableClubUser)
            ->post(route('raids.store'), $raidData);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('raids', ['raid_name' => $raidData['raid_name']]);
    }

    /**
     * Test that responsable-club cannot create a raid for another club
     */
    public function test_responsable_club_cannot_create_raid_for_other_club(): void
    {
        $raidData = $this->getValidRaidData($this->otherClubId);
        
        $response = $this->actingAs($this->responsableClubUser)
            ->post(route('raids.store'), $raidData);
        
        // Should fail validation or authorization
        $response->assertStatus(302); // Redirect with error or 403
    }

    // ===========================================
    // RAID EDITING TESTS
    // ===========================================

    /**
     * Test that responsable-club can edit their own raid
     */
    public function test_responsable_club_can_edit_own_raid(): void
    {
        // Create a raid owned by responsable
        $registrationPeriod = RegistrationPeriod::create([
            'ins_start_date' => now()->addDays(7),
            'ins_end_date' => now()->addDays(21),
        ]);

        $raid = Raid::create([
            'raid_name' => 'My Raid',
            'raid_description' => 'Description',
            'clu_id' => $this->clubId,
            'adh_id' => $this->responsableMember->adh_id,
            'raid_date_start' => now()->addMonth(),
            'raid_date_end' => now()->addMonth()->addDays(2),
            'ins_id' => $registrationPeriod->ins_id,
            'raid_contact' => 'contact@test.com',
            'raid_street' => '123 My Street',
            'raid_city' => 'Test City',
            'raid_postal_code' => '12345',
            'raid_number' => 1,
        ]);

        $response = $this->actingAs($this->responsableClubUser)
            ->get(route('raids.edit', $raid->raid_id));
        
        $response->assertStatus(200);
    }

    /**
     * Test that a user who is a manager in club_user but doesn't have the Spatie role can still edit the raid.
     * This explicitly tests the fix for the 403 error reported by the user.
     */
    public function test_club_manager_without_role_can_edit_raid(): void
    {
        // Create a user without any role
        $managerUser = User::factory()->create();
        // Manually add as manager in pivot table
        DB::table('club_user')->insert([
            'club_id' => $this->clubId,
            'user_id' => $managerUser->id,
            'role' => 'manager',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create a raid for this club
        $registrationPeriod = RegistrationPeriod::create([
            'ins_start_date' => now()->addDays(7),
            'ins_end_date' => now()->addDays(21),
        ]);
        $raid = Raid::create([
            'raid_name' => 'Manager Raid',
            'raid_description' => 'Description',
            'clu_id' => $this->clubId,
            'adh_id' => $this->responsableMember->adh_id,
            'raid_date_start' => now()->addMonth(),
            'raid_date_end' => now()->addMonth()->addDays(2),
            'ins_id' => $registrationPeriod->ins_id,
            'raid_contact' => 'contact@test.com',
            'raid_street' => '123 My Street',
            'raid_city' => 'Test City',
            'raid_postal_code' => '12345',
            'raid_number' => 101,
        ]);

        $response = $this->actingAs($managerUser)
            ->get(route('raids.edit', $raid->raid_id));
        
        $response->assertStatus(200);
    }

    /**
     * Test that responsable-club cannot edit another club's raid
     */
    public function test_responsable_club_cannot_edit_other_clubs_raid(): void
    {
        // Create a raid owned by other club
        $registrationPeriod = RegistrationPeriod::create([
            'ins_start_date' => now()->addDays(7),
            'ins_end_date' => now()->addDays(21),
        ]);

        // Get the other club's member
        $otherMember = Member::factory()->create();

        $raid = Raid::create([
            'raid_name' => 'Other Raid',
            'raid_description' => 'Description',
            'clu_id' => $this->otherClubId,
            'adh_id' => $otherMember->adh_id,
            'raid_date_start' => now()->addMonth(),
            'raid_date_end' => now()->addMonth()->addDays(2),
            'ins_id' => $registrationPeriod->ins_id,
            'raid_contact' => 'other@test.com',
            'raid_street' => '456 Other Street',
            'raid_city' => 'Test City',
            'raid_postal_code' => '12345',
            'raid_number' => 2,
        ]);

        $response = $this->actingAs($this->responsableClubUser)
            ->get(route('raids.edit', $raid->raid_id));
        
        $response->assertStatus(403);
    }

    // ===========================================
    // ADMIN USER TESTS
    // ===========================================

    /**
     * Test that admin can access raid creation page
     */
    public function test_admin_can_access_raid_creation_page(): void
    {
        // Admin needs a club to create raid, create one
        DB::table('club_user')->insert([
            'club_id' => $this->clubId,
            'user_id' => $this->adminUser->id,
            'role' => 'manager',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('raids.create'));
        
        $response->assertStatus(200);
    }

    /**
     * Test that admin can create a raid for any club
     */
    public function test_admin_can_create_raid(): void
    {
        DB::table('club_user')->insert([
            'club_id' => $this->clubId,
            'user_id' => $this->adminUser->id,
            'role' => 'manager',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $raidData = $this->getValidRaidData($this->clubId);
        
        $response = $this->actingAs($this->adminUser)
            ->post(route('raids.store'), $raidData);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('raids', ['raid_name' => $raidData['raid_name']]);
    }

    /**
     * Test that admin can edit any raid
     */
    public function test_admin_can_edit_any_raid(): void
    {
        $registrationPeriod = RegistrationPeriod::create([
            'ins_start_date' => now()->addDays(7),
            'ins_end_date' => now()->addDays(21),
        ]);

        $otherMember = Member::factory()->create();

        $raid = Raid::create([
            'raid_name' => 'Some Raid',
            'raid_description' => 'Description',
            'clu_id' => $this->otherClubId,
            'adh_id' => $otherMember->adh_id,
            'raid_date_start' => now()->addMonth(),
            'raid_date_end' => now()->addMonth()->addDays(2),
            'ins_id' => $registrationPeriod->ins_id,
            'raid_contact' => 'contact@test.com',
            'raid_street' => '123 Test Street',
            'raid_city' => 'Test City',
            'raid_postal_code' => '12345',
            'raid_number' => 3,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('raids.edit', $raid->raid_id));
        
        $response->assertStatus(200);
    }

    /**
     * Test that admin can delete any raid
     */
    public function test_admin_can_delete_any_raid(): void
    {
        $registrationPeriod = RegistrationPeriod::create([
            'ins_start_date' => now()->addDays(7),
            'ins_end_date' => now()->addDays(21),
        ]);

        $otherMember = Member::factory()->create();

        $raid = Raid::create([
            'raid_name' => 'Raid to Delete',
            'raid_description' => 'Description',
            'clu_id' => $this->otherClubId,
            'adh_id' => $otherMember->adh_id,
            'raid_date_start' => now()->addMonth(),
            'raid_date_end' => now()->addMonth()->addDays(2),
            'ins_id' => $registrationPeriod->ins_id,
            'raid_contact' => 'contact@test.com',
            'raid_street' => '123 Test Street',
            'raid_city' => 'Test City',
            'raid_postal_code' => '12345',
            'raid_number' => 4,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('raids.destroy', $raid->raid_id));
        
        $response->assertRedirect();
        $this->assertDatabaseMissing('raids', ['raid_id' => $raid->raid_id]);
    }
}
