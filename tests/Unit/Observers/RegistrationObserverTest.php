<?php

namespace Tests\Unit\Observers;

use App\Models\Registration;
use App\Models\Team;
use App\Models\Race;
use App\Models\Raid;
use App\Models\User;
use App\Models\Member;
use App\Models\MedicalDoc;
use App\Observers\RegistrationObserver;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Unit tests for RegistrationObserver
 * 
 * Tests cover:
 * - QR code auto-generation when registration is validated
 * - QR code generation on create vs update
 * - QR code deletion when registration is deleted
 * - No QR code generated for non-validated registrations
 */
class RegistrationObserverTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Team $team;
    protected Race $race;
    protected Raid $raid;

    /**
     * Setup test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('public');
        
        // Create required related models using factories
        $this->createTestData();
    }

    /**
     * Create test data for registrations using factories
     */
    protected function createTestData(): void
    {
        // Create medical doc and member
        $medicalDoc = MedicalDoc::factory()->create();
        $member = Member::factory()->create();
        
        // Create user
        $this->user = User::factory()->create([
            'doc_id' => $medicalDoc->doc_id,
            'adh_id' => $member->adh_id,
        ]);
        
        // Create team using factory
        $this->team = Team::factory()->create([
            'user_id' => $this->user->id,
        ]);
        
        // Create raid using factory
        $this->raid = Raid::factory()->create();
        
        // Create race using factory
        $this->race = Race::factory()->create([
            'raid_id' => $this->raid->raid_id,
        ]);
    }

    /**
     * Create a registration for testing
     */
    protected function createRegistration(bool $validated = false, ?string $qrCodePath = null): Registration
    {
        $medicalDoc = MedicalDoc::factory()->create();
        
        // Create payment record
        $paymentId = \Illuminate\Support\Facades\DB::table('inscriptions_payment')->insertGetId([
            'pai_date' => now(),
            'pai_is_paid' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $registration = new Registration();
        $registration->equ_id = $this->team->equ_id;
        $registration->race_id = $this->race->race_id;
        $registration->pay_id = $paymentId;
        $registration->doc_id = $medicalDoc->doc_id;
        $registration->reg_dossard = rand(100, 999);
        $registration->reg_validated = $validated;
        $registration->reg_points = 0;
        $registration->qr_code_path = $qrCodePath;
        $registration->is_present = false;
        $registration->saveQuietly(); // Don't trigger observer
        
        return $registration;
    }

    /**
     * Test QR code is generated when registration is created as validated
     */
    public function test_qr_code_generated_when_registration_created_validated(): void
    {
        $registration = $this->createRegistration(validated: true);

        // Manually trigger observer
        $observer = app(RegistrationObserver::class);
        $observer->created($registration);

        $registration->refresh();

        // Check QR code was generated
        $this->assertNotNull($registration->qr_code_path);
        $this->assertStringContainsString('qrcodes/', $registration->qr_code_path);
        Storage::disk('public')->assertExists($registration->qr_code_path);
    }

    /**
     * Test QR code is NOT generated when registration is created as non-validated
     */
    public function test_no_qr_code_when_registration_created_not_validated(): void
    {
        $registration = $this->createRegistration(validated: false);

        // Manually trigger observer
        $observer = app(RegistrationObserver::class);
        $observer->created($registration);

        $registration->refresh();

        // Check QR code was NOT generated
        $this->assertNull($registration->qr_code_path);
    }

    /**
     * Test QR code is generated when registration is updated to validated
     */
    public function test_qr_code_generated_when_registration_updated_to_validated(): void
    {
        $registration = $this->createRegistration(validated: false);
        $this->assertNull($registration->qr_code_path);

        // Update to validated (this triggers the observer automatically)
        $registration->reg_validated = true;
        $registration->save();

        // Observer should have generated QR code
        $registration->refresh();
        
        $this->assertNotNull($registration->qr_code_path);
        Storage::disk('public')->assertExists($registration->qr_code_path);
    }

    /**
     * Test QR code is deleted when registration is deleted
     */
    public function test_qr_code_deleted_when_registration_deleted(): void
    {
        $registration = $this->createRegistration(validated: true);

        // Manually generate QR code
        $qrService = new QrCodeService();
        $qrPath = $qrService->generateQrCodeForTeam($registration->equ_id, $registration->reg_id);
        $registration->updateQuietly(['qr_code_path' => $qrPath]);

        Storage::disk('public')->assertExists($qrPath);

        // Delete registration (triggers observer)
        $registration->delete();

        // QR code should be deleted
        Storage::disk('public')->assertMissing($qrPath);
    }

    /**
     * Test no duplicate QR code generation if already exists
     */
    public function test_no_duplicate_qr_code_if_already_exists(): void
    {
        $existingPath = 'qrcodes/existing_qr.svg';
        $registration = $this->createRegistration(validated: true, qrCodePath: $existingPath);

        // Trigger observer
        $observer = app(RegistrationObserver::class);
        $observer->created($registration);

        $registration->refresh();

        // Path should remain unchanged (not regenerated)
        $this->assertEquals($existingPath, $registration->qr_code_path);
    }

    /**
     * Test QR code service generates valid path format
     */
    public function test_qr_code_path_format(): void
    {
        $registration = $this->createRegistration(validated: true);

        $observer = app(RegistrationObserver::class);
        $observer->created($registration);

        $registration->refresh();

        // Check path format
        $expectedPattern = sprintf(
            'qrcodes/team_%d_reg_%d.svg',
            $registration->equ_id,
            $registration->reg_id
        );
        $this->assertEquals($expectedPattern, $registration->qr_code_path);
    }
}
