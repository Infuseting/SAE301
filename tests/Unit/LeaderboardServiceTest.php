<?php

namespace Tests\Unit;

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
     */
    public function test_export_to_csv_individual(): void
    {
        $race = Race::factory()->create();
        $user = User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);

        $this->service->addResult($user->id, $race->race_id, 3661.50, 60.00);

        $csv = $this->service->exportToCsv($race->race_id, 'individual');

        $this->assertStringContainsString('Rang', $csv);
        $this->assertStringContainsString('Nom', $csv);
        $this->assertStringContainsString('John Doe', $csv);
    }

    /**
     * Test exportToCsv generates valid CSV for teams.
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
        ]);

        $csv = $this->service->exportToCsv($race->race_id, 'team');

        $this->assertStringContainsString('Equipe', $csv);
        $this->assertStringContainsString('Super Team', $csv);
        $this->assertStringContainsString('Membres', $csv);
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

        // Link users to team via has_participate (without race_id as it doesn't exist in schema)
        \DB::table('has_participate')->insert([
            ['id' => $user1->id, 'equ_id' => $team->equ_id, 'created_at' => now(), 'updated_at' => now()],
            ['id' => $user2->id, 'equ_id' => $team->equ_id, 'created_at' => now(), 'updated_at' => now()],
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
     */
    public function test_import_csv_does_not_duplicate_existing_users(): void
    {
        $race = Race::factory()->create();
        
        // Create existing users BEFORE import
        $existingUser1 = User::factory()->create(['first_name' => 'Jean', 'last_name' => 'Dupont']);
        $existingUser2 = User::factory()->create(['first_name' => 'Marie', 'last_name' => 'Martin']);
        
        // Count users before import
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

        // Verify NO new teams were created (users already exist)
        $teamCountAfter = Team::count();
        $this->assertEquals($teamCountBefore, $teamCountAfter, 'No new teams should be created for existing users');

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
}
