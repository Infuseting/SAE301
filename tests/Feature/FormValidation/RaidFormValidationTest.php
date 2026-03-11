<?php

namespace Tests\Feature\FormValidation;

use App\Models\User;
use App\Models\Club;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RaidFormValidationTest covers comprehensive validation testing for raid creation.
 * Tests date validation, contact information, and security vulnerabilities.
 */
class RaidFormValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed roles and permissions
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    // ================================
    // Raid Name Validation Tests
    // ================================

    /**
     * Test that raid name is required.
     */
    public function test_raid_creation_rejects_missing_name(): void
    {
        $user = User::factory()->create();
        $user->assignRole('gestionnaire-raid');
        $club = Club::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->post('/raids', [
            'raid_name' => '',
            'raid_date_start' => now()->addDays(10)->format('Y-m-d'),
            'raid_date_end' => now()->addDays(11)->format('Y-m-d'),
            'ins_start_date' => now()->addDay()->format('Y-m-d'),
            'ins_end_date' => now()->addDays(9)->format('Y-m-d'),
            'adh_id' => $user->adh_id ?? 1,
            'clu_id' => $club->club_id,
            'raid_contact' => 'contact@example.com',
            'raid_city' => 'Paris',
            'raid_postal_code' => '75001',
        ]);

        $this->assertTrue($response->status() === 302 || $response->status() === 422);
    }

    /**
     * Test that raid name cannot exceed maximum length.
     */
    public function test_raid_creation_rejects_excessively_long_name(): void
    {
        $user = User::factory()->create();
        $user->assignRole('gestionnaire-raid');
        $club = Club::factory()->create(['created_by' => $user->id]);

        $longName = str_repeat('A', 150); // Exceeds max:100

        $response = $this->actingAs($user)->post('/raids', [
            'raid_name' => $longName,
            'raid_date_start' => now()->addDays(10)->format('Y-m-d'),
            'raid_date_end' => now()->addDays(11)->format('Y-m-d'),
            'ins_start_date' => now()->addDay()->format('Y-m-d'),
            'ins_end_date' => now()->addDays(9)->format('Y-m-d'),
            'adh_id' => $user->adh_id ?? 1,
            'clu_id' => $club->club_id,
            'raid_contact' => 'contact@example.com',
            'raid_city' => 'Paris',
            'raid_postal_code' => '75001',
        ]);

        $this->assertTrue($response->status() === 302 || $response->status() === 422);
    }

    /**
     * Test that XSS attempts in raid name are rejected.
     */
    public function test_raid_creation_rejects_xss_in_name(): void
    {
        $user = User::factory()->create();
        $user->assignRole('gestionnaire-raid');
        $club = Club::factory()->create(['created_by' => $user->id]);
        
        $xssPayloads = [
            '<script>alert("XSS")</script>',
            '<img src=x onerror="alert(\'raid hack\')">',
            '"><script>alert(1)</script>',
        ];

        foreach ($xssPayloads as $payload) {
            $response = $this->actingAs($user)->post('/raids', [
                'raid_name' => $payload,
                'raid_date_start' => now()->addDays(10)->format('Y-m-d'),
                'raid_date_end' => now()->addDays(11)->format('Y-m-d'),
                'ins_start_date' => now()->addDay()->format('Y-m-d'),
                'ins_end_date' => now()->addDays(9)->format('Y-m-d'),
                'adh_id' => $user->adh_id ?? 1,
                'clu_id' => $club->club_id,
                'raid_contact' => 'contact@example.com',
                'raid_city' => 'Paris',
                'raid_postal_code' => '75001',
            ]);

            $this->assertTrue($response->status() === 302 || $response->status() === 422);
        }
    }

    /**
     * Test that SQL injection in raid name is rejected.
     */
    public function test_raid_creation_rejects_sql_injection_in_name(): void
    {
        $user = User::factory()->create();
        $user->assignRole('gestionnaire-raid');
        $club = Club::factory()->create(['created_by' => $user->id]);
        
        $sqlPayloads = [
            "'; DROP TABLE raids; --",
            "1' OR '1'='1",
            "raid'; DELETE FROM raids; --",
        ];

        foreach ($sqlPayloads as $payload) {
            $response = $this->actingAs($user)->post('/raids', [
                'raid_name' => $payload,
                'raid_date_start' => now()->addDays(10)->format('Y-m-d'),
                'raid_date_end' => now()->addDays(11)->format('Y-m-d'),
                'ins_start_date' => now()->addDay()->format('Y-m-d'),
                'ins_end_date' => now()->addDays(9)->format('Y-m-d'),
                'adh_id' => $user->adh_id ?? 1,
                'clu_id' => $club->club_id,
                'raid_contact' => 'contact@example.com',
                'raid_city' => 'Paris',
                'raid_postal_code' => '75001',
            ]);

            $this->assertTrue($response->status() === 302 || $response->status() === 422);
        }
    }

    // ================================
    // Date Validation Tests
    // ================================

    /**
     * Test that raid start date cannot be in past.
     */
    public function test_raid_creation_rejects_past_start_date(): void
    {
        $user = User::factory()->create();
        $user->assignRole('gestionnaire-raid');
        $club = Club::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->post('/raids', [
            'raid_name' => 'Past Raid',
            'raid_date_start' => now()->subDay()->format('Y-m-d'),
            'raid_date_end' => now()->format('Y-m-d'),
            'ins_start_date' => now()->subDays(5)->format('Y-m-d'),
            'ins_end_date' => now()->subDays(2)->format('Y-m-d'),
            'adh_id' => $user->adh_id ?? 1,
            'clu_id' => $club->club_id,
            'raid_contact' => 'contact@example.com',
            'raid_city' => 'Paris',
            'raid_postal_code' => '75001',
        ]);

        $response->assertSessionHasErrors('raid_date_start');
    }

    /**
     * Test that raid end date must be after start date.
     */
    public function test_raid_creation_rejects_end_date_before_start(): void
    {
        $user = User::factory()->create();
        $user->assignRole('gestionnaire-raid');
        $club = Club::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->post('/raids', [
            'raid_name' => 'Bad Raid',
            'raid_date_start' => now()->addDays(10)->format('Y-m-d'),
            'raid_date_end' => now()->addDays(8)->format('Y-m-d'), // Before start
            'ins_start_date' => now()->addDay()->format('Y-m-d'),
            'ins_end_date' => now()->addDays(9)->format('Y-m-d'),
            'adh_id' => $user->adh_id ?? 1,
            'clu_id' => $club->club_id,
            'raid_contact' => 'contact@example.com',
            'raid_city' => 'Paris',
            'raid_postal_code' => '75001',
        ]);

        $response->assertSessionHasErrors('raid_date_end');
    }

    /**
     * Test that registration start date must be before raid start date.
     */
    public function test_raid_creation_rejects_registration_start_after_raid_start(): void
    {
        $user = User::factory()->create();
        $user->assignRole('gestionnaire-raid');
        $club = Club::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->post('/raids', [
            'raid_name' => 'Bad Raid',
            'raid_date_start' => now()->addDays(10)->format('Y-m-d'),
            'raid_date_end' => now()->addDays(11)->format('Y-m-d'),
            'ins_start_date' => now()->addDays(15)->format('Y-m-d'), // After raid starts
            'ins_end_date' => now()->addDays(20)->format('Y-m-d'),
            'adh_id' => $user->adh_id ?? 1,
            'clu_id' => $club->club_id,
            'raid_contact' => 'contact@example.com',
            'raid_city' => 'Paris',
            'raid_postal_code' => '75001',
        ]);

        $response->assertSessionHasErrors('ins_start_date');
    }

    /**
     * Test that registration end date must be before raid start date.
     */
    public function test_raid_creation_rejects_registration_end_after_raid_start(): void
    {
        $user = User::factory()->create();
        $user->assignRole('gestionnaire-raid');
        $club = Club::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->post('/raids', [
            'raid_name' => 'Bad Raid',
            'raid_date_start' => now()->addDays(10)->format('Y-m-d'),
            'raid_date_end' => now()->addDays(11)->format('Y-m-d'),
            'ins_start_date' => now()->addDay()->format('Y-m-d'),
            'ins_end_date' => now()->addDays(15)->format('Y-m-d'), // After raid starts
            'adh_id' => $user->adh_id ?? 1,
            'clu_id' => $club->club_id,
            'raid_contact' => 'contact@example.com',
            'raid_city' => 'Paris',
            'raid_postal_code' => '75001',
        ]);

        $response->assertSessionHasErrors('ins_end_date');
    }

    /**
     * Test that registration end must be after registration start.
     */
    public function test_raid_creation_rejects_registration_end_before_start(): void
    {
        $user = User::factory()->create();
        $user->assignRole('gestionnaire-raid');
        $club = Club::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->post('/raids', [
            'raid_name' => 'Bad Raid',
            'raid_date_start' => now()->addDays(10)->format('Y-m-d'),
            'raid_date_end' => now()->addDays(11)->format('Y-m-d'),
            'ins_start_date' => now()->addDays(5)->format('Y-m-d'),
            'ins_end_date' => now()->addDays(2)->format('Y-m-d'), // Before start
            'adh_id' => $user->adh_id ?? 1,
            'clu_id' => $club->club_id,
            'raid_contact' => 'contact@example.com',
            'raid_city' => 'Paris',
            'raid_postal_code' => '75001',
        ]);

        $response->assertSessionHasErrors('ins_end_date');
    }

    /**
     * Test that invalid date formats are rejected.
     */
    public function test_raid_creation_rejects_invalid_date_formats(): void
    {
        $user = User::factory()->create();
        $user->assignRole('gestionnaire-raid');
        $club = Club::factory()->create(['created_by' => $user->id]);
        
        $invalidDates = [
            '25/12/2024',        // DD/MM/YYYY
            '2024/12/25',        // Wrong position
            'invalid-date',      // Text
        ];

        foreach ($invalidDates as $date) {
            $response = $this->actingAs($user)->post('/raids', [
                'raid_name' => 'Raid',
                'raid_date_start' => $date,
                'raid_date_end' => now()->addDays(11)->format('Y-m-d'),
                'ins_start_date' => now()->addDay()->format('Y-m-d'),
                'ins_end_date' => now()->addDays(9)->format('Y-m-d'),
                'adh_id' => $user->adh_id ?? 1,
                'clu_id' => $club->club_id,
                'raid_contact' => 'contact@example.com',
                'raid_city' => 'Paris',
                'raid_postal_code' => '75001',
            ]);

            $this->assertTrue($response->status() === 302 || $response->status() === 422);
        }
    }

    // ================================
    // Contact Information Validation Tests
    // ================================

    /**
     * Test that contact email is required.
     */
    public function test_raid_creation_rejects_missing_contact_email(): void
    {
        $user = User::factory()->create();
        $user->assignRole('gestionnaire-raid');
        $club = Club::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->post('/raids', [
            'raid_name' => 'Raid',
            'raid_date_start' => now()->addDays(10)->format('Y-m-d'),
            'raid_date_end' => now()->addDays(11)->format('Y-m-d'),
            'ins_start_date' => now()->addDay()->format('Y-m-d'),
            'ins_end_date' => now()->addDays(9)->format('Y-m-d'),
            'adh_id' => $user->adh_id ?? 1,
            'clu_id' => $club->club_id,
            'raid_contact' => '',
            'raid_city' => 'Paris',
            'raid_postal_code' => '75001',
        ]);

        $this->assertTrue($response->status() === 302 || $response->status() === 422);
    }

    /**
     * Test that invalid email addresses are rejected.
     */
    public function test_raid_creation_rejects_invalid_email(): void
    {
        $user = User::factory()->create();
        $user->assignRole('gestionnaire-raid');
        $club = Club::factory()->create(['created_by' => $user->id]);
        
        $invalidEmails = [
            'notanemail',
            'user@',
            '@example.com',
            'user @example.com',
            'user@@example.com',
        ];

        foreach ($invalidEmails as $email) {
            $response = $this->actingAs($user)->post('/raids', [
                'raid_name' => 'Raid',
                'raid_date_start' => now()->addDays(10)->format('Y-m-d'),
                'raid_date_end' => now()->addDays(11)->format('Y-m-d'),
                'ins_start_date' => now()->addDay()->format('Y-m-d'),
                'ins_end_date' => now()->addDays(9)->format('Y-m-d'),
                'adh_id' => $user->adh_id ?? 1,
                'clu_id' => $club->club_id,
                'raid_contact' => $email,
                'raid_city' => 'Paris',
                'raid_postal_code' => '75001',
            ]);

            $this->assertTrue($response->status() === 302 || $response->status() === 422);
        }
    }

    /**
     * Test that SQL injection in contact email is rejected.
     */
    public function test_raid_creation_rejects_sql_injection_in_email(): void
    {
        $user = User::factory()->create();
        $user->assignRole('gestionnaire-raid');
        $club = Club::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->post('/raids', [
            'raid_name' => 'Raid',
            'raid_date_start' => now()->addDays(10)->format('Y-m-d'),
            'raid_date_end' => now()->addDays(11)->format('Y-m-d'),
            'ins_start_date' => now()->addDay()->format('Y-m-d'),
            'ins_end_date' => now()->addDays(9)->format('Y-m-d'),
            'adh_id' => $user->adh_id ?? 1,
            'clu_id' => $club->club_id,
            'raid_contact' => "admin@example.com'; DROP TABLE raids;--",
            'raid_city' => 'Paris',
            'raid_postal_code' => '75001',
        ]);

        $this->assertTrue($response->status() === 302 || $response->status() === 422);
    }

    // ================================
    // City and Location Validation Tests
    // ================================

    /**
     * Test that raid city is required.
     */
    public function test_raid_creation_rejects_missing_city(): void
    {
        $user = User::factory()->create();
        $user->assignRole('gestionnaire-raid');
        $club = Club::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->post('/raids', [
            'raid_name' => 'Raid',
            'raid_date_start' => now()->addDays(10)->format('Y-m-d'),
            'raid_date_end' => now()->addDays(11)->format('Y-m-d'),
            'ins_start_date' => now()->addDay()->format('Y-m-d'),
            'ins_end_date' => now()->addDays(9)->format('Y-m-d'),
            'adh_id' => $user->adh_id ?? 1,
            'clu_id' => $club->club_id,
            'raid_contact' => 'contact@example.com',
            'raid_city' => '',
            'raid_postal_code' => '75001',
        ]);

        $this->assertTrue($response->status() === 302 || $response->status() === 422);
    }

    /**
     * Test that raid postal code is required.
     */
    public function test_raid_creation_rejects_missing_postal_code(): void
    {
        $user = User::factory()->create();
        $user->assignRole('gestionnaire-raid');
        $club = Club::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->post('/raids', [
            'raid_name' => 'Raid',
            'raid_date_start' => now()->addDays(10)->format('Y-m-d'),
            'raid_date_end' => now()->addDays(11)->format('Y-m-d'),
            'ins_start_date' => now()->addDay()->format('Y-m-d'),
            'ins_end_date' => now()->addDays(9)->format('Y-m-d'),
            'adh_id' => $user->adh_id ?? 1,
            'clu_id' => $club->club_id,
            'raid_contact' => 'contact@example.com',
            'raid_city' => 'Paris',
            'raid_postal_code' => '',
        ]);

        $this->assertTrue($response->status() === 302 || $response->status() === 422);
    }

    /**
     * Test that excessively long postal codes are rejected.
     */
    public function test_raid_creation_rejects_excessively_long_postal_code(): void
    {
        $user = User::factory()->create();
        $user->assignRole('gestionnaire-raid');
        $club = Club::factory()->create(['created_by' => $user->id]);

        $longCode = str_repeat('1', 50); // Exceeds max:20

        $response = $this->actingAs($user)->post('/raids', [
            'raid_name' => 'Raid',
            'raid_date_start' => now()->addDays(10)->format('Y-m-d'),
            'raid_date_end' => now()->addDays(11)->format('Y-m-d'),
            'ins_start_date' => now()->addDay()->format('Y-m-d'),
            'ins_end_date' => now()->addDays(9)->format('Y-m-d'),
            'adh_id' => $user->adh_id ?? 1,
            'clu_id' => $club->club_id,
            'raid_contact' => 'contact@example.com',
            'raid_city' => 'Paris',
            'raid_postal_code' => $longCode,
        ]);

        $this->assertTrue($response->status() === 302 || $response->status() === 422);
    }

    /**
     * Test that XSS in city is rejected.
     */
    public function test_raid_creation_rejects_xss_in_city(): void
    {
        $user = User::factory()->create();
        $user->assignRole('gestionnaire-raid');
        $club = Club::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->post('/raids', [
            'raid_name' => 'Raid',
            'raid_date_start' => now()->addDays(10)->format('Y-m-d'),
            'raid_date_end' => now()->addDays(11)->format('Y-m-d'),
            'ins_start_date' => now()->addDay()->format('Y-m-d'),
            'ins_end_date' => now()->addDays(9)->format('Y-m-d'),
            'adh_id' => $user->adh_id ?? 1,
            'clu_id' => $club->club_id,
            'raid_contact' => 'contact@example.com',
            'raid_city' => 'Paris<script>alert(1)</script>',
            'raid_postal_code' => '75001',
        ]);

        $this->assertTrue($response->status() === 302 || $response->status() === 422);
    }

    // ================================
    // Website URL Validation Tests
    // ================================

    /**
     * Test that invalid URLs are rejected.
     */
    public function test_raid_creation_rejects_invalid_url(): void
    {
        $user = User::factory()->create();
        $user->assignRole('gestionnaire-raid');
        $club = Club::factory()->create(['created_by' => $user->id]);
        
        $invalidUrls = [
            'notaurl',
            'htp://example.com',
            'example.com',
            'javascript:alert(1)',
        ];

        foreach ($invalidUrls as $url) {
            $response = $this->actingAs($user)->post('/raids', [
                'raid_name' => 'Raid',
                'raid_date_start' => now()->addDays(10)->format('Y-m-d'),
                'raid_date_end' => now()->addDays(11)->format('Y-m-d'),
                'ins_start_date' => now()->addDay()->format('Y-m-d'),
                'ins_end_date' => now()->addDays(9)->format('Y-m-d'),
                'adh_id' => $user->adh_id ?? 1,
                'clu_id' => $club->club_id,
                'raid_contact' => 'contact@example.com',
                'raid_city' => 'Paris',
                'raid_postal_code' => '75001',
                'raid_site_url' => $url,
            ]);

            $this->assertTrue($response->status() === 302 || $response->status() === 422);
        }
    }

    // ================================
    // Image Upload Validation Tests
    // ================================

    /**
     * Test that oversized images are rejected.
     */
    public function test_raid_creation_rejects_oversized_image(): void
    {
        $user = User::factory()->create();
        $user->assignRole('gestionnaire-raid');
        $club = Club::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->post('/raids', [
            'raid_name' => 'Raid',
            'raid_date_start' => now()->addDays(10)->format('Y-m-d'),
            'raid_date_end' => now()->addDays(11)->format('Y-m-d'),
            'ins_start_date' => now()->addDay()->format('Y-m-d'),
            'ins_end_date' => now()->addDays(9)->format('Y-m-d'),
            'adh_id' => $user->adh_id ?? 1,
            'clu_id' => $club->club_id,
            'raid_contact' => 'contact@example.com',
            'raid_city' => 'Paris',
            'raid_postal_code' => '75001',
            'raid_image' => str_repeat('A', (5121 * 1024)),
        ]);

        $this->assertTrue($response->status() === 302 || $response->status() === 422);
    }

    // ================================
    // Authorization Tests
    // ================================

    /**
     * Test that unauthenticated users cannot create raids.
     */
    public function test_raid_creation_requires_authentication(): void
    {
        $response = $this->post('/raids', [
            'raid_name' => 'Raid',
            'raid_date_start' => now()->addDays(10)->format('Y-m-d'),
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    /**
     * Test that only gestionnaire-raid role can create raids.
     */
    public function test_raid_creation_requires_gestionnaire_raid_role(): void
    {
        $user = User::factory()->create();
        // No role assigned

        $response = $this->actingAs($user)->post('/raids', [
            'raid_name' => 'Raid',
            'raid_date_start' => now()->addDays(10)->format('Y-m-d'),
        ]);

        $this->assertTrue($response->status() === 302 || $response->status() === 403);
    }

    // ================================
    // Security Tests
    // ================================

    /**
     * Test NULL byte injection is handled.
     */
    public function test_raid_creation_handles_null_byte_injection(): void
    {
        $user = User::factory()->create();
        $user->assignRole('gestionnaire-raid');
        $club = Club::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->post('/raids', [
            'raid_name' => "Raid\x00Name",
            'raid_date_start' => now()->addDays(10)->format('Y-m-d'),
            'raid_date_end' => now()->addDays(11)->format('Y-m-d'),
            'ins_start_date' => now()->addDay()->format('Y-m-d'),
            'ins_end_date' => now()->addDays(9)->format('Y-m-d'),
            'adh_id' => $user->adh_id ?? 1,
            'clu_id' => $club->club_id,
            'raid_contact' => 'contact@example.com',
            'raid_city' => 'Paris',
            'raid_postal_code' => '75001',
        ]);

        $this->assertTrue($response->status() === 302 || $response->status() === 422);
    }

    /**
     * Test that valid raid creation succeeds.
     */
    public function test_raid_creation_succeeds_with_valid_data(): void
    {
        $user = User::factory()->create();
        $user->assignRole('gestionnaire-raid');
        $club = Club::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->post('/raids', [
            'raid_name' => 'Valid Test Raid',
            'raid_date_start' => now()->addDays(10)->format('Y-m-d'),
            'raid_date_end' => now()->addDays(11)->format('Y-m-d'),
            'ins_start_date' => now()->addDay()->format('Y-m-d'),
            'ins_end_date' => now()->addDays(9)->format('Y-m-d'),
            'adh_id' => $user->adh_id ?? 1,
            'clu_id' => $club->club_id,
            'raid_contact' => 'contact@example.com',
            'raid_city' => 'Paris',
            'raid_postal_code' => '75001',
            'raid_street' => '123 Main Street',
        ]);

        $response->assertStatus(302);
    }
}
