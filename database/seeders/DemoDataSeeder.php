<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Race;
use App\Models\Team;
use App\Models\Raid;
use App\Models\Club;
use App\Models\Member;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder for demo data with reserved ID range 600-700.
 * Creates realistic demo data for presentations and testing.
 */
class DemoDataSeeder extends Seeder
{
    private const ID_START = 600;
    private const ID_END = 700;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting demo data seeding (ID range: 600-700)...');

        // Create param tables data if they don't exist
        DB::table('param_runners')->insertOrIgnore([
            'pac_id' => 1,
            'pac_nb_min' => 1,
            'pac_nb_max' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('param_teams')->insertOrIgnore([
            'pae_id' => 1,
            'pae_nb_min' => 2,
            'pae_nb_max' => 6,
            'pae_team_count_max' => 50,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('param_type')->insertOrIgnore([
            'typ_id' => 1,
            'typ_name' => 'Trail',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('registration_period')->insertOrIgnore([
            'ins_id' => 1,
            'ins_start_date' => now()->subMonths(2)->format('Y-m-d H:i:s'),
            'ins_end_date' => now()->addMonths(2)->format('Y-m-d H:i:s'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Use default IDs
        $paramRunnerId = 1;
        $paramTeamId = 1;
        $paramTypeId = 1;
        $registrationPeriodId = 1;

        // Create demo users first (needed for created_by in clubs)
        $userIds = $this->createDemoUsers();
        $adminUserId = $userIds[0]; // First user will be the creator/approver
        
        // Create demo clubs
        $clubIds = $this->createDemoClubs($adminUserId);
        
        // Create demo raids and races (using hardcoded param IDs)
        $raceIds = $this->createDemoRaidsAndRaces($clubIds, $registrationPeriodId, $paramRunnerId, $paramTeamId, $paramTypeId);
        
        // Create demo teams
        $teamIds = $this->createDemoTeams();
        
        // Link users to teams
        $this->linkUsersToTeams($userIds, $teamIds);
        
        // Create demo times/results
        $this->createDemoTimes($raceIds, $userIds);

        $this->command->info('Demo data seeding completed successfully!');
    }

    /**
     * Create demo clubs
     */
    private function createDemoClubs(int $createdByUserId): array
    {
        $clubsData = [
            [
                'club_id' => self::ID_START + 1,
                'club_name' => 'CO Azimut 77',
                'club_street' => '24 Rue de la Rochette',
                'club_city' => 'Melun',
                'club_postal_code' => '77000',
                'club_number' => '24',
                'ffso_id' => 'FFSO77001',
                'description' => 'Club d\'orientation CO Azimut 77 - Contact: DUPONT Claire - Tél: 0613245698',
                'is_approved' => true,
            ],
            [
                'club_id' => self::ID_START + 2,
                'club_name' => 'Balise 25',
                'club_street' => '2 Avenue Léo Lagrange',
                'club_city' => 'Besançon',
                'club_postal_code' => '25000',
                'club_number' => '2',
                'ffso_id' => 'FFSO25001',
                'description' => 'Club Balise 25 - Contact: PETIT Antoine - Tél: 0783295427',
                'is_approved' => true,
            ],
            [
                'club_id' => self::ID_START + 3,
                'club_name' => 'Raidlinks',
                'club_street' => '14 Place des Terrasses de l\'Agora',
                'club_city' => 'Évry',
                'club_postal_code' => '91000',
                'club_number' => '14',
                'ffso_id' => 'FFSO91001',
                'description' => 'Club Raidlinks - Contact: BERNARD Lucas - Tél: 0642376582',
                'is_approved' => true,
            ],
            [
                'club_id' => self::ID_START + 4,
                'club_name' => 'VIKAZIM',
                'club_street' => '28 rue des bleuets',
                'club_city' => 'Caen',
                'club_postal_code' => '14000',
                'club_number' => '28',
                'ffso_id' => 'FFSO14001',
                'description' => 'Club VIKAZIM - Contact: ANNE Jean-François - Tél: 0764923785',
                'is_approved' => true,
            ],
        ];

        $clubIds = [];
        foreach ($clubsData as $clubData) {
            DB::table('clubs')->insertOrIgnore([
                'club_id' => $clubData['club_id'],
                'club_name' => $clubData['club_name'],
                'club_street' => $clubData['club_street'],
                'club_city' => $clubData['club_city'],
                'club_postal_code' => $clubData['club_postal_code'],
                // 'club_number' removed by migration 2026_01_07_064153
                'ffso_id' => $clubData['ffso_id'],
                'description' => $clubData['description'],
                'club_image' => null,
                'is_approved' => $clubData['is_approved'],
                'approved_by' => $createdByUserId,
                'approved_at' => now(),
                'created_by' => $createdByUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $clubIds[] = $clubData['club_id'];
            $this->command->info("Created club: {$clubData['club_name']} (ID: {$clubData['club_id']})");
        }

        return $clubIds;
    }

    /**
     * Create demo raids and their races
     */
    private function createDemoRaidsAndRaces(array $clubIds, int $registrationPeriodId, int $pacId, int $paeId, int $typId): array
    {
        $allRaceIds = [];

        // Raid 1: CHAMPETRE - Responsable: Paul DORBEC (adh_id: 645)
        $raid1Id = self::ID_START + 10;
        DB::table('raids')->insertOrIgnore([
            'raid_id' => $raid1Id,
            'raid_name' => 'Raid CHAMPETRE',
            'raid_description' => 'Course d\'orientation en milieu naturel - Taille équipe: 3 max - Responsable du RAID: M. Dorbec',
            'raid_image' => null,
            'adh_id' => self::ID_START + 45, // Paul DORBEC
            'clu_id' => $clubIds[0], // CO AZIMUT 77
            'ins_id' => $registrationPeriodId,
            'raid_date_start' => '2025-11-13 08:00:00',
            'raid_date_end' => '2025-11-14 20:00:00',
            'raid_contact' => 'contact.dorbec@coazimut77.fr',
            'raid_site_url' => 'https://champetre.coazimut77.fr',
            'raid_street' => '24 Rue de la Rochette',
            'raid_city' => 'Melun',
            'raid_postal_code' => '77000',
            'raid_number' => 24,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->command->info("Created raid: Raid CHAMPETRE (ID: {$raid1Id})");

        // Races for Raid CHAMPETRE
        $raid1Races = [
            [
                'race_id' => self::ID_START + 20,
                'race_name' => 'Course LUTIN',
                'race_date_start' => '2025-11-13 10:00:00',
                'race_date_end' => '2025-11-13 18:00:00',
                'race_duration_minutes' => 150,
            ],
            [
                'race_id' => self::ID_START + 21,
                'race_name' => 'Course ELFE',
                'race_date_start' => '2025-11-14 05:00:00',
                'race_date_end' => '2025-11-14 18:00:00',
                'race_duration_minutes' => 420,
            ],
        ];

        foreach ($raid1Races as $race) {
            DB::table('races')->insertOrIgnore([
                'race_id' => $race['race_id'],
                'race_name' => $race['race_name'],
                'image_url' => null,
                'race_date_start' => $race['race_date_start'],
                'race_date_end' => $race['race_date_end'],
                'race_reduction' => 0,
                'race_meal_price' => 15.00,
                'race_duration_minutes' => $race['race_duration_minutes'],
                'raid_id' => $raid1Id,
                'adh_id' => self::ID_START + 30, // Julien MARTIN (responsable Course LUTIN)
                'pac_id' => $pacId,
                'pae_id' => $paeId,
                'typ_id' => $typId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $allRaceIds[] = $race['race_id'];
            $this->command->info("Created race: {$race['race_name']} (ID: {$race['race_id']})");
        }

        // Raid 2: O'Bivwak - Responsable: Claire DUPONT (adh_id: 635)
        $raid2Id = self::ID_START + 11;
        DB::table('raids')->insertOrIgnore([
            'raid_id' => $raid2Id,
            'raid_name' => "Raid O'Bivwak",
            'raid_description' => '3 parcours (A,B,C) chronométrés sont proposés pour répondre à tous les publics sportif débutant à expert - Taille équipe: 3 max - Responsable RAID: DUPONT Claire',
            'raid_image' => null,
            'adh_id' => self::ID_START + 35, // Claire DUPONT
            'clu_id' => $clubIds[0], // CO AZIMUT 77
            'ins_id' => $registrationPeriodId,
            'raid_date_start' => '2026-05-23 10:00:00',
            'raid_date_end' => '2026-05-24 18:00:00',
            'raid_contact' => 'contact.dupont@coazimut77.fr',
            'raid_site_url' => 'https://obivwak.coazimut77.fr',
            'raid_street' => '24 Rue de la Rochette',
            'raid_city' => 'Melun',
            'raid_postal_code' => '77000',
            'raid_number' => 24,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->command->info("Created raid: Raid O'Bivwak (ID: {$raid2Id})");

        // Races for Raid O'Bivwak
        $raid2Races = [
            [
                'race_id' => self::ID_START + 22,
                'race_name' => 'Parcours A',
                'race_date_start' => '2026-05-23 10:00:00',
                'race_date_end' => '2026-05-23 20:00:00',
                'race_duration_minutes' => 390, // 06:30
                'description' => 'Responsable: ROUSSEAU Marc - Catégories Age: 21 ans et + - Équipe de 2 - LICENCE Obligatoire, puce obligatoire - nb équipes: 20 - Compétition - Difficulté: Complexe - Distance: 20km - Participants min: 10, max: 40',
            ],
            [
                'race_id' => self::ID_START + 23,
                'race_name' => 'Parcours B',
                'race_date_start' => '2026-05-24 10:00:00',
                'race_date_end' => '2026-05-24 18:00:00',
                'race_duration_minutes' => 240, // 04:00
                'description' => 'Responsable: Dumont Clara - Catégories Age: 18 ans et + - Équipe de 2 - licence non obligatoire - nb équipes: 4 - Loisirs - Difficulté: Modérée - Distance: 10km - Participants min: 2, max: 8',
            ],
        ];

        foreach ($raid2Races as $race) {
            DB::table('races')->insertOrIgnore([
                'race_id' => $race['race_id'],
                'race_name' => $race['race_name'],
                'image_url' => null,
                'race_date_start' => $race['race_date_start'],
                'race_date_end' => $race['race_date_end'],
                'race_reduction' => 0,
                'race_meal_price' => 18.00,
                'race_duration_minutes' => $race['race_duration_minutes'],
                'raid_id' => $raid2Id,
                'adh_id' => self::ID_START + 30, // Default responsable
                'pac_id' => $pacId,
                'pae_id' => $paeId,
                'typ_id' => $typId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $allRaceIds[] = $race['race_id'];
            $this->command->info("Created race: {$race['race_name']} (ID: {$race['race_id']})");
        }

        return $allRaceIds;
    }

    /**
     * Create demo users
     */
    private function createDemoUsers(): array
    {
        // First create medical docs for users (required by FK)
        $docIds = [];
        for ($i = 0; $i < 21; $i++) {
            $docId = self::ID_START + 60 + $i;
            DB::table('medical_docs')->insertOrIgnore([
                'doc_id' => $docId,
                'doc_num_pps' => 'PPS' . str_pad($docId, 10, '0', STR_PAD_LEFT),
                'doc_end_validity' => now()->addYear()->format('Y-m-d'),
                'doc_date_added' => now()->format('Y-m-d'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $docIds[] = $docId;
        }

        // Create members for users
        $membersData = [
            ['adh_id' => self::ID_START + 30, 'name' => 'MARTIN', 'firstname' => 'Julien', 'club' => 'CO Azimut 77', 'licence' => '77001234', 'birthday' => '1990-04-15', 'phone' => '0612345678'],
            ['adh_id' => self::ID_START + 31, 'name' => 'DUMONT', 'firstname' => 'Clara', 'club' => 'Raidlinks', 'licence' => '25004567', 'birthday' => '1985-09-22', 'phone' => '0698765432'],
            ['adh_id' => self::ID_START + 32, 'name' => 'PETIT', 'firstname' => 'Antoine', 'club' => 'Balise 25', 'licence' => '2025-T1L1F3', 'birthday' => '2002-01-03', 'phone' => '0711223344'],
            ['adh_id' => self::ID_START + 33, 'name' => 'MARVELI', 'firstname' => 'Sandra', 'club' => 'VIKAZIM', 'licence' => '64005678', 'birthday' => '1995-07-18', 'phone' => '0655443322'],
            ['adh_id' => self::ID_START + 34, 'name' => 'BERNARD', 'firstname' => 'Lucas', 'club' => 'Raidlinks', 'licence' => '91002345', 'birthday' => '1988-11-30', 'phone' => '0766778899'],
            ['adh_id' => self::ID_START + 35, 'name' => 'DUPONT', 'firstname' => 'Claire', 'club' => 'CO Azimut 77', 'licence' => '1204558', 'birthday' => '1992-05-14', 'phone' => '0612457890'],
            ['adh_id' => self::ID_START + 36, 'name' => 'LEFEBVRE', 'firstname' => 'Thomas', 'club' => 'Balise 25', 'licence' => '2298741', 'birthday' => '1985-11-23', 'phone' => '0654892133'],
            ['adh_id' => self::ID_START + 37, 'name' => 'MOREAU', 'firstname' => 'Sophie', 'club' => 'Raidlinks', 'licence' => '6003214', 'birthday' => '2001-02-02', 'phone' => '0781024456'],
            ['adh_id' => self::ID_START + 38, 'name' => 'LEROY', 'firstname' => 'Thomas', 'club' => 'CO Azimut 77', 'licence' => '6901122', 'birthday' => '1995-08-30', 'phone' => '0633571288'],
            ['adh_id' => self::ID_START + 39, 'name' => 'GARNIER', 'firstname' => 'Julie', 'club' => 'CO Azimut 77', 'licence' => '', 'birthday' => '1988-07-12', 'phone' => '0765901122'],
            ['adh_id' => self::ID_START + 40, 'name' => 'LEROY', 'firstname' => 'Thomas', 'club' => 'CO Azimut 77', 'licence' => '6700548', 'birthday' => '1974-01-19', 'phone' => '0609883451'],
            ['adh_id' => self::ID_START + 41, 'name' => 'FONTAINE', 'firstname' => 'Hugo', 'club' => 'Balise 25', 'licence' => '91006754', 'birthday' => '2003-10-05', 'phone' => '0673849516'],
            ['adh_id' => self::ID_START + 42, 'name' => 'CARON', 'firstname' => 'Léa', 'club' => 'Balise 25', 'licence' => '', 'birthday' => '1990-04-27', 'phone' => '0614253647'],
            ['adh_id' => self::ID_START + 43, 'name' => 'PETIT', 'firstname' => 'Emma', 'club' => 'Balise 25', 'licence' => '77009876', 'birthday' => '2005-12-08', 'phone' => '0621436587'],
            ['adh_id' => self::ID_START + 44, 'name' => 'ROUX', 'firstname' => 'Nathan', 'club' => 'Balise 25', 'licence' => '25006789', 'birthday' => '2000-01-26', 'phone' => '0734567812'],
            ['adh_id' => self::ID_START + 45, 'name' => 'DORBEC', 'firstname' => 'Paul', 'club' => 'CO AZIMUT 77', 'licence' => '23456789', 'birthday' => '1980-04-02', 'phone' => '0743672311'],
            ['adh_id' => self::ID_START + 46, 'name' => 'JACQUIER', 'firstname' => 'Yohann', 'club' => 'VIKAZIM', 'licence' => '', 'birthday' => '2013-06-03', 'phone' => '0642864628'],
            ['adh_id' => self::ID_START + 47, 'name' => 'DELHOUMI', 'firstname' => 'Sylvian', 'club' => 'VIKAZIM', 'licence' => '2025-D2S1I3', 'birthday' => '1985-06-02', 'phone' => '0705324567'],
            ['adh_id' => self::ID_START + 48, 'name' => 'ANNE', 'firstname' => 'Jean-François', 'club' => 'VIKAZIM', 'licence' => '56723478', 'birthday' => '1964-11-05', 'phone' => '0645389485'],
        ];

        foreach ($membersData as $index => $member) {
            DB::table('members')->insertOrIgnore([
                'adh_id' => $member['adh_id'],
                'adh_license' => $member['licence'] ?: 'DEMO-' . $member['adh_id'],
                'adh_end_validity' => now()->addYear()->format('Y-m-d'),
                'adh_date_added' => now()->format('Y-m-d'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create users
        $usersData = [
            ['id' => self::ID_START + 30, 'adh_id' => self::ID_START + 30, 'doc_id' => $docIds[0], 'first_name' => 'Julien', 'last_name' => 'MARTIN', 'email' => 'julien.martin@test.fr'],
            ['id' => self::ID_START + 31, 'adh_id' => self::ID_START + 31, 'doc_id' => $docIds[1], 'first_name' => 'Clara', 'last_name' => 'DUMONT', 'email' => 'claire.dupont@test.fr'],
            ['id' => self::ID_START + 32, 'adh_id' => self::ID_START + 32, 'doc_id' => $docIds[2], 'first_name' => 'Antoine', 'last_name' => 'PETIT', 'email' => 'thomas.leroy@test.fr'],
            ['id' => self::ID_START + 33, 'adh_id' => self::ID_START + 33, 'doc_id' => $docIds[3], 'first_name' => 'Sandra', 'last_name' => 'MARVELI', 'email' => 'sophie.moreau@test.fr'],
            ['id' => self::ID_START + 34, 'adh_id' => self::ID_START + 34, 'doc_id' => $docIds[4], 'first_name' => 'Lucas', 'last_name' => 'BERNARD', 'email' => 'lucas.bernard@test.fr'],
            ['id' => self::ID_START + 35, 'adh_id' => self::ID_START + 35, 'doc_id' => $docIds[5], 'first_name' => 'Claire', 'last_name' => 'DUPONT', 'email' => 'c.dumont@email.fr'],
            ['id' => self::ID_START + 36, 'adh_id' => self::ID_START + 36, 'doc_id' => $docIds[6], 'first_name' => 'Thomas', 'last_name' => 'LEFEBVRE', 'email' => 't.lefebvre@orange.fr'],
            ['id' => self::ID_START + 37, 'adh_id' => self::ID_START + 37, 'doc_id' => $docIds[7], 'first_name' => 'Sophie', 'last_name' => 'MOREAU', 'email' => 'sophie.m60@wanadoo.fr'],
            ['id' => self::ID_START + 38, 'adh_id' => self::ID_START + 38, 'doc_id' => $docIds[8], 'first_name' => 'Thomas', 'last_name' => 'LEROY', 'email' => 'antoine.petit@gmail.com'],
            ['id' => self::ID_START + 39, 'adh_id' => self::ID_START + 39, 'doc_id' => $docIds[9], 'first_name' => 'Julie', 'last_name' => 'GARNIER', 'email' => 'julie.garnier@outlook.com'],
            ['id' => self::ID_START + 40, 'adh_id' => self::ID_START + 40, 'doc_id' => $docIds[10], 'first_name' => 'Thomas', 'last_name' => 'LEROY', 'email' => 'm.rousseau@sfr.fr'],
            ['id' => self::ID_START + 41, 'adh_id' => self::ID_START + 41, 'doc_id' => $docIds[11], 'first_name' => 'Hugo', 'last_name' => 'FONTAINE', 'email' => 'hugo.fontaine@test.fr'],
            ['id' => self::ID_START + 42, 'adh_id' => self::ID_START + 42, 'doc_id' => $docIds[12], 'first_name' => 'Léa', 'last_name' => 'CARON', 'email' => 'lea.caron@test.fr'],
            ['id' => self::ID_START + 43, 'adh_id' => self::ID_START + 43, 'doc_id' => $docIds[13], 'first_name' => 'Emma', 'last_name' => 'PETIT', 'email' => 'emma.petit@test.fr'],
            ['id' => self::ID_START + 44, 'adh_id' => self::ID_START + 44, 'doc_id' => $docIds[14], 'first_name' => 'Nathan', 'last_name' => 'ROUX', 'email' => 'nathan.roux@test.fr'],
            ['id' => self::ID_START + 45, 'adh_id' => self::ID_START + 45, 'doc_id' => $docIds[15], 'first_name' => 'Paul', 'last_name' => 'DORBEC', 'email' => 'paul.dorbec@unicaen.fr'],
            ['id' => self::ID_START + 46, 'adh_id' => self::ID_START + 46, 'doc_id' => $docIds[16], 'first_name' => 'Yohann', 'last_name' => 'JACQUIER', 'email' => 'yohann.jacquier@unicaen.fr'],
            ['id' => self::ID_START + 47, 'adh_id' => self::ID_START + 47, 'doc_id' => $docIds[17], 'first_name' => 'Sylvian', 'last_name' => 'DELHOUMI', 'email' => 'sylvian.delhoumi@unicaen.fr'],
            ['id' => self::ID_START + 48, 'adh_id' => self::ID_START + 48, 'doc_id' => $docIds[18], 'first_name' => 'Jean-François', 'last_name' => 'ANNE', 'email' => 'jeanfrancois.anne@unicaen.fr'],
        ];

        $userIds = [];
        foreach ($usersData as $index => $userData) {
            // Get corresponding member data for birth_date and phone
            $memberData = $membersData[$index];
            
            DB::table('users')->insertOrIgnore([
                'id' => $userData['id'],
                'doc_id' => $userData['doc_id'],
                'adh_id' => $userData['adh_id'],
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'email' => $userData['email'],
                'birth_date' => $memberData['birthday'], // Added from migration 2026_01_01_000017__add_profile_fields_to_users_table
                'address' => 'Demo Address ' . $userData['id'], // Added from migration 2026_01_01_000017__add_profile_fields_to_users_table
                'phone' => $memberData['phone'], // Added from migration 2026_01_01_000017__add_profile_fields_to_users_table
                'password' => Hash::make('Demo2026!'),
                'email_verified_at' => now(),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $userIds[] = $userData['id'];
            $this->command->info("Created user: {$userData['first_name']} {$userData['last_name']} (ID: {$userData['id']})");
        }

        return $userIds;
    }

    /**
     * Create demo teams (Equipes from Parcours B)
     */
    private function createDemoTeams(): array
    {
        $teamsData = [
            ['equ_id' => self::ID_START + 50, 'equ_name' => 'Equipe DORMEUR', 'adh_id' => self::ID_START + 35], // Claire DUPONT (Responsable)
            ['equ_id' => self::ID_START + 51, 'equ_name' => 'Equipe ATCHOUM', 'adh_id' => self::ID_START + 41], // Hugo FONTAINE (Responsable)
            ['equ_id' => self::ID_START + 52, 'equ_name' => 'Equipe SIMPLET', 'adh_id' => self::ID_START + 41], // Hugo FONTAINE (Responsable)
        ];

        $teamIds = [];
        foreach ($teamsData as $teamData) {
            DB::table('teams')->insertOrIgnore([
                'equ_id' => $teamData['equ_id'],
                'equ_name' => $teamData['equ_name'],
                'equ_image' => null,
                'adh_id' => $teamData['adh_id'],
                'user_id' => $teamData['adh_id'], // user_id = same as adh_id (from MySQL structure)
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $teamIds[] = $teamData['equ_id'];
            $this->command->info("Created team: {$teamData['equ_name']} (ID: {$teamData['equ_id']})");
        }

        return $teamIds;
    }

    /**
     * Link users to teams via has_participate (Based on Parcours B teams)
     */
    private function linkUsersToTeams(array $userIds, array $teamIds): void
    {
        // Equipe DORMEUR: Responsable Claire DUPONT (635) + Participants: Claire DUPONT (635), Thomas LEFEBVRE (636)
        $team1Members = [self::ID_START + 35, self::ID_START + 36]; // Claire DUPONT, Thomas LEFEBVRE
        
        // Equipe ATCHOUM: Responsable Hugo FONTAINE (641) + Participants: Hugo FONTAINE (641), Léa CARON (642)
        $team2Members = [self::ID_START + 41, self::ID_START + 42]; // Hugo FONTAINE, Léa CARON
        
        // Equipe SIMPLET: Responsable Hugo FONTAINE (641) + Participants: Julie GARNIER (639), Marc ROUSSEAU (640 - using Thomas LEROY ID)
        $team3Members = [self::ID_START + 39, self::ID_START + 40]; // Julie GARNIER, Thomas LEROY

        $assignments = [
            [$teamIds[0], $team1Members],
            [$teamIds[1], $team2Members],
            [$teamIds[2], $team3Members],
        ];

        foreach ($assignments as [$teamId, $members]) {
            foreach ($members as $index => $adhId) {
                // Calculate corresponding user_id from adh_id (both ranges start at ID_START + 30)
                $userId = $adhId;
                
                DB::table('has_participate')->insertOrIgnore([
                    // 'id' is auto-increment primary key, not manually set
                    'adh_id' => $adhId, // has_participate uses adh_id (member ID)
                    'equ_id' => $teamId,
                    'reg_id' => null, // reg_id nullable - registration ID if exists
                    'par_time' => null, // par_time nullable - participation time if tracked
                    'id_users' => $userId, // Added by migration 2026_01_07_125534_add_id_users_to_has_participate_table
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('Linked users to teams');
    }

    /**
     * Create demo times/results for races
     */
    private function createDemoTimes(array $raceIds, array $userIds): void
    {
        $timeId = self::ID_START + 70;

        foreach ($raceIds as $raceIndex => $raceId) {
            // Get race duration to adjust times
            $race = DB::table('races')->where('race_id', $raceId)->first();
            
            // Use race_duration_minutes as base time (convert to seconds)
            $baseTime = $race->race_duration_minutes * 60;
            
            // Create times for each user with realistic variations
            foreach ($userIds as $userIndex => $userId) {
                if ($timeId >= self::ID_END) {
                    $this->command->warn('Reached ID limit for demo data');
                    break 2;
                }

                // Calculate realistic time with variation
                $variation = rand(-600, 1200); // -10 to +20 minutes
                $totalSeconds = $baseTime + $variation + ($userIndex * 180); // Add 3 minutes per position
                
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
                    'time_rank' => $userIndex + 1,
                    'time_rank_start' => $userIndex + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                $timeId++;
            }
            
            $this->command->info("Created demo times for race ID: {$raceId}");
        }
    }
}