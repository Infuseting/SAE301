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
 * Test Responsable Club permissions
 * 
 * Responsable Club should be able to:
 * - All Adherent permissions (requires valid licence)
 * - Create clubs
 * - Edit/delete own clubs
 * - Manage members of own clubs (approve, reject, remove, promote, demote)
 * - NOT edit/delete other users' clubs
 * - NOT create/edit/delete raids or races (unless also has those roles)
 * - NOT access admin pages
 */
class ResponsableClubPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $responsableClub;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Create a responsable club with valid licence
        $member = Member::factory()->create([
            'adh_license' => '123456',
            'adh_end_validity' => now()->addYear(),
            'adh_date_added' => now(),
        ]);
        
        $this->responsableClub = User::factory()->create([
            'adh_id' => $member->adh_id,
        ]);
        
        // Remove all roles (including unwanted admin role) before assigning the correct one
        $this->responsableClub->syncRoles([]);
        $this->responsableClub->assignRole('responsable-club');
    }

    /** @test */
    public function responsable_club_can_create_club(): void
    {
        $response = $this->actingAs($this->responsableClub)->get(route('clubs.create'));
        $response->assertStatus(200);
    }

    /** @test */
    public function responsable_club_can_store_club(): void
    {
        $clubData = [
            'name' => 'Test Club',
            'description' => 'Test Description',
            'city' => 'Paris',
            'department' => '75',
            'postal_code' => '75001',
            'address' => '1 Rue de Test',
        ];

        $response = $this->actingAs($this->responsableClub)->post(route('clubs.store'), $clubData);

        $response->assertRedirect();
        $this->assertDatabaseHas('clubs', [
            'club_name' => 'Test Club',
            'created_by' => $this->responsableClub->id,
        ]);
    }

    /** @test */
    public function responsable_club_can_edit_own_club(): void
    {
        $club = Club::factory()->approved()->create();

        $response = $this->actingAs($this->responsableClub)->get(route('clubs.edit', $club));
        $response->assertStatus(200);
    }

    /** @test */
    public function responsable_club_can_update_own_club(): void
    {
        $club = Club::factory()->approved()->create();

        $response = $this->actingAs($this->responsableClub)
            ->put(route('clubs.update', $club), [
                'name' => 'Updated Club Name',
                'description' => $club->description,
                'city' => $club->city,
                'department' => $club->department,
                'postal_code' => $club->postal_code,
                'address' => $club->address,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('clubs', [
            'club_id' => $club->club_id,
            'club_name' => 'Updated Club Name',
        ]);
    }

    /** @test */
    public function responsable_club_can_delete_own_club(): void
    {
        $club = Club::factory()->create();

        $response = $this->actingAs($this->responsableClub)->delete(route('clubs.destroy', $club));

        $response->assertRedirect();
        $this->assertSoftDeleted('clubs', ['club_id' => $club->club_id]);
    }

    /** @test */
    public function responsable_club_cannot_edit_other_users_club(): void
    {
        $otherUser = User::factory()->create();
        $club = Club::factory()->create();

        $response = $this->actingAs($this->responsableClub)->get(route('clubs.edit', $club));
        $response->assertStatus(403);
    }

    /** @test */
    public function responsable_club_cannot_update_other_users_club(): void
    {
        $otherUser = User::factory()->create();
        $club = Club::factory()->create();

        $response = $this->actingAs($this->responsableClub)
            ->put(route('clubs.update', $club), [
                'name' => 'Hacked Club Name',
                'description' => $club->description,
                'city' => $club->city,
                'department' => $club->department,
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function responsable_club_cannot_delete_other_users_club(): void
    {
        $otherUser = User::factory()->create();
        $club = Club::factory()->create();

        $response = $this->actingAs($this->responsableClub)->delete(route('clubs.destroy', $club));
        $response->assertStatus(403);
    }

    /** @test */
    public function responsable_club_can_approve_join_requests_for_own_club(): void
    {
        $club = Club::factory()->approved()->create();

        $pendingUser = User::factory()->create();
        $club->allMembers()->attach($pendingUser, ['status' => 'pending', 'role' => 'member']);

        $response = $this->actingAs($this->responsableClub)
            ->post(route('clubs.members.approve', [$club, $pendingUser]));

        $response->assertRedirect();
        $this->assertDatabaseHas('club_user', [
            'club_id' => $club->club_id,
            'user_id' => $pendingUser->id,
            'status' => 'approved',
        ]);
    }

    /** @test */
    public function responsable_club_can_reject_join_requests_for_own_club(): void
    {
        $club = Club::factory()->approved()->create();

        $pendingUser = User::factory()->create();
        $club->allMembers()->attach($pendingUser, ['status' => 'pending', 'role' => 'member']);

        $response = $this->actingAs($this->responsableClub)
            ->post(route('clubs.members.reject', [$club, $pendingUser]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('club_user', [
            'club_id' => $club->club_id,
            'user_id' => $pendingUser->id,
        ]);
    }

    /** @test */
    public function responsable_club_can_remove_members_from_own_club(): void
    {
        $club = Club::factory()->approved()->create();

        $member = User::factory()->create();
        $club->allMembers()->attach($member, ['status' => 'approved', 'role' => 'member']);

        $response = $this->actingAs($this->responsableClub)
            ->delete(route('clubs.members.remove', [$club, $member]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('club_user', [
            'club_id' => $club->club_id,
            'user_id' => $member->id,
        ]);
    }

    /** @test */
    public function responsable_club_can_promote_member_to_manager(): void
    {
        $club = Club::factory()->approved()->create();

        $member = User::factory()->create();
        $club->allMembers()->attach($member, ['status' => 'approved', 'role' => 'member']);

        $response = $this->actingAs($this->responsableClub)
            ->post(route('clubs.members.promote', [$club, $member]));

        $response->assertRedirect();
        $this->assertDatabaseHas('club_user', [
            'club_id' => $club->club_id,
            'user_id' => $member->id,
            'role' => 'manager',
        ]);
    }

    /** @test */
    public function responsable_club_can_demote_manager_to_member(): void
    {
        $club = Club::factory()->approved()->create();

        $manager = User::factory()->create();
        $club->allMembers()->attach($manager, ['status' => 'approved', 'role' => 'manager']);

        $response = $this->actingAs($this->responsableClub)
            ->post(route('clubs.members.demote', [$club, $manager]));

        $response->assertRedirect();
        $this->assertDatabaseHas('club_user', [
            'club_id' => $club->club_id,
            'user_id' => $manager->id,
            'role' => 'member',
        ]);
    }

    /** @test */
    public function responsable_club_cannot_manage_members_of_other_clubs(): void
    {
        $otherUser = User::factory()->create();
        $club = Club::factory()->create();

        $pendingUser = User::factory()->create();
        $club->allMembers()->attach($pendingUser, ['status' => 'pending', 'role' => 'member']);

        $response = $this->actingAs($this->responsableClub)
            ->post(route('clubs.members.approve', [$club, $pendingUser]));

        $response->assertStatus(403);
    }

    /** @test */
    public function responsable_club_cannot_create_raid(): void
    {
        $response = $this->actingAs($this->responsableClub)->get(route('raids.create'));
        $response->assertStatus(403);
    }

    /** @test */
    public function responsable_club_cannot_create_race(): void
    {
        $response = $this->actingAs($this->responsableClub)->get(route('races.create'));
        $response->assertStatus(403);
    }

    /** @test */
    public function responsable_club_can_register_to_races(): void
    {
        $race = Race::factory()->create();

        $response = $this->actingAs($this->responsableClub)
            ->post(route('race.register', $race), [
                'runner_first_name' => 'Test',
                'runner_last_name' => 'Runner',
                'runner_birthdate' => '1990-01-01',
            ]);

        $response->assertRedirect();
    }

    /** @test */
    public function responsable_club_cannot_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->responsableClub)->get(route('admin.dashboard'));
        $response->assertStatus(403);
    }

    /** @test */
    public function responsable_club_without_licence_cannot_create_club(): void
    {
        // Remove licence
        $this->responsableClub->member()->delete();

        $clubData = [
            'name' => 'Test Club',
            'description' => 'Test Description',
            'city' => 'Paris',
            'department' => '75',
        ];

        $response = $this->actingAs($this->responsableClub)
            ->post(route('clubs.store'), $clubData);

        // Should be blocked by middleware
        $response->assertStatus(403);
    }
}

