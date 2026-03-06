<?php

$db = new PDO('sqlite:' . __DIR__ . '/database/database.sqlite');

echo "═══════════════════════════════════════════════════════════════════════\n";
echo "                    MEMBRES DES CLUBS                                    \n";
echo "═══════════════════════════════════════════════════════════════════════\n\n";

$result = $db->query("
    SELECT 
        c.club_name, 
        u.first_name || ' ' || u.last_name as membre, 
        cu.role, 
        cu.status 
    FROM club_user cu 
    JOIN clubs c ON cu.club_id = c.club_id 
    JOIN users u ON cu.user_id = u.id 
    WHERE c.club_id BETWEEN 600 AND 700
    ORDER BY c.club_name, cu.role DESC
")->fetchAll(PDO::FETCH_ASSOC);

if (empty($result)) {
    echo "Aucun membre trouvé dans les clubs.\n\n";
} else {
    $currentClub = '';
    foreach ($result as $row) {
        if ($currentClub !== $row['club_name']) {
            if ($currentClub !== '') echo "\n";
            $currentClub = $row['club_name'];
            echo "┌─ {$currentClub} ──────────────────────────────────────────────────\n";
        }
        $roleLabel = $row['role'] === 'manager' ? 'Gestionnaire' : 'Membre';
        $statusLabel = $row['status'] === 'approved' ? 'Approuvé' : $row['status'];
        echo "│ • {$row['membre']} ({$roleLabel}) - {$statusLabel}\n";
    }
    echo "└───────────────────────────────────────────────────────────────────────\n\n";
    echo "✓ Total : " . count($result) . " membre(s)\n";
}

echo "\n═══════════════════════════════════════════════════════════════════════\n";
