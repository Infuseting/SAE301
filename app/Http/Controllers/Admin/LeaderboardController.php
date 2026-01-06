<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use OpenApi\Annotations as OA;

class LeaderboardController extends Controller
{
    public function __construct(
        private LeaderboardService $leaderboardService
    ) {}

    public function index(Request $request): Response
    {
        $races = $this->leaderboardService->getRaces();

        return Inertia::render('Admin/Leaderboard/Index', [
            'races' => $races,
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
            'race_id' => 'required|integer|exists:races,race_id',
        ]);

        try {
            $results = $this->leaderboardService->importCsv(
                $request->file('file'),
                $request->integer('race_id')
            );

            activity()
                ->causedBy($request->user())
                ->withProperties([
                    'level' => 'info',
                    'action' => 'LEADERBOARD_CSV_IMPORT',
                    'content' => $results,
                    'ip' => $request->ip(),
                ])
                ->log('LEADERBOARD_CSV_IMPORT');

            return redirect()->back()->with('success', "Import completed: {$results['success']} records imported successfully.");

        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['file' => $e->getMessage()]);
        }
    }

    public function results(Request $request, int $raceId): Response
    {
        $search = $request->input('search');
        $type = $request->input('type', 'individual');

        $data = $type === 'team'
            ? $this->leaderboardService->getTeamLeaderboard($raceId, $search)
            : $this->leaderboardService->getIndividualLeaderboard($raceId, $search);

        return Inertia::render('Admin/Leaderboard/Results', [
            'results' => $data,
            'raceId' => $raceId,
            'type' => $type,
            'search' => $search,
        ]);
    }

    public function destroy(Request $request, int $resultId)
    {
        $deleted = $this->leaderboardService->deleteResult($resultId);

        if ($deleted) {
            activity()
                ->causedBy($request->user())
                ->withProperties([
                    'level' => 'warning',
                    'action' => 'LEADERBOARD_RESULT_DELETED',
                    'content' => ['result_id' => $resultId],
                    'ip' => $request->ip(),
                ])
                ->log('LEADERBOARD_RESULT_DELETED');

            return redirect()->back()->with('success', 'Result deleted successfully.');
        }

        return redirect()->back()->withErrors(['error' => 'Result not found.']);
    }
}
