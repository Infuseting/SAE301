<?php

namespace Database\Factories;

use App\Models\Club;
use App\Models\Member;
use App\Models\Raid;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Raid>
 */
class RaidFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Raid::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('+1 month', '+6 months');
        $endDate = clone $startDate;
        $endDate->modify('+3 days');

        // Get first registration period or use 1 as default
        $registrationPeriod = DB::table('registration_period')->first();
        $insId = $registrationPeriod?->ins_id ?? 1;

        return [
            'raid_name' => $this->faker->catchPhrase(),
            'raid_description' => $this->faker->paragraphs(3, true),
            'adh_id' => Member::factory(),
            'clu_id' => Club::factory(),
            'ins_id' => $insId,
            'raid_date_start' => $startDate,
            'raid_date_end' => $endDate,
            'raid_contact' => $this->faker->email(),
            'raid_site_url' => $this->faker->url(),
            'raid_street' => $this->faker->streetAddress(),
            'raid_city' => $this->faker->city(),
            'raid_postal_code' => $this->faker->postcode(),
            'raid_number' => $this->faker->numberBetween(1, 100),
        ];
    }
}
