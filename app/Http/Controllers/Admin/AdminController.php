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
    public function index(): \Inertia\Response|\Illuminate\Http\RedirectResponse

    {
        try {
            $user = Auth::user();

            $stats = [
                'users' => User::count(),
                'logs' => Activity::count(),
                'pendingClubs' => \App\Models\Club::where('is_approved', false)->count(),
            ];

            // Admins can see all raids and races, others only their own
            if ($user->hasRole('admin')) {
                $myresponsibleRaids = Raid::all();
                $myresponsibleRaces = Race::all();
            } else {
                $myresponsibleRaids = Raid::where('adh_id', $user->adh_id)->get();
                $myresponsibleRaces = Race::where('adh_id', $user->adh_id)->get();
            }

            \Log::debug('myresponsibleRaids query', [
                'user_id' => $user->id,
                'is_admin' => $user->hasRole('admin'),
                'count' => $myresponsibleRaids->count(),
            ]);

            \Log::debug('myresponsibleRaces query', [
                'user_id' => $user->id,
                'is_admin' => $user->hasRole('admin'),
                'count' => $myresponsibleRaces->count(),
            ]);

            $user->load('roles.permissions');

            return inertia('Admin/Dashboard', [
                'stats' => $stats,
                'myresponsibleRaids' => $myresponsibleRaids,
                'myresponsibleRaces' => $myresponsibleRaces,
            ]);
        } catch (\Exception $e) {
            \Log::error('Admin dashboard error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('dashboard')->withErrors(['error' => 'Une erreur est survenue lors de l\'accès au tableau de bord admin.']);
        }
    }
    

    public function racemanagement(): \Inertia\Response|\Illuminate\Http\RedirectResponse
    {
        try {
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
        } catch (\Exception $e) {
            \Log::error('Admin race management error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('admin.dashboard')->withErrors(['error' => 'Une erreur est survenue lors de l\'accès à la gestion des courses.']);
        }
    }

    public function raidmanagement(): \Inertia\Response|\Illuminate\Http\RedirectResponse
    {
        try {
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
        } catch (\Exception $e) {
            \Log::error('Admin raid management error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('admin.dashboard')->withErrors(['error' => 'Une erreur est survenue lors de l\'accès à la gestion des raids.']);
        }
    }

    /**
     * Display the club management page for responsable-club users.
     * Admins can see all clubs, others only clubs where they are leader or manager.
     *
     * @return \Inertia\Response|\Illuminate\Http\RedirectResponse
     */
    public function clubmanagement(): \Inertia\Response|\Illuminate\Http\RedirectResponse
    {
        try {
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
        } catch (\Exception $e) {
            \Log::error('Admin club management error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('admin.dashboard')->withErrors(['error' => 'Une erreur est survenue lors de l\'accès à la gestion des clubs.']);
        }
    }
}