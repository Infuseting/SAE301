<?php

namespace Tests\Feature\Permissions;

use App\Models\Club;
use App\Models\Race;
use App\Models\Raid;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test Guest (non-authenticated user) permissions
 * 
 * Guest should be able to:
 * - View clubs, raids, races (public pages)
 * - NOT create, edit, or delete anything
 * - NOT register to races
 * - NOT access admin pages
 */
class GuestPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    /** @test */
    public function guest_can_view_home_page(): void
    {
        $response = $this->get(route('home'));
        $response->assertStatus(200);
    }

    /** @test */
    public function guest_can_view_clubs_index(): void
    {
        $response = $this->get(route('clubs.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function guest_can_view_club_details(): void
    {
        $club = Club::factory()->approved()->create();
        $response = $this->get(route('clubs.show', $club));
        $response->assertStatus(200);
    }

    /** @test */
    public function guest_can_view_raids_index(): void
    {
        $response = $this->get(route('raids.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function guest_can_view_raid_details(): void
    {
        $raid = Raid::factory()->create();
        $response = $this->get(route('raids.show', $raid));
        $response->assertStatus(200);
    }

    /** @test */
    public function guest_can_view_races_index(): void
    {
        $response = $this->get(route('races.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function guest_can_view_race_details(): void
    {
        $race = Race::factory()->create();
        $response = $this->get(route('races.show', $race->race_id));
        $response->assertStatus(200);
    }

    /** @test */
    public function guest_can_view_leaderboard(): void
    {
        $response = $this->get(route('leaderboard.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function guest_can_view_map(): void
    {
        $response = $this->get(route('map.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function guest_cannot_create_club(): void
    {
        $response = $this->get(route('clubs.create'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function guest_cannot_store_club(): void
    {
        $clubData = [
            'name' => 'Test Club',
            'description' => 'Test Description',
            'city' => 'Test City',
            'department' => '75',
        ];

        $response = $this->post(route('clubs.store'), $clubData);
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function guest_cannot_edit_club(): void
    {
        $club = Club::factory()->create();
        $response = $this->get(route('clubs.edit', $club));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function guest_cannot_delete_club(): void
    {
        $club = Club::factory()->create();
        $response = $this->delete(route('clubs.destroy', $club));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function guest_cannot_create_raid(): void
    {
        $response = $this->get(route('raids.create'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function guest_cannot_create_race(): void
    {
        $response = $this->get(route('races.create'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function guest_cannot_register_to_race(): void
    {
        $race = Race::factory()->create();
        $response = $this->post(route('race.register', $race));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function guest_cannot_access_profile(): void
    {
        $response = $this->get(route('profile.edit'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function guest_cannot_access_admin_dashboard(): void
    {
        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function guest_cannot_access_my_race(): void
    {
        $response = $this->get(route('myrace.index'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function guest_cannot_access_my_raid(): void
    {
        $response = $this->get(route('myraid.index'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function guest_cannot_join_club(): void
    {
        $club = Club::factory()->create();
        $response = $this->post(route('clubs.join', $club));
        $response->assertRedirect(route('login'));
    }
}
