<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Retrieve users that already exist in the database
        $users = User::all();

        // Small safety check: if no users, stop to avoid bugs
        if ($users->count() === 0) {
            $this->command->info('No users found. Run the UserSeeder first!');
            return;
        }

        // 2. Create 10 teams
        Team::factory(10)
            ->create()
            ->each(function ($team) use ($users) {
                // 3. Take some random users (between 1 and 3)
                $usersToAttach = $users->random(rand(1, 3));
                
                // 4. Fill the has_participate table automatically
                $team->users()->attach($usersToAttach);
            });
    }
}
