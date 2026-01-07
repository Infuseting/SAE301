<?php

namespace Tests\Unit\Race;

use App\Models\Member;
use App\Models\MedicalDoc;
use App\Models\Race;
use App\Models\Raid;
use App\Models\RegistrationPeriod;
use App\Models\User;
use App\Models\ParamType;
use App\Models\ParamRunner;
use App\Models\ParamTeam;
use App\Policies\RacePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Unit tests for RacePolicy
 * 
 * Tests policy methods for authorization logic
 */
class RacePolicyTest extends TestCase
{
    use RefreshDatabase;

    private RacePolicy $policy;
    private User $adminUser;
    private User $raceOwner;
    private User $otherUser;
    private User $guestUser;
    private Race $race;
    private Raid $raid;
    private int $clubId;

    /**
     * Setup test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new RacePolicy();
        $this->ensureRolesAndPermissionsExist();

        // Create members
        $adminMember = Member::factory()->create();
        $ownerMember = Member::factory()->create();
        $otherMember = Member::factory()->create();
        $guestMember = Member::factory()->create();

        // Create medical documents
        $adminDoc = MedicalDoc::factory()->create();
        $ownerDoc = MedicalDoc::factory()->create();
        $otherDoc = MedicalDoc::factory()->create();
        $guestDoc = MedicalDoc::factory()->create();

        // Create admin user
        $this->adminUser = User::factory()->create([
            'adh_id' => $adminMember->adh_id,
            'doc_id' => $adminDoc->doc_id,
        ]);
        $this->adminUser->syncRoles(['admin']);

        // Create race owner (responsable-course)
        $this->raceOwner = User::factory()->create([
            'adh_id' => $ownerMember->adh_id,
            'doc_id' => $ownerDoc->doc_id,
        ]);
        $this->raceOwner->syncRoles(['responsable-course']);

        // Create other user (regular adherent)
        $this->otherUser = User::factory()->create([
            'adh_id' => $otherMember->adh_id,
            'doc_id' => $otherDoc->doc_id,
        ]);
        $this->otherUser->syncRoles(['adherent']);

        // Create guest user
        $this->guestUser = User::factory()->create([
            'adh_id' => $guestMember->adh_id,
            'doc_id' => $guestDoc->doc_id,
        ]);
        $this->guestUser->syncRoles(['guest']);

        // Create club
        $this->clubId = DB::table('clubs')->insertGetId([
            'club_name' => 'Test Club',
            'club_street' => '123 Test Street',
            'club_city' => 'Test City',
            'club_postal_code' => '12345',
            'ffso_id' => 'FFSO001',
            'is_approved' => true,
            'created_by' => $this->adminUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create registration period
        $registrationPeriod = RegistrationPeriod::create([
            'ins_start_date' => now()->addDays(1),
            'ins_end_date' => now()->addDays(30),
        ]);

        // Create raid
        $this->raid = Raid::create([
            'raid_name' => 'Test Raid',
            'raid_description' => 'Test raid description',
            'raid_date_start' => now()->addMonths(2),
            'raid_date_end' => now()->addMonths(2)->addDays(1),
            'raid_street' => '123 Raid Street',
            'raid_city' => 'Raid City',
            'raid_postal_code' => '54321',
            'raid_contact' => 'raid@test.com',
            'raid_number' => 2026001,
            'clu_id' => $this->clubId,
            'adh_id' => $ownerMember->adh_id,
            'ins_id' => $registrationPeriod->ins_id,
        ]);

        // Create param type
        $typeId = ParamType::firstOrCreate(['typ_name' => 'Sprint'])->typ_id;

        // Create race owned by raceOwner
        $paramRunner = ParamRunner::create([
            'pac_nb_min' => 5,
            'pac_nb_max' => 50,
        ]);

        $paramTeam = ParamTeam::create([
            'pae_nb_min' => 1,
            'pae_nb_max' => 10,
            'pae_team_count_max' => 3,
        ]);

        $this->race = Race::create([
            'race_name' => 'Test Race',
            'race_description' => 'Test description',
            'race_date_start' => now()->addMonths(2)->setTime(9, 0),
            'race_date_end' => now()->addMonths(2)->setTime(17, 0),
            'race_difficulty' => 'Facile',
            'price_major' => 20.00,
            'price_minor' => 10.00,
            'adh_id' => $ownerMember->adh_id,
            'raid_id' => $this->raid->raid_id,
            'typ_id' => $typeId,
            'pac_id' => $paramRunner->pac_id,
            'pae_id' => $paramTeam->pae_id,
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
            'view-race',
            'create-race', 
            'edit-own-race', 
            'delete-own-race',
            'register-to-race',
        ];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $responsableCourseRole = Role::findByName('responsable-course');
        $responsableCourseRole->syncPermissions(['create-race', 'edit-own-race', 'delete-own-race']);

        $adherentRole = Role::findByName('adherent');
        $adherentRole->syncPermissions(['register-to-race']);

        $adminRole = Role::findByName('admin');
        $adminRole->syncPermissions(Permission::all());
    }

    // ========================================
    // VIEW ANY TESTS
    // ========================================

    /**
     * Test viewAny returns true for authenticated user
     */
    public function test_view_any_returns_true_for_authenticated_user(): void
    {
        $result = $this->policy->viewAny($this->otherUser);
        $this->assertTrue($result);
    }

    /**
     * Test viewAny returns true for guest user
     */
    public function test_view_any_returns_true_for_guest(): void
    {
        $result = $this->policy->viewAny($this->guestUser);
        $this->assertTrue($result);
    }

    /**
     * Test viewAny returns true for null user (unauthenticated)
     */
    public function test_view_any_returns_true_for_null_user(): void
    {
        $result = $this->policy->viewAny(null);
        $this->assertTrue($result);
    }

    // ========================================
    // VIEW TESTS
    // ========================================

    /**
     * Test view returns true for authenticated user
     */
    public function test_view_returns_true_for_authenticated_user(): void
    {
        $result = $this->policy->view($this->otherUser, $this->race);
        $this->assertTrue($result);
    }

    /**
     * Test view returns true for null user
     */
    public function test_view_returns_true_for_null_user(): void
    {
        $result = $this->policy->view(null, $this->race);
        $this->assertTrue($result);
    }

    // ========================================
    // CREATE TESTS
    // ========================================

    /**
     * Test admin can create race
     */
    public function test_admin_can_create_race(): void
    {
        $result = $this->policy->create($this->adminUser, $this->raid);
        $this->assertTrue($result);
    }

    /**
     * Test responsable-course with permission can create race
     */
    public function test_responsable_course_with_permission_can_create_race(): void
    {
        $result = $this->policy->create($this->raceOwner, null);
        $this->assertTrue($result);
    }

    /**
     * Test guest cannot create race
     */
    public function test_guest_cannot_create_race(): void
    {
        $result = $this->policy->create($this->guestUser, $this->raid);
        $this->assertFalse($result);
    }

    /**
     * Test regular adherent cannot create race
     */
    public function test_adherent_cannot_create_race(): void
    {
        $result = $this->policy->create($this->otherUser, $this->raid);
        $this->assertFalse($result);
    }

    // ========================================
    // UPDATE TESTS
    // ========================================

    /**
     * Test admin can update any race
     */
    public function test_admin_can_update_any_race(): void
    {
        $result = $this->policy->update($this->adminUser, $this->race);
        $this->assertTrue($result);
    }

    /**
     * Test owner can update their own race
     */
    public function test_owner_can_update_own_race(): void
    {
        $result = $this->policy->update($this->raceOwner, $this->race);
        $this->assertTrue($result);
    }

    /**
     * Test non-owner cannot update race
     */
    public function test_non_owner_cannot_update_race(): void
    {
        $result = $this->policy->update($this->otherUser, $this->race);
        $this->assertFalse($result);
    }

    /**
     * Test guest cannot update race
     */
    public function test_guest_cannot_update_race(): void
    {
        $result = $this->policy->update($this->guestUser, $this->race);
        $this->assertFalse($result);
    }

    // ========================================
    // DELETE TESTS
    // ========================================

    /**
     * Test admin can delete any race
     */
    public function test_admin_can_delete_any_race(): void
    {
        $result = $this->policy->delete($this->adminUser, $this->race);
        $this->assertTrue($result);
    }

    /**
     * Test owner can delete their own race
     */
    public function test_owner_can_delete_own_race(): void
    {
        $result = $this->policy->delete($this->raceOwner, $this->race);
        $this->assertTrue($result);
    }

    /**
     * Test non-owner cannot delete race
     */
    public function test_non_owner_cannot_delete_race(): void
    {
        $result = $this->policy->delete($this->otherUser, $this->race);
        $this->assertFalse($result);
    }

    /**
     * Test guest cannot delete race
     */
    public function test_guest_cannot_delete_race(): void
    {
        $result = $this->policy->delete($this->guestUser, $this->race);
        $this->assertFalse($result);
    }

    // ========================================
    // RESTORE / FORCE DELETE TESTS
    // ========================================

    /**
     * Test only admin can restore race
     */
    public function test_only_admin_can_restore_race(): void
    {
        $this->assertTrue($this->policy->restore($this->adminUser, $this->race));
        $this->assertFalse($this->policy->restore($this->raceOwner, $this->race));
        $this->assertFalse($this->policy->restore($this->otherUser, $this->race));
    }

    /**
     * Test only admin can force delete race
     */
    public function test_only_admin_can_force_delete_race(): void
    {
        $this->assertTrue($this->policy->forceDelete($this->adminUser, $this->race));
        $this->assertFalse($this->policy->forceDelete($this->raceOwner, $this->race));
        $this->assertFalse($this->policy->forceDelete($this->otherUser, $this->race));
    }

    // ========================================
    // REGISTER TESTS
    // ========================================

    /**
     * Test adherent can register for race
     */
    public function test_adherent_can_register_for_race(): void
    {
        $result = $this->policy->register($this->otherUser, $this->race);
        $this->assertTrue($result);
    }

    /**
     * Test guest cannot register for race
     */
    public function test_guest_cannot_register_for_race(): void
    {
        $result = $this->policy->register($this->guestUser, $this->race);
        $this->assertFalse($result);
    }
}
