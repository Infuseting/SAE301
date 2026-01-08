<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Race;
use App\Models\Team;
use App\Models\Raid;
use App\Models\Club;
use App\Models\Member;
use App\Models\RegistrationPeriod;
use App\Models\LeaderboardUser;
use App\Models\LeaderboardTeam;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder for populating leaderboard test data.
 * Creates users, races, teams, and their leaderboard results.
 */
class LeaderboardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing param data
        $paramRunner = DB::table('param_runners')->first();
        $paramTeam = DB::table('param_teams')->first();
        $paramType = DB::table('param_type')->first();
        $registrationPeriod = DB::table('registration_period')->first();
        $member = DB::table('members')->first();

        if (!$paramRunner || !$paramTeam || !$paramType || !$registrationPeriod || !$member) {
            $this->command->error('Missing required param data. Please ensure param tables are seeded first.');
            return;
        }

        // Create club if not exists
        $club = DB::table('clubs')->first();
        if (!$club) {
            $clubId = DB::table('clubs')->insertGetId([
                'club_name' => 'Club Trail Aventure',
                'club_street' => '123 Rue du Sport',
                'club_city' => 'Lyon',
                'club_postal_code' => '69001',
                'club_number' => '1',
                'adh_id' => $member->adh_id,
                'adh_id_dirigeant' => $member->adh_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $club = (object) ['club_id' => $clubId];
            $this->command->info('Created club: Club Trail Aventure');
        }

        // Create raid if not exists
        $raid = DB::table('raids')->first();
        if (!$raid) {
            $raidId = DB::table('raids')->insertGetId([
                'raid_name' => 'Raid Aventure 2026',
                'raid_description' => 'Un raid aventure exceptionnel à travers les Alpes',
                'adh_id' => $member->adh_id,
                'clu_id' => $club->club_id,
                'ins_id' => $registrationPeriod->ins_id,
                'raid_date_start' => now()->addMonth(),
                'raid_date_end' => now()->addMonth()->addDays(2),
                'raid_contact' => 'contact@raid-aventure.fr',
                'raid_site_url' => 'https://raid-aventure.fr',
                'raid_street' => '456 Avenue de la Montagne',
                'raid_city' => 'Chamonix',
                'raid_postal_code' => '74400',
                'raid_number' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $raid = (object) ['raid_id' => $raidId];
            $this->command->info('Created raid: Raid Aventure 2026');
        }

        // Create races
        $racesData = [
            ['race_name' => 'Trail des Cimes - 42km', 'race_date_start' => now()->subMonths(2), 'race_date_end' => now()->subMonths(2)->addDay()],
            ['race_name' => 'Marathon des Alpes - 21km', 'race_date_start' => now()->subMonth(), 'race_date_end' => now()->subMonth()->addDay()],
            ['race_name' => 'Ultra Trail Mont-Blanc - 100km', 'race_date_start' => now()->subWeeks(2), 'race_date_end' => now()->subWeeks(2)->addDays(2)],
        ];

        $raceIds = [];
        foreach ($racesData as $raceData) {
            $existingRace = DB::table('races')->where('race_name', $raceData['race_name'])->first();
            if ($existingRace) {
                $raceIds[] = $existingRace->race_id;
            } else {
                $raceId = DB::table('races')->insertGetId([
                    'race_name' => $raceData['race_name'],
                    'race_date_start' => $raceData['race_date_start'],
                    'race_date_end' => $raceData['race_date_end'],
                    'race_reduction' => rand(0, 15),
                    'race_meal_price' => rand(10, 25),
                    'race_duration_minutes' => rand(120, 600),
                    'raid_id' => $raid->raid_id,
                    'adh_id' => $member->adh_id,
                    'pac_id' => $paramRunner->pac_id,
                    'pae_id' => $paramTeam->pae_id,
                    'race_difficulty' => 'Medium',
                    'typ_id' => $paramType->typ_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $raceIds[] = $raceId;
                $this->command->info("Created race: {$raceData['race_name']}");
            }
        }

        // Create test users
        $usersData = [
            ['first_name' => 'Jean', 'last_name' => 'Dupont', 'email' => 'jean.dupont@test.fr', 'is_public' => true],
            ['first_name' => 'Marie', 'last_name' => 'Martin', 'email' => 'marie.martin@test.fr', 'is_public' => true],
            ['first_name' => 'Pierre', 'last_name' => 'Bernard', 'email' => 'pierre.bernard@test.fr', 'is_public' => true],
            ['first_name' => 'Sophie', 'last_name' => 'Petit', 'email' => 'sophie.petit@test.fr', 'is_public' => true],
            ['first_name' => 'Lucas', 'last_name' => 'Robert', 'email' => 'lucas.robert@test.fr', 'is_public' => false],
            ['first_name' => 'Emma', 'last_name' => 'Richard', 'email' => 'emma.richard@test.fr', 'is_public' => true],
            ['first_name' => 'Thomas', 'last_name' => 'Moreau', 'email' => 'thomas.moreau@test.fr', 'is_public' => true],
            ['first_name' => 'Léa', 'last_name' => 'Simon', 'email' => 'lea.simon@test.fr', 'is_public' => true],
        ];

        $userIds = [];
        foreach ($usersData as $userData) {
            $existingUser = DB::table('users')->where('email', $userData['email'])->first();
            if ($existingUser) {
                $userIds[] = $existingUser->id;
            } else {
                $userId = DB::table('users')->insertGetId([
                    'first_name' => $userData['first_name'],
                    'last_name' => $userData['last_name'],
                    'email' => $userData['email'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'is_public' => $userData['is_public'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $userIds[] = $userId;
                $this->command->info("Created user: {$userData['first_name']} {$userData['last_name']}");
            }
        }

        // Create teams
        $teamsData = [
            ['equ_name' => 'Les Faucons Rapides'],
            ['equ_name' => 'Les Aigles des Montagnes'],
            ['equ_name' => 'Les Loups Solitaires'],
        ];

        $teamIds = [];
        foreach ($teamsData as $teamData) {
            $existingTeam = DB::table('teams')->where('equ_name', $teamData['equ_name'])->first();
            if ($existingTeam) {
                $teamIds[] = $existingTeam->equ_id;
            } else {
                $teamId = DB::table('teams')->insertGetId([
                    'equ_name' => $teamData['equ_name'],
                    'adh_id' => $member->adh_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $teamIds[] = $teamId;
                $this->command->info("Created team: {$teamData['equ_name']}");
            }
        }

        // Link users to teams via has_participate
        $teamAssignments = [
            [$teamIds[0], [$userIds[0], $userIds[1], $userIds[2]]],
            [$teamIds[1], [$userIds[3], $userIds[4], $userIds[5]]],
            [$teamIds[2], [$userIds[6], $userIds[7]]],
        ];

        foreach ($teamAssignments as [$teamId, $teamUserIds]) {
            foreach ($teamUserIds as $userId) {
                $exists = DB::table('has_participate')
                    ->where('id', $userId)
                    ->where('equ_id', $teamId)
                    ->exists();
                if (!$exists) {
                    DB::table('has_participate')->insert([
                        'id' => $userId,
                        'equ_id' => $teamId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // Create individual leaderboard results
        $this->command->info('Creating individual leaderboard results...');
        
        $individualCount = 0;
        foreach ($raceIds as $raceIndex => $raceId) {
            foreach ($userIds as $userIndex => $userId) {
                // Generate realistic times based on race type
                $raceName = $racesData[$raceIndex]['race_name'] ?? '';
                $baseTime = match(true) {
                    str_contains($raceName, '100km') => 36000 + rand(0, 18000), // 10-15 hours
                    str_contains($raceName, '42km') => 10800 + rand(0, 7200),  // 3-5 hours
                    default => 5400 + rand(0, 3600),                           // 1.5-2.5 hours
                };
                
                $temps = $baseTime + ($userIndex * rand(100, 500));
                $malus = rand(0, 5) > 3 ? rand(30, 300) : 0;

                $exists = DB::table('leaderboard_users')
                    ->where('user_id', $userId)
                    ->where('race_id', $raceId)
                    ->exists();

                if (!$exists) {
                    DB::table('leaderboard_users')->insert([
                        'user_id' => $userId,
                        'race_id' => $raceId,
                        'temps' => round($temps, 2),
                        'malus' => round($malus, 2),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $individualCount++;
                }
            }
        }

        // Create team leaderboard results
        $this->command->info('Creating team leaderboard results...');
        
        $teamCount = 0;
        foreach ($raceIds as $raceId) {
            foreach ($teamAssignments as $teamIndex => [$teamId, $teamUserIds]) {
                $memberResults = DB::table('leaderboard_users')
                    ->where('race_id', $raceId)
                    ->whereIn('user_id', $teamUserIds)
                    ->get();

                if ($memberResults->count() > 0) {
                    $avgTemps = $memberResults->avg('temps');
                    $avgMalus = $memberResults->avg('malus');
                    $avgTempsFinal = $avgTemps + $avgMalus;

                    $exists = DB::table('leaderboard_teams')
                        ->where('equ_id', $teamId)
                        ->where('race_id', $raceId)
                        ->exists();

                    if (!$exists) {
                        DB::table('leaderboard_teams')->insert([
                            'equ_id' => $teamId,
                            'race_id' => $raceId,
                            'average_temps' => round($avgTemps, 2),
                            'average_malus' => round($avgMalus, 2),
                            'average_temps_final' => round($avgTempsFinal, 2),
                            'member_count' => $memberResults->count(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $teamCount++;
                    }
                }
            }
        }

        $this->command->info('Leaderboard seeding completed!');
        $this->command->info("Created {$individualCount} individual results");
        $this->command->info("Created {$teamCount} team results");
    }
}
