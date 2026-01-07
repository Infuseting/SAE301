<?php

namespace App\Http\Controllers\Race;

use App\Http\Controllers\Controller;
use App\Http\Requests\Race\StoreRaceRequest;
use App\Models\Race;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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

        return Inertia::render('Race/NewRace', [    
            'users' => $users,
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

        // Prepare race data
        $raceData = [
            'race_name' => $request->input('title'),
            'race_date_start' => $startDateTime,
            'race_date_end' => $endDateTime,
            'race_duration_minutes' => $this->convertDurationToMinutes($request->input('duration')),
            'race_reduction' => $request->input('licenseDiscount'),
            'race_meal_price' => $request->input('price'),
            'adh_id' => $request->input('responsableId') ?? Auth::user()->adh_id ?? 1, // Use selected responsable or fallback
        ];

        // Handle image upload if present
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('races', 'public');
            $raceData['image_url'] = $imagePath;
        }

        // Create the race
        $race = Race::create($raceData);

        // Log the creation activity (temporarily commented out)
        // activity()
        //     ->performedOn($race)
        //     ->causedBy(Auth::user())
        //     ->log('Course créée: ' . $race->race_name);

        // TODO: Save categories relationship
        // TODO: Save team parameters if provided
        // TODO: Create race parameters

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
