<?php

namespace Tests\Feature\Profile;

use App\Models\Member;
use App\Models\MedicalDoc;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Test suite for Licence and Role management
 * 
 * Tests cover:
 * - Adding a licence assigns adherent role
 * - Removing a licence removes adherent role
 * - Adherent role is not removed if user has dependent roles
 */
class LicenceRoleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Setup test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Clear Spatie Permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles
        Role::firstOrCreate(['name' => 'adherent', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'responsable-club', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    }

    /**
     * Test that adding a licence assigns adherent role
     */
    public function test_adding_licence_assigns_adherent_role(): void
    {
        $user = User::factory()->create([
            'adh_id' => null,
            'doc_id' => null,
        ]);
        $user->assignRole('user');

        $this->assertFalse($user->hasRole('adherent'));

        // Add licence via profile update
        $response = $this->actingAs($user)
            ->patch(route('profile.update'), [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'birth_date' => $user->birth_date?->format('Y-m-d') ?? '1990-01-01',
                'address' => $user->address ?? '123 Test Street',
                'phone' => $user->phone ?? '+33612345678',
                'license_number' => 'LICENCE-123456',
            ]);

        $response->assertRedirect(route('profile.edit'));

        // Refresh user to get updated roles
        $user->refresh();

        // Verify user now has adherent role
        $this->assertTrue($user->hasRole('adherent'));
        $this->assertNotNull($user->adh_id);
        $this->assertNotNull($user->member);
        $this->assertEquals('LICENCE-123456', $user->member->adh_license);
    }

    /**
     * Test that removing a licence removes adherent role
     */
    public function test_removing_licence_removes_adherent_role(): void
    {
        // Create user with existing licence
        $member = Member::factory()->create([
            'adh_license' => 'LICENCE-123456',
            'adh_end_validity' => now()->addYear(),
        ]);

        $user = User::factory()->create([
            'adh_id' => $member->adh_id,
            'doc_id' => null,
        ]);
        $user->assignRole('adherent');

        $this->assertTrue($user->hasRole('adherent'));
        $this->assertNotNull($user->adh_id);

        // Remove licence via profile update (empty string)
        $response = $this->actingAs($user)
            ->patch(route('profile.update'), [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'birth_date' => $user->birth_date?->format('Y-m-d') ?? '1990-01-01',
                'address' => $user->address ?? '123 Test Street',
                'phone' => $user->phone ?? '+33612345678',
                'license_number' => '',
            ]);

        $response->assertRedirect(route('profile.edit'));

        // Refresh user to get updated roles
        $user->refresh();

        // Verify adherent role was removed
        $this->assertFalse($user->hasRole('adherent'));
        $this->assertNull($user->adh_id);
    }

    /**
     * Test that removing licence doesn't remove adherent role if user has dependent roles
     */
    public function test_removing_licence_keeps_adherent_if_has_dependent_role(): void
    {
        // Create user with existing licence and responsable-club role
        $member = Member::factory()->create([
            'adh_license' => 'LICENCE-123456',
            'adh_end_validity' => now()->addYear(),
        ]);

        $user = User::factory()->create([
            'adh_id' => $member->adh_id,
            'doc_id' => null,
        ]);
        $user->syncRoles(['adherent', 'responsable-club']);

        $this->assertTrue($user->hasRole('adherent'));
        $this->assertTrue($user->hasRole('responsable-club'));

        // Remove licence via profile update
        $response = $this->actingAs($user)
            ->patch(route('profile.update'), [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'birth_date' => $user->birth_date?->format('Y-m-d') ?? '1990-01-01',
                'address' => $user->address ?? '123 Test Street',
                'phone' => $user->phone ?? '+33612345678',
                'license_number' => '',
            ]);

        $response->assertRedirect(route('profile.edit'));

        // Refresh user to get updated roles
        $user->refresh();

        // Verify adherent role is KEPT because user has responsable-club
        $this->assertTrue($user->hasRole('adherent'), 'Adherent role should be kept when user has responsable-club role');
        $this->assertTrue($user->hasRole('responsable-club'));
    }

    /**
     * Test that removing licence also clears adh_id
     */
    public function test_removing_licence_clears_adh_id(): void
    {
        $member = Member::factory()->create([
            'adh_license' => 'LICENCE-123456',
            'adh_end_validity' => now()->addYear(),
        ]);

        $user = User::factory()->create([
            'adh_id' => $member->adh_id,
        ]);
        $user->assignRole('adherent');

        $this->assertNotNull($user->adh_id);

        // Remove licence
        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'birth_date' => $user->birth_date?->format('Y-m-d') ?? '1990-01-01',
                'address' => $user->address ?? '123 Test Street',
                'phone' => $user->phone ?? '+33612345678',
                'license_number' => '',
            ]);

        $user->refresh();

        // Verify adh_id is null
        $this->assertNull($user->adh_id);
    }

    /**
     * Test that updating licence keeps adherent role
     */
    public function test_updating_licence_keeps_adherent_role(): void
    {
        $member = Member::factory()->create([
            'adh_license' => 'OLD-LICENCE',
            'adh_end_validity' => now()->addYear(),
        ]);

        $user = User::factory()->create([
            'adh_id' => $member->adh_id,
        ]);
        $user->assignRole('adherent');

        // Update licence to new number
        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'birth_date' => $user->birth_date?->format('Y-m-d') ?? '1990-01-01',
                'address' => $user->address ?? '123 Test Street',
                'phone' => $user->phone ?? '+33612345678',
                'license_number' => 'NEW-LICENCE',
            ]);

        $user->refresh();

        // Verify adherent role is still there
        $this->assertTrue($user->hasRole('adherent'));
        $this->assertEquals('NEW-LICENCE', $user->member->adh_license);
    }
}
