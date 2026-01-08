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
 * Test suite for Race update functionality
 * 
 * Tests cover:
 * - Access control for editing (only owner/admin)
 * - Validation rules on update
 * - Successful updates
 * - ParamRunner and ParamTeam updates
 */
class RaceUpdateTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $raceOwner;
    private User $otherUser;
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

        // Create medical documents
        $adminDoc = MedicalDoc::factory()->create();
        $ownerDoc = MedicalDoc::factory()->create();
        $otherDoc = MedicalDoc::factory()->create();

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

        // Create other user (also responsable-course but not owner)
        $this->otherUser = User::factory()->create([
            'adh_id' => $otherMember->adh_id,
            'doc_id' => $otherDoc->doc_id,
        ]);
        $this->otherUser->syncRoles(['responsable-course']);

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
            [
                'club_id' => $this->clubId,
                'user_id' => $this->otherUser->id,
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

        // Create ParamRunner
        $paramRunner = ParamRunner::create([
            'pac_nb_min' => 5,
            'pac_nb_max' => 50,
        ]);

        // Create ParamTeam
        $paramTeam = ParamTeam::create([
            'pae_nb_min' => 1,
            'pae_nb_max' => 10,
            'pae_team_count_max' => 3,
        ]);

        // Create race owned by raceOwner
        $this->race = Race::create([
            'race_name' => 'Original Race Name',
            'race_description' => 'Original description',
            'race_date_start' => now()->addMonths(2)->setTime(9, 0),
            'race_date_end' => now()->addMonths(2)->setTime(17, 0),
            'race_difficulty' => 'Facile',
            'price_major' => 20.00,
            'price_minor' => 10.00,
            'adh_id' => $ownerMember->adh_id,
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

    /**
     * Get valid update data
     */
    private function getValidUpdateData(): array
    {
        return [
            'title' => 'Updated Race Name',
            'description' => 'Updated description',
            'startDate' => now()->addMonths(2)->format('Y-m-d'),
            'startTime' => '10:00',
            'endDate' => now()->addMonths(2)->format('Y-m-d'),
            'endTime' => '18:00',
            'duration' => '3:00',
            'minParticipants' => 15,
            'maxParticipants' => 150,
            'minPerTeam' => 3,
            'maxPerTeam' => 6,
            'difficulty' => 'Difficile',
            'type' => $this->typeId,
            'minTeams' => 3,
            'maxTeams' => 25,
            'mealPrice' => 12.00,
            'priceMajor' => 30.00,
            'priceMinor' => 18.00,
            'responsableId' => $this->raceOwner->id,
        ];
    }

    // ========================================
    // ACCESS CONTROL TESTS
    // ========================================

    /**
     * Test that race owner can access edit page
     */
    public function test_race_owner_can_access_edit_page(): void
    {
        $response = $this->actingAs($this->raceOwner)
            ->get(route('races.edit', $this->race->race_id));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Race/NewRace')
            ->has('race')
        );
    }

    /**
     * Test that admin can access any race edit page
     */
    public function test_admin_can_access_any_race_edit_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('races.edit', $this->race->race_id));

        $response->assertStatus(200);
    }

    /**
     * Test that other user cannot access race edit page
     */
    public function test_other_user_cannot_access_race_edit_page(): void
    {
        $response = $this->actingAs($this->otherUser)
            ->get(route('races.edit', $this->race->race_id));

        $response->assertStatus(403);
    }

    /**
     * Test that unauthenticated user cannot access race edit page
     */
    public function test_unauthenticated_user_cannot_access_race_edit_page(): void
    {
        $response = $this->get(route('races.edit', $this->race->race_id));

        $response->assertRedirect(route('login'));
    }

    // ========================================
    // UPDATE TESTS
    // ========================================

    /**
     * Test that race owner can update their race
     */
    public function test_race_owner_can_update_race(): void
    {
        $updateData = $this->getValidUpdateData();

        $response = $this->actingAs($this->raceOwner)
            ->put(route('races.update', $this->race->race_id), $updateData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->race->refresh();
        $this->assertEquals('Updated Race Name', $this->race->race_name);
        $this->assertEquals('Difficile', $this->race->race_difficulty);
    }

    /**
     * Test that admin can update any race
     */
    public function test_admin_can_update_any_race(): void
    {
        $updateData = $this->getValidUpdateData();
        $updateData['title'] = 'Admin Updated Race';

        $response = $this->actingAs($this->adminUser)
            ->put(route('races.update', $this->race->race_id), $updateData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->race->refresh();
        $this->assertEquals('Admin Updated Race', $this->race->race_name);
    }

    /**
     * Test that other user cannot update race
     */
    public function test_other_user_cannot_update_race(): void
    {
        $updateData = $this->getValidUpdateData();

        $response = $this->actingAs($this->otherUser)
            ->put(route('races.update', $this->race->race_id), $updateData);

        $response->assertStatus(403);
    }

    // ========================================
    // VALIDATION TESTS ON UPDATE
    // ========================================

    /**
     * Test that race title is required on update
     */
    public function test_race_title_is_required_on_update(): void
    {
        $updateData = $this->getValidUpdateData();
        unset($updateData['title']);

        $response = $this->actingAs($this->raceOwner)
            ->put(route('races.update', $this->race->race_id), $updateData);

        $response->assertSessionHasErrors('title');
    }

    /**
     * Test that prices cannot be negative on update
     */
    public function test_prices_cannot_be_negative_on_update(): void
    {
        $updateData = $this->getValidUpdateData();
        $updateData['priceMajor'] = -10;

        $response = $this->actingAs($this->raceOwner)
            ->put(route('races.update', $this->race->race_id), $updateData);

        $response->assertSessionHasErrors('priceMajor');
    }

    // ========================================
    // PARAM UPDATE TESTS
    // ========================================

    /**
     * Test that ParamRunner is updated with race
     */
    public function test_param_runner_is_updated_with_race(): void
    {
        $updateData = $this->getValidUpdateData();
        $updateData['minParticipants'] = 20;
        $updateData['maxParticipants'] = 200;

        $this->actingAs($this->raceOwner)
            ->put(route('races.update', $this->race->race_id), $updateData);

        // Verify the update was successful
        $this->race->refresh();
        $paramRunner = ParamRunner::find($this->race->pac_id);
        
        $this->assertNotNull($paramRunner);
        $this->assertEquals(20, $paramRunner->pac_nb_min);
        $this->assertEquals(200, $paramRunner->pac_nb_max);
    }

    /**
     * Test that ParamTeam is updated with race
     */
    public function test_param_team_is_updated_with_race(): void
    {
        $updateData = $this->getValidUpdateData();
        $updateData['minTeams'] = 5;
        $updateData['maxTeams'] = 30;
        $updateData['maxPerTeam'] = 8;

        $this->actingAs($this->raceOwner)
            ->put(route('races.update', $this->race->race_id), $updateData);

        // Verify the update was successful
        $this->race->refresh();
        $paramTeam = ParamTeam::find($this->race->pae_id);
        
        $this->assertNotNull($paramTeam);
        $this->assertEquals(5, $paramTeam->pae_nb_min);
        $this->assertEquals(30, $paramTeam->pae_nb_max);
        $this->assertEquals(8, $paramTeam->pae_team_count_max);
    }

    /**
     * Test that non-existent race returns 404
     */
    public function test_update_non_existent_race_returns_404(): void
    {
        $updateData = $this->getValidUpdateData();

        $response = $this->actingAs($this->raceOwner)
            ->put(route('races.update', 99999), $updateData);

        $response->assertStatus(404);
    }
}
