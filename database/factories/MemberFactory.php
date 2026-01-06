<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Member>
 */
class MemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'adh_license' => $this->faker->bothify('##########'),
            'adh_end_validity' => $this->faker->dateTimeBetween('+1 year', '+2 years'),
            'adh_date_added' => now(),
        ];
    }
}
