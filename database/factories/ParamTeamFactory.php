<?php

namespace Database\Factories;

use App\Models\ParamTeam;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for creating ParamTeam model instances.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ParamTeam>
 */
class ParamTeamFactory extends Factory
{
    protected $model = ParamTeam::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $min = $this->faker->numberBetween(2, 4);
        $max = $this->faker->numberBetween($min + 1, 10);

        return [
            'pae_nb_min' => $min,
            'pae_nb_max' => $max,
            'pae_team_count_max' => $this->faker->numberBetween(10, 100),
        ];
    }

    /**
     * Create a small team configuration.
     */
    public function small(): static
    {
        return $this->state(fn (array $attributes) => [
            'pae_nb_min' => 2,
            'pae_nb_max' => 4,
            'pae_team_count_max' => 20,
        ]);
    }

    /**
     * Create a large team configuration.
     */
    public function large(): static
    {
        return $this->state(fn (array $attributes) => [
            'pae_nb_min' => 4,
            'pae_nb_max' => 8,
            'pae_team_count_max' => 50,
        ]);
    }
}
