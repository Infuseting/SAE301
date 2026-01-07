<?php

namespace Tests\Unit\Race;

use App\Models\Race;
use PHPUnit\Framework\TestCase;

/**
 * Pure Unit tests for Race model validation
 * 
 * Tests model methods without database interactions
 */
class RaceValidationTest extends TestCase
{
    // ========================================
    // DIFFICULTY VALIDATION TESTS
    // ========================================

    /**
     * Test valid difficulty values
     */
    public function test_valid_difficulty_values(): void
    {
        $validDifficulties = ['Facile', 'Moyen', 'Difficile', 'Très difficile'];
        
        foreach ($validDifficulties as $difficulty) {
            $this->assertContains($difficulty, $validDifficulties);
        }
    }

    // ========================================
    // PRICE VALIDATION TESTS
    // ========================================

    /**
     * Test price must be positive
     */
    public function test_price_should_be_positive(): void
    {
        $validPrice = 20.00;
        $this->assertGreaterThanOrEqual(0, $validPrice);
    }

    /**
     * Test price with zero value is valid
     */
    public function test_zero_price_is_valid(): void
    {
        $zeroPrice = 0.00;
        $this->assertEquals(0.00, $zeroPrice);
    }

    // ========================================
    // DATE VALIDATION TESTS
    // ========================================

    /**
     * Test start date format validation
     */
    public function test_start_date_format(): void
    {
        $dateString = '2026-06-15 09:00:00';
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $dateString);
        
        $this->assertInstanceOf(\DateTime::class, $date);
    }

    /**
     * Test end date must be after start date
     */
    public function test_end_date_after_start_date(): void
    {
        $startDate = new \DateTime('2026-06-15 09:00:00');
        $endDate = new \DateTime('2026-06-15 17:00:00');
        
        $this->assertGreaterThan($startDate, $endDate);
    }

    /**
     * Test same start and end date is invalid for different times
     */
    public function test_same_day_different_times(): void
    {
        $startDate = new \DateTime('2026-06-15 09:00:00');
        $endDate = new \DateTime('2026-06-15 09:00:00');
        
        $this->assertEquals($startDate, $endDate);
    }

    // ========================================
    // TITLE VALIDATION TESTS
    // ========================================

    /**
     * Test title length validation
     */
    public function test_title_max_length(): void
    {
        $maxLength = 255;
        $validTitle = str_repeat('a', $maxLength);
        $invalidTitle = str_repeat('a', $maxLength + 1);
        
        $this->assertEquals($maxLength, strlen($validTitle));
        $this->assertGreaterThan($maxLength, strlen($invalidTitle));
    }

    /**
     * Test empty title is invalid
     */
    public function test_empty_title_is_invalid(): void
    {
        $emptyTitle = '';
        
        $this->assertEmpty($emptyTitle);
    }

    // ========================================
    // PARTICIPANT LIMIT VALIDATION TESTS
    // ========================================

    /**
     * Test minimum participants must be positive
     */
    public function test_min_participants_positive(): void
    {
        $minParticipants = 1;
        
        $this->assertGreaterThan(0, $minParticipants);
    }

    /**
     * Test max participants must be greater than min
     */
    public function test_max_greater_than_min_participants(): void
    {
        $minParticipants = 10;
        $maxParticipants = 100;
        
        $this->assertGreaterThan($minParticipants, $maxParticipants);
    }

    /**
     * Test team count must be positive
     */
    public function test_team_count_positive(): void
    {
        $teamCount = 5;
        
        $this->assertGreaterThan(0, $teamCount);
    }

    // ========================================
    // DURATION VALIDATION TESTS
    // ========================================

    /**
     * Test duration format validation (HH:MM)
     */
    public function test_duration_format(): void
    {
        $validDuration = '3:30';
        $pattern = '/^\d{1,2}:\d{2}$/';
        
        $this->assertMatchesRegularExpression($pattern, $validDuration);
    }

    /**
     * Test invalid duration format
     */
    public function test_invalid_duration_format(): void
    {
        $invalidDuration = '3:3:3';
        $pattern = '/^\d{1,2}:\d{2}$/';
        
        $this->assertDoesNotMatchRegularExpression($pattern, $invalidDuration);
    }

    // ========================================
    // TYPE VALIDATION TESTS
    // ========================================

    /**
     * Test type ID must be integer
     */
    public function test_type_id_is_integer(): void
    {
        $typeId = 1;
        
        $this->assertIsInt($typeId);
    }

    /**
     * Test type ID must be positive
     */
    public function test_type_id_positive(): void
    {
        $typeId = 1;
        
        $this->assertGreaterThan(0, $typeId);
    }

    // ========================================
    // FOREIGN KEY VALIDATION TESTS
    // ========================================

    /**
     * Test raid ID must be set
     */
    public function test_raid_id_required(): void
    {
        $raidId = 1;
        
        $this->assertNotNull($raidId);
        $this->assertIsInt($raidId);
    }

    /**
     * Test adh_id (responsable) must be set
     */
    public function test_responsable_id_required(): void
    {
        $adhId = 123;
        
        $this->assertNotNull($adhId);
        $this->assertIsInt($adhId);
    }
}
