<?php

namespace Tests\Feature\Permissions;

use App\Models\Club;
use App\Models\Member;
use App\Models\Race;
use App\Models\Raid;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\RolesAndPermissionsSeeder;
use Tests\TestCase;

/**
 * Test Adherent (user with valid licence or PPS) permissions
 *
 * Adherent should be able to:
 * - All User permissions
 * - Register to races
 * - View registered races
 * - Cancel race registrations
 * - NOT create clubs, raids, or races (requires manager role)
 * - NOT access admin pages
 */
class AdherentPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adherent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        // Create an adherent with valid licence
        $member = Member::factory()->create([
            'adh_license' => '123456',
            'adh_end_validity' => now()->addYear(),
            'adh_date_added' => now(),
        ]);

        $this->adherent = User::factory()->create([
            'adh_id' => $member->adh_id,
        ]);
        // Remove all roles (including unwanted admin role) before assigning the correct one
        $this->adherent->syncRoles([]);
        $this->adherent->assignRole('adherent');
    }

    public function test_adherent_can_view_all_public_pages(): void
    {
        $club = Club::factory()->approved()->create();
        $raid = Raid::factory()->create();
        $race = Race::factory()->create();

        $this->actingAs($this->adherent)->get(route('home'))->assertStatus(200);
        $this->actingAs($this->adherent)->get(route('clubs.index'))->assertStatus(200);
        $this->actingAs($this->adherent)->get(route('clubs.show', $club))->assertStatus(200);
        $this->actingAs($this->adherent)->get(route('raids.index'))->assertStatus(200);
        $this->actingAs($this->adherent)->get(route('raids.show', $raid))->assertStatus(200);
        $this->actingAs($this->adherent)->get(route('races.index'))->assertStatus(200);
        $this->actingAs($this->adherent)->get(route('races.show', $race->race_id))->assertStatus(200);
        $this->actingAs($this->adherent)->get(route('leaderboard.index'))->assertStatus(200);
    }

    public function test_adherent_can_access_profile_pages(): void
    {
        $this->actingAs($this->adherent)->get(route('profile.edit'))->assertStatus(200);
        $this->actingAs($this->adherent)->get(route('profile.index'))->assertStatus(200);
    }

    public function test_adherent_can_update_profile(): void
    {
        $response = $this->actingAs($this->adherent)->patch(route('profile.update'), [
            'first_name' => 'Updated',
            'last_name' => 'Adherent',
            'email' => $this->adherent->email,
            'birth_date' => '1990-01-01',
            'address' => '123 Test Street',
            'phone' => '0612345678',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $this->adherent->id,
            'first_name' => 'Updated',
            'last_name' => 'Adherent',
        ]);
    }

    public function test_adherent_with_valid_licence_can_register_to_race(): void
    {
        $race = Race::factory()->create();

        $response = $this->actingAs($this->adherent)
            ->postJson(route('race.register', $race));

        // Should succeed because adherent has valid licence
        // The register endpoint returns JSON, not a redirect
        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    public function test_adherent_can_view_my_races(): void
    {
        $response = $this->actingAs($this->adherent)->get(route('myrace.index'));
        $response->assertStatus(200);
    }

    public function test_adherent_can_join_club(): void
    {
        $club = Club::factory()->approved()->create();

        $response = $this->actingAs($this->adherent)->post(route('clubs.join', $club));

        $response->assertRedirect();
        $this->assertDatabaseHas('club_user', [
            'club_id' => $club->club_id,
            'user_id' => $this->adherent->id,
            'status' => 'pending',
        ]);
    }

    public function test_adherent_can_leave_club(): void
    {
        $club = Club::factory()->approved()->create();
        $this->adherent->clubs()->attach($club, ['status' => 'approved', 'role' => 'member']);

        $response = $this->actingAs($this->adherent)->post(route('clubs.leave', $club));

        $response->assertRedirect();
        $this->assertDatabaseMissing('club_user', [
            'club_id' => $club->club_id,
            'user_id' => $this->adherent->id,
        ]);
    }

    public function test_adherent_can_access_club_creation_page(): void
    {
        $response = $this->actingAs($this->adherent)->get(route('clubs.create'));
        $response->assertStatus(200);
    }

    public function test_adherent_can_store_club_pending_approval(): void
    {
        $clubData = [
            'club_name' => 'Test Club',
            'club_street' => '1 Rue de Test',
            'club_city' => 'Test City',
            'club_postal_code' => '75001',
            'ffso_id' => 'FFCO-TEST',
        ];

        $response = $this->actingAs($this->adherent)->post(route('clubs.store'), $clubData);
        $response->assertRedirect();
        $this->assertDatabaseHas('clubs', [
            'club_name' => 'Test Club',
            'is_approved' => false,
        ]);
    }

    public function test_adherent_cannot_edit_club(): void
    {
        $club = Club::factory()->create();
        $response = $this->actingAs($this->adherent)->get(route('clubs.edit', $club));
        $response->assertStatus(403);
    }

    public function test_adherent_cannot_delete_club(): void
    {
        $club = Club::factory()->create();
        $response = $this->actingAs($this->adherent)->delete(route('clubs.destroy', $club));
        $response->assertStatus(403);
    }

    public function test_adherent_cannot_create_raid(): void
    {
        $response = $this->actingAs($this->adherent)->get(route('raids.create'));
        $response->assertStatus(403);
    }

    public function test_adherent_cannot_store_raid(): void
    {
        $club = Club::factory()->create();

        $raidData = [
            'name' => 'Test Raid',
            'description' => 'Test Description',
            'club_id' => $club->id,
            'start_date' => now()->addMonth()->format('Y-m-d'),
            'end_date' => now()->addMonth()->addDays(2)->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->adherent)->post(route('raids.store'), $raidData);
        $response->assertStatus(403);
    }

    public function test_adherent_cannot_edit_raid(): void
    {
        $raid = Raid::factory()->create();
        $response = $this->actingAs($this->adherent)->get(route('raids.edit', $raid));
        $response->assertStatus(403);
    }

    public function test_adherent_cannot_delete_raid(): void
    {
        $raid = Raid::factory()->create();
        $response = $this->actingAs($this->adherent)->delete(route('raids.destroy', $raid));
        $response->assertStatus(403);
    }

    public function test_adherent_cannot_create_race(): void
    {
        $response = $this->actingAs($this->adherent)->get(route('races.create'));
        $response->assertStatus(403);
    }

    public function test_adherent_cannot_store_race(): void
    {
        $raid = Raid::factory()->create();

        $raceData = [
            'name' => 'Test Race',
            'raid_id' => $raid->id,
            'start_date' => now()->addMonth()->format('Y-m-d H:i:s'),
        ];

        $response = $this->actingAs($this->adherent)->post(route('races.store'), $raceData);
        $response->assertStatus(403);
    }

    public function test_adherent_cannot_edit_race(): void
    {
        $race = Race::factory()->create();
        $response = $this->actingAs($this->adherent)->get(route('races.edit', $race));
        $response->assertStatus(403);
    }

    public function test_adherent_cannot_delete_race(): void
    {
        $race = Race::factory()->create();
        $response = $this->actingAs($this->adherent)->delete(route('races.destroy', $race));
        $response->assertStatus(403);
    }

    public function test_adherent_cannot_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->adherent)->get(route('admin.dashboard'));
        $response->assertStatus(403);
    }

    public function test_adherent_cannot_access_admin_users(): void
    {
        $response = $this->actingAs($this->adherent)->get(route('admin.users.index'));
        $response->assertStatus(403);
    }

    public function test_adherent_cannot_access_admin_logs(): void
    {
        $response = $this->actingAs($this->adherent)->get(route('admin.logs.index'));
        $response->assertStatus(403);
    }

    public function test_adherent_cannot_approve_clubs(): void
    {
        $club = Club::factory()->pending()->create();
        $response = $this->actingAs($this->adherent)->post(route('admin.clubs.approve', $club));
        $response->assertStatus(403);
    }

    public function test_adherent_cannot_manage_other_users(): void
    {
        $otherUser = User::factory()->create();
        $response = $this->actingAs($this->adherent)
            ->put(route('admin.users.update', $otherUser), [
                'first_name' => 'Hacked',
            ]);
        $response->assertStatus(403);
    }

    public function test_adherent_cannot_assign_roles(): void
    {
        $otherUser = User::factory()->create();
        $response = $this->actingAs($this->adherent)
            ->post(route('admin.users.assignRole', $otherUser), [
                'role' => 'admin',
            ]);
        $response->assertStatus(403);
    }
}
