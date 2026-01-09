<?php

namespace Tests\Feature\Team;

use App\Models\User;
use App\Models\Team;
use App\Models\Registration;
use App\Models\Race;
use App\Models\Raid;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test QR Code download functionality with permission verification
 */
class QrCodeDownloadTest extends TestCase
{
    use RefreshDatabase;

    private QrCodeService $qrCodeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->qrCodeService = new QrCodeService();
    }

    /**
     * Test that unauthorized users cannot download QR codes
     */
    public function test_unauthorized_user_cannot_download_qr_code(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        
        // Create raid and race
        $raid = Raid::factory()->create();
        $race = Race::factory()->create(['raid_id' => $raid->raid_id]);
        
        // Create team with first user
        $team = Team::factory()->create(['user_id' => $user->id]);
        
        // Create registration
        $registration = Registration::factory()->create([
            'equ_id' => $team->equ_id,
            'race_id' => $race->race_id,
            'qr_code_path' => 'qrcodes/test.svg'
        ]);

        // Ensure second user is not a team member
        $this->actingAs($otherUser)
            ->getJson(route('teams.registration.qr-code', [
                'team' => $team->equ_id,
                'registration' => $registration->reg_id
            ]))
            ->assertForbidden();
    }

    /**
     * Test that team leader can download QR code
     */
    public function test_team_leader_can_download_qr_code(): void
    {
        $leader = User::factory()->create();
        
        // Create raid and race
        $raid = Raid::factory()->create();
        $race = Race::factory()->create(['raid_id' => $raid->raid_id]);
        
        // Create team with leader
        $team = Team::factory()->create(['user_id' => $leader->id]);
        
        // Generate QR code
        $qrPath = $this->qrCodeService->generateQrCodeForTeam(
            $team->equ_id,
            1
        );
        
        // Create registration
        $registration = Registration::factory()->create([
            'equ_id' => $team->equ_id,
            'race_id' => $race->race_id,
            'qr_code_path' => $qrPath
        ]);

        // Team leader should be able to download
        $response = $this->actingAs($leader)
            ->get(route('teams.registration.qr-code', [
                'team' => $team->equ_id,
                'registration' => $registration->reg_id
            ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/svg+xml');
    }

    /**
     * Test that team member can download QR code
     */
    public function test_team_member_can_download_qr_code(): void
    {
        $leader = User::factory()->create();
        $member = User::factory()->create();
        
        // Create raid and race
        $raid = Raid::factory()->create();
        $race = Race::factory()->create(['raid_id' => $raid->raid_id]);
        
        // Create team with leader
        $team = Team::factory()->create(['user_id' => $leader->id]);
        
        // Add member to team
        $team->users()->attach($member->id);
        
        // Generate QR code
        $qrPath = $this->qrCodeService->generateQrCodeForTeam(
            $team->equ_id,
            1
        );
        
        // Create registration
        $registration = Registration::factory()->create([
            'equ_id' => $team->equ_id,
            'race_id' => $race->race_id,
            'qr_code_path' => $qrPath
        ]);

        // Team member should be able to download
        $response = $this->actingAs($member)
            ->get(route('teams.registration.qr-code', [
                'team' => $team->equ_id,
                'registration' => $registration->reg_id
            ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/svg+xml');
    }

    /**
     * Test that 404 is returned if QR code doesn't exist
     */
    public function test_returns_404_if_qr_code_not_found(): void
    {
        $user = User::factory()->create();
        
        // Create raid and race
        $raid = Raid::factory()->create();
        $race = Race::factory()->create(['raid_id' => $raid->raid_id]);
        
        // Create team
        $team = Team::factory()->create(['user_id' => $user->id]);
        
        // Create registration without QR code
        $registration = Registration::factory()->create([
            'equ_id' => $team->equ_id,
            'race_id' => $race->race_id,
            'qr_code_path' => null
        ]);

        // Should return 404
        $this->actingAs($user)
            ->get(route('teams.registration.qr-code', [
                'team' => $team->equ_id,
                'registration' => $registration->reg_id
            ]))
            ->assertNotFound();
    }

    /**
     * Test that unauthenticated users are redirected to login
     */
    public function test_unauthenticated_user_redirected_to_login(): void
    {
        // Create raid and race
        $raid = Raid::factory()->create();
        $race = Race::factory()->create(['raid_id' => $raid->raid_id]);
        
        // Create team
        $team = Team::factory()->create();
        
        // Create registration with QR code
        $registration = Registration::factory()->create([
            'equ_id' => $team->equ_id,
            'race_id' => $race->race_id,
            'qr_code_path' => 'qrcodes/test.svg'
        ]);

        // Unauthenticated user should be redirected
        $this->get(route('teams.registration.qr-code', [
            'team' => $team->equ_id,
            'registration' => $registration->reg_id
        ]))
        ->assertRedirect(route('login'));
    }
}
