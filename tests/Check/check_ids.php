<?php

$db = new PDO('sqlite:' . __DIR__ . '/database/database.sqlite');

echo "=== TEAMS ADH_ID ===\n";
$teams = $db->query('SELECT equ_id, equ_name, adh_id FROM teams WHERE equ_id BETWEEN 656 AND 659 ORDER BY equ_id')->fetchAll(PDO::FETCH_ASSOC);
print_r($teams);

echo "\n=== USERS ===\n";
$users = $db->query('SELECT id, first_name, last_name FROM users WHERE id IN (635, 648, 641, 644) ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
print_r($users);
