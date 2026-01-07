<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\Race;
use App\Models\Raid;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Race>
 */
class RaceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Race::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('+1 month', '+6 months');
        $endDate = clone $startDate;
        $endDate->modify('+4 hours');

        // Get first records from related tables
        $leaderboard = DB::table('leaderboards')->first();
        $paramRunner = DB::table('param_runners')->first();
        $paramTeam = DB::table('param_teams')->first();
        $paramDifficulty = DB::table('param_difficulty')->first();
        $paramType = DB::table('param_type')->first();

        return [
            'race_name' => $this->faker->words(3, true),
            'race_date_start' => $startDate,
            'race_date_end' => $endDate,
            'race_reduction' => $this->faker->numberBetween(0, 50),
            'race_meal_price' => $this->faker->numberBetween(5, 25),
            'race_duration_minutes' => $this->faker->numberBetween(60, 360),
            
            // Relations - use first records from database
            'raid_id' => Raid::factory(),
            'cla_id' => $leaderboard?->cla_id ?? 1,
            'adh_id' => Member::factory(),
            'pac_id' => $paramRunner?->pac_id ?? 1,
            'pae_id' => $paramTeam?->pae_id ?? 1,
            'dif_id' => $paramDifficulty?->dif_id ?? 1,
            'typ_id' => $paramType?->typ_id ?? 1,
        ];
    }
}
