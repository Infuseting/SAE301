<?php

namespace Database\Factories;

use App\Models\ParamType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for creating ParamType model instances.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ParamType>
 */
class ParamTypeFactory extends Factory
{
    protected $model = ParamType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'typ_name' => $this->faker->randomElement([
                'Marathon',
                'Trail',
                'Triathlon',
                'Sprint',
                'Relay',
                'Ultra',
                'Cross Country',
                'Road Race',
            ]),
        ];
    }

    /**
     * Create a marathon type.
     */
    public function marathon(): static
    {
        return $this->state(fn (array $attributes) => [
            'typ_name' => 'Marathon',
        ]);
    }

    /**
     * Create a trail type.
     */
    public function trail(): static
    {
        return $this->state(fn (array $attributes) => [
            'typ_name' => 'Trail',
        ]);
    }

    /**
     * Create a triathlon type.
     */
    public function triathlon(): static
    {
        return $this->state(fn (array $attributes) => [
            'typ_name' => 'Triathlon',
        ]);
    }
}
