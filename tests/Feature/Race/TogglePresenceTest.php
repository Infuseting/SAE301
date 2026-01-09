<?php

namespace Tests\Feature\Race;

use App\Models\Race;
use App\Models\Registration;
use App\Models\Team;
use App\Models\User;
use App\Models\Member;
use App\Models\Raid;
use App\Models\Club;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

/**
 * Test suite for toggling participant presence in races
 */
class TogglePresenceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Setup test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles if they don't exist
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'responsable-course']);
        Role::firstOrCreate(['name' => 'user']);
    }

    /**
     * Test that race manager can toggle participant presence
     */
    public function test_race_manager_can_toggle_presence(): void
    {
        // Create a user with race manager role
        $manager = User::factory()->create();
        $manager->assignRole('responsable-course');
        
        $member = Member::factory()->create([
            'adh_id' => $manager->adh_id,
        ]);

        // Create a club
        $club = Club::factory()->create([
            'created_by' => $manager->id,
        ]);

        // Create a raid
        $raid = Raid::factory()->create([
            'club_id' => $club->club_id,
        ]);

        // Create a race
        $race = Race::factory()->create([
            'raid_id' => $raid->raid_id,
        ]);

        // Create a team
        $team = Team::factory()->create();

        // Create a registration
        $registration = Registration::factory()->create([
            'race_id' => $race->race_id,
            'equ_id' => $team->equ_id,
            'is_present' => false,
        ]);

        // Toggle presence to true
        $response = $this->actingAs($manager)->postJson(
            route('races.toggle-presence', ['race' => $race->race_id]),
            ['reg_id' => $registration->reg_id]
        );

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'is_present' => true,
            ]);

        // Verify database
        $this->assertDatabaseHas('registration', [
            'reg_id' => $registration->reg_id,
            'is_present' => true,
        ]);

        // Toggle back to false
        $response = $this->actingAs($manager)->postJson(
            route('races.toggle-presence', ['race' => $race->race_id]),
            ['reg_id' => $registration->reg_id]
        );

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'is_present' => false,
            ]);

        // Verify database
        $this->assertDatabaseHas('registration', [
            'reg_id' => $registration->reg_id,
            'is_present' => false,
        ]);
    }

    /**
     * Test that non-manager cannot toggle presence
     */
    public function test_non_manager_cannot_toggle_presence(): void
    {
        // Create a regular user
        $user = User::factory()->create();
        $user->assignRole('user');

        // Create a race
        $race = Race::factory()->create();

        // Create a team
        $team = Team::factory()->create();

        // Create a registration
        $registration = Registration::factory()->create([
            'race_id' => $race->race_id,
            'equ_id' => $team->equ_id,
            'is_present' => false,
        ]);

        // Try to toggle presence
        $response = $this->actingAs($user)->postJson(
            route('races.toggle-presence', ['race' => $race->race_id]),
            ['reg_id' => $registration->reg_id]
        );

        $response->assertStatus(403);

        // Verify database hasn't changed
        $this->assertDatabaseHas('registration', [
            'reg_id' => $registration->reg_id,
            'is_present' => false,
        ]);
    }

    /**
     * Test that admin can toggle presence
     */
    public function test_admin_can_toggle_presence(): void
    {
        // Create an admin user
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Create a race
        $race = Race::factory()->create();

        // Create a team
        $team = Team::factory()->create();

        // Create a registration
        $registration = Registration::factory()->create([
            'race_id' => $race->race_id,
            'equ_id' => $team->equ_id,
            'is_present' => false,
        ]);

        // Toggle presence
        $response = $this->actingAs($admin)->postJson(
            route('races.toggle-presence', ['race' => $race->race_id]),
            ['reg_id' => $registration->reg_id]
        );

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'is_present' => true,
            ]);
    }

    /**
     * Test validation error for missing reg_id
     */
    public function test_validation_error_for_missing_reg_id(): void
    {
        // Create an admin user
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Create a race
        $race = Race::factory()->create();

        // Try to toggle presence without reg_id
        $response = $this->actingAs($admin)->postJson(
            route('races.toggle-presence', ['race' => $race->race_id]),
            []
        );

        $response->assertStatus(422);
    }

    /**
     * Test error for non-existent registration
     */
    public function test_error_for_non_existent_registration(): void
    {
        // Create an admin user
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Create a race
        $race = Race::factory()->create();

        // Try to toggle presence with non-existent registration (validation error)
        $response = $this->actingAs($admin)->postJson(
            route('races.toggle-presence', ['race' => $race->race_id]),
            ['reg_id' => 99999]
        );

        // Will get 422 because validation fails (reg_id doesn't exist in database)
        $response->assertStatus(422);
    }
}
