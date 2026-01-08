<?php

namespace Database\Factories;

use App\Models\Race;
use App\Models\Raid;
use App\Models\Member;
use App\Models\ParamRunner;
use App\Models\ParamTeam;
use App\Models\ParamType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for creating Race model instances.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Race>
 */
class RaceFactory extends Factory
{
    protected $model = Race::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 year', '+1 year');
        $endDate = (clone $startDate)->modify('+1 day');

        return [
            'race_name' => $this->faker->words(3, true) . ' Race',
            'race_date_start' => $startDate,
            'race_date_end' => $endDate,
            'race_reduction' => $this->faker->randomFloat(2, 0, 20),
            'race_meal_price' => $this->faker->randomFloat(2, 5, 25),
            'race_duration_minutes' => $this->faker->numberBetween(60, 480),
            'race_difficulty' => $this->faker->randomElement(['Easy', 'Medium', 'Hard', 'Expert']),
            'raid_id' => Raid::factory(),
            'adh_id' => Member::factory(),
            'pac_id' => ParamRunner::factory(),
            'pae_id' => ParamTeam::factory(),
            'typ_id' => ParamType::factory(),
        ];
    }

    /**
     * Create a past race.
     */
    public function past(): static
    {
        $startDate = $this->faker->dateTimeBetween('-1 year', '-1 week');
        $endDate = (clone $startDate)->modify('+1 day');

        return $this->state(fn (array $attributes) => [
            'race_date_start' => $startDate,
            'race_date_end' => $endDate,
        ]);
    }

    /**
     * Create a future race.
     */
    public function future(): static
    {
        $startDate = $this->faker->dateTimeBetween('+1 week', '+1 year');
        $endDate = (clone $startDate)->modify('+1 day');

        return $this->state(fn (array $attributes) => [
            'race_date_start' => $startDate,
            'race_date_end' => $endDate,
        ]);
    }

    /**
     * Set a specific name for the race.
     */
    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'race_name' => $name,
        ]);
    }

    /**
     * Associate with a specific raid.
     */
    public function forRaid(Raid $raid): static
    {
        return $this->state(fn (array $attributes) => [
            'raid_id' => $raid->raid_id,
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
}
