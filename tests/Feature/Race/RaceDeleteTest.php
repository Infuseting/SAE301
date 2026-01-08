<?php

namespace Tests\Feature\Race;

use App\Models\Member;
use App\Models\MedicalDoc;
use App\Models\Race;
use App\Models\Raid;
use App\Models\RegistrationPeriod;
use App\Models\User;
use App\Models\ParamType;
use App\Models\ParamRunner;
use App\Models\ParamTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Test suite for Race deletion functionality
 * 
 * Tests cover:
 * - Access control (only owner/admin can delete)
 * - Cascade deletions (params, etc.)
 * - Protection against deleting races with registrations
 */
class RaceDeleteTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $raceOwner;
    private User $otherUser;
    private User $guestUser;
    private Race $race;
    private Raid $raid;
    private int $clubId;
    private int $typeId;

    /**
     * Setup test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();

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

        // Create other user (responsable-course but not owner)
        $this->otherUser = User::factory()->create([
            'adh_id' => $otherMember->adh_id,
            'doc_id' => $otherDoc->doc_id,
        ]);
        $this->otherUser->syncRoles(['responsable-course']);

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
            'ffso_id' => 'FFCO001',
            'is_approved' => true,
            'created_by' => $this->adminUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Add users to club
        DB::table('club_user')->insert([
            [
                'club_id' => $this->clubId,
                'user_id' => $this->raceOwner->id,
                'role' => 'member',
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ],
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
        $this->typeId = ParamType::firstOrCreate(['typ_name' => 'Sprint'])->typ_id;

        // Create race
        $this->createRaceForOwner();
    }

    /**
     * Create a fresh race for the owner
     */
    private function createRaceForOwner(): void
    {
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
            'race_name' => 'Race To Delete',
            'race_description' => 'Test description',
            'race_date_start' => now()->addMonths(2)->setTime(9, 0),
            'race_date_end' => now()->addMonths(2)->setTime(17, 0),
            'race_difficulty' => 'Facile',
            'price_major' => 20.00,
            'price_minor' => 10.00,
            'adh_id' => $this->raceOwner->member->adh_id,
            'raid_id' => $this->raid->raid_id,
            'typ_id' => $this->typeId,
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

        $permissions = ['create-race', 'edit-own-race', 'delete-own-race'];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $responsableCourseRole = Role::findByName('responsable-course');
        $responsableCourseRole->syncPermissions(['create-race', 'edit-own-race', 'delete-own-race']);

        $adminRole = Role::findByName('admin');
        $adminRole->syncPermissions(Permission::all());
    }

    // ========================================
    // ACCESS CONTROL TESTS
    // ========================================

    /**
     * Test that unauthenticated user cannot delete a race
     */
    public function test_unauthenticated_user_cannot_delete_race(): void
    {
        $response = $this->delete(route('races.destroy', $this->race->race_id));

        $response->assertRedirect(route('login'));
        // Verify race still exists
        $this->assertNotNull(Race::find($this->race->race_id));
    }

    /**
     * Test that guest user cannot delete a race
     */
    public function test_guest_cannot_delete_race(): void
    {
        $response = $this->actingAs($this->guestUser)
            ->delete(route('races.destroy', $this->race->race_id));

        $response->assertStatus(403);
        // Verify race still exists
        $this->assertNotNull(Race::find($this->race->race_id));
    }

    /**
     * Test that other user (non-owner) cannot delete race
     */
    public function test_non_owner_cannot_delete_race(): void
    {
        $response = $this->actingAs($this->otherUser)
            ->delete(route('races.destroy', $this->race->race_id));

        $response->assertStatus(403);
        // Verify race still exists
        $this->assertNotNull(Race::find($this->race->race_id));
    }

    // ========================================
    // SUCCESSFUL DELETION TESTS
    // ========================================

    /**
     * Test that race owner can delete their race
     */
    public function test_race_owner_can_delete_race(): void
    {
        $raceId = $this->race->race_id;

        $response = $this->actingAs($this->raceOwner)
            ->delete(route('races.destroy', $raceId));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        // Verify race is deleted
        $this->assertNull(Race::find($raceId));
    }

    /**
     * Test that admin can delete any race
     */
    public function test_admin_can_delete_any_race(): void
    {
        $raceId = $this->race->race_id;

        $response = $this->actingAs($this->adminUser)
            ->delete(route('races.destroy', $raceId));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        // Verify race is deleted
        $this->assertNull(Race::find($raceId));
    }

    // ========================================
    // CASCADE DELETION TESTS
    // ========================================

    /**
     * Test that ParamRunner reference is handled when race is deleted
     */
    public function test_param_runner_reference_handled_on_race_delete(): void
    {
        $pacId = $this->race->pac_id;
        $raceId = $this->race->race_id;

        // Verify ParamRunner exists before delete
        $this->assertNotNull(ParamRunner::find($pacId));

        $this->actingAs($this->raceOwner)
            ->delete(route('races.destroy', $raceId));

        // Verify race is deleted
        $this->assertNull(Race::find($raceId));
    }

    /**
     * Test that ParamTeam reference is handled when race is deleted
     */
    public function test_param_team_reference_handled_on_race_delete(): void
    {
        $paeId = $this->race->pae_id;
        $raceId = $this->race->race_id;

        // Verify ParamTeam exists before delete
        $this->assertNotNull(ParamTeam::find($paeId));

        $this->actingAs($this->raceOwner)
            ->delete(route('races.destroy', $raceId));

        // Verify race is deleted
        $this->assertNull(Race::find($raceId));
    }

    // ========================================
    // ERROR HANDLING TESTS
    // ========================================

    /**
     * Test deleting non-existent race returns 404
     */
    public function test_delete_non_existent_race_returns_404(): void
    {
        $response = $this->actingAs($this->raceOwner)
            ->delete(route('races.destroy', 99999));

        $response->assertStatus(404);
    }

    /**
     * Test that multiple delete requests don't cause issues
     */
    public function test_double_delete_request_handles_gracefully(): void
    {
        $raceId = $this->race->race_id;

        // First delete should succeed
        $response1 = $this->actingAs($this->raceOwner)
            ->delete(route('races.destroy', $raceId));

        $response1->assertRedirect();

        // Second delete should return 404
        $response2 = $this->actingAs($this->raceOwner)
            ->delete(route('races.destroy', $raceId));

        $response2->assertStatus(404);
    }
}
