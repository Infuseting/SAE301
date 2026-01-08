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
     * @return array Import results with success count, errors, total, created and removed users count
     * @throws Exception If file cannot be read or race not found
     */
    public function importCsv(UploadedFile $file, int $raceId): array
    {
        $results = [
            'success' => 0,
            'errors' => [],
            'total' => 0,
            'created' => 0, // Count of newly created users
            'removed' => 0, // Count of removed leaderboard entries
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
            $processedUserIds = []; // Track all user IDs processed in this import
            
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
                    // Track the user ID that was processed
                    if (!empty($result['user_id'])) {
                        $processedUserIds[] = $result['user_id'];
                    }
                    // Track if a new user was created
                    if (!empty($result['created'])) {
                        $results['created']++;
                    }
                } else {
                    $results['errors'][] = $result['error'];
                }
            }

            // Remove leaderboard entries for users NOT in the CSV
            // This ensures the CSV is the source of truth
            $removedCount = $this->removeAbsentParticipants($raceId, $processedUserIds);
            $results['removed'] = $removedCount;

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
        // Determine the correct column name for user reference in has_participate
        $hasIdUsersColumn = DB::getSchemaBuilder()->hasColumn('has_participate', 'id_users');
        $userIdColumn = $hasIdUsersColumn ? 'id_users' : 'id';

        $teamResults = DB::table('leaderboard_users as lr')
            ->join('has_participate as hp', 'lr.user_id', '=', "hp.{$userIdColumn}")
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
                'points' => $item->points,
                'status' => $item->status,
                'category' => $item->category,
                'puce' => $item->puce,
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
            ->with('ageCategories:id,nom')
            ->orderBy('race_date_start', 'desc')
            ->get()
            ->map(function ($race) {
                $race->age_category_names = $race->age_category_names;
                return $race;
            });
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
                'points' => $item->points,
                'status' => $item->status,
                'category' => $item->category,
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
     * Only shows users with is_public = true.
     * Includes: rank, name, race, raw time, malus, final time, points.
     */
    private function getPublicIndividualLeaderboard(?int $raceId = null, ?string $search = null, int $perPage = 20): array
    {
        $query = LeaderboardUser::with([
                'user:id,first_name,last_name,email,is_public,profile_photo_path',
                'race' => function ($q) {
                    $q->select('race_id', 'race_name', 'race_date_start')->with('ageCategories:id,nom');
                }
            ])
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
                'race_age_categories' => $item->race ? $item->race->age_category_names : [],
                'temps' => $item->temps,
                'temps_formatted' => $item->formatted_temps,
                'malus' => $item->malus,
                'malus_formatted' => $item->formatted_malus,
                'temps_final' => $item->temps_final,
                'temps_final_formatted' => $item->formatted_temps_final,
                'points' => $item->points ?? 0,
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
     * All teams are displayed without visibility restrictions.
     * Includes team members list and age category from race.
     */
    private function getPublicTeamLeaderboard(?int $raceId = null, ?string $search = null, int $perPage = 20): array
    {
        $query = LeaderboardTeam::with([
                'team' => function ($q) {
                    $q->select('equ_id', 'equ_name', 'equ_image')
                      ->with(['users:id,first_name,last_name', 'participants:id,first_name,last_name']);
                },
                'race' => function ($q) {
                    $q->select('race_id', 'race_name', 'race_date_start')->with('ageCategories:id,nom');
                }
            ])
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
            // Get team members list - try both relations (users via id_users, participants via id)
            $members = [];
            if ($item->team) {
                // Try 'users' relation first (id_users column)
                if ($item->team->users && $item->team->users->isNotEmpty()) {
                    $members = $item->team->users->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->first_name . ' ' . $user->last_name,
                        ];
                    })->toArray();
                }
                // Fallback to 'participants' relation (id column)
                elseif ($item->team->participants && $item->team->participants->isNotEmpty()) {
                    $members = $item->team->participants->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->first_name . ' ' . $user->last_name,
                        ];
                    })->toArray();
                }
            }

            // Get age category from race relation
            $ageCategory = null;
            if ($item->race && $item->race->ageCategories->isNotEmpty()) {
                $ageCategory = $item->race->ageCategories->pluck('nom')->implode(', ');
            }

            return [
                'rank' => $rank++,
                'id' => $item->id,
                'equ_id' => $item->equ_id,
                'team_name' => $item->team ? $item->team->equ_name : 'Unknown',
                'team_image' => $item->team ? $item->team->equ_image : null,
                'race_id' => $item->race_id,
                'race_name' => $item->race ? $item->race->race_name : 'Unknown',
                'race_date' => $item->race ? $item->race->race_date_start : null,
                'race_age_categories' => $item->race ? $item->race->age_category_names : [],
                'age_category' => $ageCategory,
                'average_temps' => $item->average_temps,
                'average_temps_formatted' => $item->formatted_average_temps,
                'average_malus' => $item->average_malus,
                'average_malus_formatted' => $item->formatted_average_malus,
                'average_temps_final' => $item->average_temps_final,
                'average_temps_final_formatted' => $item->formatted_average_temps_final,
                'member_count' => $item->member_count,
                'members' => $members,
                'points' => $item->points,
                'status' => $item->status,
                'category' => $item->category,
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
     * For individual: includes rank, name, time, race, malus, final time, points (excludes private profiles)
     * For team: includes rank, team, category, time, points
     *
     * @param int $raceId The race ID
     * @param string $type 'individual' or 'team'
     * @return string CSV content
     */
    public function exportToCsv(int $raceId, string $type = 'individual'): string
    {
        $output = fopen('php://temp', 'r+');

        if ($type === 'team') {
            // Team export: rang, équipe, catégorie, temps, points
            fputcsv($output, ['Rang', 'Equipe', 'Catégorie', 'Temps', 'Points'], ';');

            $results = LeaderboardTeam::with([
                    'team:equ_id,equ_name',
                    'race' => function ($q) {
                        $q->select('race_id', 'race_name')->with('ageCategories:id,nom');
                    }
                ])
                ->where('race_id', $raceId)
                ->orderBy('average_temps_final', 'asc')
                ->get();

            $rank = 1;
            foreach ($results as $result) {
                // Get age category from race relation
                $ageCategory = $result->category ?? '-';
                if ($result->race && $result->race->ageCategories->isNotEmpty()) {
                    $ageCategory = $result->race->ageCategories->pluck('nom')->implode(', ');
                }

                fputcsv($output, [
                    $rank++,
                    $result->team ? $result->team->equ_name : 'Unknown',
                    $ageCategory,
                    $result->formatted_average_temps_final,
                    $result->points ?? 0,
                ], ';');
            }
        } else {
            // Individual export: rang, nom, temps, course, malus, temps final, points (ONLY PUBLIC PROFILES)
            fputcsv($output, ['Rang', 'Nom', 'Temps', 'Course', 'Malus', 'Temps Final', 'Points'], ';');

            $race = Race::find($raceId);
            $raceName = $race ? $race->race_name : 'Unknown';

            $results = LeaderboardUser::with(['user:id,first_name,last_name,is_public'])
                ->where('race_id', $raceId)
                ->whereHas('user', function ($query) {
                    $query->where('is_public', true);
                })
                ->orderBy('temps_final', 'asc')
                ->get();

            $rank = 1;
            foreach ($results as $result) {
                fputcsv($output, [
                    $rank++,
                    $result->user ? $result->user->first_name . ' ' . $result->user->last_name : 'Unknown',
                    $result->formatted_temps,
                    $raceName,
                    $result->formatted_malus,
                    $result->formatted_temps_final,
                    $result->points ?? 0,
                ], ';');
            }
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Export ALL leaderboards (all races) to CSV format.
     * This is used for the general leaderboard export.
     * For individual: only exports PUBLIC profiles (is_public = true).
     * For team: all teams are exported (no visibility restriction).
     *
     * Individual CSV: rang, nom, temps, course, malus, temps final, points
     * Team CSV: rang, équipe, catégorie, temps, points
     *
     * @param string $type 'individual' or 'team'
     * @return string CSV content
     */
    public function exportAllToCsv(string $type = 'individual'): string
    {
        $output = fopen('php://temp', 'r+');

        if ($type === 'team') {
            // Team export - all races (all teams are public by default)
            // Columns: rang, équipe, catégorie, temps, points
            fputcsv($output, ['Rang', 'Equipe', 'Catégorie', 'Temps', 'Points'], ';');

            $results = LeaderboardTeam::with([
                    'team:equ_id,equ_name',
                    'race' => function ($q) {
                        $q->select('race_id', 'race_name')->with('ageCategories:id,nom');
                    }
                ])
                ->orderBy('average_temps_final', 'asc')
                ->get();

            $rank = 1;
            foreach ($results as $result) {
                // Get age category from race relation
                $ageCategory = $result->category ?? '-';
                if ($result->race && $result->race->ageCategories->isNotEmpty()) {
                    $ageCategory = $result->race->ageCategories->pluck('nom')->implode(', ');
                }

                fputcsv($output, [
                    $rank++,
                    $result->team ? $result->team->equ_name : 'Unknown',
                    $ageCategory,
                    $result->formatted_average_temps_final,
                    $result->points ?? 0,
                ], ';');
            }
        } else {
            // Individual export - all races, ONLY PUBLIC PROFILES
            // Columns: rang, nom, temps, course, malus, temps final, points
            fputcsv($output, ['Rang', 'Nom', 'Temps', 'Course', 'Malus', 'Temps Final', 'Points'], ';');

            $results = LeaderboardUser::with([
                'user:id,first_name,last_name,is_public',
                'race:race_id,race_name,race_date_start'
            ])
                ->whereHas('user', function ($query) {
                    $query->where('is_public', true);
                })
                ->orderBy('temps_final', 'asc')
                ->get();

            $rank = 1;
            foreach ($results as $result) {
                fputcsv($output, [
                    $rank++,
                    $result->user ? $result->user->first_name . ' ' . $result->user->last_name : 'Unknown',
                    $result->formatted_temps,
                    $result->race ? $result->race->race_name : 'Unknown',
                    $result->formatted_malus,
                    $result->formatted_temps_final,
                    $result->points ?? 0,
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
     * If user not found, creates a new user with adh_id=null, doc_id=null, team and participation.
     * If user exists but has no team, creates a solo team for them.
     * Also creates leaderboard_teams entry for solo teams.
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
        $team = null;

        // If user not found, create new user with team and participation (adh_id=null, doc_id=null)
        if (!$user) {
            $result = $this->createUserFromName($nom, $raceId);
            $user = $result['user'];
            $team = $result['team'];
            $wasCreated = true;
            Log::info("Created new user from CSV import: {$nom} (ID: {$user->id}, adh_id=null, doc_id=null)");
        } else {
            // User exists - ensure they have a team and participation
            // If they don't have a team, create a solo team for them
            $team = $this->ensureExistingUserHasTeamForRace($user, $raceId);
        }

        LeaderboardUser::updateOrCreate(
            ['user_id' => $user->id, 'race_id' => $raceId],
            ['temps' => $temps, 'malus' => $malus]
        );

        // Add/update team in leaderboard_teams for this race
        if ($team) {
            LeaderboardTeam::updateOrCreate(
                ['equ_id' => $team->equ_id, 'race_id' => $raceId],
                [
                    'average_temps' => $temps,
                    'average_malus' => $malus,
                    'average_temps_final' => $temps + $malus,
                    'member_count' => 1,
                ]
            );
        }

        return ['success' => true, 'created' => $wasCreated, 'user_id' => $user->id];
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

        // Get user's existing team if they have one
        $team = $this->getUserExistingTeam($user);

        LeaderboardUser::updateOrCreate(
            ['user_id' => $userId, 'race_id' => $raceId],
            ['temps' => $temps, 'malus' => $malus]
        );

        // If user has a team, add/update it in leaderboard_teams for this race
        if ($team) {
            LeaderboardTeam::updateOrCreate(
                ['equ_id' => $team->equ_id, 'race_id' => $raceId],
                [
                    'average_temps' => $temps,
                    'average_malus' => $malus,
                    'average_temps_final' => $temps + $malus,
                    'member_count' => 1,
                ]
            );
        }

        return ['success' => true, 'user_id' => $userId];
    }

    /**
     * Get the existing team for a user via has_participate.
     * Does NOT create a new team if the user doesn't have one.
     *
     * @param User $user The user to check
     * @return Team|null The user's existing team, or null if they don't have one
     */
    private function getUserExistingTeam(User $user): ?Team
    {
        // Check which column exists for user reference
        $hasIdUsersColumn = DB::getSchemaBuilder()->hasColumn('has_participate', 'id_users');
        $userIdColumn = $hasIdUsersColumn ? 'id_users' : 'id';

        // Check if user already has a participation
        $existingParticipation = DB::table('has_participate')
            ->where($userIdColumn, $user->id)
            ->first();

        if ($existingParticipation) {
            return Team::find($existingParticipation->equ_id);
        }

        return null;
    }

    /**
     * Ensure an existing user has a team and participation for the given race.
     * Creates a solo team if the user doesn't have one.
     * This handles existing users who are not yet in has_participate.
     *
     * @param User $user The existing user
     * @param int $raceId The race ID for the participation
     * @return Team The user's team (existing or newly created)
     */
    private function ensureExistingUserHasTeamForRace(User $user, int $raceId): Team
    {
        $hasIdUsersColumn = DB::getSchemaBuilder()->hasColumn('has_participate', 'id_users');
        $userIdColumn = $hasIdUsersColumn ? 'id_users' : 'id';

        // Check if user already has a participation
        $existingParticipation = DB::table('has_participate')
            ->where($userIdColumn, $user->id)
            ->first();

        if ($existingParticipation) {
            // User already has a team
            return Team::find($existingParticipation->equ_id);
        }

        // User has no participation - create a solo team
        Log::info("User {$user->id} has no team, creating solo team for race {$raceId}");

        // Ensure user has a member record or create one
        $memberId = $user->adh_id;
        if (!$memberId) {
            // Create a dummy member for the team
            $member = Member::create([
                'adh_license' => 'AUTO-' . Str::random(8),
                'adh_end_validity' => now()->addYear(),
                'adh_date_added' => now(),
            ]);
            $memberId = $member->adh_id;
        }

        // Create solo team
        $teamName = Str::limit($user->first_name . ' ' . $user->last_name, 32, '');
        $team = Team::create([
            'equ_name' => $teamName,
            'adh_id' => $memberId,
        ]);

        // Link user to team via has_participate
        $participateData = [
            $userIdColumn => $user->id,
            'equ_id' => $team->equ_id,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (DB::getSchemaBuilder()->hasColumn('has_participate', 'adh_id')) {
            $participateData['adh_id'] = $memberId;
        }
        if (DB::getSchemaBuilder()->hasColumn('has_participate', 'is_leader')) {
            $participateData['is_leader'] = true;
        }

        DB::table('has_participate')->insert($participateData);

        Log::info("Created solo team '{$teamName}' (ID: {$team->equ_id}) and participation for existing user {$user->id}");

        return $team;
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
     * The user profile is set to private by default with adh_id = null and doc_id = null.
     * A dummy Member is created ONLY for the team reference (teams.adh_id is NOT NULL).
     *
     * @param string $fullName The full name (e.g., "Jean Dupont")
     * @param int $raceId The race ID to link participation to
     * @return array Array with 'user' and 'team' keys
     */
    private function createUserFromName(string $fullName, int $raceId): array
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

        // Create a dummy Member for the team (teams.adh_id is NOT NULL)
        // This member is ONLY used for the team reference, NOT linked to the user
        $dummyMember = Member::create([
            'adh_license' => 'IMPORT-' . Str::random(8),
            'adh_end_validity' => now()->addYear(),
            'adh_date_added' => now(),
        ]);

        // Create the user with private profile, NO Member reference (adh_id = null), NO medical doc (doc_id = null)
        $user = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => Hash::make(Str::random(32)),
            'is_public' => false, // Private profile
            'password_is_set' => false, // User needs to set their own password
            'active' => true,
            'doc_id' => null, // No medical doc
            'adh_id' => null, // No member reference - user is imported only
        ]);

        // Create a solo team for this user (using dummy member for the required adh_id)
        $teamName = Str::limit($firstName . ' ' . $lastName, 32, '');
        $team = Team::create([
            'equ_name' => $teamName,
            'adh_id' => $dummyMember->adh_id, // Team needs a member reference
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

        // Add adh_id if column exists (use user's id as fallback since user has no adh_id)
        if (DB::getSchemaBuilder()->hasColumn('has_participate', 'adh_id')) {
            $participateData['adh_id'] = $dummyMember->adh_id;
        }
        if (DB::getSchemaBuilder()->hasColumn('has_participate', 'is_leader')) {
            $participateData['is_leader'] = true;
        }

        DB::table('has_participate')->insert($participateData);

        Log::info("Created imported user {$user->id} with solo team {$team->equ_id} for race {$raceId} (adh_id=null, doc_id=null)");

        return ['user' => $user, 'team' => $team];
    }

    /**
     * Remove leaderboard entries for users not present in the imported CSV.
     * This ensures the CSV is the source of truth for race results.
     * Users who were in the leaderboard but are not in the new CSV will be removed.
     * For imported users (email ending with @imported.local), also cleans up:
     * - has_participate entries (for this race only)
     * - Solo teams if not used in other races
     *
     * @param int $raceId The race ID
     * @param array $processedUserIds Array of user IDs that were in the CSV
     * @return int Number of entries removed
     */
    private function removeAbsentParticipants(int $raceId, array $processedUserIds): int
    {
        if (empty($processedUserIds)) {
            // If no users were processed, remove ALL entries for this race
            $entriesToRemove = LeaderboardUser::where('race_id', $raceId)->get();
            $removedCount = $entriesToRemove->count();

            if ($removedCount > 0) {
                $removedUserIds = $entriesToRemove->pluck('user_id')->toArray();
                $this->cleanupUsersParticipationForRace($removedUserIds, $raceId);
                LeaderboardUser::where('race_id', $raceId)->delete();
                Log::info("Removed all {$removedCount} leaderboard entries for race {$raceId} (empty CSV import)");
            }

            return $removedCount;
        }

        // Find entries that exist in leaderboard but were NOT in the CSV
        $entriesToRemove = LeaderboardUser::where('race_id', $raceId)
            ->whereNotIn('user_id', $processedUserIds)
            ->get();

        $removedCount = $entriesToRemove->count();

        if ($removedCount > 0) {
            // Log which users are being removed
            $removedUserIds = $entriesToRemove->pluck('user_id')->toArray();
            Log::info("Removing {$removedCount} leaderboard entries for race {$raceId}. User IDs: " . implode(', ', $removedUserIds));

            // Clean up users' participation for THIS race only
            $this->cleanupUsersParticipationForRace($removedUserIds, $raceId);

            // Delete the leaderboard entries
            LeaderboardUser::where('race_id', $raceId)
                ->whereNotIn('user_id', $processedUserIds)
                ->delete();
        }

        return $removedCount;
    }

    /**
     * Clean up participation data for users being removed from a specific race leaderboard.
     * Removes has_participate entries for this race.
     * If the user's team is no longer used in any other race, deletes the team.
     * Only affects imported users (email ending with @imported.local).
     *
     * @param array $userIds Array of user IDs to check and clean up
     * @param int $raceId The race ID being cleaned up
     * @return void
     */
    private function cleanupUsersParticipationForRace(array $userIds, int $raceId): void
    {
        if (empty($userIds)) {
            return;
        }

        // Get imported users (email ending with @imported.local)
        $importedUsers = User::whereIn('id', $userIds)
            ->where('email', 'like', '%@imported.local')
            ->get();

        if ($importedUsers->isEmpty()) {
            return;
        }

        $hasIdUsersColumn = DB::getSchemaBuilder()->hasColumn('has_participate', 'id_users');
        $userIdColumn = $hasIdUsersColumn ? 'id_users' : 'id';

        foreach ($importedUsers as $user) {
            Log::info("Cleaning up imported user participation for race {$raceId}: {$user->first_name} {$user->last_name} (ID: {$user->id})");

            // Get user's team before deleting participation
            $participation = DB::table('has_participate')
                ->where($userIdColumn, $user->id)
                ->first();

            if (!$participation) {
                continue;
            }

            $teamId = $participation->equ_id;

            // Delete has_participate entry for this user
            DB::table('has_participate')
                ->where($userIdColumn, $user->id)
                ->delete();

            // Check if the team is still used in other races (via leaderboard_teams)
            $teamUsedInOtherRaces = LeaderboardTeam::where('equ_id', $teamId)
                ->where('race_id', '!=', $raceId)
                ->exists();

            // Check if team has other members (other users in has_participate with this team)
            $teamHasOtherMembers = DB::table('has_participate')
                ->where('equ_id', $teamId)
                ->exists();

            // Delete the team if it's not used anywhere else
            if (!$teamUsedInOtherRaces && !$teamHasOtherMembers) {
                $team = Team::find($teamId);
                if ($team) {
                    $memberId = $team->adh_id;
                    
                    // Delete the leaderboard_teams entry for this race first
                    LeaderboardTeam::where('equ_id', $teamId)->where('race_id', $raceId)->delete();
                    
                    // Delete the team
                    $team->delete();
                    Log::info("Deleted solo team {$teamId} as it's no longer used");

                    // Delete the dummy member if it was created for import (starts with IMPORT- or AUTO-)
                    $member = Member::find($memberId);
                    if ($member && (Str::startsWith($member->adh_license, 'IMPORT-') || Str::startsWith($member->adh_license, 'AUTO-'))) {
                        $member->delete();
                        Log::info("Deleted dummy member {$memberId} as it's no longer used");
                    }
                }
            } else {
                // Just delete the leaderboard_teams entry for this race
                LeaderboardTeam::where('equ_id', $teamId)->where('race_id', $raceId)->delete();
            }
        }

        Log::info("Cleaned up participation data for {$importedUsers->count()} imported users in race {$raceId}");
    }

    /**
     * Import team results from CSV with new format.
     * Format: CLT;PUCE;EQUIPE;CATÉGORIE;TEMPS;PTS
     * 
     * Supports:
     * - Negative times (handled as valid times with absolute value)
     * - Different categories (Masculin, Féminin, Mixte)
     * - Points from CSV (optional, can be recalculated)
     *
     * @param UploadedFile $file The CSV file
     * @param int $raceId The race ID
     * @param bool $recalculatePoints If true, recalculates points ignoring CSV values
     * @return array Import results
     */
    public function importTeamCsvV2(UploadedFile $file, int $raceId, bool $recalculatePoints = true): array
    {
        $results = [
            'success' => 0,
            'errors' => [],
            'total' => 0,
            'created_teams' => 0,
        ];

        $race = Race::find($raceId);
        if (!$race) {
            throw new Exception("Race with ID {$raceId} not found");
        }

        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            throw new Exception('Unable to open CSV file');
        }

        // Auto-detect CSV separator
        $firstLine = fgets($handle);
        rewind($handle);
        $separator = $this->detectCsvSeparator($firstLine);

        $header = fgetcsv($handle, 0, $separator);
        if ($header === false) {
            fclose($handle);
            throw new Exception('Unable to read CSV header');
        }

        // Normalize header: lowercase and trim, handle UTF-8 BOM
        $header = array_map(function ($col) {
            $col = trim($col);
            // Remove UTF-8 BOM if present
            $col = preg_replace('/^\xEF\xBB\xBF/', '', $col);
            $col = strtolower($col);
            // Map column names
            $mapping = [
                'clt' => 'clt',
                'puce' => 'puce',
                'equipe' => 'equipe',
                'équipe' => 'equipe',
                'catégorie' => 'category',
                'categorie' => 'category',
                'temps' => 'temps',
                'pts' => 'points',
                'points' => 'points',
            ];
            return $mapping[$col] ?? $col;
        }, $header);

        // Validate required columns
        if (!in_array('equipe', $header)) {
            fclose($handle);
            throw new Exception("Missing required column: EQUIPE");
        }
        if (!in_array('temps', $header)) {
            fclose($handle);
            throw new Exception("Missing required column: TEMPS");
        }

        DB::beginTransaction();

        try {
            $lineNumber = 1;
            $teamsData = [];

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

                $teamName = $data['equipe'] ?? '';
                if (empty($teamName)) {
                    $results['errors'][] = "Line {$lineNumber}: Empty team name";
                    continue;
                }

                // Parse time (can be negative in the CSV)
                $tempsString = $data['temps'] ?? '0';
                $isNegativeTime = str_starts_with($tempsString, '-');
                $temps = $this->parseTime(ltrim($tempsString, '-'));
                if ($isNegativeTime) {
                    // Keep the time as positive for ranking, but we could mark it specially
                    $temps = abs($temps);
                }

                $category = $data['category'] ?? null;
                $puce = $data['puce'] ?? null;
                $csvPoints = isset($data['points']) ? (int) $data['points'] : null;

                // Find or create team by name
                $team = Team::where('equ_name', $teamName)->first();
                $teamCreated = false;

                if (!$team) {
                    // Create a new team
                    $team = $this->createTeamForImport($teamName, $raceId);
                    $teamCreated = true;
                    $results['created_teams']++;
                }

                // Store data for points calculation
                $teamsData[] = [
                    'team' => $team,
                    'temps' => $temps,
                    'category' => $category,
                    'puce' => $puce,
                    'csv_points' => $csvPoints,
                    'line_number' => $lineNumber,
                    'created' => $teamCreated,
                ];

                $results['success']++;
            }

            // Calculate points and save results
            $this->saveTeamLeaderboardResults($teamsData, $race, $recalculatePoints);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            fclose($handle);
            throw $e;
        }

        fclose($handle);

        return $results;
    }

    /**
     * Create a team for import when team doesn't exist.
     *
     * @param string $teamName The team name
     * @param int $raceId The race ID
     * @return Team The created team
     */
    private function createTeamForImport(string $teamName, int $raceId): Team
    {
        // Create a dummy member for the team
        $member = Member::create([
            'adh_license' => 'IMPORT-TEAM-' . Str::random(8),
            'adh_end_validity' => now()->addYear(),
            'adh_date_added' => now(),
        ]);

        // Create the team
        $team = Team::create([
            'equ_name' => $teamName,
            'equ_description' => 'Team imported from CSV',
            'adh_id' => $member->adh_id,
        ]);

        Log::info("Created new team from CSV import: {$teamName} (ID: {$team->equ_id})");

        return $team;
    }

    /**
     * Save team leaderboard results with calculated points.
     *
     * @param array $teamsData Array of team data from import
     * @param Race $race The race
     * @param bool $recalculatePoints Whether to recalculate points
     */
    private function saveTeamLeaderboardResults(array $teamsData, Race $race, bool $recalculatePoints): void
    {
        // Sort by time for ranking
        usort($teamsData, function ($a, $b) {
            return $a['temps'] <=> $b['temps'];
        });

        // Get difficulty coefficient
        $coefficient = $this->getDifficultyCoefficient($race);
        $isLeisureRace = $this->isLeisureRace($race);

        // Calculate points for each team based on ranking
        $previousTime = null;
        $previousPoints = null;
        $rank = 0;

        foreach ($teamsData as $index => $data) {
            $rank++;
            $team = $data['team'];
            $temps = $data['temps'];

            // Calculate points (or use CSV points if not recalculating)
            if ($recalculatePoints) {
                $points = $this->calculatePointsForRank($rank, $temps, $previousTime, $previousPoints, $coefficient, $isLeisureRace);
            } else {
                $points = $data['csv_points'] ?? $this->calculatePointsForRank($rank, $temps, $previousTime, $previousPoints, $coefficient, $isLeisureRace);
            }

            // Store for equality check
            if ($temps !== $previousTime) {
                $previousTime = $temps;
                $previousPoints = $points;
            }

            // Determine status
            $status = LeaderboardTeam::STATUS_CLASSIFIED;

            // Save to leaderboard_teams
            LeaderboardTeam::updateOrCreate(
                ['equ_id' => $team->equ_id, 'race_id' => $race->race_id],
                [
                    'average_temps' => $temps,
                    'average_malus' => 0,
                    'average_temps_final' => $temps,
                    'member_count' => 1, // Will be updated later if team members are imported
                    'points' => $points,
                    'status' => $status,
                    'category' => $data['category'],
                    'puce' => $data['puce'],
                ]
            );
        }
    }

    /**
     * Calculate points for a given rank.
     * 
     * Rules:
     * - 100 points for 1st place
     * - -10 points per position
     * - Minimum 20 points for any classified team
     * - Equal times = equal points
     * - Points multiplied by difficulty coefficient
     * - Leisure races capped at 50 points
     *
     * @param int $rank The rank position
     * @param float $temps The team's time
     * @param float|null $previousTime Previous team's time (for equality check)
     * @param int|null $previousPoints Previous team's points (for equality)
     * @param float $coefficient Difficulty coefficient
     * @param bool $isLeisure Whether it's a leisure race
     * @return int The calculated points
     */
    private function calculatePointsForRank(int $rank, float $temps, ?float $previousTime, ?int $previousPoints, float $coefficient, bool $isLeisure): int
    {
        // If same time as previous, give same points
        if ($previousTime !== null && $temps === $previousTime && $previousPoints !== null) {
            return $previousPoints;
        }

        // Base points calculation: 100 - (rank - 1) * 10
        $basePoints = 100 - (($rank - 1) * 10);

        // Minimum 20 points for any classified team
        $basePoints = max($basePoints, 20);

        // Apply difficulty coefficient
        $points = (int) round($basePoints * $coefficient);

        // Cap at 50 for leisure races
        if ($isLeisure) {
            $points = min($points, 50);
        }

        return $points;
    }

    /**
     * Get difficulty coefficient for a race.
     * 
     * Coefficients:
     * - facile (easy): 1.0
     * - moyenne (medium): 1.2
     * - difficile (hard): 1.5
     *
     * @param Race $race The race
     * @return float The difficulty coefficient
     */
    private function getDifficultyCoefficient(Race $race): float
    {
        $difficulty = strtolower($race->race_difficulty ?? 'moyenne');

        return match ($difficulty) {
            'facile', 'easy' => 1.0,
            'moyenne', 'medium' => 1.2,
            'difficile', 'hard', 'difficult' => 1.5,
            default => 1.0,
        };
    }

    /**
     * Check if a race is a leisure race (points capped at 50).
     *
     * @param Race $race The race
     * @return bool True if leisure race
     */
    private function isLeisureRace(Race $race): bool
    {
        // Check if race type is "loisir" (leisure)
        if ($race->type && strtolower($race->type->typ_name) === 'loisir') {
            return true;
        }

        return false;
    }

    /**
     * Recalculate points for all teams in a race.
     * Useful after modifying results or race parameters.
     *
     * @param int $raceId The race ID
     * @return int Number of teams updated
     */
    public function recalculateTeamPoints(int $raceId): int
    {
        $race = Race::find($raceId);
        if (!$race) {
            throw new Exception("Race with ID {$raceId} not found");
        }

        // Get all classified teams sorted by time
        $teams = LeaderboardTeam::where('race_id', $raceId)
            ->where('status', LeaderboardTeam::STATUS_CLASSIFIED)
            ->orderBy('average_temps_final', 'asc')
            ->get();

        $coefficient = $this->getDifficultyCoefficient($race);
        $isLeisure = $this->isLeisureRace($race);

        $previousTime = null;
        $previousPoints = null;
        $rank = 0;
        $updated = 0;

        foreach ($teams as $team) {
            $rank++;
            $temps = (float) $team->average_temps_final;

            $points = $this->calculatePointsForRank($rank, $temps, $previousTime, $previousPoints, $coefficient, $isLeisure);

            if ($temps !== $previousTime) {
                $previousTime = $temps;
                $previousPoints = $points;
            }

            $team->points = $points;
            $team->save();
            $updated++;
        }

        // Set 0 points for non-classified teams
        LeaderboardTeam::where('race_id', $raceId)
            ->whereIn('status', [
                LeaderboardTeam::STATUS_ABANDONED,
                LeaderboardTeam::STATUS_DISQUALIFIED,
                LeaderboardTeam::STATUS_OUT_OF_RANKING,
            ])
            ->update(['points' => 0]);

        return $updated;
    }

    /**
     * Update team status (abandon, disqualified, etc.).
     *
     * @param int $teamId The team ID in leaderboard_teams
     * @param string $status The new status
     * @return bool Success
     */
    public function updateTeamStatus(int $teamId, string $status): bool
    {
        $validStatuses = [
            LeaderboardTeam::STATUS_CLASSIFIED,
            LeaderboardTeam::STATUS_ABANDONED,
            LeaderboardTeam::STATUS_DISQUALIFIED,
            LeaderboardTeam::STATUS_OUT_OF_RANKING,
        ];

        if (!in_array($status, $validStatuses)) {
            throw new Exception("Invalid status: {$status}");
        }

        $leaderboardTeam = LeaderboardTeam::find($teamId);
        if (!$leaderboardTeam) {
            return false;
        }

        $leaderboardTeam->status = $status;

        // If not classified, set points to 0
        if ($status !== LeaderboardTeam::STATUS_CLASSIFIED) {
            $leaderboardTeam->points = 0;
        }

        $leaderboardTeam->save();

        // Recalculate points for all teams in the race
        $this->recalculateTeamPoints($leaderboardTeam->race_id);

        return true;
    }
}
