<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MedicalDoc>
 */
class MedicalDocFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'doc_num_pps' => $this->faker->bothify('PPS-#######'),
            'doc_end_validity' => $this->faker->dateTimeBetween('+1 year', '+2 years'),
            'doc_date_added' => now(),
        ];
    }
}
