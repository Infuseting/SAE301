<?php

$db = new PDO('sqlite:' . __DIR__ . '/database/database.sqlite');

echo "═══════════════════════════════════════════════════════════════════════\n";
echo "                    DONNÉES DU SEEDER (IDs 600-700)                     \n";
echo "═══════════════════════════════════════════════════════════════════════\n\n";

// ============================================================================
// UTILISATEURS (USERS + MEMBERS)
// ============================================================================
echo "┌─────────────────────────────────────────────────────────────────────┐\n";
echo "│                            UTILISATEURS                              │\n";
echo "└─────────────────────────────────────────────────────────────────────┘\n";

$users = $db->query("
    SELECT 
        u.id,
        u.first_name || ' ' || u.last_name as nom_complet,
        u.email,
        u.phone,
        u.address,
        u.birth_date,
        CASE WHEN m.adh_id IS NOT NULL THEN m.adh_license ELSE 'Pas de licence' END as licence,
        u.created_at
    FROM users u
    LEFT JOIN members m ON u.adh_id = m.adh_id
    WHERE u.id BETWEEN 600 AND 700
    ORDER BY u.id
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $user) {
    echo "\n┌─ ID: {$user['id']} ─────────────────────────────────────────────────────────\n";
    echo "│ Nom complet    : {$user['nom_complet']}\n";
    echo "│ Email          : {$user['email']}\n";
    echo "│ Téléphone      : {$user['phone']}\n";
    echo "│ Adresse        : {$user['address']}\n";
    echo "│ Date naissance : {$user['birth_date']}\n";
    echo "│ Licence        : {$user['licence']}\n";
    echo "└───────────────────────────────────────────────────────────────────────\n";
}

echo "\n✓ Total utilisateurs : " . count($users) . "\n\n";

// ============================================================================
// CLUBS
// ============================================================================
echo "\n┌─────────────────────────────────────────────────────────────────────┐\n";
echo "│                               CLUBS                                  │\n";
echo "└─────────────────────────────────────────────────────────────────────┘\n";

$clubs = $db->query("
    SELECT 
        c.club_id,
        c.club_name,
        c.club_street,
        c.club_city,
        c.club_postal_code,
        c.ffso_id,
        c.is_approved,
        u.first_name || ' ' || u.last_name as createur,
        c.created_at
    FROM clubs c
    LEFT JOIN users u ON c.created_by = u.id
    WHERE c.club_id BETWEEN 600 AND 700
    ORDER BY c.club_id
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($clubs as $club) {
    echo "\n┌─ ID: {$club['club_id']} ─────────────────────────────────────────────────────\n";
    echo "│ Nom            : {$club['club_name']}\n";
    echo "│ Adresse        : {$club['club_street']}, {$club['club_postal_code']} {$club['club_city']}\n";
    echo "│ FFSO ID        : {$club['ffso_id']}\n";
    echo "│ Approuvé       : " . ($club['is_approved'] ? 'Oui' : 'Non') . "\n";
    echo "│ Créé par       : {$club['createur']}\n";
    echo "└───────────────────────────────────────────────────────────────────────\n";
}

echo "\n✓ Total clubs : " . count($clubs) . "\n\n";

// ============================================================================
// RAIDS
// ============================================================================
echo "\n┌─────────────────────────────────────────────────────────────────────┐\n";
echo "│                               RAIDS                                  │\n";
echo "└─────────────────────────────────────────────────────────────────────┘\n";

$raids = $db->query("
    SELECT 
        r.raid_id,
        r.raid_name,
        r.raid_description,
        r.raid_date_start,
        r.raid_date_end,
        r.raid_contact,
        r.raid_street || ', ' || r.raid_postal_code || ' ' || r.raid_city as adresse,
        c.club_name as club,
        u.first_name || ' ' || u.last_name as responsable
    FROM raids r
    LEFT JOIN clubs c ON r.clu_id = c.club_id
    LEFT JOIN members m ON r.adh_id = m.adh_id
    LEFT JOIN users u ON m.adh_id = u.adh_id
    WHERE r.raid_id BETWEEN 600 AND 700
    ORDER BY r.raid_id
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($raids as $raid) {
    echo "\n┌─ ID: {$raid['raid_id']} ─────────────────────────────────────────────────────\n";
    echo "│ Nom            : {$raid['raid_name']}\n";
    echo "│ Description    : {$raid['raid_description']}\n";
    echo "│ Dates          : Du {$raid['raid_date_start']} au {$raid['raid_date_end']}\n";
    echo "│ Lieu           : {$raid['adresse']}\n";
    echo "│ Contact        : {$raid['raid_contact']}\n";
    echo "│ Club           : {$raid['club']}\n";
    echo "│ Responsable    : {$raid['responsable']}\n";
    echo "└───────────────────────────────────────────────────────────────────────\n";
}

echo "\n✓ Total raids : " . count($raids) . "\n\n";

// ============================================================================
// RACES (COURSES)
// ============================================================================
echo "\n┌─────────────────────────────────────────────────────────────────────┐\n";
echo "│                             COURSES                                  │\n";
echo "└─────────────────────────────────────────────────────────────────────┘\n";

$races = $db->query("
    SELECT 
        race.race_id,
        race.race_name,
        raid.raid_name as raid,
        race.race_date_start,
        race.race_date_end,
        race.race_duration_minutes,
        race.race_meal_price,
        u.first_name || ' ' || u.last_name as responsable
    FROM races race
    LEFT JOIN raids raid ON race.raid_id = raid.raid_id
    LEFT JOIN members m ON race.adh_id = m.adh_id
    LEFT JOIN users u ON m.adh_id = u.adh_id
    WHERE race.race_id BETWEEN 600 AND 700
    ORDER BY race.race_id
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($races as $race) {
    $duration_h = floor($race['race_duration_minutes'] / 60);
    $duration_m = $race['race_duration_minutes'] % 60;
    echo "\n┌─ ID: {$race['race_id']} ─────────────────────────────────────────────────────\n";
    echo "│ Nom            : {$race['race_name']}\n";
    echo "│ Raid           : {$race['raid']}\n";
    echo "│ Dates          : Du {$race['race_date_start']} au {$race['race_date_end']}\n";
    echo "│ Durée          : {$duration_h}h{$duration_m}min\n";
    echo "│ Prix repas     : {$race['race_meal_price']}€\n";
    echo "│ Responsable    : {$race['responsable']}\n";
    echo "└───────────────────────────────────────────────────────────────────────\n";
}

echo "\n✓ Total courses : " . count($races) . "\n\n";

// ============================================================================
// ÉQUIPES (TEAMS)
// ============================================================================
echo "\n┌─────────────────────────────────────────────────────────────────────┐\n";
echo "│                             ÉQUIPES                                  │\n";
echo "└─────────────────────────────────────────────────────────────────────┘\n";

$teams = $db->query("
    SELECT 
        t.equ_id,
        t.equ_name,
        u.first_name || ' ' || u.last_name as responsable,
        t.created_at
    FROM teams t
    LEFT JOIN users u ON t.adh_id = u.id
    WHERE t.equ_id BETWEEN 600 AND 700
    ORDER BY t.equ_id
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($teams as $team) {
    echo "\n┌─ ID: {$team['equ_id']} ─────────────────────────────────────────────────────\n";
    echo "│ Nom            : {$team['equ_name']}\n";
    echo "│ Responsable    : {$team['responsable']}\n";
    
    // Récupérer les participants
    $participants = $db->query("
        SELECT 
            u.first_name || ' ' || u.last_name as nom,
            CASE WHEN m.adh_license IS NOT NULL THEN m.adh_license ELSE 'Sans licence' END as licence
        FROM has_participate hp
        JOIN users u ON hp.id_users = u.id
        LEFT JOIN members m ON hp.adh_id = m.adh_id
        WHERE hp.equ_id = {$team['equ_id']}
        ORDER BY u.last_name
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "│ Participants   : " . count($participants) . " membre(s)\n";
    foreach ($participants as $i => $p) {
        echo "│   " . ($i + 1) . ". {$p['nom']} ({$p['licence']})\n";
    }
    echo "└───────────────────────────────────────────────────────────────────────\n";
}

echo "\n✓ Total équipes : " . count($teams) . "\n\n";

// ============================================================================
// TEMPS (TIME)
// ============================================================================
echo "\n┌─────────────────────────────────────────────────────────────────────┐\n";
echo "│                        TEMPS / RÉSULTATS                             │\n";
echo "└─────────────────────────────────────────────────────────────────────┘\n";

$times = $db->query("
    SELECT 
        t.user_id,
        u.first_name || ' ' || u.last_name as participant,
        r.race_name as course,
        t.time_hours || 'h ' || t.time_minutes || 'm ' || t.time_seconds || 's' as temps,
        t.time_total_seconds as total_sec,
        t.time_rank as rang
    FROM time t
    JOIN users u ON t.user_id = u.id
    JOIN races r ON t.race_id = r.race_id
    WHERE t.user_id BETWEEN 600 AND 700
    ORDER BY r.race_name, t.time_rank
")->fetchAll(PDO::FETCH_ASSOC);

$current_race = '';
foreach ($times as $time) {
    if ($current_race !== $time['course']) {
        $current_race = $time['course'];
        echo "\n┌─ Course: {$current_race} ──────────────────────────────────────────────\n";
    }
    echo "│ #{$time['rang']} - {$time['participant']}: {$time['temps']} ({$time['total_sec']}s)\n";
}
if (!empty($times)) {
    echo "└───────────────────────────────────────────────────────────────────────\n";
}

echo "\n✓ Total résultats : " . count($times) . "\n\n";

// ============================================================================
// LEADERBOARD ÉQUIPES
// ============================================================================
echo "\n┌─────────────────────────────────────────────────────────────────────┐\n";
echo "│                      CLASSEMENT ÉQUIPES                              │\n";
echo "└─────────────────────────────────────────────────────────────────────┘\n";

$leaderboard = $db->query("
    SELECT 
        t.equ_name as equipe,
        r.race_name as course,
        lt.member_count as nb_membres,
        lt.average_temps as temps_moyen_sec,
        lt.points,
        ac.nom as categorie,
        lt.average_malus as malus
    FROM leaderboard_teams lt
    JOIN teams t ON lt.equ_id = t.equ_id
    JOIN races r ON lt.race_id = r.race_id
    LEFT JOIN age_categories ac ON lt.age_category_id = ac.id
    WHERE lt.equ_id BETWEEN 600 AND 700
    ORDER BY r.race_name, lt.points DESC
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($leaderboard as $entry) {
    $hours = floor($entry['temps_moyen_sec'] / 3600);
    $minutes = floor(($entry['temps_moyen_sec'] % 3600) / 60);
    $seconds = $entry['temps_moyen_sec'] % 60;
    
    echo "\n┌─ {$entry['equipe']} ────────────────────────────────────────────────────────\n";
    echo "│ Course         : {$entry['course']}\n";
    echo "│ Catégorie      : {$entry['categorie']}\n";
    echo "│ Membres        : {$entry['nb_membres']}\n";
    echo "│ Temps moyen    : {$hours}h {$minutes}m {$seconds}s ({$entry['temps_moyen_sec']}s)\n";
    echo "│ Malus          : {$entry['malus']}s\n";
    echo "│ Points         : {$entry['points']}\n";
    echo "└───────────────────────────────────────────────────────────────────────\n";
}

echo "\n✓ Total entrées leaderboard : " . count($leaderboard) . "\n\n";

// ============================================================================
// STATISTIQUES GLOBALES
// ============================================================================
echo "\n╔═══════════════════════════════════════════════════════════════════════╗\n";
echo "║                        STATISTIQUES GLOBALES                          ║\n";
echo "╚═══════════════════════════════════════════════════════════════════════╝\n\n";

$stats = [
    'Utilisateurs' => $db->query("SELECT COUNT(*) FROM users WHERE id BETWEEN 600 AND 700")->fetchColumn(),
    'Membres avec licence' => $db->query("SELECT COUNT(*) FROM members WHERE adh_id BETWEEN 600 AND 700")->fetchColumn(),
    'Clubs' => $db->query("SELECT COUNT(*) FROM clubs WHERE club_id BETWEEN 600 AND 700")->fetchColumn(),
    'Raids' => $db->query("SELECT COUNT(*) FROM raids WHERE raid_id BETWEEN 600 AND 700")->fetchColumn(),
    'Courses' => $db->query("SELECT COUNT(*) FROM races WHERE race_id BETWEEN 600 AND 700")->fetchColumn(),
    'Équipes' => $db->query("SELECT COUNT(*) FROM teams WHERE equ_id BETWEEN 600 AND 700")->fetchColumn(),
    'Participations' => $db->query("SELECT COUNT(*) FROM has_participate WHERE equ_id BETWEEN 600 AND 700")->fetchColumn(),
    'Résultats (temps)' => $db->query("SELECT COUNT(*) FROM time WHERE user_id BETWEEN 600 AND 700")->fetchColumn(),
    'Classements équipes' => $db->query("SELECT COUNT(*) FROM leaderboard_teams WHERE equ_id BETWEEN 600 AND 700")->fetchColumn(),
];

foreach ($stats as $label => $count) {
    printf("  %-25s : %3d\n", $label, $count);
}

echo "\n═══════════════════════════════════════════════════════════════════════\n";
echo "                         FIN DU RAPPORT                                 \n";
echo "═══════════════════════════════════════════════════════════════════════\n";
