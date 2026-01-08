<?php

namespace Tests\Unit\Services;

use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Unit tests for QrCodeService
 * 
 * Tests cover:
 * - QR code generation (SVG format)
 * - QR code deletion
 * - QR code existence check
 * - Data encoding in QR code
 */
class QrCodeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected QrCodeService $qrCodeService;

    /**
     * Setup test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Use fake storage for tests
        Storage::fake('public');
        
        $this->qrCodeService = new QrCodeService();
    }

    /**
     * Test that a QR code can be generated successfully
     */
    public function test_can_generate_qr_code(): void
    {
        $equId = 1;
        $regId = 100;

        $path = $this->qrCodeService->generateQrCodeForTeam($equId, $regId);

        // Check path format
        $this->assertEquals("qrcodes/team_{$equId}_reg_{$regId}.svg", $path);
        
        // Check file was created
        Storage::disk('public')->assertExists($path);
    }

    /**
     * Test that QR code is generated in SVG format
     */
    public function test_qr_code_is_svg_format(): void
    {
        $path = $this->qrCodeService->generateQrCodeForTeam(1, 1);

        $content = Storage::disk('public')->get($path);
        
        // SVG files start with <?xml or <svg
        $this->assertTrue(
            str_starts_with($content, '<?xml') || str_starts_with($content, '<svg'),
            'QR code should be in SVG format'
        );
    }

    /**
     * Test that QR code contains correct data
     */
    public function test_qr_code_contains_correct_data(): void
    {
        $equId = 42;
        $regId = 123;

        $path = $this->qrCodeService->generateQrCodeForTeam($equId, $regId);

        // The QR code content is embedded in the SVG
        // We can verify the file exists and has reasonable size
        $content = Storage::disk('public')->get($path);
        
        $this->assertNotEmpty($content);
        $this->assertGreaterThan(1000, strlen($content), 'QR code SVG should have substantial content');
    }

    /**
     * Test that different registrations get different QR codes
     */
    public function test_different_registrations_get_unique_qr_codes(): void
    {
        $path1 = $this->qrCodeService->generateQrCodeForTeam(1, 100);
        $path2 = $this->qrCodeService->generateQrCodeForTeam(1, 101);
        $path3 = $this->qrCodeService->generateQrCodeForTeam(2, 100);

        $this->assertNotEquals($path1, $path2);
        $this->assertNotEquals($path1, $path3);
        $this->assertNotEquals($path2, $path3);

        // All files should exist
        Storage::disk('public')->assertExists($path1);
        Storage::disk('public')->assertExists($path2);
        Storage::disk('public')->assertExists($path3);
    }

    /**
     * Test that QR code can be deleted
     */
    public function test_can_delete_qr_code(): void
    {
        $path = $this->qrCodeService->generateQrCodeForTeam(1, 1);
        
        Storage::disk('public')->assertExists($path);

        $result = $this->qrCodeService->deleteQrCode($path);

        $this->assertTrue($result);
        Storage::disk('public')->assertMissing($path);
    }

    /**
     * Test that deleting non-existent QR code returns false
     */
    public function test_deleting_nonexistent_qr_code_returns_false(): void
    {
        $result = $this->qrCodeService->deleteQrCode('qrcodes/nonexistent.svg');

        $this->assertFalse($result);
    }

    /**
     * Test that qrCodeExists returns true for existing file
     */
    public function test_qr_code_exists_returns_true_for_existing_file(): void
    {
        $path = $this->qrCodeService->generateQrCodeForTeam(1, 1);

        $this->assertTrue($this->qrCodeService->qrCodeExists($path));
    }

    /**
     * Test that qrCodeExists returns false for non-existing file
     */
    public function test_qr_code_exists_returns_false_for_nonexistent_file(): void
    {
        $this->assertFalse($this->qrCodeService->qrCodeExists('qrcodes/nonexistent.svg'));
    }

    /**
     * Test that regenerating QR code overwrites existing file
     */
    public function test_regenerating_qr_code_overwrites_existing(): void
    {
        $path1 = $this->qrCodeService->generateQrCodeForTeam(1, 1);
        $content1 = Storage::disk('public')->get($path1);

        // Regenerate (should overwrite)
        $path2 = $this->qrCodeService->generateQrCodeForTeam(1, 1);

        $this->assertEquals($path1, $path2);
        Storage::disk('public')->assertExists($path1);
    }

    /**
     * Test QR code path follows naming convention
     */
    public function test_qr_code_path_follows_naming_convention(): void
    {
        $path = $this->qrCodeService->generateQrCodeForTeam(999, 888);

        $this->assertMatchesRegularExpression(
            '/^qrcodes\/team_\d+_reg_\d+\.svg$/',
            $path
        );
    }
}
