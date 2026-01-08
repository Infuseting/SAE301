<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tests for adherent role assignment based on license number.
 */
class AdherentRoleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create adherent role if not exists
        Role::firstOrCreate(['name' => 'adherent', 'guard_name' => 'web']);
    }

    /**
     * Test that user gets adherent role when submitting valid license number.
     */
    public function test_user_gets_adherent_role_with_valid_license(): void
    {
        $user = User::factory()->create([
            'birth_date' => '1990-01-01',
            'address' => '123 Test Street',
            'phone' => '+33612345678',
        ]);

        $this->assertFalse($user->hasRole('adherent'));

        $response = $this->actingAs($user)
            ->patch(route('profile.update'), [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'birth_date' => '1990-01-01',
                'address' => '123 Test Street',
                'phone' => '+33612345678',
                'license_number' => 'AB12345',
            ]);

        $response->assertRedirect(route('profile.edit'));
        
        $user->refresh();
        $this->assertTrue($user->hasRole('adherent'));
        $this->assertNotNull($user->member);
        $this->assertEquals('AB12345', $user->member->adh_license);
    }

    /**
     * Test that user loses adherent role when clearing license number.
     */
    public function test_user_loses_adherent_role_when_clearing_license(): void
    {
        // Create user with valid license and adherent role
        $member = Member::create([
            'adh_license' => 'AB12345',
            'adh_end_validity' => now()->addYear(),
            'adh_date_added' => now(),
        ]);

        $user = User::factory()->create([
            'birth_date' => '1990-01-01',
            'address' => '123 Test Street',
            'phone' => '+33612345678',
            'adh_id' => $member->adh_id,
        ]);

        $user->assignRole('adherent');
        $this->assertTrue($user->hasRole('adherent'));

        // Clear license number
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

        $response->assertRedirect(route('profile.edit'));
        
        $user->refresh();
        $this->assertFalse($user->hasRole('adherent'));
        $this->assertNull($user->adh_id);
    }

    /**
     * Test that user doesn't get adherent role with invalid license number.
     */
    public function test_user_doesnt_get_adherent_role_with_invalid_license(): void
    {
        $user = User::factory()->create([
            'birth_date' => '1990-01-01',
            'address' => '123 Test Street',
            'phone' => '+33612345678',
            'adh_id' => null, // No member by default
            'doc_id' => null,
        ]);

        $this->assertFalse($user->hasRole('adherent'));

        $response = $this->actingAs($user)
            ->patch(route('profile.update'), [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'birth_date' => '1990-01-01',
                'address' => '123 Test Street',
                'phone' => '+33612345678',
                'license_number' => 'INVALID',
            ]);

        $response->assertSessionHasErrors('license_number');
        
        $user->refresh();
        $this->assertFalse($user->hasRole('adherent'));
    }

    /**
     * Test that user with responsable roles keeps adherent role even when license is cleared.
     */
    public function test_user_with_responsable_role_keeps_adherent_when_clearing_license(): void
    {
        Role::firstOrCreate(['name' => 'responsable-club', 'guard_name' => 'web']);

        // Create user with valid license and adherent role
        $member = Member::create([
            'adh_license' => 'AB12345',
            'adh_end_validity' => now()->addYear(),
            'adh_date_added' => now(),
        ]);

        $user = User::factory()->create([
            'birth_date' => '1990-01-01',
            'address' => '123 Test Street',
            'phone' => '+33612345678',
            'adh_id' => $member->adh_id,
        ]);

        $user->assignRole(['adherent', 'responsable-club']);
        $this->assertTrue($user->hasRole('adherent'));

        // Clear license number
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

        $response->assertRedirect(route('profile.edit'));
        
        $user->refresh();
        // Should keep adherent role because has responsable-club
        $this->assertTrue($user->hasRole('adherent'));
        $this->assertNull($user->adh_id);
    }

    /**
     * Test profile completion with valid license assigns adherent role.
     */
    public function test_profile_completion_with_valid_license_assigns_adherent_role(): void
    {
        $user = User::factory()->create([
            'birth_date' => null,
            'address' => null,
            'phone' => null,
        ]);

        $this->assertFalse($user->hasRole('adherent'));

        $response = $this->actingAs($user)
            ->post(route('profile.complete'), [
                'birth_date' => '1990-01-01',
                'address' => '123 Test Street',
                'phone' => '+33612345678',
                'license_number' => 'AB123456',
            ]);

        $response->assertRedirect(route('dashboard'));
        
        $user->refresh();
        $this->assertTrue($user->hasRole('adherent'));
        $this->assertNotNull($user->member);
        $this->assertEquals('AB123456', $user->member->adh_license);
    }

    /**
     * Test profile completion without license doesn't assign adherent role.
     */
    public function test_profile_completion_without_license_doesnt_assign_adherent_role(): void
    {
        $user = User::factory()->create([
            'birth_date' => null,
            'address' => null,
            'phone' => null,
            'adh_id' => null, // No member by default
            'doc_id' => null,
        ]);

        $this->assertFalse($user->hasRole('adherent'));

        $response = $this->actingAs($user)
            ->post(route('profile.complete'), [
                'birth_date' => '1990-01-01',
                'address' => '123 Test Street',
                'phone' => '+33612345678',
                'license_number' => '',
            ]);

        $response->assertRedirect(route('dashboard'));
        
        $user->refresh();
        $this->assertFalse($user->hasRole('adherent'));
        $this->assertNull($user->adh_id);
    }
}
