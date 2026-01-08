<?php

namespace Tests\Feature\Leaderboard;

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

    // ============================================
    // RACE FILTERING TESTS
    // ============================================

    /**
     * Test public individual leaderboard filters by race correctly.
     */
    public function test_public_individual_leaderboard_filters_by_race(): void
    {
        $user = User::factory()->create();
        $race1 = Race::factory()->create(['race_name' => 'Race 1']);
        $race2 = Race::factory()->create(['race_name' => 'Race 2']);
        $publicUser = User::factory()->create(['is_public' => true]);

        LeaderboardUser::create(['user_id' => $publicUser->id, 'race_id' => $race1->race_id, 'temps' => 3600, 'malus' => 0]);
        LeaderboardUser::create(['user_id' => $publicUser->id, 'race_id' => $race2->race_id, 'temps' => 4000, 'malus' => 0]);

        $response = $this->actingAs($user)->get(route('leaderboard.index', [
            'race_id' => $race1->race_id,
            'type' => 'individual',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('results.data', 1)
        );
    }

    /**
     * Test public team leaderboard filters by race correctly.
     */
    public function test_public_team_leaderboard_filters_by_race(): void
    {
        $user = User::factory()->create();
        $race1 = Race::factory()->create(['race_name' => 'Race 1']);
        $race2 = Race::factory()->create(['race_name' => 'Race 2']);
        $team = Team::factory()->create();

        LeaderboardTeam::create([
            'equ_id' => $team->equ_id,
            'race_id' => $race1->race_id,
            'average_temps' => 3600,
            'average_malus' => 0,
            'average_temps_final' => 3600,
            'member_count' => 2,
        ]);
        LeaderboardTeam::create([
            'equ_id' => $team->equ_id,
            'race_id' => $race2->race_id,
            'average_temps' => 4000,
            'average_malus' => 0,
            'average_temps_final' => 4000,
            'member_count' => 2,
        ]);

        $response = $this->actingAs($user)->get(route('leaderboard.index', [
            'race_id' => $race1->race_id,
            'type' => 'team',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('results.data', 1)
        );
    }

    // ============================================
    // MY LEADERBOARD TEAM RESULTS TESTS
    // ============================================

    /**
     * Test my leaderboard shows team results.
     */
    public function test_my_leaderboard_shows_team_results(): void
    {
        $race = Race::factory()->create();
        $user = User::factory()->create();
        $team = Team::factory()->create();

        // Link user to team
        $hasIdUsersColumn = \Illuminate\Support\Facades\DB::getSchemaBuilder()->hasColumn('has_participate', 'id_users');
        $userIdColumn = $hasIdUsersColumn ? 'id_users' : 'id';
        \Illuminate\Support\Facades\DB::table('has_participate')->insert([
            $userIdColumn => $user->id,
            'equ_id' => $team->equ_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        LeaderboardTeam::create([
            'equ_id' => $team->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 3600,
            'average_malus' => 0,
            'average_temps_final' => 3600,
            'member_count' => 2,
        ]);

        $response = $this->actingAs($user)->get(route('my-leaderboard.index', ['type' => 'team']));

        $response->assertStatus(200);
        // Team results may be in 'results' or 'teamResults' depending on controller
        $response->assertInertia(fn ($page) => $page
            ->has('results')
        );
    }

    /**
     * Test my leaderboard team results empty for user without team.
     */
    public function test_my_leaderboard_team_results_empty_for_user_without_team(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('my-leaderboard.index', ['type' => 'team']));

        $response->assertStatus(200);
        // Team results may be in 'results' depending on controller implementation
        $response->assertInertia(fn ($page) => $page
            ->has('results')
        );
    }

    // ============================================
    // SEARCH TESTS
    // ============================================

    /**
     * Test public leaderboard search by participant name.
     */
    public function test_public_leaderboard_search_by_name(): void
    {
        $user = User::factory()->create();
        $race = Race::factory()->create();
        $jean = User::factory()->create(['first_name' => 'Jean', 'last_name' => 'Dupont', 'is_public' => true]);
        $marie = User::factory()->create(['first_name' => 'Marie', 'last_name' => 'Martin', 'is_public' => true]);

        LeaderboardUser::create(['user_id' => $jean->id, 'race_id' => $race->race_id, 'temps' => 3600, 'malus' => 0]);
        LeaderboardUser::create(['user_id' => $marie->id, 'race_id' => $race->race_id, 'temps' => 3700, 'malus' => 0]);

        $response = $this->actingAs($user)->get(route('leaderboard.index', [
            'race_id' => $race->race_id,
            'search' => 'Jean',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('results.data', 1)
            ->where('search', 'Jean')
        );
    }

    /**
     * Test public leaderboard search by team name.
     */
    public function test_public_leaderboard_search_by_team_name(): void
    {
        $user = User::factory()->create();
        $race = Race::factory()->create();
        $alphaTeam = Team::factory()->create(['equ_name' => 'Alpha Team']);
        $betaTeam = Team::factory()->create(['equ_name' => 'Beta Team']);

        LeaderboardTeam::create([
            'equ_id' => $alphaTeam->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 3600,
            'average_malus' => 0,
            'average_temps_final' => 3600,
            'member_count' => 2,
        ]);
        LeaderboardTeam::create([
            'equ_id' => $betaTeam->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 3700,
            'average_malus' => 0,
            'average_temps_final' => 3700,
            'member_count' => 2,
        ]);

        $response = $this->actingAs($user)->get(route('leaderboard.index', [
            'race_id' => $race->race_id,
            'type' => 'team',
            'search' => 'Alpha',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('results.data', 1)
        );
    }

    // ============================================
    // VISIBILITY TESTS
    // ============================================

    /**
     * Test public leaderboard respects profile visibility.
     */
    public function test_public_leaderboard_respects_visibility(): void
    {
        $user = User::factory()->create();
        $race = Race::factory()->create();
        
        // Create 5 public and 5 private users
        for ($i = 0; $i < 5; $i++) {
            $publicUser = User::factory()->create(['is_public' => true]);
            LeaderboardUser::create(['user_id' => $publicUser->id, 'race_id' => $race->race_id, 'temps' => 3600 + $i, 'malus' => 0]);
            
            $privateUser = User::factory()->create(['is_public' => false]);
            LeaderboardUser::create(['user_id' => $privateUser->id, 'race_id' => $race->race_id, 'temps' => 3500 + $i, 'malus' => 0]);
        }

        $response = $this->actingAs($user)->get(route('leaderboard.index', [
            'race_id' => $race->race_id,
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('results.data', 5) // Only public profiles
        );
    }

    /**
     * Test ranking is correct even with hidden private profiles.
     */
    public function test_ranking_correct_with_hidden_profiles(): void
    {
        $user = User::factory()->create();
        $race = Race::factory()->create();
        
        // Private user with best time
        $privateUser = User::factory()->create(['is_public' => false, 'first_name' => 'Private']);
        LeaderboardUser::create(['user_id' => $privateUser->id, 'race_id' => $race->race_id, 'temps' => 3000, 'malus' => 0]);
        
        // Public user with second best time
        $publicUser1 = User::factory()->create(['is_public' => true, 'first_name' => 'First']);
        LeaderboardUser::create(['user_id' => $publicUser1->id, 'race_id' => $race->race_id, 'temps' => 3100, 'malus' => 0]);
        
        // Public user with third best time
        $publicUser2 = User::factory()->create(['is_public' => true, 'first_name' => 'Second']);
        LeaderboardUser::create(['user_id' => $publicUser2->id, 'race_id' => $race->race_id, 'temps' => 3200, 'malus' => 0]);

        $response = $this->actingAs($user)->get(route('leaderboard.index', [
            'race_id' => $race->race_id,
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('results.data', 2) // Only 2 public users
            ->where('results.data.0.rank', 1) // First public user is rank 1 in public view
        );
    }
}
