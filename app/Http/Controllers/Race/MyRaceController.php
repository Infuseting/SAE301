<?php

namespace App\Http\Controllers\Race;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\Race;
use Carbon\Carbon;

class MyRaceController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $userId = $user->id;
        $period = $request->input('period', 'all'); // Default: all

        // Calculate date filter based on period
        $dateFilter = null;
        switch ($period) {
            case '1month':
                $dateFilter = Carbon::now()->subMonth();
                break;
            case '6months':
                $dateFilter = Carbon::now()->subMonths(6);
                break;
            case '1year':
                $dateFilter = Carbon::now()->subYear();
                break;
            case 'all':
            default:
                $dateFilter = null;
                break;
        }

        // 1. Récupération des courses "Inscrites" (sans forcément de résultat au leaderboard)
        $registers = Race::whereIn('race_id', function ($query) use ($userId) {
            $query->select('race_id')
                ->from('registration')
                ->whereIn('equ_id', function ($subQuery) use ($userId) {
                    $subQuery->select('equ_id')
                        ->from('has_participate')
                        ->where('id_users', $userId);
                });
        })
        ->when($dateFilter, function ($query) use ($dateFilter) {
            $query->where('race_date_start', '>=', $dateFilter);
        })
        ->get()
        ->map(function ($race) use ($userId) {
            // On récupère uniquement la team et l'inscription de l'user pour cette course précise
            $userTeamAndRegistration = DB::table('teams')
                ->join('has_participate', 'teams.equ_id', '=', 'has_participate.equ_id')
                ->join('registration', 'teams.equ_id', '=', 'registration.equ_id')
                ->where('registration.race_id', $race->race_id)
                ->where('has_participate.id_users', $userId)
                ->select('teams.equ_id', 'teams.equ_name', 'teams.equ_image', 'registration.reg_id')
                ->first();

            return [
                'id' => $race->race_id,
                'name' => $race->race_name,
                'description' => $race->race_description,
                'date_start' => $race->race_date_start ? $race->race_date_start->toDateString() : null,
                'date_end' => $race->race_date_end ? $race->race_date_end->toDateString() : null,
                'image' => $race->image_url ? '/storage/' . $race->image_url : null,
                'is_open' => $race->isOpen(),
                'team' => $userTeamAndRegistration ? [
                    'id' => $userTeamAndRegistration->equ_id,
                    'name' => $userTeamAndRegistration->equ_name,
                    'image' => $userTeamAndRegistration->equ_image ? '/storage/' . $userTeamAndRegistration->equ_image : null,
                ] : null,
                'registration_id' => $userTeamAndRegistration ? $userTeamAndRegistration->reg_id : null,
            ];
        });

        // 2. Récupération des courses avec "Leaderboard" (résultats finaux)
        $races = Race::whereHas('leaderboardUsers', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->when($dateFilter, function ($query) use ($dateFilter) {
                $query->where('race_date_start', '>=', $dateFilter);
            })
            ->with(['leaderboardUsers' => function ($query) use ($userId) {
                $query->where('user_id', $userId);
            }])
            ->orderBy('race_date_start', 'desc')
            ->get()
            ->map(function ($race) use ($userId) {
                $leaderboardEntry = $race->leaderboardUsers->first();

                // On récupère uniquement la team de l'user pour cette course précise
                $userTeam = DB::table('teams')
                    ->join('has_participate', 'teams.equ_id', '=', 'has_participate.equ_id')
                    ->join('registration', 'teams.equ_id', '=', 'registration.equ_id')
                    ->where('registration.race_id', $race->race_id)
                    ->where('has_participate.id_users', $userId)
                    ->select('teams.equ_id', 'teams.equ_name', 'teams.equ_image')
                    ->first();

                return [
                    'id' => $race->race_id,
                    'name' => $race->race_name,
                    'description' => $race->race_description,
                    'date_start' => $race->race_date_start ? $race->race_date_start->toDateString() : null,
                    'date_end' => $race->race_date_end ? $race->race_date_end->toDateString() : null,
                    'image' => $race->image_url ? '/storage/' . $race->image_url : null,
                    'is_open' => $race->isOpen(),
                    'team' => $userTeam ? [
                        'id' => $userTeam->equ_id,
                        'name' => $userTeam->equ_name,
                        'image' => $userTeam->equ_image ? '/storage/' . $userTeam->equ_image : null,
                    ] : null,
                    'leaderboard' => $leaderboardEntry ? [
                        'temps' => $leaderboardEntry->temps,
                        'malus' => $leaderboardEntry->malus,
                        'temps_final' => $leaderboardEntry->temps_final,
                        'points' => $leaderboardEntry->points,
                    ] : null,
                ];
            });

        return Inertia::render('Race/MyRaceIndex', [
            'races' => $races,
            'registers' => $registers,
            'currentPeriod' => $period,
        ]);
    }
}