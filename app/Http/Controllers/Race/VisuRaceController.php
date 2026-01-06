<?php

namespace App\Http\Controllers\Race;

use App\Http\Controllers\Controller;
use App\Models\Race;
use Inertia\Inertia;

/**
 * Controller for displaying race details
 */
class VisuRaceController extends Controller
{
    /**
     * Display the race visualization page.
     *
     * @return \Inertia\Response
     */
    public function show()
    {
        // Test mode: return example data
        return Inertia::render('Race/VisuRace', [
            'race' => [
                'race_id' => 1,
                'id' => 1,
                'title' => 'La Boussole de la Forêt',
                'race_name' => 'La Boussole de la Forêt',
                'description' => 'Une course d\'orientation passionnante à travers les sentiers de la forêt de Fontainebleau.',
                'location' => 'Fontainebleau, France',
                'latitude' => 48.4009,
                'longitude' => 2.6985,
                'raceDate' => now()->toIso8601String(),
                'race_date_start' => now()->toIso8601String(),
                'endDate' => now()->addHours(3)->toIso8601String(),
                'race_date_end' => now()->addHours(3)->toIso8601String(),
                'duration' => '2:30',
                'raceType' => 'medium',
                'difficulty' => 'medium',
                'status' => 'planned',
                'imageUrl' => 'https://images.unsplash.com/photo-1541625602330-2277a4c46182?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80',
                'maxParticipants' => 150,
                'minParticipants' => 20,
                'registeredCount' => 87,
                'maxPerTeam' => 4,
                'minTeams' => 5,
                'maxTeams' => 30,
                'organizer' => [
                    'id' => 1,
                    'adh_id' => 1,
                    'name' => 'Club Orientation Paris',
                    'adh_name' => 'Club Orientation Paris',
                    'email' => 'contact@co-paris.fr'
                ],
                'participants' => [],
                'categories' => [
                    ['name' => 'Junior', 'minAge' => 12, 'maxAge' => 17, 'price' => 15],
                    ['name' => 'Senior', 'minAge' => 18, 'maxAge' => 39, 'price' => 25],
                    ['name' => 'Vétéran', 'minAge' => 40, 'maxAge' => 99, 'price' => 20]
                ],
                'licenseDiscount' => '5€ de réduction pour les licenciés FFCO',
                'meals' => 'Repas inclus (sandwich + boisson)',
                'mealsPrice' => 8,
            ],
        ]);
    }
}
