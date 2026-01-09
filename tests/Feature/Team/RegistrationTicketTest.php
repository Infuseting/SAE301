<?php

namespace Tests\Feature\Team;

use App\Models\Member;
use App\Models\MedicalDoc;
use App\Models\Race;
use App\Models\Raid;
use App\Models\Registration;
use App\Models\Team;
use App\Models\User;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Feature tests for Registration Ticket functionality
 * 
 * Tests cover:
 * - Ticket page access control (team members, admin)
 * - QR code display
 * - Ticket data integrity
 */
class RegistrationTicketTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $teamLeader;
    protected User $teamMember;
    protected User $otherUser;
    protected Team $team;
    protected Race $race;
    protected Raid $raid;
    protected Registration $registration;

    /**
     * Setup test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('public');
        
        $this->createRolesAndPermissions();
        $this->createTestData();
    }

    /**
     * Create roles needed for tests
     */
    protected function createRolesAndPermissions(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    }

    /**
     * Create test data for ticket tests using factories
     */
    protected function createTestData(): void
    {
        // Create admin
        $adminDoc = MedicalDoc::factory()->create();
        $adminMember = Member::factory()->create();
        $this->admin = User::factory()->create([
            'doc_id' => $adminDoc->doc_id,
            'adh_id' => $adminMember->adh_id,
        ]);
        $this->admin->assignRole('admin');

        // Create team leader
        $leaderDoc = MedicalDoc::factory()->create();
        $leaderMember = Member::factory()->create();
        $this->teamLeader = User::factory()->create([
            'doc_id' => $leaderDoc->doc_id,
            'adh_id' => $leaderMember->adh_id,
        ]);
        $this->teamLeader->assignRole('user');

        // Create team member
        $memberDoc = MedicalDoc::factory()->create();
        $memberMember = Member::factory()->create();
        $this->teamMember = User::factory()->create([
            'doc_id' => $memberDoc->doc_id,
            'adh_id' => $memberMember->adh_id,
        ]);
        $this->teamMember->assignRole('user');

        // Create other user (not in team)
        $otherDoc = MedicalDoc::factory()->create();
        $otherMember = Member::factory()->create();
        $this->otherUser = User::factory()->create([
            'doc_id' => $otherDoc->doc_id,
            'adh_id' => $otherMember->adh_id,
        ]);
        $this->otherUser->assignRole('user');

        // Create raid using factory
        $this->raid = Raid::factory()->create();

        // Create race using factory
        $this->race = Race::factory()->create([
            'raid_id' => $this->raid->raid_id,
        ]);

        // Create team with leader using factory
        $this->team = Team::factory()->create([
            'user_id' => $this->teamLeader->id,
        ]);

        // Add members to team participation
        DB::table('has_participate')->insert([
            ['equ_id' => $this->team->equ_id, 'id_users' => $this->teamLeader->id, 'created_at' => now(), 'updated_at' => now()],
            ['equ_id' => $this->team->equ_id, 'id_users' => $this->teamMember->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Create payment record
        $paymentId = DB::table('inscriptions_payment')->insertGetId([
            'pai_date' => now(),
            'pai_is_paid' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create registration
        $regMedicalDoc = MedicalDoc::factory()->create();
        $this->registration = new Registration();
        $this->registration->equ_id = $this->team->equ_id;
        $this->registration->race_id = $this->race->race_id;
        $this->registration->pay_id = $paymentId;
        $this->registration->doc_id = $regMedicalDoc->doc_id;
        $this->registration->reg_dossard = 777;
        $this->registration->reg_validated = true;
        $this->registration->reg_points = 100;
        $this->registration->is_present = false;
        $this->registration->saveQuietly();

        // Generate QR code
        $qrService = new QrCodeService();
        $qrPath = $qrService->generateQrCodeForTeam($this->team->equ_id, $this->registration->reg_id);
        $this->registration->updateQuietly(['qr_code_path' => $qrPath]);
    }

    // ==========================================
    // ACCESS CONTROL TESTS
    // ==========================================

    /**
     * Test team leader can access their team's registration ticket
     */
    public function test_team_leader_can_access_ticket(): void
    {
        $response = $this->actingAs($this->teamLeader)
            ->get(route('teams.registration.ticket', [
                'team' => $this->team->equ_id,
                'registration' => $this->registration->reg_id,
            ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Team/RegistrationTicket'));
    }

    /**
     * Test team member can access their team's registration ticket
     */
    public function test_team_member_can_access_ticket(): void
    {
        $response = $this->actingAs($this->teamMember)
            ->get(route('teams.registration.ticket', [
                'team' => $this->team->equ_id,
                'registration' => $this->registration->reg_id,
            ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Team/RegistrationTicket'));
    }

    /**
     * Test admin cannot access another team's ticket (must be team member)
     */
    public function test_admin_cannot_access_other_team_ticket(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('teams.registration.ticket', [
                'team' => $this->team->equ_id,
                'registration' => $this->registration->reg_id,
            ]));

        // Admin is not a team member, so should be forbidden
        $response->assertStatus(403);
    }

    /**
     * Test non-team member cannot access registration ticket
     */
    public function test_non_team_member_cannot_access_ticket(): void
    {
        $response = $this->actingAs($this->otherUser)
            ->get(route('teams.registration.ticket', [
                'team' => $this->team->equ_id,
                'registration' => $this->registration->reg_id,
            ]));

        $response->assertStatus(403);
    }

    /**
     * Test guest cannot access registration ticket
     */
    public function test_guest_cannot_access_ticket(): void
    {
        $response = $this->get(route('teams.registration.ticket', [
            'team' => $this->team->equ_id,
            'registration' => $this->registration->reg_id,
        ]));

        $response->assertRedirect(route('login'));
    }

    // ==========================================
    // TICKET DATA TESTS
    // ==========================================

    /**
     * Test ticket contains QR code URL
     */
    public function test_ticket_contains_qr_code_url(): void
    {
        $response = $this->actingAs($this->teamLeader)
            ->get(route('teams.registration.ticket', [
                'team' => $this->team->equ_id,
                'registration' => $this->registration->reg_id,
            ]));

        $response->assertInertia(fn ($page) => $page
            ->has('registration.qr_code_url')
        );
    }

    /**
     * Test ticket contains correct team information
     */
    public function test_ticket_contains_team_info(): void
    {
        $response = $this->actingAs($this->teamLeader)
            ->get(route('teams.registration.ticket', [
                'team' => $this->team->equ_id,
                'registration' => $this->registration->reg_id,
            ]));

        $response->assertInertia(fn ($page) => $page
            ->where('team.equ_id', $this->team->equ_id)
        );
    }

    /**
     * Test ticket contains correct registration data
     */
    public function test_ticket_contains_registration_data(): void
    {
        $response = $this->actingAs($this->teamLeader)
            ->get(route('teams.registration.ticket', [
                'team' => $this->team->equ_id,
                'registration' => $this->registration->reg_id,
            ]));

        $response->assertInertia(fn ($page) => $page
            ->where('registration.reg_id', $this->registration->reg_id)
            ->where('registration.reg_dossard', 777)
            ->where('registration.is_present', false)
        );
    }

    // ==========================================
    // EDGE CASE TESTS
    // ==========================================

    /**
     * Test accessing ticket for non-existent registration returns 404
     */
    public function test_non_existent_registration_returns_404(): void
    {
        $response = $this->actingAs($this->teamLeader)
            ->get(route('teams.registration.ticket', [
                'team' => $this->team->equ_id,
                'registration' => 99999,
            ]));

        $response->assertStatus(404);
    }

    /**
     * Test accessing ticket for non-existent team returns 404
     */
    public function test_non_existent_team_returns_404(): void
    {
        $response = $this->actingAs($this->teamLeader)
            ->get(route('teams.registration.ticket', [
                'team' => 99999,
                'registration' => $this->registration->reg_id,
            ]));

        $response->assertStatus(404);
    }

    /**
     * Test ticket shows correct presence status when present
     */
    public function test_ticket_shows_present_status(): void
    {
        // Mark as present
        $this->registration->update(['is_present' => true]);

        $response = $this->actingAs($this->teamLeader)
            ->get(route('teams.registration.ticket', [
                'team' => $this->team->equ_id,
                'registration' => $this->registration->reg_id,
            ]));

        $response->assertInertia(fn ($page) => $page
            ->where('registration.is_present', true)
        );
    }
}
