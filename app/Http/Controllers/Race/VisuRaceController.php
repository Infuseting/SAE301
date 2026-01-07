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
     * Display the specified race.
     *
     * @param int $id
     * @return \Inertia\Response
     */
    public function show(int $id)
    {
        // Find the race by ID
        $race = Race::find($id);

        // If race not found, return error page
        if (!$race) {
            return Inertia::render('Race/VisuRace', [
                'race' => null,
                'error' => 'Course non trouvée',
                'errorMessage' => "Aucune course ne correspond à l'identifiant #" . $id . ". Elle a peut-être été supprimée ou l'ID est incorrect.",
            ]);
        }

        // Load only existing relations
        $race->load(['organizer', 'raid']);

        // Transform data for frontend
        $raceData = [
            'id' => $race->race_id,
            'title' => $race->race_name,
            'description' => $race->raid?->raid_description ?? 'Aucune description disponible.',
            'location' => $race->raid?->raid_location ?? 'Lieu à définir',
            'latitude' => $race->raid?->raid_latitude ?? 48.8566,
            'longitude' => $race->raid?->raid_longitude ?? 2.3522,
            'raceDate' => $race->race_date_start?->toIso8601String(),
            'endDate' => $race->race_date_end?->toIso8601String(),
            'duration' => $race->race_duration_minutes ? floor($race->race_duration_minutes / 60) . ':' . str_pad((int)($race->race_duration_minutes % 60), 2, '0', STR_PAD_LEFT) : '0:00',
            'raceType' => 'medium', // Default value
            'difficulty' => 'medium', // Default value
            'status' => $this->getRaceStatus($race),
            'imageUrl' => $race->image_url ? asset('storage/' . $race->image_url) : null,
            'maxParticipants' => 100,
            'minParticipants' => 1,
            'registeredCount' => 0,
            'maxPerTeam' => 4,
            'minTeams' => 1,
            'maxTeams' => 50,
            'organizer' => [
                'id' => $race->organizer?->adh_id,
                'name' => trim(($race->organizer?->adh_firstname ?? '') . ' ' . ($race->organizer?->adh_lastname ?? '')) ?: 'Organisateur',
                'email' => $race->organizer?->user?->email ?? ''
            ],
            'categories' => [],
            'licenseDiscount' => $race->race_reduction ? $race->race_reduction . '€ de réduction pour les licenciés' : null,
            'meals' => $race->race_meal_price ? 'Repas disponible' : null,
            'mealsPrice' => $race->race_meal_price,
            'createdAt' => $race->created_at?->toIso8601String(),
            'updatedAt' => $race->updated_at?->toIso8601String(),
        ];

        return Inertia::render('Race/VisuRace', [
            'race' => $raceData,
        ]);
    }

    /**
     * Determine race status based on dates.
     *
     * @param Race $race
     * @return string
     */
    private function getRaceStatus(Race $race): string
    {
        $now = now();

        if ($race->race_date_end && $now->isAfter($race->race_date_end)) {
            return 'completed';
        }

        if ($race->race_date_start && $now->isAfter($race->race_date_start)) {
            return 'ongoing';
        }

        return 'planned';
    }
}
