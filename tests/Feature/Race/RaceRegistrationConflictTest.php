<?php

namespace Tests\Feature\Race;

use App\Models\Club;
use App\Models\Race;
use App\Models\Raid;
use App\Models\RegistrationPeriod;
use App\Models\Team;
use App\Models\User;
use App\Models\Member;
use App\Models\MedicalDoc;
use App\Models\ParamTeam;
use App\Models\ParamRunner;
use App\Models\ParamDifficulty;
use App\Models\ParamType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

/**
 * Test suite for race registration conflicts
 * 
 * Verifies that users cannot register for overlapping races
 * within the same registration period.
 */
class RaceRegistrationConflictTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $teamMember;
    protected Club $club;
    protected RegistrationPeriod $registrationPeriod;
    protected Raid $raid;
    protected Race $race1;
    protected Race $race2;
    protected Team $team;

    /**
     * Set up the test environment before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create param_difficulty table if it doesn't exist (workaround for test database issue)
        DB::statement('CREATE TABLE IF NOT EXISTS param_difficulty (
            dif_id INTEGER PRIMARY KEY AUTOINCREMENT,
            dif_level VARCHAR(10) NOT NULL,
            created_at DATETIME,
            updated_at DATETIME
        )');

        // Create param_type table if it doesn't exist (workaround for test database issue)
        DB::statement('CREATE TABLE IF NOT EXISTS param_type (
            typ_id INTEGER PRIMARY KEY AUTOINCREMENT,
            typ_name VARCHAR(100) NOT NULL,
            created_at DATETIME,
            updated_at DATETIME
        )');

        // Create users first
        $this->user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
        ]);

        // Create member with valid licence for the user
        $member1 = Member::create([
            'adh_license' => 'LIC123456',
            'adh_end_validity' => now()->addYear(),
            'adh_date_added' => now(),
        ]);
        $this->user->adh_id = $member1->adh_id;
        $this->user->save();

        $this->teamMember = User::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
        ]);

        // Create member with valid licence for the team member
        $member2 = Member::create([
            'adh_license' => 'LIC789012',
            'adh_end_validity' => now()->addYear(),
            'adh_date_added' => now(),
        ]);
        $this->teamMember->adh_id = $member2->adh_id;
        $this->teamMember->save();

        // Create a registration period
        $this->registrationPeriod = RegistrationPeriod::create([
            'ins_start_date' => now()->subDays(10),
            'ins_end_date' => now()->addDays(30),
        ]);

        // Create a club
        $this->club = Club::create([
            'club_name' => 'Test Club',
            'club_street' => '123 Test Street',
            'club_city' => 'Test City',
            'club_postal_code' => '12345',
            'created_by' => $this->user->id,
        ]);

        // Create a raid with the registration period
        $this->raid = Raid::create([
            'raid_name' => 'Test Raid 2026',
            'raid_description' => 'Test raid for registration conflict tests',
            'raid_date_start' => now()->addDays(45),
            'raid_date_end' => now()->addDays(47),
            'adh_id' => $member1->adh_id, // Organizer of the raid
            'clu_id' => $this->club->club_id,
            'ins_id' => $this->registrationPeriod->ins_id,
            'raid_contact' => 'test@example.com',
            'raid_street' => '123 Raid Street',
            'raid_city' => 'Test City',
            'raid_postal_code' => '12345',
            'raid_number' => 1,
        ]);

        // Create team parameters
        $teamParams = ParamTeam::create([
            'pae_nb_min' => 1, // Minimum participants per team
            'pae_nb_max' => 10, // Maximum participants per team
            'pae_team_count_max' => 5, // Maximum number of teams
        ]);

        // Create runner parameters
        $runnerParams = ParamRunner::create([
            'pac_nb_min' => 1,
            'pac_nb_max' => 1,
        ]);

        // Create difficulty parameter directly via DB insert (check if not exists)
        if (DB::table('param_difficulty')->where('dif_id', 1)->doesntExist()) {
            DB::table('param_difficulty')->insert([
                'dif_id' => 1,
                'dif_level' => 'medium',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create type parameter directly via DB insert (check if not exists)
        if (DB::table('param_type')->where('typ_id', 1)->doesntExist()) {
            DB::table('param_type')->insert([
                'typ_id' => 1,
                'typ_name' => 'Trail',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create two races in the same raid (same registration period)
        $this->race1 = Race::create([
            'race_name' => 'Race 1 - Morning',
            'race_date_start' => now()->addDays(45)->setTime(9, 0),
            'race_date_end' => now()->addDays(45)->setTime(12, 0),
            'raid_id' => $this->raid->raid_id,
            'adh_id' => $member1->adh_id, // Organizer of the race
            'pac_id' => $runnerParams->pac_id,
            'pae_id' => $teamParams->pae_id,
            'dif_id' => 1, // Reference to the difficulty we inserted
            'typ_id' => 1, // Reference to the type we inserted
        ]);

        $this->race2 = Race::create([
            'race_name' => 'Race 2 - Afternoon',
            'race_date_start' => now()->addDays(45)->setTime(14, 0),
            'race_date_end' => now()->addDays(45)->setTime(17, 0),
            'raid_id' => $this->raid->raid_id,
            'adh_id' => $member1->adh_id, // Organizer of the race
            'pac_id' => $runnerParams->pac_id,
            'pae_id' => $teamParams->pae_id,
            'dif_id' => 1, // Reference to the difficulty we inserted
            'typ_id' => 1, // Reference to the type we inserted
        ]);

        // Create a team with the user as leader and another member
        $this->team = Team::create([
            'equ_name' => 'Test Team',
            'user_id' => $this->user->id,
        ]);

        // Add team members to the team via has_participate table
        DB::table('has_participate')->insert([
            ['equ_id' => $this->team->equ_id, 'id_users' => $this->user->id, 'created_at' => now(), 'updated_at' => now()],
            ['equ_id' => $this->team->equ_id, 'id_users' => $this->teamMember->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Note: Permissions are tested separately - skipping for this validation test
    }

    /**
     * Test that a user can register for a race when they have no conflicts.
     *
     * @return void
     */
    public function test_user_can_register_when_no_conflicts(): void
    {
        // Act as the user
        $response = $this->actingAs($this->user)
            ->post(route('race.registerTeam', $this->race1), [
                'team_id' => $this->team->equ_id,
            ]);

        // Assert the registration was successful
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify the team is registered in the database
        $this->assertDatabaseHas('registration', [
            'race_id' => $this->race1->race_id,
            'equ_id' => $this->team->equ_id,
        ]);
    }

    /**
     * Test that the current user cannot register for another race in the same period.
     *
     * @return void
     */
    public function test_user_cannot_register_when_already_registered_in_same_period(): void
    {
        // First, register the team for race1
        $this->registerTeamForRace($this->team, $this->race1);

        // Try to register the same team for race2 (should fail)
        $response = $this->actingAs($this->user)
            ->post(route('race.registerTeam', $this->race2), [
                'team_id' => $this->team->equ_id,
            ]);

        // Assert validation error
        $response->assertSessionHasErrors('team_id');
        
        // Verify error message mentions the user is already registered
        $errors = session('errors');
        $teamIdErrors = $errors->get('team_id');
        
        $this->assertNotEmpty($teamIdErrors);
        $this->assertStringContainsString('already registered', $teamIdErrors[0]);

        // Verify the team is NOT registered for race2
        $this->assertDatabaseMissing('registration', [
            'race_id' => $this->race2->race_id,
            'equ_id' => $this->team->equ_id,
        ]);
    }

    /**
     * Test that a team cannot register when one of its members is already in another race.
     *
     * @return void
     */
    public function test_team_cannot_register_when_member_has_conflict(): void
    {
        // Create another team with the team member as leader
        $otherTeam = Team::create([
            'equ_name' => 'Other Test Team',
            'user_id' => $this->teamMember->id,
        ]);

        // Create another user for the other team
        $anotherMember = Member::create([
            'adh_first_name' => 'Another',
            'adh_last_name' => 'Member',
            'adh_address' => '123 Test St',
            'adh_city' => 'Test City',
            'adh_postal_code' => '12345',
            'adh_country' => 'FR',
            'adh_license' => 'LIC999999',
            'adh_end_validity' => now()->addYear(),
            'adh_date_added' => now(),
            'club_id' => $this->club->club_id,
            'created_by' => $this->user->id,
        ]);

        $anotherDoc = MedicalDoc::create([
            'doc_num_pps' => 'PPS999999',
            'doc_end_validity' => now()->addYear(),
            'doc_date_added' => now(),
        ]);

        $anotherUser = User::factory()->create([
            'adh_id' => $anotherMember->adh_id,
            'doc_id' => $anotherDoc->doc_id,
        ]);

        // Add members to the other team
        DB::table('has_participate')->insert([
            ['equ_id' => $otherTeam->equ_id, 'id_users' => $this->teamMember->id, 'created_at' => now(), 'updated_at' => now()],
            ['equ_id' => $otherTeam->equ_id, 'id_users' => $anotherUser->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Register the other team for race1
        $this->registerTeamForRace($otherTeam, $this->race1);

        // Note: Permissions tested separately - skipping for this validation test

        // Now try to register the original team (which includes teamMember) for race2
        $response = $this->actingAs($this->user)
            ->post(route('race.registerTeam', $this->race2), [
                'team_id' => $this->team->equ_id,
            ]);

        // Assert validation error
        $response->assertSessionHasErrors('team_id');

        // Verify error message mentions a team member is already registered
        $errors = session('errors');
        $teamIdErrors = $errors->get('team_id');
        
        $this->assertNotEmpty($teamIdErrors);
        $this->assertStringContainsString('already registered', $teamIdErrors[0]);

        // Verify the team is NOT registered for race2
        $this->assertDatabaseMissing('registration', [
            'race_id' => $this->race2->race_id,
            'equ_id' => $this->team->equ_id,
        ]);
    }

    /**
     * Test that a user with only PPS (non-adherent) cannot register when already registered in same period.
     *
     * @return void
     */
    public function test_non_adherent_with_pps_cannot_register_when_already_registered(): void
    {
        // Bypass all policy checks for this test - we're only testing the validation rule
        \Illuminate\Support\Facades\Gate::before(function () {
            return true;
        });
        
        // Create a non-adherent user (only PPS, no licence)
        $ppsDoc = MedicalDoc::create([
            'doc_num_pps' => 'PPS-NON-ADH-001',
            'doc_end_validity' => now()->addYear(),
            'doc_date_added' => now(),
        ]);

        $nonAdherentUser = User::factory()->create([
            'first_name' => 'Non',
            'last_name' => 'Adherent',
            'email' => 'non.adherent@example.com',
            'doc_id' => $ppsDoc->doc_id,
            'adh_id' => null, // No member/licence
        ]);

        // Create a team for the non-adherent user
        $nonAdherentTeam = Team::create([
            'equ_name' => 'Non-Adherent Team',
            'user_id' => $nonAdherentUser->id,
        ]);

        // Manually add the user to the team using the pivot table
        // This is what would normally be done by the Team model
        DB::table('has_participate')->insert([
            'equ_id' => $nonAdherentTeam->equ_id,
            'id_users' => $nonAdherentUser->id,
            'adh_id' => null, // Non-adherent has no adh_id
            'reg_id' => null, // Will be set when registering for a race
            'par_time' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Register the non-adherent team for race1
        $this->registerTeamForRace($nonAdherentTeam, $this->race1);

        // Try to register the same team for race2 (same period) - should fail
        $response = $this->actingAs($nonAdherentUser)
            ->post(route('race.registerTeam', $this->race2), [
                'team_id' => $nonAdherentTeam->equ_id,
            ]);

        // Assert validation error
        $response->assertSessionHasErrors('team_id');

        // Verify error message mentions the user is already registered
        $errors = session('errors');
        $teamIdErrors = $errors->get('team_id');
        
        $this->assertNotEmpty($teamIdErrors);
        $this->assertStringContainsString('already registered', $teamIdErrors[0]);

        // Verify the team is NOT registered for race2
        $this->assertDatabaseMissing('registration', [
            'race_id' => $this->race2->race_id,
            'equ_id' => $nonAdherentTeam->equ_id,
        ]);

        // Verify the team IS still registered for race1
        $this->assertDatabaseHas('registration', [
            'race_id' => $this->race1->race_id,
            'equ_id' => $nonAdherentTeam->equ_id,
        ]);
    }

    /**
     * Test that a user can register for races in different registration periods.
     *
     * @return void
     */
    public function test_user_can_register_for_races_in_different_periods(): void
    {
        // Register team for race1 in the current period
        $this->registerTeamForRace($this->team, $this->race1);

        // Create a different registration period
        $differentPeriod = RegistrationPeriod::create([
            'ins_start_date' => now()->addMonths(2),
            'ins_end_date' => now()->addMonths(3),
        ]);

        // Create a raid in the different period
        $differentRaid = Raid::create([
            'raid_name' => 'Different Raid',
            'raid_description' => 'Raid in a different period',
            'raid_date_start' => now()->addMonths(4),
            'raid_date_end' => now()->addMonths(4)->addDays(2),
            'clu_id' => $this->club->club_id,
            'ins_id' => $differentPeriod->ins_id,
            'raid_city' => 'Test City',
            'raid_postal_code' => '12345',
            'adh_id' => $this->user->member->adh_id,
            'raid_contact' => 'contact@test.com',
            'raid_street' => '123 Test St',
            'raid_number' => '123',
        ]);

        // Create team params for the new race
        $teamParams = ParamTeam::first();
        $difficultyParams = ParamDifficulty::first();
        $runnerParams = ParamRunner::first();
        $typeParams = ParamType::first();

        // Create a race in the different period
        $raceInDifferentPeriod = Race::create([
            'race_name' => 'Race in Different Period',
            'race_description' => 'This is in a different registration period',
            'race_date_start' => now()->addMonths(4),
            'race_date_end' => now()->addMonths(4)->addHours(3),
            'raid_id' => $differentRaid->raid_id,
            'pae_id' => $teamParams->pae_id,
            'adh_id' => $this->user->member->adh_id,
            'pac_id' => $runnerParams->pac_id,
            'dif_id' => $difficultyParams->dif_id,
            'typ_id' => $typeParams->typ_id,
            'price_major' => 10.00,
            'price_minor' => 5.00,
            'price_adherent' => 8.00,
        ]);

        // Try to register for the race in the different period (should succeed)
        $response = $this->actingAs($this->user)
            ->post(route('race.registerTeam', $raceInDifferentPeriod), [
                'team_id' => $this->team->equ_id,
            ]);

        // Assert the registration was successful
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify both registrations exist
        $this->assertDatabaseHas('registration', [
            'race_id' => $this->race1->race_id,
            'equ_id' => $this->team->equ_id,
        ]);

        $this->assertDatabaseHas('registration', [
            'race_id' => $raceInDifferentPeriod->race_id,
            'equ_id' => $this->team->equ_id,
        ]);
    }

    /**
     * Helper method to register a team for a race.
     *
     * @param Team $team
     * @param Race $race
     * @return void
     */
    protected function registerTeamForRace(Team $team, Race $race): void
    {
        // Create payment record
        $paiId = DB::table('inscriptions_payment')->insertGetId([
            'pai_date' => now(),
            'pai_is_paid' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create medical doc record
        $docId = DB::table('medical_docs')->insertGetId([
            'doc_num_pps' => 'TEST-PPS-' . time(),
            'doc_end_validity' => now()->addYear(),
            'doc_date_added' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Register the team
        $regId = DB::table('registration')->insertGetId([
            'equ_id' => $team->equ_id,
            'race_id' => $race->race_id,
            'pay_id' => $paiId,
            'doc_id' => $docId,
            'reg_validated' => false,
            'reg_points' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Get all team members
        $team->refresh();
        $teamMembers = $team->users()->get();
        
        // Create or update has_participate records for all team members
        foreach ($teamMembers as $member) {
            // Check if has_participate record already exists for this team and user
            $exists = DB::table('has_participate')
                ->where('equ_id', $team->equ_id)
                ->where('id_users', $member->id)
                ->exists();

            if ($exists) {
                // Update existing record with registration ID
                DB::table('has_participate')
                    ->where('equ_id', $team->equ_id)
                    ->where('id_users', $member->id)
                    ->update([
                        'reg_id' => $regId,
                        'updated_at' => now(),
                    ]);
            } else {
                // Insert new record
                DB::table('has_participate')->insert([
                    'equ_id' => $team->equ_id,
                    'id_users' => $member->id,
                    'adh_id' => $member->member?->adh_id, // Can be null for non-adherents
                    'reg_id' => $regId,
                    'par_time' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
