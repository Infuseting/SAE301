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
 * Test Responsable Course permissions
 * 
 * Responsable Course should be able to:
 * - All Adherent permissions (requires valid licence)
 * - Create races
 * - Edit/delete own races
 * - Manage registrations for own races
 * - NOT edit/delete other users' races
 * - NOT create/manage clubs or raids (unless also has those roles)
 * - NOT access admin pages
 */
class ResponsableCoursePermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $responsableCourse;
    protected Raid $raid;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Create a responsable course with valid licence
        $member = Member::factory()->create([
            'adh_license' => '123456',
            'adh_end_validity' => now()->addYear(),
            'adh_date_added' => now(),
        ]);
        
        $this->responsableCourse = User::factory()->create([
            'adh_id' => $member->adh_id,
        ]);
        // Remove all roles (including unwanted admin role) before assigning the correct one
        $this->responsableCourse->syncRoles([]);
        $this->responsableCourse->assignRole('responsable-course');

        // Create a raid for races
        $this->raid = Raid::factory()->create();
    }

    /** @test */
    public function responsable_course_can_view_races_create_page(): void
    {
        $response = $this->actingAs($this->responsableCourse)->get(route('races.create'));
        $response->assertStatus(200);
    }

    /** @test */
    public function responsable_course_can_create_race(): void
    {
        $raceData = [
            'name' => 'Test Race',
            'raid_id' => $this->raid->id,
            'start_date' => now()->addMonth()->format('Y-m-d H:i:s'),
            'max_participants' => 100,
            'price' => 25.00,
        ];

        $response = $this->actingAs($this->responsableCourse)->post(route('races.store'), $raceData);

        $response->assertRedirect();
        $this->assertDatabaseHas('races', [
            'race_name' => 'Test Race',
            'raid_id' => $this->raid->raid_id,
        ]);
    }

    /** @test */
    public function responsable_course_can_edit_own_race(): void
    {
        $race = Race::factory()->create([
            'raid_id' => $this->raid->raid_id,
        ]);

        $response = $this->actingAs($this->responsableCourse)->get(route('races.edit', $race));
        $response->assertStatus(200);
    }

    /** @test */
    public function responsable_course_can_update_own_race(): void
    {
        $race = Race::factory()->create([
            'raid_id' => $this->raid->raid_id,
        ]);

        $response = $this->actingAs($this->responsableCourse)
            ->put(route('races.update', $race), [
                'name' => 'Updated Race Name',
                'raid_id' => $race->raid_id,
                'start_date' => $race->start_date->format('Y-m-d H:i:s'),
                'max_participants' => $race->max_participants,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('races', [
            'race_id' => $race->race_id,
            'race_name' => 'Updated Race Name',
        ]);
    }

    /** @test */
    public function responsable_course_can_delete_own_race(): void
    {
        $race = Race::factory()->create([
            'raid_id' => $this->raid->raid_id,
            'adh_id' => $this->responsableCourse->adh_id,
        ]);

        $response = $this->actingAs($this->responsableCourse)->delete(route('races.destroy', $race));

        $response->assertRedirect();
        $this->assertSoftDeleted('races', ['race_id' => $race->race_id]);
    }

    /** @test */
    public function responsable_course_cannot_edit_other_users_race(): void
    {
        $otherUser = User::factory()->create();
        $race = Race::factory()->create([
            'raid_id' => $this->raid->raid_id,
        ]);

        $response = $this->actingAs($this->responsableCourse)->get(route('races.edit', $race));
        $response->assertStatus(403);
    }

    /** @test */
    public function responsable_course_cannot_update_other_users_race(): void
    {
        $otherUser = User::factory()->create();
        $race = Race::factory()->create([
            'raid_id' => $this->raid->raid_id,
        ]);

        $response = $this->actingAs($this->responsableCourse)
            ->put(route('races.update', $race), [
                'name' => 'Hacked Race Name',
                'raid_id' => $race->raid_id,
                'start_date' => $race->start_date->format('Y-m-d H:i:s'),
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function responsable_course_cannot_delete_other_users_race(): void
    {
        $otherUser = User::factory()->create();
        $race = Race::factory()->create([
            'raid_id' => $this->raid->raid_id,
        ]);

        $response = $this->actingAs($this->responsableCourse)->delete(route('races.destroy', $race));
        $response->assertStatus(403);
    }

    /** @test */
    public function responsable_course_can_view_my_races(): void
    {
        $response = $this->actingAs($this->responsableCourse)->get(route('myrace.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function responsable_course_can_register_to_races(): void
    {
        $race = Race::factory()->create();

        $response = $this->actingAs($this->responsableCourse)
            ->post(route('race.register', $race), [
                'runner_first_name' => 'Test',
                'runner_last_name' => 'Runner',
                'runner_birthdate' => '1990-01-01',
            ]);

        $response->assertRedirect();
    }

    /** @test */
    public function responsable_course_cannot_create_club(): void
    {
        $response = $this->actingAs($this->responsableCourse)->get(route('clubs.create'));
        $response->assertStatus(403);
    }

    /** @test */
    public function responsable_course_cannot_store_club(): void
    {
        $clubData = [
            'name' => 'Test Club',
            'description' => 'Test Description',
            'city' => 'Paris',
            'department' => '75',
        ];

        $response = $this->actingAs($this->responsableCourse)->post(route('clubs.store'), $clubData);
        $response->assertStatus(403);
    }

    /** @test */
    public function responsable_course_cannot_create_raid(): void
    {
        $response = $this->actingAs($this->responsableCourse)->get(route('raids.create'));
        $response->assertStatus(403);
    }

    /** @test */
    public function responsable_course_cannot_store_raid(): void
    {
        $club = Club::factory()->create();
        
        $raidData = [
            'name' => 'Test Raid',
            'description' => 'Test Description',
            'club_id' => $club->id,
            'start_date' => now()->addMonth()->format('Y-m-d'),
            'end_date' => now()->addMonth()->addDays(2)->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->responsableCourse)->post(route('raids.store'), $raidData);
        $response->assertStatus(403);
    }

    /** @test */
    public function responsable_course_cannot_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->responsableCourse)->get(route('admin.dashboard'));
        $response->assertStatus(403);
    }

    /** @test */
    public function responsable_course_cannot_access_admin_users(): void
    {
        $response = $this->actingAs($this->responsableCourse)->get(route('admin.users.index'));
        $response->assertStatus(403);
    }

    /** @test */
    public function responsable_course_cannot_approve_clubs(): void
    {
        $club = Club::factory()->pending()->create();
        $response = $this->actingAs($this->responsableCourse)->post(route('admin.clubs.approve', $club));
        $response->assertStatus(403);
    }

    /** @test */
    public function responsable_course_without_licence_cannot_create_race(): void
    {
        // Remove licence
        $this->responsableCourse->member()->delete();

        $raceData = [
            'name' => 'Test Race',
            'raid_id' => $this->raid->raid_id,
            'start_date' => now()->addMonth()->format('Y-m-d H:i:s'),
        ];

        $response = $this->actingAs($this->responsableCourse)
            ->post(route('races.store'), $raceData);

        // Should be blocked by middleware
        $response->assertStatus(403);
    }

    /** @test */
    public function responsable_course_can_access_admin_races_page(): void
    {
        // Responsable course should have access to /admin/races to manage their races
        $response = $this->actingAs($this->responsableCourse)->get(route('admin.races.index'));
        $response->assertStatus(200);
    }
}
