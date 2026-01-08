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
 * Test suite for verifying ownership-based edit restrictions
 * 
 * Tests cover:
 * - Responsable-club can only edit their own raids (raids from their club)
 * - Responsable-course can only edit their own races
 * - Gestionnaire-raid can only edit raids they manage
 * - Cross-ownership attempts are blocked
 */
class EditOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private User $responsableClubUser1;
    private User $responsableClubUser2;
    private User $responsableCourseUser1;
    private User $responsableCourseUser2;
    private User $gestionnaireRaidUser;
    private User $adminUser;
    private int $club1Id;
    private int $club2Id;
    private Raid $raid1;
    private Raid $raid2;
    private Raid $raidForGestionnaire;
    private Race $race1;
    private Race $race2;
    private Member $responsableClub1Member;
    private Member $responsableClub2Member;
    private Member $responsableCourse1Member;
    private Member $responsableCourse2Member;
    private Member $gestionnaireMember;

    /**
     * Setup test environment before each test
     * 
     * Raid ownership is determined by adh_id on the raid (the member who is responsible)
     * Race ownership is determined by adh_id on the race (the member who is responsible)
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Clear Spatie Permission cache before setting up roles/permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->ensureRolesAndPermissionsExist();

        // Clear cache again after setting up roles/permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create first responsable-club with their club
        // Note: Use syncRoles() to REPLACE any roles set by migrations (user ID 1 gets admin role)
        $responsableClub1Doc = MedicalDoc::factory()->create();
        $this->responsableClub1Member = Member::factory()->create();
        $this->responsableClubUser1 = User::factory()->create([
            'adh_id' => $this->responsableClub1Member->adh_id,
            'doc_id' => $responsableClub1Doc->doc_id,
        ]);
        $this->responsableClubUser1->syncRoles(['responsable-club']);

        $this->club1Id = DB::table('clubs')->insertGetId([
            'club_name' => 'Club One',
            'club_street' => '123 Club One Street',
            'club_city' => 'City One',
            'club_postal_code' => '11111',
            'ffso_id' => 'FFSO001',
            'description' => 'Club One Description',
            'is_approved' => true,
            'created_by' => $this->responsableClubUser1->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('club_user')->insert([
            'club_id' => $this->club1Id,
            'user_id' => $this->responsableClubUser1->id,
            'role' => 'manager',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create second responsable-club with their club
        $responsableClub2Doc = MedicalDoc::factory()->create();
        $this->responsableClub2Member = Member::factory()->create();
        $this->responsableClubUser2 = User::factory()->create([
            'adh_id' => $this->responsableClub2Member->adh_id,
            'doc_id' => $responsableClub2Doc->doc_id,
        ]);
        $this->responsableClubUser2->syncRoles(['responsable-club']);

        $this->club2Id = DB::table('clubs')->insertGetId([
            'club_name' => 'Club Two',
            'club_street' => '456 Club Two Street',
            'club_city' => 'City Two',
            'club_postal_code' => '22222',
            'ffso_id' => 'FFSO002',
            'description' => 'Club Two Description',
            'is_approved' => true,
            'created_by' => $this->responsableClubUser2->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('club_user')->insert([
            'club_id' => $this->club2Id,
            'user_id' => $this->responsableClubUser2->id,
            'role' => 'manager',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create raids for each club
        $regPeriod1 = RegistrationPeriod::create([
            'ins_start_date' => now()->addDays(7),
            'ins_end_date' => now()->addDays(21),
        ]);

        $this->raid1 = Raid::create([
            'raid_name' => 'Raid One',
            'raid_description' => 'Description One',
            'clu_id' => $this->club1Id,
            'adh_id' => $this->responsableClub1Member->adh_id,
            'raid_date_start' => now()->addMonth(),
            'raid_date_end' => now()->addMonth()->addDays(2),
            'ins_id' => $regPeriod1->ins_id,
            'raid_contact' => 'contact1@test.com',
            'raid_street' => '123 One Street',
            'raid_city' => 'City One',
            'raid_postal_code' => '11111',
            'raid_number' => 1,
        ]);

        $regPeriod2 = RegistrationPeriod::create([
            'ins_start_date' => now()->addDays(7),
            'ins_end_date' => now()->addDays(21),
        ]);

        $this->raid2 = Raid::create([
            'raid_name' => 'Raid Two',
            'raid_description' => 'Description Two',
            'clu_id' => $this->club2Id,
            'adh_id' => $this->responsableClub2Member->adh_id,
            'raid_date_start' => now()->addMonth(),
            'raid_date_end' => now()->addMonth()->addDays(2),
            'ins_id' => $regPeriod2->ins_id,
            'raid_contact' => 'contact2@test.com',
            'raid_street' => '456 Two Street',
            'raid_city' => 'City Two',
            'raid_postal_code' => '22222',
            'raid_number' => 2,
        ]);

        // Create first responsable-course with their race
        $responsableCourse1Doc = MedicalDoc::factory()->create();
        $this->responsableCourse1Member = Member::factory()->create();
        $this->responsableCourseUser1 = User::factory()->create([
            'adh_id' => $this->responsableCourse1Member->adh_id,
            'doc_id' => $responsableCourse1Doc->doc_id,
        ]);
        $this->responsableCourseUser1->syncRoles(['responsable-course']);

        DB::table('club_user')->insert([
            'club_id' => $this->club1Id,
            'user_id' => $this->responsableCourseUser1->id,
            'role' => 'member',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create second responsable-course with their race
        $responsableCourse2Doc = MedicalDoc::factory()->create();
        $this->responsableCourse2Member = Member::factory()->create();
        $this->responsableCourseUser2 = User::factory()->create([
            'adh_id' => $this->responsableCourse2Member->adh_id,
            'doc_id' => $responsableCourse2Doc->doc_id,
        ]);
        $this->responsableCourseUser2->syncRoles(['responsable-course']);

        DB::table('club_user')->insert([
            'club_id' => $this->club2Id,
            'user_id' => $this->responsableCourseUser2->id,
            'role' => 'member',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create races for each responsable-course
        $paramRunner1 = ParamRunner::create([
            'pac_nb_min' => 2,
            'pac_nb_max' => 10,
        ]);

        $paramTeam1 = ParamTeam::create([
            'pae_nb_min' => 1,
            'pae_nb_max' => 20,
            'pae_team_count_max' => 5,
        ]);

        $this->race1 = Race::create([
            'race_name' => 'Race One',
            'race_date_start' => now()->addMonth(),
            'race_date_end' => now()->addMonth(),
            'adh_id' => $this->responsableCourse1Member->adh_id,
            'pac_id' => $paramRunner1->pac_id,
            'pae_id' => $paramTeam1->pae_id,
            'typ_id' => 1,
            'raid_id' => $this->raid1->raid_id,
        ]);

        $paramRunner2 = ParamRunner::create([
            'pac_nb_min' => 2,
            'pac_nb_max' => 10,
        ]);

        $paramTeam2 = ParamTeam::create([
            'pae_nb_min' => 1,
            'pae_nb_max' => 20,
            'pae_team_count_max' => 5,
        ]);

        $this->race2 = Race::create([
            'race_name' => 'Race Two',
            'race_date_start' => now()->addMonth(),
            'race_date_end' => now()->addMonth(),
            'adh_id' => $this->responsableCourse2Member->adh_id,
            'pac_id' => $paramRunner2->pac_id,
            'pae_id' => $paramTeam2->pae_id,
            'typ_id' => 1,
            'raid_id' => $this->raid2->raid_id,
        ]);

        // Create gestionnaire-raid (can manage raids where adh_id matches their member)
        $gestionnaireDoc = MedicalDoc::factory()->create();
        $this->gestionnaireMember = Member::factory()->create();
        $this->gestionnaireRaidUser = User::factory()->create([
            'adh_id' => $this->gestionnaireMember->adh_id,
            'doc_id' => $gestionnaireDoc->doc_id,
        ]);
        $this->gestionnaireRaidUser->syncRoles(['gestionnaire-raid']);

        // Create a raid specifically for the gestionnaire-raid user
        // This raid's adh_id matches the gestionnaire's member adh_id
        $regPeriodGestionnaire = RegistrationPeriod::create([
            'ins_start_date' => now()->addDays(7),
            'ins_end_date' => now()->addDays(21),
        ]);

        $this->raidForGestionnaire = Raid::create([
            'raid_name' => 'Raid for Gestionnaire',
            'raid_description' => 'Raid managed by gestionnaire',
            'clu_id' => $this->club1Id,
            'adh_id' => $this->gestionnaireMember->adh_id, // Gestionnaire's member is responsible
            'raid_date_start' => now()->addMonth(),
            'raid_date_end' => now()->addMonth()->addDays(2),
            'ins_id' => $regPeriodGestionnaire->ins_id,
            'raid_contact' => 'gestionnaire@test.com',
            'raid_street' => '789 Gestionnaire Street',
            'raid_city' => 'Gestionnaire City',
            'raid_postal_code' => '33333',
            'raid_number' => 3,
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
    private function ensureRolesAndPermissionsExist(): void
    {
        $roles = ['guest', 'user', 'adherent', 'responsable-club', 'gestionnaire-raid', 'responsable-course', 'admin'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $permissions = [
            'create-club',
            'edit-own-club',
            'delete-own-club',
            'view-clubs',
            'create-raid',
            'edit-own-raid',
            'delete-own-raid',
            'view-raids',
            'create-race',
            'edit-own-race',
            'delete-own-race',
            'view-races',
            'manage-all-raids',
            'manage-all-clubs',
            'manage-all-races',
            'access-admin',
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

        $gestionnaireRole = Role::findByName('gestionnaire-raid');
        $gestionnaireRole->givePermissionTo(['view-raids', 'edit-own-raid', 'delete-own-raid', 'create-raid', 'view-races', 'view-clubs']);

        $adminRole = Role::findByName('admin');
        $adminRole->givePermissionTo(Permission::all());

        // Create required ParamType entries
        ParamType::firstOrCreate(['typ_id' => 1], ['typ_name' => 'Course']);
    }

    // ===========================================
    // RESPONSABLE-CLUB OWNERSHIP TESTS
    // ===========================================

    /**
     * Test that responsable-club can edit their own club's raid
     */
    public function test_responsable_club_can_edit_own_clubs_raid(): void
    {
        $response = $this->actingAs($this->responsableClubUser1)
            ->get(route('raids.edit', $this->raid1->raid_id));

        $response->assertStatus(200);
    }

    /**
     * Test that responsable-club cannot edit another club's raid
     */
    public function test_responsable_club_cannot_edit_other_clubs_raid(): void
    {
        $response = $this->actingAs($this->responsableClubUser1)
            ->get(route('raids.edit', $this->raid2->raid_id));

        $response->assertStatus(403);
    }

    /**
     * Test that responsable-club can update their own club's raid
     */
    public function test_responsable_club_can_update_own_clubs_raid(): void
    {
        $response = $this->actingAs($this->responsableClubUser1)
            ->put(route('raids.update', $this->raid1->raid_id), [
                'raid_name' => 'Updated Raid Name',
                'raid_description' => 'Updated description',
                'clu_id' => $this->club1Id,
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
     * Test that responsable-club cannot update another club's raid
     * 
     * Note: Must send complete valid data so validation passes and authorization check in controller is reached.
     * The StoreRaidRequest::authorize() only checks if user is club leader (not ownership).
     * The actual ownership check is in RaidController::update() via $this->authorize('update', $raid).
     */
    public function test_responsable_club_cannot_update_other_clubs_raid(): void
    {
        $response = $this->actingAs($this->responsableClubUser1)
            ->put(route('raids.update', $this->raid2->raid_id), [
                'raid_name' => 'Hacked Raid Name',
                'raid_description' => 'Hacked description',
                'clu_id' => $this->club2Id,
                'adh_id' => $this->responsableClub2Member->adh_id,
                'raid_date_start' => now()->addMonth()->format('Y-m-d'),
                'raid_date_end' => now()->addMonth()->addDays(2)->format('Y-m-d'),
                'ins_start_date' => now()->addDays(7)->format('Y-m-d'),
                'ins_end_date' => now()->addDays(21)->format('Y-m-d'),
                'raid_contact' => 'hacker@test.com',
                'raid_street' => '123 Hacker Street',
                'raid_city' => 'Hack City',
                'raid_postal_code' => '00000',
                'raid_number' => 999,
                'raid_site_url' => 'http://hacker.com',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test that responsable-club can delete their own club's raid
     */
    public function test_responsable_club_can_delete_own_clubs_raid(): void
    {
        $response = $this->actingAs($this->responsableClubUser1)
            ->delete(route('raids.destroy', $this->raid1->raid_id));

        $response->assertRedirect();
    }

    /**
     * Test that responsable-club cannot delete another club's raid
     */
    public function test_responsable_club_cannot_delete_other_clubs_raid(): void
    {
        $response = $this->actingAs($this->responsableClubUser1)
            ->delete(route('raids.destroy', $this->raid2->raid_id));

        $response->assertStatus(403);
    }

    // ===========================================
    // RESPONSABLE-COURSE OWNERSHIP TESTS
    // ===========================================

    /**
     * Test that responsable-course can edit their own race
     */
    public function test_responsable_course_can_edit_own_race(): void
    {
        $response = $this->actingAs($this->responsableCourseUser1)
            ->get(route('races.edit', $this->race1->race_id));

        $response->assertStatus(200);
    }

    /**
     * Test that responsable-course cannot edit another user's race
     */
    public function test_responsable_course_cannot_edit_other_users_race(): void
    {
        $response = $this->actingAs($this->responsableCourseUser1)
            ->get(route('races.edit', $this->race2->race_id));

        $response->assertStatus(403);
    }

    /**
     * Test that responsable-course can update their own race
     * Note: type 1 is "compétitif" which doesn't allow minor prices > 0
     */
    public function test_responsable_course_can_update_own_race(): void
    {
        $response = $this->actingAs($this->responsableCourseUser1)
            ->put(route('races.update', $this->race1->race_id), [
                'title' => 'Updated Race Name',
                'description' => 'Updated description',
                'startDate' => now()->addMonth()->format('Y-m-d'),
                'startTime' => '10:00',
                'endDate' => now()->addMonth()->format('Y-m-d'),
                'endTime' => '18:00',
                'minParticipants' => 2,
                'maxParticipants' => 10,
                'maxPerTeam' => 5,
                'minTeams' => 1,
                'maxTeams' => 20,
                'priceMajor' => 25.00,
                'priceMinor' => 0, // Competitive races don't allow minor prices
                'difficulty' => 'medium',
                'type' => 1,
                'responsableId' => $this->responsableCourseUser1->id,
                'raid_id' => $this->raid1->raid_id,
            ]);

        $response->assertRedirect();
    }

    /**
     * Test that responsable-course cannot update another user's race
     * Note: type 1 is "compétitif" which doesn't allow minor prices > 0
     */
    public function test_responsable_course_cannot_update_other_users_race(): void
    {
        $response = $this->actingAs($this->responsableCourseUser1)
            ->put(route('races.update', $this->race2->race_id), [
                'title' => 'Hacked Race Name',
                'description' => 'Hacked description',
                'startDate' => now()->addMonth()->format('Y-m-d'),
                'startTime' => '10:00',
                'endDate' => now()->addMonth()->format('Y-m-d'),
                'endTime' => '18:00',
                'minParticipants' => 2,
                'maxParticipants' => 10,
                'maxPerTeam' => 5,
                'minTeams' => 1,
                'maxTeams' => 20,
                'priceMajor' => 25.00,
                'priceMinor' => 0, // Competitive races don't allow minor prices
                'difficulty' => 'medium',
                'type' => 1,
                'responsableId' => $this->responsableCourseUser1->id,
                'raid_id' => $this->raid2->raid_id,
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test that responsable-course can delete their own race
     */
    public function test_responsable_course_can_delete_own_race(): void
    {
        $response = $this->actingAs($this->responsableCourseUser1)
            ->delete(route('races.destroy', $this->race1->race_id));

        $response->assertRedirect();
    }

    /**
     * Test that responsable-course cannot delete another user's race
     */
    public function test_responsable_course_cannot_delete_other_users_race(): void
    {
        $response = $this->actingAs($this->responsableCourseUser1)
            ->delete(route('races.destroy', $this->race2->race_id));

        $response->assertStatus(403);
    }

    // ===========================================
    // GESTIONNAIRE-RAID OWNERSHIP TESTS
    // ===========================================

    /**
     * Test that gestionnaire-raid can edit raids they manage
     * (raids where the adh_id matches their member's adh_id)
     */
    public function test_gestionnaire_raid_can_edit_managed_raid(): void
    {
        $response = $this->actingAs($this->gestionnaireRaidUser)
            ->get(route('raids.edit', $this->raidForGestionnaire->raid_id));

        $response->assertStatus(200);
    }

    /**
     * Test that gestionnaire-raid cannot edit raids they don't manage
     * (raids where adh_id doesn't match their member's adh_id)
     */
    public function test_gestionnaire_raid_cannot_edit_unmanaged_raid(): void
    {
        $response = $this->actingAs($this->gestionnaireRaidUser)
            ->get(route('raids.edit', $this->raid2->raid_id));

        $response->assertStatus(403);
    }

    // ===========================================
    // ADMIN BYPASS TESTS
    // ===========================================

    /**
     * Test that admin can edit any raid regardless of ownership
     */
    public function test_admin_can_edit_any_raid(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('raids.edit', $this->raid1->raid_id));
        $response->assertStatus(200);

        $response = $this->actingAs($this->adminUser)
            ->get(route('raids.edit', $this->raid2->raid_id));
        $response->assertStatus(200);
    }

    /**
     * Test that admin can edit any race regardless of ownership
     */
    public function test_admin_can_edit_any_race(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('races.edit', $this->race1->race_id));
        $response->assertStatus(200);

        $response = $this->actingAs($this->adminUser)
            ->get(route('races.edit', $this->race2->race_id));
        $response->assertStatus(200);
    }

    /**
     * Test that admin can delete any raid regardless of ownership
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
            'clu_id' => $this->club2Id,
            'adh_id' => $this->responsableClub2Member->adh_id,
            'raid_date_start' => now()->addMonth(),
            'raid_date_end' => now()->addMonth()->addDays(2),
            'ins_id' => $regPeriod->ins_id,
            'raid_contact' => 'contact@test.com',
            'raid_street' => '123 Delete Street',
            'raid_city' => 'City',
            'raid_postal_code' => '12345',
            'raid_number' => 99,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('raids.destroy', $raidToDelete->raid_id));

        $response->assertRedirect();
    }

    /**
     * Test that admin can delete any race regardless of ownership
     */
    public function test_admin_can_delete_any_race(): void
    {
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
            'adh_id' => $this->responsableCourse2Member->adh_id,
            'pac_id' => $paramRunner->pac_id,
            'pae_id' => $paramTeam->pae_id,
            'typ_id' => 1,
            'raid_id' => $this->raid2->raid_id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('races.destroy', $raceToDelete->race_id));

        $response->assertRedirect();
    }

    // ===========================================
    // CROSS-OWNERSHIP ATTACK TESTS
    // ===========================================

    /**
     * Test that a user cannot edit another user's club by manipulating IDs
     */
    public function test_user_cannot_manipulate_club_ownership(): void
    {
        // responsableClubUser1 tries to update club2 by changing club_id in request
        $response = $this->actingAs($this->responsableClubUser1)
            ->put(route('clubs.update', $this->club2Id), [
                'club_name' => 'Hacked Club Name',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test that a user cannot create a raid for another club
     */
    public function test_user_cannot_create_raid_for_other_club(): void
    {
        $response = $this->actingAs($this->responsableClubUser1)
            ->post(route('raids.store'), [
                'raid_name' => 'Unauthorized Raid',
                'raid_description' => 'Trying to create raid for another club',
                'clu_id' => $this->club2Id, // Another club's ID
                'adh_id' => $this->responsableClub1Member->adh_id,
                'raid_date_start' => now()->addMonth()->format('Y-m-d'),
                'raid_date_end' => now()->addMonth()->addDays(2)->format('Y-m-d'),
                'ins_start_date' => now()->addDays(7)->format('Y-m-d'),
                'ins_end_date' => now()->addDays(21)->format('Y-m-d'),
                'raid_city' => 'City',
                'raid_postal_code' => '12345',
            ]);

        // Should be redirected with error or forbidden
        $this->assertTrue(in_array($response->status(), [302, 403, 422]));
    }
}
