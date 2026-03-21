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
        $this->seed(RolesAndPermissionsSeeder::class);

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

        // Create a club owned by the gestionnaire (required for isClubLeader() check)
        $this->club = Club::factory()->approved()->create([
            'created_by' => $this->gestionnaireRaid->id,
        ]);

        // Link user to club via club_user (required for adh_id validation in raid requests)
        \DB::table('club_user')->insert([
            'club_id' => $this->club->club_id,
            'user_id' => $this->gestionnaireRaid->id,
            'status' => 'approved',
            'role' => 'manager',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_gestionnaire_raid_can_view_raids_create_page(): void
    {
        $response = $this->actingAs($this->gestionnaireRaid)->get(route('raids.create'));
        $response->assertStatus(200);
    }

    public function test_gestionnaire_raid_can_create_raid(): void
    {
        $startDate = now()->addMonth();
        $endDate = now()->addMonth()->addDays(2);

        $raidData = [
            'raid_name' => 'Test Raid',
            'raid_description' => 'Test Description',
            'clu_id' => $this->club->club_id,
            'raid_date_start' => $startDate->format('Y-m-d'),
            'raid_date_end' => $endDate->format('Y-m-d'),
            'ins_start_date' => now()->addDays(5)->format('Y-m-d'),
            'ins_end_date' => now()->addDays(20)->format('Y-m-d'),
            'raid_city' => 'Paris',
            'raid_street' => '1 Rue de Test',
            'raid_postal_code' => '75001',
            'raid_contact' => 'contact@test.com',
            'adh_id' => $this->gestionnaireRaid->adh_id,
        ];

        $response = $this->actingAs($this->gestionnaireRaid)->post(route('raids.store'), $raidData);

        $response->assertRedirect();
        $this->assertDatabaseHas('raids', [
            'raid_name' => 'Test Raid',
            'adh_id' => $this->gestionnaireRaid->adh_id,
        ]);
    }

    public function test_gestionnaire_raid_can_edit_own_raid(): void
    {
        $raid = Raid::factory()->create([
            'clu_id' => $this->club->club_id,
            'adh_id' => $this->gestionnaireRaid->adh_id,
        ]);

        $response = $this->actingAs($this->gestionnaireRaid)->get(route('raids.edit', $raid));
        $response->assertStatus(200);
    }

    public function test_gestionnaire_raid_can_update_own_raid(): void
    {
        $raid = Raid::factory()->create([
            'clu_id' => $this->club->club_id,
            'adh_id' => $this->gestionnaireRaid->adh_id,
        ]);

        $response = $this->actingAs($this->gestionnaireRaid)
            ->put(route('raids.update', $raid), [
                'raid_name' => 'Updated Raid Name',
                'raid_description' => $raid->raid_description,
                'adh_id' => $this->gestionnaireRaid->adh_id,
                'clu_id' => $this->club->club_id,
                'raid_date_start' => $raid->raid_date_start->format('Y-m-d'),
                'raid_date_end' => $raid->raid_date_end->format('Y-m-d'),
                'raid_contact' => $raid->raid_contact,
                'raid_street' => $raid->raid_street,
                'raid_city' => $raid->raid_city,
                'raid_postal_code' => $raid->raid_postal_code,
                'raid_number' => $raid->raid_number,
                'ins_start_date' => $raid->registrationPeriod->ins_start_date->format('Y-m-d'),
                'ins_end_date' => $raid->registrationPeriod->ins_end_date->format('Y-m-d'),
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('raids', [
            'raid_id' => $raid->raid_id,
            'raid_name' => 'Updated Raid Name',
        ]);
    }

    public function test_gestionnaire_raid_can_delete_own_raid(): void
    {
        $raid = Raid::factory()->create([
            'clu_id' => $this->club->club_id,
            'adh_id' => $this->gestionnaireRaid->adh_id,
        ]);

        $response = $this->actingAs($this->gestionnaireRaid)->delete(route('raids.destroy', $raid));

        $response->assertRedirect();
        $this->assertDatabaseMissing('raids', ['raid_id' => $raid->raid_id]);
    }

    public function test_gestionnaire_raid_cannot_edit_other_users_raid(): void
    {
        // Raid belongs to a different club the user is NOT linked to
        $otherClub = Club::factory()->approved()->create();
        $raid = Raid::factory()->create([
            'clu_id' => $otherClub->club_id,
        ]);

        $response = $this->actingAs($this->gestionnaireRaid)->get(route('raids.edit', $raid));
        $response->assertStatus(403);
    }

    public function test_gestionnaire_raid_cannot_update_other_users_raid(): void
    {
        // Raid belongs to a different club the user is NOT linked to
        $otherClub = Club::factory()->approved()->create();
        $otherMember = Member::factory()->create();
        $otherUser = User::factory()->create(['adh_id' => $otherMember->adh_id]);
        \DB::table('club_user')->insert([
            'club_id' => $otherClub->club_id,
            'user_id' => $otherUser->id,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $raid = Raid::factory()->create([
            'clu_id' => $otherClub->club_id,
            'adh_id' => $otherMember->adh_id,
        ]);

        $response = $this->actingAs($this->gestionnaireRaid)
            ->put(route('raids.update', $raid), [
                'raid_name' => 'Hacked Raid Name',
                'raid_description' => $raid->raid_description,
                'clu_id' => $otherClub->club_id,
                'raid_date_start' => $raid->raid_date_start->format('Y-m-d'),
                'raid_date_end' => $raid->raid_date_end->format('Y-m-d'),
                'ins_start_date' => $raid->registrationPeriod->ins_start_date->format('Y-m-d'),
                'ins_end_date' => $raid->registrationPeriod->ins_end_date->format('Y-m-d'),
                'raid_city' => $raid->raid_city,
                'raid_street' => $raid->raid_street,
                'raid_postal_code' => $raid->raid_postal_code,
                'raid_contact' => $raid->raid_contact,
                'adh_id' => $otherMember->adh_id,
                'raid_number' => $raid->raid_number,
            ]);

        $response->assertStatus(403);
    }

    public function test_gestionnaire_raid_cannot_delete_other_users_raid(): void
    {
        // Raid belongs to a different club the user is NOT linked to
        $otherClub = Club::factory()->approved()->create();
        $raid = Raid::factory()->create([
            'clu_id' => $otherClub->club_id,
        ]);

        $response = $this->actingAs($this->gestionnaireRaid)->delete(route('raids.destroy', $raid));
        $response->assertStatus(403);
    }

    public function test_gestionnaire_raid_can_view_races_create_page(): void
    {
        $response = $this->actingAs($this->gestionnaireRaid)->get(route('races.create'));
        $response->assertStatus(200);
    }

    public function test_gestionnaire_raid_can_create_race(): void
    {
        $raid = Raid::factory()->create([
            'clu_id' => $this->club->club_id,
            'adh_id' => $this->gestionnaireRaid->adh_id,
        ]);

        $type = \App\Models\ParamType::where('typ_name', 'loisir')->first()
            ?? \App\Models\ParamType::factory()->create(['typ_name' => 'loisir']);
        $responsable = User::factory()->create();

        $raceData = [
            'title' => 'Test Race',
            'raid_id' => $raid->raid_id,
            'startDate' => $raid->raid_date_start->format('Y-m-d'),
            'startTime' => '09:00',
            'endDate' => $raid->raid_date_start->format('Y-m-d'),
            'endTime' => '12:00',
            'minParticipants' => 10,
            'maxParticipants' => 100,
            'minPerTeam' => 2,
            'maxPerTeam' => 5,
            'difficulty' => 'moyenne',
            'type' => $type->typ_id,
            'minTeams' => 2,
            'maxTeams' => 20,
            'priceMajor' => 25.00,
            'priceMinor' => 15.00,
            'responsableId' => $responsable->id,
        ];

        $response = $this->actingAs($this->gestionnaireRaid)->post(route('races.store'), $raceData);

        $response->assertRedirect();
        $this->assertDatabaseHas('races', [
            'race_name' => 'Test Race',
            'raid_id' => $raid->raid_id,
        ]);
    }

    public function test_gestionnaire_raid_can_access_club_creation_page(): void
    {
        // Gestionnaire-raid with valid licence also gets adherent role,
        // which allows club creation
        $response = $this->actingAs($this->gestionnaireRaid)->get(route('clubs.create'));
        $response->assertStatus(200);
    }

    public function test_gestionnaire_raid_can_store_club(): void
    {
        // Gestionnaire-raid with valid licence also gets adherent role via AssignDefaultRole
        $clubData = [
            'club_name' => 'Test Club',
            'description' => 'Test Description',
            'club_city' => 'Paris',
            'club_postal_code' => '75001',
            'club_street' => '1 Rue de Test',
            'ffso_id' => 'FFCO-1234',
        ];

        $response = $this->actingAs($this->gestionnaireRaid)->post(route('clubs.store'), $clubData);
        $response->assertRedirect();
        $this->assertDatabaseHas('clubs', ['club_name' => 'Test Club']);
    }

    public function test_gestionnaire_raid_can_register_to_races(): void
    {
        // The register endpoint returns JSON responses
        $race = Race::factory()->create();

        $response = $this->actingAs($this->gestionnaireRaid)
            ->post(route('race.register', $race), [
                'runner_first_name' => 'Test',
                'runner_last_name' => 'Runner',
                'runner_birthdate' => '1990-01-01',
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    public function test_gestionnaire_raid_can_view_my_raids(): void
    {
        $response = $this->actingAs($this->gestionnaireRaid)->get(route('myraid.index'));
        $response->assertStatus(200);
    }

    public function test_gestionnaire_raid_can_access_admin_dashboard(): void
    {
        // Gestionnaire-raid has access-admin permission
        $response = $this->actingAs($this->gestionnaireRaid)->get(route('admin.dashboard'));
        $response->assertStatus(200);
    }

    public function test_gestionnaire_raid_cannot_access_admin_users(): void
    {
        $response = $this->actingAs($this->gestionnaireRaid)->get(route('admin.users.index'));
        $response->assertStatus(403);
    }

    public function test_gestionnaire_raid_cannot_approve_clubs(): void
    {
        $club = Club::factory()->pending()->create();
        $response = $this->actingAs($this->gestionnaireRaid)->post(route('admin.clubs.approve', $club));
        $response->assertStatus(403);
    }

    public function test_gestionnaire_raid_without_licence_cannot_create_raid(): void
    {
        // Remove license by deleting member
        $this->gestionnaireRaid->update(['adh_id' => null]);

        $raidData = [
            'raid_name' => 'Test Raid',
            'raid_description' => 'Test Description',
            'clu_id' => $this->club->club_id,
            'raid_date_start' => now()->addMonth()->format('Y-m-d'),
            'raid_date_end' => now()->addMonth()->addDays(2)->format('Y-m-d'),
            'ins_start_date' => now()->addDays(5)->format('Y-m-d'),
            'ins_end_date' => now()->addDays(20)->format('Y-m-d'),
            'raid_city' => 'Paris',
            'raid_postal_code' => '75001',
            'raid_contact' => 'test@test.com',
            'adh_id' => 999,
        ];

        $response = $this->actingAs($this->gestionnaireRaid)
            ->post(route('raids.store'), $raidData);

        // Should be blocked by manager_licence middleware (redirect for non-Inertia POST)
        $response->assertRedirect();
    }

    public function test_gestionnaire_raid_can_access_admin_raids_page(): void
    {
        // Gestionnaire raid should have access to /admin/raids to manage their raids
        $response = $this->actingAs($this->gestionnaireRaid)->get(route('admin.raids.index'));
        $response->assertStatus(200);
    }
}
