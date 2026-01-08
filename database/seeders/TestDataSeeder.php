<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Raid;
use App\Models\Race;
use App\Models\RaceType;
use App\Models\TeamParam;

class TestDataSeeder extends Seeder
{
    public function run()
    {
        $user = User::first();
        if (!$user) {
            $this->command->error('Aucun utilisateur trouvé.');
            return;
        }

        // Créer un Raid
        $raid = Raid::create([
            'raid_name' => 'Raid Test Integration ' . rand(100, 999),
            'raid_location' => 'Forêt de Brocéliande',
            'raid_date_start' => now()->addMonths(1),
            'raid_date_end' => now()->addMonths(1)->addDays(1),
            'raid_description' => 'Un raid de test créé automatiquement.',
            'club_id' => 4,
            'user_id' => $user->id,
        ]);

        $raceType = RaceType::firstOrCreate(['typ_name' => 'Découverte']);

        // Créer une Course
        $race = Race::create([
            'raid_id' => $raid->raid_id,
            'race_name' => 'Course Découverte ' . rand(10, 99) . 'km',
            'race_date_start' => now()->addMonths(1)->addHours(9),
            'race_date_end' => now()->addMonths(1)->addHours(14),
            'race_type_id' => $raceType->typ_id,
            'race_difficulty' => 'Facile',
            'race_description' => 'Parcours idéal pour débuter.',
            'price_major' => 20.00,
            'price_minor' => 15.00,
            'price_adherent' => 10.00,
            'organizer_id' => $user->adh_id,
        ]);

        // Créer les paramètres d'équipe
        TeamParam::create([
            'race_id' => $race->race_id,
            'pae_nb_min' => 2,
            'pae_nb_max' => 100,
            'pae_team_count_min' => 2,
            'pae_team_count_max' => 4,
        ]);

        $this->command->info('Raid et Course créés avec succès !');
        $this->command->info('Race ID: ' . $race->race_id);
    }
}
