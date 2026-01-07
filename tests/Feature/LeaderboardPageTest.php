<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Race;
use App\Models\Team;
use App\Models\LeaderboardUser;
use App\Models\LeaderboardTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for public leaderboard pages.
 */
class LeaderboardPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test authenticated user can access my leaderboard page.
     */
    public function test_authenticated_user_can_access_my_leaderboard(): void
    {
        $user = User::factory()->create();
        $race = Race::factory()->create();

        LeaderboardUser::create([
            'user_id' => $user->id,
            'race_id' => $race->race_id,
            'temps' => 3600.00,
            'malus' => 0,
        ]);

        $response = $this->actingAs($user)->get(route('my-leaderboard.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('MyLeaderboard/Index'));
    }

    /**
     * Test guest cannot access my leaderboard page.
     */
    public function test_guest_cannot_access_my_leaderboard(): void
    {
        $response = $this->get(route('my-leaderboard.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test my leaderboard shows user's results.
     */
    public function test_my_leaderboard_shows_user_results(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $race = Race::factory()->create();

        LeaderboardUser::create([
            'user_id' => $user->id,
            'race_id' => $race->race_id,
            'temps' => 3600.00,
            'malus' => 0,
        ]);

        LeaderboardUser::create([
            'user_id' => $otherUser->id,
            'race_id' => $race->race_id,
            'temps' => 3700.00,
            'malus' => 0,
        ]);

        $response = $this->actingAs($user)->get(route('my-leaderboard.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('results.data', 1)
        );
    }

    /**
     * Test my leaderboard can be filtered by search.
     */
    public function test_my_leaderboard_can_be_filtered_by_search(): void
    {
        $user = User::factory()->create();
        $race1 = Race::factory()->create(['race_name' => 'Marathon Paris']);
        $race2 = Race::factory()->create(['race_name' => 'Trail Lyon']);

        LeaderboardUser::create([
            'user_id' => $user->id,
            'race_id' => $race1->race_id,
            'temps' => 3600.00,
            'malus' => 0,
        ]);

        LeaderboardUser::create([
            'user_id' => $user->id,
            'race_id' => $race2->race_id,
            'temps' => 3700.00,
            'malus' => 0,
        ]);

        $response = $this->actingAs($user)->get(route('my-leaderboard.index', ['search' => 'Marathon']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('results.data', 1)
            ->where('search', 'Marathon')
        );
    }

    /**
     * Test my leaderboard can be sorted by best/worst.
     */
    public function test_my_leaderboard_can_be_sorted(): void
    {
        $user = User::factory()->create();
        $race = Race::factory()->create();

        LeaderboardUser::create([
            'user_id' => $user->id,
            'race_id' => $race->race_id,
            'temps' => 3600.00,
            'malus' => 0,
        ]);

        $response = $this->actingAs($user)->get(route('my-leaderboard.index', ['sort' => 'worst']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('sortBy', 'worst')
        );
    }

    /**
     * Test public leaderboard page is accessible.
     */
    public function test_public_leaderboard_is_accessible(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('leaderboard.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Leaderboard/Index'));
    }

    /**
     * Test public leaderboard only shows public profiles.
     */
    public function test_public_leaderboard_only_shows_public_profiles(): void
    {
        $publicUser = User::factory()->create(['is_public' => true, 'first_name' => 'Public', 'last_name' => 'User']);
        $privateUser = User::factory()->create(['is_public' => false, 'first_name' => 'Private', 'last_name' => 'User']);
        $currentUser = User::factory()->create();
        $race = Race::factory()->create();

        LeaderboardUser::create([
            'user_id' => $publicUser->id,
            'race_id' => $race->race_id,
            'temps' => 3600.00,
            'malus' => 0,
        ]);

        LeaderboardUser::create([
            'user_id' => $privateUser->id,
            'race_id' => $race->race_id,
            'temps' => 3500.00, // Faster, but private
            'malus' => 0,
        ]);

        $response = $this->actingAs($currentUser)->get(route('leaderboard.index', ['race_id' => $race->race_id]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('results.data', 1)
        );
    }

    /**
     * Test public leaderboard can filter by race.
     */
    public function test_public_leaderboard_can_filter_by_race(): void
    {
        $user = User::factory()->create(['is_public' => true]);
        $currentUser = User::factory()->create();
        $race1 = Race::factory()->create();
        $race2 = Race::factory()->create();

        LeaderboardUser::create([
            'user_id' => $user->id,
            'race_id' => $race1->race_id,
            'temps' => 3600.00,
            'malus' => 0,
        ]);

        LeaderboardUser::create([
            'user_id' => $user->id,
            'race_id' => $race2->race_id,
            'temps' => 3700.00,
            'malus' => 0,
        ]);

        $response = $this->actingAs($currentUser)->get(route('leaderboard.index', ['race_id' => $race1->race_id]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('results.data', 1)
        );
    }

    /**
     * Test public leaderboard can switch to team view.
     */
    public function test_public_leaderboard_can_switch_to_team_view(): void
    {
        $user = User::factory()->create();
        $race = Race::factory()->create();
        $team = Team::factory()->create();

        LeaderboardTeam::create([
            'equ_id' => $team->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 3600.00,
            'average_malus' => 0,
            'average_temps_final' => 3600.00,
            'member_count' => 3,
        ]);

        $response = $this->actingAs($user)->get(route('leaderboard.index', [
            'race_id' => $race->race_id,
            'type' => 'team',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('type', 'team')
            ->has('results.data', 1)
        );
    }

    /**
     * Test leaderboard export is accessible for authenticated users.
     */
    public function test_leaderboard_export_accessible_for_authenticated_users(): void
    {
        $user = User::factory()->create(['is_public' => true]);
        $currentUser = User::factory()->create();
        $race = Race::factory()->create();

        LeaderboardUser::create([
            'user_id' => $user->id,
            'race_id' => $race->race_id,
            'temps' => 3600.00,
            'malus' => 0,
        ]);

        $response = $this->actingAs($currentUser)
            ->get(route('leaderboard.export', ['raceId' => $race->race_id]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    /**
     * Test my leaderboard can switch between individual and team.
     */
    public function test_my_leaderboard_can_switch_between_individual_and_team(): void
    {
        $user = User::factory()->create();
        $race = Race::factory()->create();
        $team = Team::factory()->create();

        // Link user to team (without race_id as it doesn't exist in schema)
        \DB::table('has_participate')->insert([
            'id' => $user->id,
            'equ_id' => $team->equ_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        LeaderboardTeam::create([
            'equ_id' => $team->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 3600.00,
            'average_malus' => 0,
            'average_temps_final' => 3600.00,
            'member_count' => 1,
        ]);

        $response = $this->actingAs($user)->get(route('my-leaderboard.index', ['type' => 'team']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('type', 'team')
        );
    }

    /**
     * Test results pagination works correctly.
     */
    public function test_leaderboard_pagination_works(): void
    {
        $race = Race::factory()->create();
        $currentUser = User::factory()->create();

        // Create 25 users with public profiles
        for ($i = 0; $i < 25; $i++) {
            $user = User::factory()->create(['is_public' => true]);
            LeaderboardUser::create([
                'user_id' => $user->id,
                'race_id' => $race->race_id,
                'temps' => 3600 + ($i * 10),
                'malus' => 0,
            ]);
        }

        $response = $this->actingAs($currentUser)->get(route('leaderboard.index', [
            'race_id' => $race->race_id,
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('results.current_page', 1)
            ->has('results.data', 20) // Default per_page is 20
        );
    }

    /**
     * Test empty leaderboard displays correctly.
     */
    public function test_empty_leaderboard_displays_correctly(): void
    {
        $user = User::factory()->create();
        $race = Race::factory()->create();

        $response = $this->actingAs($user)->get(route('leaderboard.index', [
            'race_id' => $race->race_id,
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('results.data', 0)
            ->where('results.total', 0)
        );
    }

    /**
     * Test my leaderboard empty state for new user.
     */
    public function test_my_leaderboard_empty_state_for_new_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('my-leaderboard.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('results.total', 0)
        );
    }
}

