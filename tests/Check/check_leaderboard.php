<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "============================================================\n";
echo "LEADERBOARD TEAMS - Course ELFE\n";
echo "============================================================\n\n";

$leaderboards = DB::table('leaderboard_teams as lt')
    ->join('teams as t', 'lt.equ_id', '=', 't.equ_id')
    ->join('races as r', 'lt.race_id', '=', 'r.race_id')
    ->select('t.equ_name', 'r.race_name', 'lt.member_count', 'lt.average_temps', 'lt.points', 'lt.category')
    ->orderBy('lt.points', 'desc')
    ->get();

foreach ($leaderboards as $l) {
    $hours = floor($l->average_temps / 3600);
    $minutes = floor(($l->average_temps % 3600) / 60);
    $seconds = $l->average_temps % 60;
    $time = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
    
    echo "Équipe: {$l->equ_name}\n";
    echo "  Course: {$l->race_name}\n";
    echo "  Membres: {$l->member_count}\n";
    echo "  Temps: {$time}\n";
    echo "  Points: {$l->points}\n";
    echo "  Catégorie: {$l->category}\n";
    echo "\n";
}

echo "Total: " . $leaderboards->count() . " équipes au classement\n";
