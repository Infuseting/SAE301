<?php

$db = new PDO('sqlite:' . __DIR__ . '/database/database.sqlite');

echo "═══════════════════════════════════════════════════════════════════════\n";
echo "                    RÔLES DES UTILISATEURS DEMO                         \n";
echo "═══════════════════════════════════════════════════════════════════════\n\n";

$users = $db->query("
    SELECT 
        u.id,
        u.first_name || ' ' || u.last_name as nom,
        u.email,
        GROUP_CONCAT(r.name, ', ') as roles,
        CASE WHEN u.adh_id IS NOT NULL THEN 'Oui' ELSE 'Non' END as a_licence
    FROM users u
    LEFT JOIN model_has_roles mhr ON u.id = mhr.model_id AND mhr.model_type = 'App\\Models\\User'
    LEFT JOIN roles r ON mhr.role_id = r.id
    WHERE u.id BETWEEN 600 AND 700
    GROUP BY u.id
    ORDER BY u.id
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $user) {
    $roles = $user['roles'] ?? 'Aucun rôle';
    echo "┌─ ID: {$user['id']} ─────────────────────────────────────────────────────────\n";
    echo "│ Nom      : {$user['nom']}\n";
    echo "│ Email    : {$user['email']}\n";
    echo "│ Licence  : {$user['a_licence']}\n";
    echo "│ Rôles    : $roles\n";
    echo "└───────────────────────────────────────────────────────────────────────\n\n";
}

echo "\n═══════════════════════════════════════════════════════════════════════\n";
echo "                    RÉSUMÉ PAR RÔLE                                     \n";
echo "═══════════════════════════════════════════════════════════════════════\n\n";

$rolesSummary = $db->query("
    SELECT 
        r.name as role,
        COUNT(*) as count,
        GROUP_CONCAT(u.first_name || ' ' || u.last_name, ', ') as users
    FROM model_has_roles mhr
    JOIN roles r ON mhr.role_id = r.id
    JOIN users u ON mhr.model_id = u.id AND mhr.model_type = 'App\\Models\\User'
    WHERE u.id BETWEEN 600 AND 700
    GROUP BY r.name
    ORDER BY r.name
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($rolesSummary as $summary) {
    echo "Rôle: {$summary['role']} ({$summary['count']} utilisateur(s))\n";
    echo "  → {$summary['users']}\n\n";
}

echo "═══════════════════════════════════════════════════════════════════════\n";
