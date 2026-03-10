<?php

namespace Tests\Feature\Race;

use App\Models\Club;
use App\Models\MedicalDoc;
use App\Models\Member;
use App\Models\Race;
use App\Models\Raid;
use App\Models\RegistrationPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Test class for race registration credential validation
 *
 * Ensures that users cannot register for races without valid credentials (licence or PPS).
 * Tests use the API endpoint POST /api/races/{race}/register which checks
 * the authenticated user's credentials via LicenceService::hasValidCredentials.
 */
class RaceRegistrationCredentialsTest extends TestCase
{
    use RefreshDatabase;

    private User $userWithLicence;
    private User $userWithPPS;
    private User $userWithoutCredentials;
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

        // Create member with valid licence
        $member1 = Member::create([
            'adh_license' => 'LIC-001-2026',
            'adh_end_validity' => now()->addYear(),
            'adh_date_added' => now(),
        ]);

        // Create user WITH valid licence
        $this->userWithLicence = User::factory()->create([
            'adh_id' => $member1->adh_id,
        ]);

        // Create valid PPS document
        $pps1 = MedicalDoc::factory()->create([
            'doc_num_pps' => 'PPS-001-2026',
            'doc_end_validity' => now()->addYear(),
        ]);

        // Create user WITH valid PPS (non-adherent)
        $this->userWithPPS = User::factory()->create([
            'adh_id' => null,
            'doc_id' => $pps1->doc_id,
        ]);

        // Create user WITHOUT credentials
        $this->userWithoutCredentials = User::factory()->create([
            'adh_id' => null,
            'doc_id' => null,
        ]);

        // Create a club
        $this->club = Club::factory()->create([
            'created_by' => $this->userWithLicence->id,
        ]);

        // Create a raid
        $this->raid = Raid::factory()->create([
            'clu_id' => $this->club->club_id,
            'adh_id' => $member1->adh_id,
            'ins_id' => $this->registrationPeriod->ins_id,
        ]);

        // Create a race using factory for correct field defaults
        $this->race = Race::factory()->create([
            'raid_id' => $this->raid->raid_id,
            'adh_id' => $member1->adh_id,
        ]);
    }

    /**
     * Test that registration is blocked when user has no credentials (no licence, no PPS)
     */
    public function test_registration_blocked_when_team_member_has_no_credentials(): void
    {
        Sanctum::actingAs($this->userWithoutCredentials);

        $response = $this->postJson("/api/races/{$this->race->race_id}/register");

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'needs_credentials' => true,
        ]);
    }

    /**
     * Test that registration succeeds when user has a valid licence
     */
    public function test_registration_succeeds_with_all_members_having_licence(): void
    {
        Sanctum::actingAs($this->userWithLicence);

        $response = $this->postJson("/api/races/{$this->race->race_id}/register");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    /**
     * Test that registration succeeds when user has a valid PPS
     */
    public function test_registration_succeeds_with_member_having_pps(): void
    {
        Sanctum::actingAs($this->userWithPPS);

        $response = $this->postJson("/api/races/{$this->race->race_id}/register");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    /**
     * Test that registration is blocked when user has an expired licence
     */
    public function test_registration_blocked_when_team_member_has_expired_licence(): void
    {
        $expiredMember = Member::create([
            'adh_license' => 'LIC-EXPIRED-2025',
            'adh_end_validity' => now()->subDays(10),
            'adh_date_added' => now()->subYear(),
        ]);

        $userWithExpiredLicence = User::factory()->create([
            'adh_id' => $expiredMember->adh_id,
            'doc_id' => null,
        ]);

        Sanctum::actingAs($userWithExpiredLicence);

        $response = $this->postJson("/api/races/{$this->race->race_id}/register");

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'needs_credentials' => true,
        ]);
    }

    /**
     * Test that registration is blocked when user has an expired PPS
     */
    public function test_registration_blocked_when_team_member_has_expired_pps(): void
    {
        $expiredPPS = MedicalDoc::factory()->create([
            'doc_num_pps' => 'PPS-EXPIRED-2025',
            'doc_end_validity' => now()->subDays(5),
        ]);

        $userWithExpiredPPS = User::factory()->create([
            'adh_id' => null,
            'doc_id' => $expiredPPS->doc_id,
        ]);

        Sanctum::actingAs($userWithExpiredPPS);

        $response = $this->postJson("/api/races/{$this->race->race_id}/register");

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'needs_credentials' => true,
        ]);
    }

    /**
     * Test that registration is blocked when user has no valid credentials
     * (has PPS document but with past validity date)
     */
    public function test_registration_blocked_when_team_member_has_pending_pps(): void
    {
        $pendingPPS = MedicalDoc::factory()->create([
            'doc_num_pps' => 'PENDING-12345',
            'doc_end_validity' => now()->subDay(),
        ]);

        $userWithPendingPPS = User::factory()->create([
            'adh_id' => null,
            'doc_id' => $pendingPPS->doc_id,
        ]);

        Sanctum::actingAs($userWithPendingPPS);

        $response = $this->postJson("/api/races/{$this->race->race_id}/register");

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'needs_credentials' => true,
        ]);
    }
}
