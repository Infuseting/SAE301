<?php

namespace Tests\Feature\Middleware;

use App\Models\User;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LicenceRequirementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles only if they don't exist
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'adherent', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'responsable-club', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'gestionnaire-raid', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'responsable-course', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'gestionnaire-equipe', 'guard_name' => 'web']);
    }

    /**
     * Test that requiresLicenceUpdate is false for regular users
     */
    public function test_regular_user_does_not_require_licence_update(): void
    {
        $user = User::factory()->create([
            'adh_id' => null,
        ]);
        $user->assignRole('user');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertSuccessful();
        $this->assertFalse($response->viewData('page')['props']['requiresLicenceUpdate']);
    }

    /**
     * Test that requiresLicenceUpdate is false for managers with valid licence
     */
    public function test_manager_with_valid_licence_does_not_require_update(): void
    {
        $member = Member::create([
            'adh_license' => 'VALID-LICENCE',
            'adh_end_validity' => now()->addYear(),
            'adh_date_added' => now(),
        ]);

        $user = User::factory()->create([
            'adh_id' => $member->adh_id,
        ]);
        $user->assignRole(['user', 'adherent', 'responsable-club']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertSuccessful();
        $this->assertFalse($response->viewData('page')['props']['requiresLicenceUpdate']);
    }

    /**
     * Test that requiresLicenceUpdate is true for responsable-club without licence
     * Page should load with modal showing
     */
    public function test_responsable_club_without_licence_requires_update(): void
    {
        $user = User::factory()->create([
            'adh_id' => null,
        ]);
        $user->assignRole(['user', 'responsable-club']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        // Page loads but requiresLicenceUpdate is true (modal will show)
        $response->assertSuccessful();
        $this->assertTrue($response->viewData('page')['props']['requiresLicenceUpdate']);
    }

    /**
     * Test that requiresLicenceUpdate is true for gestionnaire-raid without licence
     * Page should load with modal showing
     */
    public function test_gestionnaire_raid_without_licence_requires_update(): void
    {
        $user = User::factory()->create([
            'adh_id' => null,
        ]);
        $user->assignRole(['user', 'gestionnaire-raid']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        // Page loads but requiresLicenceUpdate is true (modal will show)
        $response->assertSuccessful();
        $this->assertTrue($response->viewData('page')['props']['requiresLicenceUpdate']);
    }

    /**
     * Test that requiresLicenceUpdate is true for responsable-course without licence
     * Page should load with modal showing
     */
    public function test_responsable_course_without_licence_requires_update(): void
    {
        $user = User::factory()->create([
            'adh_id' => null,
        ]);
        $user->assignRole(['user', 'responsable-course']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        // Page loads but requiresLicenceUpdate is true (modal will show)
        $response->assertSuccessful();
        $this->assertTrue($response->viewData('page')['props']['requiresLicenceUpdate']);
    }

    /**
     * Test that requiresLicenceUpdate is true for gestionnaire-equipe without licence
     * Page should load with modal showing
     */
    public function test_gestionnaire_equipe_without_licence_requires_update(): void
    {
        $user = User::factory()->create([
            'adh_id' => null,
        ]);
        $user->assignRole(['user', 'gestionnaire-equipe']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        // Page loads but requiresLicenceUpdate is true (modal will show)
        $response->assertSuccessful();
        $this->assertTrue($response->viewData('page')['props']['requiresLicenceUpdate']);
    }

    /**
     * Test that requiresLicenceUpdate is true for manager with expired licence
     * Page should load with modal showing
     */
    public function test_manager_with_expired_licence_requires_update(): void
    {
        $member = Member::create([
            'adh_license' => 'EXPIRED-LICENCE',
            'adh_end_validity' => now()->subDay(), // Expired yesterday
            'adh_date_added' => now()->subYear(),
        ]);

        $user = User::factory()->create([
            'adh_id' => $member->adh_id,
        ]);
        $user->assignRole(['user', 'adherent', 'responsable-club']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        // Page loads but requiresLicenceUpdate is true (modal will show)
        $response->assertSuccessful();
        $this->assertTrue($response->viewData('page')['props']['requiresLicenceUpdate']);
    }

    /**
     * Test that managers without licence can view pages but modal blocks actions
     */
    public function test_manager_can_view_pages_with_modal(): void
    {
        $user = User::factory()->create([
            'adh_id' => null,
        ]);
        $user->assignRole(['user', 'gestionnaire-raid']);

        // Can access dashboard (modal will show)
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertSuccessful();
        $this->assertTrue($response->viewData('page')['props']['requiresLicenceUpdate']);

        // Can access profile.edit
        $response = $this->actingAs($user)->get(route('profile.edit'));
        $response->assertSuccessful();
        $this->assertTrue($response->viewData('page')['props']['requiresLicenceUpdate']);
    }
    
    /**
     * Test that POST actions are blocked for managers without licence
     */
    public function test_manager_without_licence_cannot_perform_actions(): void
    {
        $user = User::factory()->create([
            'adh_id' => null,
        ]);
        $user->assignRole(['user', 'responsable-club']);

        // POST actions should be blocked with 403
        $response = $this->actingAs($user)
            ->withHeader('X-Inertia', 'true')
            ->post(route('clubs.store'), [
                'club_name' => 'Test Club',
                'club_street' => '123 Test Street',
                'club_city' => 'Test City',
                'club_postal_code' => '12345',
                'ffso_id' => 'ABC123',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test that adding licence removes the requirement
     */
    public function test_adding_licence_removes_requirement(): void
    {
        $user = User::factory()->create([
            'adh_id' => null,
            'birth_date' => '1990-01-01',
            'address' => '123 Test Street',
            'phone' => '+33612345678',
        ]);
        $user->assignRole(['user', 'responsable-club']);

        // First verify requiresLicenceUpdate is true
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertSuccessful();
        $this->assertTrue($response->viewData('page')['props']['requiresLicenceUpdate']);

        // Add licence via profile update
        $this->actingAs($user)->patch(route('profile.update'), [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'birth_date' => '1990-01-01',
            'address' => '123 Test Street',
            'phone' => '+33612345678',
            'license_number' => 'AB123456',
        ]);

        // Verify requirement is now false
        $user->refresh();
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertSuccessful();
        $this->assertFalse($response->viewData('page')['props']['requiresLicenceUpdate']);
    }
}
