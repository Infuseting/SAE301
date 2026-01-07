<?php

namespace Tests\Feature\PermissionsRoles;

use App\Models\Club;
use App\Models\Member;
use App\Models\MedicalDoc;
use App\Models\Race;
use App\Models\Raid;
use App\Models\ParamRunner;
use App\Models\ParamTeam;
use App\Models\ParamType;

use App\Models\RegistrationPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Test suite for Admin role full access permissions
 * 
 * Tests cover:
 * - Admin can create any club, raid, or race
 * - Admin can edit any club, raid, or race
 * - Admin can delete any club, raid, or race
 * - Admin can access admin dashboard
 * - Admin bypasses all ownership checks
 */
class AdminPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $regularOwner;
    private int $clubId;
    private Raid $raid;
    private Race $race;
    private Member $ownerMember;
    private Member $adminMember;

    /**
     * Setup test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Clear Spatie Permission cache before setting up roles/permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->ensureRolesAndPermissionsExist();

        // Clear cache again after setting up roles/permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create regular owner for testing ownership bypass
        // Note: Use syncRoles() to REPLACE any roles set by migrations (user ID 1 gets admin role)
        $ownerDoc = MedicalDoc::factory()->create();
        $this->ownerMember = Member::factory()->create();
        $this->regularOwner = User::factory()->create([
            'adh_id' => $this->ownerMember->adh_id,
            'doc_id' => $ownerDoc->doc_id,
        ]);
        $this->regularOwner->syncRoles(['responsable-club', 'responsable-course']);

        // Create club owned by regular user
        $this->clubId = DB::table('clubs')->insertGetId([
            'club_name' => 'Owner Club',
            'club_street' => '123 Owner Street',
            'club_city' => 'Owner City',
            'club_postal_code' => '12345',
            'ffso_id' => 'FFSO001',
            'description' => 'Owner Club Description',
            'is_approved' => true,
            'created_by' => $this->regularOwner->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('club_user')->insert([
            'club_id' => $this->clubId,
            'user_id' => $this->regularOwner->id,
            'role' => 'manager',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create raid owned by regular user
        $regPeriod = RegistrationPeriod::create([
            'ins_start_date' => now()->addDays(7),
            'ins_end_date' => now()->addDays(21),
        ]);

        $this->raid = Raid::create([
            'raid_name' => 'Owner Raid',
            'raid_description' => 'Description',
            'clu_id' => $this->clubId,
            'adh_id' => $this->ownerMember->adh_id,
            'raid_date_start' => now()->addMonth(),
            'raid_date_end' => now()->addMonth()->addDays(2),
            'ins_id' => $regPeriod->ins_id,
            'raid_contact' => 'contact@test.com',
            'raid_street' => '123 Test Street',
            'raid_city' => 'Owner City',
            'raid_postal_code' => '12345',
            'raid_number' => 1,
        ]);

        // Create race owned by regular user
        $paramRunner = ParamRunner::create([
            'pac_nb_min' => 2,
            'pac_nb_max' => 10,
        ]);
        
        $paramTeam = ParamTeam::create([
            'pae_nb_min' => 1,
            'pae_nb_max' => 20,
            'pae_team_count_max' => 5,
        ]);

        $this->race = Race::create([
            'race_name' => 'Owner Race',
            'race_date_start' => now()->addMonth(),
            'race_date_end' => now()->addMonth(),
            'adh_id' => $this->ownerMember->adh_id,
            'pac_id' => $paramRunner->pac_id,
            'pae_id' => $paramTeam->pae_id,
            'typ_id' => 1,
            'raid_id' => $this->raid->raid_id,
        ]);

        // Create admin user (not owner of anything)
        $adminDoc = MedicalDoc::factory()->create();
        $this->adminMember = Member::factory()->create();
        $this->adminUser = User::factory()->create([
            'adh_id' => $this->adminMember->adh_id,
            'doc_id' => $adminDoc->doc_id,
        ]);
        $this->adminUser->syncRoles(['admin']);
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
        $responsableClubRole->givePermissionTo(['create-club', 'edit-own-club', 'delete-own-club', 'view-clubs', 'create-raid', 'edit-own-raid', 'delete-own-raid', 'view-raids']);

        $responsableCourseRole = Role::findByName('responsable-course');
        $responsableCourseRole->givePermissionTo(['create-race', 'edit-own-race', 'delete-own-race', 'view-races', 'view-raids', 'view-clubs', 'register-to-race']);

        $adminRole = Role::findByName('admin');
        $adminRole->givePermissionTo(Permission::all());
        
        // Create required ParamType entries
        ParamType::firstOrCreate(['typ_id' => 1], ['typ_name' => 'Course']);
    }

    // ===========================================
    // ADMIN CLUB PERMISSIONS
    // ===========================================

    /**
     * Test that admin can view any club
     */
    public function test_admin_can_view_any_club(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('clubs.show', $this->clubId));
        
        $response->assertStatus(200);
    }

    /**
     * Test that admin can access club creation page
     */
    public function test_admin_can_access_club_creation_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('clubs.create'));
        
        $response->assertStatus(200);
    }

    /**
     * Test that admin can create a club
     */
    public function test_admin_can_create_club(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('clubs.store'), [
                'club_name' => 'Admin Created Club',
                'club_street' => '123 Admin Street',
                'club_city' => 'Admin City',
                'club_postal_code' => '99999',
                'ffso_id' => 'FFSO999',
                'description' => 'Admin Club Description',
            ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('clubs', ['club_name' => 'Admin Created Club']);
    }

    /**
     * Test that admin can edit any club (not their own)
     */
    public function test_admin_can_edit_any_club(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('clubs.edit', $this->clubId));
        
        $response->assertStatus(200);
    }

    /**
     * Test that admin can update any club (not their own)
     */
    public function test_admin_can_update_any_club(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->put(route('clubs.update', $this->clubId), [
                'club_name' => 'Admin Updated Club',
                'club_street' => '456 Updated Street',
                'club_city' => 'Updated City',
                'club_postal_code' => '11111',
                'ffso_id' => 'FFSO001',
                'description' => 'Admin Updated Description',
            ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('clubs', ['club_name' => 'Admin Updated Club']);
    }

    /**
     * Test that admin can delete any club (not their own)
     */
    public function test_admin_can_delete_any_club(): void
    {
        // Create a new club to delete
        $clubToDeleteId = DB::table('clubs')->insertGetId([
            'club_name' => 'Club to Delete',
            'club_street' => '123 Delete Street',
            'club_city' => 'Delete City',
            'club_postal_code' => '00000',
            'ffso_id' => 'FFSO000',
            'description' => 'Club to delete',
            'is_approved' => true,
            'created_by' => $this->regularOwner->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('clubs.destroy', $clubToDeleteId));
        
        $response->assertRedirect();
    }

    // ===========================================
    // ADMIN RAID PERMISSIONS
    // ===========================================

    /**
     * Test that admin can view any raid
     */
    public function test_admin_can_view_any_raid(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('raids.show', $this->raid->raid_id));
        
        $response->assertStatus(200);
    }

    /**
     * Test that admin can access raid creation page
     */
    public function test_admin_can_access_raid_creation_page(): void
    {
        // Admin needs to be in a club to create raids
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
     * Test that admin can create a raid
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

        $response = $this->actingAs($this->adminUser)
            ->post(route('raids.store'), [
                'raid_name' => 'Admin Created Raid',
                'raid_description' => 'Admin raid description',
                'clu_id' => $this->clubId,
                'adh_id' => $this->adminMember->adh_id,
                'raid_date_start' => now()->addMonth()->format('Y-m-d'),
                'raid_date_end' => now()->addMonth()->addDays(2)->format('Y-m-d'),
                'ins_start_date' => now()->addDays(7)->format('Y-m-d'),
                'ins_end_date' => now()->addDays(21)->format('Y-m-d'),
                'raid_contact' => 'admin@test.com',
                'raid_street' => 'Admin Street',
                'raid_city' => 'Admin City',
                'raid_postal_code' => '12345',
                'raid_number' => 99,
            ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('raids', ['raid_name' => 'Admin Created Raid']);
    }

    /**
     * Test that admin can edit any raid (not their own)
     */
    public function test_admin_can_edit_any_raid(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('raids.edit', $this->raid->raid_id));
        
        $response->assertStatus(200);
    }

    /**
     * Test that admin can update any raid (not their own)
     */
    public function test_admin_can_update_any_raid(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->put(route('raids.update', $this->raid->raid_id), [
                'raid_name' => 'Admin Updated Raid',
                'raid_description' => 'Admin updated description',
                'clu_id' => $this->clubId,
                'raid_date_start' => now()->addMonth()->format('Y-m-d'),
                'raid_date_end' => now()->addMonth()->addDays(2)->format('Y-m-d'),
                'ins_start_date' => now()->addDays(7)->format('Y-m-d'),
                'ins_end_date' => now()->addDays(21)->format('Y-m-d'),
                'raid_city' => 'Updated City',
                'raid_postal_code' => '99999',
            ]);
        
        $response->assertRedirect();
    }

    /**
     * Test that admin can delete any raid (not their own)
     */
    public function test_admin_can_delete_any_raid(): void
    {
        // Create a new raid to delete
        $regPeriod = RegistrationPeriod::create([
            'ins_start_date' => now()->addDays(7),
            'ins_end_date' => now()->addDays(21),
        ]);

        $raidToDelete = Raid::create([
            'raid_name' => 'Raid to Delete',
            'raid_description' => 'Description',
            'clu_id' => $this->clubId,
            'adh_id' => $this->ownerMember->adh_id,
            'raid_date_start' => now()->addMonth(),
            'raid_date_end' => now()->addMonth()->addDays(2),
            'ins_id' => $regPeriod->ins_id,
            'raid_contact' => 'contact@test.com',
            'raid_street' => '123 Test Street',
            'raid_city' => 'City',
            'raid_postal_code' => '12345',
            'raid_number' => 2,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('raids.destroy', $raidToDelete->raid_id));
        
        $response->assertRedirect();
        $this->assertDatabaseMissing('raids', ['raid_id' => $raidToDelete->raid_id]);
    }

    // ===========================================
    // ADMIN RACE PERMISSIONS
    // ===========================================

    /**
     * Test that admin can view any race
     */
    public function test_admin_can_view_any_race(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('races.show', $this->race->race_id));
        
        $response->assertStatus(200);
    }

    /**
     * Test that admin can access race creation page
     */
    public function test_admin_can_access_race_creation_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('races.create'));
        
        $response->assertStatus(200);
    }

    /**
     * Test that admin can create a race
     */
    public function test_admin_can_create_race(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('races.store'), [
                'title' => 'Admin Created Race',
                'startDate' => now()->addMonth()->format('Y-m-d'),
                'startTime' => '09:00',
                'endDate' => now()->addMonth()->format('Y-m-d'),
                'endTime' => '17:00',
                'duration' => '02:00',
                'minParticipants' => 2,
                'maxParticipants' => 10,
                'minTeams' => 1,
                'maxTeams' => 20,
                'maxPerTeam' => 5,
                'difficulty' => 'Moyen',
                'type' => 1,
                'licenseDiscount' => 0,
                'price' => 10,
                'priceMajor' => 20,
                'priceMinor' => 15,
                'priceMajorAdherent' => 18,
                'priceMinorAdherent' => 12,
                'responsableId' => $this->adminUser->id,
                'raid_id' => $this->raid->raid_id,
            ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('races', ['race_name' => 'Admin Created Race']);
    }

    /**
     * Test that admin can edit any race (not their own)
     */
    public function test_admin_can_edit_any_race(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('races.edit', $this->race->race_id));
        
        $response->assertStatus(200);
    }

    /**
     * Test that admin can delete any race (not their own)
     * 
     * @TODO: Implement races.destroy route
     */
    public function test_admin_can_delete_any_race(): void
    {
        $this->markTestSkipped('Race delete route (races.destroy) not yet implemented');
        
        // Create a new race to delete
        $paramRunner = ParamRunner::create([
            'pac_nb_min' => 2,
            'pac_nb_max' => 10,
        ]);
        
        $paramTeam = ParamTeam::create([
            'pae_nb_min' => 1,
            'pae_nb_max' => 20,
            'pae_team_count_max' => 5,
        ]);

        $raceToDelete = Race::create([
            'race_name' => 'Race to Delete',
            'race_date_start' => now()->addMonth(),
            'race_date_end' => now()->addMonth(),
            'adh_id' => $this->ownerMember->adh_id,
            'pac_id' => $paramRunner->pac_id,
            'pae_id' => $paramTeam->pae_id,
            'typ_id' => 1,
            'raid_id' => $this->raid->raid_id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('races.destroy', $raceToDelete->race_id));
        
        $response->assertRedirect();
        $this->assertDatabaseMissing('races', ['race_id' => $raceToDelete->race_id]);
    }

    // ===========================================
    // ADMIN SPECIAL PERMISSIONS
    // ===========================================

    /**
     * Test that admin has access-admin permission
     */
    public function test_admin_has_access_admin_permission(): void
    {
        $this->assertTrue($this->adminUser->hasPermissionTo('access-admin'));
    }

    /**
     * Test that admin has manage-all-clubs permission
     */
    public function test_admin_has_manage_all_clubs_permission(): void
    {
        $this->assertTrue($this->adminUser->hasPermissionTo('manage-all-clubs'));
    }

    /**
     * Test that admin has manage-all-raids permission
     */
    public function test_admin_has_manage_all_raids_permission(): void
    {
        $this->assertTrue($this->adminUser->hasPermissionTo('manage-all-raids'));
    }

    /**
     * Test that admin has manage-all-races permission
     */
    public function test_admin_has_manage_all_races_permission(): void
    {
        $this->assertTrue($this->adminUser->hasPermissionTo('manage-all-races'));
    }

    /**
     * Test that admin has all permissions
     */
    public function test_admin_has_all_permissions(): void
    {
        $allPermissions = Permission::all()->pluck('name')->toArray();
        
        foreach ($allPermissions as $permission) {
            $this->assertTrue(
                $this->adminUser->hasPermissionTo($permission),
                "Admin should have permission: {$permission}"
            );
        }
    }

    /**
     * Test that regular user does not have admin permissions
     */
    public function test_regular_user_does_not_have_admin_permissions(): void
    {
        $this->assertFalse($this->regularOwner->hasPermissionTo('access-admin'));
        $this->assertFalse($this->regularOwner->hasPermissionTo('manage-all-clubs'));
        $this->assertFalse($this->regularOwner->hasPermissionTo('manage-all-raids'));
        $this->assertFalse($this->regularOwner->hasPermissionTo('manage-all-races'));
    }
}
