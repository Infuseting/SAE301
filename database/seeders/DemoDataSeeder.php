<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder for demo data with reserved ID range 600-700.
 * Updated: Julie Garnier treated as non-licensed participant.
 */
class DemoDataSeeder extends Seeder
{
    private const ID_START = 600;
    private const ID_END = 700;

    public function run(): void
    {
        $this->command->info('Starting demo data seeding (ID range: 600-700)...');

        $this->cleanExistingDemoData();
        $this->createParamTables(); // Helper method for params

        // Parameters
        $paramRunnerId = 1;
        $paramTeamId = 1;
        $paramTypeId = 1;
        $registrationPeriodId = 1;

        // 1. Users & Members
        $userIds = $this->createDemoUsers();
        $adminUserId = $userIds[0]; 
        
        // 2. Clubs
        $clubIds = $this->createDemoClubs($adminUserId);
        
        // 3. Raids & Races
        $raceIds = $this->createDemoRaidsAndRaces($clubIds, $registrationPeriodId, $paramRunnerId, $paramTeamId, $paramTypeId);
        
        // 4. Teams
        $teamIds = $this->createDemoTeams();
        
        // 5. Link Users -> Teams
        $this->linkUsersToTeams($userIds, $teamIds);
        
        // 6. Times/Results (Updated logic to include non-licensed users)
        $this->createDemoTimes($raceIds);

        // 7. Leaderboard
        $this->createLeaderboardTeams();

        // 8. Assign Roles based on responsibilities
        $this->assignUserRoles();

        $this->command->info('Demo data seeding completed successfully!');
    }

    private function cleanExistingDemoData(): void
    {
        $this->command->info('Cleaning existing demo data...');
        // Order matters for Foreign Keys
        DB::table('leaderboard_teams')->whereBetween('equ_id', [self::ID_START, self::ID_END])->delete();
        DB::table('time')->whereBetween('user_id', [self::ID_START, self::ID_END])->delete();
        DB::table('has_participate')->whereBetween('equ_id', [self::ID_START, self::ID_END])->delete();
        DB::table('teams')->whereBetween('equ_id', [self::ID_START, self::ID_END])->delete();
        DB::table('races')->whereBetween('race_id', [self::ID_START, self::ID_END])->delete();
        DB::table('raids')->whereBetween('raid_id', [self::ID_START, self::ID_END])->delete();
        DB::table('clubs')->whereBetween('club_id', [self::ID_START, self::ID_END])->delete();
        DB::table('users')->whereBetween('id', [self::ID_START, self::ID_END])->delete();
        DB::table('members')->whereBetween('adh_id', [self::ID_START, self::ID_END])->delete();
        $this->command->info('Cleanup completed!');
    }

    private function createParamTables(): void
    {
        DB::table('param_runners')->insertOrIgnore(['pac_id' => 1, 'pac_nb_min' => 1, 'pac_nb_max' => 100, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('param_teams')->insertOrIgnore(['pae_id' => 1, 'pae_nb_min' => 2, 'pae_nb_max' => 6, 'pae_team_count_max' => 50, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('param_type')->insertOrIgnore(['typ_id' => 1, 'typ_name' => 'Trail', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('registration_period')->insertOrIgnore(['ins_id' => 1, 'ins_start_date' => now()->subMonths(2)->format('Y-m-d H:i:s'), 'ins_end_date' => now()->addMonths(2)->format('Y-m-d H:i:s'), 'created_at' => now(), 'updated_at' => now()]);
    }

    private function createDemoClubs(int $createdByUserId): array
    {
        $clubsData = [
            ['club_id' => self::ID_START + 1, 'club_name' => 'CO Azimut 77', 'club_street' => '24 Rue de la Rochette', 'club_city' => 'Melun', 'club_postal_code' => '77000', 'ffso_id' => 'FFSO77001', 'is_approved' => true, 'created_by' => self::ID_START + 35],
            ['club_id' => self::ID_START + 2, 'club_name' => 'Balise 25', 'club_street' => '2 Avenue Léo Lagrange', 'club_city' => 'Besançon', 'club_postal_code' => '25000', 'ffso_id' => 'FFSO25001', 'is_approved' => true, 'created_by' => self::ID_START + 32],
            ['club_id' => self::ID_START + 3, 'club_name' => 'Raidlinks', 'club_street' => '14 Place des Terrasses de l\'Agora', 'club_city' => 'Évry', 'club_postal_code' => '91000', 'ffso_id' => 'FFSO91001', 'is_approved' => true, 'created_by' => self::ID_START + 34],
            ['club_id' => self::ID_START + 4, 'club_name' => 'VIKAZIM', 'club_street' => '28 rue des bleuets', 'club_city' => 'Caen', 'club_postal_code' => '14000', 'ffso_id' => 'FFSO14001', 'is_approved' => true, 'created_by' => self::ID_START + 48],
        ];

        $clubIds = [];
        foreach ($clubsData as $clubData) {
            DB::table('clubs')->insertOrIgnore([
                'club_id' => $clubData['club_id'],
                'club_name' => $clubData['club_name'],
                'club_street' => $clubData['club_street'],
                'club_city' => $clubData['club_city'],
                'club_postal_code' => $clubData['club_postal_code'],
                'ffso_id' => $clubData['ffso_id'],
                'is_approved' => $clubData['is_approved'],
                'approved_by' => $clubData['created_by'],
                'approved_at' => now(),
                'created_by' => $clubData['created_by'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $clubIds[] = $clubData['club_id'];
        }
        return $clubIds;
    }

    private function createDemoRaidsAndRaces(array $clubIds, int $registrationPeriodId, int $pacId, int $paeId, int $typId): array
    {
        $allRaceIds = [];

        // Raid 1: CHAMPETRE
        $raid1Id = self::ID_START + 10;
        DB::table('raids')->insertOrIgnore([
            'raid_id' => $raid1Id,
            'raid_name' => 'Raid CHAMPETRE',
            'raid_description' => 'Course d’orientation en milieu naturel',
            'adh_id' => self::ID_START + 45, // Paul DORBEC
            'clu_id' => $clubIds[0],
            'ins_id' => $registrationPeriodId,
            'raid_date_start' => '2025-11-13 08:00:00',
            'raid_date_end' => '2025-11-14 20:00:00',
            'raid_contact' => 'contact.dorbec@coazimut77.fr',
            'raid_street' => 'PARC INTERCOMMUNAL DEBREUIL',
            'raid_city' => 'Melun',
            'raid_postal_code' => '77000',            'raid_number' => 24,            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $raid1Races = [
            [
                'race_id' => self::ID_START + 20,
                'race_name' => 'Course LUTIN',
                'race_date_start' => '2025-11-13 10:00:00',
                'race_date_end' => '2025-11-13 18:00:00',
                'race_duration_minutes' => 150,
                'adh_id' => self::ID_START + 30, // Resp: MARTIN
            ],
            [
                'race_id' => self::ID_START + 21,
                'race_name' => 'Course ELFE',
                'race_date_start' => '2025-11-14 05:00:00',
                'race_date_end' => '2025-11-14 18:00:00',
                'race_duration_minutes' => 420,
                'adh_id' => self::ID_START + 45, // Resp: DORBEC
            ],
        ];

        foreach ($raid1Races as $race) {
            DB::table('races')->insertOrIgnore([
                'race_id' => $race['race_id'],
                'race_name' => $race['race_name'],
                'race_date_start' => $race['race_date_start'],
                'race_date_end' => $race['race_date_end'],
                'race_meal_price' => 15.00,
                'race_duration_minutes' => $race['race_duration_minutes'],
                'raid_id' => $raid1Id,
                'adh_id' => $race['adh_id'],
                'pac_id' => $pacId,
                'pae_id' => $paeId,
                'typ_id' => $typId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $allRaceIds[] = $race['race_id'];
        }
        
        // Raid 2: O'Bivwak
        $raid2Id = self::ID_START + 11;
        DB::table('raids')->insertOrIgnore([
            'raid_id' => $raid2Id,
            'raid_name' => "Raid O'Bivwak",
            'raid_description' => '3 parcours chronométrés (A,B,C)',
            'adh_id' => self::ID_START + 35, // DUPONT Claire
            'clu_id' => $clubIds[0], // CO AZIMUT 77
            'ins_id' => $registrationPeriodId,
            'raid_date_start' => '2026-05-23 10:00:00',
            'raid_date_end' => '2026-05-24 18:00:00',
            'raid_contact' => 'contact.dupont@coazimut77.fr',
            'raid_street' => '7 boulevard de la République',
            'raid_city' => 'MONTERAUT',
            'raid_postal_code' => '77130',
            'raid_number' => 7,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Courses for O'Bivwak Raid
        $raid2Races = [
            [
                'race_id' => self::ID_START + 22,
                'race_name' => 'Parcours A',
                'race_date_start' => '2026-05-23 10:00:00',
                'race_date_end' => '2026-05-23 20:00:00',
                'race_duration_minutes' => 390, // 6h30
                'adh_id' => self::ID_START + 40, // ROUSSEAU Marc
            ],
            [
                'race_id' => self::ID_START + 23,
                'race_name' => 'Parcours B',
                'race_date_start' => '2026-05-24 10:00:00',
                'race_date_end' => '2026-05-24 18:00:00',
                'race_duration_minutes' => 240, // 4h00
                'adh_id' => self::ID_START + 31, // DUMONT Clara
            ],
        ];

        foreach ($raid2Races as $race) {
            DB::table('races')->insertOrIgnore([
                'race_id' => $race['race_id'],
                'race_name' => $race['race_name'],
                'race_date_start' => $race['race_date_start'],
                'race_date_end' => $race['race_date_end'],
                'race_meal_price' => 15.00,
                'race_duration_minutes' => $race['race_duration_minutes'],
                'raid_id' => $raid2Id,
                'adh_id' => $race['adh_id'],
                'pac_id' => $pacId,
                'pae_id' => $paeId,
                'typ_id' => $typId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $allRaceIds[] = $race['race_id'];
        }

        return $allRaceIds;
    }

    private function createDemoUsers(): array
    {
        $membersData = [
            ['adh_id' => self::ID_START + 30, 'name' => 'MARTIN', 'firstname' => 'Julien', 'licence' => '77001234', 'email' => 'julien.martin@test.fr'],
            ['adh_id' => self::ID_START + 31, 'name' => 'DUMONT', 'firstname' => 'Clara', 'licence' => '25004567', 'email' => 'c.dumont@email.fr'],
            ['adh_id' => self::ID_START + 32, 'name' => 'PETIT', 'firstname' => 'Antoine', 'licence' => '2025-T1L1F3', 'email' => 'antoine.petit@gmail.com'],
            ['adh_id' => self::ID_START + 33, 'name' => 'MARVELI', 'firstname' => 'Sandra', 'licence' => '64005678', 'email' => 'sandra.m60@wanadoo.fr'],
            ['adh_id' => self::ID_START + 34, 'name' => 'BERNARD', 'firstname' => 'Lucas', 'licence' => '91002345', 'email' => 'lucas.bernard@test.fr'],
            ['adh_id' => self::ID_START + 35, 'name' => 'DUPONT', 'firstname' => 'Claire', 'licence' => '1204558', 'email' => 'claire.dupont@test.fr'],
            ['adh_id' => self::ID_START + 36, 'name' => 'LEFEBVRE', 'firstname' => 'Thomas', 'licence' => '2298741', 'email' => 't.lefebvre@orange.fr'],
            ['adh_id' => self::ID_START + 37, 'name' => 'MOREAU', 'firstname' => 'Sophie', 'licence' => '6003214', 'email' => 'sophie.moreau@test.fr'],
            ['adh_id' => self::ID_START + 38, 'name' => 'LEROY', 'firstname' => 'Thomas', 'licence' => '', 'email' => 'thomas.leroy@test.fr'],
            
            // CORRECTION: Julie Garnier - Pas de licence (vide)
            ['adh_id' => self::ID_START + 39, 'name' => 'GARNIER', 'firstname' => 'Julie', 'licence' => '', 'email' => 'julie.garnier@outlook.com'],
            
            ['adh_id' => self::ID_START + 40, 'name' => 'ROUSSEAU', 'firstname' => 'Marc', 'licence' => '6700548', 'email' => 'm.rousseau@sfr.fr'],
            ['adh_id' => self::ID_START + 41, 'name' => 'FONTAINE', 'firstname' => 'Hugo', 'licence' => '91006754', 'email' => 'hugo.fontaine@test.fr'],
            ['adh_id' => self::ID_START + 42, 'name' => 'CARON', 'firstname' => 'Léa', 'licence' => '', 'email' => 'lea.caron@test.fr'],
            ['adh_id' => self::ID_START + 43, 'name' => 'PETIT', 'firstname' => 'Emma', 'licence' => '77009876', 'email' => 'emma.petit@test.fr'],
            ['adh_id' => self::ID_START + 45, 'name' => 'DORBEC', 'firstname' => 'Paul', 'licence' => '23456789', 'email' => 'paul.dorbec@unicaen.fr'],
            ['adh_id' => self::ID_START + 46, 'name' => 'JACQUIER', 'firstname' => 'Yohann', 'licence' => '', 'email' => 'yohann.jacquier@unicaen.fr'],
            ['adh_id' => self::ID_START + 47, 'name' => 'DELHOUMI', 'firstname' => 'Sylvian', 'licence' => '2025-D2S1I3', 'email' => 'sylvian.delhoumi@unicaen.fr'],
            ['adh_id' => self::ID_START + 48, 'name' => 'ANNE', 'firstname' => 'Jean-François', 'licence' => '56723478', 'email' => 'jeanfrancois.anne@unicaen.fr'],
        ];

        $userIds = [];
        
        // IMPORTANT: Create members FIRST (for FK constraints in races.adh_id -> members.adh_id)
        foreach ($membersData as $member) {
            if (!empty($member['licence'])) {
                DB::table('members')->insertOrIgnore([
                    'adh_id' => $member['adh_id'],
                    'adh_license' => $member['licence'],
                    'adh_end_validity' => now()->addYear()->format('Y-m-d'),
                    'adh_date_added' => now()->format('Y-m-d'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        
        // THEN create users (users.adh_id -> members.adh_id)
        $addresses = [
            self::ID_START + 30 => ['address' => '12 rue des Pins, 77000 Melun', 'phone' => '0612345678', 'birth_date' => '1990-04-15'],
            self::ID_START + 31 => ['address' => '45 rue des plantes 14123 IFS', 'phone' => '0698765432', 'birth_date' => '1985-09-22'],
            self::ID_START + 32 => ['address' => '5 chemin du Lac, 25140 Charquemont', 'phone' => '0711223344', 'birth_date' => '2002-01-03'],
            self::ID_START + 33 => ['address' => '8 bis rue du Parc, 14400 BAYEUX', 'phone' => '0655443322', 'birth_date' => '1995-07-18'],
            self::ID_START + 34 => ['address' => '3 allée des Sports, 91000 Évry', 'phone' => '0766778899', 'birth_date' => '1988-11-30'],
            self::ID_START + 35 => ['address' => '12 rue des Pins, 77100 MEAUX', 'phone' => '0612457890', 'birth_date' => '1992-05-14'],
            self::ID_START + 36 => ['address' => '5 avenue de l\'Europe, 25200 Montbéliard', 'phone' => '0654892133', 'birth_date' => '1985-11-23'],
            self::ID_START + 37 => ['address' => '21 route de Bayonne, 91300 MASSY', 'phone' => '0781024456', 'birth_date' => '2001-02-02'],
            self::ID_START + 38 => ['address' => '45 chemin de la Forêt, 77000 Melun', 'phone' => '0633571288', 'birth_date' => '1995-08-30'],
            self::ID_START + 39 => ['address' => '102 rue du Moulin, 77500 Chelles', 'phone' => '0765901122', 'birth_date' => '1988-07-12'],
            self::ID_START + 40 => ['address' => '3 place de la Mairie, 77000 Melun', 'phone' => '0609883451', 'birth_date' => '1974-01-19'],
            self::ID_START + 41 => ['address' => '2 rue des Peupliers, 25200 Athis-Mons', 'phone' => '0673849516', 'birth_date' => '2003-10-05'],
            self::ID_START + 42 => ['address' => '6 rue du Collège, 25200 Montbéliard', 'phone' => '0614253647', 'birth_date' => '1990-04-27'],
            self::ID_START + 43 => ['address' => '4 rue des Écoles, 25200 Montbéliard', 'phone' => '0621436587', 'birth_date' => '2005-12-08'],
            self::ID_START + 45 => ['address' => '22 rue des roses 77000 Melun', 'phone' => '0743672311', 'birth_date' => '1980-04-02'],
            self::ID_START + 46 => ['address' => '35 rue des acacias 14123 ifs', 'phone' => '0642864628', 'birth_date' => '2013-06-03'],
            self::ID_START + 47 => ['address' => '47 rue des chênes 14000 Caen', 'phone' => '0705324567', 'birth_date' => '1985-06-02'],
            self::ID_START + 48 => ['address' => '27 rue des tilleuls 14123 Cormelles Le Royal', 'phone' => '0645389485', 'birth_date' => '1964-11-05'],
        ];
        
        foreach ($membersData as $member) {
            $userId = $member['adh_id'];
            $userData = $addresses[$userId] ?? ['address' => 'Demo Address', 'phone' => '0600000000', 'birth_date' => '1990-01-01'];
            
            DB::table('users')->insertOrIgnore([
                'id' => $userId,
                'first_name' => $member['firstname'],
                'last_name' => $member['name'],
                'email' => $member['email'],
                'email_verified_at' => now(),
                'password' => '$2y$12$dnWSGCpdICbHsdeXidDJYepmhlOBg.bqNrMI3etC73Fyual6Dh8Wu',
                'adh_id' => !empty($member['licence']) ? $member['adh_id'] : null,
                'address' => $userData['address'],
                'birth_date' => $userData['birth_date'],
                'phone' => $userData['phone'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $userIds[] = $userId;
        }

        return $userIds;
    }

    private function createDemoTeams(): array
    {
        $teamsData = [
            // CHAMPETRE - Course LUTIN (653-655)
            ['equ_id' => self::ID_START + 53, 'equ_name' => 'Equipe 1', 'adh_id' => self::ID_START + 48],
            ['equ_id' => self::ID_START + 54, 'equ_name' => 'Equipe 2', 'adh_id' => self::ID_START + 34],
            ['equ_id' => self::ID_START + 55, 'equ_name' => 'Equipe 3', 'adh_id' => self::ID_START + 45],
            // CHAMPETRE - Course ELFE (656-659)
            ['equ_id' => self::ID_START + 56, 'equ_name' => 'Equipe 1', 'adh_id' => self::ID_START + 35],
            ['equ_id' => self::ID_START + 57, 'equ_name' => 'Equipe 2', 'adh_id' => self::ID_START + 48],
            ['equ_id' => self::ID_START + 58, 'equ_name' => 'Equipe 3', 'adh_id' => self::ID_START + 41],
            ['equ_id' => self::ID_START + 59, 'equ_name' => 'Equipe 4', 'adh_id' => self::ID_START + 43],
            // O'BIVWAK - Parcours B (660-662)
            ['equ_id' => self::ID_START + 60, 'equ_name' => 'Equipe DORMEUR', 'adh_id' => self::ID_START + 36], // LEFEBVRE Thomas
            ['equ_id' => self::ID_START + 61, 'equ_name' => 'Equipe ATCHOUM', 'adh_id' => self::ID_START + 41], // FONTAINE Hugo
            ['equ_id' => self::ID_START + 62, 'equ_name' => 'Equipe SIMPLET', 'adh_id' => self::ID_START + 41], // FONTAINE Hugo
        ];

        $teamIds = [];
        foreach ($teamsData as $teamData) {
            DB::table('teams')->insertOrIgnore([
                'equ_id' => $teamData['equ_id'],
                'equ_name' => $teamData['equ_name'],
                'adh_id' => $teamData['adh_id'],
                'user_id' => $teamData['adh_id'], 
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $teamIds[$teamData['equ_id']] = $teamData['equ_id'];
        }
        return $teamIds;
    }

    private function linkUsersToTeams(array $userIds, array $teamIds): void
    {
        $assignments = [
            // CHAMPETRE - Course LUTIN
            [self::ID_START + 53, [self::ID_START + 33, self::ID_START + 47]],
            [self::ID_START + 54, [self::ID_START + 34, self::ID_START + 37]],
            [self::ID_START + 55, [self::ID_START + 39, self::ID_START + 45]], // Julie (39) is here
            // CHAMPETRE - Course ELFE
            [self::ID_START + 56, [self::ID_START + 35, self::ID_START + 38]], // Dupont Claire + Leroy Thomas
            [self::ID_START + 57, [self::ID_START + 33, self::ID_START + 34]], // Marveli Sandra + Bernard Lucas
            [self::ID_START + 58, [self::ID_START + 32, self::ID_START + 41]], // Petit Antoine + Fontaine Hugo
            [self::ID_START + 59, [self::ID_START + 43, self::ID_START + 36]], // Petit Emma + Lefebvre Thomas
            // O'BIVWAK - Parcours B
            [self::ID_START + 60, [self::ID_START + 43, self::ID_START + 36]], // DORMEUR: Emma PETIT + Thomas LEFEBVRE
            [self::ID_START + 61, [self::ID_START + 41, self::ID_START + 42]], // ATCHOUM: Hugo FONTAINE + Léa CARON
            [self::ID_START + 62, [self::ID_START + 39, self::ID_START + 40]], // SIMPLET: Julie GARNIER + Marc ROUSSEAU
        ];

        foreach ($assignments as [$teamId, $members]) {
            foreach ($members as $userId) {
                // Check if user has a member/license (adh_id in members table)
                $hasMember = DB::table('members')->where('adh_id', $userId)->exists();
                
                DB::table('has_participate')->insertOrIgnore([
                    'adh_id' => $hasMember ? $userId : null, // NULL if no license (like Julie GARNIER)
                    'equ_id' => $teamId,
                    'id_users' => $userId, // Always set user_id
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        $this->command->info('Linked users to teams.');
    }

    /**
     * Create times. Updated to include ALL participants (even without license)
     */
    private function createDemoTimes(array $raceIds): void
    {
        $timeId = self::ID_START + 70;

        // NEW LOGIC: Get unique users from has_participate within our ID range
        // This ensures Julie (who has no license but is in a team) gets a time.
        $participatingUsers = DB::table('has_participate')
            ->whereBetween('id_users', [self::ID_START, self::ID_END])
            ->pluck('id_users')
            ->unique()
            ->toArray();

        foreach ($raceIds as $raceId) {
            // Only generate for Champetre
            if ($raceId < self::ID_START + 20 || $raceId > self::ID_START + 21) continue;
            
            $race = DB::table('races')->where('race_id', $raceId)->first();
            $baseTime = $race->race_duration_minutes * 60;
            
            foreach ($participatingUsers as $index => $userId) {
                if ($timeId >= self::ID_END) break;

                $variation = rand(-600, 1200);
                $totalSeconds = $baseTime + $variation + ($index * 120);
                
                $hours = floor($totalSeconds / 3600);
                $minutes = floor(($totalSeconds % 3600) / 60);
                $seconds = $totalSeconds % 60;
                
                DB::table('time')->insertOrIgnore([
                    'user_id' => $userId,
                    'race_id' => $raceId,
                    'time_hours' => $hours,
                    'time_minutes' => $minutes,
                    'time_seconds' => $seconds,
                    'time_total_seconds' => $totalSeconds,
                    'time_rank' => $index + 1,
                    'time_rank_start' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $timeId++;
            }
        }
        $this->command->info("Created demo times for all participants.");
    }

    private function createLeaderboardTeams(): void
    {
        $raceId = self::ID_START + 21; // ELFE

        // Get or create "18 ans et +" age category
        $ageCategory = DB::table('age_categories')
            ->where('nom', '18 ans et +')
            ->orWhere(function($query) {
                $query->where('age_min', '<=', 18)->whereNull('age_max');
            })
            ->first();
        
        if (!$ageCategory) {
            // Create the category if it doesn't exist
            $ageCategoryId = DB::table('age_categories')->insertGetId([
                'nom' => '18 ans et +',
                'age_min' => 18,
                'age_max' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $ageCategoryId = $ageCategory->id;
        }

        $leaderboardData = [
            ['equ_id' => self::ID_START + 56, 'avg' => 22895, 'pts' => 199],
            ['equ_id' => self::ID_START + 57, 'avg' => 20842, 'pts' => 287],
            ['equ_id' => self::ID_START + 58, 'avg' => 19405, 'pts' => 322],
            ['equ_id' => self::ID_START + 59, 'avg' => 20036, 'pts' => 305],
        ];

        foreach ($leaderboardData as $data) {
            DB::table('leaderboard_teams')->insertOrIgnore([
                'equ_id' => $data['equ_id'],
                'race_id' => $raceId,
                'average_temps' => $data['avg'],
                'average_malus' => 0,
                'average_temps_final' => $data['avg'],
                'member_count' => 2,
                'points' => $data['pts'],
                'age_category_id' => $ageCategoryId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $this->command->info('Created leaderboard entries.');
    }

    /**
     * Assign roles to users based on their responsibilities
     */
    private function assignUserRoles(): void
    {
        // Responsables Club (créateurs de clubs)
        $clubResponsibles = [
            self::ID_START + 35, // Claire DUPONT - CO Azimut 77
            self::ID_START + 32, // Antoine PETIT - Balise 25
            self::ID_START + 34, // Lucas BERNARD - Raidlinks
            self::ID_START + 48, // Jean-François ANNE - VIKAZIM
        ];

        foreach ($clubResponsibles as $userId) {
            DB::table('model_has_roles')->insertOrIgnore([
                'role_id' => DB::table('roles')->where('name', 'responsable-club')->value('id'),
                'model_type' => 'App\\Models\\User',
                'model_id' => $userId,
            ]);
        }

        // Gestionnaires Raid (responsables de raids)
        $raidManagers = [
            self::ID_START + 45, // Paul DORBEC - Raid CHAMPETRE
            self::ID_START + 35, // Claire DUPONT - Raid O'Bivwak
        ];

        foreach ($raidManagers as $userId) {
            DB::table('model_has_roles')->insertOrIgnore([
                'role_id' => DB::table('roles')->where('name', 'gestionnaire-raid')->value('id'),
                'model_type' => 'App\\Models\\User',
                'model_id' => $userId,
            ]);
        }

        // Responsables Course (responsables de courses)
        $courseResponsibles = [
            self::ID_START + 30, // Julien MARTIN - Course LUTIN
            self::ID_START + 45, // Paul DORBEC - Course ELFE
            self::ID_START + 40, // Marc ROUSSEAU - Parcours A
            self::ID_START + 31, // Clara DUMONT - Parcours B
        ];

        foreach ($courseResponsibles as $userId) {
            DB::table('model_has_roles')->insertOrIgnore([
                'role_id' => DB::table('roles')->where('name', 'responsable-course')->value('id'),
                'model_type' => 'App\\Models\\User',
                'model_id' => $userId,
            ]);
        }

        // All users with licence get 'adherent' role
        $usersWithLicence = DB::table('users')
            ->whereBetween('id', [self::ID_START, self::ID_END])
            ->whereNotNull('adh_id')
            ->pluck('id');

        $adherentRoleId = DB::table('roles')->where('name', 'adherent')->value('id');
        
        foreach ($usersWithLicence as $userId) {
            DB::table('model_has_roles')->insertOrIgnore([
                'role_id' => $adherentRoleId,
                'model_type' => 'App\\Models\\User',
                'model_id' => $userId,
            ]);
        }

        // Users without licence get 'user' role
        $usersWithoutLicence = DB::table('users')
            ->whereBetween('id', [self::ID_START, self::ID_END])
            ->whereNull('adh_id')
            ->pluck('id');

        $userRoleId = DB::table('roles')->where('name', 'user')->value('id');
        
        foreach ($usersWithoutLicence as $userId) {
            DB::table('model_has_roles')->insertOrIgnore([
                'role_id' => $userRoleId,
                'model_type' => 'App\\Models\\User',
                'model_id' => $userId,
            ]);
        }

        $this->command->info('Assigned roles to users.');
    }
}