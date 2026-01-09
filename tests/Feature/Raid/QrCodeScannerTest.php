<?php

namespace Tests\Feature\Raid;

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
 * Feature tests for QR Code Scanner functionality
 * 
 * Tests cover:
 * - Scanner page access (authorization)
 * - Check-in API endpoint
 * - Start-list PDF generation
 * - Registration ticket display
 */
class QrCodeScannerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $raidManager;
    protected User $teamLeader;
    protected User $regularUser;
    protected Raid $raid;
    protected Race $race;
    protected Team $team;
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
     * Create roles and permissions needed for tests
     */
    protected function createRolesAndPermissions(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'gestionnaire-raid', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'adherent', 'guard_name' => 'web']);
    }

    /**
     * Create test data for scanner tests using factories
     */
    protected function createTestData(): void
    {
        // Create admin user
        $adminDoc = MedicalDoc::factory()->create();
        $adminMember = Member::factory()->create(['adh_license' => 'ADMIN-2026']);
        $this->admin = User::factory()->create([
            'doc_id' => $adminDoc->doc_id,
            'adh_id' => $adminMember->adh_id,
        ]);
        $this->admin->assignRole('admin');

        // Create raid manager user
        $managerDoc = MedicalDoc::factory()->create();
        $managerMember = Member::factory()->create(['adh_license' => 'MANAGER-2026']);
        $this->raidManager = User::factory()->create([
            'doc_id' => $managerDoc->doc_id,
            'adh_id' => $managerMember->adh_id,
        ]);
        $this->raidManager->assignRole('gestionnaire-raid');

        // Create team leader user
        $leaderDoc = MedicalDoc::factory()->create();
        $leaderMember = Member::factory()->create(['adh_license' => 'LEADER-2026']);
        $this->teamLeader = User::factory()->create([
            'doc_id' => $leaderDoc->doc_id,
            'adh_id' => $leaderMember->adh_id,
        ]);
        $this->teamLeader->assignRole('user');

        // Create regular user
        $regularDoc = MedicalDoc::factory()->create();
        $regularMember = Member::factory()->create();
        $this->regularUser = User::factory()->create([
            'doc_id' => $regularDoc->doc_id,
            'adh_id' => $regularMember->adh_id,
        ]);
        $this->regularUser->assignRole('user');

        // Create club with raidManager as creator
        $club = \App\Models\Club::factory()->create([
            'created_by' => $this->raidManager->id,
        ]);

        // Create raid with the club owned by raidManager
        $this->raid = Raid::factory()->create([
            'clu_id' => $club->club_id,
        ]);

        // Create race using factory
        $this->race = Race::factory()->create([
            'raid_id' => $this->raid->raid_id,
        ]);

        // Create team using factory
        $this->team = Team::factory()->create([
            'user_id' => $this->teamLeader->id,
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
        $this->registration->reg_dossard = 500;
        $this->registration->reg_validated = true;
        $this->registration->reg_points = 0;
        $this->registration->is_present = false;
        $this->registration->saveQuietly();

        // Generate QR code for registration
        $qrService = new QrCodeService();
        $qrPath = $qrService->generateQrCodeForTeam($this->team->equ_id, $this->registration->reg_id);
        $this->registration->updateQuietly(['qr_code_path' => $qrPath]);
    }

    // ==========================================
    // SCANNER PAGE ACCESS TESTS
    // ==========================================

    /**
     * Test admin can access scanner page
     */
    public function test_admin_can_access_scanner_page(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('raids.scanner', $this->raid));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Raid/Scanner'));
    }

    /**
     * Test raid manager can access scanner page
     */
    public function test_raid_manager_can_access_scanner_page(): void
    {
        $response = $this->actingAs($this->raidManager)
            ->get(route('raids.scanner', $this->raid));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Raid/Scanner'));
    }

    /**
     * Test regular user cannot access scanner page
     */
    public function test_regular_user_cannot_access_scanner_page(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('raids.scanner', $this->raid));

        $response->assertStatus(403);
    }

    /**
     * Test guest cannot access scanner page
     */
    public function test_guest_cannot_access_scanner_page(): void
    {
        $response = $this->get(route('raids.scanner', $this->raid));

        $response->assertRedirect(route('login'));
    }

    // ==========================================
    // CHECK-IN API TESTS
    // ==========================================

    /**
     * Test raid manager can check-in a team
     */
    public function test_raid_manager_can_check_in_team(): void
    {
        $response = $this->actingAs($this->raidManager)
            ->postJson(route('raids.check-in', $this->raid), [
                'equ_id' => $this->team->equ_id,
                'reg_id' => $this->registration->reg_id,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Team successfully checked in!',
        ]);

        // Verify database was updated
        $this->registration->refresh();
        $this->assertTrue($this->registration->is_present);
    }

    /**
     * Test admin cannot check-in a team (only raid manager can)
     */
    public function test_admin_cannot_check_in_team(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('raids.check-in', $this->raid), [
                'equ_id' => $this->team->equ_id,
                'reg_id' => $this->registration->reg_id,
            ]);

        // Admin is not the raid manager, so should be forbidden
        $response->assertStatus(403);
    }

    /**
     * Test regular user cannot check-in a team
     */
    public function test_regular_user_cannot_check_in_team(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->postJson(route('raids.check-in', $this->raid), [
                'equ_id' => $this->team->equ_id,
                'reg_id' => $this->registration->reg_id,
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test check-in with invalid registration returns error
     */
    public function test_check_in_with_invalid_registration_returns_error(): void
    {
        $response = $this->actingAs($this->raidManager)
            ->postJson(route('raids.check-in', $this->raid), [
                'equ_id' => 99999,
                'reg_id' => 99999,
            ]);

        // Validation should fail before reaching authorization
        $response->assertStatus(422);
    }

    /**
     * Test check-in prevents duplicate check-in
     */
    public function test_check_in_prevents_duplicate(): void
    {
        // First check-in
        $this->registration->update(['is_present' => true]);

        // Attempt second check-in
        $response = $this->actingAs($this->raidManager)
            ->postJson(route('raids.check-in', $this->raid), [
                'equ_id' => $this->team->equ_id,
                'reg_id' => $this->registration->reg_id,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'already_present' => true,
        ]);
    }

    // ==========================================
    // START-LIST PDF TESTS
    // ==========================================

    /**
     * Test admin can download start-list PDF
     */
    public function test_admin_can_download_start_list_pdf(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('raids.start-list', $this->raid));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    /**
     * Test raid manager can download start-list PDF
     */
    public function test_raid_manager_can_download_start_list_pdf(): void
    {
        $response = $this->actingAs($this->raidManager)
            ->get(route('raids.start-list', $this->raid));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    /**
     * Test regular user cannot download start-list PDF
     */
    public function test_regular_user_cannot_download_start_list_pdf(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('raids.start-list', $this->raid));

        $response->assertStatus(403);
    }

    /**
     * Test guest cannot download start-list PDF
     */
    public function test_guest_cannot_download_start_list_pdf(): void
    {
        $response = $this->get(route('raids.start-list', $this->raid));

        $response->assertRedirect(route('login'));
    }
}
