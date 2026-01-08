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
 * Test Admin permissions
 * 
 * Admin should be able to:
 * - Access ALL pages and resources
 * - Manage all clubs, raids, races (create, edit, delete)
 * - Approve/reject clubs
 * - Manage all users (view, edit, delete, toggle status)
 * - Assign/remove roles
 * - View activity logs
 * - Manage leaderboards
 * - Full access to admin dashboard and all admin pages
 */
class AdminPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Create an admin user
        $this->admin = User::factory()->create();
        // Remove all roles (including unwanted admin role) before assigning the correct one
        $this->admin->syncRoles([]);
        $this->admin->assignRole('admin');
    }

    /** @test */
    public function admin_can_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_access_admin_users_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_access_admin_clubs_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route(name: 'admin.clubs.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_access_admin_raids_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.raids.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_access_admin_races_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.races.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_access_logs_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.logs.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_access_leaderboard_management(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.leaderboard.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_view_pending_clubs(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.clubs.pending'));
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_approve_club(): void
    {
        $club = Club::factory()->create(['is_approved' => false]);

        $response = $this->actingAs($this->admin)->post(route('admin.clubs.approve', $club));

        $response->assertRedirect();
        $this->assertDatabaseHas('clubs', [
            'club_id' => $club->club_id,
            'is_approved' => true,
        ]);
    }

    /** @test */
    public function admin_can_reject_club(): void
    {
        $club = Club::factory()->create(['is_approved' => false]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.clubs.reject', $club), [
                'rejection_reason' => 'Does not meet requirements',
            ]);

        $response->assertRedirect();
        // Club should be deleted when rejected
        $this->assertDatabaseMissing('clubs', [
            'club_id' => $club->club_id,
        ]);
    }

    /** @test */
    public function admin_can_create_club(): void
    {
        $clubData = [
            'club_name' => 'Admin Test Club',
            'description' => 'Test Description',
            'club_city' => 'Paris',
            'club_postal_code' => '75001',
            'club_street' => '1 Rue de Test',
            'ffso_id' => 'FFSO123',
        ];

        $response = $this->actingAs($this->admin)->post(route('clubs.store'), $clubData);

        $response->assertRedirect();
        $this->assertDatabaseHas('clubs', [
            'club_name' => 'Admin Test Club',
        ]);
    }

    /** @test */
    public function admin_can_edit_any_club(): void
    {
        $otherUser = User::factory()->create();
        $club = Club::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('clubs.edit', $club));
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_update_any_club(): void
    {
        $otherUser = User::factory()->create();
        $club = Club::factory()->create();

        $response = $this->actingAs($this->admin)
            ->put(route('clubs.update', $club), [
                'club_name' => 'Admin Updated Club',
                'club_street' => $club->club_street,
                'club_city' => $club->club_city,
                'club_postal_code' => $club->club_postal_code,
                'ffso_id' => $club->ffso_id,
                'description' => $club->description,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('clubs', [
            'club_id' => $club->club_id,
            'club_name' => 'Admin Updated Club',
        ]);
    }

    /** @test */
    public function admin_can_delete_any_club(): void
    {
        $otherUser = User::factory()->create();
        $club = Club::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('clubs.destroy', $club));

        $response->assertRedirect();
        $this->assertDatabaseMissing('clubs', ['club_id' => $club->club_id]);
    }

    /** @test */
    public function admin_can_create_raid(): void
    {
        $club = Club::factory()->create();
        $member = \App\Models\Member::factory()->create();
        
        // Create a user and link member to club
        $user = \App\Models\User::factory()->create(['adh_id' => $member->adh_id]);
        \DB::table('club_user')->insert([
            'club_id' => $club->club_id,
            'user_id' => $user->id,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $raidData = [
            'raid_name' => 'Admin Test Raid',
            'raid_description' => 'Test Description',
            'clu_id' => $club->club_id,
            'raid_date_start' => now()->addMonth()->format('Y-m-d'),
            'raid_date_end' => now()->addMonth()->addDays(2)->format('Y-m-d'),
            'ins_start_date' => now()->addDays(5)->format('Y-m-d'),
            'ins_end_date' => now()->addDays(20)->format('Y-m-d'),
            'raid_city' => 'Paris',
            'raid_street' => '1 Rue de Test',
            'raid_postal_code' => '75001',
            'raid_contact' => 'contact@example.com',
            'adh_id' => $member->adh_id,
        ];

        $response = $this->actingAs($this->admin)->post(route('raids.store'), $raidData);

        $response->assertRedirect();
        $this->assertDatabaseHas('raids', [
            'raid_name' => 'Admin Test Raid',
        ]);
    }

    /** @test */
    public function admin_can_edit_any_raid(): void
    {
        $otherUser = User::factory()->create();
        $raid = Raid::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('raids.edit', $raid));
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_update_any_raid(): void
    {
        $otherUser = User::factory()->create();
        $raid = Raid::factory()->create();

        $response = $this->actingAs($this->admin)
            ->put(route('raids.update', $raid), [
                'raid_name' => 'Admin Updated Raid',
                'raid_description' => $raid->raid_description,
                'clu_id' => $raid->clu_id,
                'raid_date_start' => $raid->raid_date_start->format('Y-m-d'),
                'raid_date_end' => $raid->raid_date_end->format('Y-m-d'),
                'ins_start_date' => $raid->registrationPeriod->ins_start_date->format('Y-m-d'),
                'ins_end_date' => $raid->registrationPeriod->ins_end_date->format('Y-m-d'),
                'raid_city' => $raid->raid_city,
                'raid_street' => $raid->raid_street,
                'raid_postal_code' => $raid->raid_postal_code,
                'raid_contact' => $raid->raid_contact,
                'adh_id' => $raid->adh_id,
                'raid_number' => $raid->raid_number,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('raids', [
            'raid_id' => $raid->raid_id,
            'raid_name' => 'Admin Updated Raid',
        ]);
    }

    /** @test */
    public function admin_can_delete_any_raid(): void
    {
        $otherUser = User::factory()->create();
        $raid = Raid::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('raids.destroy', $raid));

        $response->assertRedirect();
        $this->assertDatabaseMissing('raids', ['raid_id' => $raid->raid_id]);
    }

    /** @test */
    public function admin_can_create_race(): void
    {
        $raid = Raid::factory()->create();
        $type = \App\Models\ParamType::first();
        $difficulty = \App\Models\ParamDifficulty::first();
        $user = \App\Models\User::factory()->create();

        $raceData = [
            'title' => 'Admin Test Race',
            'raid_id' => $raid->raid_id,
            'startDate' => $raid->raid_date_start->format('Y-m-d'),
            'startTime' => '09:00',
            'endDate' => $raid->raid_date_start->format('Y-m-d'),
            'endTime' => '12:00',
            'minParticipants' => 10,
            'maxParticipants' => 100,
            'maxPerTeam' => 5,
            'difficulty' => $difficulty->dif_id,
            'type' => $type->typ_id,
            'minTeams' => 2,
            'maxTeams' => 20,
            'priceMajor' => 25.00,
            'priceMinor' => 15.00,
            'responsableId' => $user->id,
        ];

        $response = $this->actingAs($this->admin)->post(route('races.store'), $raceData);

        $response->assertRedirect();
        $this->assertDatabaseHas('races', [
            'race_name' => 'Admin Test Race',
        ]);
    }

    /** @test */
    public function admin_can_edit_any_race(): void
    {
        $otherUser = User::factory()->create();
        $race = Race::factory()->create();


        $response = $this->actingAs($this->admin)->get(route('races.edit', $race));
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_update_any_race(): void
    {
        $otherUser = User::factory()->create();
        $race = Race::factory()->create();

        $response = $this->actingAs($this->admin)
            ->put(route('races.update', $race), [
                'title' => 'Admin Updated Race',
                'raid_id' => $race->raid_id,
                'startDate' => $race->race_date_start->format('Y-m-d'),
                'startTime' => $race->race_date_start->format('H:i'),
                'endDate' => $race->race_date_end->format('Y-m-d'),
                'endTime' => $race->race_date_end->format('H:i'),
                'minParticipants' => $race->race_min_participants,
                'maxParticipants' => $race->race_max_participants,
                'maxPerTeam' => $race->race_max_per_team,
                'difficulty' => $race->dif_id,
                'type' => $race->typ_id,
                'minTeams' => $race->race_min_teams,
                'maxTeams' => $race->race_max_teams,
                'priceMajor' => $race->race_price_adult,
                'priceMinor' => 0,
                'responsableId' => $race->res_adh_id,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('races', [
            'race_id' => $race->race_id,
            'race_name' => 'Admin Updated Race',
        ]);
    }

    /** @test */
    public function admin_can_delete_any_race(): void
    {
        $otherUser = User::factory()->create();
        $race = Race::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('races.destroy', $race));

        $response->assertRedirect();
        $this->assertDatabaseMissing('races', ['race_id' => $race->race_id]);
    }

    /** @test */
    public function admin_can_view_all_users(): void
    {
        User::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)->get(route('admin.users.index'));
        
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_update_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)
            ->put(route('admin.users.update', $user), [
                'first_name' => 'Admin Updated',
                'last_name' => 'User',
                'email' => $user->email,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Admin Updated',
        ]);
    }

    /** @test */
    public function admin_can_toggle_user_status(): void
    {
        $user = User::factory()->create(['active' => true]);

        $response = $this->actingAs($this->admin)->post(route('admin.users.toggle', $user));

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'active' => false,
        ]);
    }

    /** @test */
    public function admin_can_delete_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('admin.users.destroy', $user));

        $response->assertRedirect();
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    /** @test */
    public function admin_can_get_roles_list(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.roles.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_assign_role_to_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.users.assignRole', $user), [
                'role' => 'responsable-club',
            ]);

        $response->assertRedirect();
        $this->assertTrue($user->fresh()->hasRole('responsable-club'));
    }

    /** @test */
    public function admin_can_remove_role_from_user(): void
    {
        $user = User::factory()->create();
        $user->assignRole('responsable-club');

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.users.removeRole', $user), [
                'role' => 'responsable-club',
            ]);

        $response->assertRedirect();
        $this->assertFalse($user->fresh()->hasRole('responsable-club'));
    }

    /** @test */
    public function admin_can_register_to_races_without_licence(): void
    {
        // Admin should be able to do everything, even without licence
        $race = Race::factory()->create();

        $response = $this->actingAs($this->admin)
            ->post(route('race.register', $race), [
                'runner_first_name' => 'Admin',
                'runner_last_name' => 'Test',
                'runner_birthdate' => '1990-01-01',
            ]);

        $response->assertRedirect();
    }

    /** @test */
    public function admin_without_licence_can_still_create_resources(): void
    {
        // Admin should bypass licence requirements
        $clubData = [
            'name' => 'No Licence Club',
            'description' => 'Test',
            'city' => 'Paris',
            'department' => '75',
            'postal_code' => '75001',
            'address' => 'Test',
        ];

        $response = $this->actingAs($this->admin)->post(route('clubs.store'), $clubData);
        $response->assertRedirect();
        
        $this->assertDatabaseHas('clubs', ['club_name' => 'No Licence Club']);
    }
}
