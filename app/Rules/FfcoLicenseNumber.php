<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validation rule for FFCO (Fédération Française de Course d'Orientation) license numbers.
 * 
 * Valid license number format:
 * - 6 digits (e.g., 123456)
 * - Or 7 characters: 1-2 letters followed by 5-6 digits (e.g., A12345, AB12345)
 * 
 * This rule is reusable and can be used in any form validation.
 * 
 * @example
 * // In a FormRequest:
 * 'license_number' => ['nullable', new FfcoLicenseNumber()]
 * 
 * // In controller validation:
 * $request->validate(['license_number' => [new FfcoLicenseNumber()]]);
 */
class FfcoLicenseNumber implements ValidationRule
{
    /**
     * Regular expression pattern for valid FFCO license numbers.
     * Matches:
     * - 6 digits only (e.g., 123456)
     * - 1-2 letters followed by 5-6 digits (e.g., A12345, AB123456)
     */
    public const PATTERN = '/^([A-Z]{1,2})?[0-9]{5,6}$/i';

    /**
     * Example of valid license number for placeholders.
     */
    public const EXAMPLE = '123456';

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Allow empty values (use 'required' rule if needed)
        if ($value === null || trim($value) === '') {
            return;
        }

        // Remove any spaces or dashes for validation
        $cleanedValue = preg_replace('/[\s\-]/', '', $value);

        if (!preg_match(self::PATTERN, $cleanedValue)) {
            $fail(__('validation.ffco_license_number', [
                'attribute' => $attribute,
                'example' => self::EXAMPLE,
            ]));
        }
    }

    /**
     * Static helper to validate a license number without using the rule class.
     * Useful for quick validation checks.
     *
     * @param string|null $licenseNumber The license number to validate
     * @return bool True if valid, false otherwise
     */
    public static function isValid(?string $licenseNumber): bool
    {
        if (empty($licenseNumber)) {
            return true; // Empty is valid (use required rule separately)
        }

        $cleanedValue = preg_replace('/[\s\-]/', '', $licenseNumber);
        return (bool) preg_match(self::PATTERN, $cleanedValue);
    }

    /**
     * Static helper to normalize a license number (remove spaces and dashes, uppercase letters).
     *
     * @param string|null $licenseNumber The license number to normalize
     * @return string|null The normalized license number or null if empty
     */
    public static function normalize(?string $licenseNumber): ?string
    {
        if (empty($licenseNumber)) {
            return null;
        }

        $cleaned = preg_replace('/[\s\-]/', '', $licenseNumber);
        return strtoupper($cleaned);
    }
}
