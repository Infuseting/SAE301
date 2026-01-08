<?php

namespace Database\Factories;

use App\Models\ParamDifficulty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for creating ParamDifficulty model instances.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ParamDifficulty>
 */
class ParamDifficultyFactory extends Factory
{
    protected $model = ParamDifficulty::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dif_level' => $this->faker->randomElement(['Easy', 'Medium', 'Hard', 'Expert']),
        ];
    }

    /**
     * Create an easy difficulty.
     */
    public function easy(): static
    {
        return $this->state(fn (array $attributes) => [
            'dif_level' => 'Easy',
        ]);
    }

    /**
     * Create a medium difficulty.
     */
    public function medium(): static
    {
        return $this->state(fn (array $attributes) => [
            'dif_level' => 'Medium',
        ]);
    }

    /**
     * Create a hard difficulty.
     */
    public function hard(): static
    {
        return $this->state(fn (array $attributes) => [
            'dif_level' => 'Hard',
        ]);
    }

    /**
     * Create an expert difficulty.
     */
    public function expert(): static
    {
        return $this->state(fn (array $attributes) => [
            'dif_level' => 'Expert',
        ]);
    }
}
