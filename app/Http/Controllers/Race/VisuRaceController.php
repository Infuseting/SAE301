<?php

namespace App\Http\Controllers\Race;

use App\Http\Controllers\Controller;
use App\Models\RaceModel;
use Inertia\Inertia;

/**
 * Controller for displaying race details
 */
class VisuRaceController extends Controller
{
    /**
     * Display the specified race.
     *
     * @param RaceModel $race
     * @return \Inertia\Response
     */
    public function show(RaceModel $race)
    {
        return Inertia::render('Race/VisuRace', [
            'race' => $race->load('organizer', 'participants'),
        ]);
    }
}
