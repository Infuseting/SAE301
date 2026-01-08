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
        // 1. On récupère les utilisateurs qui existent déjà en base
        $users = User::all();

        // Petite sécurité : si pas de users, on arrête pour éviter le bug
        if ($users->count() === 0) {
            $this->command->info('Aucun utilisateur trouvé. Lancez d\'abord le UserSeeder !');
            return;
        }

        // 2. On crée 10 équipes
        Team::factory(10)
            ->create()
            ->each(function ($team) use ($users) {
                // 3. On prend quelques users au hasard (entre 1 et 3)
                $usersToAttach = $users->random(rand(1, 3));
                
                // 4. On remplit la table has_participate automatiquement
                $team->users()->attach($usersToAttach);
            });
    }
}
