<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use OpenApi\Annotations as OA;

/**
 * Admin controller for managing leaderboard data.
 * Handles CSV import/export for both individual and team results.
 */
class LeaderboardController extends Controller
{
    public function __construct(
        private LeaderboardService $leaderboardService
    ) {}

    /**
     * Display the admin leaderboard management page.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $races = $this->leaderboardService->getRaces();

        return Inertia::render('Admin/Leaderboard/Index', [
            'races' => $races,
        ]);
    }

    /**
     * Import leaderboard results from CSV file.
     * Supports both individual and team imports.
     * 
     * Team CSV format: CLT;PUCE;EQUIPE;CATÉGORIE;TEMPS;PTS
     * Individual CSV format: Rang,Nom,Temps,Malus,Temps Final or user_id;temps;malus
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
            'race_id' => 'required|integer|exists:races,race_id',
            'type' => 'required|in:individual,team',
        ]);

        try {
            $type = $request->input('type', 'individual');
            
            if ($type === 'team') {
                // Use importTeamCsvV2 which supports the new format: CLT;PUCE;EQUIPE;CATÉGORIE;TEMPS;PTS
                // Points from CSV are imported as-is without recalculation
                $results = $this->leaderboardService->importTeamCsvV2(
                    $request->file('file'),
                    $request->integer('race_id'),
                    false // Use points from CSV as-is
                );
            } else {
                $results = $this->leaderboardService->importCsv(
                    $request->file('file'),
                    $request->integer('race_id')
                );
            }

            activity()
                ->causedBy($request->user())
                ->withProperties([
                    'level' => 'info',
                    'action' => 'LEADERBOARD_CSV_IMPORT',
                    'content' => array_merge($results, ['type' => $type]),
                    'ip' => $request->ip(),
                ])
                ->log('LEADERBOARD_CSV_IMPORT');

            $typeLabel = $type === 'team' ? 'équipes' : 'individuels';
            $message = "Import terminé: {$results['success']} résultats {$typeLabel} importés avec succès.";
            
            // Add info about created teams if applicable
            if ($type === 'team' && isset($results['created_teams']) && $results['created_teams'] > 0) {
                $message .= " ({$results['created_teams']} nouvelles équipes créées)";
            }
            
            // Add info about created users if applicable (for individual import)
            if ($type === 'individual' && isset($results['created']) && $results['created'] > 0) {
                $message .= " ({$results['created']} nouveaux utilisateurs créés)";
            }
            
            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['file' => $e->getMessage()]);
        }
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

        activity()
            ->causedBy($request->user())
            ->withProperties([
                'level' => 'info',
                'action' => 'LEADERBOARD_CSV_EXPORT',
                'content' => ['race_id' => $raceId, 'type' => $type],
                'ip' => $request->ip(),
            ])
            ->log('LEADERBOARD_CSV_EXPORT');

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Display leaderboard results for a specific race.
     *
     * @param Request $request
     * @param int $raceId
     * @return Response
     */
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

    /**
     * Delete a leaderboard result.
     *
     * @param Request $request
     * @param int $resultId
     * @return \Illuminate\Http\RedirectResponse
     */
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

            return redirect()->back()->with('success', 'Résultat supprimé avec succès.');
        }

        return redirect()->back()->withErrors(['error' => 'Résultat non trouvé.']);
    }
}
