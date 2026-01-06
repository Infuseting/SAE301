<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TeamAgeApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test getting age thresholds.
     */
    public function test_can_get_age_thresholds()
    {
        $response = $this->getJson('/api/team/age-thresholds');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'thresholds' => [
                    'min',
                    'intermediate',
                    'adult',
                ],
                'rules',
            ]);
    }

    /**
     * Test validating a valid team.
     */
    public function test_validates_valid_team()
    {
        // 14 (minor) + 20 (adult) should be valid
        $response = $this->postJson('/api/team/validate-ages', [
            'ages' => [14, 20],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'valid' => true,
                'errors' => [],
            ]);
    }

    /**
     * Test validating an invalid team (minors only).
     */
    public function test_rejects_invalid_team_minors_only()
    {
        // 14 (minor) + 15 (minor) should be invalid (no adult)
        $response = $this->postJson('/api/team/validate-ages', [
            'ages' => [14, 15],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'valid' => false,
            ]);
        
        $response->assertJsonStructure(['errors']);
    }

    /**
     * Test checking participant logic.
     */
    public function test_check_participant_eligibility()
    {
        // 10 years old -> too young (min 12)
        $response = $this->postJson('/api/team/check-participant', [
            'age' => 10,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'eligible' => false,
            ]);

        // 14 years old -> eligible but minor
        $response = $this->postJson('/api/team/check-participant', [
            'age' => 14,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'eligible' => true,
                'is_minor' => true,
                'is_adult' => false,
            ]);

        // 20 years old -> eligible adult
        $response = $this->postJson('/api/team/check-participant', [
            'age' => 20,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'eligible' => true,
                'is_minor' => false,
                'is_adult' => true,
            ]);
    }

    /**
     * Test validation handles input errors.
     */
    public function test_handles_validation_errors()
    {
        // Missing 'ages' field
        $response = $this->postJson('/api/team/validate-ages', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ages']);
    }
}
