<?php

namespace Tests\Feature\Permissions;

use App\Models\Club;
use App\Models\Member;
use App\Models\Race;
use App\Models\Raid;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test User (authenticated without license) permissions
 *
 * User should be able to:
 * - View clubs, raids, races
 * - Create and edit profile
 * - View public profiles
 * - NOT register to races (requires license)
 * - NOT create clubs, raids, or races
 * - NOT access admin pages
 */
class UserPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Create a user without license (no adh_id, no doc_id)
        $this->user = User::factory()->create([
            'adh_id' => null,
            'doc_id' => null,
        ]);
        // Remove all roles (including unwanted admin role) before assigning the correct one
        $this->user->syncRoles([]);
        $this->user->assignRole('user');
    }

    public function test_user_can_view_home_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('home'));
        $response->assertStatus(200);
    }

    public function test_user_can_view_clubs_index(): void
    {
        $response = $this->actingAs($this->user)->get(route('clubs.index'));
        $response->assertStatus(200);
    }

    public function test_user_can_view_club_details(): void
    {
        $club = Club::factory()->approved()->create();
        $response = $this->actingAs($this->user)->get(route('clubs.show', $club));
        $response->assertStatus(200);
    }

    public function test_user_can_view_raids_index(): void
    {
        $response = $this->actingAs($this->user)->get(route('raids.index'));
        $response->assertStatus(200);
    }

    public function test_user_can_view_raid_details(): void
    {
        $raid = Raid::factory()->create();
        $response = $this->actingAs($this->user)->get(route('raids.show', $raid));
        $response->assertStatus(200);
    }

    public function test_user_can_view_races_index(): void
    {
        $response = $this->actingAs($this->user)->get(route('races.index'));
        $response->assertStatus(200);
    }

    public function test_user_can_view_race_details(): void
    {
        $race = Race::factory()->create();
        $response = $this->actingAs($this->user)->get(route('races.show', $race->race_id));
        $response->assertStatus(200);
    }

    public function test_user_can_view_leaderboard(): void
    {
        $response = $this->actingAs($this->user)->get(route('leaderboard.index'));
        $response->assertStatus(200);
    }

    public function test_user_can_access_profile(): void
    {
        $response = $this->actingAs($this->user)->get(route('profile.edit'));
        $response->assertStatus(200);
    }

    public function test_user_can_view_own_profile(): void
    {
        $response = $this->actingAs($this->user)->get(route('profile.index'));
        $response->assertStatus(200);
    }

    public function test_user_can_view_other_profiles(): void
    {
        $otherUser = User::factory()->create();
        $response = $this->actingAs($this->user)->get(route('profile.show', $otherUser));
        $response->assertStatus(200);
    }

    public function test_user_can_update_profile(): void
    {
        $response = $this->actingAs($this->user)->patch(route('profile.update'), [
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'email' => $this->user->email,
            'birth_date' => '1990-01-01',
            'address' => '123 Test Street',
            'phone' => '0612345678',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ]);
    }

    public function test_user_can_add_licence(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('licence.store'), [
            'licence_number' => '123456',
        ]);

        // Licence store returns JSON response
        $response->assertOk();
        $response->assertJson(['status' => 'success']);
    }

    public function test_user_can_add_pps_code(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('pps.store'), [
            'pps_code' => 'ABC123',
        ]);

        // PPS store returns JSON response
        $response->assertOk();
        $response->assertJson(['status' => 'success']);
    }

    public function test_user_cannot_create_club(): void
    {
        $response = $this->actingAs($this->user)->get(route('clubs.create'));
        $response->assertStatus(403);
    }

    public function user_cannot_store_club(): void
    {
        $clubData = [
            'name' => 'Test Club',
            'description' => 'Test Description',
            'city' => 'Test City',
            'department' => '75',
        ];

        $response = $this->actingAs($this->user)->post(route('clubs.store'), $clubData);
        $response->assertStatus(403);
    }

    public function test_user_cannot_edit_club(): void
    {
        $club = Club::factory()->create();
        $response = $this->actingAs($this->user)->get(route('clubs.edit', $club));
        $response->assertStatus(403);
    }

    public function test_user_cannot_delete_club(): void
    {
        $club = Club::factory()->create();
        $response = $this->actingAs($this->user)->delete(route('clubs.destroy', $club));
        $response->assertStatus(403);
    }

    public function test_user_cannot_create_raid(): void
    {
        $response = $this->actingAs($this->user)->get(route('raids.create'));
        $response->assertStatus(403);
    }

    public function test_user_cannot_create_race(): void
    {
        $response = $this->actingAs($this->user)->get(route('races.create'));
        $response->assertStatus(403);
    }

    public function test_user_without_licence_cannot_register_to_race(): void
    {
        $race = Race::factory()->create();

        $response = $this->actingAs($this->user)
            ->postJson(route('race.register', $race));

        // Should fail because user doesn't have valid license or PPS
        // The register endpoint returns 400 with needs_credentials flag
        $response->assertStatus(400);
        $response->assertJson([
            'status' => 'error',
            'errors' => [
                'needs_credentials' => true
            ]
        ]);
    }

    public function test_user_cannot_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.dashboard'));
        $response->assertStatus(403);
    }

    public function test_user_cannot_access_admin_users(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.users.index'));
        $response->assertStatus(403);
    }

    public function test_user_cannot_access_admin_logs(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.logs.index'));
        $response->assertStatus(403);
    }

    public function test_user_can_join_club(): void
    {
        $club = Club::factory()->approved()->create();

        $response = $this->actingAs($this->user)->post(route('clubs.join', $club));

        $response->assertRedirect();
        $this->assertDatabaseHas('club_user', [
            'club_id' => $club->club_id,
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);
    }

    public function test_user_can_leave_club(): void
    {
        $club = Club::factory()->approved()->create();
        $this->user->clubs()->attach($club, ['status' => 'approved', 'role' => 'member']);

        $response = $this->actingAs($this->user)->post(route('clubs.leave', $club));

        $response->assertRedirect();
        $this->assertDatabaseMissing('club_user', [
            'club_id' => $club->club_id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_user_cannot_approve_club_members(): void
    {
        $club = Club::factory()->approved()->create();
        $pendingUser = User::factory()->create();
        $club->members()->attach($pendingUser, ['status' => 'pending', 'role' => 'member']);

        $response = $this->actingAs($this->user)
            ->post(route('clubs.members.approve', [$club, $pendingUser]));

        $response->assertStatus(403);
    }

    public function test_user_cannot_remove_club_members(): void
    {
        $club = Club::factory()->approved()->create();
        $member = User::factory()->create();
        $club->members()->attach($member, ['status' => 'approved', 'role' => 'member']);

        $response = $this->actingAs($this->user)
            ->delete(route('clubs.members.remove', [$club, $member]));

        $response->assertStatus(403);
    }
}
