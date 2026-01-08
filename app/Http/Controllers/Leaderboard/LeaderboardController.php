<?php

namespace App\Http\Controllers\Leaderboard;

use App\Http\Controllers\Controller;

use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller for public leaderboard page.
 * Displays rankings for races with search and filter capabilities.
 * Only shows users with public profiles.
 */
class LeaderboardController extends Controller
{
    public function __construct(
        private LeaderboardService $leaderboardService
    ) {}

    /**
     * Display the public leaderboard page.
     * Shows only users with public profiles.
     *
     * @param Request $request
     * @return Response
     */
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
            // Use public leaderboard that filters by is_public
            $results = $this->leaderboardService->getPublicLeaderboard(
                (int) $raceId,
                $search,
                $type
            );
        } else {
            // Show all public results across all races
            $results = $this->leaderboardService->getPublicLeaderboard(
                null,
                $search,
                $type
            );
        }

        return Inertia::render('Leaderboard/Index', [
            'races' => $races,
            'selectedRace' => $selectedRace,
            'results' => $results,
            'type' => $type,
            'search' => $search,
        ]);
    }

    /**
     * Export leaderboard results to CSV.
     *
     * @param Request $request
     * @param int $raceId
     * @return HttpResponse
     */
    public function export(Request $request, int $raceId): HttpResponse
    {
        $type = $request->input('type', 'individual');
        $race = $this->leaderboardService->getRaces()->firstWhere('race_id', $raceId);
        
        $csv = $this->leaderboardService->exportToCsv($raceId, $type);
        
        $filename = sprintf(
            'classement_%s_%s_%s.csv',
            $race ? str_replace(' ', '_', $race->race_name) : $raceId,
            $type,
            date('Y-m-d')
        );

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export ALL leaderboard results (all races) to CSV.
     * Used for the general leaderboard export.
     *
     * @param Request $request
     * @return HttpResponse
     */
    public function exportAll(Request $request): HttpResponse
    {
        $type = $request->input('type', 'individual');
        
        $csv = $this->leaderboardService->exportAllToCsv($type);
        
        $filename = sprintf(
            'classement_general_%s_%s.csv',
            $type,
            date('Y-m-d')
        );

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
