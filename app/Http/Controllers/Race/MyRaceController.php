<?php

namespace App\Http\Controllers\Race;

use App\Http\Controllers\Controller;
use App\Models\Time;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\Race;

class MyRaceController extends Controller
{
    /**
     * Display a listing of the user's races where they have a time record.
     *
     * @return \Inertia\Response
     */
    public function index(): Response
    {
        $user = Auth::user();
        
        // Get races where the user has a time record
        $races = Race::whereHas('times', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->orderBy('race_date_start', 'desc')
            ->get()
            ->map(function ($race) use ($user) {
                // Get the time record for this user and race
                $timeRecord = Time::where('user_id', $user->id)
                    ->where('race_id', $race->race_id)
                    ->first();

                return [
                    'id' => $race->race_id,
                    'name' => $race->race_name,
                    'description' => $race->race_description,
                    'date_start' => $race->race_date_start ? $race->race_date_start->toDateString() : null,
                    'date_end' => $race->race_date_end ? $race->race_date_end->toDateString() : null,
                    'image' => $race->image_url ? '/storage/' . $race->image_url : null,
                    'is_open' => $race->isOpen(),
                    'time' => $timeRecord ? [
                        'hours' => $timeRecord->time_hours,
                        'minutes' => $timeRecord->time_minutes,
                        'seconds' => $timeRecord->time_seconds,
                        'total_seconds' => $timeRecord->time_total_seconds,
                        'rank' => $timeRecord->time_rank,
                        'rank_start' => $timeRecord->time_rank_start,
                    ] : null,
                ];
            });

        return Inertia::render('Race/MyRaceIndex', [
            'races' => $races,
        ]);
    }
}