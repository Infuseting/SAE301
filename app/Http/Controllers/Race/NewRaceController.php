<?php

namespace App\Http\Controllers\Race;

use App\Http\Controllers\Controller;
use App\Http\Requests\Race\StoreRaceRequest;
use App\Models\Race;
use App\Models\User;
use App\Models\ParamDifficulty;
use App\Models\ParamType;
use App\Models\ParamRunner;
use App\Models\ParamTeam;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

/**
 * Controller for managing race creation.
 */
class NewRaceController extends Controller
{
    /**
     * Show the form for creating a new race.
     *
     * @return \Inertia\Response
     */
    public function show()
    {
        // Get all users for responsable selection
        $users = User::select('id', 'last_name', 'first_name', 'email')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->first_name . ' ' . $user->last_name,
                'email' => $user->email,
            ])
            ->toArray();

        // Get all difficulties from database
        $difficulties = ParamDifficulty::select('dif_id', 'dif_level')
            ->orderBy('dif_id')
            ->get()
            ->map(fn($difficulty) => [
                'id' => $difficulty->dif_id,
                'level' => $difficulty->dif_level,
            ])
            ->toArray();

        // Get all types from database
        $types = ParamType::select('typ_id', 'typ_name')
            ->orderBy('typ_id')
            ->get()
            ->map(fn($type) => [
                'id' => $type->typ_id,
                'name' => $type->typ_name,
            ])
            ->toArray();

        return Inertia::render('Race/NewRace', [    
            'users' => $users,
            'difficulties' => $difficulties,
            'types' => $types,
            'auth' => [
                'user' => Auth::user(),
            ],
        ]);
    }

    /**
     * Store a newly created race in the database.
     *
     * @param StoreRaceRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRaceRequest $request)
    {
        // Combine date and time fields
        $startDateTime = $request->input('startDate') . ' ' . $request->input('startTime');
        $endDateTime = $request->input('endDate') . ' ' . $request->input('endTime');

        // Create ParamRunner entry for this race
        $paramRunner = ParamRunner::create([
            'pac_nb_min' => $request->input('minParticipants'),
            'pac_nb_max' => $request->input('maxParticipants'),
        ]);

        // Create ParamTeam entry for this race
        $paramTeam = ParamTeam::create([
            'pae_nb_min' => $request->input('minTeams'),
            'pae_nb_max' => $request->input('maxTeams'),
            'pae_team_count_max' => $request->input('maxPerTeam'),
        ]);

        // Prepare race data
        $raceData = [
            'race_name' => $request->input('title'),
            'race_date_start' => $startDateTime,
            'race_date_end' => $endDateTime,
            'race_duration_minutes' => $this->convertDurationToMinutes($request->input('duration')),
            'race_reduction' => $request->input('licenseDiscount'),
            'race_meal_price' => $request->input('price'),
            'adh_id' => $request->input('responsableId'),
            'dif_id' => $request->input('difficulty'),
            'typ_id' => $request->input('type'),
            'pac_id' => $paramRunner->pac_id,
            'pae_id' => $paramTeam->pae_id,
            'image_url' => null,
            'cla_id' => null,
            'raid_id' => null,
        ];

        // Create the race
        $race = Race::create($raceData);

        return redirect()->route('race.view', $race->race_id)
            ->with('success', 'La course a été créée avec succès!');
    }

    /**
     * Convert duration string (H:mm) to minutes.
     *
     * @param string|null $duration
     * @return int|null
     */
    private function convertDurationToMinutes(?string $duration): ?int
    {
        if (!$duration) {
            return null;
        }

        try {
            [$hours, $minutes] = explode(':', $duration);
            return (int)$hours * 60 + (int)$minutes;
        } catch (\Exception $e) {
            return null;
        }
    }
}
