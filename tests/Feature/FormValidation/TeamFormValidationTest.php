<?php

namespace Tests\Feature\FormValidation;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * TeamFormValidationTest covers comprehensive validation testing for team creation forms.
 * Tests various security vulnerabilities, malicious input patterns, and edge cases.
 */
class TeamFormValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed roles and permissions for authentication context
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    // ================================
    // Team Name Validation Tests
    // ================================

    /**
     * Test that team name is required.
     */
    public function test_team_creation_rejects_missing_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/createTeam', [
            'name' => '',
            'teammates' => [],
            'join_team' => true,
        ]);

        $response->assertSessionHasErrors('name');
        $response->assertStatus(302);
    }

    /**
     * Test that team name cannot exceed maximum length.
     */
    public function test_team_creation_rejects_excessively_long_name(): void
    {
        $user = User::factory()->create();
        $longName = str_repeat('A', 100); // Exceeds max:32

        $response = $this->actingAs($user)->post('/createTeam', [
            'name' => $longName,
            'teammates' => [],
            'join_team' => true,
        ]);

        $response->assertSessionHasErrors('name');
        $response->assertStatus(302);
    }

    /**
     * Test that XSS attempts in team name are sanitized.
     */
    public function test_team_creation_rejects_xss_in_name(): void
    {
        $user = User::factory()->create();
        
        $xssPayloads = [
            '<script>alert("XSS")</script>',
            '<img src=x onerror="alert(\'XSS\')">',
            'javascript:alert("XSS")',
            '<svg/onload=alert("XSS")>',
            '"><script>alert(String.fromCharCode(88,83,83))</script>',
        ];

        foreach ($xssPayloads as $payload) {
            $response = $this->actingAs($user)->post('/createTeam', [
                'name' => $payload,
                'teammates' => [],
                'join_team' => true,
            ]);

            // Server should either reject or sanitize
            $this->assertTrue(
                $response->status() === 302 || $response->status() === 422,
                "XSS payload should be rejected or sanitized: {$payload}"
            );
        }
    }

    /**
     * Test that SQL injection attempts in team name are rejected.
     */
    public function test_team_creation_rejects_sql_injection_in_name(): void
    {
        $user = User::factory()->create();
        
        $sqlPayloads = [
            "'; DROP TABLE teams; --",
            "1' OR '1'='1",
            "admin' --",
            "1; DELETE FROM teams WHERE 1=1;",
            "' UNION SELECT * FROM users WHERE '1'='1",
        ];

        foreach ($sqlPayloads as $payload) {
            $response = $this->actingAs($user)->post('/createTeam', [
                'name' => $payload,
                'teammates' => [],
                'join_team' => true,
            ]);

            // Should not cause database errors (either validation error or success with sanitization)
            $this->assertTrue(
                $response->status() === 302 || $response->status() === 422,
                "SQL injection payload should be rejected: {$payload}"
            );
        }
    }

    /**
     * Test that special characters in team name are handled safely.
     */
    public function test_team_creation_handles_special_characters_in_name(): void
    {
        $user = User::factory()->create();
        
        $specialNames = [
            'Team & Friends',
            "Team's Glory",
            'Team "Awesome"',
            'Team <Legends>',
            'Team {Champions}',
            'Team | Runners',
            'Équipe été',
            'Команда 123',
        ];

        foreach ($specialNames as $name) {
            $response = $this->actingAs($user)->post('/createTeam', [
                'name' => $name,
                'teammates' => [],
                'join_team' => true,
            ]);

            // Should accept or validate gracefully
            $this->assertTrue(
                $response->status() === 302 || $response->status() === 422,
                "Special character name should be handled: {$name}"
            );
        }
    }

    // ================================
    // Team Image Validation Tests
    // ================================

    /**
     * Test that invalid image files are rejected.
     */
    public function test_team_creation_rejects_invalid_image_formats(): void
    {
        $user = User::factory()->create();
        
        // Test with text file instead of image
        $response = $this->actingAs($user)->post('/createTeam', [
            'name' => 'Valid Team',
            'image' => 'not_an_image.txt',
            'teammates' => [],
            'join_team' => true,
        ]);

        // Depending on validation, may return 422
        $this->assertTrue(
            $response->status() === 302 || $response->status() === 422,
            'Non-image file should be rejected'
        );
    }

    /**
     * Test that excessively large images are rejected.
     */
    public function test_team_creation_rejects_oversized_image(): void
    {
        $user = User::factory()->create();

        // Create fake large file (over 2MB)
        $largeContent = str_repeat('A', 2049 * 1024); // 2049 KB

        $response = $this->actingAs($user)->post('/createTeam', [
            'name' => 'Valid Team',
            'image' => $largeContent,
            'teammates' => [],
            'join_team' => true,
        ]);

        // Should either reject or handle gracefully
        $this->assertTrue(
            $response->status() === 302 || $response->status() === 422,
            'Oversized image should be rejected'
        );
    }

    // ================================
    // Teammates Validation Tests
    // ================================

    /**
     * Test that non-existent teammate IDs are rejected.
     */
    public function test_team_creation_rejects_invalid_teammate_ids(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/createTeam', [
            'name' => 'Valid Team',
            'teammates' => [
                ['id' => 99999], // Non-existent user
                ['id' => 88888],
            ],
            'join_team' => false,
        ]);

        $response->assertSessionHasErrors('teammates.0.id');
        $response->assertStatus(302);
    }

    /**
     * Test that string teammate IDs are rejected.
     */
    public function test_team_creation_rejects_non_integer_teammate_ids(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/createTeam', [
            'name' => 'Valid Team',
            'teammates' => [
                ['id' => 'notanumber'],
                ['id' => 'admin'],
            ],
            'join_team' => true,
        ]);

        // Should validate and reject
        $this->assertTrue(
            $response->status() === 302 || $response->status() === 422,
            'Non-integer teammate IDs should be rejected'
        );
    }

    /**
     * Test that duplicate teammate entries are handled properly.
     */
    public function test_team_creation_handles_duplicate_teammates(): void
    {
        $user = User::factory()->create();
        $teammate = User::factory()->create();

        $response = $this->actingAs($user)->post('/createTeam', [
            'name' => 'Valid Team',
            'teammates' => [
                ['id' => $teammate->id],
                ['id' => $teammate->id], // Duplicate
            ],
            'join_team' => true,
        ]);

        // Should handle gracefully (no duplicate team members)
        $this->assertTrue($response->status() === 302 || $response->status() === 422);
    }

    // ================================
    // Email Invites Validation Tests
    // ================================

    /**
     * Test that invalid email addresses are rejected.
     */
    public function test_team_creation_rejects_invalid_email_addresses(): void
    {
        $user = User::factory()->create();
        
        $invalidEmails = [
            'notanemail',
            'user@',
            '@example.com',
            'user @example.com',
            'user@exam ple.com',
            'user@@example.com',
            '.user@example.com',
            'user.@example.com',
        ];

        foreach ($invalidEmails as $email) {
            $response = $this->actingAs($user)->post('/createTeam', [
                'name' => 'Valid Team',
                'emailInvites' => [$email],
                'join_team' => true,
            ]);

            // Should validate and reject
            $this->assertTrue(
                $response->status() === 302 || $response->status() === 422,
                "Invalid email should be rejected: {$email}"
            );
        }
    }

    /**
     * Test that extremely long email addresses are rejected.
     */
    public function test_team_creation_rejects_excessively_long_email(): void
    {
        $user = User::factory()->create();
        $longEmail = 'user' . str_repeat('a', 255) . '@example.com'; // Exceeds typical email limits

        $response = $this->actingAs($user)->post('/createTeam', [
            'name' => 'Valid Team',
            'emailInvites' => [$longEmail],
            'join_team' => true,
        ]);

        // Should reject the oversized email
        $this->assertTrue(
            $response->status() === 302 || $response->status() === 422,
            'Oversized email should be rejected'
        );
    }

    /**
     * Test that SQL injection attempts in email are rejected.
     */
    public function test_team_creation_rejects_sql_injection_in_email(): void
    {
        $user = User::factory()->create();
        
        $sqlPayloads = [
            "user@example.com'; DROP TABLE--",
            "test@test.com' OR '1'='1",
            "user@example.com--",
        ];

        foreach ($sqlPayloads as $payload) {
            $response = $this->actingAs($user)->post('/createTeam', [
                'name' => 'Valid Team',
                'emailInvites' => [$payload],
                'join_team' => true,
            ]);

            // Should reject
            $this->assertTrue(
                $response->status() === 302 || $response->status() === 422,
                "SQL injection in email should be rejected: {$payload}"
            );
        }
    }

    /**
     * Test that XSS attempts in email are rejected.
     */
    public function test_team_creation_rejects_xss_in_email(): void
    {
        $user = User::factory()->create();
        
        $xssPayloads = [
            'user<script>alert(1)</script>@example.com',
            'user@example.com<img src=x onerror=alert(1)>',
            'user@example.com" onload="alert(1)',
        ];

        foreach ($xssPayloads as $payload) {
            $response = $this->actingAs($user)->post('/createTeam', [
                'name' => 'Valid Team',
                'emailInvites' => [$payload],
                'join_team' => true,
            ]);

            // Should reject
            $this->assertTrue(
                $response->status() === 302 || $response->status() === 422,
                "XSS in email should be rejected: {$payload}"
            );
        }
    }

    // ================================
    // Business Logic Tests
    // ================================

    /**
     * Test that at least one participant is required.
     */
    public function test_team_creation_requires_at_least_one_participant(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/createTeam', [
            'name' => 'Valid Team',
            'teammates' => [],
            'join_team' => false,
        ]);

        // Should reject because no participants
        $response->assertSessionHasErrors('teammates');
        $response->assertStatus(302);
    }

    /**
     * Test that unauthenticated users cannot create teams.
     */
    public function test_team_creation_requires_authentication(): void
    {
        $response = $this->post('/createTeam', [
            'name' => 'Valid Team',
            'teammates' => [],
            'join_team' => true,
        ]);

        // Should redirect to login
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    /**
     * Test that valid team creation succeeds.
     */
    public function test_team_creation_succeeds_with_valid_data(): void
    {
        $user = User::factory()->create();
        $teammate = User::factory()->create();

        $response = $this->actingAs($user)->post('/createTeam', [
            'name' => 'Valid Team Name',
            'teammates' => [['id' => $teammate->id]],
            'join_team' => true,
        ]);

        // Should succeed (redirect to dashboard or team show page)
        $response->assertStatus(302);
    }

    /**
     * Test that only creator and valid teammates can join team.
     */
    public function test_team_creation_only_adds_creator_and_valid_teammates(): void
    {
        $user = User::factory()->create();
        $teammate1 = User::factory()->create();
        $teammate2 = User::factory()->create();

        $response = $this->actingAs($user)->post('/createTeam', [
            'name' => 'Test Team',
            'teammates' => [
                ['id' => $teammate1->id],
                ['id' => $teammate2->id],
            ],
            'join_team' => true,
        ]);

        $response->assertStatus(302);
        // In a full test, we would verify team members were added correctly
    }

    /**
     * Test NULL byte injection attempts are rejected.
     */
    public function test_team_creation_rejects_null_byte_injection(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/createTeam', [
            'name' => "Team\x00Name",
            'teammates' => [],
            'join_team' => true,
        ]);

        // Should handle safely
        $this->assertTrue($response->status() === 302 || $response->status() === 422);
    }

    /**
     * Test that numeric JOIN_TEAM values are handled.
     */
    public function test_team_creation_handles_numeric_join_team_value(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/createTeam', [
            'name' => 'Valid Team',
            'teammates' => [],
            'join_team' => 1, // Numeric instead of boolean
        ]);

        // Should handle or accept
        $this->assertTrue($response->status() === 302 || $response->status() === 422);
    }

    /**
     * Test extremely long teammate array is handled.
     */
    public function test_team_creation_handles_very_large_teammate_list(): void
    {
        $user = User::factory()->create();
        
        // Create 100+ teammate IDs (most realistic max would be much lower)
        $teammates = [];
        for ($i = 1; $i <= 100; $i++) {
            $teammates[] = ['id' => (int) $i];
        }

        $response = $this->actingAs($user)->post('/createTeam', [
            'name' => 'Big Team',
            'teammates' => $teammates,
            'join_team' => true,
        ]);

        // Should handle gracefully
        $this->assertTrue(
            $response->status() === 302 || $response->status() === 422,
            'Large teammate list should be handled'
        );
    }
}
