<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Raid;
use App\Models\Race;
use App\Models\Club;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

class AdminController extends Controller
{
    // ... méthode index() inchangée ...
    public function index()

    {


        $user = Auth::user();

        $stats = [

            'users' => User::count(),

            'logs' => Activity::count(),

            'pendingClubs' => \App\Models\Club::where('is_approved', false)->count(),

        ];


        // Debug: Voir la requête SQL générée

        $myresponsibleRaids = Raid::where('adh_id', $user->adh_id)->get();

        \Log::debug('myresponsibleRaids query', [

            'user_id' => $user->id,

            'count' => $myresponsibleRaids->count(),

            'data' => $myresponsibleRaids->toArray(),

        ]);


        $myresponsibleRaces = Race::where('adh_id', $user->adh_id)->get();

        \Log::debug('myresponsibleRaces query', [

            'user_id' => $user->id,

            'count' => $myresponsibleRaces->count(),

            'data' => $myresponsibleRaces->toArray(),

        ]);


        $user = auth()->user();

        $user->load('roles.permissions');



        return inertia('Admin/Dashboard', [

            'stats' => $stats,

            'myresponsibleRaids' => $myresponsibleRaids,

            'myresponsibleRaces' => $myresponsibleRaces,

        ]);

    }
    

    public function racemanagement()
    {
        $user = Auth::user();

        // Admins can see all races, others only their own
        if ($user->hasRole('admin')) {
            $races = Race::with('raid')->get();
        } else {
            $races = Race::where('adh_id', $user->adh_id)
                ->with('raid') 
                ->get();
        }

        return inertia('Admin/RaceManagement', [
            'races' => $races,
        ]);
    }

    public function raidmanagement()
    {
        $user = Auth::user();

        // Admins can see all raids, others only their own
        if ($user->hasRole('admin')) {
            $raids = Raid::all();
        } else {
            $raids = Raid::where('adh_id', $user->adh_id)->get();
        }

        return inertia('Admin/RaidManagement', [
            'raids' => $raids,
        ]);
    }

    /**
     * Display the club management page for responsable-club users.
     * Admins can see all clubs, others only clubs where they are leader or manager.
     *
     * @return \Inertia\Response
     */
    public function clubmanagement()
    {
        $user = Auth::user();

        // Admins can see all clubs, others only their managed clubs
        if ($user->hasRole('admin')) {
            $clubs = Club::with('members:id,first_name,last_name,email')->get();
        } else {
            // Get clubs where user is leader or manager
            $clubs = Club::whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->whereIn('role', ['leader', 'manager']);
            })->with('members:id,first_name,last_name,email')->get();
        }

        return inertia('Admin/ClubManagement', [
            'clubs' => $clubs,
        ]);
    }
}