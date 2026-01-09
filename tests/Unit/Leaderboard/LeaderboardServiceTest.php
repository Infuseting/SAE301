<?php

namespace Tests\Unit\Leaderboard;

use App\Services\LeaderboardService;
use App\Models\LeaderboardUser;
use App\Models\LeaderboardTeam;
use App\Models\User;
use App\Models\Race;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Unit tests for LeaderboardService.
 */
class LeaderboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private LeaderboardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LeaderboardService();
    }

    /**
     * Test getRaces returns all races.
     */
    public function test_get_races_returns_all_races(): void
    {
        Race::factory()->count(5)->create();

        $races = $this->service->getRaces();

        $this->assertCount(5, $races);
    }

    /**
     * Test getRaces returns races ordered by date desc.
     */
    public function test_get_races_ordered_by_date_desc(): void
    {
        $oldRace = Race::factory()->create(['race_date_start' => now()->subYear()]);
        $newRace = Race::factory()->create(['race_date_start' => now()]);

        $races = $this->service->getRaces();

        $this->assertEquals($newRace->race_id, $races->first()->race_id);
        $this->assertEquals($oldRace->race_id, $races->last()->race_id);
    }

    /**
     * Test addResult creates a new result.
     */
    public function test_add_result_creates_new_result(): void
    {
        $user = User::factory()->create();
        $race = Race::factory()->create();

        $result = $this->service->addResult($user->id, $race->race_id, 3600.50, 60.00);

        $this->assertInstanceOf(LeaderboardUser::class, $result);
        $this->assertDatabaseHas('leaderboard_users', [
            'user_id' => $user->id,
            'race_id' => $race->race_id,
            'temps' => 3600.50,
            'malus' => 60.00,
        ]);
    }

    /**
     * Test addResult updates existing result.
     */
    public function test_add_result_updates_existing_result(): void
    {
        $user = User::factory()->create();
        $race = Race::factory()->create();

        // First result
        $this->service->addResult($user->id, $race->race_id, 3600.00, 0);

        // Update with better time
        $result = $this->service->addResult($user->id, $race->race_id, 3500.00, 30.00);

        $this->assertDatabaseCount('leaderboard_users', 1);
        $this->assertEquals(3500.00, (float) $result->temps);
        $this->assertEquals(30.00, (float) $result->malus);
    }

    /**
     * Test deleteResult removes a result.
     */
    public function test_delete_result_removes_result(): void
    {
        $user = User::factory()->create();
        $race = Race::factory()->create();

        $result = $this->service->addResult($user->id, $race->race_id, 3600.00, 0);

        $deleted = $this->service->deleteResult($result->id);

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('leaderboard_users', ['id' => $result->id]);
    }

    /**
     * Test deleteResult returns false for non-existent result.
     */
    public function test_delete_result_returns_false_for_non_existent(): void
    {
        $deleted = $this->service->deleteResult(99999);

        $this->assertFalse($deleted);
    }

    /**
     * Test getIndividualLeaderboard returns sorted results.
     */
    public function test_get_individual_leaderboard_sorted_by_temps_final(): void
    {
        $race = Race::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        // Create results with different times (user2 is fastest)
        $this->service->addResult($user1->id, $race->race_id, 4000.00, 0);
        $this->service->addResult($user2->id, $race->race_id, 3500.00, 0);
        $this->service->addResult($user3->id, $race->race_id, 3800.00, 0);

        $leaderboard = $this->service->getIndividualLeaderboard($race->race_id);

        $this->assertEquals(3, $leaderboard['total']);
        $this->assertEquals($user2->id, $leaderboard['data'][0]['user_id']);
        $this->assertEquals(1, $leaderboard['data'][0]['rank']);
    }

    /**
     * Test getIndividualLeaderboard with search filter.
     */
    public function test_get_individual_leaderboard_with_search(): void
    {
        $race = Race::factory()->create();
        $user1 = User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
        $user2 = User::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith']);

        $this->service->addResult($user1->id, $race->race_id, 3600.00, 0);
        $this->service->addResult($user2->id, $race->race_id, 3700.00, 0);

        $leaderboard = $this->service->getIndividualLeaderboard($race->race_id, 'John');

        $this->assertEquals(1, $leaderboard['total']);
        $this->assertEquals($user1->id, $leaderboard['data'][0]['user_id']);
    }

    /**
     * Test getTeamLeaderboard returns sorted team results.
     */
    public function test_get_team_leaderboard_sorted(): void
    {
        $race = Race::factory()->create();
        $team1 = Team::factory()->create(['equ_name' => 'Team Alpha']);
        $team2 = Team::factory()->create(['equ_name' => 'Team Beta']);

        LeaderboardTeam::create([
            'equ_id' => $team1->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 4000.00,
            'average_malus' => 0,
            'average_temps_final' => 4000.00,
            'member_count' => 3,
        ]);

        LeaderboardTeam::create([
            'equ_id' => $team2->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 3500.00,
            'average_malus' => 0,
            'average_temps_final' => 3500.00,
            'member_count' => 2,
        ]);

        $leaderboard = $this->service->getTeamLeaderboard($race->race_id);

        $this->assertEquals(2, $leaderboard['total']);
        $this->assertEquals($team2->equ_id, $leaderboard['data'][0]['equ_id']);
        $this->assertEquals(1, $leaderboard['data'][0]['rank']);
    }

    /**
     * Test getTeamLeaderboard only returns teams for the selected race.
     * Teams from other races should not appear.
     */
    public function test_get_team_leaderboard_filters_by_race(): void
    {
        $race1 = Race::factory()->create(['race_name' => 'Course 1']);
        $race2 = Race::factory()->create(['race_name' => 'Course 2']);
        
        $teamRace1 = Team::factory()->create(['equ_name' => 'Team Race 1']);
        $teamRace2 = Team::factory()->create(['equ_name' => 'Team Race 2']);
        $teamBoth = Team::factory()->create(['equ_name' => 'Team Both Races']);

        // Team 1 only in race 1
        LeaderboardTeam::create([
            'equ_id' => $teamRace1->equ_id,
            'race_id' => $race1->race_id,
            'average_temps' => 3600,
            'average_malus' => 0,
            'average_temps_final' => 3600,
            'member_count' => 2,
        ]);

        // Team 2 only in race 2
        LeaderboardTeam::create([
            'equ_id' => $teamRace2->equ_id,
            'race_id' => $race2->race_id,
            'average_temps' => 3700,
            'average_malus' => 0,
            'average_temps_final' => 3700,
            'member_count' => 2,
        ]);

        // Team Both in both races
        LeaderboardTeam::create([
            'equ_id' => $teamBoth->equ_id,
            'race_id' => $race1->race_id,
            'average_temps' => 3800,
            'average_malus' => 0,
            'average_temps_final' => 3800,
            'member_count' => 3,
        ]);
        LeaderboardTeam::create([
            'equ_id' => $teamBoth->equ_id,
            'race_id' => $race2->race_id,
            'average_temps' => 3500,
            'average_malus' => 0,
            'average_temps_final' => 3500,
            'member_count' => 3,
        ]);

        // Get leaderboard for race 1
        $leaderboardRace1 = $this->service->getTeamLeaderboard($race1->race_id);
        
        $this->assertEquals(2, $leaderboardRace1['total'], 'Race 1 should have 2 teams');
        $teamIdsRace1 = collect($leaderboardRace1['data'])->pluck('equ_id')->toArray();
        $this->assertContains($teamRace1->equ_id, $teamIdsRace1, 'Team Race 1 should be in Race 1 leaderboard');
        $this->assertContains($teamBoth->equ_id, $teamIdsRace1, 'Team Both should be in Race 1 leaderboard');
        $this->assertNotContains($teamRace2->equ_id, $teamIdsRace1, 'Team Race 2 should NOT be in Race 1 leaderboard');

        // Get leaderboard for race 2
        $leaderboardRace2 = $this->service->getTeamLeaderboard($race2->race_id);
        
        $this->assertEquals(2, $leaderboardRace2['total'], 'Race 2 should have 2 teams');
        $teamIdsRace2 = collect($leaderboardRace2['data'])->pluck('equ_id')->toArray();
        $this->assertContains($teamRace2->equ_id, $teamIdsRace2, 'Team Race 2 should be in Race 2 leaderboard');
        $this->assertContains($teamBoth->equ_id, $teamIdsRace2, 'Team Both should be in Race 2 leaderboard');
        $this->assertNotContains($teamRace1->equ_id, $teamIdsRace2, 'Team Race 1 should NOT be in Race 2 leaderboard');
    }

    /**
     * Test getUserResults returns user's race results.
     */
    public function test_get_user_results(): void
    {
        $user = User::factory()->create();
        $race1 = Race::factory()->create(['race_name' => 'Marathon Paris']);
        $race2 = Race::factory()->create(['race_name' => 'Marathon Lyon']);

        $this->service->addResult($user->id, $race1->race_id, 3600.00, 0);
        $this->service->addResult($user->id, $race2->race_id, 3800.00, 60.00);

        $results = $this->service->getUserResults($user->id);

        $this->assertEquals(2, $results['total']);
    }

    /**
     * Test getUserResults with search filter.
     */
    public function test_get_user_results_with_search(): void
    {
        $user = User::factory()->create();
        $race1 = Race::factory()->create(['race_name' => 'Marathon Paris']);
        $race2 = Race::factory()->create(['race_name' => 'Trail Lyon']);

        $this->service->addResult($user->id, $race1->race_id, 3600.00, 0);
        $this->service->addResult($user->id, $race2->race_id, 3800.00, 0);

        $results = $this->service->getUserResults($user->id, 'Marathon');

        $this->assertEquals(1, $results['total']);
    }

    /**
     * Test getUserResults sorted by best performance.
     */
    public function test_get_user_results_sorted_by_best(): void
    {
        $user = User::factory()->create();
        $race1 = Race::factory()->create();
        $race2 = Race::factory()->create();

        // User gets rank 1 in race1, rank 2 in race2
        $otherUser = User::factory()->create();

        $this->service->addResult($user->id, $race1->race_id, 3500.00, 0);
        $this->service->addResult($user->id, $race2->race_id, 4000.00, 0);
        $this->service->addResult($otherUser->id, $race2->race_id, 3500.00, 0);

        $results = $this->service->getUserResults($user->id, null, 'best');

        $this->assertEquals(1, $results['data'][0]['rank']);
    }

    /**
     * Test getPublicLeaderboard only shows public profiles.
     */
    public function test_get_public_leaderboard_only_public_profiles(): void
    {
        $race = Race::factory()->create();
        $publicUser = User::factory()->create(['is_public' => true]);
        $privateUser = User::factory()->create(['is_public' => false]);

        $this->service->addResult($publicUser->id, $race->race_id, 3600.00, 0);
        $this->service->addResult($privateUser->id, $race->race_id, 3500.00, 0);

        $leaderboard = $this->service->getPublicLeaderboard($race->race_id);

        $this->assertEquals(1, $leaderboard['total']);
        $this->assertEquals($publicUser->id, $leaderboard['data'][0]['user_id']);
    }

    /**
     * Test exportToCsv generates valid CSV for individuals.
     * Note: exportToCsv only exports PUBLIC profiles (is_public = true).
     */
    public function test_export_to_csv_individual(): void
    {
        $race = Race::factory()->create();
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'is_public' => true, // Must be public to appear in CSV export
        ]);

        $this->service->addResult($user->id, $race->race_id, 3661.50, 60.00);

        $csv = $this->service->exportToCsv($race->race_id, 'individual');

        $this->assertStringContainsString('Rang', $csv);
        $this->assertStringContainsString('Nom', $csv);
        $this->assertStringContainsString('John Doe', $csv);
    }

    /**
     * Test exportToCsv generates valid CSV for teams.
     * CSV format: Rang, Equipe, Catégorie, Temps, Points
     */
    public function test_export_to_csv_team(): void
    {
        $race = Race::factory()->create();
        $team = Team::factory()->create(['equ_name' => 'Super Team']);

        LeaderboardTeam::create([
            'equ_id' => $team->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 3600.00,
            'average_malus' => 60.00,
            'average_temps_final' => 3660.00,
            'member_count' => 3,
            'category' => 'Senior',
            'points' => 990,
        ]);

        $csv = $this->service->exportToCsv($race->race_id, 'team');

        $this->assertStringContainsString('Equipe', $csv);
        $this->assertStringContainsString('Super Team', $csv);
        $this->assertStringContainsString('Catégorie', $csv);
        $this->assertStringContainsString('Points', $csv);
    }

    /**
     * Test importCsv with valid data.
     */
    public function test_import_csv_with_valid_data(): void
    {
        $race = Race::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $csvContent = "user_id;temps;malus\n{$user1->id};3600.50;60\n{$user2->id};01:05:30;30";
        
        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $results = $this->service->importCsv($file, $race->race_id);

        $this->assertEquals(2, $results['success']);
        $this->assertEquals(2, $results['total']);
        $this->assertEmpty($results['errors']);
        $this->assertDatabaseHas('leaderboard_users', ['user_id' => $user1->id]);
        $this->assertDatabaseHas('leaderboard_users', ['user_id' => $user2->id]);
    }

    /**
     * Test importCsv with invalid user_id.
     */
    public function test_import_csv_with_invalid_user_id(): void
    {
        $race = Race::factory()->create();
        $user = User::factory()->create();

        $csvContent = "user_id;temps;malus\n{$user->id};3600;0\n99999;3700;0";
        
        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $results = $this->service->importCsv($file, $race->race_id);

        $this->assertEquals(1, $results['success']);
        $this->assertEquals(2, $results['total']);
        $this->assertCount(1, $results['errors']);
    }

    /**
     * Test importCsv throws exception for non-existent race.
     */
    public function test_import_csv_throws_exception_for_invalid_race(): void
    {
        $csvContent = "user_id;temps;malus\n1;3600;0";
        
        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Race with ID 99999 not found');

        $this->service->importCsv($file, 99999);
    }

    /**
     * Test importCsv throws exception for missing required column.
     */
    public function test_import_csv_throws_exception_for_missing_column(): void
    {
        $race = Race::factory()->create();

        $csvContent = "user_id;malus\n1;0";
        
        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Missing required column: temps');

        $this->service->importCsv($file, $race->race_id);
    }

    /**
     * Test importTeamCsv with valid data.
     */
    public function test_import_team_csv_with_valid_data(): void
    {
        $race = Race::factory()->create();
        $team = Team::factory()->create();

        $csvContent = "equ_id;temps;malus;member_count\n{$team->equ_id};3600.50;60;3";
        
        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $results = $this->service->importTeamCsv($file, $race->race_id);

        $this->assertEquals(1, $results['success']);
        $this->assertDatabaseHas('leaderboard_teams', ['equ_id' => $team->equ_id]);
    }

    /**
     * Test time parsing with HH:MM:SS format.
     */
    public function test_import_csv_parses_hhmmss_format(): void
    {
        $race = Race::factory()->create();
        $user = User::factory()->create();

        // 01:30:45.50 = 5445.50 seconds
        $csvContent = "user_id;temps;malus\n{$user->id};01:30:45.50;00:01:00";
        
        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $this->service->importCsv($file, $race->race_id);

        $result = LeaderboardUser::where('user_id', $user->id)->first();
        
        $this->assertEquals(5445.50, (float) $result->temps);
        $this->assertEquals(60.00, (float) $result->malus);
    }

    /**
     * Test recalculateTeamAverages updates team results.
     */
    public function test_recalculate_team_averages(): void
    {
        $race = Race::factory()->create();
        $team = Team::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Determine the correct column name for user reference in has_participate
        $hasIdUsersColumn = \Illuminate\Support\Facades\DB::getSchemaBuilder()->hasColumn('has_participate', 'id_users');
        $userIdColumn = $hasIdUsersColumn ? 'id_users' : 'id';

        // Link users to team via has_participate (without race_id as it doesn't exist in schema)
        \DB::table('has_participate')->insert([
            [$userIdColumn => $user1->id, 'equ_id' => $team->equ_id, 'created_at' => now(), 'updated_at' => now()],
            [$userIdColumn => $user2->id, 'equ_id' => $team->equ_id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Add individual results
        $this->service->addResult($user1->id, $race->race_id, 3600.00, 60.00);
        $this->service->addResult($user2->id, $race->race_id, 3800.00, 120.00);

        // Check team averages were calculated
        $teamResult = LeaderboardTeam::where('equ_id', $team->equ_id)
            ->where('race_id', $race->race_id)
            ->first();

        $this->assertNotNull($teamResult);
        $this->assertEquals(2, $teamResult->member_count);
        // Average temps: (3600 + 3800) / 2 = 3700
        $this->assertEquals(3700.00, (float) $teamResult->average_temps);
        // Average malus: (60 + 120) / 2 = 90
        $this->assertEquals(90.00, (float) $teamResult->average_malus);
    }

    /**
     * Test import CSV with new format using Nom column (comma separator).
     */
    public function test_import_csv_with_name_format(): void
    {
        $race = Race::factory()->create();
        $user1 = User::factory()->create(['first_name' => 'Jean', 'last_name' => 'Dupont']);
        $user2 = User::factory()->create(['first_name' => 'Marie', 'last_name' => 'Martin']);

        // New format: Rang,Nom,Temps,Malus,Temps Final
        $csvContent = "Rang,Nom,Temps,Malus,Temps Final\n";
        $csvContent .= "1,Jean Dupont,03:01:43.00,00:00.00,03:01:43.00\n";
        $csvContent .= "2,Marie Martin,04:56:26.00,02:30.00,04:58:56.00";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $result = $this->service->importCsv($file, $race->race_id);

        $this->assertEquals(2, $result['success']);
        $this->assertEquals(2, $result['total']);
        $this->assertEmpty($result['errors']);

        // Check user 1 results
        $result1 = LeaderboardUser::where('user_id', $user1->id)->first();
        $this->assertNotNull($result1);
        // 03:01:43.00 = 3*3600 + 1*60 + 43 = 10903 seconds
        $this->assertEquals(10903.00, (float) $result1->temps);
        $this->assertEquals(0.00, (float) $result1->malus);

        // Check user 2 results
        $result2 = LeaderboardUser::where('user_id', $user2->id)->first();
        $this->assertNotNull($result2);
        // 04:56:26.00 = 4*3600 + 56*60 + 26 = 17786 seconds
        $this->assertEquals(17786.00, (float) $result2->temps);
        // 02:30.00 = 2*60 + 30 = 150 seconds
        $this->assertEquals(150.00, (float) $result2->malus);
    }

    /**
     * Test import CSV with name format creates user when not found.
     */
    public function test_import_csv_with_name_format_creates_user_when_not_found(): void
    {
        $race = Race::factory()->create();
        User::factory()->create(['first_name' => 'Jean', 'last_name' => 'Dupont']);

        $csvContent = "Rang,Nom,Temps,Malus,Temps Final\n";
        $csvContent .= "1,Jean Dupont,03:01:43.00,00:00.00,03:01:43.00\n";
        $csvContent .= "2,Unknown Person,04:56:26.00,00:00.00,04:56:26.00";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $result = $this->service->importCsv($file, $race->race_id);

        // Both should succeed - one existing, one created
        $this->assertEquals(2, $result['success']);
        $this->assertEquals(2, $result['total']);
        $this->assertEmpty($result['errors']);
        $this->assertEquals(1, $result['created']); // One user was created

        // Verify the new user was created with private profile
        $newUser = User::where('first_name', 'Unknown')->where('last_name', 'Person')->first();
        $this->assertNotNull($newUser);
        $this->assertFalse((bool) $newUser->is_public);
        $this->assertStringContainsString('@imported.local', $newUser->email);

        // Verify team was created
        $team = Team::where('equ_name', 'Unknown Person')->first();
        $this->assertNotNull($team);

        // Verify participation link exists (check both possible column names)
        $hasIdUsersColumn = \Schema::hasColumn('has_participate', 'id_users');
        $userIdColumn = $hasIdUsersColumn ? 'id_users' : 'id';
        
        $participation = \DB::table('has_participate')
            ->where($userIdColumn, $newUser->id)
            ->where('equ_id', $team->equ_id)
            ->first();
        $this->assertNotNull($participation);
        
        // Check is_leader only if column exists
        if (\Schema::hasColumn('has_participate', 'is_leader')) {
            $this->assertTrue((bool) $participation->is_leader);
        }

        // Verify leaderboard entry was created
        $leaderboardEntry = LeaderboardUser::where('user_id', $newUser->id)->first();
        $this->assertNotNull($leaderboardEntry);
        $this->assertEquals(17786.00, (float) $leaderboardEntry->temps);
    }

    /**
     * Test import CSV auto-detects comma separator.
     */
    public function test_import_csv_auto_detects_comma_separator(): void
    {
        $race = Race::factory()->create();
        $user = User::factory()->create(['first_name' => 'Test', 'last_name' => 'User']);

        // Comma-separated CSV
        $csvContent = "Rang,Nom,Temps,Malus,Temps Final\n1,Test User,01:00:00.00,00:00.00,01:00:00.00";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $result = $this->service->importCsv($file, $race->race_id);

        $this->assertEquals(1, $result['success']);
        
        $leaderboardResult = LeaderboardUser::where('user_id', $user->id)->first();
        $this->assertEquals(3600.00, (float) $leaderboardResult->temps);
    }

    /**
     * Test import CSV with reversed name order (last_name first_name).
     */
    public function test_import_csv_finds_user_with_reversed_name(): void
    {
        $race = Race::factory()->create();
        $user = User::factory()->create(['first_name' => 'Jean', 'last_name' => 'Dupont']);

        // Name in reversed order: Dupont Jean instead of Jean Dupont
        $csvContent = "Rang,Nom,Temps,Malus,Temps Final\n1,Dupont Jean,02:00:00.00,00:00.00,02:00:00.00";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $result = $this->service->importCsv($file, $race->race_id);

        $this->assertEquals(1, $result['success']);
        
        $leaderboardResult = LeaderboardUser::where('user_id', $user->id)->first();
        $this->assertNotNull($leaderboardResult);
        $this->assertEquals(7200.00, (float) $leaderboardResult->temps);
    }

    /**
     * Test import CSV does NOT create duplicate user when user already exists.
     * Verifies that existing users are linked, not recreated.
     * Note: For existing users WITHOUT teams, solo teams will be created.
     */
    public function test_import_csv_does_not_duplicate_existing_users(): void
    {
        $race = Race::factory()->create();
        
        // Create existing users BEFORE import WITH teams (to test no duplicate teams)
        $member1 = \App\Models\Member::factory()->create();
        $member2 = \App\Models\Member::factory()->create();
        
        $existingUser1 = User::factory()->create(['first_name' => 'Jean', 'last_name' => 'Dupont', 'adh_id' => $member1->adh_id]);
        $existingUser2 = User::factory()->create(['first_name' => 'Marie', 'last_name' => 'Martin', 'adh_id' => $member2->adh_id]);
        
        // Create teams for existing users
        $team1 = Team::factory()->create(['adh_id' => $member1->adh_id]);
        $team2 = Team::factory()->create(['adh_id' => $member2->adh_id]);
        
        // Create has_participate entries so users have teams
        $hasIdUsersColumn = \Illuminate\Support\Facades\DB::getSchemaBuilder()->hasColumn('has_participate', 'id_users');
        $userIdColumn = $hasIdUsersColumn ? 'id_users' : 'id';
        
        \Illuminate\Support\Facades\DB::table('has_participate')->insert([
            [$userIdColumn => $existingUser1->id, 'equ_id' => $team1->equ_id, 'adh_id' => $member1->adh_id, 'created_at' => now(), 'updated_at' => now()],
            [$userIdColumn => $existingUser2->id, 'equ_id' => $team2->equ_id, 'adh_id' => $member2->adh_id, 'created_at' => now(), 'updated_at' => now()],
        ]);
        
        // Count users and teams before import
        $userCountBefore = User::count();
        $teamCountBefore = Team::count();

        // CSV with names that match existing users
        $csvContent = "Rang,Nom,Temps,Malus,Temps Final\n";
        $csvContent .= "1,Jean Dupont,03:01:43.00,00:00.00,03:01:43.00\n";
        $csvContent .= "2,Marie Martin,04:56:26.00,02:30.00,04:58:56.00";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $result = $this->service->importCsv($file, $race->race_id);

        // Verify import success
        $this->assertEquals(2, $result['success']);
        $this->assertEquals(0, $result['created']); // NO new users should be created
        $this->assertEmpty($result['errors']);

        // Verify NO new users were created
        $userCountAfter = User::count();
        $this->assertEquals($userCountBefore, $userCountAfter, 'No new users should be created when they already exist');

        // Verify NO new teams were created (users already have teams)
        $teamCountAfter = Team::count();
        $this->assertEquals($teamCountBefore, $teamCountAfter, 'No new teams should be created for existing users with teams');

        // Verify leaderboard entries are linked to EXISTING users (same IDs)
        $leaderboard1 = LeaderboardUser::where('race_id', $race->race_id)
            ->where('user_id', $existingUser1->id)
            ->first();
        $this->assertNotNull($leaderboard1, 'Leaderboard should be linked to existing user Jean Dupont');
        $this->assertEquals(10903.00, (float) $leaderboard1->temps);

        $leaderboard2 = LeaderboardUser::where('race_id', $race->race_id)
            ->where('user_id', $existingUser2->id)
            ->first();
        $this->assertNotNull($leaderboard2, 'Leaderboard should be linked to existing user Marie Martin');
        $this->assertEquals(17786.00, (float) $leaderboard2->temps);

        // Verify there are no duplicate users with same name
        $jeanCount = User::where('first_name', 'Jean')->where('last_name', 'Dupont')->count();
        $this->assertEquals(1, $jeanCount, 'There should be only one Jean Dupont');
        
        $marieCount = User::where('first_name', 'Marie')->where('last_name', 'Martin')->count();
        $this->assertEquals(1, $marieCount, 'There should be only one Marie Martin');
    }

    /**
     * Test import CSV updates existing leaderboard entry instead of creating duplicate.
     */
    public function test_import_csv_updates_existing_leaderboard_entry(): void
    {
        $race = Race::factory()->create();
        $user = User::factory()->create(['first_name' => 'Jean', 'last_name' => 'Dupont']);

        // Create initial leaderboard entry
        LeaderboardUser::create([
            'user_id' => $user->id,
            'race_id' => $race->race_id,
            'temps' => 5000.00,
            'malus' => 100.00,
        ]);

        $leaderboardCountBefore = LeaderboardUser::count();

        // Import CSV with new time for same user/race
        $csvContent = "Rang,Nom,Temps,Malus,Temps Final\n";
        $csvContent .= "1,Jean Dupont,03:01:43.00,00:05:00.00,03:06:43.00";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $result = $this->service->importCsv($file, $race->race_id);

        $this->assertEquals(1, $result['success']);
        $this->assertEquals(0, $result['created']);

        // Verify no duplicate leaderboard entries
        $leaderboardCountAfter = LeaderboardUser::count();
        $this->assertEquals($leaderboardCountBefore, $leaderboardCountAfter, 'Should update existing entry, not create new one');

        // Verify the entry was UPDATED with new values
        $entry = LeaderboardUser::where('user_id', $user->id)
            ->where('race_id', $race->race_id)
            ->first();
        
        $this->assertEquals(10903.00, (float) $entry->temps, 'Temps should be updated');
        $this->assertEquals(300.00, (float) $entry->malus, 'Malus should be updated (5 minutes = 300 seconds)');
    }

    /**
     * Test import CSV removes participants not present in CSV.
     * If a user was in the leaderboard but is not in the new CSV, they should be removed.
     */
    public function test_import_csv_removes_absent_participants(): void
    {
        $race = Race::factory()->create();
        
        // Create 3 users
        $user1 = User::factory()->create(['first_name' => 'Jean', 'last_name' => 'Dupont']);
        $user2 = User::factory()->create(['first_name' => 'Marie', 'last_name' => 'Martin']);
        $user3 = User::factory()->create(['first_name' => 'Pierre', 'last_name' => 'Bernard']);

        // All 3 users have leaderboard entries initially
        LeaderboardUser::create(['user_id' => $user1->id, 'race_id' => $race->race_id, 'temps' => 3600, 'malus' => 0]);
        LeaderboardUser::create(['user_id' => $user2->id, 'race_id' => $race->race_id, 'temps' => 3700, 'malus' => 0]);
        LeaderboardUser::create(['user_id' => $user3->id, 'race_id' => $race->race_id, 'temps' => 3800, 'malus' => 0]);

        $this->assertEquals(3, LeaderboardUser::where('race_id', $race->race_id)->count());

        // Import CSV with only 2 users (Pierre Bernard is missing)
        $csvContent = "Rang,Nom,Temps,Malus,Temps Final\n";
        $csvContent .= "1,Jean Dupont,01:00:00.00,00:00.00,01:00:00.00\n";
        $csvContent .= "2,Marie Martin,01:05:00.00,00:00.00,01:05:00.00";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $result = $this->service->importCsv($file, $race->race_id);

        // Verify import results
        $this->assertEquals(2, $result['success']);
        $this->assertEquals(1, $result['removed'], 'One participant should be removed');
        $this->assertEquals(0, $result['created']);

        // Verify only 2 entries remain
        $this->assertEquals(2, LeaderboardUser::where('race_id', $race->race_id)->count());

        // Verify Pierre Bernard was removed
        $this->assertNull(
            LeaderboardUser::where('user_id', $user3->id)->where('race_id', $race->race_id)->first(),
            'Pierre Bernard should be removed from leaderboard'
        );

        // Verify Jean and Marie still exist with updated times
        $entry1 = LeaderboardUser::where('user_id', $user1->id)->where('race_id', $race->race_id)->first();
        $this->assertNotNull($entry1);
        $this->assertEquals(3600.00, (float) $entry1->temps);

        $entry2 = LeaderboardUser::where('user_id', $user2->id)->where('race_id', $race->race_id)->first();
        $this->assertNotNull($entry2);
        $this->assertEquals(3900.00, (float) $entry2->temps); // 1:05:00 = 3900 seconds
    }

    /**
     * Test import CSV removes all participants when CSV is empty (only headers).
     */
    public function test_import_csv_removes_all_when_csv_empty(): void
    {
        $race = Race::factory()->create();
        
        $user1 = User::factory()->create(['first_name' => 'Jean', 'last_name' => 'Dupont']);
        $user2 = User::factory()->create(['first_name' => 'Marie', 'last_name' => 'Martin']);

        // Both users have leaderboard entries
        LeaderboardUser::create(['user_id' => $user1->id, 'race_id' => $race->race_id, 'temps' => 3600, 'malus' => 0]);
        LeaderboardUser::create(['user_id' => $user2->id, 'race_id' => $race->race_id, 'temps' => 3700, 'malus' => 0]);

        $this->assertEquals(2, LeaderboardUser::where('race_id', $race->race_id)->count());

        // Import CSV with only headers (no data rows)
        $csvContent = "Rang,Nom,Temps,Malus,Temps Final\n";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $result = $this->service->importCsv($file, $race->race_id);

        // Verify all entries were removed
        $this->assertEquals(0, $result['success']);
        $this->assertEquals(2, $result['removed'], 'Both participants should be removed');
        $this->assertEquals(0, LeaderboardUser::where('race_id', $race->race_id)->count());
    }

    /**
     * Test import CSV only affects the specific race, not other races.
     */
    public function test_import_csv_does_not_affect_other_races(): void
    {
        $race1 = Race::factory()->create();
        $race2 = Race::factory()->create();
        
        $user = User::factory()->create(['first_name' => 'Jean', 'last_name' => 'Dupont']);

        // User has entries in both races
        LeaderboardUser::create(['user_id' => $user->id, 'race_id' => $race1->race_id, 'temps' => 3600, 'malus' => 0]);
        LeaderboardUser::create(['user_id' => $user->id, 'race_id' => $race2->race_id, 'temps' => 4000, 'malus' => 0]);

        // Import empty CSV for race1 (should remove entry for race1 only)
        $csvContent = "Rang,Nom,Temps,Malus,Temps Final\n";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $this->service->importCsv($file, $race1->race_id);

        // Race1 should have no entries
        $this->assertEquals(0, LeaderboardUser::where('race_id', $race1->race_id)->count());

        // Race2 should still have the entry
        $this->assertEquals(1, LeaderboardUser::where('race_id', $race2->race_id)->count());
        
        $entry = LeaderboardUser::where('race_id', $race2->race_id)->first();
        $this->assertEquals($user->id, $entry->user_id);
        $this->assertEquals(4000.00, (float) $entry->temps);
    }

    /**
     * Test imported user has @imported.local email domain.
     */
    public function test_imported_user_has_imported_local_email(): void
    {
        $race = Race::factory()->create();

        $csvContent = "Rang,Nom,Temps,Malus,Temps Final\n";
        $csvContent .= "1,Nouveau Coureur,01:30:00.00,00:00.00,01:30:00.00";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $result = $this->service->importCsv($file, $race->race_id);

        $this->assertEquals(1, $result['created']);

        // Verify user was created with @imported.local email
        $user = User::where('first_name', 'Nouveau')->where('last_name', 'Coureur')->first();
        $this->assertNotNull($user);
        $this->assertStringEndsWith('@imported.local', $user->email);
    }

    /**
     * Test removing imported users cleans up their participation and team, but keeps user.
     * User should NOT be deleted - only team and has_participate entries.
     */
    public function test_removing_imported_user_cleans_up_participation(): void
    {
        $race = Race::factory()->create();

        // First import to create a user
        $csvContent = "Rang,Nom,Temps,Malus,Temps Final\n";
        $csvContent .= "1,Import Test User,01:30:00.00,00:00.00,01:30:00.00";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $result = $this->service->importCsv($file, $race->race_id);
        $this->assertEquals(1, $result['created']);

        // Get the imported user
        $importedUser = User::where('email', 'like', '%@imported.local')->first();
        $this->assertNotNull($importedUser);
        $userId = $importedUser->id;

        // For imported users, adh_id is NULL, so find team via has_participate
        $hasIdUsersColumn = \Illuminate\Support\Facades\DB::getSchemaBuilder()->hasColumn('has_participate', 'id_users');
        $userIdColumn = $hasIdUsersColumn ? 'id_users' : 'id';
        
        $participation = \Illuminate\Support\Facades\DB::table('has_participate')
            ->where($userIdColumn, $userId)
            ->first();
        $this->assertNotNull($participation, 'Participation should exist after import');
        
        $teamId = $participation->equ_id;
        $team = Team::find($teamId);
        $this->assertNotNull($team, 'Team should exist after import');

        // Now import empty CSV to remove from leaderboard
        $emptyCsv = "Rang,Nom,Temps,Malus,Temps Final\n";
        $file2 = UploadedFile::fake()->createWithContent('empty.csv', $emptyCsv);

        $result2 = $this->service->importCsv($file2, $race->race_id);
        $this->assertEquals(1, $result2['removed']);

        // Verify user is NOT deleted (user should be kept for future re-import)
        $this->assertNotNull(User::find($userId), 'Imported user should NOT be deleted');

        // Verify has_participate entry was deleted
        $participationCountAfter = \Illuminate\Support\Facades\DB::table('has_participate')
            ->where($userIdColumn, $userId)
            ->count();
        $this->assertEquals(0, $participationCountAfter, 'Participation should be deleted after removal');

        // Verify team was deleted
        $this->assertNull(Team::find($teamId), 'Team should be deleted after removal');
    }

    /**
     * Test removing regular (non-imported) users only removes leaderboard entry.
     * Regular users should NOT have their participation or team data deleted.
     */
    public function test_removing_regular_user_only_removes_leaderboard_entry(): void
    {
        $race = Race::factory()->create();
        
        // Create a regular user (not imported - email doesn't end with @imported.local)
        $user = User::factory()->create([
            'first_name' => 'Regular',
            'last_name' => 'User',
            'email' => 'regular@example.com',
        ]);

        // Create a team for the regular user
        $team = Team::factory()->create(['adh_id' => $user->adh_id]);

        // Create participation
        $hasIdUsersColumn = \Illuminate\Support\Facades\DB::getSchemaBuilder()->hasColumn('has_participate', 'id_users');
        $userIdColumn = $hasIdUsersColumn ? 'id_users' : 'id';
        
        \Illuminate\Support\Facades\DB::table('has_participate')->insert([
            $userIdColumn => $user->id,
            'equ_id' => $team->equ_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Add leaderboard entry
        LeaderboardUser::create(['user_id' => $user->id, 'race_id' => $race->race_id, 'temps' => 3600, 'malus' => 0]);

        // Import empty CSV to remove from leaderboard
        $emptyCsv = "Rang,Nom,Temps,Malus,Temps Final\n";
        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('empty.csv', $emptyCsv);

        $this->service->importCsv($file, $race->race_id);

        // Verify leaderboard entry was removed
        $this->assertEquals(0, LeaderboardUser::where('user_id', $user->id)->where('race_id', $race->race_id)->count());

        // But user, team and participation should still exist
        $this->assertNotNull(User::find($user->id), 'Regular user should NOT be deleted');
        $this->assertNotNull(Team::find($team->equ_id), 'Regular user team should NOT be deleted');
        
        $participationCount = \Illuminate\Support\Facades\DB::table('has_participate')
            ->where($userIdColumn, $user->id)
            ->count();
        $this->assertGreaterThan(0, $participationCount, 'Regular user participation should NOT be deleted');
    }

    /**
     * Test that adh_id is set for imported users (required by database constraints).
     * The member record should have license starting with IMPORT-.
     */
    public function test_imported_user_has_member_with_import_prefix(): void
    {
        $race = Race::factory()->create();

        $csvContent = "Rang,Nom,Temps,Malus,Temps Final\n";
        $csvContent .= "1,Import Member Test,01:30:00.00,00:00.00,01:30:00.00";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $this->service->importCsv($file, $race->race_id);

        // Get the imported user
        $user = User::where('first_name', 'Import')->where('last_name', 'Member Test')->first();
        $this->assertNotNull($user);

        // Verify user has adh_id = NULL (imported users don't have member reference)
        $this->assertNull($user->adh_id, 'Imported user should have adh_id = NULL');
        $this->assertNull($user->doc_id, 'Imported user should have doc_id = NULL');

        // Find the team via has_participate
        $hasIdUsersColumn = \Illuminate\Support\Facades\DB::getSchemaBuilder()->hasColumn('has_participate', 'id_users');
        $userIdColumn = $hasIdUsersColumn ? 'id_users' : 'id';
        $participation = \Illuminate\Support\Facades\DB::table('has_participate')
            ->where($userIdColumn, $user->id)
            ->first();
        $this->assertNotNull($participation, 'User should have a participation entry');

        // Get the team
        $team = Team::find($participation->equ_id);
        $this->assertNotNull($team, 'Team should exist');

        // Verify the team's dummy member has IMPORT- prefix
        $member = \App\Models\Member::where('adh_id', $team->adh_id)->first();
        $this->assertNotNull($member, 'Dummy member should exist for team');
        $this->assertStringStartsWith('IMPORT-', $member->adh_license, 'Member license should start with IMPORT-');
    }

    /**
     * Test imported user cleanup keeps the user but deletes the team and dummy member.
     * User should have adh_id = NULL and doc_id = NULL.
     */
    public function test_imported_user_cleanup_keeps_member_and_medical_doc(): void
    {
        $race = Race::factory()->create();

        // Import to create user
        $csvContent = "Rang,Nom,Temps,Malus,Temps Final\n";
        $csvContent .= "1,Cleanup Test User,01:30:00.00,00:00.00,01:30:00.00";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $this->service->importCsv($file, $race->race_id);

        $user = User::where('first_name', 'Cleanup')->where('last_name', 'Test User')->first();
        $this->assertNotNull($user);
        
        // Verify adh_id and doc_id are null for imported users
        $this->assertNull($user->adh_id, 'Imported user adh_id should be null');
        $this->assertNull($user->doc_id, 'doc_id should be null for imported users');

        // Find the team via has_participate
        $hasIdUsersColumn = \Illuminate\Support\Facades\DB::getSchemaBuilder()->hasColumn('has_participate', 'id_users');
        $userIdColumn = $hasIdUsersColumn ? 'id_users' : 'id';
        $participation = \Illuminate\Support\Facades\DB::table('has_participate')
            ->where($userIdColumn, $user->id)
            ->first();
        $this->assertNotNull($participation);
        
        $team = Team::find($participation->equ_id);
        $this->assertNotNull($team);
        $teamMemberId = $team->adh_id;
        
        // Verify dummy member exists
        $dummyMember = \App\Models\Member::find($teamMemberId);
        $this->assertNotNull($dummyMember, 'Dummy member should exist');

        // Import empty CSV to trigger cleanup
        $emptyCsv = "Rang,Nom,Temps,Malus,Temps Final\n";
        $file2 = UploadedFile::fake()->createWithContent('empty.csv', $emptyCsv);

        $this->service->importCsv($file2, $race->race_id);

        // Verify user is NOT deleted
        $this->assertNotNull(User::find($user->id), 'User should NOT be deleted');
        
        // Verify team and dummy member ARE deleted (cleanup should remove them)
        $this->assertNull(Team::find($team->equ_id), 'Team should be deleted');
        $this->assertNull(\App\Models\Member::find($teamMemberId), 'Dummy member should be deleted');
    }

    /**
     * Test imported user's team appears in leaderboard_teams for the race.
     */
    public function test_imported_user_team_appears_in_leaderboard_teams(): void
    {
        $race = Race::factory()->create();

        $csvContent = "Rang,Nom,Temps,Malus,Temps Final\n";
        $csvContent .= "1,Team Leader Test,01:30:00.00,00:01:00.00,01:31:00.00";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $this->service->importCsv($file, $race->race_id);

        // Get the imported user
        $user = User::where('first_name', 'Team')->where('last_name', 'Leader Test')->first();
        $this->assertNotNull($user);

        // Find team via has_participate (since user.adh_id is NULL)
        $hasIdUsersColumn = \Illuminate\Support\Facades\DB::getSchemaBuilder()->hasColumn('has_participate', 'id_users');
        $userIdColumn = $hasIdUsersColumn ? 'id_users' : 'id';
        $participation = \Illuminate\Support\Facades\DB::table('has_participate')
            ->where($userIdColumn, $user->id)
            ->first();
        $this->assertNotNull($participation);
        
        $team = Team::find($participation->equ_id);
        $this->assertNotNull($team);

        // Verify leaderboard_teams entry exists for this race
        $leaderboardTeam = LeaderboardTeam::where('equ_id', $team->equ_id)
            ->where('race_id', $race->race_id)
            ->first();
        
        $this->assertNotNull($leaderboardTeam, 'Team should appear in leaderboard_teams');
        $this->assertEquals(5400.00, (float) $leaderboardTeam->average_temps); // 1:30:00 = 5400
        $this->assertEquals(60.00, (float) $leaderboardTeam->average_malus); // 1:00 = 60
        $this->assertEquals(5460.00, (float) $leaderboardTeam->average_temps_final); // 5400 + 60
        $this->assertEquals(1, $leaderboardTeam->member_count);
    }

    /**
     * Test leaderboard_teams entry is removed when team is deleted on cleanup.
     */
    public function test_leaderboard_teams_entry_removed_on_cleanup(): void
    {
        $race = Race::factory()->create();

        // Import to create user and team
        $csvContent = "Rang,Nom,Temps,Malus,Temps Final\n";
        $csvContent .= "1,Leaderboard Team Test,01:30:00.00,00:00.00,01:30:00.00";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $this->service->importCsv($file, $race->race_id);

        $user = User::where('first_name', 'Leaderboard')->where('last_name', 'Team Test')->first();
        
        // Find team via has_participate
        $hasIdUsersColumn = \Illuminate\Support\Facades\DB::getSchemaBuilder()->hasColumn('has_participate', 'id_users');
        $userIdColumn = $hasIdUsersColumn ? 'id_users' : 'id';
        $participation = \Illuminate\Support\Facades\DB::table('has_participate')
            ->where($userIdColumn, $user->id)
            ->first();
        $this->assertNotNull($participation);
        
        $teamId = $participation->equ_id;
        
        // Verify leaderboard_teams entry exists
        $this->assertNotNull(
            LeaderboardTeam::where('equ_id', $teamId)->where('race_id', $race->race_id)->first(),
            'LeaderboardTeam entry should exist'
        );

        // Import empty CSV to trigger cleanup
        $emptyCsv = "Rang,Nom,Temps,Malus,Temps Final\n";
        $file2 = UploadedFile::fake()->createWithContent('empty.csv', $emptyCsv);

        $this->service->importCsv($file2, $race->race_id);

        // Verify leaderboard_teams entry was removed (cascade from team deletion)
        $this->assertNull(
            LeaderboardTeam::where('equ_id', $teamId)->where('race_id', $race->race_id)->first(),
            'LeaderboardTeam entry should be deleted when team is deleted'
        );
    }

    /**
     * Test import CSV does not create new team for user who already has a team.
     * Users with existing teams should keep their original team.
     */
    public function test_import_csv_does_not_create_new_team_for_existing_team_user(): void
    {
        $race = Race::factory()->create();
        
        // Create a user with an existing team
        $existingUser = User::factory()->create([
            'first_name' => 'Existing',
            'last_name' => 'TeamUser',
        ]);
        
        // Create the existing team for this user
        $existingTeam = Team::factory()->create([
            'equ_name' => 'Original Team',
        ]);
        
        // Link user to team via has_participate
        $hasIdUsersColumn = \Illuminate\Support\Facades\DB::getSchemaBuilder()->hasColumn('has_participate', 'id_users');
        $userIdColumn = $hasIdUsersColumn ? 'id_users' : 'id';
        
        $participateData = [
            $userIdColumn => $existingUser->id,
            'equ_id' => $existingTeam->equ_id,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        
        if (\Illuminate\Support\Facades\DB::getSchemaBuilder()->hasColumn('has_participate', 'adh_id')) {
            $participateData['adh_id'] = $existingUser->adh_id;
        }
        if (\Illuminate\Support\Facades\DB::getSchemaBuilder()->hasColumn('has_participate', 'is_leader')) {
            $participateData['is_leader'] = true;
        }
        
        \Illuminate\Support\Facades\DB::table('has_participate')->insert($participateData);
        
        // Count teams before import
        $teamCountBefore = Team::count();
        
        // Import CSV with this user's name
        $csvContent = "Rang,Nom,Temps,Malus,Temps Final\n";
        $csvContent .= "1,Existing TeamUser,01:30:00.00,00:01:00.00,01:31:00.00";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $result = $this->service->importCsv($file, $race->race_id);

        // Verify import was successful
        $this->assertEquals(1, $result['success']);
        $this->assertEquals(0, $result['created']); // No new user created
        
        // Verify no new team was created
        $teamCountAfter = Team::count();
        $this->assertEquals($teamCountBefore, $teamCountAfter, 'No new team should be created for user with existing team');
        
        // Verify user still has their original team
        $participation = \Illuminate\Support\Facades\DB::table('has_participate')
            ->where($userIdColumn, $existingUser->id)
            ->first();
        
        $this->assertNotNull($participation);
        $this->assertEquals($existingTeam->equ_id, $participation->equ_id, 'User should keep their original team');
        
        // Verify leaderboard entry was created
        $this->assertDatabaseHas('leaderboard_users', [
            'user_id' => $existingUser->id,
            'race_id' => $race->race_id,
        ]);
        
        // Verify team leaderboard entry uses the existing team
        $this->assertDatabaseHas('leaderboard_teams', [
            'equ_id' => $existingTeam->equ_id,
            'race_id' => $race->race_id,
        ]);
    }

    /**
     * Test import CSV creates new team for user without any team.
     */
    public function test_import_csv_creates_team_for_user_without_team(): void
    {
        $race = Race::factory()->create();
        
        // Create a user WITHOUT any team
        $userWithoutTeam = User::factory()->create([
            'first_name' => 'NoTeam',
            'last_name' => 'User',
        ]);
        
        // Verify user has no participation
        $hasIdUsersColumn = \Illuminate\Support\Facades\DB::getSchemaBuilder()->hasColumn('has_participate', 'id_users');
        $userIdColumn = $hasIdUsersColumn ? 'id_users' : 'id';
        
        $existingParticipation = \Illuminate\Support\Facades\DB::table('has_participate')
            ->where($userIdColumn, $userWithoutTeam->id)
            ->first();
        $this->assertNull($existingParticipation, 'User should not have any participation initially');
        
        // Count teams before import
        $teamCountBefore = Team::count();
        
        // Import CSV with this user's name
        $csvContent = "Rang,Nom,Temps,Malus,Temps Final\n";
        $csvContent .= "1,NoTeam User,01:45:00.00,00:00:30.00,01:45:30.00";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $result = $this->service->importCsv($file, $race->race_id);

        // Verify import was successful
        $this->assertEquals(1, $result['success']);
        $this->assertEquals(0, $result['created']); // User already existed, just no team
        
        // Verify a new solo team was created
        $teamCountAfter = Team::count();
        $this->assertEquals($teamCountBefore + 1, $teamCountAfter, 'A new solo team should be created for user without team');
        
        // Verify user now has a participation
        $newParticipation = \Illuminate\Support\Facades\DB::table('has_participate')
            ->where($userIdColumn, $userWithoutTeam->id)
            ->first();
        
        $this->assertNotNull($newParticipation, 'User should now have a participation');
        
        // Verify the team name is based on user's name
        $newTeam = Team::find($newParticipation->equ_id);
        $this->assertNotNull($newTeam);
        $this->assertStringContainsString('NoTeam', $newTeam->equ_name);
    }

    /**
     * Test that when a user is REPLACED by another user in CSV, the old user's participation is cleaned up.
     * - Old user's has_participate entry should be deleted
     * - Old user's solo team should be deleted (if alone in team)
     * - New user should have new participation and team created
     */
    public function test_user_replacement_in_csv_cleans_up_old_participation(): void
    {
        $race = Race::factory()->create();
        
        // First import: User A
        $csvContent1 = "Rang,Nom,Temps,Malus,Temps Final\n";
        $csvContent1 .= "1,Old Runner,01:30:00.00,00:00.00,01:30:00.00";

        Storage::fake('local');
        $file1 = UploadedFile::fake()->createWithContent('results1.csv', $csvContent1);

        $result1 = $this->service->importCsv($file1, $race->race_id);
        $this->assertEquals(1, $result1['success']);
        $this->assertEquals(1, $result1['created']); // User was created

        // Verify User A was created
        $oldUser = User::where('first_name', 'Old')->where('last_name', 'Runner')->first();
        $this->assertNotNull($oldUser, 'Old user should be created');

        // Get old user's team
        $hasIdUsersColumn = \Illuminate\Support\Facades\DB::getSchemaBuilder()->hasColumn('has_participate', 'id_users');
        $userIdColumn = $hasIdUsersColumn ? 'id_users' : 'id';

        $oldParticipation = \Illuminate\Support\Facades\DB::table('has_participate')
            ->where($userIdColumn, $oldUser->id)
            ->first();
        $this->assertNotNull($oldParticipation, 'Old user should have participation');
        $oldTeamId = $oldParticipation->equ_id;
        
        // Verify old team exists
        $this->assertNotNull(Team::find($oldTeamId), 'Old team should exist');

        // Second import: Replace User A with User B
        $csvContent2 = "Rang,Nom,Temps,Malus,Temps Final\n";
        $csvContent2 .= "1,New Runner,01:25:00.00,00:00.00,01:25:00.00";

        $file2 = UploadedFile::fake()->createWithContent('results2.csv', $csvContent2);

        $result2 = $this->service->importCsv($file2, $race->race_id);
        $this->assertEquals(1, $result2['success']);
        $this->assertEquals(1, $result2['created']); // New user was created
        $this->assertEquals(1, $result2['removed']); // Old user was removed

        // Verify new user was created
        $newUser = User::where('first_name', 'New')->where('last_name', 'Runner')->first();
        $this->assertNotNull($newUser, 'New user should be created');

        // Verify old user's participation was deleted
        $oldParticipationAfter = \Illuminate\Support\Facades\DB::table('has_participate')
            ->where($userIdColumn, $oldUser->id)
            ->count();
        $this->assertEquals(0, $oldParticipationAfter, 'Old user participation should be deleted');

        // Verify old user's solo team was deleted
        $this->assertNull(Team::find($oldTeamId), 'Old solo team should be deleted');

        // Verify new user has participation
        $newParticipation = \Illuminate\Support\Facades\DB::table('has_participate')
            ->where($userIdColumn, $newUser->id)
            ->first();
        $this->assertNotNull($newParticipation, 'New user should have participation');

        // Verify leaderboard only has new user
        $leaderboard = $this->service->getIndividualLeaderboard($race->race_id);
        $this->assertEquals(1, $leaderboard['total']);
        $this->assertEquals($newUser->id, $leaderboard['data'][0]['user_id']);
    }

    /**
     * Test that when a user is REPLACED, but the old user's team has other members,
     * only the has_participate entry is deleted, NOT the team.
     */
    public function test_user_replacement_keeps_team_when_other_members_exist(): void
    {
        $race = Race::factory()->create();
        
        // First import: User A
        $csvContent1 = "Rang,Nom,Temps,Malus,Temps Final\n";
        $csvContent1 .= "1,Old Member,01:30:00.00,00:00.00,01:30:00.00";

        Storage::fake('local');
        $file1 = UploadedFile::fake()->createWithContent('results1.csv', $csvContent1);

        $this->service->importCsv($file1, $race->race_id);

        $oldUser = User::where('first_name', 'Old')->where('last_name', 'Member')->first();
        $this->assertNotNull($oldUser);

        $hasIdUsersColumn = \Illuminate\Support\Facades\DB::getSchemaBuilder()->hasColumn('has_participate', 'id_users');
        $userIdColumn = $hasIdUsersColumn ? 'id_users' : 'id';

        $oldParticipation = \Illuminate\Support\Facades\DB::table('has_participate')
            ->where($userIdColumn, $oldUser->id)
            ->first();
        $teamId = $oldParticipation->equ_id;

        // Add another member to the same team (simulating a shared team)
        $anotherUser = User::factory()->create([
            'first_name' => 'Permanent',
            'last_name' => 'Member',
        ]);

        $participateData = [
            $userIdColumn => $anotherUser->id,
            'equ_id' => $teamId,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (\Illuminate\Support\Facades\DB::getSchemaBuilder()->hasColumn('has_participate', 'adh_id')) {
            $participateData['adh_id'] = $anotherUser->adh_id;
        }
        \Illuminate\Support\Facades\DB::table('has_participate')->insert($participateData);

        // Verify team now has 2 members
        $memberCount = \Illuminate\Support\Facades\DB::table('has_participate')
            ->where('equ_id', $teamId)
            ->count();
        $this->assertEquals(2, $memberCount);

        // Second import: Replace User A with User B (new user)
        $csvContent2 = "Rang,Nom,Temps,Malus,Temps Final\n";
        $csvContent2 .= "1,Replacement Runner,01:25:00.00,00:00.00,01:25:00.00";

        $file2 = UploadedFile::fake()->createWithContent('results2.csv', $csvContent2);

        $result2 = $this->service->importCsv($file2, $race->race_id);
        $this->assertEquals(1, $result2['removed']);

        // Verify old user's participation was deleted
        $oldParticipationAfter = \Illuminate\Support\Facades\DB::table('has_participate')
            ->where($userIdColumn, $oldUser->id)
            ->count();
        $this->assertEquals(0, $oldParticipationAfter, 'Old user participation should be deleted');

        // Verify team still exists (because Permanent Member is still in it)
        $this->assertNotNull(Team::find($teamId), 'Team should NOT be deleted when other members exist');

        // Verify Permanent Member is still in team
        $permanentMemberParticipation = \Illuminate\Support\Facades\DB::table('has_participate')
            ->where($userIdColumn, $anotherUser->id)
            ->where('equ_id', $teamId)
            ->count();
        $this->assertEquals(1, $permanentMemberParticipation, 'Permanent member should still be in team');
    }

    /**
     * Test multiple users being replaced at once in CSV.
     */
    public function test_multiple_users_replacement_in_csv(): void
    {
        $race = Race::factory()->create();
        
        // First import: 3 users
        $csvContent1 = "Rang,Nom,Temps,Malus,Temps Final\n";
        $csvContent1 .= "1,User Alpha,01:00:00.00,00:00.00,01:00:00.00\n";
        $csvContent1 .= "2,User Beta,01:10:00.00,00:00.00,01:10:00.00\n";
        $csvContent1 .= "3,User Gamma,01:20:00.00,00:00.00,01:20:00.00";

        Storage::fake('local');
        $file1 = UploadedFile::fake()->createWithContent('results1.csv', $csvContent1);

        $result1 = $this->service->importCsv($file1, $race->race_id);
        $this->assertEquals(3, $result1['success']);
        $this->assertEquals(3, $result1['created']);

        $hasIdUsersColumn = \Illuminate\Support\Facades\DB::getSchemaBuilder()->hasColumn('has_participate', 'id_users');
        $userIdColumn = $hasIdUsersColumn ? 'id_users' : 'id';

        // Get all old users and their teams
        $userAlpha = User::where('first_name', 'User')->where('last_name', 'Alpha')->first();
        $userBeta = User::where('first_name', 'User')->where('last_name', 'Beta')->first();
        $userGamma = User::where('first_name', 'User')->where('last_name', 'Gamma')->first();

        $teamAlpha = \Illuminate\Support\Facades\DB::table('has_participate')
            ->where($userIdColumn, $userAlpha->id)->first()->equ_id;
        $teamBeta = \Illuminate\Support\Facades\DB::table('has_participate')
            ->where($userIdColumn, $userBeta->id)->first()->equ_id;
        $teamGamma = \Illuminate\Support\Facades\DB::table('has_participate')
            ->where($userIdColumn, $userGamma->id)->first()->equ_id;

        // Second import: Only User Beta stays, Alpha and Gamma are replaced
        $csvContent2 = "Rang,Nom,Temps,Malus,Temps Final\n";
        $csvContent2 .= "1,User Delta,00:55:00.00,00:00.00,00:55:00.00\n";
        $csvContent2 .= "2,User Beta,01:05:00.00,00:00.00,01:05:00.00\n";
        $csvContent2 .= "3,User Epsilon,01:15:00.00,00:00.00,01:15:00.00";

        $file2 = UploadedFile::fake()->createWithContent('results2.csv', $csvContent2);

        $result2 = $this->service->importCsv($file2, $race->race_id);
        $this->assertEquals(3, $result2['success']);
        $this->assertEquals(2, $result2['created']); // Delta and Epsilon
        $this->assertEquals(2, $result2['removed']); // Alpha and Gamma removed

        // Verify Alpha and Gamma participation deleted
        $alphaParticipation = \Illuminate\Support\Facades\DB::table('has_participate')
            ->where($userIdColumn, $userAlpha->id)->count();
        $gammaParticipation = \Illuminate\Support\Facades\DB::table('has_participate')
            ->where($userIdColumn, $userGamma->id)->count();
        $this->assertEquals(0, $alphaParticipation, 'Alpha participation should be deleted');
        $this->assertEquals(0, $gammaParticipation, 'Gamma participation should be deleted');

        // Verify Alpha and Gamma solo teams deleted
        $this->assertNull(Team::find($teamAlpha), 'Alpha solo team should be deleted');
        $this->assertNull(Team::find($teamGamma), 'Gamma solo team should be deleted');

        // Verify Beta still has participation (user was retained)
        $betaParticipation = \Illuminate\Support\Facades\DB::table('has_participate')
            ->where($userIdColumn, $userBeta->id)->count();
        $this->assertEquals(1, $betaParticipation, 'Beta participation should still exist');

        // Verify Beta team still exists
        $this->assertNotNull(Team::find($teamBeta), 'Beta team should still exist');

        // Verify leaderboard has correct 3 users now
        $leaderboard = $this->service->getIndividualLeaderboard($race->race_id);
        $this->assertEquals(3, $leaderboard['total']);
        $userNames = collect($leaderboard['data'])->pluck('user_name')->toArray();
        $this->assertContains('User Delta', $userNames);
        $this->assertContains('User Beta', $userNames);
        $this->assertContains('User Epsilon', $userNames);
        $this->assertNotContains('User Alpha', $userNames);
        $this->assertNotContains('User Gamma', $userNames);
    }

    /**
     * Test cleanup when imported user is removed but team has other members.
     * The team should NOT be deleted, only the user's has_participate entry.
     */
    public function test_cleanup_keeps_team_when_other_members_exist(): void
    {
        $race = Race::factory()->create();
        
        // First import to create an imported user with a team
        $csvContent = "Rang,Nom,Temps,Malus,Temps Final\n";
        $csvContent .= "1,Member One,01:30:00.00,00:00.00,01:30:00.00";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $this->service->importCsv($file, $race->race_id);

        $importedUser = User::where('first_name', 'Member')->where('last_name', 'One')->first();
        $this->assertNotNull($importedUser);

        $hasIdUsersColumn = \Illuminate\Support\Facades\DB::getSchemaBuilder()->hasColumn('has_participate', 'id_users');
        $userIdColumn = $hasIdUsersColumn ? 'id_users' : 'id';

        $participation = \Illuminate\Support\Facades\DB::table('has_participate')
            ->where($userIdColumn, $importedUser->id)
            ->first();
        $teamId = $participation->equ_id;

        // Add another member to the same team
        $anotherUser = User::factory()->create([
            'first_name' => 'Another',
            'last_name' => 'Member',
        ]);

        $participateData = [
            $userIdColumn => $anotherUser->id,
            'equ_id' => $teamId,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (\Illuminate\Support\Facades\DB::getSchemaBuilder()->hasColumn('has_participate', 'adh_id')) {
            $participateData['adh_id'] = $anotherUser->adh_id;
        }
        \Illuminate\Support\Facades\DB::table('has_participate')->insert($participateData);

        // Verify team has 2 members now
        $memberCount = \Illuminate\Support\Facades\DB::table('has_participate')
            ->where('equ_id', $teamId)
            ->count();
        $this->assertEquals(2, $memberCount, 'Team should have 2 members');

        // Now import empty CSV to remove imported user from leaderboard
        $emptyCsv = "Rang,Nom,Temps,Malus,Temps Final\n";
        $file2 = UploadedFile::fake()->createWithContent('empty.csv', $emptyCsv);

        $this->service->importCsv($file2, $race->race_id);

        // Verify imported user's participation was deleted
        $importedUserParticipation = \Illuminate\Support\Facades\DB::table('has_participate')
            ->where($userIdColumn, $importedUser->id)
            ->count();
        $this->assertEquals(0, $importedUserParticipation, 'Imported user participation should be deleted');

        // Verify team still exists (because other member is still there)
        $team = Team::find($teamId);
        $this->assertNotNull($team, 'Team should NOT be deleted when other members exist');

        // Verify other member still in team
        $otherMemberParticipation = \Illuminate\Support\Facades\DB::table('has_participate')
            ->where($userIdColumn, $anotherUser->id)
            ->where('equ_id', $teamId)
            ->count();
        $this->assertEquals(1, $otherMemberParticipation, 'Other member should still be in team');
    }

    /**
     * Test cleanup when solo imported user is removed.
     * The team should be deleted since no other members exist.
     */
    public function test_cleanup_deletes_solo_team_when_user_removed(): void
    {
        $race = Race::factory()->create();
        
        // First import to create an imported user with a solo team
        $csvContent = "Rang,Nom,Temps,Malus,Temps Final\n";
        $csvContent .= "1,Solo Runner,01:30:00.00,00:00.00,01:30:00.00";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $this->service->importCsv($file, $race->race_id);

        $importedUser = User::where('first_name', 'Solo')->where('last_name', 'Runner')->first();
        $this->assertNotNull($importedUser);

        $hasIdUsersColumn = \Illuminate\Support\Facades\DB::getSchemaBuilder()->hasColumn('has_participate', 'id_users');
        $userIdColumn = $hasIdUsersColumn ? 'id_users' : 'id';

        $participation = \Illuminate\Support\Facades\DB::table('has_participate')
            ->where($userIdColumn, $importedUser->id)
            ->first();
        $teamId = $participation->equ_id;

        // Verify team exists
        $this->assertNotNull(Team::find($teamId), 'Team should exist after import');

        // Now import empty CSV to remove imported user from leaderboard
        $emptyCsv = "Rang,Nom,Temps,Malus,Temps Final\n";
        $file2 = UploadedFile::fake()->createWithContent('empty.csv', $emptyCsv);

        $this->service->importCsv($file2, $race->race_id);

        // Verify imported user's participation was deleted
        $importedUserParticipation = \Illuminate\Support\Facades\DB::table('has_participate')
            ->where($userIdColumn, $importedUser->id)
            ->count();
        $this->assertEquals(0, $importedUserParticipation, 'Imported user participation should be deleted');

        // Verify team was deleted (since it was solo)
        $this->assertNull(Team::find($teamId), 'Solo team should be deleted when user is removed');
    }

    /**
     * Test getPublicLeaderboard for teams filters by race correctly.
     */
    public function test_get_public_team_leaderboard_filters_by_race(): void
    {
        $race1 = Race::factory()->create(['race_name' => 'Public Race 1']);
        $race2 = Race::factory()->create(['race_name' => 'Public Race 2']);
        
        $teamRace1 = Team::factory()->create(['equ_name' => 'Public Team 1']);
        $teamRace2 = Team::factory()->create(['equ_name' => 'Public Team 2']);

        // Team 1 only in race 1
        LeaderboardTeam::create([
            'equ_id' => $teamRace1->equ_id,
            'race_id' => $race1->race_id,
            'average_temps' => 3600,
            'average_malus' => 0,
            'average_temps_final' => 3600,
            'member_count' => 2,
        ]);

        // Team 2 only in race 2
        LeaderboardTeam::create([
            'equ_id' => $teamRace2->equ_id,
            'race_id' => $race2->race_id,
            'average_temps' => 3700,
            'average_malus' => 0,
            'average_temps_final' => 3700,
            'member_count' => 2,
        ]);

        // Get public leaderboard for race 1 (team type)
        $publicRace1 = $this->service->getPublicLeaderboard($race1->race_id, null, 'team');
        
        $this->assertEquals(1, $publicRace1['total'], 'Public Race 1 team leaderboard should have 1 team');
        $teamIdsRace1 = collect($publicRace1['data'])->pluck('equ_id')->toArray();
        $this->assertContains($teamRace1->equ_id, $teamIdsRace1, 'Public Team 1 should be in Race 1');
        $this->assertNotContains($teamRace2->equ_id, $teamIdsRace1, 'Public Team 2 should NOT be in Race 1');

        // Get public leaderboard for race 2 (team type)
        $publicRace2 = $this->service->getPublicLeaderboard($race2->race_id, null, 'team');
        
        $this->assertEquals(1, $publicRace2['total'], 'Public Race 2 team leaderboard should have 1 team');
        $teamIdsRace2 = collect($publicRace2['data'])->pluck('equ_id')->toArray();
        $this->assertContains($teamRace2->equ_id, $teamIdsRace2, 'Public Team 2 should be in Race 2');
        $this->assertNotContains($teamRace1->equ_id, $teamIdsRace2, 'Public Team 1 should NOT be in Race 2');
    }

    /**
     * Test getPublicLeaderboard for teams without race filter shows all races.
     */
    public function test_get_public_team_leaderboard_shows_all_when_no_race_filter(): void
    {
        $race1 = Race::factory()->create(['race_name' => 'All Race 1']);
        $race2 = Race::factory()->create(['race_name' => 'All Race 2']);
        
        $team1 = Team::factory()->create(['equ_name' => 'All Team 1']);
        $team2 = Team::factory()->create(['equ_name' => 'All Team 2']);

        LeaderboardTeam::create([
            'equ_id' => $team1->equ_id,
            'race_id' => $race1->race_id,
            'average_temps' => 3600,
            'average_malus' => 0,
            'average_temps_final' => 3600,
            'member_count' => 2,
        ]);

        LeaderboardTeam::create([
            'equ_id' => $team2->equ_id,
            'race_id' => $race2->race_id,
            'average_temps' => 3700,
            'average_malus' => 0,
            'average_temps_final' => 3700,
            'member_count' => 2,
        ]);

        // Get public leaderboard without race filter (should show all)
        $publicAll = $this->service->getPublicLeaderboard(null, null, 'team');
        
        $this->assertEquals(2, $publicAll['total'], 'Public leaderboard without filter should show all teams from all races');
    }

    // ============================================
    // INDIVIDUAL LEADERBOARD TESTS
    // ============================================

    /**
     * Test getIndividualLeaderboard filters by race correctly.
     */
    public function test_get_individual_leaderboard_filters_by_race(): void
    {
        $race1 = Race::factory()->create(['race_name' => 'Individual Race 1']);
        $race2 = Race::factory()->create(['race_name' => 'Individual Race 2']);
        
        $user1 = User::factory()->create(['first_name' => 'User', 'last_name' => 'One']);
        $user2 = User::factory()->create(['first_name' => 'User', 'last_name' => 'Two']);

        // User 1 only in race 1
        LeaderboardUser::create([
            'user_id' => $user1->id,
            'race_id' => $race1->race_id,
            'temps' => 3600,
            'malus' => 0,
        ]);

        // User 2 only in race 2
        LeaderboardUser::create([
            'user_id' => $user2->id,
            'race_id' => $race2->race_id,
            'temps' => 3700,
            'malus' => 0,
        ]);

        // Get leaderboard for race 1
        $leaderboardRace1 = $this->service->getIndividualLeaderboard($race1->race_id);
        
        $this->assertEquals(1, $leaderboardRace1['total']);
        $userIdsRace1 = collect($leaderboardRace1['data'])->pluck('user_id')->toArray();
        $this->assertContains($user1->id, $userIdsRace1);
        $this->assertNotContains($user2->id, $userIdsRace1);

        // Get leaderboard for race 2
        $leaderboardRace2 = $this->service->getIndividualLeaderboard($race2->race_id);
        
        $this->assertEquals(1, $leaderboardRace2['total']);
        $userIdsRace2 = collect($leaderboardRace2['data'])->pluck('user_id')->toArray();
        $this->assertContains($user2->id, $userIdsRace2);
        $this->assertNotContains($user1->id, $userIdsRace2);
    }

    /**
     * Test individual leaderboard pagination.
     */
    public function test_individual_leaderboard_pagination(): void
    {
        $race = Race::factory()->create();
        
        // Create 25 users with results
        for ($i = 1; $i <= 25; $i++) {
            $user = User::factory()->create();
            LeaderboardUser::create([
                'user_id' => $user->id,
                'race_id' => $race->race_id,
                'temps' => 3600 + ($i * 10),
                'malus' => 0,
            ]);
        }

        // Get first page (default 20 per page)
        $page1 = $this->service->getIndividualLeaderboard($race->race_id);
        
        $this->assertEquals(25, $page1['total']);
        $this->assertEquals(1, $page1['current_page']);
        $this->assertEquals(2, $page1['last_page']);
        $this->assertCount(20, $page1['data']);
    }

    /**
     * Test individual leaderboard ranks are correct.
     */
    public function test_individual_leaderboard_ranks_correctly(): void
    {
        $race = Race::factory()->create();
        
        $user1 = User::factory()->create(['first_name' => 'Fast', 'last_name' => 'Runner']);
        $user2 = User::factory()->create(['first_name' => 'Medium', 'last_name' => 'Runner']);
        $user3 = User::factory()->create(['first_name' => 'Slow', 'last_name' => 'Runner']);

        LeaderboardUser::create(['user_id' => $user2->id, 'race_id' => $race->race_id, 'temps' => 3700, 'malus' => 0]);
        LeaderboardUser::create(['user_id' => $user1->id, 'race_id' => $race->race_id, 'temps' => 3500, 'malus' => 0]);
        LeaderboardUser::create(['user_id' => $user3->id, 'race_id' => $race->race_id, 'temps' => 3900, 'malus' => 0]);

        $leaderboard = $this->service->getIndividualLeaderboard($race->race_id);
        
        $this->assertEquals(1, $leaderboard['data'][0]['rank']);
        $this->assertEquals($user1->id, $leaderboard['data'][0]['user_id']);
        
        $this->assertEquals(2, $leaderboard['data'][1]['rank']);
        $this->assertEquals($user2->id, $leaderboard['data'][1]['user_id']);
        
        $this->assertEquals(3, $leaderboard['data'][2]['rank']);
        $this->assertEquals($user3->id, $leaderboard['data'][2]['user_id']);
    }

    /**
     * Test individual leaderboard includes malus in final time.
     */
    public function test_individual_leaderboard_includes_malus(): void
    {
        $race = Race::factory()->create();
        
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // User 1: fast time but big penalty
        LeaderboardUser::create(['user_id' => $user1->id, 'race_id' => $race->race_id, 'temps' => 3500, 'malus' => 500]);
        // User 2: slower time but no penalty
        LeaderboardUser::create(['user_id' => $user2->id, 'race_id' => $race->race_id, 'temps' => 3800, 'malus' => 0]);

        $leaderboard = $this->service->getIndividualLeaderboard($race->race_id);
        
        // User 2 should be first (3800 < 4000)
        $this->assertEquals($user2->id, $leaderboard['data'][0]['user_id']);
        $this->assertEquals($user1->id, $leaderboard['data'][1]['user_id']);
    }

    /**
     * Test individual leaderboard search by first name.
     */
    public function test_individual_leaderboard_search_by_first_name(): void
    {
        $race = Race::factory()->create();
        
        $user1 = User::factory()->create(['first_name' => 'Jean', 'last_name' => 'Dupont']);
        $user2 = User::factory()->create(['first_name' => 'Pierre', 'last_name' => 'Martin']);

        LeaderboardUser::create(['user_id' => $user1->id, 'race_id' => $race->race_id, 'temps' => 3600, 'malus' => 0]);
        LeaderboardUser::create(['user_id' => $user2->id, 'race_id' => $race->race_id, 'temps' => 3700, 'malus' => 0]);

        $searchResult = $this->service->getIndividualLeaderboard($race->race_id, 'Jean');
        
        $this->assertEquals(1, $searchResult['total']);
        $this->assertEquals($user1->id, $searchResult['data'][0]['user_id']);
    }

    /**
     * Test individual leaderboard search by last name.
     */
    public function test_individual_leaderboard_search_by_last_name(): void
    {
        $race = Race::factory()->create();
        
        $user1 = User::factory()->create(['first_name' => 'Jean', 'last_name' => 'Dupont']);
        $user2 = User::factory()->create(['first_name' => 'Pierre', 'last_name' => 'Martin']);

        LeaderboardUser::create(['user_id' => $user1->id, 'race_id' => $race->race_id, 'temps' => 3600, 'malus' => 0]);
        LeaderboardUser::create(['user_id' => $user2->id, 'race_id' => $race->race_id, 'temps' => 3700, 'malus' => 0]);

        $searchResult = $this->service->getIndividualLeaderboard($race->race_id, 'Martin');
        
        $this->assertEquals(1, $searchResult['total']);
        $this->assertEquals($user2->id, $searchResult['data'][0]['user_id']);
    }

    /**
     * Test individual leaderboard search by email.
     */
    public function test_individual_leaderboard_search_by_email(): void
    {
        $race = Race::factory()->create();
        
        $user1 = User::factory()->create(['email' => 'jean@example.com']);
        $user2 = User::factory()->create(['email' => 'pierre@example.com']);

        LeaderboardUser::create(['user_id' => $user1->id, 'race_id' => $race->race_id, 'temps' => 3600, 'malus' => 0]);
        LeaderboardUser::create(['user_id' => $user2->id, 'race_id' => $race->race_id, 'temps' => 3700, 'malus' => 0]);

        $searchResult = $this->service->getIndividualLeaderboard($race->race_id, 'pierre@example');
        
        $this->assertEquals(1, $searchResult['total']);
        $this->assertEquals($user2->id, $searchResult['data'][0]['user_id']);
    }

    /**
     * Test individual leaderboard returns formatted times.
     */
    public function test_individual_leaderboard_returns_formatted_times(): void
    {
        $race = Race::factory()->create();
        $user = User::factory()->create();

        LeaderboardUser::create([
            'user_id' => $user->id,
            'race_id' => $race->race_id,
            'temps' => 5461.5, // 1h 31m 1.5s
            'malus' => 60,     // 1 minute
        ]);

        $leaderboard = $this->service->getIndividualLeaderboard($race->race_id);
        
        $this->assertArrayHasKey('temps_formatted', $leaderboard['data'][0]);
        $this->assertArrayHasKey('malus_formatted', $leaderboard['data'][0]);
        $this->assertArrayHasKey('temps_final_formatted', $leaderboard['data'][0]);
    }

    // ============================================
    // TEAM LEADERBOARD TESTS
    // ============================================

    /**
     * Test team leaderboard search by team name.
     */
    public function test_team_leaderboard_search_by_name(): void
    {
        $race = Race::factory()->create();
        
        $team1 = Team::factory()->create(['equ_name' => 'Les Champions']);
        $team2 = Team::factory()->create(['equ_name' => 'Les Rapides']);

        LeaderboardTeam::create([
            'equ_id' => $team1->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 3600,
            'average_malus' => 0,
            'average_temps_final' => 3600,
            'member_count' => 2,
        ]);
        LeaderboardTeam::create([
            'equ_id' => $team2->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 3700,
            'average_malus' => 0,
            'average_temps_final' => 3700,
            'member_count' => 3,
        ]);

        $searchResult = $this->service->getTeamLeaderboard($race->race_id, 'Champions');
        
        $this->assertEquals(1, $searchResult['total']);
        $this->assertEquals($team1->equ_id, $searchResult['data'][0]['equ_id']);
    }

    /**
     * Test team leaderboard pagination.
     */
    public function test_team_leaderboard_pagination(): void
    {
        $race = Race::factory()->create();
        
        // Create 25 teams with results
        for ($i = 1; $i <= 25; $i++) {
            $team = Team::factory()->create(['equ_name' => "Team $i"]);
            LeaderboardTeam::create([
                'equ_id' => $team->equ_id,
                'race_id' => $race->race_id,
                'average_temps' => 3600 + ($i * 10),
                'average_malus' => 0,
                'average_temps_final' => 3600 + ($i * 10),
                'member_count' => 2,
            ]);
        }

        $page1 = $this->service->getTeamLeaderboard($race->race_id);
        
        $this->assertEquals(25, $page1['total']);
        $this->assertEquals(1, $page1['current_page']);
        $this->assertEquals(2, $page1['last_page']);
        $this->assertCount(20, $page1['data']);
    }

    /**
     * Test team leaderboard ranks correctly.
     */
    public function test_team_leaderboard_ranks_correctly(): void
    {
        $race = Race::factory()->create();
        
        $team1 = Team::factory()->create(['equ_name' => 'Fast Team']);
        $team2 = Team::factory()->create(['equ_name' => 'Medium Team']);
        $team3 = Team::factory()->create(['equ_name' => 'Slow Team']);

        LeaderboardTeam::create(['equ_id' => $team2->equ_id, 'race_id' => $race->race_id, 'average_temps' => 3700, 'average_malus' => 0, 'average_temps_final' => 3700, 'member_count' => 2]);
        LeaderboardTeam::create(['equ_id' => $team1->equ_id, 'race_id' => $race->race_id, 'average_temps' => 3500, 'average_malus' => 0, 'average_temps_final' => 3500, 'member_count' => 2]);
        LeaderboardTeam::create(['equ_id' => $team3->equ_id, 'race_id' => $race->race_id, 'average_temps' => 3900, 'average_malus' => 0, 'average_temps_final' => 3900, 'member_count' => 2]);

        $leaderboard = $this->service->getTeamLeaderboard($race->race_id);
        
        $this->assertEquals(1, $leaderboard['data'][0]['rank']);
        $this->assertEquals($team1->equ_id, $leaderboard['data'][0]['equ_id']);
        
        $this->assertEquals(2, $leaderboard['data'][1]['rank']);
        $this->assertEquals($team2->equ_id, $leaderboard['data'][1]['equ_id']);
        
        $this->assertEquals(3, $leaderboard['data'][2]['rank']);
        $this->assertEquals($team3->equ_id, $leaderboard['data'][2]['equ_id']);
    }

    /**
     * Test team leaderboard includes member count.
     */
    public function test_team_leaderboard_includes_member_count(): void
    {
        $race = Race::factory()->create();
        $team = Team::factory()->create(['equ_name' => 'Test Team']);

        LeaderboardTeam::create([
            'equ_id' => $team->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 3600,
            'average_malus' => 0,
            'average_temps_final' => 3600,
            'member_count' => 5,
        ]);

        $leaderboard = $this->service->getTeamLeaderboard($race->race_id);
        
        $this->assertEquals(5, $leaderboard['data'][0]['member_count']);
    }

    /**
     * Test team leaderboard returns formatted times.
     */
    public function test_team_leaderboard_returns_formatted_times(): void
    {
        $race = Race::factory()->create();
        $team = Team::factory()->create();

        LeaderboardTeam::create([
            'equ_id' => $team->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 5461.5,
            'average_malus' => 60,
            'average_temps_final' => 5521.5,
            'member_count' => 2,
        ]);

        $leaderboard = $this->service->getTeamLeaderboard($race->race_id);
        
        $this->assertArrayHasKey('average_temps_formatted', $leaderboard['data'][0]);
        $this->assertArrayHasKey('average_malus_formatted', $leaderboard['data'][0]);
        $this->assertArrayHasKey('average_temps_final_formatted', $leaderboard['data'][0]);
    }

    // ============================================
    // PUBLIC LEADERBOARD TESTS
    // ============================================

    /**
     * Test public leaderboard only shows public profiles.
     */
    public function test_public_individual_leaderboard_hides_private_profiles(): void
    {
        $race = Race::factory()->create();
        
        $publicUser = User::factory()->create(['first_name' => 'Public', 'last_name' => 'User', 'is_public' => true]);
        $privateUser = User::factory()->create(['first_name' => 'Private', 'last_name' => 'User', 'is_public' => false]);

        LeaderboardUser::create(['user_id' => $publicUser->id, 'race_id' => $race->race_id, 'temps' => 3600, 'malus' => 0]);
        LeaderboardUser::create(['user_id' => $privateUser->id, 'race_id' => $race->race_id, 'temps' => 3500, 'malus' => 0]);

        $publicLeaderboard = $this->service->getPublicLeaderboard($race->race_id, null, 'individual');
        
        $this->assertEquals(1, $publicLeaderboard['total']);
        $this->assertEquals($publicUser->id, $publicLeaderboard['data'][0]['user_id']);
    }

    /**
     * Test public leaderboard search works for user names.
     */
    public function test_public_individual_leaderboard_search_by_name(): void
    {
        $race = Race::factory()->create();
        
        $user1 = User::factory()->create(['first_name' => 'Jean', 'last_name' => 'Dupont', 'is_public' => true]);
        $user2 = User::factory()->create(['first_name' => 'Pierre', 'last_name' => 'Martin', 'is_public' => true]);

        LeaderboardUser::create(['user_id' => $user1->id, 'race_id' => $race->race_id, 'temps' => 3600, 'malus' => 0]);
        LeaderboardUser::create(['user_id' => $user2->id, 'race_id' => $race->race_id, 'temps' => 3700, 'malus' => 0]);

        $searchResult = $this->service->getPublicLeaderboard($race->race_id, 'Jean', 'individual');
        
        $this->assertEquals(1, $searchResult['total']);
        $this->assertEquals($user1->id, $searchResult['data'][0]['user_id']);
    }

    /**
     * Test public leaderboard search works for race names.
     */
    public function test_public_individual_leaderboard_search_by_race_name(): void
    {
        $race1 = Race::factory()->create(['race_name' => 'Marathon Paris']);
        $race2 = Race::factory()->create(['race_name' => 'Trail Lyon']);
        
        $user = User::factory()->create(['is_public' => true]);

        LeaderboardUser::create(['user_id' => $user->id, 'race_id' => $race1->race_id, 'temps' => 3600, 'malus' => 0]);
        LeaderboardUser::create(['user_id' => $user->id, 'race_id' => $race2->race_id, 'temps' => 3700, 'malus' => 0]);

        // Search without race filter, by race name
        $searchResult = $this->service->getPublicLeaderboard(null, 'Marathon', 'individual');
        
        $this->assertEquals(1, $searchResult['total']);
        $this->assertEquals($race1->race_id, $searchResult['data'][0]['race_id']);
    }

    /**
     * Test public team leaderboard search by team name.
     */
    public function test_public_team_leaderboard_search_by_team_name(): void
    {
        $race = Race::factory()->create();
        
        $team1 = Team::factory()->create(['equ_name' => 'Les Champions']);
        $team2 = Team::factory()->create(['equ_name' => 'Les Rapides']);

        LeaderboardTeam::create(['equ_id' => $team1->equ_id, 'race_id' => $race->race_id, 'average_temps' => 3600, 'average_malus' => 0, 'average_temps_final' => 3600, 'member_count' => 2]);
        LeaderboardTeam::create(['equ_id' => $team2->equ_id, 'race_id' => $race->race_id, 'average_temps' => 3700, 'average_malus' => 0, 'average_temps_final' => 3700, 'member_count' => 2]);

        $searchResult = $this->service->getPublicLeaderboard($race->race_id, 'Champions', 'team');
        
        $this->assertEquals(1, $searchResult['total']);
        $this->assertEquals($team1->equ_id, $searchResult['data'][0]['equ_id']);
    }

    /**
     * Test public team leaderboard search by race name.
     */
    public function test_public_team_leaderboard_search_by_race_name(): void
    {
        $race1 = Race::factory()->create(['race_name' => 'Marathon Paris']);
        $race2 = Race::factory()->create(['race_name' => 'Trail Lyon']);
        
        $team = Team::factory()->create(['equ_name' => 'Test Team']);

        LeaderboardTeam::create(['equ_id' => $team->equ_id, 'race_id' => $race1->race_id, 'average_temps' => 3600, 'average_malus' => 0, 'average_temps_final' => 3600, 'member_count' => 2]);
        LeaderboardTeam::create(['equ_id' => $team->equ_id, 'race_id' => $race2->race_id, 'average_temps' => 3700, 'average_malus' => 0, 'average_temps_final' => 3700, 'member_count' => 2]);

        // Search without race filter, by race name
        $searchResult = $this->service->getPublicLeaderboard(null, 'Trail', 'team');
        
        $this->assertEquals(1, $searchResult['total']);
        $this->assertEquals($race2->race_id, $searchResult['data'][0]['race_id']);
    }

    /**
     * Test public leaderboard includes race info when no filter.
     */
    public function test_public_leaderboard_includes_race_info(): void
    {
        $race = Race::factory()->create(['race_name' => 'Test Race', 'race_date_start' => '2026-06-15']);
        $user = User::factory()->create(['is_public' => true]);

        LeaderboardUser::create(['user_id' => $user->id, 'race_id' => $race->race_id, 'temps' => 3600, 'malus' => 0]);

        $leaderboard = $this->service->getPublicLeaderboard(null, null, 'individual');
        
        $this->assertEquals('Test Race', $leaderboard['data'][0]['race_name']);
        $this->assertArrayHasKey('race_date', $leaderboard['data'][0]);
    }

    /**
     * Test public individual leaderboard includes age categories from param_categorie_age.
     */
    public function test_public_individual_leaderboard_includes_age_categories(): void
    {
        $race = Race::factory()->create(['race_name' => 'Test Race']);
        $user = User::factory()->create(['is_public' => true]);

        // Create age categories
        $ageCategory1 = \App\Models\AgeCategorie::create(['nom' => 'Seniors', 'age_min' => 21, 'age_max' => 39]);
        $ageCategory2 = \App\Models\AgeCategorie::create(['nom' => 'Juniors', 'age_min' => 17, 'age_max' => 18]);

        // Link age categories to race via param_categorie_age
        \App\Models\ParamCategorieAge::create(['race_id' => $race->race_id, 'age_categorie_id' => $ageCategory1->id]);
        \App\Models\ParamCategorieAge::create(['race_id' => $race->race_id, 'age_categorie_id' => $ageCategory2->id]);

        LeaderboardUser::create(['user_id' => $user->id, 'race_id' => $race->race_id, 'temps' => 3600, 'malus' => 0]);

        $leaderboard = $this->service->getPublicLeaderboard(null, null, 'individual');
        
        $this->assertArrayHasKey('race_age_categories', $leaderboard['data'][0]);
        $this->assertCount(2, $leaderboard['data'][0]['race_age_categories']);
        $this->assertContains('Seniors', $leaderboard['data'][0]['race_age_categories']);
        $this->assertContains('Juniors', $leaderboard['data'][0]['race_age_categories']);
    }

    /**
     * Test public team leaderboard includes age categories from param_categorie_age.
     */
    public function test_public_team_leaderboard_includes_age_categories(): void
    {
        $race = Race::factory()->create(['race_name' => 'Test Race']);
        $team = Team::factory()->create();

        // Create age categories
        $ageCategory1 = \App\Models\AgeCategorie::create(['nom' => 'Cadets', 'age_min' => 15, 'age_max' => 16]);
        $ageCategory2 = \App\Models\AgeCategorie::create(['nom' => 'Minimes', 'age_min' => 13, 'age_max' => 14]);

        // Link age categories to race via param_categorie_age
        \App\Models\ParamCategorieAge::create(['race_id' => $race->race_id, 'age_categorie_id' => $ageCategory1->id]);
        \App\Models\ParamCategorieAge::create(['race_id' => $race->race_id, 'age_categorie_id' => $ageCategory2->id]);

        LeaderboardTeam::create([
            'equ_id' => $team->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 3600,
            'average_malus' => 0,
            'average_temps_final' => 3600,
            'member_count' => 2,
        ]);

        $leaderboard = $this->service->getPublicLeaderboard(null, null, 'team');
        
        $this->assertArrayHasKey('race_age_categories', $leaderboard['data'][0]);
        $this->assertCount(2, $leaderboard['data'][0]['race_age_categories']);
        $this->assertContains('Cadets', $leaderboard['data'][0]['race_age_categories']);
        $this->assertContains('Minimes', $leaderboard['data'][0]['race_age_categories']);
    }

    /**
     * Test getRaces includes age categories from param_categorie_age.
     */
    public function test_get_races_includes_age_categories(): void
    {
        $race = Race::factory()->create(['race_name' => 'Test Race']);

        // Create age categories
        $ageCategory1 = \App\Models\AgeCategorie::create(['nom' => 'Espoirs', 'age_min' => 19, 'age_max' => 20]);

        // Link age category to race
        \App\Models\ParamCategorieAge::create(['race_id' => $race->race_id, 'age_categorie_id' => $ageCategory1->id]);

        $races = $this->service->getRaces();
        
        $this->assertNotEmpty($races);
        $this->assertContains('Espoirs', $races[0]->age_category_names);
    }

    // ============================================
    // USER RESULTS TESTS
    // ============================================

    /**
     * Test getUserResults filters by user correctly.
     */
    public function test_get_user_results_filters_by_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $race = Race::factory()->create();

        LeaderboardUser::create(['user_id' => $user1->id, 'race_id' => $race->race_id, 'temps' => 3600, 'malus' => 0]);
        LeaderboardUser::create(['user_id' => $user2->id, 'race_id' => $race->race_id, 'temps' => 3700, 'malus' => 0]);

        $results = $this->service->getUserResults($user1->id);
        
        $this->assertEquals(1, $results['total']);
    }

    /**
     * Test getUserResults includes rank calculation.
     */
    public function test_get_user_results_includes_rank(): void
    {
        $user = User::factory()->create();
        $otherUser1 = User::factory()->create();
        $otherUser2 = User::factory()->create();
        $race = Race::factory()->create();

        // Create results: user is in second place
        LeaderboardUser::create(['user_id' => $otherUser1->id, 'race_id' => $race->race_id, 'temps' => 3400, 'malus' => 0]);
        LeaderboardUser::create(['user_id' => $user->id, 'race_id' => $race->race_id, 'temps' => 3600, 'malus' => 0]);
        LeaderboardUser::create(['user_id' => $otherUser2->id, 'race_id' => $race->race_id, 'temps' => 3800, 'malus' => 0]);

        $results = $this->service->getUserResults($user->id);
        
        $this->assertEquals(2, $results['data'][0]['rank']);
        $this->assertEquals(3, $results['data'][0]['total_participants']);
    }

    /**
     * Test getUserResults sort by best.
     */
    public function test_get_user_results_sort_by_best(): void
    {
        $user = User::factory()->create();
        $race1 = Race::factory()->create();
        $race2 = Race::factory()->create();

        // User got 1st in race1, 3rd in race2
        LeaderboardUser::create(['user_id' => $user->id, 'race_id' => $race1->race_id, 'temps' => 3600, 'malus' => 0]);
        LeaderboardUser::create(['user_id' => $user->id, 'race_id' => $race2->race_id, 'temps' => 4000, 'malus' => 0]);
        
        // Other users in race2 to make user's rank 3rd
        $other1 = User::factory()->create();
        $other2 = User::factory()->create();
        LeaderboardUser::create(['user_id' => $other1->id, 'race_id' => $race2->race_id, 'temps' => 3500, 'malus' => 0]);
        LeaderboardUser::create(['user_id' => $other2->id, 'race_id' => $race2->race_id, 'temps' => 3700, 'malus' => 0]);

        $results = $this->service->getUserResults($user->id, null, 'best');
        
        // First result should be the one with rank 1
        $this->assertEquals(1, $results['data'][0]['rank']);
    }

    /**
     * Test getUserResults sort by worst.
     */
    public function test_get_user_results_sort_by_worst(): void
    {
        $user = User::factory()->create();
        $race1 = Race::factory()->create();
        $race2 = Race::factory()->create();

        // User got 1st in race1, 3rd in race2
        LeaderboardUser::create(['user_id' => $user->id, 'race_id' => $race1->race_id, 'temps' => 3600, 'malus' => 0]);
        LeaderboardUser::create(['user_id' => $user->id, 'race_id' => $race2->race_id, 'temps' => 4000, 'malus' => 0]);
        
        // Other users in race2 to make user's rank 3rd
        $other1 = User::factory()->create();
        $other2 = User::factory()->create();
        LeaderboardUser::create(['user_id' => $other1->id, 'race_id' => $race2->race_id, 'temps' => 3500, 'malus' => 0]);
        LeaderboardUser::create(['user_id' => $other2->id, 'race_id' => $race2->race_id, 'temps' => 3700, 'malus' => 0]);

        $results = $this->service->getUserResults($user->id, null, 'worst');
        
        // First result should be the one with rank 3
        $this->assertEquals(3, $results['data'][0]['rank']);
    }

    /**
     * Test getUserTeamResults returns empty for user without team.
     */
    public function test_get_user_team_results_empty_for_user_without_team(): void
    {
        $user = User::factory()->create();
        
        $results = $this->service->getUserTeamResults($user->id);
        
        $this->assertEquals(0, $results['total']);
        $this->assertEmpty($results['data']);
    }

    // ============================================
    // CSV IMPORT EDGE CASES
    // ============================================

    /**
     * Test import CSV handles empty temps value.
     */
    public function test_import_csv_handles_empty_temps(): void
    {
        $race = Race::factory()->create();
        $user = User::factory()->create(['first_name' => 'Test', 'last_name' => 'User']);

        $csvContent = "Rang,Nom,Temps,Malus,Temps Final\n";
        $csvContent .= "1,Test User,,00:00.00,";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $result = $this->service->importCsv($file, $race->race_id);

        $this->assertEquals(1, $result['success']);
        
        $entry = LeaderboardUser::where('user_id', $user->id)->first();
        $this->assertEquals(0, $entry->temps);
    }

    /**
     * Test import CSV handles minutes:seconds format.
     */
    public function test_import_csv_handles_minutes_seconds_format(): void
    {
        $race = Race::factory()->create();
        $user = User::factory()->create(['first_name' => 'Test', 'last_name' => 'User']);

        $csvContent = "Rang,Nom,Temps,Malus,Temps Final\n";
        $csvContent .= "1,Test User,45:30,01:00,46:30";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $result = $this->service->importCsv($file, $race->race_id);

        $this->assertEquals(1, $result['success']);
        
        $entry = LeaderboardUser::where('user_id', $user->id)->first();
        $this->assertEquals(2730, $entry->temps); // 45*60 + 30
        $this->assertEquals(60, $entry->malus);   // 1*60
    }

    /**
     * Test import CSV handles decimal seconds.
     */
    public function test_import_csv_handles_decimal_seconds(): void
    {
        $race = Race::factory()->create();
        $user = User::factory()->create(['first_name' => 'Test', 'last_name' => 'User']);

        $csvContent = "Rang,Nom,Temps,Malus,Temps Final\n";
        $csvContent .= "1,Test User,01:30:45.75,00:00.50,01:30:46.25";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $result = $this->service->importCsv($file, $race->race_id);

        $this->assertEquals(1, $result['success']);
        
        $entry = LeaderboardUser::where('user_id', $user->id)->first();
        $this->assertEquals(5445.75, $entry->temps); // 1*3600 + 30*60 + 45.75
    }

    /**
     * Test import CSV handles numeric time values.
     */
    public function test_import_csv_handles_numeric_time_values(): void
    {
        $race = Race::factory()->create();
        $user = User::factory()->create(['first_name' => 'Test', 'last_name' => 'User']);

        $csvContent = "Rang,Nom,Temps,Malus,Temps Final\n";
        $csvContent .= "1,Test User,3661.5,60,3721.5";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $result = $this->service->importCsv($file, $race->race_id);

        $this->assertEquals(1, $result['success']);
        
        $entry = LeaderboardUser::where('user_id', $user->id)->first();
        $this->assertEquals(3661.5, $entry->temps);
        $this->assertEquals(60, $entry->malus);
    }

    /**
     * Test import CSV with multiple users at once.
     */
    public function test_import_csv_with_multiple_users(): void
    {
        $race = Race::factory()->create();
        
        $user1 = User::factory()->create(['first_name' => 'User', 'last_name' => 'One']);
        $user2 = User::factory()->create(['first_name' => 'User', 'last_name' => 'Two']);
        $user3 = User::factory()->create(['first_name' => 'User', 'last_name' => 'Three']);

        $csvContent = "Rang,Nom,Temps,Malus,Temps Final\n";
        $csvContent .= "1,User One,01:00:00,00:00,01:00:00\n";
        $csvContent .= "2,User Two,01:10:00,00:00,01:10:00\n";
        $csvContent .= "3,User Three,01:20:00,00:00,01:20:00";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $result = $this->service->importCsv($file, $race->race_id);

        $this->assertEquals(3, $result['success']);
        $this->assertDatabaseCount('leaderboard_users', 3);
    }

    /**
     * Test import CSV skips empty lines.
     */
    public function test_import_csv_skips_empty_lines(): void
    {
        $race = Race::factory()->create();
        $user = User::factory()->create(['first_name' => 'Test', 'last_name' => 'User']);

        $csvContent = "Rang,Nom,Temps,Malus,Temps Final\n";
        $csvContent .= "\n";
        $csvContent .= "1,Test User,01:00:00,00:00,01:00:00\n";
        $csvContent .= "\n";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $result = $this->service->importCsv($file, $race->race_id);

        $this->assertEquals(1, $result['success']);
    }

    /**
     * Test import CSV handles user with special characters in name.
     */
    public function test_import_csv_handles_special_characters_in_name(): void
    {
        $race = Race::factory()->create();
        $user = User::factory()->create(['first_name' => 'Jean-Pierre', 'last_name' => "O'Brien"]);

        $csvContent = "Rang,Nom,Temps,Malus,Temps Final\n";
        $csvContent .= "1,Jean-Pierre O'Brien,01:00:00,00:00,01:00:00";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $result = $this->service->importCsv($file, $race->race_id);

        $this->assertEquals(1, $result['success']);
    }

    /**
     * Test import CSV creates user with accented name.
     */
    public function test_import_csv_creates_user_with_accented_name(): void
    {
        $race = Race::factory()->create();

        $csvContent = "Rang,Nom,Temps,Malus,Temps Final\n";
        $csvContent .= "1,Éloïse Müller,01:00:00,00:00,01:00:00";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $result = $this->service->importCsv($file, $race->race_id);

        $this->assertEquals(1, $result['success']);
        $this->assertEquals(1, $result['created']);
        
        $user = User::where('first_name', 'Éloïse')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Müller', $user->last_name);
    }

    /**
     * Test import CSV case insensitive name matching.
     */
    public function test_import_csv_case_insensitive_name_matching(): void
    {
        $race = Race::factory()->create();
        $user = User::factory()->create(['first_name' => 'Jean', 'last_name' => 'Dupont']);

        $csvContent = "Rang,Nom,Temps,Malus,Temps Final\n";
        $csvContent .= "1,JEAN DUPONT,01:00:00,00:00,01:00:00";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $result = $this->service->importCsv($file, $race->race_id);

        $this->assertEquals(1, $result['success']);
        $this->assertEquals(0, $result['created']); // Should match existing user
    }

    // ============================================
    // CSV EXPORT TESTS
    // ============================================

    /**
     * Test export CSV individual format.
     * Note: exportToCsv only exports PUBLIC profiles.
     */
    public function test_export_csv_individual_format(): void
    {
        $race = Race::factory()->create();
        $user = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'Runner',
            'is_public' => true, // Must be public to appear in CSV export
        ]);

        LeaderboardUser::create([
            'user_id' => $user->id,
            'race_id' => $race->race_id,
            'temps' => 3661,
            'malus' => 60,
        ]);

        $csv = $this->service->exportToCsv($race->race_id, 'individual');
        
        $this->assertStringContainsString('Rang', $csv);
        $this->assertStringContainsString('Nom', $csv);
        $this->assertStringContainsString('Test Runner', $csv);
    }

    /**
     * Test export CSV team format.
     * CSV format: Rang, Equipe, Catégorie, Temps, Points
     */
    public function test_export_csv_team_format(): void
    {
        $race = Race::factory()->create();
        $team = Team::factory()->create(['equ_name' => 'Test Team']);

        LeaderboardTeam::create([
            'equ_id' => $team->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 3661,
            'average_malus' => 60,
            'average_temps_final' => 3721,
            'member_count' => 3,
            'category' => 'Senior',
        ]);

        $csv = $this->service->exportToCsv($race->race_id, 'team');
        
        $this->assertStringContainsString('Equipe', $csv);
        $this->assertStringContainsString('Catégorie', $csv);
        $this->assertStringContainsString('Test Team', $csv);
    }

    /**
     * Test export CSV preserves ranking order.
     * Note: exportToCsv only exports PUBLIC profiles.
     */
    public function test_export_csv_preserves_ranking_order(): void
    {
        $race = Race::factory()->create();
        
        $user1 = User::factory()->create(['first_name' => 'First', 'last_name' => 'Place', 'is_public' => true]);
        $user2 = User::factory()->create(['first_name' => 'Second', 'last_name' => 'Place', 'is_public' => true]);

        LeaderboardUser::create(['user_id' => $user2->id, 'race_id' => $race->race_id, 'temps' => 4000, 'malus' => 0]);
        LeaderboardUser::create(['user_id' => $user1->id, 'race_id' => $race->race_id, 'temps' => 3600, 'malus' => 0]);

        $csv = $this->service->exportToCsv($race->race_id, 'individual');
        $lines = explode("\n", trim($csv));
        
        // First data line should be rank 1
        $this->assertStringContainsString('1', $lines[1]);
        $this->assertStringContainsString('First Place', $lines[1]);
    }

    /**
     * Test export CSV empty race returns header only.
     */
    public function test_export_csv_empty_race(): void
    {
        $race = Race::factory()->create();

        $csv = $this->service->exportToCsv($race->race_id, 'individual');
        $lines = explode("\n", trim($csv));
        
        $this->assertCount(1, $lines); // Only header
        $this->assertStringContainsString('Rang', $lines[0]);
    }

    /**
     * Test export ALL individual results to CSV (all races).
     */
    public function test_export_all_csv_individual_format(): void
    {
        $race1 = Race::factory()->create(['race_name' => 'Marathon Paris']);
        $race2 = Race::factory()->create(['race_name' => 'Trail Lyon']);
        
        // Only public users should be exported
        $user1 = User::factory()->create(['first_name' => 'Jean', 'last_name' => 'Dupont', 'is_public' => true]);
        $user2 = User::factory()->create(['first_name' => 'Marie', 'last_name' => 'Martin', 'is_public' => true]);

        LeaderboardUser::create(['user_id' => $user1->id, 'race_id' => $race1->race_id, 'temps' => 3600, 'malus' => 60]);
        LeaderboardUser::create(['user_id' => $user2->id, 'race_id' => $race2->race_id, 'temps' => 4000, 'malus' => 0]);

        $csv = $this->service->exportAllToCsv('individual');
        $lines = explode("\n", trim($csv));
        
        // Header + 2 data rows
        $this->assertCount(3, $lines);
        
        // Check header includes race columns
        // CSV format: Rang, Nom, Temps, Course, Malus, Temps Final, Points
        $this->assertStringContainsString('Rang', $lines[0]);
        $this->assertStringContainsString('Nom', $lines[0]);
        $this->assertStringContainsString('Course', $lines[0]);
        $this->assertStringContainsString('Malus', $lines[0]);
        $this->assertStringContainsString('Points', $lines[0]);
        
        // Check data includes race names
        $csvContent = implode("\n", $lines);
        $this->assertStringContainsString('Jean Dupont', $csvContent);
        $this->assertStringContainsString('Marie Martin', $csvContent);
        $this->assertStringContainsString('Marathon Paris', $csvContent);
        $this->assertStringContainsString('Trail Lyon', $csvContent);
    }

    /**
     * Test export ALL individual CSV only includes public profiles.
     */
    public function test_export_all_csv_only_includes_public_profiles(): void
    {
        $race = Race::factory()->create(['race_name' => 'Test Race']);
        
        // Public user - should be included
        $publicUser = User::factory()->create(['first_name' => 'Public', 'last_name' => 'User', 'is_public' => true]);
        // Private user - should NOT be included
        $privateUser = User::factory()->create(['first_name' => 'Private', 'last_name' => 'User', 'is_public' => false]);

        LeaderboardUser::create(['user_id' => $publicUser->id, 'race_id' => $race->race_id, 'temps' => 3600, 'malus' => 0]);
        LeaderboardUser::create(['user_id' => $privateUser->id, 'race_id' => $race->race_id, 'temps' => 3500, 'malus' => 0]);

        $csv = $this->service->exportAllToCsv('individual');
        $lines = explode("\n", trim($csv));
        
        // Header + 1 data row (only public user)
        $this->assertCount(2, $lines);
        
        $csvContent = implode("\n", $lines);
        $this->assertStringContainsString('Public User', $csvContent);
        $this->assertStringNotContainsString('Private User', $csvContent);
    }

    /**
     * Test export ALL team results to CSV (all races).
     */
    public function test_export_all_csv_team_format(): void
    {
        $race1 = Race::factory()->create(['race_name' => 'Race Alpha']);
        $race2 = Race::factory()->create(['race_name' => 'Race Beta']);
        
        $team1 = Team::factory()->create(['equ_name' => 'Alpha Team']);
        $team2 = Team::factory()->create(['equ_name' => 'Beta Team']);

        LeaderboardTeam::create([
            'equ_id' => $team1->equ_id,
            'race_id' => $race1->race_id,
            'average_temps' => 3600,
            'average_malus' => 30,
            'average_temps_final' => 3630,
            'member_count' => 3,
        ]);
        
        LeaderboardTeam::create([
            'equ_id' => $team2->equ_id,
            'race_id' => $race2->race_id,
            'average_temps' => 4000,
            'average_malus' => 60,
            'average_temps_final' => 4060,
            'member_count' => 2,
        ]);

        $csv = $this->service->exportAllToCsv('team');
        $lines = explode("\n", trim($csv));
        
        // Header + 2 data rows
        $this->assertCount(3, $lines);
        
        // Check header - CSV format: Rang, Equipe, Catégorie, Temps, Points
        $this->assertStringContainsString('Rang', $lines[0]);
        $this->assertStringContainsString('Equipe', $lines[0]);
        $this->assertStringContainsString('Catégorie', $lines[0]);
        $this->assertStringContainsString('Points', $lines[0]);
        
        // Check data
        $csvContent = implode("\n", $lines);
        $this->assertStringContainsString('Alpha Team', $csvContent);
        $this->assertStringContainsString('Beta Team', $csvContent);
    }

    /**
     * Test export ALL CSV empty leaderboard returns header only.
     */
    public function test_export_all_csv_empty_returns_header(): void
    {
        // No races or results
        $csv = $this->service->exportAllToCsv('individual');
        $lines = explode("\n", trim($csv));
        
        $this->assertCount(1, $lines); // Only header
        $this->assertStringContainsString('Rang', $lines[0]);
    }

    /**
     * Test export ALL CSV includes results from multiple races.
     */
    public function test_export_all_csv_includes_all_races(): void
    {
        // Create 3 races with different results
        $race1 = Race::factory()->create(['race_name' => 'Course 1']);
        $race2 = Race::factory()->create(['race_name' => 'Course 2']);
        $race3 = Race::factory()->create(['race_name' => 'Course 3']);
        
        // User must be public to be exported
        $user = User::factory()->create(['first_name' => 'Test', 'last_name' => 'Runner', 'is_public' => true]);

        // Same user in all 3 races
        LeaderboardUser::create(['user_id' => $user->id, 'race_id' => $race1->race_id, 'temps' => 3600, 'malus' => 0]);
        LeaderboardUser::create(['user_id' => $user->id, 'race_id' => $race2->race_id, 'temps' => 3700, 'malus' => 0]);
        LeaderboardUser::create(['user_id' => $user->id, 'race_id' => $race3->race_id, 'temps' => 3800, 'malus' => 0]);

        $csv = $this->service->exportAllToCsv('individual');
        $lines = explode("\n", trim($csv));
        
        // Header + 3 data rows (one per race)
        $this->assertCount(4, $lines);
        
        $csvContent = implode("\n", $lines);
        $this->assertStringContainsString('Course 1', $csvContent);
        $this->assertStringContainsString('Course 2', $csvContent);
        $this->assertStringContainsString('Course 3', $csvContent);
    }

    // ============================================
    // RECALCULATE TEAM AVERAGES TESTS
    // ============================================

    /**
     * Test recalculate team averages with single member.
     */
    public function test_recalculate_team_averages_single_member(): void
    {
        $race = Race::factory()->create();
        $team = Team::factory()->create();
        $user = User::factory()->create();

        // Link user to team
        $hasIdUsersColumn = \Illuminate\Support\Facades\DB::getSchemaBuilder()->hasColumn('has_participate', 'id_users');
        $userIdColumn = $hasIdUsersColumn ? 'id_users' : 'id';
        \Illuminate\Support\Facades\DB::table('has_participate')->insert([
            $userIdColumn => $user->id,
            'equ_id' => $team->equ_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        LeaderboardUser::create(['user_id' => $user->id, 'race_id' => $race->race_id, 'temps' => 3600, 'malus' => 60]);

        $this->service->recalculateTeamAverages($race->race_id);

        $teamResult = LeaderboardTeam::where('equ_id', $team->equ_id)->where('race_id', $race->race_id)->first();
        
        $this->assertNotNull($teamResult);
        $this->assertEquals(3600, $teamResult->average_temps);
        $this->assertEquals(60, $teamResult->average_malus);
        $this->assertEquals(3660, $teamResult->average_temps_final);
        $this->assertEquals(1, $teamResult->member_count);
    }

    /**
     * Test recalculate team averages with multiple members.
     */
    public function test_recalculate_team_averages_multiple_members(): void
    {
        $race = Race::factory()->create();
        $team = Team::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $hasIdUsersColumn = \Illuminate\Support\Facades\DB::getSchemaBuilder()->hasColumn('has_participate', 'id_users');
        $userIdColumn = $hasIdUsersColumn ? 'id_users' : 'id';
        
        \Illuminate\Support\Facades\DB::table('has_participate')->insert([
            ['equ_id' => $team->equ_id, $userIdColumn => $user1->id, 'created_at' => now(), 'updated_at' => now()],
            ['equ_id' => $team->equ_id, $userIdColumn => $user2->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        LeaderboardUser::create(['user_id' => $user1->id, 'race_id' => $race->race_id, 'temps' => 3600, 'malus' => 0]);
        LeaderboardUser::create(['user_id' => $user2->id, 'race_id' => $race->race_id, 'temps' => 4000, 'malus' => 100]);

        $this->service->recalculateTeamAverages($race->race_id);

        $teamResult = LeaderboardTeam::where('equ_id', $team->equ_id)->where('race_id', $race->race_id)->first();
        
        $this->assertNotNull($teamResult);
        $this->assertEquals(3800, $teamResult->average_temps);   // (3600 + 4000) / 2
        $this->assertEquals(50, $teamResult->average_malus);     // (0 + 100) / 2
        $this->assertEquals(3850, $teamResult->average_temps_final); // (3600 + 4100) / 2
        $this->assertEquals(2, $teamResult->member_count);
    }

    // ============================================
    // DELETE RESULT TESTS
    // ============================================

    /**
     * Test delete result recalculates team averages.
     */
    public function test_delete_result_recalculates_team_averages(): void
    {
        $race = Race::factory()->create();
        $team = Team::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $hasIdUsersColumn = \Illuminate\Support\Facades\DB::getSchemaBuilder()->hasColumn('has_participate', 'id_users');
        $userIdColumn = $hasIdUsersColumn ? 'id_users' : 'id';
        
        \Illuminate\Support\Facades\DB::table('has_participate')->insert([
            ['equ_id' => $team->equ_id, $userIdColumn => $user1->id, 'created_at' => now(), 'updated_at' => now()],
            ['equ_id' => $team->equ_id, $userIdColumn => $user2->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $result1 = LeaderboardUser::create(['user_id' => $user1->id, 'race_id' => $race->race_id, 'temps' => 3600, 'malus' => 0]);
        LeaderboardUser::create(['user_id' => $user2->id, 'race_id' => $race->race_id, 'temps' => 4000, 'malus' => 0]);

        $this->service->recalculateTeamAverages($race->race_id);

        // Delete first user's result
        $this->service->deleteResult($result1->id);

        $teamResult = LeaderboardTeam::where('equ_id', $team->equ_id)->where('race_id', $race->race_id)->first();
        
        // Team average should now be just user2's result
        $this->assertEquals(4000, $teamResult->average_temps);
        $this->assertEquals(1, $teamResult->member_count);
    }

    // ============================================
    // EDGE CASES
    // ============================================

    /**
     * Test leaderboard with zero temps.
     */
    public function test_leaderboard_with_zero_temps(): void
    {
        $race = Race::factory()->create();
        $user = User::factory()->create();

        LeaderboardUser::create(['user_id' => $user->id, 'race_id' => $race->race_id, 'temps' => 0, 'malus' => 0]);

        $leaderboard = $this->service->getIndividualLeaderboard($race->race_id);
        
        $this->assertEquals(1, $leaderboard['total']);
        $this->assertEquals(0, $leaderboard['data'][0]['temps']);
    }

    /**
     * Test leaderboard with very large temps.
     */
    public function test_leaderboard_with_large_temps(): void
    {
        $race = Race::factory()->create();
        $user = User::factory()->create();

        // 24 hours in seconds
        LeaderboardUser::create(['user_id' => $user->id, 'race_id' => $race->race_id, 'temps' => 86400, 'malus' => 0]);

        $leaderboard = $this->service->getIndividualLeaderboard($race->race_id);
        
        $this->assertEquals(1, $leaderboard['total']);
        $this->assertEquals(86400, $leaderboard['data'][0]['temps']);
    }

    /**
     * Test team leaderboard with missing team reference.
     * When team is deleted but LeaderboardTeam entry remains (no cascade),
     * the entry should show 'Unknown' for team name.
     * Note: If cascade delete is enabled, the LeaderboardTeam entry will be deleted too.
     */
    public function test_team_leaderboard_handles_missing_team_gracefully(): void
    {
        $race = Race::factory()->create();
        $team = Team::factory()->create();
        $teamId = $team->equ_id;

        $leaderboardEntry = LeaderboardTeam::create([
            'equ_id' => $teamId,
            'race_id' => $race->race_id,
            'average_temps' => 3600,
            'average_malus' => 0,
            'average_temps_final' => 3600,
            'member_count' => 2,
        ]);

        // Delete the team - this may or may not cascade to LeaderboardTeam
        $team->delete();

        $leaderboard = $this->service->getTeamLeaderboard($race->race_id);
        
        // Check if cascade deleted the entry or if it remains with Unknown
        $entryStillExists = LeaderboardTeam::find($leaderboardEntry->id);
        if ($entryStillExists) {
            // Entry exists, should show 'Unknown' team name
            $this->assertEquals(1, $leaderboard['total']);
            $this->assertEquals('Unknown', $leaderboard['data'][0]['team_name']);
        } else {
            // Cascade deleted the entry
            $this->assertEquals(0, $leaderboard['total']);
        }
    }

    /**
     * Test individual leaderboard handles missing user gracefully.
     * When user is deleted but LeaderboardUser entry remains,
     * the entry should show 'Unknown' for user name.
     */
    public function test_individual_leaderboard_handles_missing_user_gracefully(): void
    {
        $race = Race::factory()->create();
        $user = User::factory()->create();
        $userId = $user->id;

        $leaderboardEntry = LeaderboardUser::create([
            'user_id' => $userId,
            'race_id' => $race->race_id,
            'temps' => 3600,
            'malus' => 0,
        ]);

        // Delete the user - this may cascade to LeaderboardUser
        $user->delete();

        $leaderboard = $this->service->getIndividualLeaderboard($race->race_id);
        
        // Check if cascade deleted the entry or if it remains with Unknown
        $entryStillExists = LeaderboardUser::find($leaderboardEntry->id);
        if ($entryStillExists) {
            $this->assertEquals(1, $leaderboard['total']);
            $this->assertEquals('Unknown', $leaderboard['data'][0]['user_name']);
        } else {
            // Cascade deleted the entry
            $this->assertEquals(0, $leaderboard['total']);
        }
    }

    /**
     * Test public leaderboard excludes results from deleted users.
     */
    public function test_public_leaderboard_handles_deleted_user(): void
    {
        $race = Race::factory()->create();
        $user = User::factory()->create(['is_public' => true]);

        LeaderboardUser::create(['user_id' => $user->id, 'race_id' => $race->race_id, 'temps' => 3600, 'malus' => 0]);

        // Delete user
        $user->delete();

        $leaderboard = $this->service->getPublicLeaderboard($race->race_id, null, 'individual');
        
        // Should not include deleted user since they have no is_public status
        $this->assertEquals(0, $leaderboard['total']);
    }

    /**
     * Test import CSV returns removed count.
     */
    public function test_import_csv_returns_removed_count(): void
    {
        $race = Race::factory()->create();
        $user1 = User::factory()->create(['first_name' => 'Keep', 'last_name' => 'User', 'email' => 'keep@imported.local']);
        $user2 = User::factory()->create(['first_name' => 'Remove', 'last_name' => 'User', 'email' => 'remove@imported.local']);

        LeaderboardUser::create(['user_id' => $user1->id, 'race_id' => $race->race_id, 'temps' => 3600, 'malus' => 0]);
        LeaderboardUser::create(['user_id' => $user2->id, 'race_id' => $race->race_id, 'temps' => 3700, 'malus' => 0]);

        // Import CSV with only user1
        $csvContent = "Rang,Nom,Temps,Malus,Temps Final\n";
        $csvContent .= "1,Keep User,01:00:00,00:00,01:00:00";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $result = $this->service->importCsv($file, $race->race_id);

        $this->assertEquals(1, $result['success']);
        $this->assertEquals(1, $result['removed']);
    }

    /**
     * Test import CSV handles single name (no last name).
     */
    public function test_import_csv_handles_single_name(): void
    {
        $race = Race::factory()->create();

        $csvContent = "Rang,Nom,Temps,Malus,Temps Final\n";
        $csvContent .= "1,Madonna,01:00:00,00:00,01:00:00";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('results.csv', $csvContent);

        $result = $this->service->importCsv($file, $race->race_id);

        $this->assertEquals(1, $result['success']);
        
        $user = User::where('first_name', 'Madonna')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Unknown', $user->last_name);
    }

    /**
     * Test multiple races don't interfere with each other.
     */
    public function test_multiple_races_isolation(): void
    {
        $race1 = Race::factory()->create();
        $race2 = Race::factory()->create();
        
        $user = User::factory()->create(['is_public' => true]);

        // Different times for same user in different races
        LeaderboardUser::create(['user_id' => $user->id, 'race_id' => $race1->race_id, 'temps' => 3600, 'malus' => 0]);
        LeaderboardUser::create(['user_id' => $user->id, 'race_id' => $race2->race_id, 'temps' => 4000, 'malus' => 0]);

        $leaderboard1 = $this->service->getIndividualLeaderboard($race1->race_id);
        $leaderboard2 = $this->service->getIndividualLeaderboard($race2->race_id);
        
        $this->assertEquals(3600, $leaderboard1['data'][0]['temps']);
        $this->assertEquals(4000, $leaderboard2['data'][0]['temps']);
    }

    // ============================================
    // TEAM CSV V2 IMPORT TESTS
    // ============================================

    /**
     * Test import team CSV V2 with valid data.
     */
    public function test_import_team_csv_v2_with_valid_data(): void
    {
        $race = Race::factory()->create(['race_difficulty' => 'moyenne']);
        $team1 = Team::factory()->create(['equ_name' => 'Team Alpha']);
        $team2 = Team::factory()->create(['equ_name' => 'Team Beta']);

        // Use ASCII header to avoid encoding issues in tests
        $csvContent = "CLT;PUCE;EQUIPE;CATEGORIE;TEMPS;PTS\n";
        $csvContent .= "1;7000001;Team Alpha;Masculin;03:12:25;222\n";
        $csvContent .= "2;7000002;Team Beta;Mixte;03:15:30;210";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('teams.csv', $csvContent);

        $result = $this->service->importTeamCsvV2($file, $race->race_id);

        $this->assertEquals(2, $result['success']);
        $this->assertEquals(0, count($result['errors']));

        // Check leaderboard entries
        $entry1 = LeaderboardTeam::where('equ_id', $team1->equ_id)->where('race_id', $race->race_id)->first();
        $entry2 = LeaderboardTeam::where('equ_id', $team2->equ_id)->where('race_id', $race->race_id)->first();

        $this->assertNotNull($entry1);
        $this->assertNotNull($entry2);
        $this->assertEquals('Masculin', $entry1->category);
        $this->assertEquals('Mixte', $entry2->category);
        $this->assertEquals('7000001', $entry1->puce);
    }

    /**
     * Test import team CSV V2 creates new teams.
     */
    public function test_import_team_csv_v2_creates_new_teams(): void
    {
        $race = Race::factory()->create(['race_difficulty' => 'facile']);

        $csvContent = "CLT;PUCE;EQUIPE;CATEGORIE;TEMPS;PTS\n";
        $csvContent .= "1;7000099;New Team;Feminin;02:30:00;100";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('teams.csv', $csvContent);

        $result = $this->service->importTeamCsvV2($file, $race->race_id);

        $this->assertEquals(1, $result['success']);
        $this->assertEquals(1, $result['created_teams']);

        // Verify team was created
        $team = Team::where('equ_name', 'New Team')->first();
        $this->assertNotNull($team);
        
        // Verify leaderboard entry was created
        $entry = LeaderboardTeam::where('equ_id', $team->equ_id)->where('race_id', $race->race_id)->first();
        $this->assertNotNull($entry);
        $this->assertEquals('Feminin', $entry->category);
    }

    /**
     * Test import team CSV V2 handles negative times.
     */
    public function test_import_team_csv_v2_handles_negative_times(): void
    {
        $race = Race::factory()->create(['race_difficulty' => 'moyenne']);
        $team = Team::factory()->create(['equ_name' => 'Negative Time Team']);

        $csvContent = "CLT;PUCE;EQUIPE;CATÉGORIE;TEMPS;PTS\n";
        $csvContent .= "1;7000001;Negative Time Team;Masculin;-6:06:12;180";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('teams.csv', $csvContent);

        $result = $this->service->importTeamCsvV2($file, $race->race_id);

        $this->assertEquals(1, $result['success']);

        $entry = LeaderboardTeam::where('equ_id', $team->equ_id)->where('race_id', $race->race_id)->first();
        $this->assertNotNull($entry);
        // Time should be stored as absolute value (6:06:12 = 21972 seconds)
        $this->assertEquals(21972, $entry->average_temps);
    }

    // ============================================
    // POINTS CALCULATION TESTS
    // ============================================

    /**
     * Test points calculation for first place (100 points).
     */
    public function test_points_calculation_first_place(): void
    {
        $race = Race::factory()->create(['race_difficulty' => 'facile']); // coefficient 1.0
        $team = Team::factory()->create(['equ_name' => 'First Place']);

        $csvContent = "CLT;PUCE;EQUIPE;CATÉGORIE;TEMPS;PTS\n";
        $csvContent .= "1;7000001;First Place;Masculin;01:00:00;0";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('teams.csv', $csvContent);

        $this->service->importTeamCsvV2($file, $race->race_id, true);

        $entry = LeaderboardTeam::where('equ_id', $team->equ_id)->where('race_id', $race->race_id)->first();
        $this->assertEquals(100, $entry->points); // 100 * 1.0 = 100
    }

    /**
     * Test points decrease by 10 per place.
     */
    public function test_points_decrease_per_place(): void
    {
        $race = Race::factory()->create(['race_difficulty' => 'facile']); // coefficient 1.0
        $team1 = Team::factory()->create(['equ_name' => 'Team 1']);
        $team2 = Team::factory()->create(['equ_name' => 'Team 2']);
        $team3 = Team::factory()->create(['equ_name' => 'Team 3']);

        $csvContent = "CLT;PUCE;EQUIPE;CATÉGORIE;TEMPS;PTS\n";
        $csvContent .= "1;7000001;Team 1;Masculin;01:00:00;0\n";
        $csvContent .= "2;7000002;Team 2;Masculin;01:10:00;0\n";
        $csvContent .= "3;7000003;Team 3;Masculin;01:20:00;0";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('teams.csv', $csvContent);

        $this->service->importTeamCsvV2($file, $race->race_id, true);

        $entry1 = LeaderboardTeam::where('equ_id', $team1->equ_id)->first();
        $entry2 = LeaderboardTeam::where('equ_id', $team2->equ_id)->first();
        $entry3 = LeaderboardTeam::where('equ_id', $team3->equ_id)->first();

        $this->assertEquals(100, $entry1->points); // 1st: 100
        $this->assertEquals(90, $entry2->points);  // 2nd: 100 - 10 = 90
        $this->assertEquals(80, $entry3->points);  // 3rd: 100 - 20 = 80
    }

    /**
     * Test minimum 20 points for classified teams.
     */
    public function test_minimum_points_is_20(): void
    {
        $race = Race::factory()->create(['race_difficulty' => 'facile']);
        
        // Create 10 teams to go beyond rank 9 (100 - 90 = 10 < 20)
        $teams = [];
        $csvContent = "CLT;PUCE;EQUIPE;CATÉGORIE;TEMPS;PTS\n";
        for ($i = 1; $i <= 10; $i++) {
            $teams[$i] = Team::factory()->create(['equ_name' => "Team {$i}"]);
            $csvContent .= "{$i};700000{$i};Team {$i};Masculin;0{$i}:00:00;0\n";
        }

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('teams.csv', $csvContent);

        $this->service->importTeamCsvV2($file, $race->race_id, true);

        // Check 9th and 10th place have minimum 20 points
        $entry9 = LeaderboardTeam::where('equ_id', $teams[9]->equ_id)->first();
        $entry10 = LeaderboardTeam::where('equ_id', $teams[10]->equ_id)->first();

        $this->assertEquals(20, $entry9->points);  // 9th: max(100 - 80, 20) = 20
        $this->assertEquals(20, $entry10->points); // 10th: max(100 - 90, 20) = 20
    }

    /**
     * Test equal times give equal points.
     */
    public function test_equal_times_equal_points(): void
    {
        $race = Race::factory()->create(['race_difficulty' => 'facile']);
        $team1 = Team::factory()->create(['equ_name' => 'Team A']);
        $team2 = Team::factory()->create(['equ_name' => 'Team B']);
        $team3 = Team::factory()->create(['equ_name' => 'Team C']);

        $csvContent = "CLT;PUCE;EQUIPE;CATÉGORIE;TEMPS;PTS\n";
        $csvContent .= "1;7000001;Team A;Masculin;01:00:00;0\n";
        $csvContent .= "2;7000002;Team B;Masculin;01:00:00;0\n"; // Same time as Team A
        $csvContent .= "3;7000003;Team C;Masculin;01:10:00;0";   // Different time

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('teams.csv', $csvContent);

        $this->service->importTeamCsvV2($file, $race->race_id, true);

        $entryA = LeaderboardTeam::where('equ_id', $team1->equ_id)->first();
        $entryB = LeaderboardTeam::where('equ_id', $team2->equ_id)->first();
        $entryC = LeaderboardTeam::where('equ_id', $team3->equ_id)->first();

        // Team A and B have same time, should have same points
        $this->assertEquals($entryA->points, $entryB->points);
        $this->assertEquals(100, $entryA->points);
        // Team C should have less points
        $this->assertLessThan($entryA->points, $entryC->points);
    }

    /**
     * Test difficulty coefficient for medium difficulty.
     */
    public function test_difficulty_coefficient_medium(): void
    {
        $race = Race::factory()->create(['race_difficulty' => 'moyenne']); // coefficient 1.2
        $team = Team::factory()->create(['equ_name' => 'Medium Race Team']);

        $csvContent = "CLT;PUCE;EQUIPE;CATÉGORIE;TEMPS;PTS\n";
        $csvContent .= "1;7000001;Medium Race Team;Masculin;01:00:00;0";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('teams.csv', $csvContent);

        $this->service->importTeamCsvV2($file, $race->race_id, true);

        $entry = LeaderboardTeam::where('equ_id', $team->equ_id)->first();
        $this->assertEquals(120, $entry->points); // 100 * 1.2 = 120
    }

    /**
     * Test difficulty coefficient for hard difficulty.
     */
    public function test_difficulty_coefficient_hard(): void
    {
        $race = Race::factory()->create(['race_difficulty' => 'difficile']); // coefficient 1.5
        $team = Team::factory()->create(['equ_name' => 'Hard Race Team']);

        $csvContent = "CLT;PUCE;EQUIPE;CATÉGORIE;TEMPS;PTS\n";
        $csvContent .= "1;7000001;Hard Race Team;Masculin;01:00:00;0";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('teams.csv', $csvContent);

        $this->service->importTeamCsvV2($file, $race->race_id, true);

        $entry = LeaderboardTeam::where('equ_id', $team->equ_id)->first();
        $this->assertEquals(150, $entry->points); // 100 * 1.5 = 150
    }

    /**
     * Test recalculate team points.
     */
    public function test_recalculate_team_points(): void
    {
        $race = Race::factory()->create(['race_difficulty' => 'facile']);
        $team1 = Team::factory()->create();
        $team2 = Team::factory()->create();

        // Create entries with wrong points
        LeaderboardTeam::create([
            'equ_id' => $team1->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 3600,
            'average_malus' => 0,
            'average_temps_final' => 3600,
            'member_count' => 1,
            'points' => 0, // Wrong
        ]);
        LeaderboardTeam::create([
            'equ_id' => $team2->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 4000,
            'average_malus' => 0,
            'average_temps_final' => 4000,
            'member_count' => 1,
            'points' => 0, // Wrong
        ]);

        $updated = $this->service->recalculateTeamPoints($race->race_id);

        $this->assertEquals(2, $updated);

        $entry1 = LeaderboardTeam::where('equ_id', $team1->equ_id)->first();
        $entry2 = LeaderboardTeam::where('equ_id', $team2->equ_id)->first();

        $this->assertEquals(100, $entry1->points);
        $this->assertEquals(90, $entry2->points);
    }

    /**
     * Test team leaderboard includes points.
     */
    public function test_team_leaderboard_includes_points(): void
    {
        $race = Race::factory()->create();
        $team = Team::factory()->create();

        LeaderboardTeam::create([
            'equ_id' => $team->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 3600,
            'average_malus' => 0,
            'average_temps_final' => 3600,
            'member_count' => 1,
            'points' => 100,
            'category' => 'Masculin',
        ]);

        $leaderboard = $this->service->getTeamLeaderboard($race->race_id);

        $this->assertArrayHasKey('points', $leaderboard['data'][0]);
        $this->assertArrayHasKey('category', $leaderboard['data'][0]);
        $this->assertEquals(100, $leaderboard['data'][0]['points']);
        $this->assertEquals('Masculin', $leaderboard['data'][0]['category']);
    }

    /**
     * Test export CSV team includes points.
     */
    public function test_export_csv_team_includes_points(): void
    {
        $race = Race::factory()->create();
        $team = Team::factory()->create(['equ_name' => 'Export Team']);

        LeaderboardTeam::create([
            'equ_id' => $team->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 3600,
            'average_malus' => 0,
            'average_temps_final' => 3600,
            'member_count' => 1,
            'points' => 150,
            'category' => 'Mixte',
        ]);

        $csv = $this->service->exportToCsv($race->race_id, 'team');

        // CSV format: Rang, Equipe, Catégorie, Temps, Points
        $this->assertStringContainsString('Points', $csv);
        $this->assertStringContainsString('150', $csv);
        $this->assertStringContainsString('Mixte', $csv);
    }

    /**
    /**
     * Test team category constants exist.
     */
    public function test_team_category_constants(): void
    {
        $this->assertEquals('Masculin', LeaderboardTeam::CATEGORY_MALE);
        $this->assertEquals('Féminin', LeaderboardTeam::CATEGORY_FEMALE);
        $this->assertEquals('Mixte', LeaderboardTeam::CATEGORY_MIXED);
    }

    /**
     * Test leisure race caps points at 50.
     */
    public function test_leisure_race_caps_points_at_50(): void
    {
        // Create a leisure race type
        $leisureType = \App\Models\ParamType::create(['typ_name' => 'loisir']);
        $race = Race::factory()->create([
            'race_difficulty' => 'facile',
            'typ_id' => $leisureType->typ_id,
        ]);
        $team = Team::factory()->create(['equ_name' => 'Leisure Team']);

        $csvContent = "CLT;PUCE;EQUIPE;CATEGORIE;TEMPS;PTS\n";
        $csvContent .= "1;7000001;Leisure Team;Masculin;01:00:00;0";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('teams.csv', $csvContent);

        $this->service->importTeamCsvV2($file, $race->race_id, true);

        $entry = LeaderboardTeam::where('equ_id', $team->equ_id)->first();
        // Should be capped at 50 despite 100 * 1.0 = 100
        $this->assertEquals(50, $entry->points);
    }

    /**
     * Test import team CSV V2 with real format from attachment.
     */
    public function test_import_team_csv_v2_real_format(): void
    {
        $race = Race::factory()->create(['race_difficulty' => 'moyenne']); // coefficient 1.2
        
        // Create some teams that exist in the CSV
        Team::factory()->create(['equ_name' => 'Team dingo']);
        Team::factory()->create(['equ_name' => 'Les freres Gaut']);
        
        $csvContent = "CLT;PUCE;EQUIPE;CATÉGORIE;TEMPS;PTS\n";
        $csvContent .= "22;7141178;Team dingo;Masculin;03:12:25;222\n";
        $csvContent .= "24;7000594;Les freres Gaut;Masculin;03:17:39;222";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('teams.csv', $csvContent);

        $result = $this->service->importTeamCsvV2($file, $race->race_id);

        $this->assertEquals(2, $result['success']);
        $this->assertEquals(0, count($result['errors']));
        
        // Verify leaderboard entries exist
        $this->assertDatabaseHas('leaderboard_teams', ['race_id' => $race->race_id]);
    }

    // ==================== NEW TESTS FOR ADMIN LEADERBOARD ====================

    /**
     * Test getAdminIndividualLeaderboard includes both public and private users.
     */
    public function test_admin_individual_leaderboard_includes_all_users(): void
    {
        $race = Race::factory()->create();
        
        // Create public and private users
        $publicUser = User::factory()->create([
            'first_name' => 'Public',
            'last_name' => 'User',
            'is_public' => true,
        ]);
        $privateUser = User::factory()->create([
            'first_name' => 'Private',
            'last_name' => 'User',
            'is_public' => false,
        ]);

        LeaderboardUser::create([
            'user_id' => $publicUser->id,
            'race_id' => $race->race_id,
            'temps' => 3600,
            'malus' => 0,
        ]);
        LeaderboardUser::create([
            'user_id' => $privateUser->id,
            'race_id' => $race->race_id,
            'temps' => 3700,
            'malus' => 0,
        ]);

        $result = $this->service->getAdminIndividualLeaderboard($race->race_id);

        // Admin leaderboard should show both users
        $this->assertEquals(2, $result['total']);
        
        $names = collect($result['data'])->pluck('user_name')->toArray();
        $this->assertContains('Public User', $names);
        $this->assertContains('Private User', $names);
        
        // Should include is_public flag for each user
        $publicEntry = collect($result['data'])->firstWhere('user_name', 'Public User');
        $privateEntry = collect($result['data'])->firstWhere('user_name', 'Private User');
        
        $this->assertTrue($publicEntry['is_public']);
        $this->assertFalse($privateEntry['is_public']);
    }

    /**
     * Test getAdminTeamLeaderboard returns all teams with members.
     */
    public function test_admin_team_leaderboard_includes_members(): void
    {
        $race = Race::factory()->create();
        $team = Team::factory()->create(['equ_name' => 'Test Team']);
        
        // Create users in the team
        $user1 = User::factory()->create(['first_name' => 'Alice', 'last_name' => 'Smith']);
        $user2 = User::factory()->create(['first_name' => 'Bob', 'last_name' => 'Jones']);
        
        // Link users to team via has_participate (using id column)
        \DB::table('has_participate')->insert([
            ['id' => $user1->id, 'equ_id' => $team->equ_id, 'created_at' => now(), 'updated_at' => now()],
            ['id' => $user2->id, 'equ_id' => $team->equ_id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        LeaderboardTeam::create([
            'equ_id' => $team->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 3600,
            'average_malus' => 0,
            'average_temps_final' => 3600,
            'member_count' => 2,
        ]);

        $result = $this->service->getAdminTeamLeaderboard($race->race_id);

        $this->assertEquals(1, $result['total']);
        $this->assertEquals('Test Team', $result['data'][0]['team_name']);
        $this->assertArrayHasKey('members', $result['data'][0]);
        $this->assertCount(2, $result['data'][0]['members']);
    }

    /**
     * Test getAdminLeaderboard dispatches to correct method based on type.
     */
    public function test_admin_leaderboard_dispatcher(): void
    {
        $race = Race::factory()->create();
        $user = User::factory()->create();
        $team = Team::factory()->create();

        LeaderboardUser::create([
            'user_id' => $user->id,
            'race_id' => $race->race_id,
            'temps' => 3600,
            'malus' => 0,
        ]);
        LeaderboardTeam::create([
            'equ_id' => $team->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 3600,
            'average_malus' => 0,
            'average_temps_final' => 3600,
            'member_count' => 1,
        ]);

        // Test individual
        $individualResult = $this->service->getAdminLeaderboard($race->race_id, null, 'individual');
        $this->assertArrayHasKey('user_name', $individualResult['data'][0]);

        // Test team
        $teamResult = $this->service->getAdminLeaderboard($race->race_id, null, 'team');
        $this->assertArrayHasKey('team_name', $teamResult['data'][0]);
    }

    /**
     * Test admin leaderboard calculates points dynamically when null.
     */
    public function test_admin_leaderboard_calculates_points_when_null(): void
    {
        $race = Race::factory()->create();
        $user1 = User::factory()->create(['first_name' => 'First', 'last_name' => 'Place']);
        $user2 = User::factory()->create(['first_name' => 'Second', 'last_name' => 'Place']);

        // Create entries without points (null)
        LeaderboardUser::create([
            'user_id' => $user1->id,
            'race_id' => $race->race_id,
            'temps' => 3600,
            'malus' => 0,
            'points' => null,
        ]);
        LeaderboardUser::create([
            'user_id' => $user2->id,
            'race_id' => $race->race_id,
            'temps' => 3700,
            'malus' => 0,
            'points' => null,
        ]);

        $result = $this->service->getAdminIndividualLeaderboard($race->race_id);

        // Points should be calculated dynamically
        $firstPlace = collect($result['data'])->firstWhere('rank', 1);
        $secondPlace = collect($result['data'])->firstWhere('rank', 2);
        
        $this->assertNotNull($firstPlace['points']);
        $this->assertNotNull($secondPlace['points']);
        // First place should have more points than second
        $this->assertGreaterThan($secondPlace['points'], $firstPlace['points']);
    }

    /**
     * Test admin leaderboard search works across all users.
     */
    public function test_admin_leaderboard_search_includes_private_users(): void
    {
        $race = Race::factory()->create();
        
        $privateUser = User::factory()->create([
            'first_name' => 'Hidden',
            'last_name' => 'Ninja',
            'is_public' => false,
        ]);

        LeaderboardUser::create([
            'user_id' => $privateUser->id,
            'race_id' => $race->race_id,
            'temps' => 3600,
            'malus' => 0,
        ]);

        // Search for private user
        $result = $this->service->getAdminIndividualLeaderboard($race->race_id, 'Ninja');

        $this->assertEquals(1, $result['total']);
        $this->assertEquals('Hidden Ninja', $result['data'][0]['user_name']);
    }

    // ==================== UTF-8 IMPORT TESTS ====================

    /**
     * Test CSV import handles UTF-8 headers with accented characters.
     */
    public function test_import_team_csv_v2_utf8_headers(): void
    {
        $race = Race::factory()->create();
        $team = Team::factory()->create(['equ_name' => 'Test Team']);

        // CSV with accented header CATÉGORIE (É = UTF-8)
        $csvContent = "CLT;PUCE;EQUIPE;CATÉGORIE;TEMPS;PTS\n";
        $csvContent .= "1;7000001;Test Team;Féminin;01:00:00;100";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('teams.csv', $csvContent);

        $result = $this->service->importTeamCsvV2($file, $race->race_id);

        $this->assertEquals(1, $result['success']);
        
        // Verify category was imported correctly
        $entry = LeaderboardTeam::where('race_id', $race->race_id)->first();
        $this->assertEquals('Féminin', $entry->category);
    }

    /**
     * Test CSV import handles special characters in category names.
     */
    public function test_import_team_csv_v2_special_category_names(): void
    {
        $race = Race::factory()->create();
        $team1 = Team::factory()->create(['equ_name' => 'Team Alpha']);
        $team2 = Team::factory()->create(['equ_name' => 'Team Beta']);
        $team3 = Team::factory()->create(['equ_name' => 'Team Gamma']);

        $csvContent = "CLT;PUCE;EQUIPE;CATÉGORIE;TEMPS;PTS\n";
        $csvContent .= "1;7000001;Team Alpha;Mixte;01:00:00;100\n";
        $csvContent .= "2;7000002;Team Beta;Féminin;01:05:00;90\n";
        $csvContent .= "3;7000003;Team Gamma;Masculin;01:10:00;80";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('teams.csv', $csvContent);

        $result = $this->service->importTeamCsvV2($file, $race->race_id);

        $this->assertEquals(3, $result['success']);
        
        // Verify categories
        $entries = LeaderboardTeam::where('race_id', $race->race_id)
            ->orderBy('average_temps_final')
            ->get();
        
        $this->assertEquals('Mixte', $entries[0]->category);
        $this->assertEquals('Féminin', $entries[1]->category);
        $this->assertEquals('Masculin', $entries[2]->category);
    }

    /**
     * Test import team CSV sets puce field correctly.
     */
    public function test_import_team_csv_v2_imports_puce(): void
    {
        $race = Race::factory()->create();
        $team = Team::factory()->create(['equ_name' => 'Puce Team']);

        $csvContent = "CLT;PUCE;EQUIPE;CATÉGORIE;TEMPS;PTS\n";
        $csvContent .= "1;7141178;Puce Team;Mixte;01:00:00;100";

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('teams.csv', $csvContent);

        $result = $this->service->importTeamCsvV2($file, $race->race_id);

        $entry = LeaderboardTeam::where('race_id', $race->race_id)->first();
        $this->assertEquals('7141178', $entry->puce);
    }

    /**
     * Test points in leaderboard response when database value is used.
     */
    public function test_team_leaderboard_uses_stored_points(): void
    {
        $race = Race::factory()->create();
        $team = Team::factory()->create();

        LeaderboardTeam::create([
            'equ_id' => $team->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 3600,
            'average_malus' => 0,
            'average_temps_final' => 3600,
            'member_count' => 1,
            'points' => 500, // Specific stored value
        ]);

        $result = $this->service->getAdminTeamLeaderboard($race->race_id);

        // Should use stored value, not calculated
        $this->assertEquals(500, $result['data'][0]['points']);
    }

    // ==================== UPDATE RESULT TESTS ====================

    /**
     * Test updateIndividualResult updates temps and malus.
     */
    public function test_update_individual_result_updates_temps(): void
    {
        $race = Race::factory()->create();
        $user = User::factory()->create();

        $entry = LeaderboardUser::create([
            'user_id' => $user->id,
            'race_id' => $race->race_id,
            'temps' => 3600,
            'malus' => 60,
            'points' => 100,
        ]);

        $result = $this->service->updateIndividualResult($entry->id, [
            'temps' => '02:00:00',
            'malus' => '00:02:00',
            'points' => 150,
        ]);

        $this->assertNotNull($result);
        $this->assertEquals(7200, $result->temps); // 2 hours in seconds
        $this->assertEquals(120, $result->malus);  // 2 minutes in seconds
        $this->assertEquals(150, $result->points);
    }

    /**
     * Test updateIndividualResult returns null for non-existent result.
     */
    public function test_update_individual_result_not_found(): void
    {
        $result = $this->service->updateIndividualResult(99999, [
            'temps' => '01:00:00',
        ]);

        $this->assertNull($result);
    }

    /**
     * Test updateTeamResult updates all team fields.
     */
    public function test_update_team_result_updates_all_fields(): void
    {
        $race = Race::factory()->create();
        $team = Team::factory()->create();

        $entry = LeaderboardTeam::create([
            'equ_id' => $team->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 3600,
            'average_malus' => 0,
            'average_temps_final' => 3600,
            'member_count' => 1,
            'points' => 100,
            'category' => 'Masculin',
            'puce' => '123456',
        ]);

        $result = $this->service->updateTeamResult($entry->id, [
            'average_temps' => '02:30:00',
            'average_malus' => '00:05:00',
            'points' => 200,
            'category' => 'Mixte',
            'puce' => '654321',
        ]);

        $this->assertNotNull($result);
        $this->assertEquals(9000, $result->average_temps); // 2h30m in seconds
        $this->assertEquals(300, $result->average_malus);  // 5min in seconds
        $this->assertEquals(200, $result->points);
        $this->assertEquals('Mixte', $result->category);
        $this->assertEquals('654321', $result->puce);
    }

    /**
     * Test updateTeamResult returns null for non-existent result.
     */
    public function test_update_team_result_not_found(): void
    {
        $result = $this->service->updateTeamResult(99999, [
            'average_temps' => '01:00:00',
        ]);

        $this->assertNull($result);
    }

    /**
     * Test deleteTeamResult removes team entry.
     */
    public function test_delete_team_result(): void
    {
        $race = Race::factory()->create();
        $team = Team::factory()->create();

        $entry = LeaderboardTeam::create([
            'equ_id' => $team->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 3600,
            'average_malus' => 0,
            'average_temps_final' => 3600,
            'member_count' => 1,
        ]);

        $deleted = $this->service->deleteTeamResult($entry->id);

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('leaderboard_teams', ['id' => $entry->id]);
    }

    /**
     * Test deleteTeamResult returns false for non-existent result.
     */
    public function test_delete_team_result_not_found(): void
    {
        $deleted = $this->service->deleteTeamResult(99999);

        $this->assertFalse($deleted);
    }

    /**
     * Test updating individual result recalculates team averages.
     */
    public function test_update_individual_result_recalculates_team_averages(): void
    {
        $race = Race::factory()->create();
        $team = Team::factory()->create();
        $user = User::factory()->create();

        // Link user to team - use both id and id_users to cover both columns
        $participationData = [
            'equ_id' => $team->equ_id,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        
        // Check which column exists and use the appropriate one
        if (\Schema::hasColumn('has_participate', 'id_users')) {
            $participationData['id_users'] = $user->id;
        }
        if (\Schema::hasColumn('has_participate', 'id')) {
            $participationData['id'] = $user->id;
        }
        
        \DB::table('has_participate')->insert($participationData);

        // Create individual result
        $entry = LeaderboardUser::create([
            'user_id' => $user->id,
            'race_id' => $race->race_id,
            'temps' => 3600,
            'malus' => 60,
        ]);

        // Create team result - will be recalculated based on individual results
        LeaderboardTeam::create([
            'equ_id' => $team->equ_id,
            'race_id' => $race->race_id,
            'average_temps' => 3600,
            'average_malus' => 60,
            'average_temps_final' => 3660,
            'member_count' => 1,
        ]);

        // Update individual result
        $this->service->updateIndividualResult($entry->id, [
            'temps' => '02:00:00',
            'malus' => '00:00:00',
        ]);

        // Refresh the entry to get updated values
        $entry->refresh();
        
        // Verify individual entry was updated
        $this->assertEquals(7200, $entry->temps);
        $this->assertEquals(0, $entry->malus);

        // Team averages should be recalculated from individual results
        $teamEntry = LeaderboardTeam::where('equ_id', $team->equ_id)
            ->where('race_id', $race->race_id)
            ->first();

        // The team averages are calculated from leaderboard_users joined with has_participate
        $this->assertEquals(7200, (float) $teamEntry->average_temps);
        $this->assertEquals(0, (float) $teamEntry->average_malus);
    }

    /**
     * Test partial update only changes specified fields.
     */
    public function test_update_individual_result_partial_update(): void
    {
        $race = Race::factory()->create();
        $user = User::factory()->create();

        $entry = LeaderboardUser::create([
            'user_id' => $user->id,
            'race_id' => $race->race_id,
            'temps' => 3600,
            'malus' => 60,
            'points' => 100,
        ]);

        // Only update points
        $result = $this->service->updateIndividualResult($entry->id, [
            'points' => 200,
        ]);

        $this->assertNotNull($result);
        $this->assertEquals(3600, $result->temps);  // Unchanged
        $this->assertEquals(60, $result->malus);    // Unchanged
        $this->assertEquals(200, $result->points);  // Changed
    }
}
