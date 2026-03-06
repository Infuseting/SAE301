<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "============================================================\n";
echo "SAE301 - Points des équipes ayant terminé une course\n";
echo "============================================================\n\n";

// Récupérer toutes les équipes avec leurs participants et leurs temps
$teams = DB::table('teams')
    ->whereBetween('equ_id', [650, 659])
    ->orderBy('equ_id')
    ->get();

foreach ($teams as $team) {
    echo "=== Équipe #{$team->equ_id} - {$team->equ_name} ===\n";
    
    // Récupérer les participants de l'équipe
    $participants = DB::table('has_participate as hp')
        ->join('users as u', 'hp.id_users', '=', 'u.id')
        ->where('hp.equ_id', $team->equ_id)
        ->get(['u.id', 'u.first_name', 'u.last_name', 'hp.adh_id']);
    
    echo "Participants: " . $participants->count() . "\n";
    
    if ($participants->count() > 0) {
        foreach ($participants as $p) {
            echo "  - {$p->first_name} {$p->last_name} (User #{$p->id})\n";
            
            // Récupérer les temps de ce participant
            $times = DB::table('time as t')
                ->join('races as r', 't.race_id', '=', 'r.race_id')
                ->where('t.user_id', $p->id)
                ->orderBy('r.race_name')
                ->get(['r.race_id', 'r.race_name', 't.time_hours', 't.time_minutes', 't.time_seconds', 't.time_total_seconds', 't.time_rank']);
            
            if ($times->count() > 0) {
                foreach ($times as $t) {
                    $time = sprintf("%02d:%02d:%02d", $t->time_hours, $t->time_minutes, $t->time_seconds);
                    echo "    → {$t->race_name}: {$time} (Rank: {$t->time_rank})\n";
                }
            } else {
                echo "    → Aucun temps enregistré\n";
            }
        }
        
        // Calculer les points de l'équipe par course
        echo "\n  Points par course:\n";
        
        $userIds = $participants->pluck('id')->toArray();
        
        // Grouper par course
        $coursePoints = DB::table('time as t')
            ->join('races as r', 't.race_id', '=', 'r.race_id')
            ->whereIn('t.user_id', $userIds)
            ->select('r.race_id', 'r.race_name', DB::raw('SUM(t.time_rank) as total_rank'), DB::raw('COUNT(*) as participant_count'))
            ->groupBy('r.race_id', 'r.race_name')
            ->get();
        
        if ($coursePoints->count() > 0) {
            foreach ($coursePoints as $cp) {
                echo "    - {$cp->race_name}: {$cp->participant_count} participants, Total des rangs: {$cp->total_rank}\n";
            }
        } else {
            echo "    Aucune course terminée\n";
        }
    } else {
        echo "  Aucun participant\n";
    }
    
    echo "\n";
}

echo "============================================================\n";
echo "Note: En raid, les points sont généralement calculés par la\n";
echo "somme des rangs des membres de l'équipe (plus bas = mieux)\n";
echo "============================================================\n";
