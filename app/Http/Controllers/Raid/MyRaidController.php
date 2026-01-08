<?php

namespace App\Http\Controllers\Raid;

use App\Http\Controllers\Controller;
use App\Models\Raid; 
use Illuminate\Http\Request;
use Inertia\Inertia;

class MyRaidController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // On cherche les raids qui ont des courses (races) 
        // possédant une entrée dans la table 'time' pour cet utilisateur
        $raids = Raid::whereHas('races.times', function ($query) use ($user) {
            $query->where('user_id', $user->id); 
        })->get()->map(function ($raid) {
            return [
                'id' => $raid->raid_id,
                'name' => $raid->raid_name,
                'description' => $raid->raid_description,
                'date_start' => $raid->raid_date_start?->toDateString(),
                'date_end' => $raid->raid_date_end?->toDateString(),
                'image' => $raid->raid_image ? '/storage/' . $raid->raid_image : null,
                'is_open' => $raid->isOpen(),
            ];
        });

        return Inertia::render('Raid/MyRaidIndex', [
            'raids' => $raids
        ]);
    }
}