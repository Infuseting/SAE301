<?php

namespace Database\Factories;

use App\Models\RegistrationPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for creating RegistrationPeriod model instances.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RegistrationPeriod>
 */
class RegistrationPeriodFactory extends Factory
{
    protected $model = RegistrationPeriod::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-2 months', '+1 month');
        $endDate = (clone $startDate)->modify('+3 months');

        return [
            'ins_start_date' => $startDate,
            'ins_end_date' => $endDate,
        ];
    }

    /**
     * Create a past registration period.
     */
    public function past(): static
    {
        $startDate = $this->faker->dateTimeBetween('-1 year', '-6 months');
        $endDate = (clone $startDate)->modify('+3 months');

        return $this->state(fn (array $attributes) => [
            'ins_start_date' => $startDate,
            'ins_end_date' => $endDate,
        ]);
    }

    /**
     * Create a future registration period.
     */
    public function future(): static
    {
        $startDate = $this->faker->dateTimeBetween('+1 month', '+6 months');
        $endDate = (clone $startDate)->modify('+3 months');

        return $this->state(fn (array $attributes) => [
            'ins_start_date' => $startDate,
            'ins_end_date' => $endDate,
        ]);
    }

    /**
     * Create a currently active registration period.
     */
    public function active(): static
    {
        $startDate = now()->subMonth();
        $endDate = now()->addMonths(2);

        return $this->state(fn (array $attributes) => [
            'ins_start_date' => $startDate,
            'ins_end_date' => $endDate,
        ]);
    }
}
