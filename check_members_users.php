<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$db = new PDO('sqlite:' . __DIR__ . '/database/database.sqlite');

echo "=== MEMBERS (630-648) ===\n";
$members = $db->query('SELECT adh_id, adh_license FROM members WHERE adh_id BETWEEN 630 AND 648 ORDER BY adh_id')->fetchAll(PDO::FETCH_ASSOC);
if (empty($members)) {
    echo "No members found!\n";
} else {
    foreach ($members as $m) {
        echo "Member ID: {$m['adh_id']}, License: {$m['adh_license']}\n";
    }
}

echo "\n=== USERS (630-648) ===\n";
$users = $db->query('SELECT id, first_name, last_name, adh_id FROM users WHERE id BETWEEN 630 AND 648 ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
if (empty($users)) {
    echo "No users found!\n";
} else {
    foreach ($users as $u) {
        echo "User ID: {$u['id']}, Name: {$u['first_name']} {$u['last_name']}, adh_id: " . ($u['adh_id'] ?? 'NULL') . "\n";
    }
}
