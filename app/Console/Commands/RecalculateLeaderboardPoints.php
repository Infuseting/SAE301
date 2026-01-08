<?php

namespace App\Console\Commands;

use App\Services\LeaderboardService;
use Illuminate\Console\Command;

class RecalculateLeaderboardPoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leaderboard:recalculate-points 
                            {--type=all : Type of leaderboard to recalculate (individual, team, all)}
                            {--race= : Specific race ID to recalculate}
                            {--force : Force recalculation even if points already exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate points for leaderboard entries where points are null';

    /**
     * Execute the console command.
     */
    public function handle(LeaderboardService $service): int
    {
        $type = $this->option('type');
        $raceId = $this->option('race');
        $force = $this->option('force');

        if (!in_array($type, ['individual', 'team', 'all'])) {
            $this->error("Invalid type. Must be 'individual', 'team', or 'all'.");
            return Command::FAILURE;
        }

        $this->info("Recalculating leaderboard points...");
        $this->info("Type: {$type}");
        $this->info("Force: " . ($force ? 'Yes' : 'No'));

        if ($raceId) {
            // Recalculate for specific race
            $this->info("Race ID: {$raceId}");
            
            if ($type === 'all' || $type === 'individual') {
                $result = $service->recalculatePointsForRace((int) $raceId, 'individual', $force);
                $this->info("Individual: {$result['updated']}/{$result['total']} updated");
            }
            
            if ($type === 'all' || $type === 'team') {
                $result = $service->recalculatePointsForRace((int) $raceId, 'team', $force);
                $this->info("Team: {$result['updated']}/{$result['total']} updated");
            }
        } else {
            // Recalculate for all races
            $results = $service->recalculateAllPoints($type, $force);
            
            $this->newLine();
            
            if (!empty($results['individual'])) {
                $totalIndividual = array_sum(array_column($results['individual'], 'updated'));
                $this->info("Individual results updated: {$totalIndividual}");
                
                $this->table(
                    ['Race ID', 'Total', 'Updated'],
                    array_map(fn($r) => [$r['race_id'], $r['total'], $r['updated']], $results['individual'])
                );
            }
            
            if (!empty($results['team'])) {
                $totalTeam = array_sum(array_column($results['team'], 'updated'));
                $this->info("Team results updated: {$totalTeam}");
                
                $this->table(
                    ['Race ID', 'Total', 'Updated'],
                    array_map(fn($r) => [$r['race_id'], $r['total'], $r['updated']], $results['team'])
                );
            }
        }

        $this->newLine();
        $this->info('Points recalculation complete!');
        
        return Command::SUCCESS;
    }
}
