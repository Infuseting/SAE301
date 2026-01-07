<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for creating Team model instances.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    protected $model = Team::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'equ_name' => $this->faker->company() . ' Team',
            'equ_image' => null,
            'adh_id' => Member::factory(),
        ];
    }

    /**
     * Set a specific name for the team.
     */
    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'equ_name' => $name,
        ]);
    }

    /**
     * Associate with a specific member.
     */
    public function forMember(Member $member): static
    {
        return $this->state(fn (array $attributes) => [
            'adh_id' => $member->adh_id,
        ]);
    }

    /**
     * Set a specific image for the team.
     */
    public function withImage(string $image): static
    {
        return $this->state(fn (array $attributes) => [
            'equ_image' => $image,
        ]);
    }
}
