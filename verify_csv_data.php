<?php

$db = new PDO('sqlite:' . __DIR__ . '/database/database.sqlite');

echo "=== VÉRIFICATION DES UTILISATEURS (630-648) ===\n\n";

$expectedData = [
    630 => ['name' => 'MARTIN', 'firstname' => 'Julien', 'licence' => '77001234', 'email' => 'julien.martin@test.fr'],
    631 => ['name' => 'DUMONT', 'firstname' => 'Clara', 'licence' => '25004567', 'email' => 'c.dumont@email.fr'],
    632 => ['name' => 'PETIT', 'firstname' => 'Antoine', 'licence' => '2025-T1L1F3', 'email' => 'antoine.petit@gmail.com'],
    633 => ['name' => 'MARVELI', 'firstname' => 'Sandra', 'licence' => '64005678', 'email' => 'sandra.m60@wanadoo.fr'],
    634 => ['name' => 'BERNARD', 'firstname' => 'Lucas', 'licence' => '91002345', 'email' => 'lucas.bernard@test.fr'],
    635 => ['name' => 'DUPONT', 'firstname' => 'Claire', 'licence' => '1204558', 'email' => 'claire.dupont@test.fr'],
    636 => ['name' => 'LEFEBVRE', 'firstname' => 'Thomas', 'licence' => '2298741', 'email' => 't.lefebvre@orange.fr'],
    637 => ['name' => 'MOREAU', 'firstname' => 'Sophie', 'licence' => '6003214', 'email' => 'sophie.moreau@test.fr'],
    638 => ['name' => 'LEROY', 'firstname' => 'Thomas', 'licence' => '', 'email' => 'thomas.leroy@test.fr'], // PAS DE LICENCE
    639 => ['name' => 'GARNIER', 'firstname' => 'Julie', 'licence' => '', 'email' => 'julie.garnier@outlook.com'], // PAS DE LICENCE
    640 => ['name' => 'ROUSSEAU', 'firstname' => 'Marc', 'licence' => '6700548', 'email' => 'm.rousseau@sfr.fr'],
    641 => ['name' => 'FONTAINE', 'firstname' => 'Hugo', 'licence' => '91006754', 'email' => 'hugo.fontaine@test.fr'],
    642 => ['name' => 'CARON', 'firstname' => 'Léa', 'licence' => '', 'email' => 'lea.caron@test.fr'], // PAS DE LICENCE
    643 => ['name' => 'PETIT', 'firstname' => 'Emma', 'licence' => '77009876', 'email' => 'emma.petit@test.fr'],
    645 => ['name' => 'DORBEC', 'firstname' => 'Paul', 'licence' => '23456789', 'email' => 'paul.dorbec@unicaen.fr'],
    646 => ['name' => 'JACQUIER', 'firstname' => 'Yohann', 'licence' => '', 'email' => 'yohann.jacquier@unicaen.fr'], // PAS DE LICENCE
    647 => ['name' => 'DELHOUMI', 'firstname' => 'Sylvian', 'licence' => '2025-D2S1I3', 'email' => 'sylvian.delhoumi@unicaen.fr'],
    648 => ['name' => 'ANNE', 'firstname' => 'Jean-François', 'licence' => '56723478', 'email' => 'jeanfrancois.anne@unicaen.fr'],
];

$users = $db->query('SELECT id, first_name, last_name, email, adh_id FROM users WHERE id BETWEEN 630 AND 648 ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$members = $db->query('SELECT adh_id, adh_license FROM members WHERE adh_id BETWEEN 630 AND 648')->fetchAll(PDO::FETCH_ASSOC);
$membersMap = [];
foreach ($members as $m) {
    $membersMap[$m['adh_id']] = $m['adh_license'];
}

$errors = 0;
foreach ($users as $user) {
    $id = $user['id'];
    if (!isset($expectedData[$id])) {
        echo "❌ ID $id: N'existe pas dans le CSV\n";
        $errors++;
        continue;
    }
    
    $expected = $expectedData[$id];
    $actualLicence = isset($membersMap[$id]) ? $membersMap[$id] : '';
    
    $issues = [];
    if ($user['last_name'] !== $expected['name']) $issues[] = "Nom: {$user['last_name']} au lieu de {$expected['name']}";
    if ($user['first_name'] !== $expected['firstname']) $issues[] = "Prénom: {$user['first_name']} au lieu de {$expected['firstname']}";
    if ($user['email'] !== $expected['email']) $issues[] = "Email: {$user['email']} au lieu de {$expected['email']}";
    if ($actualLicence !== $expected['licence']) $issues[] = "Licence: '$actualLicence' au lieu de '{$expected['licence']}'";
    
    if (!empty($issues)) {
        echo "❌ ID $id ({$user['first_name']} {$user['last_name']}):\n";
        foreach ($issues as $issue) {
            echo "   - $issue\n";
        }
        $errors++;
    } else {
        echo "✅ ID $id ({$user['first_name']} {$user['last_name']})\n";
    }
}

echo "\n=== VÉRIFICATION DES ÉQUIPES COURSE ELFE (656-659) ===\n\n";

$expectedTeams = [
    656 => ['responsable' => 635, 'responsable_name' => 'DUPONT Claire', 'participants' => [635, 638], 'participants_names' => 'Dupont Claire + Leroy Thomas'],
    657 => ['responsable' => 648, 'responsable_name' => 'ANNE Jean-François', 'participants' => [633, 634], 'participants_names' => 'Marveli Sandra + Bernard Lucas'],
    658 => ['responsable' => 641, 'responsable_name' => 'FONTAINE Hugo', 'participants' => [632, 641], 'participants_names' => 'Petit Antoine + Fontaine Hugo'],
    659 => ['responsable' => 643, 'responsable_name' => 'PETIT Emma', 'participants' => [643, 636], 'participants_names' => 'PETIT Emma + LEFEBVRE Thomas'],
];

$teams = $db->query('SELECT equ_id, equ_name, adh_id FROM teams WHERE equ_id BETWEEN 656 AND 659 ORDER BY equ_id')->fetchAll(PDO::FETCH_ASSOC);

foreach ($teams as $team) {
    $equId = $team['equ_id'];
    $expected = $expectedTeams[$equId];
    
    $participants = $db->query("SELECT id_users FROM has_participate WHERE equ_id = $equId ORDER BY id_users")->fetchAll(PDO::FETCH_COLUMN);
    $expectedParticipants = $expected['participants'];
    sort($expectedParticipants); // Sort pour comparer sans tenir compte de l'ordre
    
    $responsableOK = $team['adh_id'] == $expected['responsable'];
    $participantsOK = $participants == $expectedParticipants;
    
    if ($responsableOK && $participantsOK) {
        echo "✅ Équipe {$team['equ_name']} (ID: $equId)\n";
        echo "   Responsable: {$expected['responsable_name']}\n";
        echo "   Participants: {$expected['participants_names']}\n";
    } else {
        echo "❌ Équipe {$team['equ_name']} (ID: $equId)\n";
        if (!$responsableOK) {
            echo "   ⚠️  Responsable: {$team['adh_id']} au lieu de {$expected['responsable']}\n";
        }
        if (!$participantsOK) {
            echo "   ⚠️  Participants: " . implode(', ', $participants) . " au lieu de " . implode(', ', $expectedParticipants) . "\n";
        }
        $errors++;
    }
}

echo "\n=== VÉRIFICATION DES ÉQUIPES COURSE LUTIN (653-655) ===\n\n";

$expectedLutinTeams = [
    653 => ['responsable' => 648, 'responsable_name' => 'ANNE Jean-François', 'participants' => [633, 647], 'participants_names' => 'Marveli Sandra + Delhoumi Sylvian'],
    654 => ['responsable' => 634, 'responsable_name' => 'BERNARD Lucas', 'participants' => [634, 637], 'participants_names' => 'Bernard Lucas + Moreau Sophie'],
    655 => ['responsable' => 645, 'responsable_name' => 'DORBEC Paul', 'participants' => [639, 645], 'participants_names' => 'Garnier Julie + Dorbec Paul'],
];

$lutinTeams = $db->query('SELECT equ_id, equ_name, adh_id FROM teams WHERE equ_id BETWEEN 653 AND 655 ORDER BY equ_id')->fetchAll(PDO::FETCH_ASSOC);

foreach ($lutinTeams as $team) {
    $equId = $team['equ_id'];
    $expected = $expectedLutinTeams[$equId];
    
    $participants = $db->query("SELECT id_users FROM has_participate WHERE equ_id = $equId ORDER BY id_users")->fetchAll(PDO::FETCH_COLUMN);
    $expectedParticipants = $expected['participants'];
    sort($expectedParticipants); // Sort pour comparer sans tenir compte de l'ordre
    
    $responsableOK = $team['adh_id'] == $expected['responsable'];
    $participantsOK = $participants == $expectedParticipants;
    
    if ($responsableOK && $participantsOK) {
        echo "✅ Équipe {$team['equ_name']} (ID: $equId)\n";
        echo "   Responsable: {$expected['responsable_name']}\n";
        echo "   Participants: {$expected['participants_names']}\n";
    } else {
        echo "❌ Équipe {$team['equ_name']} (ID: $equId)\n";
        if (!$responsableOK) {
            echo "   ⚠️  Responsable: {$team['adh_id']} au lieu de {$expected['responsable']}\n";
        }
        if (!$participantsOK) {
            echo "   ⚠️  Participants: " . implode(', ', $participants) . " au lieu de " . implode(', ', $expectedParticipants) . "\n";
        }
        $errors++;
    }
}

echo "\n";
if ($errors > 0) {
    echo "❌ TOTAL: $errors erreur(s) trouvée(s)\n";
} else {
    echo "✅ Toutes les données sont conformes aux CSV!\n";
}
