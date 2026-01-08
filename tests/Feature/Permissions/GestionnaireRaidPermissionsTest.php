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
 * Test Gestionnaire Raid permissions
 * 
 * Gestionnaire Raid should be able to:
 * - All Adherent permissions (requires valid licence)
 * - Create raids
 * - Edit/delete own raids
 * - Create races within own raids
 * - NOT edit/delete other users' raids
 * - NOT create/manage clubs (unless also has responsable-club role)
 * - NOT access admin pages
 */
class GestionnaireRaidPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $gestionnaireRaid;
    protected Club $club;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Create a gestionnaire raid with valid licence
        $member = Member::factory()->create([
            'adh_license' => '123456',
            'adh_end_validity' => now()->addYear(),
            'adh_date_added' => now(),
        ]);
        
        $this->gestionnaireRaid = User::factory()->create([
            'adh_id' => $member->adh_id,
        ]);
        // Remove all roles (including unwanted admin role) before assigning the correct one
        $this->gestionnaireRaid->syncRoles([]);
        $this->gestionnaireRaid->assignRole('gestionnaire-raid');

        // Create a club for raids
        $this->club = Club::factory()->approved()->create();
    }

    /** @test */
    public function gestionnaire_raid_can_view_raids_create_page(): void
    {
        $response = $this->actingAs($this->gestionnaireRaid)->get(route('raids.create'));
        $response->assertStatus(200);
    }

    /** @test */
    public function gestionnaire_raid_can_create_raid(): void
    {
        $raidData = [
            'name' => 'Test Raid',
            'description' => 'Test Description',
            'club_id' => $this->club->id,
            'start_date' => now()->addMonth()->format('Y-m-d'),
            'end_date' => now()->addMonth()->addDays(2)->format('Y-m-d'),
            'city' => 'Paris',
            'department' => '75',
            'address' => '1 Rue de Test',
            'postal_code' => '75001',
        ];

        $response = $this->actingAs($this->gestionnaireRaid)->post(route('raids.store'), $raidData);

        $response->assertRedirect();
        $this->assertDatabaseHas('raids', [
            'raid_name' => 'Test Raid',
            'adh_id' => $this->gestionnaireRaid->adh_id,
        ]);
    }

    /** @test */
    public function gestionnaire_raid_can_edit_own_raid(): void
    {
        $raid = Raid::factory()->create([
            'clu_id' => $this->club->club_id,
        ]);

        $response = $this->actingAs($this->gestionnaireRaid)->get(route('raids.edit', $raid));
        $response->assertStatus(200);
    }

    /** @test */
    public function gestionnaire_raid_can_update_own_raid(): void
    {
        $raid = Raid::factory()->create([
            'clu_id' => $this->club->club_id,
        ]);

        $response = $this->actingAs($this->gestionnaireRaid)
            ->put(route('raids.update', $raid), [
                'name' => 'Updated Raid Name',
                'description' => $raid->description,
                'club_id' => $raid->club_id,
                'start_date' => $raid->start_date->format('Y-m-d'),
                'end_date' => $raid->end_date->format('Y-m-d'),
                'city' => $raid->city,
                'department' => $raid->department,
                'address' => $raid->address,
                'postal_code' => $raid->postal_code,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('raids', [
            'raid_id' => $raid->raid_id,
            'raid_name' => 'Updated Raid Name',
        ]);
    }

    /** @test */
    public function gestionnaire_raid_can_delete_own_raid(): void
    {
        $raid = Raid::factory()->create([
            'clu_id' => $this->club->club_id,
        ]);

        $response = $this->actingAs($this->gestionnaireRaid)->delete(route('raids.destroy', $raid));

        $response->assertRedirect();
        $this->assertSoftDeleted('raids', ['raid_id' => $raid->raid_id]);
    }

    /** @test */
    public function gestionnaire_raid_cannot_edit_other_users_raid(): void
    {
        $otherUser = User::factory()->create();
        $raid = Raid::factory()->create([
            'clu_id' => $this->club->club_id,
        ]);

        $response = $this->actingAs($this->gestionnaireRaid)->get(route('raids.edit', $raid));
        $response->assertStatus(403);
    }

    /** @test */
    public function gestionnaire_raid_cannot_update_other_users_raid(): void
    {
        $otherUser = User::factory()->create();
        $raid = Raid::factory()->create([
            'clu_id' => $this->club->club_id,
        ]);

        $response = $this->actingAs($this->gestionnaireRaid)
            ->put(route('raids.update', $raid), [
                'name' => 'Hacked Raid Name',
                'description' => $raid->description,
                'club_id' => $raid->club_id,
                'start_date' => $raid->start_date->format('Y-m-d'),
                'end_date' => $raid->end_date->format('Y-m-d'),
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function gestionnaire_raid_cannot_delete_other_users_raid(): void
    {
        $otherUser = User::factory()->create();
        $raid = Raid::factory()->create([
            'clu_id' => $this->club->club_id,
        ]);

        $response = $this->actingAs($this->gestionnaireRaid)->delete(route('raids.destroy', $raid));
        $response->assertStatus(403);
    }

    /** @test */
    public function gestionnaire_raid_can_view_races_create_page(): void
    {
        $response = $this->actingAs($this->gestionnaireRaid)->get(route('races.create'));
        $response->assertStatus(200);
    }

    /** @test */
    public function gestionnaire_raid_can_create_race(): void
    {
        $raid = Raid::factory()->create([
            'clu_id' => $this->club->club_id,
        ]);

        $raceData = [
            'name' => 'Test Race',
            'raid_id' => $raid->id,
            'start_date' => now()->addMonth()->format('Y-m-d H:i:s'),
            'max_participants' => 100,
        ];

        $response = $this->actingAs($this->gestionnaireRaid)->post(route('races.store'), $raceData);

        $response->assertRedirect();
        $this->assertDatabaseHas('races', [
            'race_name' => 'Test Race',
            'raid_id' => $raid->raid_id,
        ]);
    }

    /** @test */
    public function gestionnaire_raid_cannot_create_club(): void
    {
        $response = $this->actingAs($this->gestionnaireRaid)->get(route('clubs.create'));
        $response->assertStatus(403);
    }

    /** @test */
    public function gestionnaire_raid_cannot_store_club(): void
    {
        $clubData = [
            'name' => 'Test Club',
            'description' => 'Test Description',
            'city' => 'Paris',
            'department' => '75',
        ];

        $response = $this->actingAs($this->gestionnaireRaid)->post(route('clubs.store'), $clubData);
        $response->assertStatus(403);
    }

    /** @test */
    public function gestionnaire_raid_can_register_to_races(): void
    {
        $race = Race::factory()->create();

        $response = $this->actingAs($this->gestionnaireRaid)
            ->post(route('race.register', $race), [
                'runner_first_name' => 'Test',
                'runner_last_name' => 'Runner',
                'runner_birthdate' => '1990-01-01',
            ]);

        $response->assertRedirect();
    }

    /** @test */
    public function gestionnaire_raid_can_view_my_raids(): void
    {
        $response = $this->actingAs($this->gestionnaireRaid)->get(route('myraid.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function gestionnaire_raid_cannot_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->gestionnaireRaid)->get(route('admin.dashboard'));
        $response->assertStatus(403);
    }

    /** @test */
    public function gestionnaire_raid_cannot_access_admin_users(): void
    {
        $response = $this->actingAs($this->gestionnaireRaid)->get(route('admin.users.index'));
        $response->assertStatus(403);
    }

    /** @test */
    public function gestionnaire_raid_cannot_approve_clubs(): void
    {
        $club = Club::factory()->pending()->create();
        $response = $this->actingAs($this->gestionnaireRaid)->post(route('admin.clubs.approve', $club));
        $response->assertStatus(403);
    }

    /** @test */
    public function gestionnaire_raid_without_licence_cannot_create_raid(): void
    {
        // Remove licence
        $this->gestionnaireRaid->member()->delete();

        $raidData = [
            'name' => 'Test Raid',
            'description' => 'Test Description',
            'club_id' => $this->club->id,
            'start_date' => now()->addMonth()->format('Y-m-d'),
            'end_date' => now()->addMonth()->addDays(2)->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->gestionnaireRaid)
            ->post(route('raids.store'), $raidData);

        // Should be blocked by middleware
        $response->assertStatus(403);
    }

    /** @test */
    public function gestionnaire_raid_can_access_admin_raids_page(): void
    {
        // Gestionnaire raid should have access to /admin/raids to manage their raids
        $response = $this->actingAs($this->gestionnaireRaid)->get(route('admin.raids.index'));
        $response->assertStatus(200);
    }
}
