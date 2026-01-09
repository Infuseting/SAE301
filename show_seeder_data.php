<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "============================================================\n";
echo "SAE301 - DemoDataSeeder Verification (ID Range: 600-700)\n";
echo "============================================================\n\n";

// 1. MEMBERS
echo "=== MEMBERS (Adhérents) - IDs 630-648 ===\n";
$members = DB::table('members')->whereBetween('adh_id', [630, 648])->orderBy('adh_id')->get();
echo "Total: " . $members->count() . " members\n";
foreach ($members as $m) {
    echo "  ID: {$m->adh_id} - License: {$m->adh_license}\n";
}

// 2. USERS
echo "\n=== USERS (Utilisateurs) - IDs 630-648 ===\n";
$users = DB::table('users')->whereBetween('id', [630, 648])->orderBy('id')->get();
echo "Total: " . $users->count() . " users\n";
foreach ($users as $u) {
    $member = $u->adh_id ? "Member #{$u->adh_id}" : "No member";
    echo "  ID: {$u->id} - {$u->first_name} {$u->last_name} ({$u->email}) - {$member}\n";
}

// 3. CLUBS
echo "\n=== CLUBS - IDs 601-604 ===\n";
$clubs = DB::table('clubs')->whereBetween('club_id', [601, 604])->orderBy('club_id')->get();
echo "Total: " . $clubs->count() . " clubs\n";
foreach ($clubs as $c) {
    echo "  ID: {$c->club_id} - {$c->club_name} ({$c->club_city}) - FFSO: {$c->ffso_id}\n";
    echo "    Created by: User #{$c->created_by}\n";
}

// 4. RAIDS
echo "\n=== RAIDS - IDs 610-611 ===\n";
$raids = DB::table('raids')->whereBetween('raid_id', [610, 611])->orderBy('raid_id')->get();
echo "Total: " . $raids->count() . " raids\n";
foreach ($raids as $r) {
    echo "  ID: {$r->raid_id} - {$r->raid_name}\n";
}

// 5. RACES
echo "\n=== RACES (Courses) - IDs 620-623 ===\n";
$races = DB::table('races')
    ->whereBetween('race_id', [620, 623])
    ->orderBy('race_id')
    ->get();
echo "Total: " . $races->count() . " races\n";
foreach ($races as $r) {
    echo "  ID: {$r->race_id} - {$r->race_name} (Raid #{$r->raid_id}) - Duration: {$r->race_duration_minutes}min\n";
}

// 6. TEAMS
echo "\n=== TEAMS (Équipes) - IDs 650-659 ===\n";
$teams = DB::table('teams')
    ->whereBetween('equ_id', [650, 659])
    ->orderBy('equ_id')
    ->get();
echo "Total: " . $teams->count() . " teams\n";
foreach ($teams as $t) {
    $user = DB::table('users')->where('id', $t->user_id)->first();
    $responsible = $user ? "{$user->first_name} {$user->last_name}" : "Unknown";
    echo "  ID: {$t->equ_id} - {$t->equ_name} - Responsible: {$responsible} (User #{$t->user_id})\n";
}

// 7. TEAM PARTICIPANTS
echo "\n=== TEAM PARTICIPANTS (has_participate) ===\n";
$participants = DB::table('has_participate as hp')
    ->join('teams as t', 'hp.equ_id', '=', 't.equ_id')
    ->join('users as u', 'hp.id_users', '=', 'u.id')
    ->whereBetween('t.equ_id', [650, 659])
    ->orderBy('t.equ_id')
    ->orderBy('u.id')
    ->get(['hp.equ_id', 't.equ_name', 'u.id', 'u.first_name', 'u.last_name', 'hp.adh_id']);
echo "Total: " . $participants->count() . " participations\n";
$currentTeam = null;
foreach ($participants as $p) {
    if ($currentTeam !== $p->equ_id) {
        $currentTeam = $p->equ_id;
        echo "  Team #{$p->equ_id} - {$p->equ_name}:\n";
    }
    echo "    - {$p->first_name} {$p->last_name} (User #{$p->id}, Member #{$p->adh_id})\n";
}

// 8. TIMES/RESULTS
echo "\n=== TIMES/RESULTS (Partial - limited to 10) ===\n";
$times = DB::table('time as t')
    ->join('users as u', 't.user_id', '=', 'u.id')
    ->join('races as r', 't.race_id', '=', 'r.race_id')
    ->whereBetween('t.user_id', [630, 648])
    ->orderBy('t.race_id')
    ->orderBy('t.time_rank')
    ->limit(10)
    ->get(['r.race_name', 'u.first_name', 'u.last_name', 't.time_hours', 't.time_minutes', 't.time_seconds', 't.time_rank']);
    
echo "Showing first " . $times->count() . " results:\n";
foreach ($times as $t) {
    $time = sprintf("%02d:%02d:%02d", $t->time_hours, $t->time_minutes, $t->time_seconds);
    echo "  {$t->race_name} - {$t->first_name} {$t->last_name} - Time: {$time} - Rank: {$t->time_rank}\n";
}

echo "\n============================================================\n";
echo "End of verification\n";
echo "============================================================\n";
