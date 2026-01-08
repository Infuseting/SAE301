<?php

namespace Database\Factories;

use App\Models\Club;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Club>
 */
class ClubFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Club::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'club_name' => $this->faker->company(),
            'club_street' => $this->faker->streetAddress(),
            'club_city' => $this->faker->city(),
            'club_postal_code' => $this->faker->postcode(),
            'ffso_id' => $this->faker->bothify('FFCO-####'),
            'description' => $this->faker->paragraph(),
            'club_image' => null,
            'is_approved' => false,
            'created_by' => User::factory(),  // Auto-create a user if not provided
        ];
    }

    /**
     * Indicate that the club is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_approved' => true,
        ]);
    }

    /**
     * Indicate that the club is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_approved' => false,
        ]);
    }
}
