<?php

namespace App\Http\Controllers\Race;

use App\Http\Controllers\Controller;
use App\Models\Race;
use App\Models\Registration;
use App\Models\LeaderboardTeam;
use App\Models\Team;
use App\Models\AgeCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Controller for managing race results by race managers.
 * Handles CSV export of registered teams and import of results.
 * Supports multiple age categories for competitive races.
 */
class RaceResultController extends Controller
{
    use AuthorizesRequests;

    /**
     * Export registered teams to CSV for filling in results.
     * For competitive races with multiple age categories, teams are grouped by category.
     * Only includes teams where at least one member is present.
     *
     * @param int $raceId
     * @return HttpResponse
     */
    public function exportTeamsTemplate(int $raceId): HttpResponse
    {
        $race = Race::with(['categorieAges.ageCategory', 'type'])->findOrFail($raceId);
        
        // Authorize the user (must be race manager)
        $this->authorize('update', $race);

        // Check if race is competitive with age categories
        $isCompetitive = $race->type && strtolower($race->type->typ_name) === 'compétitif';
        $ageCategories = $race->categorieAges->map(fn($pc) => $pc->ageCategory)->filter();

        // Get registrations with at least one present member
        $registrations = Registration::where('race_id', $raceId)
            ->whereHas('participants', function ($query) {
                $query->where('is_present', true);
            })
            ->with(['team', 'participants' => function ($query) {
                $query->where('is_present', true)->with('user');
            }])
            ->get();

        // Build CSV content
        $csv = "\xEF\xBB\xBF"; // UTF-8 BOM for Excel compatibility
        $csv .= "DOSSARD;EQUIPE;CATEGORIE_AGE;TEMPS;MALUS;POINTS\n";

        // If competitive with multiple age categories, organize by category
        if ($isCompetitive && $ageCategories->count() > 1) {
            foreach ($ageCategories as $ageCategory) {
                // Add category separator
                $csv .= "\n";
                $csv .= "# === CATÉGORIE: {$ageCategory->nom} ({$ageCategory->age_min}-{$ageCategory->age_max} ans) ===\n";
                
                foreach ($registrations as $registration) {
                    // Check if team fits in this age category
                    $teamAgeCategory = $this->determineTeamAgeCategory($registration, $ageCategories);
                    
                    if ($teamAgeCategory && $teamAgeCategory->id === $ageCategory->id) {
                        $teamName = $registration->team ? $registration->team->equ_name : 'Équipe inconnue';
                        $dossard = $registration->reg_dossard ?? '';
                        
                        $csv .= sprintf(
                            "%s;%s;%s;;;\n",
                            $dossard,
                            str_replace(';', ',', $teamName),
                            $ageCategory->nom
                        );
                    }
                }
            }
        } else {
            // Non-competitive or single category: simple list
            foreach ($registrations as $registration) {
                $teamName = $registration->team ? $registration->team->equ_name : 'Équipe inconnue';
                $dossard = $registration->reg_dossard ?? '';
                $ageCategory = $ageCategories->first();
                $ageCategoryName = $ageCategory ? $ageCategory->nom : '';
                
                $csv .= sprintf(
                    "%s;%s;%s;;;\n",
                    $dossard,
                    str_replace(';', ',', $teamName),
                    $ageCategoryName
                );
            }
        }

        $filename = sprintf(
            'resultats_%s_%s.csv',
            str_replace(' ', '_', preg_replace('/[^a-zA-Z0-9\s]/', '', $race->race_name)),
            date('Y-m-d')
        );

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Import race results from CSV file.
     * CSV format: DOSSARD;EQUIPE;CATEGORIE_AGE;TEMPS;MALUS;POINTS
     *
     * @param Request $request
     * @param int $raceId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function importResults(Request $request, int $raceId)
    {
        $race = Race::with(['categorieAges.ageCategory'])->findOrFail($raceId);
        
        // Authorize the user (must be race manager)
        $this->authorize('update', $race);

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        try {
            $file = $request->file('csv_file');
            $content = file_get_contents($file->getRealPath());
            
            // Remove BOM if present
            $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
            
            $lines = array_filter(explode("\n", $content));
            
            // Skip header line
            array_shift($lines);
            
            $imported = 0;
            $errors = [];
            
            // Get age categories for this race
            $ageCategories = $race->categorieAges->map(fn($pc) => $pc->ageCategory)->filter();
            
            DB::beginTransaction();
            
            foreach ($lines as $lineNumber => $line) {
                $line = trim($line);
                
                // Skip empty lines and category separator comments
                if (empty($line) || str_starts_with($line, '#')) {
                    continue;
                }
                
                $data = str_getcsv($line, ';');
                
                if (count($data) < 6) {
                    $errors[] = "Ligne " . ($lineNumber + 2) . ": Format invalide (moins de 6 colonnes)";
                    continue;
                }
                
                [$dossard, $teamName, $ageCategoryName, $temps, $malus, $points] = $data;
                
                // Skip if no time data
                if (empty(trim($temps)) && empty(trim($points))) {
                    continue;
                }
                
                // Find registration by dossard or team name
                $registration = $this->findRegistration($raceId, $dossard, $teamName);
                
                if (!$registration) {
                    $errors[] = "Ligne " . ($lineNumber + 2) . ": Équipe non trouvée (dossard: {$dossard}, nom: {$teamName})";
                    continue;
                }
                
                // Find age category by name
                $ageCategory = null;
                if (!empty(trim($ageCategoryName))) {
                    $ageCategory = $ageCategories->first(function ($cat) use ($ageCategoryName) {
                        return strcasecmp($cat->nom, trim($ageCategoryName)) === 0;
                    });
                }
                
                // Parse time values
                $tempsSeconds = $this->parseTimeToSeconds($temps);
                $malusSeconds = $this->parseTimeToSeconds($malus);
                $tempsFinal = $tempsSeconds + $malusSeconds;
                $pointsValue = is_numeric(trim($points)) ? (int)trim($points) : 0;
                
                // Create or update leaderboard entry
                LeaderboardTeam::updateOrCreate(
                    [
                        'equ_id' => $registration->equ_id,
                        'race_id' => $raceId,
                        'age_category_id' => $ageCategory?->id,
                    ],
                    [
                        'average_temps' => $tempsSeconds,
                        'average_malus' => $malusSeconds,
                        'average_temps_final' => $tempsFinal,
                        'member_count' => $registration->participants()->where('is_present', true)->count(),
                        'points' => $pointsValue,
                    ]
                );
                
                $imported++;
            }
            
            DB::commit();
            
            // Log activity
            activity()
                ->causedBy($request->user())
                ->performedOn($race)
                ->withProperties([
                    'level' => 'info',
                    'action' => 'RACE_RESULTS_IMPORTED',
                    'content' => ['race_id' => $raceId, 'imported' => $imported, 'errors' => count($errors)],
                    'ip' => $request->ip(),
                ])
                ->log('RACE_RESULTS_IMPORTED');
            
            $message = "{$imported} résultat(s) importé(s) avec succès.";
            if (!empty($errors)) {
                $message .= " " . count($errors) . " erreur(s) rencontrée(s).";
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'errors' => $errors,
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Erreur lors de l\'import: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Check if race has results uploaded.
     *
     * @param int $raceId
     * @return bool
     */
    public static function hasResults(int $raceId): bool
    {
        return LeaderboardTeam::where('race_id', $raceId)->exists();
    }

    /**
     * Find registration by dossard number or team name.
     *
     * @param int $raceId
     * @param string $dossard
     * @param string $teamName
     * @return Registration|null
     */
    private function findRegistration(int $raceId, string $dossard, string $teamName): ?Registration
    {
        // First try by dossard
        if (!empty(trim($dossard))) {
            $registration = Registration::where('race_id', $raceId)
                ->where('reg_dossard', trim($dossard))
                ->first();
            
            if ($registration) {
                return $registration;
            }
        }
        
        // Then try by team name
        $registration = Registration::where('race_id', $raceId)
            ->whereHas('team', function ($query) use ($teamName) {
                $query->where('equ_name', 'LIKE', trim($teamName));
            })
            ->first();
        
        return $registration;
    }

    /**
     * Parse time string (HH:MM:SS or MM:SS or seconds) to seconds.
     *
     * @param string|null $time
     * @return float
     */
    private function parseTimeToSeconds(?string $time): float
    {
        if (empty($time)) {
            return 0;
        }
        
        $time = trim($time);
        
        // If already numeric (seconds)
        if (is_numeric($time)) {
            return (float)$time;
        }
        
        // Parse HH:MM:SS or MM:SS format
        $parts = explode(':', $time);
        
        if (count($parts) === 3) {
            // HH:MM:SS
            return ((int)$parts[0] * 3600) + ((int)$parts[1] * 60) + (float)$parts[2];
        } elseif (count($parts) === 2) {
            // MM:SS
            return ((int)$parts[0] * 60) + (float)$parts[1];
        }
        
        return 0;
    }

    /**
     * Determine the age category of a team based on members' ages.
     * Returns the category where all team members fit within the age range.
     *
     * @param Registration $registration
     * @param \Illuminate\Support\Collection $ageCategories
     * @return AgeCategory|null
     */
    private function determineTeamAgeCategory(Registration $registration, $ageCategories): ?AgeCategory
    {
        $participants = $registration->participants()->with('user')->where('is_present', true)->get();
        
        if ($participants->isEmpty()) {
            return $ageCategories->first();
        }
        
        $now = now();
        $ages = [];
        
        foreach ($participants as $participant) {
            if ($participant->user && $participant->user->birth_date) {
                $birthDate = \Carbon\Carbon::parse($participant->user->birth_date);
                $ages[] = (int) $birthDate->diffInYears($now);
            }
        }
        
        if (empty($ages)) {
            return $ageCategories->first();
        }
        
        // Find the category where all members fit
        // For competitive races, the team's category is determined by the oldest member
        $maxAge = max($ages);
        $minAge = min($ages);
        
        foreach ($ageCategories as $category) {
            // Check if team fits in this category (oldest member determines category)
            if ($maxAge >= $category->age_min && $maxAge <= $category->age_max) {
                return $category;
            }
        }
        
        // If no exact match, return the category for the oldest age
        return $ageCategories->filter(function ($cat) use ($maxAge) {
            return $maxAge <= $cat->age_max;
        })->sortBy('age_max')->first() ?? $ageCategories->last();
    }

    /**
     * Download results CSV (for public view after results are uploaded).
     * Groups results by age category for competitive races.
     *
     * @param int $raceId
     * @return HttpResponse
     */
    public function downloadResults(int $raceId): HttpResponse
    {
        $race = Race::with(['categorieAges.ageCategory', 'type'])->findOrFail($raceId);
        
        // Check if race is competitive with age categories
        $isCompetitive = $race->type && strtolower($race->type->typ_name) === 'compétitif';
        $ageCategories = $race->categorieAges->map(fn($pc) => $pc->ageCategory)->filter();
        
        // Get leaderboard results
        $results = LeaderboardTeam::where('race_id', $raceId)
            ->with(['team', 'ageCategory'])
            ->orderBy('age_category_id')
            ->orderBy('points', 'desc')
            ->orderBy('average_temps_final', 'asc')
            ->get();

        // Build CSV content
        $csv = "\xEF\xBB\xBF"; // UTF-8 BOM for Excel compatibility
        $csv .= "CLASSEMENT;EQUIPE;CATEGORIE_AGE;TEMPS;MALUS;TEMPS_FINAL;POINTS\n";

        // If competitive with multiple age categories, group by category
        if ($isCompetitive && $ageCategories->count() > 1) {
            foreach ($ageCategories as $ageCategory) {
                $categoryResults = $results->filter(fn($r) => $r->age_category_id === $ageCategory->id);
                
                if ($categoryResults->isEmpty()) {
                    continue;
                }
                
                // Add category separator
                $csv .= "\n";
                $csv .= "# === CATÉGORIE: {$ageCategory->nom} ({$ageCategory->age_min}-{$ageCategory->age_max} ans) ===\n";
                
                $rank = 1;
                foreach ($categoryResults as $result) {
                    $teamName = $result->team ? $result->team->equ_name : 'Équipe inconnue';
                    
                    $csv .= sprintf(
                        "%d;%s;%s;%s;%s;%s;%d\n",
                        $rank++,
                        str_replace(';', ',', $teamName),
                        $ageCategory->nom,
                        $this->formatSecondsToTime($result->average_temps ?? 0),
                        $this->formatSecondsToTime($result->average_malus ?? 0),
                        $this->formatSecondsToTime($result->average_temps_final ?? 0),
                        $result->points ?? 0
                    );
                }
            }
        } else {
            // Non-competitive or single category: simple ranking
            $rank = 1;
            foreach ($results as $result) {
                $teamName = $result->team ? $result->team->equ_name : 'Équipe inconnue';
                $ageCategoryName = $result->ageCategory?->nom ?? '';
                
                $csv .= sprintf(
                    "%d;%s;%s;%s;%s;%s;%d\n",
                    $rank++,
                    str_replace(';', ',', $teamName),
                    $ageCategoryName,
                    $this->formatSecondsToTime($result->average_temps ?? 0),
                    $this->formatSecondsToTime($result->average_malus ?? 0),
                    $this->formatSecondsToTime($result->average_temps_final ?? 0),
                    $result->points ?? 0
                );
            }
        }

        $filename = sprintf(
            'classement_%s_%s.csv',
            str_replace(' ', '_', preg_replace('/[^a-zA-Z0-9\s]/', '', $race->race_name)),
            date('Y-m-d')
        );

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Format seconds to time string (HH:MM:SS).
     *
     * @param float $seconds
     * @return string
     */
    private function formatSecondsToTime(float $seconds): string
    {
        if ($seconds <= 0) {
            return '';
        }
        
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        
        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
        }
        
        return sprintf('%02d:%02d', $minutes, $secs);
    }
}
