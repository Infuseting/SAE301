<?php

namespace App\Http\Controllers\Race;

use App\Http\Controllers\Controller;
use App\Models\RaceRegistration;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Controller for displaying user's race registrations.
 */
class MyRaceController extends Controller
{
    /**
     * Display a listing of the user's race registrations.
     * 
     * @return Response
     */
    public function index(): Response
    {
        $user = Auth::user();

        // Get user's registrations with race data
        $registrations = RaceRegistration::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'confirmed']) // Exclude cancelled
            ->with(['race.raid', 'race.type', 'team'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($registration) {
                $race = $registration->race;

                return [
                    'registration_id' => $registration->reg_id,
                    'status' => $registration->status,
                    'is_team_leader' => $registration->is_team_leader,
                    'is_temporary_team' => $registration->is_temporary_team,
                    'registered_at' => $registration->created_at?->toIso8601String(),
                    'team' => $registration->team ? [
                        'id' => $registration->team->equ_id,
                        'name' => $registration->team->equ_name,
                    ] : ($registration->is_temporary_team ? [
                            'id' => null,
                            'name' => 'Équipe temporaire',
                        ] : null),
                    'race' => $race ? [
                        'id' => $race->race_id,
                        'name' => $race->race_name,
                        'description' => $race->race_description,
                        'date_start' => $race->race_date_start?->toDateString(),
                        'date_end' => $race->race_date_end?->toDateString(),
                        'location' => $race->raid?->raid_location ?? 'Lieu à définir',
                        'image' => $race->image_url,
                        'is_open' => $race->isOpen(),
                        'status' => $this->getRaceStatus($race),
                        'type' => $race->type?->typ_name ?? 'Classique',
                    ] : null,
                ];
            })
            ->filter(fn($r) => $r['race'] !== null); // Remove registrations for deleted races

        return Inertia::render('Race/MyRaceIndex', [
            'registrations' => $registrations->values(),
        ]);
    }

    /**
     * Determine race status based on dates.
     */
    private function getRaceStatus($race): string
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