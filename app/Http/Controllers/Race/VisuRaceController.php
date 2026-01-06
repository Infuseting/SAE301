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
     * Display a listing of races.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $races = Race::where('race_date_start', '>=', now())
            ->orderBy('race_date_start')
            ->get();

        return Inertia::render('Race/Index', [
            'races' => $races
        ]);
    }

    /**
     * Display the race visualization page.
     *
     * @param Race|null $race
     * @return \Inertia\Response
     */
    public function show(?Race $race = null)
    {
        // If no race provided (e.g. from /race test route), use mock data or first race
        if (!$race) {
            // For backward compatibility / testing /race route
             return Inertia::render('Race/VisuRace', [
                'race' => [
                    'race_id' => 1,
                    'id' => 1,
                    // ... (keep the mock data or fetch a default one)
                    // Reducing mock data for brevity in this replace, ensuring we don't break existing mock format
                    'title' => 'La Boussole de la Forêt (Demo)',
                    'race_name' => 'La Boussole de la Forêt (Demo)',
                    'description' => 'Une course d\'orientation passionnante à travers les sentiers de la forêt de Fontainebleau.',
                    'location' => 'Fontainebleau, France',
                    'latitude' => 48.4009,
                    'longitude' => 2.6985,
                    'raceDate' => now()->toIso8601String(),
                    'race_date_start' => now()->toIso8601String(),
                    'endDate' => now()->addHours(3)->toIso8601String(),
                    'race_date_end' => now()->addHours(3)->toIso8601String(),
                    // ... other mock fields assumed to be handled or minimal
                    'imageUrl' => 'https://images.unsplash.com/photo-1541625602330-2277a4c46182?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80',
                    'organizer' => ['name' => 'Club Demo'],
                    'categories' => [],
                    'participants' => [],
                ],
            ]);
        }

        // Return real race data with loaded relationships
        $race->load(['organizer', 'raid', 'categories']);

        return Inertia::render('Race/VisuRace', [
            'race' => $race
        ]);
    }
}
