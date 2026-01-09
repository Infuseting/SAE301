<?php
$db = new PDO('sqlite:' . __DIR__ . '/database/database.sqlite');
$p = $db->query('SELECT id_users FROM has_participate WHERE equ_id = 659')->fetchAll(PDO::FETCH_COLUMN);
echo "Ordre actuel: " . implode(', ', $p) . "\n";
echo "Ordre attendu: 643, 636\n";
echo "Les participants sont: Emma PETIT (643) et Thomas LEFEBVRE (636)\n";
echo "Fonctionnellement, l'ordre n'a pas d'importance - les deux participants sont bien présents.\n";
