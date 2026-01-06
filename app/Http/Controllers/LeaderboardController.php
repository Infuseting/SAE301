<?php

namespace App\Http\Controllers;

use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaderboardController extends Controller
{
    public function __construct(
        private LeaderboardService $leaderboardService
    ) {}

    public function index(Request $request): Response
    {
        $races = $this->leaderboardService->getRaces();
        $raceId = $request->input('race_id');
        $search = $request->input('search');
        $type = $request->input('type', 'individual');

        $results = null;
        $selectedRace = null;

        if ($raceId) {
            $selectedRace = $races->firstWhere('race_id', (int) $raceId);
            $results = $type === 'team'
                ? $this->leaderboardService->getTeamLeaderboard((int) $raceId, $search)
                : $this->leaderboardService->getIndividualLeaderboard((int) $raceId, $search);
        }

        return Inertia::render('Leaderboard/Index', [
            'races' => $races,
            'selectedRace' => $selectedRace,
            'results' => $results,
            'type' => $type,
            'search' => $search,
        ]);
    }
}
