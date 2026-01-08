<?php

namespace Database\Factories;

use App\Models\Raid;
use App\Models\Club;
use App\Models\Member;
use App\Models\RegistrationPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Raid>
 */
class RaidFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Raid::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('+1 month', '+3 months');
        $endDate = $this->faker->dateTimeBetween($startDate->format('Y-m-d') . ' +1 day', $startDate->format('Y-m-d') . ' +5 days');
        
        $insStartDate = $this->faker->dateTimeBetween('now', $startDate->format('Y-m-d') . ' -1 day');
        $insEndDate = $this->faker->dateTimeBetween($insStartDate, $startDate->format('Y-m-d') . ' -1 day');

        // Create registration period
        $registrationPeriod = RegistrationPeriod::create([
            'ins_start_date' => $insStartDate,
            'ins_end_date' => $insEndDate,
        ]);

        return [
            'raid_name' => $this->faker->words(3, true) . ' Raid',
            'raid_description' => $this->faker->paragraph(),
            'adh_id' => Member::factory(),
            'clu_id' => Club::factory(),
            'ins_id' => $registrationPeriod->ins_id,
            'raid_date_start' => $startDate,
            'raid_date_end' => $endDate,
            'raid_contact' => $this->faker->safeEmail(),
            'raid_site_url' => $this->faker->optional()->url(),
            'raid_image' => null,
            'raid_street' => $this->faker->streetAddress(),
            'raid_city' => $this->faker->city(),
            'raid_postal_code' => $this->faker->postcode(),
            'raid_number' => $this->faker->numberBetween(1, 999),
        ];
    }
}

