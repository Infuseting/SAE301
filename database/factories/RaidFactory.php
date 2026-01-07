<?php

namespace Database\Factories;

use App\Models\Raid;
use App\Models\Club;
use App\Models\Member;
use App\Models\RegistrationPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for creating Raid model instances.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Raid>
 */
class RaidFactory extends Factory
{
    protected $model = Raid::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 year', '+1 year');
        $endDate = (clone $startDate)->modify('+2 days');

        return [
            'raid_name' => $this->faker->words(2, true) . ' Raid',
            'raid_description' => $this->faker->paragraph(),
            'adh_id' => Member::factory(),
            'clu_id' => Club::factory(),
            'ins_id' => RegistrationPeriod::factory(),
            'raid_date_start' => $startDate,
            'raid_date_end' => $endDate,
            'raid_contact' => $this->faker->email(),
            'raid_site_url' => $this->faker->url(),
            'raid_image' => null,
            'raid_street' => $this->faker->streetAddress(),
            'raid_city' => $this->faker->city(),
            'raid_postal_code' => $this->faker->postcode(),
            'raid_number' => $this->faker->buildingNumber(),
        ];
    }

    /**
     * Create a past raid.
     */
    public function past(): static
    {
        $startDate = $this->faker->dateTimeBetween('-1 year', '-1 month');
        $endDate = (clone $startDate)->modify('+2 days');

        return $this->state(fn (array $attributes) => [
            'raid_date_start' => $startDate,
            'raid_date_end' => $endDate,
        ]);
    }

    /**
     * Create a future raid.
     */
    public function future(): static
    {
        $startDate = $this->faker->dateTimeBetween('+1 month', '+1 year');
        $endDate = (clone $startDate)->modify('+2 days');

        return $this->state(fn (array $attributes) => [
            'raid_date_start' => $startDate,
            'raid_date_end' => $endDate,
        ]);
    }

    /**
     * Set a specific name for the raid.
     */
    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'raid_name' => $name,
        ]);
    }

    /**
     * Associate with a specific club.
     */
    public function forClub(Club $club): static
    {
        return $this->state(fn (array $attributes) => [
            'clu_id' => $club->club_id,
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
