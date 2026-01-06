<?php

namespace App\Services;

use App\Models\LeaderboardResult;
use App\Models\LeaderboardTeam;
use App\Models\User;
use App\Models\Team;
use App\Models\Race;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Exception;

class LeaderboardService
{
    public function importCsv(UploadedFile $file, int $raceId): array
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

        $requiredColumns = ['user_id', 'temps'];
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

                $userId = (int) ($data['user_id'] ?? 0);
                $temps = $this->parseTime($data['temps'] ?? '0');
                $malus = $this->parseTime($data['malus'] ?? '0');

                if ($userId <= 0) {
                    $results['errors'][] = "Line {$lineNumber}: Invalid user_id";
                    continue;
                }

                $user = User::find($userId);
                if (!$user) {
                    $results['errors'][] = "Line {$lineNumber}: User ID {$userId} not found";
                    continue;
                }

                LeaderboardResult::updateOrCreate(
                    ['user_id' => $userId, 'race_id' => $raceId],
                    ['temps' => $temps, 'malus' => $malus]
                );

                $results['success']++;
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
        $teamResults = DB::table('leaderboard_results as lr')
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
        $query = LeaderboardResult::with(['user:id,first_name,last_name,email'])
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

    public function addResult(int $userId, int $raceId, float $temps, float $malus = 0): LeaderboardResult
    {
        $result = LeaderboardResult::updateOrCreate(
            ['user_id' => $userId, 'race_id' => $raceId],
            ['temps' => $temps, 'malus' => $malus]
        );

        $this->recalculateTeamAverages($raceId);

        return $result;
    }

    public function deleteResult(int $resultId): bool
    {
        $result = LeaderboardResult::find($resultId);
        if (!$result) {
            return false;
        }

        $raceId = $result->race_id;
        $result->delete();

        $this->recalculateTeamAverages($raceId);

        return true;
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
}
