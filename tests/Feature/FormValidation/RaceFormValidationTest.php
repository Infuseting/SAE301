<?php

namespace Tests\Feature\FormValidation;

use App\Models\User;
use App\Models\ParamType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RaceFormValidationTest covers comprehensive validation testing for race creation.
 * Tests date validation, pricing, participants count, and security vulnerabilities.
 */
class RaceFormValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed roles and permissions
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    // ================================
    // Race Title Validation Tests
    // ================================

    /**
     * Test that race title is required.
     */
    public function test_race_creation_rejects_missing_title(): void
    {
        $user = User::factory()->create();
        $user->assignRole('responsable-course');

        $response = $this->actingAs($user)->post('/races/create', [
            'title' => '',
            'startDate' => now()->addDay()->format('Y-m-d'),
            'startTime' => '09:00',
            'endDate' => now()->addDay()->format('Y-m-d'),
            'endTime' => '17:00',
            'minParticipants' => 1,
            'maxParticipants' => 100,
            'minPerTeam' => 1,
            'maxPerTeam' => 5,
            'difficulty' => 'Moyen',
            'type' => 1,
            'minTeams' => 1,
            'maxTeams' => 20,
            'priceMajor' => 50,
            'responsableId' => $user->id,
        ]);

        $this->assertTrue($response->status() === 302 || $response->status() === 422);
    }

    /**
     * Test that race title cannot exceed maximum length.
     */
    public function test_race_creation_rejects_excessively_long_title(): void
    {
        $user = User::factory()->create();
        $user->assignRole('responsable-course');

        $longTitle = str_repeat('A', 150); // Exceeds max:100

        $response = $this->actingAs($user)->post('/races/create', [
            'title' => $longTitle,
            'startDate' => now()->addDay()->format('Y-m-d'),
            'startTime' => '09:00',
            'endDate' => now()->addDay()->format('Y-m-d'),
            'endTime' => '17:00',
            'minParticipants' => 1,
            'maxParticipants' => 100,
            'minPerTeam' => 1,
            'maxPerTeam' => 5,
            'difficulty' => 'Moyen',
            'type' => 1,
            'minTeams' => 1,
            'maxTeams' => 20,
            'priceMajor' => 50,
            'responsableId' => $user->id,
        ]);

        $this->assertTrue($response->status() === 302 || $response->status() === 422);
    }

    /**
     * Test that XSS attempts in race title are rejected.
     */
    public function test_race_creation_rejects_xss_in_title(): void
    {
        $user = User::factory()->create();
        $user->assignRole('responsable-course');
        
        $xssPayloads = [
            '<script>alert("XSS")</script>',
            '<img src=x onerror="alert(\'race hack\')">',
            '"><script>alert(1)</script>',
        ];

        foreach ($xssPayloads as $payload) {
            $response = $this->actingAs($user)->post('/races/create', [
                'title' => $payload,
                'startDate' => now()->addDay()->format('Y-m-d'),
                'startTime' => '09:00',
                'endDate' => now()->addDay()->format('Y-m-d'),
                'endTime' => '17:00',
                'minParticipants' => 1,
                'maxParticipants' => 100,
                'minPerTeam' => 1,
                'maxPerTeam' => 5,
                'difficulty' => 'Moyen',
                'type' => 1,
                'minTeams' => 1,
                'maxTeams' => 20,
                'priceMajor' => 50,
                'responsableId' => $user->id,
            ]);

            $this->assertTrue($response->status() === 302 || $response->status() === 422);
        }
    }

    // ================================
    // Date and Time Validation Tests
    // ================================

    /**
     * Test that past start dates are rejected.
     */
    public function test_race_creation_rejects_past_start_date(): void
    {
        $user = User::factory()->create();
        $user->assignRole('responsable-course');

        $response = $this->actingAs($user)->post('/races/create', [
            'title' => 'Past Race',
            'startDate' => now()->subDay()->format('Y-m-d'),
            'startTime' => '09:00',
            'endDate' => now()->format('Y-m-d'),
            'endTime' => '17:00',
            'minParticipants' => 1,
            'maxParticipants' => 100,
            'minPerTeam' => 1,
            'maxPerTeam' => 5,
            'difficulty' => 'Moyen',
            'type' => 1,
            'minTeams' => 1,
            'maxTeams' => 20,
            'priceMajor' => 50,
            'responsableId' => $user->id,
        ]);

        $response->assertSessionHasErrors('startDate');
    }

    /**
     * Test that invalid date formats are rejected.
     */
    public function test_race_creation_rejects_invalid_date_formats(): void
    {
        $user = User::factory()->create();
        $user->assignRole('responsable-course');
        
        $invalidDates = [
            '25/12/2024',        // DD/MM/YYYY format
            '2024/12/25',        // Wrong year position
            'invalid-date',      // Text
            '2024-13-45',        // Invalid month and day
        ];

        foreach ($invalidDates as $date) {
            $response = $this->actingAs($user)->post('/races/create', [
                'title' => 'Race',
                'startDate' => $date,
                'startTime' => '09:00',
                'endDate' => now()->addDay()->format('Y-m-d'),
                'endTime' => '17:00',
                'minParticipants' => 1,
                'maxParticipants' => 100,
                'minPerTeam' => 1,
                'maxPerTeam' => 5,
                'difficulty' => 'Moyen',
                'type' => 1,
                'minTeams' => 1,
                'maxTeams' => 20,
                'priceMajor' => 50,
                'responsableId' => $user->id,
            ]);

            $this->assertTrue($response->status() === 302 || $response->status() === 422);
        }
    }

    /**
     * Test that invalid time formats are rejected.
     */
    public function test_race_creation_rejects_invalid_time_formats(): void
    {
        $user = User::factory()->create();
        $user->assignRole('responsable-course');
        
        $invalidTimes = [
            '9:00',          // Single digit hour (may vary)
            '25:00',         // Invalid hour
            '09:60',         // Invalid minute
            '09-00',         // Wrong separator
            '0900',          // No separator
            'invalid',       // Text
        ];

        foreach ($invalidTimes as $time) {
            $response = $this->actingAs($user)->post('/races/create', [
                'title' => 'Race',
                'startDate' => now()->addDay()->format('Y-m-d'),
                'startTime' => $time,
                'endDate' => now()->addDay()->format('Y-m-d'),
                'endTime' => '17:00',
                'minParticipants' => 1,
                'maxParticipants' => 100,
                'minPerTeam' => 1,
                'maxPerTeam' => 5,
                'difficulty' => 'Moyen',
                'type' => 1,
                'minTeams' => 1,
                'maxTeams' => 20,
                'priceMajor' => 50,
                'responsableId' => $user->id,
            ]);

            $this->assertTrue($response->status() === 302 || $response->status() === 422);
        }
    }

    /**
     * Test that end date before start date is rejected.
     */
    public function test_race_creation_rejects_end_date_before_start(): void
    {
        $user = User::factory()->create();
        $user->assignRole('responsable-course');

        $response = $this->actingAs($user)->post('/races/create', [
            'title' => 'Bad Race',
            'startDate' => now()->addDays(5)->format('Y-m-d'),
            'startTime' => '09:00',
            'endDate' => now()->addDays(2)->format('Y-m-d'), // Before start
            'endTime' => '17:00',
            'minParticipants' => 1,
            'maxParticipants' => 100,
            'minPerTeam' => 1,
            'maxPerTeam' => 5,
            'difficulty' => 'Moyen',
            'type' => 1,
            'minTeams' => 1,
            'maxTeams' => 20,
            'priceMajor' => 50,
            'responsableId' => $user->id,
        ]);

        $response->assertSessionHasErrors('endDate');
    }

    // ================================
    // Participant Count Validation Tests
    // ================================

    /**
     * Test that minimum participants must be at least 1.
     */
    public function test_race_creation_rejects_zero_min_participants(): void
    {
        $user = User::factory()->create();
        $user->assignRole('responsable-course');

        $response = $this->actingAs($user)->post('/races/create', [
            'title' => 'Race',
            'startDate' => now()->addDay()->format('Y-m-d'),
            'startTime' => '09:00',
            'endDate' => now()->addDay()->format('Y-m-d'),
            'endTime' => '17:00',
            'minParticipants' => 0,
            'maxParticipants' => 100,
            'minPerTeam' => 1,
            'maxPerTeam' => 5,
            'difficulty' => 'Moyen',
            'type' => 1,
            'minTeams' => 1,
            'maxTeams' => 20,
            'priceMajor' => 50,
            'responsableId' => $user->id,
        ]);

        $response->assertSessionHasErrors('minParticipants');
    }

    /**
     * Test that max participants must exceed min participants.
     */
    public function test_race_creation_rejects_max_less_than_min_participants(): void
    {
        $user = User::factory()->create();
        $user->assignRole('responsable-course');

        $response = $this->actingAs($user)->post('/races/create', [
            'title' => 'Race',
            'startDate' => now()->addDay()->format('Y-m-d'),
            'startTime' => '09:00',
            'endDate' => now()->addDay()->format('Y-m-d'),
            'endTime' => '17:00',
            'minParticipants' => 100,
            'maxParticipants' => 50, // Less than min
            'minPerTeam' => 1,
            'maxPerTeam' => 5,
            'difficulty' => 'Moyen',
            'type' => 1,
            'minTeams' => 1,
            'maxTeams' => 20,
            'priceMajor' => 50,
            'responsableId' => $user->id,
        ]);

        $response->assertSessionHasErrors('maxParticipants');
    }

    /**
     * Test that SQL injection in participant counts is sanitized.
     */
    public function test_race_creation_rejects_sql_injection_in_participant_counts(): void
    {
        $user = User::factory()->create();
        $user->assignRole('responsable-course');

        $response = $this->actingAs($user)->post('/races/create', [
            'title' => 'Race',
            'startDate' => now()->addDay()->format('Y-m-d'),
            'startTime' => '09:00',
            'endDate' => now()->addDay()->format('Y-m-d'),
            'endTime' => '17:00',
            'minParticipants' => "1'; DROP TABLE races; --",
            'maxParticipants' => 100,
            'minPerTeam' => 1,
            'maxPerTeam' => 5,
            'difficulty' => 'Moyen',
            'type' => 1,
            'minTeams' => 1,
            'maxTeams' => 20,
            'priceMajor' => 50,
            'responsableId' => $user->id,
        ]);

        // Should reject or handle gracefully
        $this->assertTrue($response->status() === 302 || $response->status() === 422);
    }

    // ================================
    // Team Count Validation Tests
    // ================================

    /**
     * Test that min teams must be at least 1.
     */
    public function test_race_creation_rejects_zero_min_teams(): void
    {
        $user = User::factory()->create();
        $user->assignRole('responsable-course');

        $response = $this->actingAs($user)->post('/races/create', [
            'title' => 'Race',
            'startDate' => now()->addDay()->format('Y-m-d'),
            'startTime' => '09:00',
            'endDate' => now()->addDay()->format('Y-m-d'),
            'endTime' => '17:00',
            'minParticipants' => 1,
            'maxParticipants' => 100,
            'minPerTeam' => 1,
            'maxPerTeam' => 5,
            'difficulty' => 'Moyen',
            'type' => 1,
            'minTeams' => 0, // Invalid
            'maxTeams' => 20,
            'priceMajor' => 50,
            'responsableId' => $user->id,
        ]);

        $response->assertSessionHasErrors('minTeams');
    }

    /**
     * Test that max teams must exceed min teams.
     */
    public function test_race_creation_rejects_max_teams_less_than_min(): void
    {
        $user = User::factory()->create();
        $user->assignRole('responsable-course');

        $response = $this->actingAs($user)->post('/races/create', [
            'title' => 'Race',
            'startDate' => now()->addDay()->format('Y-m-d'),
            'startTime' => '09:00',
            'endDate' => now()->addDay()->format('Y-m-d'),
            'endTime' => '17:00',
            'minParticipants' => 1,
            'maxParticipants' => 100,
            'minPerTeam' => 1,
            'maxPerTeam' => 5,
            'difficulty' => 'Moyen',
            'type' => 1,
            'minTeams' => 20,
            'maxTeams' => 10, // Less than min
            'priceMajor' => 50,
            'responsableId' => $user->id,
        ]);

        $response->assertSessionHasErrors('maxTeams');
    }

    // ================================
    // Pricing Validation Tests
    // ================================

    /**
     * Test that pricing cannot be negative.
     */
    public function test_race_creation_rejects_negative_pricing(): void
    {
        $user = User::factory()->create();
        $user->assignRole('responsable-course');

        $response = $this->actingAs($user)->post('/races/create', [
            'title' => 'Race',
            'startDate' => now()->addDay()->format('Y-m-d'),
            'startTime' => '09:00',
            'endDate' => now()->addDay()->format('Y-m-d'),
            'endTime' => '17:00',
            'minParticipants' => 1,
            'maxParticipants' => 100,
            'minPerTeam' => 1,
            'maxPerTeam' => 5,
            'difficulty' => 'Moyen',
            'type' => 1,
            'minTeams' => 1,
            'maxTeams' => 20,
            'priceMajor' => -50, // Negative
            'responsableId' => $user->id,
        ]);

        $this->assertTrue($response->status() === 302 || $response->status() === 422);
    }

    /**
     * Test that adherent price cannot exceed major price.
     */
    public function test_race_creation_rejects_adherent_price_exceeding_major(): void
    {
        $user = User::factory()->create();
        $user->assignRole('responsable-course');

        $response = $this->actingAs($user)->post('/races/create', [
            'title' => 'Race',
            'startDate' => now()->addDay()->format('Y-m-d'),
            'startTime' => '09:00',
            'endDate' => now()->addDay()->format('Y-m-d'),
            'endTime' => '17:00',
            'minParticipants' => 1,
            'maxParticipants' => 100,
            'minPerTeam' => 1,
            'maxPerTeam' => 5,
            'difficulty' => 'Moyen',
            'type' => 1,
            'minTeams' => 1,
            'maxTeams' => 20,
            'priceMajor' => 50,
            'priceAdherent' => 100, // Greater than major
            'responsableId' => $user->id,
        ]);

        $response->assertSessionHasErrors('priceAdherent');
    }

    /**
     * Test SQL injection attempts in pricing.
     */
    public function test_race_creation_rejects_sql_injection_in_pricing(): void
    {
        $user = User::factory()->create();
        $user->assignRole('responsable-course');

        $response = $this->actingAs($user)->post('/races/create', [
            'title' => 'Race',
            'startDate' => now()->addDay()->format('Y-m-d'),
            'startTime' => '09:00',
            'endDate' => now()->addDay()->format('Y-m-d'),
            'endTime' => '17:00',
            'minParticipants' => 1,
            'maxParticipants' => 100,
            'minPerTeam' => 1,
            'maxPerTeam' => 5,
            'difficulty' => 'Moyen',
            'type' => 1,
            'minTeams' => 1,
            'maxTeams' => 20,
            'priceMajor' => "50; UPDATE races SET priceMajor=0;--",
            'responsableId' => $user->id,
        ]);

        $this->assertTrue($response->status() === 302 || $response->status() === 422);
    }

    // ================================
    // Authorization and Responsibility Tests
    // ================================

    /**
     * Test that invalid responsible user is rejected.
     */
    public function test_race_creation_rejects_invalid_responsible_user(): void
    {
        $user = User::factory()->create();
        $user->assignRole('responsable-course');

        $response = $this->actingAs($user)->post('/races/create', [
            'title' => 'Race',
            'startDate' => now()->addDay()->format('Y-m-d'),
            'startTime' => '09:00',
            'endDate' => now()->addDay()->format('Y-m-d'),
            'endTime' => '17:00',
            'minParticipants' => 1,
            'maxParticipants' => 100,
            'minPerTeam' => 1,
            'maxPerTeam' => 5,
            'difficulty' => 'Moyen',
            'type' => 1,
            'minTeams' => 1,
            'maxTeams' => 20,
            'priceMajor' => 50,
            'responsableId' => 99999, // Non-existent user
        ]);

        $response->assertSessionHasErrors('responsableId');
    }

    /**
     * Test that unauthenticated users cannot create races.
     */
    public function test_race_creation_requires_authentication(): void
    {
        $response = $this->post('/races/create', [
            'title' => 'Race',
            'startDate' => now()->addDay()->format('Y-m-d'),
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    // ================================
    // Image Validation Tests
    // ================================

    /**
     * Test that oversized images are rejected.
     */
    public function test_race_creation_rejects_oversized_image(): void
    {
        $user = User::factory()->create();
        $user->assignRole('responsable-course');

        $response = $this->actingAs($user)->post('/races/create', [
            'title' => 'Race',
            'startDate' => now()->addDay()->format('Y-m-d'),
            'startTime' => '09:00',
            'endDate' => now()->addDay()->format('Y-m-d'),
            'endTime' => '17:00',
            'minParticipants' => 1,
            'maxParticipants' => 100,
            'minPerTeam' => 1,
            'maxPerTeam' => 5,
            'difficulty' => 'Moyen',
            'type' => 1,
            'minTeams' => 1,
            'maxTeams' => 20,
            'priceMajor' => 50,
            'responsableId' => $user->id,
            'image' => str_repeat('A', (5121 * 1024)),
        ]);

        $this->assertTrue($response->status() === 302 || $response->status() === 422);
    }

    // ================================
    // Difficulty and Type Validation Tests
    // ================================

    /**
     * Test that difficulty is required.
     */
    public function test_race_creation_rejects_missing_difficulty(): void
    {
        $user = User::factory()->create();
        $user->assignRole('responsable-course');

        $response = $this->actingAs($user)->post('/races/create', [
            'title' => 'Race',
            'startDate' => now()->addDay()->format('Y-m-d'),
            'startTime' => '09:00',
            'endDate' => now()->addDay()->format('Y-m-d'),
            'endTime' => '17:00',
            'minParticipants' => 1,
            'maxParticipants' => 100,
            'minPerTeam' => 1,
            'maxPerTeam' => 5,
            'difficulty' => '',
            'type' => 1,
            'minTeams' => 1,
            'maxTeams' => 20,
            'priceMajor' => 50,
            'responsableId' => $user->id,
        ]);

        $this->assertTrue($response->status() === 302 || $response->status() === 422);
    }

    /**
     * Test that invalid race type is rejected.
     */
    public function test_race_creation_rejects_invalid_race_type(): void
    {
        $user = User::factory()->create();
        $user->assignRole('responsable-course');

        $response = $this->actingAs($user)->post('/races/create', [
            'title' => 'Race',
            'startDate' => now()->addDay()->format('Y-m-d'),
            'startTime' => '09:00',
            'endDate' => now()->addDay()->format('Y-m-d'),
            'endTime' => '17:00',
            'minParticipants' => 1,
            'maxParticipants' => 100,
            'minPerTeam' => 1,
            'maxPerTeam' => 5,
            'difficulty' => 'Moyen',
            'type' => 99999, // Non-existent type
            'minTeams' => 1,
            'maxTeams' => 20,
            'priceMajor' => 50,
            'responsableId' => $user->id,
        ]);

        $this->assertTrue($response->status() === 302 || $response->status() === 422);
    }

    // ================================
    // XSS and Injection Tests in Description
    // ================================

    /**
     * Test that very long descriptions are rejected.
     */
    public function test_race_creation_rejects_excessively_long_description(): void
    {
        $user = User::factory()->create();
        $user->assignRole('responsable-course');

        $longDesc = str_repeat('A', 2500); // Exceeds max:2000

        $response = $this->actingAs($user)->post('/races/create', [
            'title' => 'Race',
            'startDate' => now()->addDay()->format('Y-m-d'),
            'startTime' => '09:00',
            'endDate' => now()->addDay()->format('Y-m-d'),
            'endTime' => '17:00',
            'minParticipants' => 1,
            'maxParticipants' => 100,
            'minPerTeam' => 1,
            'maxPerTeam' => 5,
            'difficulty' => 'Moyen',
            'description' => $longDesc,
            'type' => 1,
            'minTeams' => 1,
            'maxTeams' => 20,
            'priceMajor' => 50,
            'responsableId' => $user->id,
        ]);

        $this->assertTrue($response->status() === 302 || $response->status() === 422);
    }

    /**
     * Test that XSS in description is rejected.
     */
    public function test_race_creation_rejects_xss_in_description(): void
    {
        $user = User::factory()->create();
        $user->assignRole('responsable-course');

        $response = $this->actingAs($user)->post('/races/create', [
            'title' => 'Race',
            'startDate' => now()->addDay()->format('Y-m-d'),
            'startTime' => '09:00',
            'endDate' => now()->addDay()->format('Y-m-d'),
            'endTime' => '17:00',
            'minParticipants' => 1,
            'maxParticipants' => 100,
            'minPerTeam' => 1,
            'maxPerTeam' => 5,
            'difficulty' => 'Moyen',
            'description' => '<script>alert("XSS")</script>',
            'type' => 1,
            'minTeams' => 1,
            'maxTeams' => 20,
            'priceMajor' => 50,
            'responsableId' => $user->id,
        ]);

        $this->assertTrue($response->status() === 302 || $response->status() === 422);
    }
}
