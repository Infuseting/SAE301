<?php

namespace Database\Factories;

use App\Models\LeaderboardUser;
use App\Models\User;
use App\Models\Race;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for creating LeaderboardUser model instances.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LeaderboardUser>
 */
class LeaderboardUserFactory extends Factory
{
    protected $model = LeaderboardUser::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'race_id' => Race::factory(),
            'temps' => $this->faker->randomFloat(2, 1800, 7200), // 30min to 2h
            'malus' => $this->faker->randomFloat(2, 0, 300), // 0 to 5min malus
        ];
    }

    /**
     * Set a specific user for the result.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
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
     * Create a fast result (good performance).
     */
    public function fast(): static
    {
        return $this->state(fn (array $attributes) => [
            'temps' => $this->faker->randomFloat(2, 1800, 2400),
            'malus' => 0,
        ]);
    }

    /**
     * Create a slow result (poor performance).
     */
    public function slow(): static
    {
        return $this->state(fn (array $attributes) => [
            'temps' => $this->faker->randomFloat(2, 5400, 7200),
            'malus' => $this->faker->randomFloat(2, 60, 300),
        ]);
    }

    /**
     * Create a result with no malus.
     */
    public function noMalus(): static
    {
        return $this->state(fn (array $attributes) => [
            'malus' => 0,
        ]);
    }
}
