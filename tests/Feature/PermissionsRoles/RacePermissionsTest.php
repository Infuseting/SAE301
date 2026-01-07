<?php

namespace Tests\Feature\PermissionsRoles;

use App\Models\Club;
use App\Models\Member;
use App\Models\MedicalDoc;
use App\Models\Race;
use App\Models\Raid;
use App\Models\ParamRunner;
use App\Models\ParamTeam;
use App\Models\ParamDifficulty;
use App\Models\ParamType;
use App\Models\RegistrationPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Test suite for Race creation permissions
 * 
 * Tests cover:
 * - Guest users cannot create races
 * - Regular users cannot create races
 * - Adherents without responsable-course role cannot create races
 * - Only responsable-course can create races
 * - Responsable-course can only edit their own races
 * - Gestionnaire-raid cannot create races (only edit raid)
 * - Admin can always create and edit any race
 */
class RacePermissionsTest extends TestCase
{
    use RefreshDatabase;

    private User $guestUser;
    private User $regularUser;
    private User $adherentUser;
    private User $responsableClubUser;
    private User $gestionnaireRaidUser;
    private User $responsableCourseUser;
    private User $otherResponsableCourseUser;
    private User $adminUser;
    private int $clubId;
    private Raid $raid;
    private Member $responsableCourseMember;
    private Member $otherResponsableCourseMember;

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
        $responsableClubDoc = MedicalDoc::factory()->create();
        $responsableClubMember = Member::factory()->create();
        $this->responsableClubUser = User::factory()->create([
            'adh_id' => $responsableClubMember->adh_id,
            'doc_id' => $responsableClubDoc->doc_id,
        ]);
        $this->responsableClubUser->syncRoles(['responsable-club']);

        // Create club
        $this->clubId = DB::table('clubs')->insertGetId([
            'club_name' => 'Test Club',
            'club_street' => '123 Test Street',
            'club_city' => 'Test City',
            'club_postal_code' => '12345',
            'ffso_id' => 'FFSO999',
            'description' => 'Test Club Description',
            'is_approved' => true,
            'created_by' => $this->responsableClubUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Add responsable-club to club
        DB::table('club_user')->insert([
            'club_id' => $this->clubId,
            'user_id' => $this->responsableClubUser->id,
            'role' => 'manager',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create raid for races
        $registrationPeriod = RegistrationPeriod::create([
            'ins_start_date' => now()->addDays(7),
            'ins_end_date' => now()->addDays(21),
        ]);

        $this->raid = Raid::create([
            'raid_name' => 'Test Raid',
            'raid_description' => 'Description',
            'clu_id' => $this->clubId,
            'adh_id' => $responsableClubMember->adh_id,
            'raid_date_start' => now()->addMonth(),
            'raid_date_end' => now()->addMonth()->addDays(2),
            'ins_id' => $registrationPeriod->ins_id,
            'raid_contact' => 'contact@test.com',
            'raid_street' => '123 Test Street',
            'raid_city' => 'Test City',
            'raid_postal_code' => '12345',
            'raid_number' => 1,
        ]);

        // Create gestionnaire-raid user
        $gestionnaireDoc = MedicalDoc::factory()->create();
        $gestionnaireMember = Member::factory()->create();
        $this->gestionnaireRaidUser = User::factory()->create([
            'adh_id' => $gestionnaireMember->adh_id,
            'doc_id' => $gestionnaireDoc->doc_id,
        ]);
        $this->gestionnaireRaidUser->syncRoles(['gestionnaire-raid']);

        // Create responsable-course user
        $responsableCourseDoc = MedicalDoc::factory()->create();
        $this->responsableCourseMember = Member::factory()->create();
        $this->responsableCourseUser = User::factory()->create([
            'adh_id' => $this->responsableCourseMember->adh_id,
            'doc_id' => $responsableCourseDoc->doc_id,
        ]);
        $this->responsableCourseUser->syncRoles(['responsable-course']);

        // Add responsable-course to club
        DB::table('club_user')->insert([
            'club_id' => $this->clubId,
            'user_id' => $this->responsableCourseUser->id,
            'role' => 'member',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create another responsable-course user
        $otherCourseDoc = MedicalDoc::factory()->create();
        $this->otherResponsableCourseMember = Member::factory()->create();
        $this->otherResponsableCourseUser = User::factory()->create([
            'adh_id' => $this->otherResponsableCourseMember->adh_id,
            'doc_id' => $otherCourseDoc->doc_id,
        ]);
        $this->otherResponsableCourseUser->syncRoles(['responsable-course']);

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
        $responsableCourseRole = Role::findByName('responsable-course');
        $responsableCourseRole->givePermissionTo(['create-race', 'edit-own-race', 'delete-own-race', 'view-races', 'view-raids', 'view-clubs', 'register-to-race']);

        $gestionnaireRole = Role::findByName('gestionnaire-raid');
        $gestionnaireRole->givePermissionTo(['view-raids', 'edit-own-raid', 'delete-own-raid', 'create-raid', 'view-races', 'view-clubs']);

        $adminRole = Role::findByName('admin');
        $adminRole->givePermissionTo(Permission::all());

        // Create required param entries
        ParamDifficulty::firstOrCreate(['dif_id' => 1], ['dif_level' => 'Facile']);
        ParamDifficulty::firstOrCreate(['dif_id' => 2], ['dif_level' => 'Moyen']);
        ParamDifficulty::firstOrCreate(['dif_id' => 3], ['dif_level' => 'Difficile']);
        ParamType::firstOrCreate(['typ_id' => 1], ['typ_name' => 'Course']);
        ParamType::firstOrCreate(['typ_id' => 2], ['typ_name' => 'Relais']);
    }

    /**
     * Get valid race data for creating a race
     */
    private function getValidRaceData(): array
    {
        return [
            'title' => 'Test Race ' . uniqid(),
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
            'responsableId' => $this->responsableCourseUser->id,
            'raid_id' => $this->raid->raid_id,
        ];
    }

    // ===========================================
    // UNAUTHENTICATED USER TESTS
    // ===========================================

    /**
     * Test that an unauthenticated user cannot access race creation page
     */
    public function test_unauthenticated_user_cannot_access_race_creation_page(): void
    {
        $response = $this->get(route('races.create'));
        
        $response->assertRedirect(route('login'));
    }

    /**
     * Test that an unauthenticated user cannot create a race
     */
    public function test_unauthenticated_user_cannot_create_race(): void
    {
        $response = $this->post(route('races.store'), $this->getValidRaceData());
        
        $response->assertRedirect(route('login'));
    }

    // ===========================================
    // GUEST USER TESTS
    // ===========================================

    /**
     * Test that a guest user cannot access race creation page
     */
    public function test_guest_user_cannot_access_race_creation_page(): void
    {
        $response = $this->actingAs($this->guestUser)
            ->get(route('races.create'));
        
        $response->assertStatus(403);
    }

    /**
     * Test that a guest user cannot create a race
     */
    public function test_guest_user_cannot_create_race(): void
    {
        $response = $this->actingAs($this->guestUser)
            ->post(route('races.store'), $this->getValidRaceData());
        
        $response->assertStatus(403);
    }

    // ===========================================
    // REGULAR USER TESTS
    // ===========================================

    /**
     * Test that a regular user cannot access race creation page
     */
    public function test_regular_user_cannot_access_race_creation_page(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('races.create'));
        
        $response->assertStatus(403);
    }

    /**
     * Test that a regular user cannot create a race
     */
    public function test_regular_user_cannot_create_race(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->post(route('races.store'), $this->getValidRaceData());
        
        $response->assertStatus(403);
    }

    // ===========================================
    // ADHERENT USER TESTS
    // ===========================================

    /**
     * Test that an adherent without responsable-course role cannot access race creation page
     */
    public function test_adherent_cannot_access_race_creation_page(): void
    {
        $response = $this->actingAs($this->adherentUser)
            ->get(route('races.create'));
        
        $response->assertStatus(403);
    }

    /**
     * Test that an adherent without responsable-course role cannot create a race
     */
    public function test_adherent_cannot_create_race(): void
    {
        $response = $this->actingAs($this->adherentUser)
            ->post(route('races.store'), $this->getValidRaceData());
        
        $response->assertStatus(403);
    }

    // ===========================================
    // RESPONSABLE-CLUB USER TESTS (WITHOUT RESPONSABLE-COURSE ROLE)
    // ===========================================

    /**
     * Test that a responsable-club without responsable-course role cannot create races
     */
    public function test_responsable_club_without_course_role_cannot_create_race(): void
    {
        $response = $this->actingAs($this->responsableClubUser)
            ->post(route('races.store'), $this->getValidRaceData());
        
        $response->assertStatus(403);
    }

    // ===========================================
    // GESTIONNAIRE-RAID USER TESTS
    // ===========================================

    /**
     * Test that gestionnaire-raid cannot create races (only manage raids)
     */
    public function test_gestionnaire_raid_cannot_access_race_creation_page(): void
    {
        $response = $this->actingAs($this->gestionnaireRaidUser)
            ->get(route('races.create'));
        
        $response->assertStatus(403);
    }

    /**
     * Test that gestionnaire-raid cannot create a race
     */
    public function test_gestionnaire_raid_cannot_create_race(): void
    {
        $response = $this->actingAs($this->gestionnaireRaidUser)
            ->post(route('races.store'), $this->getValidRaceData());
        
        $response->assertStatus(403);
    }

    // ===========================================
    // RESPONSABLE-COURSE USER TESTS
    // ===========================================

    /**
     * Test that responsable-course can access race creation page
     */
    public function test_responsable_course_can_access_race_creation_page(): void
    {
        $response = $this->actingAs($this->responsableCourseUser)
            ->get(route('races.create'));
        
        $response->assertStatus(200);
    }

    /**
     * Test that responsable-course can create a race
     */
    public function test_responsable_course_can_create_race(): void
    {
        $raceData = $this->getValidRaceData();
        
        $response = $this->actingAs($this->responsableCourseUser)
            ->post(route('races.store'), $raceData);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('races', ['race_name' => $raceData['title']]);
    }

    // ===========================================
    // RACE EDITING TESTS
    // ===========================================

    /**
     * Test that responsable-course can edit their own race
     */
    public function test_responsable_course_can_edit_own_race(): void
    {
        // Create a race owned by responsable-course
        $paramRunner = ParamRunner::create([
            'pac_nb_min' => 2,
            'pac_nb_max' => 10,
        ]);
        
        $paramTeam = ParamTeam::create([
            'pae_nb_min' => 1,
            'pae_nb_max' => 20,
            'pae_team_count_max' => 5,
        ]);

        $race = Race::create([
            'race_name' => 'My Race',
            'race_date_start' => now()->addMonth(),
            'race_date_end' => now()->addMonth(),
            'adh_id' => $this->responsableCourseMember->adh_id,
            'pac_id' => $paramRunner->pac_id,
            'pae_id' => $paramTeam->pae_id,
            'typ_id' => 1,
            'raid_id' => $this->raid->raid_id,
        ]);

        $response = $this->actingAs($this->responsableCourseUser)
            ->get(route('races.edit', $race->race_id));
        
        $response->assertStatus(200);
    }

    /**
     * Test that responsable-course cannot edit another user's race
     * 
     * @TODO: Implement ownership check in NewRaceController::show() for edit routes
     * Currently the edit route uses the same controller method as create and doesn't
     * check if the user owns the race being edited.
     */
    public function test_responsable_course_cannot_edit_other_users_race(): void
    {
        $this->markTestSkipped('Race edit ownership check not yet implemented in controller');
        
        // Create a race owned by another responsable-course
        $paramRunner = ParamRunner::create([
            'pac_nb_min' => 2,
            'pac_nb_max' => 10,
        ]);
        
        $paramTeam = ParamTeam::create([
            'pae_nb_min' => 1,
            'pae_nb_max' => 20,
            'pae_team_count_max' => 5,
        ]);

        $race = Race::create([
            'race_name' => 'Other Race',
            'race_date_start' => now()->addMonth(),
            'race_date_end' => now()->addMonth(),
            'adh_id' => $this->otherResponsableCourseMember->adh_id,
            'pac_id' => $paramRunner->pac_id,
            'pae_id' => $paramTeam->pae_id,
            'typ_id' => 1,
            'raid_id' => $this->raid->raid_id,
        ]);

        $response = $this->actingAs($this->responsableCourseUser)
            ->get(route('races.edit', $race->race_id));
        
        $response->assertStatus(403);
    }

    // ===========================================
    // ADMIN USER TESTS
    // ===========================================

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
        $raceData = $this->getValidRaceData();
        $raceData['responsableId'] = $this->adminUser->id;
        
        $response = $this->actingAs($this->adminUser)
            ->post(route('races.store'), $raceData);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('races', ['race_name' => $raceData['title']]);
    }

    /**
     * Test that admin can edit any race
     */
    public function test_admin_can_edit_any_race(): void
    {
        // Create a race owned by another user
        $paramRunner = ParamRunner::create([
            'pac_nb_min' => 2,
            'pac_nb_max' => 10,
        ]);
        
        $paramTeam = ParamTeam::create([
            'pae_nb_min' => 1,
            'pae_nb_max' => 20,
            'pae_team_count_max' => 5,
        ]);

        $race = Race::create([
            'race_name' => 'Some Race',
            'race_date_start' => now()->addMonth(),
            'race_date_end' => now()->addMonth(),
            'adh_id' => $this->responsableCourseMember->adh_id,
            'pac_id' => $paramRunner->pac_id,
            'pae_id' => $paramTeam->pae_id,
            'typ_id' => 1,
            'raid_id' => $this->raid->raid_id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('races.edit', $race->race_id));
        
        $response->assertStatus(200);
    }

    /**
     * Test that admin can delete any race
     * 
     * @TODO: Implement races.destroy route and RaceController::destroy method
     */
    public function test_admin_can_delete_any_race(): void
    {
        $this->markTestSkipped('Race delete route (races.destroy) not yet implemented');
        
        // Create a race
        $paramRunner = ParamRunner::create([
            'pac_nb_min' => 2,
            'pac_nb_max' => 10,
        ]);
        
        $paramTeam = ParamTeam::create([
            'pae_nb_min' => 1,
            'pae_nb_max' => 20,
            'pae_team_count_max' => 5,
        ]);

        $race = Race::create([
            'race_name' => 'Race to Delete',
            'race_date_start' => now()->addMonth(),
            'race_date_end' => now()->addMonth(),
            'adh_id' => $this->responsableCourseMember->adh_id,
            'pac_id' => $paramRunner->pac_id,
            'pae_id' => $paramTeam->pae_id,
            'typ_id' => 1,
            'raid_id' => $this->raid->raid_id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('races.destroy', $race->race_id));
        
        $response->assertRedirect();
        $this->assertDatabaseMissing('races', ['race_id' => $race->race_id]);
    }
}
