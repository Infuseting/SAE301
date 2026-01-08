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
 * Test suite for Race creation functionality
 * 
 * Tests cover:
 * - Access control (only responsable-course, gestionnaire-raid, admin can create)
 * - Validation rules (dates, required fields, relationships)
 * - Auto-assignment of responsable and role assignment
 * - Integration with ParamRunner and ParamTeam
 */
class RaceCreationTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $responsableCourseUser;
    private User $gestionnaireRaidUser;
    private User $regularUser;
    private User $guestUser;
    private Raid $raid;
    private int $clubId;
    private int $typeId;

    /**
     * Setup test environment before each test
     * Creates club, raid, users, and roles needed for testing
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureRolesAndPermissionsExist();

        // Create members
        $adminMember = Member::factory()->create();
        $responsableMember = Member::factory()->create();
        $gestionnaireMember = Member::factory()->create();
        $regularMember = Member::factory()->create();

        // Create medical documents
        $adminDoc = MedicalDoc::factory()->create();
        $responsableDoc = MedicalDoc::factory()->create();
        $gestionnaireDoc = MedicalDoc::factory()->create();
        $regularDoc = MedicalDoc::factory()->create();

        // Create admin user
        $this->adminUser = User::factory()->create([
            'adh_id' => $adminMember->adh_id,
            'doc_id' => $adminDoc->doc_id,
        ]);
        $this->adminUser->syncRoles(['admin']);

        // Create responsable-course user
        $this->responsableCourseUser = User::factory()->create([
            'adh_id' => $responsableMember->adh_id,
            'doc_id' => $responsableDoc->doc_id,
        ]);
        $this->responsableCourseUser->syncRoles(['responsable-course']);

        // Create gestionnaire-raid user
        $this->gestionnaireRaidUser = User::factory()->create([
            'adh_id' => $gestionnaireMember->adh_id,
            'doc_id' => $gestionnaireDoc->doc_id,
        ]);
        $this->gestionnaireRaidUser->syncRoles(['gestionnaire-raid']);

        // Create regular user (no special role)
        $this->regularUser = User::factory()->create([
            'adh_id' => $regularMember->adh_id,
            'doc_id' => $regularDoc->doc_id,
        ]);
        $this->regularUser->syncRoles(['user']);

        // Create guest user
        $this->guestUser = User::factory()->create([
            'adh_id' => null,
            'doc_id' => null,
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

        // Add users to club
        DB::table('club_user')->insert([
            [
                'club_id' => $this->clubId,
                'user_id' => $this->responsableCourseUser->id,
                'role' => 'member',
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'club_id' => $this->clubId,
                'user_id' => $this->gestionnaireRaidUser->id,
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
            'raid_name' => 'Test Raid',            'raid_description' => 'Test raid description',            'raid_date_start' => now()->addMonths(2),
            'raid_date_end' => now()->addMonths(2)->addDays(1),
            'raid_street' => '123 Raid Street',
            'raid_city' => 'Raid City',
            'raid_postal_code' => '54321',
            'raid_contact' => 'raid@test.com',
            'raid_number' => 2026001,
            'clu_id' => $this->clubId,
            'adh_id' => $gestionnaireMember->adh_id,
            'ins_id' => $registrationPeriod->ins_id,
        ]);

        // Create param type
        $this->typeId = ParamType::firstOrCreate(['typ_name' => 'Sprint'])->typ_id;
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

        // Assign permissions to roles
        $responsableCourseRole = Role::findByName('responsable-course');
        $responsableCourseRole->syncPermissions(['create-race', 'edit-own-race', 'delete-own-race']);

        $adminRole = Role::findByName('admin');
        $adminRole->syncPermissions(Permission::all());
    }

    /**
     * Get valid race data for testing
     */
    private function getValidRaceData(): array
    {
        return [
            'title' => 'Test Race',
            'description' => 'Test race description',
            'startDate' => now()->addMonths(2)->format('Y-m-d'),
            'startTime' => '09:00',
            'endDate' => now()->addMonths(2)->format('Y-m-d'),
            'endTime' => '17:00',
            'duration' => '2:30',
            'minParticipants' => 10,
            'maxParticipants' => 100,
            'maxPerTeam' => 5,
            'difficulty' => 'Moyen',
            'type' => $this->typeId,
            'minTeams' => 2,
            'maxTeams' => 20,
            'mealPrice' => 10.00,
            'priceMajor' => 25.00,
            'priceMinor' => 15.00,
            'responsableId' => $this->responsableCourseUser->id,
            'raid_id' => $this->raid->raid_id,
        ];
    }

    // ========================================
    // ACCESS CONTROL TESTS
    // ========================================

    /**
     * Test that unauthenticated user cannot access race creation page
     */
    public function test_unauthenticated_user_cannot_access_race_creation_page(): void
    {
        $response = $this->get(route('races.create'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test that guest user cannot access race creation page
     */
    public function test_guest_user_cannot_access_race_creation_page(): void
    {
        $response = $this->actingAs($this->guestUser)
            ->get(route('races.create'));

        $response->assertStatus(403);
    }

    /**
     * Test that regular user cannot access race creation page
     */
    public function test_regular_user_cannot_access_race_creation_page(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('races.create'));

        $response->assertStatus(403);
    }

    /**
     * Test that responsable-course can access race creation page
     */
    public function test_responsable_course_can_access_race_creation_page(): void
    {
        $response = $this->actingAs($this->responsableCourseUser)
            ->get(route('races.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Race/NewRace')
            ->has('users')
            ->has('types')
        );
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

    // ========================================
    // RACE CREATION TESTS
    // ========================================

    /**
     * Test that responsable-course can create a race
     */
    public function test_responsable_course_can_create_race(): void
    {
        $raceData = $this->getValidRaceData();

        $response = $this->actingAs($this->responsableCourseUser)
            ->post(route('races.store'), $raceData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('races', [
            'race_name' => 'Test Race',
            'race_difficulty' => 'Moyen',
        ]);
    }

    /**
     * Test that admin can create a race
     */
    public function test_admin_can_create_race(): void
    {
        $raceData = $this->getValidRaceData();

        $response = $this->actingAs($this->adminUser)
            ->post(route('races.store'), $raceData);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /**
     * Test that guest user cannot create a race
     */
    public function test_guest_user_cannot_create_race(): void
    {
        $raceData = $this->getValidRaceData();

        $response = $this->actingAs($this->guestUser)
            ->post(route('races.store'), $raceData);

        $response->assertStatus(403);
    }

    /**
     * Test that regular user cannot create a race
     */
    public function test_regular_user_cannot_create_race(): void
    {
        $raceData = $this->getValidRaceData();

        $response = $this->actingAs($this->regularUser)
            ->post(route('races.store'), $raceData);

        $response->assertStatus(403);
    }

    // ========================================
    // VALIDATION TESTS
    // ========================================

    /**
     * Test that race title is required
     */
    public function test_race_title_is_required(): void
    {
        $raceData = $this->getValidRaceData();
        unset($raceData['title']);

        $response = $this->actingAs($this->responsableCourseUser)
            ->post(route('races.store'), $raceData);

        $response->assertSessionHasErrors('title');
    }

    /**
     * Test that race title cannot exceed 100 characters
     */
    public function test_race_title_max_length(): void
    {
        $raceData = $this->getValidRaceData();
        $raceData['title'] = str_repeat('a', 101);

        $response = $this->actingAs($this->responsableCourseUser)
            ->post(route('races.store'), $raceData);

        $response->assertSessionHasErrors('title');
    }

    /**
     * Test that start date is required
     */
    public function test_start_date_is_required(): void
    {
        $raceData = $this->getValidRaceData();
        unset($raceData['startDate']);

        $response = $this->actingAs($this->responsableCourseUser)
            ->post(route('races.store'), $raceData);

        $response->assertSessionHasErrors('startDate');
    }

    /**
     * Test that start date cannot be in the past
     */
    public function test_start_date_cannot_be_in_past(): void
    {
        $raceData = $this->getValidRaceData();
        $raceData['startDate'] = now()->subDays(1)->format('Y-m-d');

        $response = $this->actingAs($this->responsableCourseUser)
            ->post(route('races.store'), $raceData);

        $response->assertSessionHasErrors('startDate');
    }

    /**
     * Test that end date must be after or equal to start date
     */
    public function test_end_date_must_be_after_start_date(): void
    {
        $raceData = $this->getValidRaceData();
        $raceData['endDate'] = now()->addMonths(2)->subDays(1)->format('Y-m-d');

        $response = $this->actingAs($this->responsableCourseUser)
            ->post(route('races.store'), $raceData);

        $response->assertSessionHasErrors('endDate');
    }

    /**
     * Test that min participants is required
     */
    public function test_min_participants_is_required(): void
    {
        $raceData = $this->getValidRaceData();
        unset($raceData['minParticipants']);

        $response = $this->actingAs($this->responsableCourseUser)
            ->post(route('races.store'), $raceData);

        $response->assertSessionHasErrors('minParticipants');
    }

    /**
     * Test that max participants must be greater than or equal to min
     */
    public function test_max_participants_must_be_gte_min(): void
    {
        $raceData = $this->getValidRaceData();
        $raceData['minParticipants'] = 50;
        $raceData['maxParticipants'] = 20;

        $response = $this->actingAs($this->responsableCourseUser)
            ->post(route('races.store'), $raceData);

        $response->assertSessionHasErrors('maxParticipants');
    }

    /**
     * Test that difficulty is required
     */
    public function test_difficulty_is_required(): void
    {
        $raceData = $this->getValidRaceData();
        unset($raceData['difficulty']);

        $response = $this->actingAs($this->responsableCourseUser)
            ->post(route('races.store'), $raceData);

        $response->assertSessionHasErrors('difficulty');
    }

    /**
     * Test that type must exist in database
     */
    public function test_type_must_exist(): void
    {
        $raceData = $this->getValidRaceData();
        $raceData['type'] = 99999;

        $response = $this->actingAs($this->responsableCourseUser)
            ->post(route('races.store'), $raceData);

        $response->assertSessionHasErrors('type');
    }

    /**
     * Test that responsable must exist
     */
    public function test_responsable_must_exist(): void
    {
        $raceData = $this->getValidRaceData();
        $raceData['responsableId'] = 99999;

        $response = $this->actingAs($this->responsableCourseUser)
            ->post(route('races.store'), $raceData);

        $response->assertSessionHasErrors('responsableId');
    }

    /**
     * Test that price major is required
     */
    public function test_price_major_is_required(): void
    {
        $raceData = $this->getValidRaceData();
        unset($raceData['priceMajor']);

        $response = $this->actingAs($this->responsableCourseUser)
            ->post(route('races.store'), $raceData);

        $response->assertSessionHasErrors('priceMajor');
    }

    /**
     * Test that price minor is required
     */
    public function test_price_minor_is_required(): void
    {
        $raceData = $this->getValidRaceData();
        unset($raceData['priceMinor']);

        $response = $this->actingAs($this->responsableCourseUser)
            ->post(route('races.store'), $raceData);

        $response->assertSessionHasErrors('priceMinor');
    }

    /**
     * Test that duration format must be valid
     */
    public function test_duration_format_must_be_valid(): void
    {
        $raceData = $this->getValidRaceData();
        $raceData['duration'] = 'invalid';

        $response = $this->actingAs($this->responsableCourseUser)
            ->post(route('races.store'), $raceData);

        $response->assertSessionHasErrors('duration');
    }

    /**
     * Test that max teams must be greater than or equal to min teams
     */
    public function test_max_teams_must_be_gte_min_teams(): void
    {
        $raceData = $this->getValidRaceData();
        $raceData['minTeams'] = 10;
        $raceData['maxTeams'] = 5;

        $response = $this->actingAs($this->responsableCourseUser)
            ->post(route('races.store'), $raceData);

        $response->assertSessionHasErrors('maxTeams');
    }

    // ========================================
    // PARAM CREATION TESTS
    // ========================================

    /**
     * Test that ParamRunner is created with race
     */
    public function test_param_runner_is_created_with_race(): void
    {
        $raceData = $this->getValidRaceData();

        $this->actingAs($this->responsableCourseUser)
            ->post(route('races.store'), $raceData);

        $race = Race::where('race_name', 'Test Race')->first();

        // Verify race has a pac_id reference
        $this->assertNotNull($race);
        $this->assertNotNull($race->pac_id);
        
        // Verify ParamRunner exists via model
        $this->assertNotNull(ParamRunner::find($race->pac_id));
    }

    /**
     * Test that ParamTeam is created with race
     */
    public function test_param_team_is_created_with_race(): void
    {
        $raceData = $this->getValidRaceData();

        $this->actingAs($this->responsableCourseUser)
            ->post(route('races.store'), $raceData);

        $race = Race::where('race_name', 'Test Race')->first();

        // Verify race has a pae_id reference
        $this->assertNotNull($race);
        $this->assertNotNull($race->pae_id);
        
        // Verify ParamTeam exists via model
        $this->assertNotNull(ParamTeam::find($race->pae_id));
    }
}
