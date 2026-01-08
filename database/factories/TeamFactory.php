<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for generating Team model instances.
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Team::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'equ_name' => fake()->words(2, true),
            'equ_image' => null,
            'users_id' => User::factory(),
        ];
    }

    /**
     * Create a team for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'users_id' => $user->id,
        ]);
    }

    /**
     * Set a custom team name.
     */
    public function withName(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'equ_name' => $name,
        ]);
    }

    /**
     * Set a team image.
     */
    public function withImage(string $imagePath): static
    {
        return $this->state(fn (array $attributes) => [
            'equ_image' => $imagePath,
        ]);
    }
}
