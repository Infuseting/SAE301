<?php

namespace Database\Factories;

use App\Models\Club;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for creating Club model instances.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Club>
 */
class ClubFactory extends Factory
{
    protected $model = Club::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'club_name' => $this->faker->company() . ' Club',
            'club_street' => $this->faker->streetAddress(),
            'club_city' => $this->faker->city(),
            'club_postal_code' => $this->faker->postcode(),
            'club_number' => $this->faker->buildingNumber(),
            'adh_id' => Member::factory(),
            'adh_id_dirigeant' => Member::factory(),
        ];
    }

    /**
     * Set specific responsable member.
     */
    public function withResponsable(Member $member): static
    {
        return $this->state(fn (array $attributes) => [
            'adh_id' => $member->adh_id,
        ]);
    }

    /**
     * Set specific dirigeant member.
     */
    public function withDirigeant(Member $member): static
    {
        return $this->state(fn (array $attributes) => [
            'adh_id_dirigeant' => $member->adh_id,
        ]);
    }
}
