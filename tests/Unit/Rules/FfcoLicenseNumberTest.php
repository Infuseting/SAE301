<?php

namespace Tests\Unit\Rules;

use App\Rules\FfcoLicenseNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the FfcoLicenseNumber validation rule.
 *
 * Tests cover various formats of FFCO license numbers including:
 * - 5-6 digit numbers (e.g., 12345, 123456)
 * - 1-2 letters followed by 5-6 digits (e.g., A12345, AB123456)
 * - Case insensitivity
 * - Invalid formats
 */
class FfcoLicenseNumberTest extends TestCase
{
    /**
     * Test valid license number formats.
     */
    #[DataProvider('validLicenseNumbersProvider')]
    public function test_valid_license_numbers(string $licenseNumber): void
    {
        $this->assertTrue(
            FfcoLicenseNumber::isValid($licenseNumber),
            "License number '{$licenseNumber}' should be valid"
        );
    }

    /**
     * Test invalid license number formats.
     */
    #[DataProvider('invalidLicenseNumbersProvider')]
    public function test_invalid_license_numbers(string $licenseNumber): void
    {
        $this->assertFalse(
            FfcoLicenseNumber::isValid($licenseNumber),
            "License number '{$licenseNumber}' should be invalid"
        );
    }

    /**
     * Test that spaces and dashes are stripped and validation still works.
     */
    public function test_spaces_and_dashes_are_stripped(): void
    {
        // Valid after stripping
        $this->assertTrue(FfcoLicenseNumber::isValid('A-12345'));
        $this->assertTrue(FfcoLicenseNumber::isValid('AB 12345'));
        $this->assertTrue(FfcoLicenseNumber::isValid('123-456'));
        $this->assertTrue(FfcoLicenseNumber::isValid('12 34 56'));
        
        // Still invalid after stripping (contains underscore which isn't stripped)
        $this->assertFalse(FfcoLicenseNumber::isValid('AB_12345'));
    }

    /**
     * Test that empty values are considered valid (nullable rule).
     */
    public function test_empty_values_are_valid(): void
    {
        $this->assertTrue(FfcoLicenseNumber::isValid(''));
        $this->assertTrue(FfcoLicenseNumber::isValid(null));
    }

    /**
     * Test normalization of license numbers.
     */
    public function test_normalization(): void
    {
        // Lowercase to uppercase
        $this->assertEquals('AB12345', FfcoLicenseNumber::normalize('ab12345'));
        
        // Already uppercase
        $this->assertEquals('AB12345', FfcoLicenseNumber::normalize('AB12345'));
        
        // Digits only
        $this->assertEquals('123456', FfcoLicenseNumber::normalize('123456'));
        
        // With whitespace (should be trimmed)
        $this->assertEquals('AB12345', FfcoLicenseNumber::normalize('  ab12345  '));
    }

    /**
     * Data provider for valid license numbers.
     *
     * @return array<int, array<int, string>>
     */
    public static function validLicenseNumbersProvider(): array
    {
        return [
            // 5 digits only
            ['12345'],
            ['00000'],
            ['99999'],
            
            // 6 digits only
            ['123456'],
            ['000000'],
            ['999999'],
            
            // 1 letter + 5 digits
            ['A12345'],
            ['a12345'],
            ['Z00000'],
            
            // 1 letter + 6 digits
            ['A123456'],
            ['a123456'],
            ['Z000000'],
            
            // 2 letters + 5 digits
            ['AB12345'],
            ['ab12345'],
            ['ZZ00000'],
            
            // 2 letters + 6 digits
            ['AB123456'],
            ['ab123456'],
            ['ZZ000000'],
            
            // Mixed case
            ['Ab12345'],
            ['aB12345'],
        ];
    }

    /**
     * Data provider for invalid license numbers.
     *
     * @return array<int, array<int, string>>
     */
    public static function invalidLicenseNumbersProvider(): array
    {
        return [
            // Too short (less than 5 digits)
            ['1234'],
            ['A1234'],
            ['AB1234'],
            
            // Too long (more than 6 digits)
            ['1234567'],
            ['A1234567'],
            ['AB1234567'],
            
            // Too many letters (more than 2)
            ['ABC12345'],
            ['ABCD12345'],
            
            // Numbers before letters
            ['1A2345'],
            ['12AB345'],
            
            // Only letters
            ['ABCDEF'],
            
            // Mixed alphanumeric in wrong order
            ['12AB34'],
            ['1A2B3C'],
        ];
    }
}
