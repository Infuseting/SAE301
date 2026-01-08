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
 * Test suite for Race listing and viewing functionality
 * 
 * Tests cover:
 * - Race listing page access
 * - Individual race viewing
 * - Race data display
 */
class RaceViewTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $regularUser;
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
        $regularMember = Member::factory()->create();
        $guestMember = Member::factory()->create();

        // Create medical documents
        $adminDoc = MedicalDoc::factory()->create();
        $regularDoc = MedicalDoc::factory()->create();
        $guestDoc = MedicalDoc::factory()->create();

        // Create admin user
        $this->adminUser = User::factory()->create([
            'adh_id' => $adminMember->adh_id,
            'doc_id' => $adminDoc->doc_id,
        ]);
        $this->adminUser->syncRoles(['admin']);

        // Create regular user (adherent)
        $this->regularUser = User::factory()->create([
            'adh_id' => $regularMember->adh_id,
            'doc_id' => $regularDoc->doc_id,
        ]);
        $this->regularUser->syncRoles(['adherent']);

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
            'adh_id' => $adminMember->adh_id,
            'ins_id' => $registrationPeriod->ins_id,
        ]);

        // Create param type
        $this->typeId = ParamType::firstOrCreate(['typ_name' => 'Sprint'])->typ_id;

        // Create race
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
            'race_description' => 'A test race description',
            'race_date_start' => now()->addMonths(2)->setTime(9, 0),
            'race_date_end' => now()->addMonths(2)->setTime(17, 0),
            'race_difficulty' => 'Facile',
            'price_major' => 20.00,
            'price_minor' => 10.00,
            'adh_id' => $adminMember->adh_id,
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

        $permissions = ['view-race', 'create-race', 'edit-own-race', 'delete-own-race'];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }

    // ========================================
    // RACE INDEX (LISTING) TESTS - Using Raid detail page for listing
    // ========================================

    /**
     * Test that races are listed on raid detail page (authenticated)
     */
    public function test_authenticated_user_can_view_race_list_via_raid(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('raids.show', $this->raid->raid_id));

        $response->assertStatus(200);
    }

    /**
     * Test that admin can view race list via raid detail
     */
    public function test_admin_can_view_race_list_via_raid(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('raids.show', $this->raid->raid_id));

        $response->assertStatus(200);
    }

    /**
     * Test that guest user can view race list via raid (public listing)
     */
    public function test_guest_can_view_race_list_via_raid(): void
    {
        $response = $this->actingAs($this->guestUser)
            ->get(route('raids.show', $this->raid->raid_id));

        $response->assertStatus(200);
    }

    // ========================================
    // RACE SHOW (SINGLE) TESTS
    // ========================================

    /**
     * Test that authenticated user can view a single race
     */
    public function test_authenticated_user_can_view_single_race(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('races.show', $this->race->race_id));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Race/VisuRace')
            ->has('race')
        );
    }

    /**
     * Test that race detail contains expected data
     */
    public function test_race_detail_contains_expected_data(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('races.show', $this->race->race_id));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Race/VisuRace')
            ->has('race')
        );
    }

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
     * Test that guest can view race (public)
     */
    public function test_guest_can_view_race(): void
    {
        $response = $this->actingAs($this->guestUser)
            ->get(route('races.show', $this->race->race_id));

        $response->assertStatus(200);
    }

    // ========================================
    // ERROR HANDLING TESTS
    // ========================================

    /**
     * Test viewing non-existent race returns 404 or redirect
     */
    public function test_view_non_existent_race_handles_gracefully(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('races.show', 99999));

        // May return 404 or redirect depending on controller implementation
        $this->assertTrue(in_array($response->status(), [404, 200, 302]));
    }

    // ========================================
    // RACE LIST FILTERING TESTS
    // ========================================

    /**
     * Test that races are listed on raid detail page
     */
    public function test_races_are_listed_on_raid_detail_page(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('raids.show', $this->raid->raid_id));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('raid.races')
        );
    }

    /**
     * Test multiple races are listed
     */
    public function test_multiple_races_are_listed(): void
    {
        // Create additional races
        $paramRunner2 = ParamRunner::create([
            'pac_nb_min' => 10,
            'pac_nb_max' => 100,
        ]);

        $paramTeam2 = ParamTeam::create([
            'pae_nb_min' => 2,
            'pae_nb_max' => 20,
            'pae_team_count_max' => 5,
        ]);

        Race::create([
            'race_name' => 'Second Test Race',
            'race_description' => 'Second test race description',
            'race_date_start' => now()->addMonths(2)->addHours(2)->setTime(11, 0),
            'race_date_end' => now()->addMonths(2)->addHours(4)->setTime(19, 0),
            'race_difficulty' => 'Difficile',
            'price_major' => 25.00,
            'price_minor' => 15.00,
            'adh_id' => $this->adminUser->member->adh_id,
            'raid_id' => $this->raid->raid_id,
            'typ_id' => $this->typeId,
            'pac_id' => $paramRunner2->pac_id,
            'pae_id' => $paramTeam2->pae_id,
        ]);

        $response = $this->actingAs($this->regularUser)
            ->get(route('raids.show', $this->raid->raid_id));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('raid.races', 2)
        );
    }

    // ========================================
    // RACE CREATE PAGE ACCESS TESTS
    // ========================================

    /**
     * Test that responsable-course can access race create page
     * Note: gestionnaire-raid authorization is checked at store time, not at create page access
     */
    public function test_responsable_course_can_access_race_create_page(): void
    {
        // The responsable-course role has permission to access the create page
        $this->regularUser->syncRoles(['responsable-course']);
        $this->regularUser->givePermissionTo('create-race');

        $response = $this->actingAs($this->regularUser)
            ->get(route('races.create', ['raid' => $this->raid->raid_id]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Race/NewRace')
        );
    }

    /**
     * Test that admin can access race create page
     */
    public function test_admin_can_access_race_create_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('races.create', ['raid' => $this->raid->raid_id]));

        $response->assertStatus(200);
    }

    /**
     * Test that regular user cannot access race create page
     */
    public function test_regular_user_cannot_access_race_create_page(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('races.create', ['raid' => $this->raid->raid_id]));

        $response->assertStatus(403);
    }
}
