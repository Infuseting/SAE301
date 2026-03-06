<?php

$db = new PDO('sqlite:' . __DIR__ . '/database/database.sqlite');

echo "=== FOREIGN KEYS IN 'races' TABLE ===\n";
$fks = $db->query('PRAGMA foreign_key_list(races)')->fetchAll(PDO::FETCH_ASSOC);
print_r($fks);
