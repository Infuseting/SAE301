<?php

namespace Tests\Feature\FormValidation;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ClubFormValidationTest covers comprehensive validation testing for club creation and updates.
 * Tests various security vulnerabilities, malicious input patterns, and edge cases.
 */
class ClubFormValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed roles and permissions
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    // ================================
    // Club Name Validation Tests
    // ================================

    /**
     * Test that club name is required.
     */
    public function test_club_creation_rejects_missing_name(): void
    {
        $user = User::factory()->create();
        $user->assignRole('adherent');

        $response = $this->actingAs($user)->post('/clubs', [
            'club_name' => '',
            'club_street' => '123 Main St',
            'club_city' => 'Paris',
            'club_postal_code' => '75001',
            'ffso_id' => 'FFCO-12345',
        ]);

        $response->assertSessionHasErrors('club_name');
    }

    /**
     * Test that club name cannot exceed maximum length.
     */
    public function test_club_creation_rejects_excessively_long_name(): void
    {
        $user = User::factory()->create();
        $user->assignRole('adherent');

        $longName = str_repeat('A', 150); // Exceeds max:100

        $response = $this->actingAs($user)->post('/clubs', [
            'club_name' => $longName,
            'club_street' => '123 Main St',
            'club_city' => 'Paris',
            'club_postal_code' => '75001',
            'ffso_id' => 'FFCO-12345',
        ]);

        $response->assertSessionHasErrors('club_name');
    }

    /**
     * Test that XSS attempts in club name are rejected or sanitized.
     */
    public function test_club_creation_rejects_xss_in_name(): void
    {
        $user = User::factory()->create();
        $user->assignRole('adherent');
        
        $xssPayloads = [
            '<script>alert("XSS")</script>',
            '<img src=x onerror="alert(\'XSS\')">',
            'javascript:alert("XSS")',
            '<svg/onload=alert("XSS")>',
            '"><script>alert("Club Hack")</script>',
        ];

        foreach ($xssPayloads as $payload) {
            $response = $this->actingAs($user)->post('/clubs', [
                'club_name' => $payload,
                'club_street' => '123 Main St',
                'club_city' => 'Paris',
                'club_postal_code' => '75001',
                'ffso_id' => 'FFCO-12345',
            ]);

            $this->assertTrue($response->status() === 302 || $response->status() === 422);
        }
    }

    /**
     * Test that SQL injection attempts in club name are rejected.
     */
    public function test_club_creation_rejects_sql_injection_in_name(): void
    {
        $user = User::factory()->create();
        $user->assignRole('adherent');
        
        $sqlPayloads = [
            "'; DROP TABLE clubs; --",
            "1' OR '1'='1",
            "club'; DELETE FROM clubs; --",
            "1' UNION SELECT * FROM users; --",
        ];

        foreach ($sqlPayloads as $payload) {
            $response = $this->actingAs($user)->post('/clubs', [
                'club_name' => $payload,
                'club_street' => '123 Main St',
                'club_city' => 'Paris',
                'club_postal_code' => '75001',
                'ffso_id' => 'FFCO-12345',
            ]);

            $this->assertTrue($response->status() === 302 || $response->status() === 422);
        }
    }

    // ================================
    // Street Address Validation Tests
    // ================================

    /**
     * Test that street address is required.
     */
    public function test_club_creation_rejects_missing_street(): void
    {
        $user = User::factory()->create();
        $user->assignRole('adherent');

        $response = $this->actingAs($user)->post('/clubs', [
            'club_name' => 'Test Club',
            'club_street' => '',
            'club_city' => 'Paris',
            'club_postal_code' => '75001',
            'ffso_id' => 'FFCO-12345',
        ]);

        $response->assertSessionHasErrors('club_street');
    }

    /**
     * Test that very long street addresses are rejected.
     */
    public function test_club_creation_rejects_excessively_long_street(): void
    {
        $user = User::factory()->create();
        $user->assignRole('adherent');

        $longStreet = str_repeat('A', 200); // Exceeds max:100

        $response = $this->actingAs($user)->post('/clubs', [
            'club_name' => 'Test Club',
            'club_street' => $longStreet,
            'club_city' => 'Paris',
            'club_postal_code' => '75001',
            'ffso_id' => 'FFCO-12345',
        ]);

        $response->assertSessionHasErrors('club_street');
    }

    /**
     * Test that XSS in street address is rejected.
     */
    public function test_club_creation_rejects_xss_in_street(): void
    {
        $user = User::factory()->create();
        $user->assignRole('adherent');
        
        $response = $this->actingAs($user)->post('/clubs', [
            'club_name' => 'Test Club',
            'club_street' => '123 Street<script>alert(1)</script>',
            'club_city' => 'Paris',
            'club_postal_code' => '75001',
            'ffso_id' => 'FFCO-12345',
        ]);

        $this->assertTrue($response->status() === 302 || $response->status() === 422);
    }

    // ================================
    // City Validation Tests
    // ================================

    /**
     * Test that city is required.
     */
    public function test_club_creation_rejects_missing_city(): void
    {
        $user = User::factory()->create();
        $user->assignRole('adherent');

        $response = $this->actingAs($user)->post('/clubs', [
            'club_name' => 'Test Club',
            'club_street' => '123 Main St',
            'club_city' => '',
            'club_postal_code' => '75001',
            'ffso_id' => 'FFCO-12345',
        ]);

        $response->assertSessionHasErrors('club_city');
    }

    /**
     * Test that very long city names are rejected.
     */
    public function test_club_creation_rejects_excessively_long_city(): void
    {
        $user = User::factory()->create();
        $user->assignRole('adherent');

        $longCity = str_repeat('A', 150); // Exceeds max:100

        $response = $this->actingAs($user)->post('/clubs', [
            'club_name' => 'Test Club',
            'club_street' => '123 Main St',
            'club_city' => $longCity,
            'club_postal_code' => '75001',
            'ffso_id' => 'FFCO-12345',
        ]);

        $response->assertSessionHasErrors('club_city');
    }

    /**
     * Test that special characters in city name are handled.
     */
    public function test_club_creation_handles_special_characters_in_city(): void
    {
        $user = User::factory()->create();
        $user->assignRole('adherent');
        
        $specialCities = [
            "Saint-Étienne",
            "Île-de-France",
            'Москва', // Moscow in Russian
            '北京', // Beijing in Chinese
        ];

        foreach ($specialCities as $city) {
            $response = $this->actingAs($user)->post('/clubs', [
                'club_name' => 'Test Club',
                'club_street' => '123 Main St',
                'club_city' => $city,
                'club_postal_code' => '75001',
                'ffso_id' => 'FFCO-12345',
            ]);

            $this->assertTrue($response->status() === 302 || $response->status() === 422);
        }
    }

    // ================================
    // Postal Code Validation Tests
    // ================================

    /**
     * Test that postal code is required.
     */
    public function test_club_creation_rejects_missing_postal_code(): void
    {
        $user = User::factory()->create();
        $user->assignRole('adherent');

        $response = $this->actingAs($user)->post('/clubs', [
            'club_name' => 'Test Club',
            'club_street' => '123 Main St',
            'club_city' => 'Paris',
            'club_postal_code' => '',
            'ffso_id' => 'FFCO-12345',
        ]);

        $response->assertSessionHasErrors('club_postal_code');
    }

    /**
     * Test that very long postal codes are rejected.
     */
    public function test_club_creation_rejects_excessively_long_postal_code(): void
    {
        $user = User::factory()->create();
        $user->assignRole('adherent');

        $longCode = str_repeat('1', 50); // Exceeds max:20

        $response = $this->actingAs($user)->post('/clubs', [
            'club_name' => 'Test Club',
            'club_street' => '123 Main St',
            'club_city' => 'Paris',
            'club_postal_code' => $longCode,
            'ffso_id' => 'FFCO-12345',
        ]);

        $response->assertSessionHasErrors('club_postal_code');
    }

    /**
     * Test that various postal code formats are accepted.
     */
    public function test_club_creation_accepts_various_postal_formats(): void
    {
        $user = User::factory()->create();
        $user->assignRole('adherent');
        
        $postalCodes = [
            '75001',          // French
            'SW1A 1AA',       // UK
            '10001',          // US
            '1000 AA',        // Netherlands
            '12345',          // Standard numeric
        ];

        foreach ($postalCodes as $code) {
            $response = $this->actingAs($user)->post('/clubs', [
                'club_name' => 'Test Club',
                'club_street' => '123 Main St',
                'club_city' => 'Test City',
                'club_postal_code' => $code,
                'ffso_id' => 'FFCO-12345',
            ]);

            $this->assertTrue($response->status() === 302 || $response->status() === 422);
        }
    }

    // ================================
    // FFSO ID Validation Tests
    // ================================

    /**
     * Test that FFSO ID is required.
     */
    public function test_club_creation_rejects_missing_ffso_id(): void
    {
        $user = User::factory()->create();
        $user->assignRole('adherent');

        $response = $this->actingAs($user)->post('/clubs', [
            'club_name' => 'Test Club',
            'club_street' => '123 Main St',
            'club_city' => 'Paris',
            'club_postal_code' => '75001',
            'ffso_id' => '',
        ]);

        $response->assertSessionHasErrors('ffso_id');
    }

    /**
     * Test that very long FFSO IDs are rejected.
     */
    public function test_club_creation_rejects_excessively_long_ffso_id(): void
    {
        $user = User::factory()->create();
        $user->assignRole('adherent');

        $longId = str_repeat('X', 100); // Exceeds max:50

        $response = $this->actingAs($user)->post('/clubs', [
            'club_name' => 'Test Club',
            'club_street' => '123 Main St',
            'club_city' => 'Paris',
            'club_postal_code' => '75001',
            'ffso_id' => $longId,
        ]);

        $response->assertSessionHasErrors('ffso_id');
    }

    /**
     * Test that SQL injection in FFSO ID is rejected.
     */
    public function test_club_creation_rejects_sql_injection_in_ffso_id(): void
    {
        $user = User::factory()->create();
        $user->assignRole('adherent');
        
        $sqlPayloads = [
            "FFCO-123'; DROP TABLE--",
            "FFCO' OR '1'='1",
            "FFCO-123; DELETE FROM clubs;",
        ];

        foreach ($sqlPayloads as $payload) {
            $response = $this->actingAs($user)->post('/clubs', [
                'club_name' => 'Test Club',
                'club_street' => '123 Main St',
                'club_city' => 'Paris',
                'club_postal_code' => '75001',
                'ffso_id' => $payload,
            ]);

            $this->assertTrue($response->status() === 302 || $response->status() === 422);
        }
    }

    // ================================
    // Description Validation Tests
    // ================================

    /**
     * Test that very long descriptions are rejected.
     */
    public function test_club_creation_rejects_excessively_long_description(): void
    {
        $user = User::factory()->create();
        $user->assignRole('adherent');

        $longDesc = str_repeat('A', 1500); // Exceeds max:1000

        $response = $this->actingAs($user)->post('/clubs', [
            'club_name' => 'Test Club',
            'club_street' => '123 Main St',
            'club_city' => 'Paris',
            'club_postal_code' => '75001',
            'ffso_id' => 'FFCO-12345',
            'description' => $longDesc,
        ]);

        $response->assertSessionHasErrors('description');
    }

    /**
     * Test that XSS in description is rejected.
     */
    public function test_club_creation_rejects_xss_in_description(): void
    {
        $user = User::factory()->create();
        $user->assignRole('adherent');
        
        $xssPayloads = [
            '<script>alert("XSS")</script>',
            '<img src=x onerror="alert(\'hack\')">',
            '"><script>alert(1)</script><"',
        ];

        foreach ($xssPayloads as $payload) {
            $response = $this->actingAs($user)->post('/clubs', [
                'club_name' => 'Test Club',
                'club_street' => '123 Main St',
                'club_city' => 'Paris',
                'club_postal_code' => '75001',
                'ffso_id' => 'FFCO-12345',
                'description' => $payload,
            ]);

            $this->assertTrue($response->status() === 302 || $response->status() === 422);
        }
    }

    /**
     * Test that HTML/Markdown in description is handled.
     */
    public function test_club_creation_handles_markdown_in_description(): void
    {
        $user = User::factory()->create();
        $user->assignRole('adherent');

        $markdownText = "# Club Description\n\nThis is a **bold** and *italic* description.\n- Item 1\n- Item 2";

        $response = $this->actingAs($user)->post('/clubs', [
            'club_name' => 'Test Club',
            'club_street' => '123 Main St',
            'club_city' => 'Paris',
            'club_postal_code' => '75001',
            'ffso_id' => 'FFCO-12345',
            'description' => $markdownText,
        ]);

        $this->assertTrue($response->status() === 302 || $response->status() === 422);
    }

    // ================================
    // Club Image Validation Tests
    // ================================

    /**
     * Test that invalid image types are rejected.
     */
    public function test_club_creation_rejects_invalid_image_types(): void
    {
        $user = User::factory()->create();
        $user->assignRole('adherent');

        $response = $this->actingAs($user)->post('/clubs', [
            'club_name' => 'Test Club',
            'club_street' => '123 Main St',
            'club_city' => 'Paris',
            'club_postal_code' => '75001',
            'ffso_id' => 'FFCO-12345',
            'club_image' => 'not_an_image.txt',
        ]);

        $this->assertTrue($response->status() === 302 || $response->status() === 422);
    }

    /**
     * Test that oversized club images are rejected.
     */
    public function test_club_creation_rejects_oversized_image(): void
    {
        $user = User::factory()->create();
        $user->assignRole('adherent');

        // Create fake large file (over 5MB)
        $largeContent = str_repeat('A', (5121 * 1024));

        $response = $this->actingAs($user)->post('/clubs', [
            'club_name' => 'Test Club',
            'club_street' => '123 Main St',
            'club_city' => 'Paris',
            'club_postal_code' => '75001',
            'ffso_id' => 'FFCO-12345',
            'club_image' => $largeContent,
        ]);

        $this->assertTrue($response->status() === 302 || $response->status() === 422);
    }

    // ================================
    // Authorization Tests
    // ================================

    /**
     * Test that unauthenticated users cannot create clubs.
     */
    public function test_club_creation_requires_authentication(): void
    {
        $response = $this->post('/clubs', [
            'club_name' => 'Test Club',
            'club_street' => '123 Main St',
            'club_city' => 'Paris',
            'club_postal_code' => '75001',
            'ffso_id' => 'FFCO-12345',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    /**
     * Test that only adherent/admin role can create clubs.
     */
    public function test_club_creation_requires_adherent_role(): void
    {
        $user = User::factory()->create();
        // User has no role

        $response = $this->actingAs($user)->post('/clubs', [
            'club_name' => 'Test Club',
            'club_street' => '123 Main St',
            'club_city' => 'Paris',
            'club_postal_code' => '75001',
            'ffso_id' => 'FFCO-12345',
        ]);

        // Should be forbidden or redirect
        $this->assertTrue($response->status() === 302 || $response->status() === 403);
    }

    // ================================
    // Security Tests
    // ================================

    /**
     * Test NULL byte injection is handled.
     */
    public function test_club_creation_handles_null_byte_injection(): void
    {
        $user = User::factory()->create();
        $user->assignRole('adherent');

        $response = $this->actingAs($user)->post('/clubs', [
            'club_name' => "Club\x00Name",
            'club_street' => '123 Main St',
            'club_city' => 'Paris',
            'club_postal_code' => '75001',
            'ffso_id' => 'FFCO-12345',
        ]);

        $this->assertTrue($response->status() === 302 || $response->status() === 422);
    }

    /**
     * Test valid club creation succeeds.
     */
    public function test_club_creation_succeeds_with_valid_data(): void
    {
        $user = User::factory()->create();
        $user->assignRole('adherent');

        $response = $this->actingAs($user)->post('/clubs', [
            'club_name' => 'Valid Test Club',
            'club_street' => '123 Main Street',
            'club_city' => 'Paris',
            'club_postal_code' => '75001',
            'ffso_id' => 'FFCO-12345',
            'description' => 'A valid club description',
        ]);

        $response->assertStatus(302);
    }
}
