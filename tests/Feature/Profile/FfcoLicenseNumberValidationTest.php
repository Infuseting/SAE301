<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Feature tests for FFCO License Number validation.
 *
 * Tests cover the validation of license numbers on profile update
 * and profile completion endpoints.
 */
class FfcoLicenseNumberValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that valid license numbers are accepted on profile update.
     */
    #[DataProvider('validLicenseNumbersProvider')]
    public function test_profile_update_accepts_valid_license_numbers(string $licenseNumber): void
    {
        $user = User::factory()->create([
            'birth_date' => '1990-01-01',
            'address' => '123 Test Street',
            'phone' => '+33612345678',
        ]);

        $response = $this->actingAs($user)
            ->patch(route('profile.update'), [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'birth_date' => '1990-01-01',
                'address' => '123 Test Street',
                'phone' => '+33612345678',
                'license_number' => $licenseNumber,
            ]);

        $response->assertSessionHasNoErrors('license_number');
        $response->assertRedirect(route('profile.edit'));
    }

    /**
     * Test that invalid license numbers are rejected on profile update.
     */
    #[DataProvider('invalidLicenseNumbersProvider')]
    public function test_profile_update_rejects_invalid_license_numbers(string $licenseNumber): void
    {
        $user = User::factory()->create([
            'birth_date' => '1990-01-01',
            'address' => '123 Test Street',
            'phone' => '+33612345678',
        ]);

        $response = $this->actingAs($user)
            ->patch(route('profile.update'), [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'birth_date' => '1990-01-01',
                'address' => '123 Test Street',
                'phone' => '+33612345678',
                'license_number' => $licenseNumber,
            ]);

        $response->assertSessionHasErrors('license_number');
    }

    /**
     * Test that empty license number is accepted (optional field).
     */
    public function test_profile_update_accepts_empty_license_number(): void
    {
        $user = User::factory()->create([
            'birth_date' => '1990-01-01',
            'address' => '123 Test Street',
            'phone' => '+33612345678',
        ]);

        $response = $this->actingAs($user)
            ->patch(route('profile.update'), [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'birth_date' => '1990-01-01',
                'address' => '123 Test Street',
                'phone' => '+33612345678',
                'license_number' => '',
            ]);

        $response->assertSessionHasNoErrors('license_number');
        $response->assertRedirect(route('profile.edit'));
    }

    /**
     * Test that null license number is accepted (optional field).
     */
    public function test_profile_update_accepts_null_license_number(): void
    {
        $user = User::factory()->create([
            'birth_date' => '1990-01-01',
            'address' => '123 Test Street',
            'phone' => '+33612345678',
        ]);

        $response = $this->actingAs($user)
            ->patch(route('profile.update'), [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'birth_date' => '1990-01-01',
                'address' => '123 Test Street',
                'phone' => '+33612345678',
                // license_number not provided (null)
            ]);

        $response->assertSessionHasNoErrors('license_number');
        $response->assertRedirect(route('profile.edit'));
    }

    /**
     * Test that license numbers with spaces and dashes are accepted (they are stripped).
     */
    public function test_profile_update_accepts_license_with_spaces_and_dashes(): void
    {
        $user = User::factory()->create([
            'birth_date' => '1990-01-01',
            'address' => '123 Test Street',
            'phone' => '+33612345678',
        ]);

        // Test with dash
        $response = $this->actingAs($user)
            ->patch(route('profile.update'), [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'birth_date' => '1990-01-01',
                'address' => '123 Test Street',
                'phone' => '+33612345678',
                'license_number' => 'A-12345',
            ]);

        $response->assertSessionHasNoErrors('license_number');
        $response->assertRedirect(route('profile.edit'));

        // Test with space
        $response = $this->actingAs($user)
            ->patch(route('profile.update'), [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'birth_date' => '1990-01-01',
                'address' => '123 Test Street',
                'phone' => '+33612345678',
                'license_number' => 'AB 12345',
            ]);

        $response->assertSessionHasNoErrors('license_number');
        $response->assertRedirect(route('profile.edit'));
    }

    /**
     * Test profile completion with valid license number.
     */
    public function test_profile_completion_accepts_valid_license_number(): void
    {
        $user = User::factory()->create([
            'birth_date' => null,
            'address' => null,
            'phone' => null,
        ]);

        $response = $this->actingAs($user)
            ->post(route('profile.complete'), [
                'birth_date' => '1990-01-01',
                'address' => '123 Test Street',
                'phone' => '+33612345678',
                'license_number' => 'AB123456',
            ]);

        $response->assertSessionHasNoErrors('license_number');
    }

    /**
     * Test profile completion with invalid license number.
     */
    public function test_profile_completion_rejects_invalid_license_number(): void
    {
        $user = User::factory()->create([
            'birth_date' => null,
            'address' => null,
            'phone' => null,
        ]);

        $response = $this->actingAs($user)
            ->post(route('profile.complete'), [
                'birth_date' => '1990-01-01',
                'address' => '123 Test Street',
                'phone' => '+33612345678',
                'license_number' => 'INVALID-FORMAT',
            ]);

        $response->assertSessionHasErrors('license_number');
    }

    /**
     * Data provider for valid license numbers.
     *
     * @return array<int, array<int, string>>
     */
    public static function validLicenseNumbersProvider(): array
    {
        return [
            ['123456'],      // 6 digits
            ['12345'],       // 5 digits
            ['A12345'],      // 1 letter + 5 digits
            ['AB123456'],    // 2 letters + 6 digits
            ['ab123456'],    // lowercase letters (should be normalized)
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
            ['1234'],           // Too short
            ['1234567'],        // Too long
            ['ABC12345'],       // 3 letters (too many)
            ['INVALID'],        // No digits
            ['12AB34'],         // Digits then letters
        ];
    }
}
