<?php

namespace Database\Factories;

use App\Models\LeaderboardTeam;
use App\Models\Team;
use App\Models\Race;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for creating LeaderboardTeam model instances.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LeaderboardTeam>
 */
class LeaderboardTeamFactory extends Factory
{
    protected $model = LeaderboardTeam::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $avgTemps = $this->faker->randomFloat(2, 1800, 7200);
        $avgMalus = $this->faker->randomFloat(2, 0, 300);

        return [
            'equ_id' => Team::factory(),
            'race_id' => Race::factory(),
            'average_temps' => $avgTemps,
            'average_malus' => $avgMalus,
            'average_temps_final' => $avgTemps + $avgMalus,
            'member_count' => $this->faker->numberBetween(2, 5),
        ];
    }

    /**
     * Set a specific team for the result.
     */
    public function forTeam(Team $team): static
    {
        return $this->state(fn (array $attributes) => [
            'equ_id' => $team->equ_id,
        ]);
    }

    /**
     * Set a specific race for the result.
     */
    public function forRace(Race $race): static
    {
        return $this->state(fn (array $attributes) => [
            'race_id' => $race->race_id,
        ]);
    }

    /**
     * Create a fast team result.
     */
    public function fast(): static
    {
        $avgTemps = $this->faker->randomFloat(2, 1800, 2400);
        return $this->state(fn (array $attributes) => [
            'average_temps' => $avgTemps,
            'average_malus' => 0,
            'average_temps_final' => $avgTemps,
        ]);
    }

    /**
     * Create a slow team result.
     */
    public function slow(): static
    {
        $avgTemps = $this->faker->randomFloat(2, 5400, 7200);
        $avgMalus = $this->faker->randomFloat(2, 60, 300);
        return $this->state(fn (array $attributes) => [
            'average_temps' => $avgTemps,
            'average_malus' => $avgMalus,
            'average_temps_final' => $avgTemps + $avgMalus,
        ]);
    }

    /**
     * Set specific member count.
     */
    public function withMembers(int $count): static
    {
        return $this->state(fn (array $attributes) => [
            'member_count' => $count,
        ]);
    }
}
