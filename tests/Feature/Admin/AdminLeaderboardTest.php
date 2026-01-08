<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Race;
use App\Models\LeaderboardUser;
use App\Models\LeaderboardTeam;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Feature tests for admin leaderboard management.
 */
class AdminLeaderboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::firstOrCreate(['name' => 'view users']);
        Permission::firstOrCreate(['name' => 'edit users']);
        Permission::firstOrCreate(['name' => 'delete users']);

        // Create admin role
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(['view users', 'edit users', 'delete users']);

        // Create admin user
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // Create regular user
        $this->user = User::factory()->create();
    }

    /**
     * Test admin can access leaderboard index.
     */
    public function test_admin_can_access_leaderboard_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.leaderboard.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/Leaderboard/Index'));
    }

    /**
     * Test regular user cannot access leaderboard index.
     */
    public function test_regular_user_cannot_access_leaderboard_index(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.leaderboard.index'));

        $response->assertStatus(403);
    }

    /**
     * Test guest cannot access leaderboard index.
     */
    public function test_guest_cannot_access_leaderboard_index(): void
    {
        $response = $this->get(route('admin.leaderboard.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test admin can view race results.
     */
    public function test_admin_can_view_race_results(): void
    {
        $race = Race::factory()->create();
        $user = User::factory()->create();

        LeaderboardUser::create([
            'user_id' => $user->id,
            'race_id' => $race->race_id,
            'temps' => 3600.00,
            'malus' => 0,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.leaderboard.results', ['raceId' => $race->race_id]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Leaderboard/Results')
            ->has('results.data', 1)
        );
    }

    /**
     * Test admin can import individual CSV.
     */
    public function test_admin_can_import_individual_csv(): void
    {
        $race = Race::factory()->create();
        $user = User::factory()->create();

        $csvContent = "user_id;temps;malus\n{$user->id};3600.50;60";
        
        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(route('admin.leaderboard.import'), [
            'file' => $file,
            'race_id' => $race->race_id,
            'type' => 'individual',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('leaderboard_users', [
            'user_id' => $user->id,
            'race_id' => $race->race_id,
        ]);
    }

    /**
     * Test admin can import team CSV.
     */
    public function test_admin_can_import_team_csv(): void
    {
        $race = Race::factory()->create();
        $team = Team::factory()->create();

        $csvContent = "equ_id;temps;malus;member_count\n{$team->equ_id};3600.50;60;3";
        
        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(route('admin.leaderboard.import'), [
            'file' => $file,
            'race_id' => $race->race_id,
            'type' => 'team',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('leaderboard_teams', [
            'equ_id' => $team->equ_id,
            'race_id' => $race->race_id,
        ]);
    }

    /**
     * Test import validation requires file.
     */
    public function test_import_validation_requires_file(): void
    {
        $race = Race::factory()->create();

        $response = $this->actingAs($this->admin)->post(route('admin.leaderboard.import'), [
            'race_id' => $race->race_id,
            'type' => 'individual',
        ]);

        $response->assertSessionHasErrors('file');
    }

    /**
     * Test import validation requires race_id.
     */
    public function test_import_validation_requires_race_id(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', "user_id;temps;malus\n1;3600;0");

        $response = $this->actingAs($this->admin)->post(route('admin.leaderboard.import'), [
            'file' => $file,
            'type' => 'individual',
        ]);

        $response->assertSessionHasErrors('race_id');
    }

    /**
     * Test import validation requires valid type.
     */
    public function test_import_validation_requires_valid_type(): void
    {
        $race = Race::factory()->create();
        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', "user_id;temps;malus\n1;3600;0");

        $response = $this->actingAs($this->admin)->post(route('admin.leaderboard.import'), [
            'file' => $file,
            'race_id' => $race->race_id,
            'type' => 'invalid',
        ]);

        $response->assertSessionHasErrors('type');
    }

    /**
     * Test admin can export individual CSV.
     */
    public function test_admin_can_export_individual_csv(): void
    {
        $race = Race::factory()->create(['race_name' => 'Test Race']);
        $user = User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);

        LeaderboardUser::create([
            'user_id' => $user->id,
            'race_id' => $race->race_id,
            'temps' => 3600.00,
            'malus' => 60.00,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.leaderboard.export', ['raceId' => $race->race_id, 'type' => 'individual']));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertSee('John Doe');
    }

    /**
     * Test admin can export team CSV.
     */
    public function test_admin_can_export_team_csv(): void
    {
        $race = Race::factory()->create(['race_name' => 'Test Race']);
        $team = Team::factory()->create(['equ_name' => 'Super Team']);

        LeaderboardTeam::create([
            'equ_id' => $team->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 3600.00,
            'average_malus' => 60.00,
            'average_temps_final' => 3660.00,
            'member_count' => 3,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.leaderboard.export', ['raceId' => $race->race_id, 'type' => 'team']));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertSee('Super Team');
    }

    /**
     * Test admin can delete a result.
     */
    public function test_admin_can_delete_result(): void
    {
        $race = Race::factory()->create();
        $user = User::factory()->create();

        $result = LeaderboardUser::create([
            'user_id' => $user->id,
            'race_id' => $race->race_id,
            'temps' => 3600.00,
            'malus' => 0,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.leaderboard.destroy', ['resultId' => $result->id]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('leaderboard_users', ['id' => $result->id]);
    }

    /**
     * Test regular user cannot import CSV.
     */
    public function test_regular_user_cannot_import_csv(): void
    {
        $race = Race::factory()->create();
        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', "user_id;temps;malus\n1;3600;0");

        $response = $this->actingAs($this->user)->post(route('admin.leaderboard.import'), [
            'file' => $file,
            'race_id' => $race->race_id,
            'type' => 'individual',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test regular user cannot delete result.
     */
    public function test_regular_user_cannot_delete_result(): void
    {
        $race = Race::factory()->create();
        $user = User::factory()->create();

        $result = LeaderboardUser::create([
            'user_id' => $user->id,
            'race_id' => $race->race_id,
            'temps' => 3600.00,
            'malus' => 0,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('admin.leaderboard.destroy', ['resultId' => $result->id]));

        $response->assertStatus(403);
        $this->assertDatabaseHas('leaderboard_users', ['id' => $result->id]);
    }

    /**
     * Test leaderboard results can be filtered by search.
     */
    public function test_leaderboard_results_can_be_filtered_by_search(): void
    {
        $race = Race::factory()->create();
        $user1 = User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
        $user2 = User::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith']);

        LeaderboardUser::create([
            'user_id' => $user1->id,
            'race_id' => $race->race_id,
            'temps' => 3600.00,
            'malus' => 0,
        ]);

        LeaderboardUser::create([
            'user_id' => $user2->id,
            'race_id' => $race->race_id,
            'temps' => 3700.00,
            'malus' => 0,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.leaderboard.results', [
                'raceId' => $race->race_id,
                'search' => 'John',
            ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('results.data', 1)
            ->where('search', 'John')
        );
    }

    /**
     * Test leaderboard results can be switched between individual and team.
     */
    public function test_leaderboard_results_can_switch_type(): void
    {
        $race = Race::factory()->create();
        $team = Team::factory()->create();

        LeaderboardTeam::create([
            'equ_id' => $team->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 3600.00,
            'average_malus' => 0,
            'average_temps_final' => 3600.00,
            'member_count' => 2,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.leaderboard.results', [
                'raceId' => $race->race_id,
                'type' => 'team',
            ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('results.data', 1)
            ->where('type', 'team')
        );
    }

    /**
     * Test import logs activity.
     */
    public function test_import_logs_activity(): void
    {
        $race = Race::factory()->create();
        $user = User::factory()->create();

        $csvContent = "user_id;temps;malus\n{$user->id};3600.50;60";
        
        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $this->actingAs($this->admin)->post(route('admin.leaderboard.import'), [
            'file' => $file,
            'race_id' => $race->race_id,
            'type' => 'individual',
        ]);

        $this->assertDatabaseHas('activity_log', [
            'description' => 'LEADERBOARD_CSV_IMPORT',
            'causer_id' => $this->admin->id,
        ]);
    }

    /**
     * Test export logs activity.
     */
    public function test_export_logs_activity(): void
    {
        $race = Race::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('admin.leaderboard.export', ['raceId' => $race->race_id]));

        $this->assertDatabaseHas('activity_log', [
            'description' => 'LEADERBOARD_CSV_EXPORT',
            'causer_id' => $this->admin->id,
        ]);
    }

    /**
     * Test import handles invalid CSV gracefully.
     */
    public function test_import_handles_invalid_csv_gracefully(): void
    {
        $race = Race::factory()->create();

        // CSV missing required column
        $csvContent = "user_id;malus\n1;0";
        
        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(route('admin.leaderboard.import'), [
            'file' => $file,
            'race_id' => $race->race_id,
            'type' => 'individual',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('file');
    }
}
