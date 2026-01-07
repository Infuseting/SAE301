<?php

namespace Tests\Unit;

use App\Models\LeaderboardUser;
use App\Models\LeaderboardTeam;
use App\Models\User;
use App\Models\Race;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for Leaderboard models.
 */
class LeaderboardModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test LeaderboardUser model can be created.
     */
    public function test_leaderboard_user_can_be_created(): void
    {
        $user = User::factory()->create();
        $race = Race::factory()->create();

        $result = LeaderboardUser::create([
            'user_id' => $user->id,
            'race_id' => $race->race_id,
            'temps' => 3600.50,
            'malus' => 60.00,
        ]);

        $this->assertDatabaseHas('leaderboard_users', [
            'user_id' => $user->id,
            'race_id' => $race->race_id,
            'temps' => 3600.50,
            'malus' => 60.00,
        ]);

        $this->assertInstanceOf(LeaderboardUser::class, $result);
    }

    /**
     * Test LeaderboardUser temps_final is calculated correctly.
     */
    public function test_leaderboard_user_temps_final_is_calculated(): void
    {
        $user = User::factory()->create();
        $race = Race::factory()->create();

        $result = LeaderboardUser::create([
            'user_id' => $user->id,
            'race_id' => $race->race_id,
            'temps' => 3600.00,
            'malus' => 120.00,
        ]);

        // Refresh to get the computed column
        $result->refresh();

        $this->assertEquals(3720.00, (float) $result->temps_final);
    }

    /**
     * Test LeaderboardUser belongs to user.
     */
    public function test_leaderboard_user_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $race = Race::factory()->create();

        $result = LeaderboardUser::create([
            'user_id' => $user->id,
            'race_id' => $race->race_id,
            'temps' => 3600.00,
            'malus' => 0,
        ]);

        $this->assertInstanceOf(User::class, $result->user);
        $this->assertEquals($user->id, $result->user->id);
    }

    /**
     * Test LeaderboardUser belongs to race.
     */
    public function test_leaderboard_user_belongs_to_race(): void
    {
        $user = User::factory()->create();
        $race = Race::factory()->create();

        $result = LeaderboardUser::create([
            'user_id' => $user->id,
            'race_id' => $race->race_id,
            'temps' => 3600.00,
            'malus' => 0,
        ]);

        $this->assertInstanceOf(Race::class, $result->race);
        $this->assertEquals($race->race_id, $result->race->race_id);
    }

    /**
     * Test LeaderboardUser formatted temps attribute.
     */
    public function test_leaderboard_user_formatted_temps(): void
    {
        $user = User::factory()->create();
        $race = Race::factory()->create();

        $result = LeaderboardUser::create([
            'user_id' => $user->id,
            'race_id' => $race->race_id,
            'temps' => 3661.50, // 1h 1m 1.5s
            'malus' => 0,
        ]);

        $this->assertEquals('01:01:01.50', $result->formatted_temps);
    }

    /**
     * Test LeaderboardUser formatted temps under an hour.
     */
    public function test_leaderboard_user_formatted_temps_under_hour(): void
    {
        $user = User::factory()->create();
        $race = Race::factory()->create();

        $result = LeaderboardUser::create([
            'user_id' => $user->id,
            'race_id' => $race->race_id,
            'temps' => 1861.25, // 31m 1.25s
            'malus' => 0,
        ]);

        $this->assertEquals('31:01.25', $result->formatted_temps);
    }

    /**
     * Test unique constraint on user_id and race_id.
     */
    public function test_leaderboard_user_unique_constraint(): void
    {
        $user = User::factory()->create();
        $race = Race::factory()->create();

        LeaderboardUser::create([
            'user_id' => $user->id,
            'race_id' => $race->race_id,
            'temps' => 3600.00,
            'malus' => 0,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        LeaderboardUser::create([
            'user_id' => $user->id,
            'race_id' => $race->race_id,
            'temps' => 3700.00,
            'malus' => 0,
        ]);
    }

    /**
     * Test LeaderboardTeam model can be created.
     */
    public function test_leaderboard_team_can_be_created(): void
    {
        $team = Team::factory()->create();
        $race = Race::factory()->create();

        $result = LeaderboardTeam::create([
            'equ_id' => $team->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 3600.50,
            'average_malus' => 60.00,
            'average_temps_final' => 3660.50,
            'member_count' => 3,
        ]);

        $this->assertDatabaseHas('leaderboard_teams', [
            'equ_id' => $team->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 3600.50,
        ]);

        $this->assertInstanceOf(LeaderboardTeam::class, $result);
    }

    /**
     * Test LeaderboardTeam belongs to team.
     */
    public function test_leaderboard_team_belongs_to_team(): void
    {
        $team = Team::factory()->create();
        $race = Race::factory()->create();

        $result = LeaderboardTeam::create([
            'equ_id' => $team->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 3600.00,
            'average_malus' => 0,
            'average_temps_final' => 3600.00,
            'member_count' => 2,
        ]);

        $this->assertInstanceOf(Team::class, $result->team);
        $this->assertEquals($team->equ_id, $result->team->equ_id);
    }

    /**
     * Test LeaderboardTeam formatted average temps.
     */
    public function test_leaderboard_team_formatted_average_temps(): void
    {
        $team = Team::factory()->create();
        $race = Race::factory()->create();

        $result = LeaderboardTeam::create([
            'equ_id' => $team->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 7322.75, // 2h 2m 2.75s
            'average_malus' => 0,
            'average_temps_final' => 7322.75,
            'member_count' => 3,
        ]);

        $this->assertEquals('02:02:02.75', $result->formatted_average_temps);
    }

    /**
     * Test LeaderboardTeam unique constraint.
     */
    public function test_leaderboard_team_unique_constraint(): void
    {
        $team = Team::factory()->create();
        $race = Race::factory()->create();

        LeaderboardTeam::create([
            'equ_id' => $team->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 3600.00,
            'average_malus' => 0,
            'average_temps_final' => 3600.00,
            'member_count' => 2,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        LeaderboardTeam::create([
            'equ_id' => $team->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 3700.00,
            'average_malus' => 0,
            'average_temps_final' => 3700.00,
            'member_count' => 2,
        ]);
    }

    /**
     * Test LeaderboardUser with zero malus.
     */
    public function test_leaderboard_user_with_zero_malus(): void
    {
        $user = User::factory()->create();
        $race = Race::factory()->create();

        $result = LeaderboardUser::create([
            'user_id' => $user->id,
            'race_id' => $race->race_id,
            'temps' => 3600.00,
            'malus' => 0,
        ]);

        $result->refresh();

        $this->assertEquals(3600.00, (float) $result->temps_final);
        $this->assertEquals('00:00.00', $result->formatted_malus);
    }

    /**
     * Test cascading delete when user is deleted.
     */
    public function test_leaderboard_user_cascade_delete_on_user(): void
    {
        $user = User::factory()->create();
        $race = Race::factory()->create();

        LeaderboardUser::create([
            'user_id' => $user->id,
            'race_id' => $race->race_id,
            'temps' => 3600.00,
            'malus' => 0,
        ]);

        $this->assertDatabaseHas('leaderboard_users', ['user_id' => $user->id]);

        $user->delete();

        $this->assertDatabaseMissing('leaderboard_users', ['user_id' => $user->id]);
    }

    /**
     * Test cascading delete when race is deleted.
     */
    public function test_leaderboard_user_cascade_delete_on_race(): void
    {
        $user = User::factory()->create();
        $race = Race::factory()->create();

        LeaderboardUser::create([
            'user_id' => $user->id,
            'race_id' => $race->race_id,
            'temps' => 3600.00,
            'malus' => 0,
        ]);

        $this->assertDatabaseHas('leaderboard_users', ['race_id' => $race->race_id]);

        $race->delete();

        $this->assertDatabaseMissing('leaderboard_users', ['race_id' => $race->race_id]);
    }
}
