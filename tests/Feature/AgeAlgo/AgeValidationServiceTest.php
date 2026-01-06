<?php

namespace Tests\Unit;

use App\Services\AgeValidationService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AgeValidationService.
 * 
 * Tests the age validation algorithm:
 * - All participants >= A (minimum age)
 * - If any participant < B, at least one must be >= C
 * - Default values: A=12, B=16, C=18
 */
class AgeValidationServiceTest extends TestCase
{
    /**
     * Test that invalid thresholds throw an exception.
     */
    public function test_invalid_thresholds_throw_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        // A > B is invalid
        new AgeValidationService(18, 16, 20);
    }

    /**
     * Test that B > C throws an exception.
     */
    public function test_b_greater_than_c_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        // B > C is invalid
        new AgeValidationService(12, 20, 18);
    }

    /**
     * Test valid threshold configuration.
     */
    public function test_valid_thresholds_are_accepted(): void
    {
        $service = new AgeValidationService(12, 16, 18);
        
        $thresholds = $service->getThresholds();
        
        $this->assertEquals(12, $thresholds['min']);
        $this->assertEquals(16, $thresholds['intermediate']);
        $this->assertEquals(18, $thresholds['adult']);
    }

    /**
     * Test that equal thresholds are valid (A = B = C).
     */
    public function test_equal_thresholds_are_valid(): void
    {
        $service = new AgeValidationService(18, 18, 18);
        
        $thresholds = $service->getThresholds();
        
        $this->assertEquals(18, $thresholds['min']);
        $this->assertEquals(18, $thresholds['intermediate']);
        $this->assertEquals(18, $thresholds['adult']);
    }

    /**
     * Test empty team is invalid.
     */
    public function test_empty_team_is_invalid(): void
    {
        $service = new AgeValidationService(12, 16, 18);
        
        $result = $service->validateTeam([]);
        
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    /**
     * Test participant under minimum age is invalid.
     */
    public function test_participant_under_minimum_age_is_invalid(): void
    {
        $service = new AgeValidationService(12, 16, 18);
        
        // 10 year old is under minimum (12)
        $result = $service->validateTeam([10, 20]);
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('at least 12', $result['errors'][0]);
    }

    /**
     * Test all adults team is valid.
     */
    public function test_all_adults_team_is_valid(): void
    {
        $service = new AgeValidationService(12, 16, 18);
        
        // All are 18+ (adults)
        $result = $service->validateTeam([20, 25, 30]);
        
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /**
     * Test all intermediate age team is valid.
     */
    public function test_all_intermediate_age_team_is_valid(): void
    {
        $service = new AgeValidationService(12, 16, 18);
        
        // All are 16-17 (intermediate, no minors)
        $result = $service->validateTeam([16, 17, 17]);
        
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /**
     * Test minor with adult is valid.
     */
    public function test_minor_with_adult_is_valid(): void
    {
        $service = new AgeValidationService(12, 16, 18);
        
        // 14 year old (minor) with 18 year old (adult) - valid
        $result = $service->validateTeam([14, 18]);
        
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /**
     * Test minor without adult is invalid.
     */
    public function test_minor_without_adult_is_invalid(): void
    {
        $service = new AgeValidationService(12, 16, 18);
        
        // 14 year old (minor) with 17 year old (not adult) - invalid
        $result = $service->validateTeam([14, 17]);
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('must have at least one participant aged 18', $result['errors'][0]);
    }

    /**
     * Test multiple minors with one adult is valid.
     */
    public function test_multiple_minors_with_one_adult_is_valid(): void
    {
        $service = new AgeValidationService(12, 16, 18);
        
        // Multiple minors (12, 13, 14, 15) with one adult (20)
        $result = $service->validateTeam([12, 13, 14, 15, 20]);
        
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /**
     * Test example from specification: A=12, B=16, C=18.
     */
    public function test_specification_example_valid_team(): void
    {
        $service = new AgeValidationService(12, 16, 18);
        
        // Valid: 14 (minor) + 20 (adult)
        $result = $service->validateTeam([14, 20]);
        
        $this->assertTrue($result['valid']);
    }

    /**
     * Test example from specification: invalid team.
     */
    public function test_specification_example_invalid_team(): void
    {
        $service = new AgeValidationService(12, 16, 18);
        
        // Invalid: 14 (minor) + 15 (minor) - no adult
        $result = $service->validateTeam([14, 15]);
        
        $this->assertFalse($result['valid']);
    }

    /**
     * Test isMinor method.
     */
    public function test_is_minor_method(): void
    {
        $service = new AgeValidationService(12, 16, 18);
        
        $this->assertTrue($service->isMinor(12));
        $this->assertTrue($service->isMinor(15));
        $this->assertFalse($service->isMinor(16));
        $this->assertFalse($service->isMinor(18));
    }

    /**
     * Test isAdult method.
     */
    public function test_is_adult_method(): void
    {
        $service = new AgeValidationService(12, 16, 18);
        
        $this->assertFalse($service->isAdult(12));
        $this->assertFalse($service->isAdult(17));
        $this->assertTrue($service->isAdult(18));
        $this->assertTrue($service->isAdult(25));
    }

    /**
     * Test isParticipantValid method.
     */
    public function test_is_participant_valid_method(): void
    {
        $service = new AgeValidationService(12, 16, 18);
        
        $this->assertFalse($service->isParticipantValid(11));
        $this->assertTrue($service->isParticipantValid(12));
        $this->assertTrue($service->isParticipantValid(25));
    }

    /**
     * Test validation details include minors and adults lists.
     */
    public function test_validation_details_include_participant_info(): void
    {
        $service = new AgeValidationService(12, 16, 18);
        
        $result = $service->validateTeam([14, 16, 20]);
        
        $this->assertTrue($result['valid']);
        $this->assertCount(1, $result['details']['minors']);
        $this->assertCount(1, $result['details']['adults']);
        $this->assertEquals(14, $result['details']['minors'][0]['age']);
        $this->assertEquals(20, $result['details']['adults'][0]['age']);
    }

    /**
     * Test custom thresholds work correctly.
     */
    public function test_custom_thresholds(): void
    {
        // Custom: A=10, B=14, C=21
        $service = new AgeValidationService(10, 14, 21);
        
        // 12 year old (minor) with 20 year old (not adult at 21 threshold)
        $result = $service->validateTeam([12, 20]);
        
        $this->assertFalse($result['valid']);
        
        // 12 year old (minor) with 21 year old (adult)
        $result = $service->validateTeam([12, 21]);
        
        $this->assertTrue($result['valid']);
    }

    /**
     * Test single adult participant is valid.
     */
    public function test_single_adult_is_valid(): void
    {
        $service = new AgeValidationService(12, 16, 18);
        
        $result = $service->validateTeam([25]);
        
        $this->assertTrue($result['valid']);
    }

    /**
     * Test single minor is invalid (needs adult).
     */
    public function test_single_minor_is_invalid(): void
    {
        $service = new AgeValidationService(12, 16, 18);
        
        $result = $service->validateTeam([14]);
        
        $this->assertFalse($result['valid']);
    }

    /**
     * Test single intermediate age participant is valid.
     */
    public function test_single_intermediate_age_is_valid(): void
    {
        $service = new AgeValidationService(12, 16, 18);
        
        // 16-17 is not a minor, so valid alone
        $result = $service->validateTeam([17]);
        
        $this->assertTrue($result['valid']);
    }

    /**
     * Test getRulesExplanation returns non-empty string.
     */
    public function test_get_rules_explanation(): void
    {
        $service = new AgeValidationService(12, 16, 18);
        
        $explanation = $service->getRulesExplanation();
        
        $this->assertIsString($explanation);
        $this->assertStringContainsString('12', $explanation);
        $this->assertStringContainsString('16', $explanation);
        $this->assertStringContainsString('18', $explanation);
    }
}
