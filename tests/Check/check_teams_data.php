<?php

$db = new PDO('sqlite:' . __DIR__ . '/database/database.sqlite');

echo "=== TEAMS COURSE ELFE (656-659) ===\n";
$teams = $db->query('SELECT equ_id, equ_name, adh_id, (SELECT first_name || " " || last_name FROM users WHERE id = adh_id) as responsable FROM teams WHERE equ_id BETWEEN 656 AND 659 ORDER BY equ_id')->fetchAll(PDO::FETCH_ASSOC);
foreach ($teams as $team) {
    echo "Équipe {$team['equ_name']} (ID: {$team['equ_id']}): Responsable {$team['responsable']} (ID: {$team['adh_id']})\n";
}

echo "\n=== PARTICIPANTS PAR ÉQUIPE ===\n";
$participants = $db->query('
    SELECT 
        t.equ_id,
        t.equ_name,
        GROUP_CONCAT(u.first_name || " " || u.last_name, ", ") as participants
    FROM teams t
    JOIN has_participate hp ON t.equ_id = hp.equ_id
    JOIN users u ON hp.id_users = u.id
    WHERE t.equ_id BETWEEN 656 AND 659
    GROUP BY t.equ_id
    ORDER BY t.equ_id
')->fetchAll(PDO::FETCH_ASSOC);
foreach ($participants as $p) {
    echo "Équipe {$p['equ_name']} (ID: {$p['equ_id']}): Participants: {$p['participants']}\n";
}

echo "\n=== LEADERBOARD TEAMS ===\n";
$leaderboard = $db->query('
    SELECT 
        lt.equ_id,
        t.equ_name,
        lt.member_count,
        lt.points,
        ac.nom as category
    FROM leaderboard_teams lt
    JOIN teams t ON lt.equ_id = t.equ_id
    LEFT JOIN age_categories ac ON lt.age_category_id = ac.id
    WHERE lt.equ_id BETWEEN 656 AND 659
    ORDER BY lt.equ_id
')->fetchAll(PDO::FETCH_ASSOC);
foreach ($leaderboard as $l) {
    echo "Équipe {$l['equ_name']} (ID: {$l['equ_id']}): {$l['member_count']} membres, {$l['points']} points, Catégorie: {$l['category']}\n";
}
