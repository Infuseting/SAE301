<?php

namespace Database\Factories;

use App\Models\ParamRunner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for creating ParamRunner model instances.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ParamRunner>
 */
class ParamRunnerFactory extends Factory
{
    protected $model = ParamRunner::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $min = $this->faker->numberBetween(1, 3);
        $max = $this->faker->numberBetween($min + 1, 10);

        return [
            'pac_nb_min' => $min,
            'pac_nb_max' => $max,
        ];
    }

    /**
     * Create a solo runner configuration.
     */
    public function solo(): static
    {
        return $this->state(fn (array $attributes) => [
            'pac_nb_min' => 1,
            'pac_nb_max' => 1,
        ]);
    }

    /**
     * Create a relay configuration.
     */
    public function relay(): static
    {
        return $this->state(fn (array $attributes) => [
            'pac_nb_min' => 2,
            'pac_nb_max' => 4,
        ]);
    }
}
