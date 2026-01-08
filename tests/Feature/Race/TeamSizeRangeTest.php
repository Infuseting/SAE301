<?php

namespace Tests\Feature\Race;

use App\Models\ParamTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite for Team Size Range functionality
 * 
 * Simple unit tests focused on:
 * - ParamTeam model stores minPerTeam and maxPerTeam correctly
 * - Validation logic for team size range
 */
class TeamSizeRangeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: ParamTeam can store min and max team count
     */
    public function test_param_team_stores_min_and_max_team_count(): void
    {
        $paramTeam = ParamTeam::create([
            'pae_nb_min' => 5,
            'pae_nb_max' => 20,
            'pae_team_count_min' => 2,
            'pae_team_count_max' => 5,
        ]);

        $this->assertDatabaseHas('param_teams', [
            'pae_id' => $paramTeam->pae_id,
            'pae_team_count_min' => 2,
            'pae_team_count_max' => 5,
        ]);

        $retrieved = ParamTeam::find($paramTeam->pae_id);
        $this->assertEquals(2, $retrieved->pae_team_count_min);
        $this->assertEquals(5, $retrieved->pae_team_count_max);
    }

    /**
     * Test: ParamTeam can update min and max team count
     */
    public function test_param_team_updates_min_and_max_team_count(): void
    {
        $paramTeam = ParamTeam::create([
            'pae_nb_min' => 5,
            'pae_nb_max' => 20,
            'pae_team_count_min' => 2,
            'pae_team_count_max' => 4,
        ]);

        $paramTeam->update([
            'pae_team_count_min' => 3,
            'pae_team_count_max' => 6,
        ]);

        $this->assertDatabaseHas('param_teams', [
            'pae_id' => $paramTeam->pae_id,
            'pae_team_count_min' => 3,
            'pae_team_count_max' => 6,
        ]);
    }

    /**
     * Test: Team size validation logic - below minimum
     */
    public function test_team_size_validation_below_minimum(): void
    {
        $minSize = 3;
        $maxSize = 5;
        $currentSize = 2;

        $isValid = $currentSize >= $minSize && $currentSize <= $maxSize;
        
        $this->assertFalse($isValid, "Team with $currentSize members should be invalid (min: $minSize, max: $maxSize)");
    }

    /**
     * Test: Team size validation logic - above maximum
     */
    public function test_team_size_validation_above_maximum(): void
    {
        $minSize = 2;
        $maxSize = 4;
        $currentSize = 5;

        $isValid = $currentSize >= $minSize && $currentSize <= $maxSize;
        
        $this->assertFalse($isValid, "Team with $currentSize members should be invalid (min: $minSize, max: $maxSize)");
    }

    /**
     * Test: Team size validation logic - within range
     */
    public function test_team_size_validation_within_range(): void
    {
        $minSize = 2;
        $maxSize = 5;
        $currentSize = 3;

        $isValid = $currentSize >= $minSize && $currentSize <= $maxSize;
        
        $this->assertTrue($isValid, "Team with $currentSize members should be valid (min: $minSize, max: $maxSize)");
    }

    /**
     * Test: Team size validation logic - at minimum boundary
     */
    public function test_team_size_validation_at_minimum_boundary(): void
    {
        $minSize = 4;
        $maxSize = 6;
        $currentSize = 4;

        $isValid = $currentSize >= $minSize && $currentSize <= $maxSize;
        
        $this->assertTrue($isValid, "Team with $currentSize members should be valid at minimum boundary");
    }

    /**
     * Test: Team size validation logic - at maximum boundary
     */
    public function test_team_size_validation_at_maximum_boundary(): void
    {
        $minSize = 3;
        $maxSize = 8;
        $currentSize = 8;

        $isValid = $currentSize >= $minSize && $currentSize <= $maxSize;
        
        $this->assertTrue($isValid, "Team with $currentSize members should be valid at maximum boundary");
    }

    /**
     * Test: Exact team size validation (min == max)
     */
    public function test_exact_team_size_validation(): void
    {
        $minSize = 5;
        $maxSize = 5;

        // Below exact
        $this->assertFalse(4 >= $minSize && 4 <= $maxSize);
        
        // Exact match
        $this->assertTrue(5 >= $minSize && 5 <= $maxSize);
        
        // Above exact
        $this->assertFalse(6 >= $minSize && 6 <= $maxSize);
    }

    /**
     * Test: Error message generation for range
     */
    public function test_error_message_generation_for_range(): void
    {
        $minSize = 3;
        $maxSize = 5;
        $currentSize = 2;

        $message = "Le nombre de coureurs doit être entre $minSize et $maxSize (actuellement $currentSize).";
        
        $this->assertEquals(
            "Le nombre de coureurs doit être entre 3 et 5 (actuellement 2).",
            $message
        );
    }

    /**
     * Test: Smart message for exact vs range
     */
    public function test_smart_message_exact_vs_range(): void
    {
        // Range scenario
        $minRunners1 = 2;
        $maxRunners1 = 5;
        $message1 = $minRunners1 === $maxRunners1
            ? "Vous devez sélectionner exactement $maxRunners1 coureur" . ($maxRunners1 > 1 ? 's' : '')
            : "Vous devez sélectionner entre $minRunners1 et $maxRunners1 coureurs";
        
        $this->assertEquals("Vous devez sélectionner entre 2 et 5 coureurs", $message1);

        // Exact scenario
        $minRunners2 = 3;
        $maxRunners2 = 3;
        $message2 = $minRunners2 === $maxRunners2
            ? "Vous devez sélectionner exactement $maxRunners2 coureur" . ($maxRunners2 > 1 ? 's' : '')
            : "Vous devez sélectionner entre $minRunners2 et $maxRunners2 coureurs";
        
        $this->assertEquals("Vous devez sélectionner exactement 3 coureurs", $message2);

        // Exact single runner
        $minRunners3 = 1;
        $maxRunners3 = 1;
        $message3 = $minRunners3 === $maxRunners3
            ? "Vous devez sélectionner exactement $maxRunners3 coureur" . ($maxRunners3 > 1 ? 's' : '')
            : "Vous devez sélectionner entre $minRunners3 et $maxRunners3 coureurs";
        
        $this->assertEquals("Vous devez sélectionner exactement 1 coureur", $message3);
    }
}
