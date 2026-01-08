<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Race;
use App\Models\Team;
use App\Models\RaceRegistration;
use App\Models\TeamInvitation;

/**
 * Seeder for testing race registration system.
 * Creates sample registrations with both permanent and temporary teams.
 */
class RaceRegistrationTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create test users
        $user1 = User::firstOrCreate(
            ['email' => 'test.registration1@example.com'],
            [
                'first_name' => 'Test',
                'last_name' => 'Registration1',
                'password' => bcrypt('password'),
                'birth_date' => '1990-01-01',
                'address' => '123 Test St',
                'phone' => '0123456789',
            ]
        );

        $user2 = User::firstOrCreate(
            ['email' => 'test.registration2@example.com'],
            [
                'first_name' => 'Test',
                'last_name' => 'Registration2',
                'password' => bcrypt('password'),
                'birth_date' => '1992-05-15',
                'address' => '456 Test Ave',
                'phone' => '0987654321',
            ]
        );

        $user3 = User::firstOrCreate(
            ['email' => 'test.registration3@example.com'],
            [
                'first_name' => 'Test',
                'last_name' => 'Registration3',
                'password' => bcrypt('password'),
                'birth_date' => '1988-08-20',
                'address' => '789 Test Blvd',
                'phone' => '0555555555',
            ]
        );

        // Get first available race
        $race = Race::where('race_date_start', '>', now())->first();

        if (!$race) {
            $this->command->error('No future races found. Please create a race first.');
            return;
        }

        $this->command->info("Using race: {$race->race_name} (ID: {$race->race_id})");

        // Get or create a test team
        $team = Team::firstOrCreate(
            ['equ_name' => 'Test Registration Team'],
            [
                'user_id' => $user1->id,
            ]
        );

        // Add users to team via has_participate table
        $teamUsers = [$user1, $user2, $user3];
        foreach ($teamUsers as $user) {
            \DB::table('has_participate')->updateOrInsert(
                [
                    'equ_id' => $team->equ_id,
                    'id_users' => $user->id,
                ],
                [
                    'is_leader' => $user->id === $user1->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info("Team created/updated: {$team->equ_name} (ID: {$team->equ_id}) with 3 members");

        // Registration 1: Permanent team (user1 registers with existing team)
        $registration1 = RaceRegistration::updateOrCreate(
            [
                'race_id' => $race->race_id,
                'user_id' => $user1->id,
            ],
            [
                'equ_id' => $team->equ_id,
                'is_team_leader' => true,
                'is_temporary_team' => false,
                'is_creator_participating' => true,
                'status' => 'confirmed',
            ]
        );

        $this->command->info("✓ Registration 1: {$user1->name} with permanent team");

        // Registration 2: Temporary team (user2 creates temp team with invitations)
        $registration2 = RaceRegistration::updateOrCreate(
            [
                'race_id' => $race->race_id,
                'user_id' => $user2->id,
            ],
            [
                'equ_id' => null,
                'is_team_leader' => true,
                'is_temporary_team' => true,
                'temporary_team_data' => [
                    [
                        'user_id' => $user3->id,
                        'email' => $user3->email,
                        'name' => $user3->name,
                        'status' => 'pending',
                    ],
                    [
                        'user_id' => null,
                        'email' => 'invited.user@example.com',
                        'name' => 'invited.user',
                        'status' => 'pending_account',
                    ],
                ],
                'is_creator_participating' => true,
                'status' => 'pending',
            ]
        );

        $this->command->info("✓ Registration 2: {$user2->name} with temporary team (2 members)");

        $this->command->info("\n=== Test Data Summary ===");
        $this->command->info("Race: {$race->race_name}");
        $this->command->info("Registrations: 2");
        $this->command->info("  - Permanent team: 3 participants");
        $this->command->info("  - Temporary team: 1 creator + 2 invited = 3 participants");
        $this->command->info("Total participants: 6");
        $this->command->info("\nTest users:");
        $this->command->info("  - {$user1->email} / password");
        $this->command->info("  - {$user2->email} / password");
        $this->command->info("  - {$user3->email} / password");
        $this->command->info("\nNote: Team invitations skipped due to schema complexity.");
    }
}
