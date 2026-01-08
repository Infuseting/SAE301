<?php

namespace Tests\Feature\Race;

use App\Models\Club;
use App\Models\MedicalDoc;
use App\Models\Member;
use App\Models\Race;
use App\Models\Raid;
use App\Models\RegistrationPeriod;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test class for race registration credential validation
 * 
 * Ensures that users cannot register for races without valid credentials (licence or PPS)
 */
class RaceRegistrationCredentialsTest extends TestCase
{
    use RefreshDatabase;

    private User $userWithLicence;
    private User $userWithPPS;
    private User $userWithoutCredentials;
    private Team $team;
    private Race $race;
    private Club $club;
    private Raid $raid;
    private RegistrationPeriod $registrationPeriod;

    protected function setUp(): void
    {
        parent::setUp();

        // Create registration period
        $this->registrationPeriod = RegistrationPeriod::create([
            'ins_start_date' => now()->subDays(5),
            'ins_end_date' => now()->addDays(35),
        ]);

        // Create user WITH valid licence
        $this->userWithLicence = User::create([
            'last_name' => 'WithLicence',
            'first_name' => 'User',
            'email' => 'with.licence@test.com',
            'password' => bcrypt('password'),
            'phone' => '1234567890',
            'birthdate' => '1990-01-01',
            'gender' => 'M',
            'street' => 'Test Street',
            'city' => 'Test City',
            'postal_code' => '12345',
        ]);

        $member1 = Member::create([
            'adh_license' => 'LIC-001-2026',
            'adh_end_validity' => now()->addYear(),
            'adh_date_added' => now(),
        ]);

        $this->userWithLicence->update(['adh_id' => $member1->adh_id]);

        // Create user WITH valid PPS (non-adherent)
        $this->userWithPPS = User::create([
            'last_name' => 'WithPPS',
            'first_name' => 'User',
            'email' => 'with.pps@test.com',
            'password' => bcrypt('password'),
            'phone' => '1234567891',
            'birthdate' => '1990-01-01',
            'gender' => 'F',
            'street' => 'Test Street',
            'city' => 'Test City',
            'postal_code' => '12345',
        ]);

        $pps1 = MedicalDoc::create([
            'doc_num_pps' => 'PPS-001-2026',
            'doc_end_validity' => now()->addYear(),
        ]);

        $this->userWithPPS->update(['doc_id' => $pps1->doc_id]);

        // Create user WITHOUT credentials
        $this->userWithoutCredentials = User::create([
            'last_name' => 'NoCredentials',
            'first_name' => 'User',
            'email' => 'no.credentials@test.com',
            'password' => bcrypt('password'),
            'phone' => '1234567892',
            'birthdate' => '1990-01-01',
            'gender' => 'M',
            'street' => 'Test Street',
            'city' => 'Test City',
            'postal_code' => '12345',
        ]);
        // No adh_id and no doc_id - no credentials

        // Create a club
        $this->club = Club::create([
            'club_name' => 'Test Club',
            'club_street' => '123 Test Street',
            'club_city' => 'Test City',
            'club_postal_code' => '12345',
            'created_by' => $this->userWithLicence->id,
        ]);

        // Create a raid
        $this->raid = Raid::create([
            'raid_name' => 'Test Raid 2026',
            'raid_description' => 'Test raid for credential validation',
            'raid_date_start' => now()->addDays(45),
            'raid_date_end' => now()->addDays(47),
            'adh_id' => $member1->adh_id,
            'clu_id' => $this->club->club_id,
            'ins_id' => $this->registrationPeriod->ins_id,
            'raid_contact' => 'test@example.com',
            'raid_address' => '123 Raid Street',
            'raid_city' => 'Raid City',
            'raid_postal_code' => '54321',
            'raid_latitude' => 45.0,
            'raid_longitude' => 5.0,
        ]);

        // Create a race
        $this->race = Race::create([
            'race_name' => 'Test Race',
            'race_date_time' => now()->addDays(46)->setTime(9, 0),
            'race_inscription_price' => 25.00,
            'race_min_runners' => 1,
            'race_max_runners' => 2,
            'raid_id' => $this->raid->raid_id,
        ]);

        // Create a team with the user who has a licence (captain)
        $this->team = Team::create([
            'team_name' => 'Test Team',
            'race_id' => $this->race->race_id,
            'user_id' => $this->userWithLicence->id,
        ]);
    }

    /**
     * Test that registration is blocked when a team member has no credentials
     */
    public function test_registration_blocked_when_team_member_has_no_credentials(): void
    {
        // Add user without credentials to the team
        $this->team->members()->attach($this->userWithoutCredentials->id);

        $this->actingAs($this->userWithLicence);

        $response = $this->postJson("/api/races/{$this->race->race_id}/register/{$this->team->team_id}");

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'User NoCredentials does not have a valid licence or PPS',
        ]);
    }

    /**
     * Test that registration succeeds when all team members have valid licences
     */
    public function test_registration_succeeds_with_all_members_having_licence(): void
    {
        // Captain already has licence, no additional members needed for this test
        
        $this->actingAs($this->userWithLicence);

        $response = $this->postJson("/api/races/{$this->race->race_id}/register/{$this->team->team_id}");

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Registration successful',
        ]);
    }

    /**
     * Test that registration succeeds when all team members have valid PPS
     */
    public function test_registration_succeeds_with_member_having_pps(): void
    {
        // Add user with PPS to the team
        $this->team->members()->attach($this->userWithPPS->id);

        $this->actingAs($this->userWithLicence);

        $response = $this->postJson("/api/races/{$this->race->race_id}/register/{$this->team->team_id}");

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Registration successful',
        ]);
    }

    /**
     * Test that registration is blocked when a team member has expired licence
     */
    public function test_registration_blocked_when_team_member_has_expired_licence(): void
    {
        // Create user with EXPIRED licence
        $userWithExpiredLicence = User::create([
            'last_name' => 'ExpiredLicence',
            'first_name' => 'User',
            'email' => 'expired.licence@test.com',
            'password' => bcrypt('password'),
            'phone' => '1234567893',
            'birthdate' => '1990-01-01',
            'gender' => 'M',
            'street' => 'Test Street',
            'city' => 'Test City',
            'postal_code' => '12345',
        ]);

        $expiredMember = Member::create([
            'adh_license' => 'LIC-EXPIRED-2025',
            'adh_end_validity' => now()->subDays(10), // Expired 10 days ago
            'adh_date_added' => now()->subYear(),
        ]);

        $userWithExpiredLicence->update(['adh_id' => $expiredMember->adh_id]);

        // Add user with expired licence to the team
        $this->team->members()->attach($userWithExpiredLicence->id);

        $this->actingAs($this->userWithLicence);

        $response = $this->postJson("/api/races/{$this->race->race_id}/register/{$this->team->team_id}");

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'User ExpiredLicence does not have a valid licence or PPS',
        ]);
    }

    /**
     * Test that registration is blocked when a team member has expired PPS
     */
    public function test_registration_blocked_when_team_member_has_expired_pps(): void
    {
        // Create user with EXPIRED PPS
        $userWithExpiredPPS = User::create([
            'last_name' => 'ExpiredPPS',
            'first_name' => 'User',
            'email' => 'expired.pps@test.com',
            'password' => bcrypt('password'),
            'phone' => '1234567894',
            'birthdate' => '1990-01-01',
            'gender' => 'F',
            'street' => 'Test Street',
            'city' => 'Test City',
            'postal_code' => '12345',
        ]);

        $expiredPPS = MedicalDoc::create([
            'doc_num_pps' => 'PPS-EXPIRED-2025',
            'doc_end_validity' => now()->subDays(5), // Expired 5 days ago
        ]);

        $userWithExpiredPPS->update(['doc_id' => $expiredPPS->doc_id]);

        // Add user with expired PPS to the team
        $this->team->members()->attach($userWithExpiredPPS->id);

        $this->actingAs($this->userWithLicence);

        $response = $this->postJson("/api/races/{$this->race->race_id}/register/{$this->team->team_id}");

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'User ExpiredPPS does not have a valid licence or PPS',
        ]);
    }

    /**
     * Test that registration is blocked when team member has pending PPS
     */
    public function test_registration_blocked_when_team_member_has_pending_pps(): void
    {
        // Create user with PENDING PPS
        $userWithPendingPPS = User::create([
            'last_name' => 'PendingPPS',
            'first_name' => 'User',
            'email' => 'pending.pps@test.com',
            'password' => bcrypt('password'),
            'phone' => '1234567895',
            'birthdate' => '1990-01-01',
            'gender' => 'M',
            'street' => 'Test Street',
            'city' => 'Test City',
            'postal_code' => '12345',
        ]);

        $pendingPPS = MedicalDoc::create([
            'doc_num_pps' => 'PENDING-12345', // Starts with PENDING-
            'doc_end_validity' => now()->addYear(),
        ]);

        $userWithPendingPPS->update(['doc_id' => $pendingPPS->doc_id]);

        // Add user with pending PPS to the team
        $this->team->members()->attach($userWithPendingPPS->id);

        $this->actingAs($this->userWithLicence);

        $response = $this->postJson("/api/races/{$this->race->race_id}/register/{$this->team->team_id}");

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'User PendingPPS does not have a valid licence or PPS',
        ]);
    }
}
