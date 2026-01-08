<?php

namespace App\Services;

use App\Models\LeaderboardUser;
use App\Models\LeaderboardTeam;
use App\Models\User;
use App\Models\Team;
use App\Models\Race;
use App\Models\MedicalDoc;
use App\Models\Member;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Exception;

class LeaderboardService
{
    /**
     * Import individual results from CSV file.
     * Supports two formats:
     * - Legacy format: user_id;temps;malus (separator: ;)
     * - New format: Rang,Nom,Temps,Malus,Temps Final (separator: ,)
     *
     * @param UploadedFile $file The CSV file to import
     * @param int $raceId The race ID to import results for
     * @return array Import results with success count, errors, total and created users count
     * @throws Exception If file cannot be read or race not found
     */
    public function importCsv(UploadedFile $file, int $raceId): array
    {
        $results = [
            'success' => 0,
            'errors' => [],
            'total' => 0,
            'created' => 0, // Count of newly created users
        ];

        $race = Race::find($raceId);
        if (!$race) {
            throw new Exception("Race with ID {$raceId} not found");
        }

        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            throw new Exception('Unable to open CSV file');
        }

        // Auto-detect CSV separator by reading first line
        $firstLine = fgets($handle);
        rewind($handle);
        $separator = $this->detectCsvSeparator($firstLine);

        $header = fgetcsv($handle, 0, $separator);
        if ($header === false) {
            fclose($handle);
            throw new Exception('Unable to read CSV header');
        }

        // Normalize header: lowercase and trim, also handle French column names
        $header = array_map(function ($col) {
            $col = strtolower(trim($col));
            // Map French column names to internal names
            $mapping = [
                'rang' => 'rang',
                'nom' => 'nom',
                'temps final' => 'temps_final',
            ];
            return $mapping[$col] ?? $col;
        }, $header);

        // Detect format: new format has 'nom' column, legacy has 'user_id'
        $isNewFormat = in_array('nom', $header);
        $isLegacyFormat = in_array('user_id', $header);

        if (!$isNewFormat && !$isLegacyFormat) {
            fclose($handle);
            throw new Exception("Invalid CSV format: must contain either 'Nom' or 'user_id' column");
        }

        // Validate required columns based on format
        if ($isNewFormat && !in_array('temps', $header)) {
            fclose($handle);
            throw new Exception("Missing required column: Temps");
        }
        if ($isLegacyFormat && !in_array('temps', $header)) {
            fclose($handle);
            throw new Exception("Missing required column: temps");
        }

        DB::beginTransaction();

        try {
            $lineNumber = 1;
            while (($row = fgetcsv($handle, 0, $separator)) !== false) {
                $lineNumber++;
                $results['total']++;

                // Skip empty rows
                if (empty(array_filter($row))) {
                    $results['total']--;
                    continue;
                }

                // Ensure row has same number of columns as header
                if (count($row) < count($header)) {
                    $row = array_pad($row, count($header), '');
                }

                $data = array_combine($header, array_map('trim', $row));

                if ($isNewFormat) {
                    // New format: find user by name
                    $result = $this->processNewFormatRow($data, $raceId, $lineNumber);
                } else {
                    // Legacy format: find user by ID
                    $result = $this->processLegacyFormatRow($data, $raceId, $lineNumber);
                }

                if ($result['success']) {
                    $results['success']++;
                    // Track if a new user was created
                    if (!empty($result['created'])) {
                        $results['created']++;
                    }
                } else {
                    $results['errors'][] = $result['error'];
                }
            }

            $this->recalculateTeamAverages($raceId);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            fclose($handle);
            throw $e;
        }

        fclose($handle);

        return $results;
    }

    public function recalculateTeamAverages(int $raceId): void
    {
        $teamResults = DB::table('leaderboard_users as lr')
            ->join('has_participate as hp', 'lr.user_id', '=', 'hp.id')
            ->where('lr.race_id', $raceId)
            ->select(
                'hp.equ_id',
                DB::raw('AVG(lr.temps) as avg_temps'),
                DB::raw('AVG(lr.malus) as avg_malus'),
                DB::raw('AVG(lr.temps + lr.malus) as avg_temps_final'),
                DB::raw('COUNT(lr.user_id) as member_count')
            )
            ->groupBy('hp.equ_id')
            ->get();

        foreach ($teamResults as $result) {
            LeaderboardTeam::updateOrCreate(
                ['equ_id' => $result->equ_id, 'race_id' => $raceId],
                [
                    'average_temps' => $result->avg_temps,
                    'average_malus' => $result->avg_malus,
                    'average_temps_final' => $result->avg_temps_final,
                    'member_count' => $result->member_count,
                ]
            );
        }
    }

    public function getIndividualLeaderboard(int $raceId, ?string $search = null, int $perPage = 20): array
    {
        $query = LeaderboardUser::with(['user:id,first_name,last_name,email'])
            ->where('race_id', $raceId)
            ->orderBy('temps_final', 'asc');

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $results = $query->paginate($perPage);

        $rank = ($results->currentPage() - 1) * $perPage + 1;
        $data = $results->getCollection()->map(function ($item) use (&$rank) {
            return [
                'rank' => $rank++,
                'id' => $item->id,
                'user_id' => $item->user_id,
                'user_name' => $item->user ? $item->user->first_name . ' ' . $item->user->last_name : 'Unknown',
                'temps' => $item->temps,
                'temps_formatted' => $item->formatted_temps,
                'malus' => $item->malus,
                'malus_formatted' => $item->formatted_malus,
                'temps_final' => $item->temps_final,
                'temps_final_formatted' => $item->formatted_temps_final,
            ];
        });

        return [
            'data' => $data,
            'current_page' => $results->currentPage(),
            'last_page' => $results->lastPage(),
            'per_page' => $results->perPage(),
            'total' => $results->total(),
        ];
    }

    public function getTeamLeaderboard(int $raceId, ?string $search = null, int $perPage = 20): array
    {
        $query = LeaderboardTeam::with(['team:equ_id,equ_name,equ_image'])
            ->where('race_id', $raceId)
            ->orderBy('average_temps_final', 'asc');

        if ($search) {
            $query->whereHas('team', function ($q) use ($search) {
                $q->where('equ_name', 'like', "%{$search}%");
            });
        }

        $results = $query->paginate($perPage);

        $rank = ($results->currentPage() - 1) * $perPage + 1;
        $data = $results->getCollection()->map(function ($item) use (&$rank) {
            return [
                'rank' => $rank++,
                'id' => $item->id,
                'equ_id' => $item->equ_id,
                'team_name' => $item->team ? $item->team->equ_name : 'Unknown',
                'team_image' => $item->team ? $item->team->equ_image : null,
                'average_temps' => $item->average_temps,
                'average_temps_formatted' => $item->formatted_average_temps,
                'average_malus' => $item->average_malus,
                'average_malus_formatted' => $item->formatted_average_malus,
                'average_temps_final' => $item->average_temps_final,
                'average_temps_final_formatted' => $item->formatted_average_temps_final,
                'member_count' => $item->member_count,
            ];
        });

        return [
            'data' => $data,
            'current_page' => $results->currentPage(),
            'last_page' => $results->lastPage(),
            'per_page' => $results->perPage(),
            'total' => $results->total(),
        ];
    }

    public function getRaces(): Collection
    {
        return Race::select('race_id', 'race_name', 'race_date_start')
            ->orderBy('race_date_start', 'desc')
            ->get();
    }

    public function addResult(int $userId, int $raceId, float $temps, float $malus = 0): LeaderboardUser
    {
        $result = LeaderboardUser::updateOrCreate(
            ['user_id' => $userId, 'race_id' => $raceId],
            ['temps' => $temps, 'malus' => $malus]
        );

        $this->recalculateTeamAverages($raceId);

        return $result;
    }

    public function deleteResult(int $resultId): bool
    {
        $result = LeaderboardUser::find($resultId);
        if (!$result) {
            return false;
        }

        $raceId = $result->race_id;
        $result->delete();

        $this->recalculateTeamAverages($raceId);

        return true;
    }

    /**
     * Get all results for a specific user with their rank in each race.
     *
     * @param int $userId The user ID
     * @param string|null $search Search by race name
     * @param string $sortBy Sort by 'best' or 'worst' score
     * @return array
     */
    public function getUserResults(int $userId, ?string $search = null, string $sortBy = 'best'): array
    {
        $query = LeaderboardUser::with(['race:race_id,race_name,race_date_start', 'user:id,first_name,last_name'])
            ->where('user_id', $userId);

        if ($search) {
            $query->whereHas('race', function ($q) use ($search) {
                $q->where('race_name', 'like', "%{$search}%");
            });
        }

        $results = $query->get();

        // Calculate rank for each result
        $data = $results->map(function ($item) {
            // Get rank by counting how many have better temps_final
            $rank = LeaderboardUser::where('race_id', $item->race_id)
                ->where('temps_final', '<', $item->temps_final)
                ->count() + 1;

            $totalParticipants = LeaderboardUser::where('race_id', $item->race_id)->count();

            // Get user's team if exists (user belongs to a team regardless of race)
            $teamName = null;
            $teamResult = DB::table('has_participate')
                ->join('teams', 'has_participate.equ_id', '=', 'teams.equ_id')
                ->where('has_participate.id', $item->user_id)
                ->select('teams.equ_name')
                ->first();
            
            if ($teamResult) {
                $teamName = $teamResult->equ_name;
            }

            return [
                'id' => $item->id,
                'race_id' => $item->race_id,
                'race_name' => $item->race ? $item->race->race_name : 'Unknown',
                'race_date' => $item->race ? $item->race->race_date_start : null,
                'rank' => $rank,
                'total_participants' => $totalParticipants,
                'user_name' => $item->user ? $item->user->first_name . ' ' . $item->user->last_name : 'Unknown',
                'team_name' => $teamName,
                'temps' => $item->temps,
                'temps_formatted' => $item->formatted_temps,
                'malus' => $item->malus,
                'malus_formatted' => $item->formatted_malus,
                'temps_final' => $item->temps_final,
                'temps_final_formatted' => $item->formatted_temps_final,
            ];
        });

        // Sort by performance
        if ($sortBy === 'best') {
            $data = $data->sortBy('rank')->values();
        } else {
            $data = $data->sortByDesc('rank')->values();
        }

        return [
            'data' => $data->toArray(),
            'total' => $data->count(),
        ];
    }

    /**
     * Get user's team results for a specific user.
     *
     * @param int $userId The user ID
     * @param string|null $search Search by race name
     * @param string $sortBy Sort by 'best' or 'worst' score
     * @return array
     */
    public function getUserTeamResults(int $userId, ?string $search = null, string $sortBy = 'best'): array
    {
        // Get user's teams
        $userTeams = DB::table('has_participate')
            ->where('id', $userId)
            ->pluck('equ_id')
            ->unique();

        if ($userTeams->isEmpty()) {
            return ['data' => [], 'total' => 0];
        }

        $query = LeaderboardTeam::with(['race:race_id,race_name,race_date_start', 'team:equ_id,equ_name,equ_image'])
            ->whereIn('equ_id', $userTeams);

        if ($search) {
            $query->whereHas('race', function ($q) use ($search) {
                $q->where('race_name', 'like', "%{$search}%");
            });
        }

        $results = $query->get();

        // Calculate rank for each team result
        $data = $results->map(function ($item) {
            $rank = LeaderboardTeam::where('race_id', $item->race_id)
                ->where('average_temps_final', '<', $item->average_temps_final)
                ->count() + 1;

            $totalTeams = LeaderboardTeam::where('race_id', $item->race_id)->count();

            return [
                'id' => $item->id,
                'race_id' => $item->race_id,
                'race_name' => $item->race ? $item->race->race_name : 'Unknown',
                'race_date' => $item->race ? $item->race->race_date_start : null,
                'rank' => $rank,
                'total_participants' => $totalTeams,
                'team_id' => $item->equ_id,
                'team_name' => $item->team ? $item->team->equ_name : 'Unknown',
                'team_image' => $item->team ? $item->team->equ_image : null,
                'average_temps' => $item->average_temps,
                'average_temps_formatted' => $item->formatted_average_temps,
                'average_malus' => $item->average_malus,
                'average_malus_formatted' => $item->formatted_average_malus,
                'average_temps_final' => $item->average_temps_final,
                'average_temps_final_formatted' => $item->formatted_average_temps_final,
                'member_count' => $item->member_count,
            ];
        });

        // Sort by performance
        if ($sortBy === 'best') {
            $data = $data->sortBy('rank')->values();
        } else {
            $data = $data->sortByDesc('rank')->values();
        }

        return [
            'data' => $data->toArray(),
            'total' => $data->count(),
        ];
    }

    /**
     * Get public leaderboard with only public profiles visible.
     *
     * @param int|null $raceId Filter by race ID
     * @param string|null $search Search by race name or user name
     * @param string $type 'individual' or 'team'
     * @param int $perPage Results per page
     * @return array
     */
    public function getPublicLeaderboard(?int $raceId = null, ?string $search = null, string $type = 'individual', int $perPage = 20): array
    {
        if ($type === 'team') {
            return $this->getPublicTeamLeaderboard($raceId, $search, $perPage);
        }

        return $this->getPublicIndividualLeaderboard($raceId, $search, $perPage);
    }

    /**
     * Get public individual leaderboard (only public profiles).
     */
    private function getPublicIndividualLeaderboard(?int $raceId = null, ?string $search = null, int $perPage = 20): array
    {
        $query = LeaderboardUser::with(['user:id,first_name,last_name,email,is_public,profile_photo_path', 'race:race_id,race_name,race_date_start'])
            ->whereHas('user', function ($q) {
                $q->where('is_public', true);
            })
            ->orderBy('temps_final', 'asc');

        if ($raceId) {
            $query->where('race_id', $raceId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })->orWhereHas('race', function ($rq) use ($search) {
                    $rq->where('race_name', 'like', "%{$search}%");
                });
            });
        }

        $results = $query->paginate($perPage);

        $rank = ($results->currentPage() - 1) * $perPage + 1;
        $data = $results->getCollection()->map(function ($item) use (&$rank) {
            return [
                'rank' => $rank++,
                'id' => $item->id,
                'user_id' => $item->user_id,
                'user_name' => $item->user ? $item->user->first_name . ' ' . $item->user->last_name : 'Unknown',
                'user_photo' => $item->user ? $item->user->profile_photo_path : null,
                'race_id' => $item->race_id,
                'race_name' => $item->race ? $item->race->race_name : 'Unknown',
                'race_date' => $item->race ? $item->race->race_date_start : null,
                'temps' => $item->temps,
                'temps_formatted' => $item->formatted_temps,
                'malus' => $item->malus,
                'malus_formatted' => $item->formatted_malus,
                'temps_final' => $item->temps_final,
                'temps_final_formatted' => $item->formatted_temps_final,
            ];
        });

        return [
            'data' => $data,
            'current_page' => $results->currentPage(),
            'last_page' => $results->lastPage(),
            'per_page' => $results->perPage(),
            'total' => $results->total(),
        ];
    }

    /**
     * Get public team leaderboard.
     */
    private function getPublicTeamLeaderboard(?int $raceId = null, ?string $search = null, int $perPage = 20): array
    {
        $query = LeaderboardTeam::with(['team:equ_id,equ_name,equ_image', 'race:race_id,race_name,race_date_start'])
            ->orderBy('average_temps_final', 'asc');

        if ($raceId) {
            $query->where('race_id', $raceId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('team', function ($tq) use ($search) {
                    $tq->where('equ_name', 'like', "%{$search}%");
                })->orWhereHas('race', function ($rq) use ($search) {
                    $rq->where('race_name', 'like', "%{$search}%");
                });
            });
        }

        $results = $query->paginate($perPage);

        $rank = ($results->currentPage() - 1) * $perPage + 1;
        $data = $results->getCollection()->map(function ($item) use (&$rank) {
            return [
                'rank' => $rank++,
                'id' => $item->id,
                'equ_id' => $item->equ_id,
                'team_name' => $item->team ? $item->team->equ_name : 'Unknown',
                'team_image' => $item->team ? $item->team->equ_image : null,
                'race_id' => $item->race_id,
                'race_name' => $item->race ? $item->race->race_name : 'Unknown',
                'race_date' => $item->race ? $item->race->race_date_start : null,
                'average_temps' => $item->average_temps,
                'average_temps_formatted' => $item->formatted_average_temps,
                'average_malus' => $item->average_malus,
                'average_malus_formatted' => $item->formatted_average_malus,
                'average_temps_final' => $item->average_temps_final,
                'average_temps_final_formatted' => $item->formatted_average_temps_final,
                'member_count' => $item->member_count,
            ];
        });

        return [
            'data' => $data,
            'current_page' => $results->currentPage(),
            'last_page' => $results->lastPage(),
            'per_page' => $results->perPage(),
            'total' => $results->total(),
        ];
    }

    /**
     * Export leaderboard to CSV format.
     *
     * @param int $raceId The race ID
     * @param string $type 'individual' or 'team'
     * @return string CSV content
     */
    public function exportToCsv(int $raceId, string $type = 'individual'): string
    {
        $output = fopen('php://temp', 'r+');

        if ($type === 'team') {
            // Team export
            fputcsv($output, ['Rang', 'Equipe', 'Temps Moyen', 'Malus Moyen', 'Temps Final', 'Membres'], ';');

            $results = LeaderboardTeam::with(['team:equ_id,equ_name'])
                ->where('race_id', $raceId)
                ->orderBy('average_temps_final', 'asc')
                ->get();

            $rank = 1;
            foreach ($results as $result) {
                fputcsv($output, [
                    $rank++,
                    $result->team ? $result->team->equ_name : 'Unknown',
                    $result->formatted_average_temps,
                    $result->formatted_average_malus,
                    $result->formatted_average_temps_final,
                    $result->member_count,
                ], ';');
            }
        } else {
            // Individual export
            fputcsv($output, ['Rang', 'Nom', 'Temps', 'Malus', 'Temps Final'], ';');

            $results = LeaderboardUser::with(['user:id,first_name,last_name'])
                ->where('race_id', $raceId)
                ->orderBy('temps_final', 'asc')
                ->get();

            $rank = 1;
            foreach ($results as $result) {
                fputcsv($output, [
                    $rank++,
                    $result->user ? $result->user->first_name . ' ' . $result->user->last_name : 'Unknown',
                    $result->formatted_temps,
                    $result->formatted_malus,
                    $result->formatted_temps_final,
                ], ';');
            }
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Import team results from CSV.
     *
     * @param UploadedFile $file The CSV file
     * @param int $raceId The race ID
     * @return array Import results
     */
    public function importTeamCsv(UploadedFile $file, int $raceId): array
    {
        $results = [
            'success' => 0,
            'errors' => [],
            'total' => 0,
        ];

        $race = Race::find($raceId);
        if (!$race) {
            throw new Exception("Race with ID {$raceId} not found");
        }

        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            throw new Exception('Unable to open CSV file');
        }

        $header = fgetcsv($handle, 0, ';');
        if ($header === false) {
            fclose($handle);
            throw new Exception('Unable to read CSV header');
        }

        $header = array_map('strtolower', array_map('trim', $header));

        $requiredColumns = ['equ_id', 'temps'];
        foreach ($requiredColumns as $col) {
            if (!in_array($col, $header)) {
                fclose($handle);
                throw new Exception("Missing required column: {$col}");
            }
        }

        DB::beginTransaction();

        try {
            $lineNumber = 1;
            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                $lineNumber++;
                $results['total']++;

                $data = array_combine($header, array_map('trim', $row));

                $equId = (int) ($data['equ_id'] ?? 0);
                $temps = $this->parseTime($data['temps'] ?? '0');
                $malus = $this->parseTime($data['malus'] ?? '0');
                $memberCount = (int) ($data['member_count'] ?? 1);

                if ($equId <= 0) {
                    $results['errors'][] = "Line {$lineNumber}: Invalid equ_id";
                    continue;
                }

                $team = Team::where('equ_id', $equId)->first();
                if (!$team) {
                    $results['errors'][] = "Line {$lineNumber}: Team ID {$equId} not found";
                    continue;
                }

                LeaderboardTeam::updateOrCreate(
                    ['equ_id' => $equId, 'race_id' => $raceId],
                    [
                        'average_temps' => $temps,
                        'average_malus' => $malus,
                        'average_temps_final' => $temps + $malus,
                        'member_count' => $memberCount,
                    ]
                );

                $results['success']++;
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            fclose($handle);
            throw $e;
        }

        fclose($handle);

        return $results;
    }

    private function parseTime(string $time): float
    {
        $time = trim($time);

        if (is_numeric($time)) {
            return (float) $time;
        }

        if (preg_match('/^(\d+):(\d+):(\d+(?:\.\d+)?)$/', $time, $matches)) {
            return (int) $matches[1] * 3600 + (int) $matches[2] * 60 + (float) $matches[3];
        }

        if (preg_match('/^(\d+):(\d+(?:\.\d+)?)$/', $time, $matches)) {
            return (int) $matches[1] * 60 + (float) $matches[2];
        }

        return 0;
    }

    /**
     * Detect CSV separator by analyzing first line content.
     *
     * @param string $firstLine The first line of the CSV file
     * @return string The detected separator (';' or ',')
     */
    private function detectCsvSeparator(string $firstLine): string
    {
        $commaCount = substr_count($firstLine, ',');
        $semicolonCount = substr_count($firstLine, ';');

        return $semicolonCount > $commaCount ? ';' : ',';
    }

    /**
     * Process a row from the new CSV format (with Nom column).
     * Finds user by concatenated first_name + last_name.
     * If user not found, creates a new user, team and participation.
     *
     * @param array $data The row data as associative array
     * @param int $raceId The race ID
     * @param int $lineNumber The line number for error reporting
     * @return array Result with 'success' boolean, optional 'error' message, and 'created' flag
     */
    private function processNewFormatRow(array $data, int $raceId, int $lineNumber): array
    {
        $nom = $data['nom'] ?? '';
        $temps = $this->parseTime($data['temps'] ?? '0');
        $malus = $this->parseTime($data['malus'] ?? '0');

        if (empty($nom)) {
            return ['success' => false, 'error' => "Line {$lineNumber}: Empty name"];
        }

        // Find user by full name (first_name + last_name)
        $user = $this->findUserByName($nom);
        $wasCreated = false;

        // If user not found, create new user with team and participation
        if (!$user) {
            $user = $this->createUserFromName($nom, $raceId);
            $wasCreated = true;
            Log::info("Created new user from CSV import: {$nom} (ID: {$user->id})");
        }

        LeaderboardUser::updateOrCreate(
            ['user_id' => $user->id, 'race_id' => $raceId],
            ['temps' => $temps, 'malus' => $malus]
        );

        return ['success' => true, 'created' => $wasCreated];
    }

    /**
     * Process a row from the legacy CSV format (with user_id column).
     *
     * @param array $data The row data as associative array
     * @param int $raceId The race ID
     * @param int $lineNumber The line number for error reporting
     * @return array Result with 'success' boolean and optional 'error' message
     */
    private function processLegacyFormatRow(array $data, int $raceId, int $lineNumber): array
    {
        $userId = (int) ($data['user_id'] ?? 0);
        $temps = $this->parseTime($data['temps'] ?? '0');
        $malus = $this->parseTime($data['malus'] ?? '0');

        if ($userId <= 0) {
            return ['success' => false, 'error' => "Line {$lineNumber}: Invalid user_id"];
        }

        $user = User::find($userId);
        if (!$user) {
            return ['success' => false, 'error' => "Line {$lineNumber}: User ID {$userId} not found"];
        }

        LeaderboardUser::updateOrCreate(
            ['user_id' => $userId, 'race_id' => $raceId],
            ['temps' => $temps, 'malus' => $malus]
        );

        return ['success' => true];
    }

    /**
     * Find a user by their full name (first_name + last_name).
     * Tries multiple matching strategies:
     * 1. Exact match on concatenated first_name + ' ' + last_name
     * 2. Reversed order: last_name + ' ' + first_name
     * 3. Case-insensitive search
     *
     * @param string $fullName The full name to search for
     * @return User|null The found user or null
     */
    private function findUserByName(string $fullName): ?User
    {
        $fullName = trim($fullName);

        if (empty($fullName)) {
            return null;
        }

        // Try exact match first_name + last_name
        $user = User::whereRaw("CONCAT(first_name, ' ', last_name) = ?", [$fullName])->first();
        if ($user) {
            return $user;
        }

        // Try reversed order last_name + first_name
        $user = User::whereRaw("CONCAT(last_name, ' ', first_name) = ?", [$fullName])->first();
        if ($user) {
            return $user;
        }

        // Try case-insensitive match
        $user = User::whereRaw("LOWER(CONCAT(first_name, ' ', last_name)) = LOWER(?)", [$fullName])->first();
        if ($user) {
            return $user;
        }

        // Try case-insensitive reversed order
        $user = User::whereRaw("LOWER(CONCAT(last_name, ' ', first_name)) = LOWER(?)", [$fullName])->first();
        if ($user) {
            return $user;
        }

        // Split name and try partial matching
        $nameParts = preg_split('/\s+/', $fullName);
        if (count($nameParts) >= 2) {
            $firstName = $nameParts[0];
            $lastName = implode(' ', array_slice($nameParts, 1));

            $user = User::whereRaw("LOWER(first_name) = LOWER(?) AND LOWER(last_name) = LOWER(?)", [$firstName, $lastName])->first();
            if ($user) {
                return $user;
            }

            // Try reversed
            $user = User::whereRaw("LOWER(first_name) = LOWER(?) AND LOWER(last_name) = LOWER(?)", [$lastName, $firstName])->first();
            if ($user) {
                return $user;
            }
        }

        return null;
    }

    /**
     * Create a new user from a full name string.
     * Also creates a solo team for the user and links them as participant.
     * The user profile is set to private by default.
     *
     * @param string $fullName The full name (e.g., "Jean Dupont")
     * @param int $raceId The race ID to link participation to
     * @return User The newly created user
     */
    private function createUserFromName(string $fullName, int $raceId): User
    {
        // Parse name into first_name and last_name
        $nameParts = preg_split('/\s+/', trim($fullName));
        $firstName = $nameParts[0] ?? 'Unknown';
        $lastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : 'Unknown';

        // Generate unique email based on name
        $baseEmail = Str::slug($firstName . '.' . $lastName, '.') . '@imported.local';
        $email = $baseEmail;
        $counter = 1;
        while (User::where('email', $email)->exists()) {
            $email = Str::slug($firstName . '.' . $lastName, '.') . '.' . $counter . '@imported.local';
            $counter++;
        }

        // Create or get a MedicalDoc for the user
        $medicalDoc = MedicalDoc::create([
            'doc_num_pps' => 'IMPORT-' . Str::random(8),
            'doc_end_validity' => now()->addYear(),
            'doc_date_added' => now(),
        ]);

        // Create or get a Member for the user
        $member = Member::create([
            'adh_license' => 'IMPORT-' . Str::random(8),
            'adh_end_validity' => now()->addYear(),
            'adh_date_added' => now(),
        ]);

        // Create the user with private profile and random password
        $user = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => Hash::make(Str::random(32)),
            'is_public' => false, // Private profile
            'password_is_set' => false, // User needs to set their own password
            'active' => true,
            'doc_id' => $medicalDoc->doc_id,
            'adh_id' => $member->adh_id,
        ]);

        // Create a solo team for this user
        $teamName = Str::limit($firstName . ' ' . $lastName, 32, '');
        $team = Team::create([
            'equ_name' => $teamName,
            'adh_id' => $member->adh_id,
        ]);

        // Link user to team via has_participate
        // Check which column exists for user reference (id_users in production, id in test)
        $hasIdUsersColumn = DB::getSchemaBuilder()->hasColumn('has_participate', 'id_users');
        $userIdColumn = $hasIdUsersColumn ? 'id_users' : 'id';

        $participateData = [
            $userIdColumn => $user->id,
            'equ_id' => $team->equ_id,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Add adh_id and is_leader if columns exist
        if (DB::getSchemaBuilder()->hasColumn('has_participate', 'adh_id')) {
            $participateData['adh_id'] = $member->adh_id;
        }
        if (DB::getSchemaBuilder()->hasColumn('has_participate', 'is_leader')) {
            $participateData['is_leader'] = true;
        }

        DB::table('has_participate')->insert($participateData);

        return $user;
    }
}
