<?php

namespace App\Http\Controllers\Race;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;


class NewRaceController extends Controller
{
    
    public function show()
    {
        return Inertia::render('Race/NewRace', [    
            'user' => Auth::user(), // Passer des données au composant React
        ]);
    }

}
