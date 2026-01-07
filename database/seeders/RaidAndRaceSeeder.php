<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\Member;
use App\Models\Raid;
use App\Models\Race;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RaidAndRaceSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the raids and races table with test data.
     */
    public function run(): void
    {
        // Create minimal required data if it doesn't exist
        $registrationPeriod = DB::table('registration_period')->first();
        if (!$registrationPeriod) {
            DB::table('registration_period')->insert([
                'ins_id' => 1,
                'ins_start_date' => now(),
                'ins_end_date' => now()->addMonths(6),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $registrationPeriod = DB::table('registration_period')->first();
        }

        $leaderboard = DB::table('leaderboards')->first();
        if (!$leaderboard) {
            DB::table('leaderboards')->insert([
                'cla_id' => 1,
                'cla_name' => 'Default Leaderboard',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $leaderboard = DB::table('leaderboards')->first();
        }

        $paramRunner = DB::table('param_runners')->first();
        if (!$paramRunner) {
            DB::table('param_runners')->insert([
                'pac_id' => 1,
                'pac_nb_min' => 1,
                'pac_nb_max' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $paramRunner = DB::table('param_runners')->first();
        }

        $paramTeam = DB::table('param_teams')->first();
        if (!$paramTeam) {
            DB::table('param_teams')->insert([
                'pae_id' => 1,
                'pae_nb_min' => 2,
                'pae_nb_max' => 5,
                'pae_team_count_max' => 50,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $paramTeam = DB::table('param_teams')->first();
        }

        $paramDifficulty = DB::table('param_difficulty')->first();
        if (!$paramDifficulty) {
            DB::table('param_difficulty')->insert([
                'dif_id' => 1,
                'dif_level' => 'Medium',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $paramDifficulty = DB::table('param_difficulty')->first();
        }

        $paramType = DB::table('param_type')->first();
        if (!$paramType) {
            DB::table('param_type')->insert([
                'typ_id' => 1,
                'typ_name' => 'Running',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $paramType = DB::table('param_type')->first();
        }

        // Create age categories if they don't exist
        $ageCategories = DB::table('price_age_category')->get();
        if ($ageCategories->isEmpty()) {
            DB::table('price_age_category')->insert([
                [
                    'catp_name' => 'Kids',
                    'catp_price' => 10.00,
                    'age_min' => 0,
                    'age_max' => 12,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'catp_name' => 'Teen',
                    'catp_price' => 15.00,
                    'age_min' => 13,
                    'age_max' => 17,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'catp_name' => 'Adult',
                    'catp_price' => 25.00,
                    'age_min' => 18,
                    'age_max' => 64,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'catp_name' => 'Senior',
                    'catp_price' => 20.00,
                    'age_min' => 65,
                    'age_max' => 120,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
            $ageCategories = DB::table('price_age_category')->get();
        }

        // Create 5 raids with 3-5 races associated with each
        for ($i = 0; $i < 5; $i++) {
            $raid = Raid::create([
                'raid_name' => 'Raid Test ' . ($i + 1),
                'raid_description' => fake()->paragraphs(3, true),
                'adh_id' => Member::factory()->create()->adh_id,
                'clu_id' => Club::factory()->create()->club_id,
                'ins_id' => $registrationPeriod->ins_id,
                'raid_date_start' => fake()->dateTimeBetween('+1 month', '+6 months'),
                'raid_date_end' => fake()->dateTimeBetween('+1 month', '+6 months'),
                'raid_contact' => fake()->email(),
                'raid_site_url' => fake()->url(),
                'raid_street' => fake()->streetAddress(),
                'raid_city' => fake()->city(),
                'raid_postal_code' => fake()->postcode(),
                'raid_number' => fake()->numberBetween(1, 100),
            ]);

            // Create 3-5 races for this raid
            $raceCount = fake()->numberBetween(3, 5);
            for ($j = 0; $j < $raceCount; $j++) {
                $race = Race::create([
                    'race_name' => $raid->raid_name . ' - Race ' . ($j + 1),
                    'race_date_start' => fake()->dateTimeBetween('+1 month', '+6 months'),
                    'race_date_end' => fake()->dateTimeBetween('+1 month', '+6 months'),
                    'race_reduction' => fake()->numberBetween(0, 50),
                    'race_meal_price' => fake()->numberBetween(5, 25),
                    'race_duration_minutes' => fake()->numberBetween(60, 360),
                    'raid_id' => $raid->raid_id,
                    'cla_id' => $leaderboard->cla_id,
                    'adh_id' => Member::factory()->create()->adh_id,
                    'pac_id' => $paramRunner->pac_id,
                    'pae_id' => $paramTeam->pae_id,
                    'dif_id' => $paramDifficulty->dif_id,
                    'typ_id' => $paramType->typ_id,
                ]);

                // Associate age categories with this race
                foreach ($ageCategories as $category) {
                    DB::table('has_category')->insert([
                        'catpd_id' => $category->catp_id,
                        'race_id' => $race->race_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}
