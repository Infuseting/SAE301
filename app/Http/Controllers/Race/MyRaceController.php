<?php

namespace App\Http\Controllers\Race;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\Race;
class MyRaceController extends Controller
{
    /**
     * Display a listing of the user's
     * races
     * @return \Inertia\Response
     */ 
    public function index(): Response
    {
        $user = Auth::user();
        $races = Race::where('adh_id', $user->adh_id)
            ->orderBy('race_date_start', 'desc')
            ->get()
            ->map(function ($race) {   
                return [
                    'id' => $race->race_id,
                    'name' => $race->race_name,
                    'description' => $race->race_description,
                    'date_start' => $race->race_date_start ? $race->race_date_start->toDateString() : null,
                    'date_end' => $race->race_date_end ? $race->race_date_end->toDateString() : null,
                    'image' => $race->image_url,
                    'is_open' => $race->isOpen(),
                ];
            });
        return Inertia::render('Race/MyRaceIndex', [
            'races' => $races,
        ]);

    }
}