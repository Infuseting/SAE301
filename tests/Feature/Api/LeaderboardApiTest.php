<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Race;
use App\Models\Team;
use App\Models\LeaderboardUser;
use App\Models\LeaderboardTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Feature tests for Leaderboard API endpoints.
 */
class LeaderboardApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test API can get list of races.
     */
    public function test_api_can_get_races(): void
    {
        Race::factory()->count(3)->create();

        $response = $this->getJson('/api/leaderboard/races');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test API can get list of races without authentication (public API).
     */
    public function test_api_races_endpoint_is_public(): void
    {
        Race::factory()->count(3)->create();

        $response = $this->getJson('/api/leaderboard/races');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test API can get individual leaderboard for a race.
     */
    public function test_api_can_get_individual_leaderboard(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $race = Race::factory()->create();
        $participant = User::factory()->create();

        LeaderboardUser::create([
            'user_id' => $participant->id,
            'race_id' => $race->race_id,
            'temps' => 3600.00,
            'malus' => 0,
        ]);

        $response = $this->getJson("/api/leaderboard/{$race->race_id}/individual");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('total', 1);
    }

    /**
     * Test API individual leaderboard is sorted by temps_final.
     */
    public function test_api_individual_leaderboard_is_sorted(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $race = Race::factory()->create();
        $fastUser = User::factory()->create();
        $slowUser = User::factory()->create();

        LeaderboardUser::create([
            'user_id' => $slowUser->id,
            'race_id' => $race->race_id,
            'temps' => 4000.00,
            'malus' => 0,
        ]);

        LeaderboardUser::create([
            'user_id' => $fastUser->id,
            'race_id' => $race->race_id,
            'temps' => 3500.00,
            'malus' => 0,
        ]);

        $response = $this->getJson("/api/leaderboard/{$race->race_id}/individual");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals($fastUser->id, $data[0]['user_id']);
        $this->assertEquals(1, $data[0]['rank']);
    }

    /**
     * Test API can search individual leaderboard.
     */
    public function test_api_can_search_individual_leaderboard(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $race = Race::factory()->create();
        $john = User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
        $jane = User::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith']);

        LeaderboardUser::create([
            'user_id' => $john->id,
            'race_id' => $race->race_id,
            'temps' => 3600.00,
            'malus' => 0,
        ]);

        LeaderboardUser::create([
            'user_id' => $jane->id,
            'race_id' => $race->race_id,
            'temps' => 3700.00,
            'malus' => 0,
        ]);

        $response = $this->getJson("/api/leaderboard/{$race->race_id}/individual?search=John");

        $response->assertStatus(200)
            ->assertJsonPath('total', 1);
    }

    /**
     * Test API can get team leaderboard for a race.
     */
    public function test_api_can_get_team_leaderboard(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

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

        $response = $this->getJson("/api/leaderboard/{$race->race_id}/teams");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('total', 1);
    }

    /**
     * Test API team leaderboard is sorted by average_temps_final.
     */
    public function test_api_team_leaderboard_is_sorted(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $race = Race::factory()->create();
        $fastTeam = Team::factory()->create(['equ_name' => 'Fast Team']);
        $slowTeam = Team::factory()->create(['equ_name' => 'Slow Team']);

        LeaderboardTeam::create([
            'equ_id' => $slowTeam->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 4000.00,
            'average_malus' => 0,
            'average_temps_final' => 4000.00,
            'member_count' => 2,
        ]);

        LeaderboardTeam::create([
            'equ_id' => $fastTeam->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 3500.00,
            'average_malus' => 0,
            'average_temps_final' => 3500.00,
            'member_count' => 3,
        ]);

        $response = $this->getJson("/api/leaderboard/{$race->race_id}/teams");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals($fastTeam->equ_id, $data[0]['equ_id']);
        $this->assertEquals(1, $data[0]['rank']);
    }

    /**
     * Test API can get specific user result in a race.
     */
    public function test_api_can_get_user_result(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $race = Race::factory()->create();
        $participant = User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);

        LeaderboardUser::create([
            'user_id' => $participant->id,
            'race_id' => $race->race_id,
            'temps' => 3600.00,
            'malus' => 60.00,
        ]);

        $response = $this->getJson("/api/leaderboard/{$race->race_id}/user/{$participant->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.user_id', $participant->id)
            ->assertJsonPath('data.rank', 1)
            ->assertJsonPath('data.user_name', 'John Doe');
    }

    /**
     * Test API returns 404 for non-existent user result.
     */
    public function test_api_returns_404_for_non_existent_user_result(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $race = Race::factory()->create();

        $response = $this->getJson("/api/leaderboard/{$race->race_id}/user/99999");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Result not found',
            ]);
    }

    /**
     * Test API leaderboard pagination.
     */
    public function test_api_leaderboard_pagination(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $race = Race::factory()->create();

        // Create 25 results
        for ($i = 0; $i < 25; $i++) {
            $participant = User::factory()->create();
            LeaderboardUser::create([
                'user_id' => $participant->id,
                'race_id' => $race->race_id,
                'temps' => 3600 + ($i * 10),
                'malus' => 0,
            ]);
        }

        $response = $this->getJson("/api/leaderboard/{$race->race_id}/individual?per_page=10");

        $response->assertStatus(200)
            ->assertJsonPath('per_page', 10)
            ->assertJsonPath('total', 25)
            ->assertJsonCount(10, 'data');
    }

    /**
     * Test API leaderboard second page.
     */
    public function test_api_leaderboard_second_page(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $race = Race::factory()->create();

        // Create 25 results
        for ($i = 0; $i < 25; $i++) {
            $participant = User::factory()->create();
            LeaderboardUser::create([
                'user_id' => $participant->id,
                'race_id' => $race->race_id,
                'temps' => 3600 + ($i * 10),
                'malus' => 0,
            ]);
        }

        $response = $this->getJson("/api/leaderboard/{$race->race_id}/individual?per_page=10&page=2");

        $response->assertStatus(200)
            ->assertJsonPath('current_page', 2)
            ->assertJsonCount(10, 'data');

        // First item on page 2 should have rank 11
        $data = $response->json('data');
        $this->assertEquals(11, $data[0]['rank']);
    }

    /**
     * Test API empty leaderboard returns empty array.
     */
    public function test_api_empty_leaderboard_returns_empty_array(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $race = Race::factory()->create();

        $response = $this->getJson("/api/leaderboard/{$race->race_id}/individual");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('total', 0)
            ->assertJsonCount(0, 'data');
    }

    /**
     * Test API search is case-insensitive.
     */
    public function test_api_search_is_case_insensitive(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $race = Race::factory()->create();
        $participant = User::factory()->create(['first_name' => 'JOHN', 'last_name' => 'DOE']);

        LeaderboardUser::create([
            'user_id' => $participant->id,
            'race_id' => $race->race_id,
            'temps' => 3600.00,
            'malus' => 0,
        ]);

        $response = $this->getJson("/api/leaderboard/{$race->race_id}/individual?search=john");

        $response->assertStatus(200)
            ->assertJsonPath('total', 1);
    }

    /**
     * Test API can search team leaderboard.
     */
    public function test_api_can_search_team_leaderboard(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $race = Race::factory()->create();
        $alpha = Team::factory()->create(['equ_name' => 'Alpha Team']);
        $beta = Team::factory()->create(['equ_name' => 'Beta Team']);

        LeaderboardTeam::create([
            'equ_id' => $alpha->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 3600.00,
            'average_malus' => 0,
            'average_temps_final' => 3600.00,
            'member_count' => 3,
        ]);

        LeaderboardTeam::create([
            'equ_id' => $beta->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 3700.00,
            'average_malus' => 0,
            'average_temps_final' => 3700.00,
            'member_count' => 2,
        ]);

        $response = $this->getJson("/api/leaderboard/{$race->race_id}/teams?search=Alpha");

        $response->assertStatus(200)
            ->assertJsonPath('total', 1);
    }

    /**
     * Test API user result includes formatted times.
     */
    public function test_api_user_result_includes_formatted_times(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $race = Race::factory()->create();
        $participant = User::factory()->create();

        LeaderboardUser::create([
            'user_id' => $participant->id,
            'race_id' => $race->race_id,
            'temps' => 3661.50, // 1h 1m 1.5s
            'malus' => 60.00,  // 1m
        ]);

        $response = $this->getJson("/api/leaderboard/{$race->race_id}/user/{$participant->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.temps_formatted', '01:01:01.50')
            ->assertJsonPath('data.malus_formatted', '01:00.00');
    }
}
