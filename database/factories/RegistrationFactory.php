<?php

namespace Database\Factories;

use App\Models\MedicalDoc;
use App\Models\Race;
use App\Models\Registration;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * Factory for creating Registration model instances for testing.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Registration>
 */
class RegistrationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Registration::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Create payment record
        $payId = DB::table('inscriptions_payment')->insertGetId([
            'pai_date' => now(),
            'pai_is_paid' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'equ_id' => Team::factory(),
            'race_id' => Race::factory(),
            'pay_id' => $payId,
            'doc_id' => MedicalDoc::factory(),
            'reg_points' => 0,
            'reg_validated' => false,
            'reg_dossard' => $this->faker->numberBetween(1, 999),
            'qr_code_path' => null,
            'is_present' => false,
        ];
    }

    /**
     * Indicate that the registration is validated.
     */
    public function validated(): static
    {
        return $this->state(fn (array $attributes) => [
            'reg_validated' => true,
        ]);
    }
}
