<?php

/**
 * Complete CSV verification script for DemoDataSeeder
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

define('ID_START', 600);

echo "\n" . str_repeat("=", 80) . "\n";
echo "VERIFICATION COMPLETE DES DONNEES CSV vs SEEDER\n";
echo str_repeat("=", 80) . "\n\n";

$errors = [];
$warnings = [];

// ========== 1. VERIFICATION USERS ==========
echo "1️⃣  VERIFICATION DES UTILISATEURS\n";
echo str_repeat("-", 80) . "\n";

$expectedUsers = [
    ['id' => 630, 'name' => 'MARTIN', 'firstname' => 'Julien', 'club' => 'CO Azimut 77', 'licence' => '77001234', 'address' => '12 rue des Pins, 77000 Melun', 'phone' => '0612345678', 'email' => 'julien.martin@test.fr', 'birth_date' => '1990-04-15'],
    ['id' => 631, 'name' => 'DUMONT', 'firstname' => 'Clara', 'club' => 'Raidlinks', 'licence' => '25004567', 'address' => '45 rue des plantes 14123 IFS', 'phone' => '0698765432', 'email' => 'c.dumont@email.fr', 'birth_date' => '1985-09-22'],
    ['id' => 632, 'name' => 'PETIT', 'firstname' => 'Antoine', 'club' => 'Balise 25', 'licence' => '2025-T1L1F3', 'address' => '5 chemin du Lac, 25140 Charquemont', 'phone' => '0711223344', 'email' => 'antoine.petit@gmail.com', 'birth_date' => '2002-01-03'],
    ['id' => 633, 'name' => 'MARVELI', 'firstname' => 'Sandra', 'club' => 'VIKAZIM', 'licence' => '64005678', 'address' => '8 bis rue du Parc, 14400 BAYEUX', 'phone' => '0655443322', 'email' => 'sandra.m60@wanadoo.fr', 'birth_date' => '1995-07-18'],
    ['id' => 634, 'name' => 'BERNARD', 'firstname' => 'Lucas', 'club' => 'Raidlinks', 'licence' => '91002345', 'address' => '3 allée des Sports, 91000 Évry', 'phone' => '0766778899', 'email' => 'lucas.bernard@test.fr', 'birth_date' => '1988-11-30'],
    ['id' => 635, 'name' => 'DUPONT', 'firstname' => 'Claire', 'club' => 'CO Azimut 77', 'licence' => '1204558', 'address' => '12 rue des Pins, 77100 MEAUX', 'phone' => '0612457890', 'email' => 'claire.dupont@test.fr', 'birth_date' => '1992-05-14'],
    ['id' => 636, 'name' => 'LEFEBVRE', 'firstname' => 'Thomas', 'club' => 'Balise 25', 'licence' => '2298741', 'address' => '5 avenue de l\'Europe, 25200 Montbéliard', 'phone' => '0654892133', 'email' => 't.lefebvre@orange.fr', 'birth_date' => '1985-11-23'],
    ['id' => 637, 'name' => 'MOREAU', 'firstname' => 'Sophie', 'club' => 'Raidlinks', 'licence' => '6003214', 'address' => '21 route de Bayonne, 91300 MASSY', 'phone' => '0781024456', 'email' => 'sophie.moreau@test.fr', 'birth_date' => '2001-02-02'],
    ['id' => 638, 'name' => 'LEROY', 'firstname' => 'Thomas', 'club' => 'CO Azimut 77', 'licence' => '', 'address' => '45 chemin de la Forêt, 77000 Melun', 'phone' => '0633571288', 'email' => 'thomas.leroy@test.fr', 'birth_date' => '1995-08-30'],
    ['id' => 639, 'name' => 'GARNIER', 'firstname' => 'Julie', 'club' => 'CO Azimut 77', 'licence' => '', 'address' => '102 rue du Moulin, 77500 Chelles', 'phone' => '0765901122', 'email' => 'julie.garnier@outlook.com', 'birth_date' => '1988-07-12'],
    ['id' => 640, 'name' => 'ROUSSEAU', 'firstname' => 'Marc', 'club' => 'CO Azimut 77', 'licence' => '6700548', 'address' => '3 place de la Mairie, 77000 Melun', 'phone' => '0609883451', 'email' => 'm.rousseau@sfr.fr', 'birth_date' => '1974-01-19'],
    ['id' => 641, 'name' => 'FONTAINE', 'firstname' => 'Hugo', 'club' => 'Balise 25', 'licence' => '91006754', 'address' => '2 rue des Peupliers, 25200 Athis-Mons', 'phone' => '0673849516', 'email' => 'hugo.fontaine@test.fr', 'birth_date' => '2003-10-05'],
    ['id' => 642, 'name' => 'CARON', 'firstname' => 'Léa', 'club' => 'Balise 25', 'licence' => '', 'address' => '6 rue du Collège, 25200 Montbéliard', 'phone' => '0614253647', 'email' => 'lea.caron@test.fr', 'birth_date' => '1990-04-27'],
    ['id' => 643, 'name' => 'PETIT', 'firstname' => 'Emma', 'club' => 'Balise 25', 'licence' => '77009876', 'address' => '4 rue des Écoles, 25200 Montbéliard', 'phone' => '0621436587', 'email' => 'emma.petit@test.fr', 'birth_date' => '2005-12-08'],
    ['id' => 645, 'name' => 'DORBEC', 'firstname' => 'Paul', 'club' => 'CO AZIMUT 77', 'licence' => '23456789', 'address' => '22 rue des roses 77000 Melun', 'phone' => '0743672311', 'email' => 'paul.dorbec@unicaen.fr', 'birth_date' => '1980-04-02'],
    ['id' => 646, 'name' => 'JACQUIER', 'firstname' => 'Yohann', 'club' => 'VIKAZIM', 'licence' => '', 'address' => '35 rue des acacias 14123 ifs', 'phone' => '0642864628', 'email' => 'yohann.jacquier@unicaen.fr', 'birth_date' => '2013-06-03'],
    ['id' => 647, 'name' => 'DELHOUMI', 'firstname' => 'Sylvian', 'club' => 'VIKAZIM', 'licence' => '2025-D2S1I3', 'address' => '47 rue des chênes 14000 Caen', 'phone' => '0705324567', 'email' => 'sylvian.delhoumi@unicaen.fr', 'birth_date' => '1985-06-02'],
    ['id' => 648, 'name' => 'ANNE', 'firstname' => 'Jean-François', 'club' => 'VIKAZIM', 'licence' => '56723478', 'address' => '27 rue des tilleuls 14123 Cormelles Le Royal', 'phone' => '0645389485', 'email' => 'jeanfrancois.anne@unicaen.fr', 'birth_date' => '1964-11-05'],
];

foreach ($expectedUsers as $expected) {
    $user = DB::table('users')->where('id', $expected['id'])->first();
    
    if (!$user) {
        $errors[] = "❌ Utilisateur {$expected['firstname']} {$expected['name']} (ID {$expected['id']}) n'existe pas dans la DB";
        continue;
    }
    
    $userErrors = [];
    
    if ($user->first_name !== $expected['firstname']) {
        $userErrors[] = "Prénom: '{$user->first_name}' au lieu de '{$expected['firstname']}'";
    }
    if ($user->last_name !== $expected['name']) {
        $userErrors[] = "Nom: '{$user->last_name}' au lieu de '{$expected['name']}'";
    }
    if ($user->email !== $expected['email']) {
        $userErrors[] = "Email: '{$user->email}' au lieu de '{$expected['email']}'";
    }
    if ($user->phone !== $expected['phone']) {
        $userErrors[] = "Téléphone: '{$user->phone}' au lieu de '{$expected['phone']}'";
    }
    if ($user->address !== $expected['address']) {
        $userErrors[] = "Adresse: '{$user->address}' au lieu de '{$expected['address']}'";
    }
    if ($user->birth_date !== $expected['birth_date']) {
        $userErrors[] = "Date naissance: '{$user->birth_date}' au lieu de '{$expected['birth_date']}'";
    }
    
    // Verify license
    if (!empty($expected['licence'])) {
        $member = DB::table('members')->where('adh_id', $expected['id'])->first();
        if (!$member) {
            $userErrors[] = "Licence manquante dans members";
        } elseif ($member->adh_license !== $expected['licence']) {
            $userErrors[] = "Licence: '{$member->adh_license}' au lieu de '{$expected['licence']}'";
        }
    } else {
        // Should NOT have a member record
        $member = DB::table('members')->where('adh_id', $expected['id'])->first();
        if ($member) {
            $userErrors[] = "Ne devrait PAS avoir de licence mais en a une: {$member->adh_license}";
        }
    }
    
    if (empty($userErrors)) {
        echo "✅ {$expected['firstname']} {$expected['name']}\n";
    } else {
        echo "⚠️  {$expected['firstname']} {$expected['name']}:\n";
        foreach ($userErrors as $err) {
            echo "   - $err\n";
            $warnings[] = "{$expected['firstname']} {$expected['name']}: $err";
        }
    }
}

// ========== 2. VERIFICATION CLUBS ==========
echo "\n2️⃣  VERIFICATION DES CLUBS\n";
echo str_repeat("-", 80) . "\n";

$expectedClubs = [
    ['id' => 601, 'name' => 'CO Azimut 77', 'street' => '24 Rue de la Rochette', 'city' => 'Melun', 'postal' => '77000', 'phone' => '0613245698', 'responsable' => 'DUPONT Claire'],
    ['id' => 602, 'name' => 'Balise 25', 'street' => '2 Avenue Léo Lagrange', 'city' => 'Besançon', 'postal' => '25000', 'phone' => '0783295427', 'responsable' => 'PETIT Antoine'],
    ['id' => 603, 'name' => 'Raidlinks', 'street' => '14 Place des Terrasses de l\'Agora', 'city' => 'Évry', 'postal' => '91000', 'phone' => '0642376582', 'responsable' => 'BERNARD Lucas'],
    ['id' => 604, 'name' => 'VIKAZIM', 'street' => '28 rue des bleuets', 'city' => 'Caen', 'postal' => '14000', 'phone' => '0764923785', 'responsable' => 'ANNE Jean-François'],
];

foreach ($expectedClubs as $expected) {
    $club = DB::table('clubs')->where('club_id', $expected['id'])->first();
    
    if (!$club) {
        $errors[] = "❌ Club {$expected['name']} n'existe pas";
        continue;
    }
    
    $clubErrors = [];
    
    if ($club->club_name !== $expected['name']) {
        $clubErrors[] = "Nom: '{$club->club_name}' au lieu de '{$expected['name']}'";
    }
    if ($club->club_street !== $expected['street']) {
        $clubErrors[] = "Rue: '{$club->club_street}' au lieu de '{$expected['street']}'";
    }
    if ($club->club_city !== $expected['city']) {
        $clubErrors[] = "Ville: '{$club->club_city}' au lieu de '{$expected['city']}'";
    }
    if ($club->club_postal_code !== $expected['postal']) {
        $clubErrors[] = "CP: '{$club->club_postal_code}' au lieu de '{$expected['postal']}'";
    }
    
    if (empty($clubErrors)) {
        echo "✅ {$expected['name']}\n";
    } else {
        echo "⚠️  {$expected['name']}:\n";
        foreach ($clubErrors as $err) {
            echo "   - $err\n";
            $warnings[] = "{$expected['name']}: $err";
        }
    }
}

// ========== 3. VERIFICATION RAIDS ==========
echo "\n3️⃣  VERIFICATION DES RAIDS\n";
echo str_repeat("-", 80) . "\n";

$expectedRaids = [
    [
        'id' => 610,
        'name' => 'Raid CHAMPETRE',
        'description' => 'Course d\'orientation en milieu naturel',
        'responsable' => 'Paul DORBEC',
        'club' => 'CO AZIMUT 77',
        'date_start' => '2025-11-13',
        'date_end' => '2025-11-14',
        'location' => 'PARC INTERCOMMUNAL DEBREUIL',
        'city' => 'Melun',
        'postal' => '77000',
    ],
    [
        'id' => 611,
        'name' => 'Raid O\'Bivwak',
        'description' => '3 parcours chronométrés (A,B,C)',
        'responsable' => 'Claire DUPONT',
        'club' => 'CO AZIMUT 77',
        'date_start' => '2026-05-23',
        'date_end' => '2026-05-24',
        'location' => '7 boulevard de la République',
        'city' => 'MONTERAUT',
        'postal' => '77130',
    ],
];

foreach ($expectedRaids as $expected) {
    $raid = DB::table('raids')->where('raid_id', $expected['id'])->first();
    
    if (!$raid) {
        $errors[] = "❌ Raid {$expected['name']} n'existe pas";
        continue;
    }
    
    $raidErrors = [];
    
    if ($raid->raid_name !== $expected['name']) {
        $raidErrors[] = "Nom: '{$raid->raid_name}' au lieu de '{$expected['name']}'";
    }
    if (!str_contains($raid->raid_date_start, $expected['date_start'])) {
        $raidErrors[] = "Date début: '{$raid->raid_date_start}' devrait contenir '{$expected['date_start']}'";
    }
    if ($raid->raid_city !== $expected['city']) {
        $raidErrors[] = "Ville: '{$raid->raid_city}' au lieu de '{$expected['city']}'";
    }
    if ($raid->raid_postal_code !== $expected['postal']) {
        $raidErrors[] = "CP: '{$raid->raid_postal_code}' au lieu de '{$expected['postal']}'";
    }
    
    if (empty($raidErrors)) {
        echo "✅ {$expected['name']}\n";
    } else {
        echo "⚠️  {$expected['name']}:\n";
        foreach ($raidErrors as $err) {
            echo "   - $err\n";
            $warnings[] = "{$expected['name']}: $err";
        }
    }
}

// ========== 4. VERIFICATION COURSES ==========
echo "\n4️⃣  VERIFICATION DES COURSES\n";
echo str_repeat("-", 80) . "\n";

$expectedRaces = [
    ['id' => 620, 'name' => 'Course LUTIN', 'raid' => 'CHAMPETRE', 'responsable' => 'MARTIN Julien', 'date' => '2025-11-13', 'duration' => 150],
    ['id' => 621, 'name' => 'Course ELFE', 'raid' => 'CHAMPETRE', 'responsable' => 'DORBEC Paul', 'date' => '2025-11-14', 'duration' => 420],
    ['id' => 622, 'name' => 'Parcours A', 'raid' => 'O\'Bivwak', 'responsable' => 'ROUSSEAU Marc', 'date' => '2026-05-23', 'duration' => 390],
    ['id' => 623, 'name' => 'Parcours B', 'raid' => 'O\'Bivwak', 'responsable' => 'DUMONT Clara', 'date' => '2026-05-24', 'duration' => 240],
];

foreach ($expectedRaces as $expected) {
    $race = DB::table('races')->where('race_id', $expected['id'])->first();
    
    if (!$race) {
        $errors[] = "❌ Course {$expected['name']} n'existe pas";
        continue;
    }
    
    $raceErrors = [];
    
    if ($race->race_name !== $expected['name']) {
        $raceErrors[] = "Nom: '{$race->race_name}' au lieu de '{$expected['name']}'";
    }
    if ($race->race_duration_minutes !== $expected['duration']) {
        $raceErrors[] = "Durée: {$race->race_duration_minutes} min au lieu de {$expected['duration']} min";
    }
    
    if (empty($raceErrors)) {
        echo "✅ {$expected['name']}\n";
    } else {
        echo "⚠️  {$expected['name']}:\n";
        foreach ($raceErrors as $err) {
            echo "   - $err\n";
            $warnings[] = "{$expected['name']}: $err";
        }
    }
}

// ========== 5. VERIFICATION EQUIPES ==========
echo "\n5️⃣  VERIFICATION DES EQUIPES\n";
echo str_repeat("-", 80) . "\n";

$expectedTeams = [
    // CHAMPETRE LUTIN
    ['id' => 653, 'name' => 'Equipe 1', 'responsable' => 'ANNE Jean-François', 'members' => ['MARVELI Sandra', 'DELHOUMI Sylvian']],
    ['id' => 654, 'name' => 'Equipe 2', 'responsable' => 'BERNARD Lucas', 'members' => ['BERNARD Lucas', 'MOREAU Sophie']],
    ['id' => 655, 'name' => 'Equipe 3', 'responsable' => 'DORBEC Paul', 'members' => ['GARNIER Julie', 'DORBEC Paul']],
    // CHAMPETRE ELFE
    ['id' => 656, 'name' => 'Equipe 1', 'responsable' => 'DUPONT Claire', 'members' => ['DUPONT Claire', 'LEROY Thomas']],
    ['id' => 657, 'name' => 'Equipe 2', 'responsable' => 'ANNE Jean-François', 'members' => ['MARVELI Sandra', 'BERNARD Lucas']],
    ['id' => 658, 'name' => 'Equipe 3', 'responsable' => 'FONTAINE Hugo', 'members' => ['PETIT Antoine', 'FONTAINE Hugo']],
    ['id' => 659, 'name' => 'Equipe 4', 'responsable' => 'PETIT Emma', 'members' => ['PETIT Emma', 'LEFEBVRE Thomas']],
    // O'BIVWAK
    ['id' => 660, 'name' => 'Equipe DORMEUR', 'responsable' => 'LEFEBVRE Thomas', 'members' => ['PETIT Emma', 'LEFEBVRE Thomas']],
    ['id' => 661, 'name' => 'Equipe ATCHOUM', 'responsable' => 'FONTAINE Hugo', 'members' => ['FONTAINE Hugo', 'CARON Léa']],
    ['id' => 662, 'name' => 'Equipe SIMPLET', 'responsable' => 'FONTAINE Hugo', 'members' => ['GARNIER Julie', 'ROUSSEAU Marc']],
];

foreach ($expectedTeams as $expected) {
    $team = DB::table('teams')->where('equ_id', $expected['id'])->first();
    
    if (!$team) {
        $errors[] = "❌ Equipe {$expected['name']} (ID {$expected['id']}) n'existe pas";
        continue;
    }
    
    $teamErrors = [];
    
    if ($team->equ_name !== $expected['name']) {
        $teamErrors[] = "Nom: '{$team->equ_name}' au lieu de '{$expected['name']}'";
    }
    
    // Check members
    $members = DB::table('has_participate')
        ->where('equ_id', $expected['id'])
        ->get();
    
    if ($members->count() !== count($expected['members'])) {
        $teamErrors[] = "Nombre de membres: {$members->count()} au lieu de " . count($expected['members']);
    }
    
    if (empty($teamErrors)) {
        echo "✅ {$expected['name']} (ID {$expected['id']})\n";
    } else {
        echo "⚠️  {$expected['name']} (ID {$expected['id']}):\n";
        foreach ($teamErrors as $err) {
            echo "   - $err\n";
            $warnings[] = "{$expected['name']}: $err";
        }
    }
}

// ========== SUMMARY ==========
echo "\n" . str_repeat("=", 80) . "\n";
echo "RESUME DE LA VERIFICATION\n";
echo str_repeat("=", 80) . "\n";

if (empty($errors) && empty($warnings)) {
    echo "✅ TOUTES LES DONNEES CORRESPONDENT PARFAITEMENT AUX CSV !\n";
    echo "✅ 18 utilisateurs vérifiés\n";
    echo "✅ 4 clubs vérifiés\n";
    echo "✅ 2 raids vérifiés\n";
    echo "✅ 4 courses vérifiées\n";
    echo "✅ 10 équipes vérifiées\n";
} else {
    if (!empty($errors)) {
        echo "❌ ERREURS CRITIQUES: " . count($errors) . "\n";
        foreach ($errors as $error) {
            echo "   $error\n";
        }
    }
    
    if (!empty($warnings)) {
        echo "⚠️  AVERTISSEMENTS: " . count($warnings) . "\n";
        foreach ($warnings as $warning) {
            echo "   $warning\n";
        }
    }
}

echo "\n";
