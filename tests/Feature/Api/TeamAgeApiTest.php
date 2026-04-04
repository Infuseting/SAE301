<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TeamAgeApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test getting age thresholds.
     */
    public function test_can_get_age_thresholds(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/teams/age-thresholds');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'thresholds' => [
                        'min',
                        'intermediate',
                        'adult',
                    ],
                    'rules',
                ]
            ]);
    }

    /**
     * Test validating a valid team.
     */
    public function test_validates_valid_team(): void
    {
        Sanctum::actingAs($this->user);

        // 14 (minor) + 20 (adult) should be valid
        $response = $this->postJson('/api/teams/validate-ages', [
            'ages' => [14, 20],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'OK',
                'data' => [
                    'valid' => true,
                    'errors' => [],
                    'details' => [
                        'minors' => [
                            [
                                'index' => 0,
                                'age' => 14
                            ]
                        ],
                        'adults' => [
                            [
                                'index' => 1,
                                'age' => 20
                            ]
                        ],
                        'all_intermediate_or_above' => false
                    ],
                ]
            ]);
    }

    /**
     * Test validating an invalid team (minors only).
     */
    public function test_rejects_invalid_team_minors_only(): void
    {
        Sanctum::actingAs($this->user);

        // 14 (minor) + 15 (minor) should be invalid (no adult)
        $response = $this->postJson('/api/teams/validate-ages', [
            'ages' => [14, 15],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'valid' => false,
                ]
            ]);

        $response->assertJsonStructure([
            'data' => [
                'errors'
            ]
        ]);
    }

    /**
     * Test checking participant logic.
     */
    public function test_check_participant_eligibility(): void
    {
        Sanctum::actingAs($this->user);

        // 10 years old -> too young (min 12)
        $response = $this->postJson('/api/teams/check-participant', [
            'age' => 10,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'eligible' => false,
                    'age' => 10,
                    'is_minor' => true,
                    'is_adult' => false
                ]
            ]);

        // 14 years old -> eligible but minor
        $response = $this->postJson('/api/teams/check-participant', [
            'age' => 14,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'eligible' => true,
                    'age' => 14,
                    'is_minor' => true,
                    'is_adult' => false
                ]
            ]);

        // 20 years old -> eligible adult
        $response = $this->postJson('/api/teams/check-participant', [
            'age' => 20,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'eligible' => true,
                    'age' => 20,
                    'is_minor' => false,
                    'is_adult' => true
                ]
            ]);
    }

    /**
     * Test validation handles input errors.
     */
    public function test_handles_validation_errors(): void
    {
        Sanctum::actingAs($this->user);

        // Missing 'ages' field
        $response = $this->postJson('/api/teams/validate-ages', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ages']);
    }
}
