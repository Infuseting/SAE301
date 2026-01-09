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

        // Clean existing demo data first to avoid duplicates
        $this->cleanExistingDemoData();

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
     * Clean existing demo data in ID range 600-700
     */
    private function cleanExistingDemoData(): void
    {
        $this->command->info('Cleaning existing demo data...');

        // Delete in reverse order to respect foreign key constraints
        DB::table('time')->whereBetween('user_id', [self::ID_START, self::ID_END])->delete();
        $this->command->info('- Deleted times/results');

        DB::table('has_participate')->whereBetween('equ_id', [self::ID_START, self::ID_END])->delete();
        $this->command->info('- Deleted team participations');

        DB::table('teams')->whereBetween('equ_id', [self::ID_START, self::ID_END])->delete();
        $this->command->info('- Deleted teams');

        DB::table('races')->whereBetween('race_id', [self::ID_START, self::ID_END])->delete();
        $this->command->info('- Deleted races');

        DB::table('raids')->whereBetween('raid_id', [self::ID_START, self::ID_END])->delete();
        $this->command->info('- Deleted raids');

        DB::table('clubs')->whereBetween('club_id', [self::ID_START, self::ID_END])->delete();
        $this->command->info('- Deleted clubs');

        DB::table('users')->whereBetween('id', [self::ID_START, self::ID_END])->delete();
        $this->command->info('- Deleted users');

        DB::table('members')->whereBetween('adh_id', [self::ID_START, self::ID_END])->delete();
        $this->command->info('- Deleted members');

        $this->command->info('Cleanup completed!');
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
                'is_approved' => true,
                'created_by' => self::ID_START + 35, // Claire DUPONT
            ],
            [
                'club_id' => self::ID_START + 2,
                'club_name' => 'Balise 25',
                'club_street' => '2 Avenue Léo Lagrange',
                'club_city' => 'Besançon',
                'club_postal_code' => '25000',
                'club_number' => '2',
                'ffso_id' => 'FFSO25001',
                'is_approved' => true,
                'created_by' => self::ID_START + 32, // Antoine PETIT
            ],
            [
                'club_id' => self::ID_START + 3,
                'club_name' => 'Raidlinks',
                'club_street' => '14 Place des Terrasses de l\'Agora',
                'club_city' => 'Évry',
                'club_postal_code' => '91000',
                'club_number' => '14',
                'ffso_id' => 'FFSO91001',
                'is_approved' => true,
                'created_by' => self::ID_START + 34, // Lucas BERNARD
            ],
            [
                'club_id' => self::ID_START + 4,
                'club_name' => 'VIKAZIM',
                'club_street' => '28 rue des bleuets',
                'club_city' => 'Caen',
                'club_postal_code' => '14000',
                'club_number' => '28',
                'ffso_id' => 'FFSO14001',
                'is_approved' => true,
                'created_by' => self::ID_START + 48, // Jean-François ANNE
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
                'description' => null,
                'club_image' => null,
                'is_approved' => $clubData['is_approved'],
                'approved_by' => $clubData['created_by'],
                'approved_at' => now(),
                'created_by' => $clubData['created_by'],
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
        // Create members for users (only for those with a license)
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
            ['adh_id' => self::ID_START + 39, 'name' => 'GARNIER', 'firstname' => 'Julie', 'club' => 'CO Azimut 77', 'licence' => '', 'birthday' => '1988-07-12', 'phone' => '0765901122'], // No license = not a member
            ['adh_id' => self::ID_START + 40, 'name' => 'LEROY', 'firstname' => 'Thomas', 'club' => 'CO Azimut 77', 'licence' => '6700548', 'birthday' => '1974-01-19', 'phone' => '0609883451'],
            ['adh_id' => self::ID_START + 41, 'name' => 'FONTAINE', 'firstname' => 'Hugo', 'club' => 'Balise 25', 'licence' => '91006754', 'birthday' => '2003-10-05', 'phone' => '0673849516'],
            ['adh_id' => self::ID_START + 42, 'name' => 'CARON', 'firstname' => 'Léa', 'club' => 'Balise 25', 'licence' => '', 'birthday' => '1990-04-27', 'phone' => '0614253647'], // No license = not a member
            ['adh_id' => self::ID_START + 43, 'name' => 'PETIT', 'firstname' => 'Emma', 'club' => 'Balise 25', 'licence' => '77009876', 'birthday' => '2005-12-08', 'phone' => '0621436587'],
            ['adh_id' => self::ID_START + 44, 'name' => 'ROUX', 'firstname' => 'Nathan', 'club' => 'Balise 25', 'licence' => '25006789', 'birthday' => '2000-01-26', 'phone' => '0734567812'],
            ['adh_id' => self::ID_START + 45, 'name' => 'DORBEC', 'firstname' => 'Paul', 'club' => 'CO AZIMUT 77', 'licence' => '23456789', 'birthday' => '1980-04-02', 'phone' => '0743672311'],
            ['adh_id' => self::ID_START + 46, 'name' => 'JACQUIER', 'firstname' => 'Yohann', 'club' => 'VIKAZIM', 'licence' => '', 'birthday' => '2013-06-03', 'phone' => '0642864628'], // No license = not a member
            ['adh_id' => self::ID_START + 47, 'name' => 'DELHOUMI', 'firstname' => 'Sylvian', 'club' => 'VIKAZIM', 'licence' => '2025-D2S1I3', 'birthday' => '1985-06-02', 'phone' => '0705324567'],
            ['adh_id' => self::ID_START + 48, 'name' => 'ANNE', 'firstname' => 'Jean-François', 'club' => 'VIKAZIM', 'licence' => '56723478', 'birthday' => '1964-11-05', 'phone' => '0645389485'],
        ];

        foreach ($membersData as $index => $member) {
            // Only create member entry if they have a license
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

        // Real addresses array
        $addresses = [
            '47 Allée des roses 77000 Melun',
            '36 Rue Claude Bernard 91000 Évry-Courcouronnes',
            '4 Rue du pré 25000 Besançon',
            '14 Rue des Mimosas 14000 Caen',
            '47 Allée des roses 77000 Melun',
            '52 Allée Saint-Joseph 77000 Melun',
            '7 Rue Henri Poincaré 25000 Besançon',
            '16 Rue des Azalées 91000 Évry-Courcouronnes',
            '12 Allée de la Fontaine 77000 Melun',
            '36 Rue du Jardin 77000 Melun',
            '24 Rue des Lilas 77000 Melun',
            '29 Rue René Descartes 25000 Besançon',
            '10 Rue des Acacias 25000 Besançon',
            '28 Rue Jean-Jacques Rousseau 25000 Besançon',
            '17 Rue des Orchidées 25000 Besançon',
            '36 Rue Claude Bernard 91000 Évry-Courcouronnes',
            '50 Rue des Fleurs 14000 Caen',
            '3 Rue Louis Pasteur 14000 Caen',
            '38 Rue des Marguerites 14000 Caen',
        ];

        // Create users
        $userIds = [];
        foreach ($membersData as $index => $member) {
            $userId = $member['adh_id'];
            // Check if user has a license (is a member)
            $isAdhérent = !empty($member['licence']);

            DB::table('users')->insertOrIgnore([
                'id' => $userId,
                'first_name' => $member['firstname'],
                'last_name' => $member['name'],
                'email' => strtolower($member['firstname'] . '.' . $member['name']) . '@example.com',
                'email_verified_at' => now(),
                'password' => '$2y$12$dnWSGCpdICbHsdeXidDJYepmhlOBg.bqNrMI3etC73Fyual6Dh8Wu',
                'doc_id' => null,
                'adh_id' => $isAdhérent ? $member['adh_id'] : null,
                'address' => $addresses[$index] ?? 'Demo Address ' . $userId,
                'birth_date' => $member['birthday'],
                'phone' => $member['phone'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $userIds[] = $userId;
        }

        return $userIds;
    }

    /**
     * Create demo teams (Equipes from Parcours B + LUTIN + ELFE)
     */
    private function createDemoTeams(): array
    {
        $teamsData = [
            // Parcours B teams (3 teams)
            ['equ_id' => self::ID_START + 50, 'equ_name' => 'Equipe DORMEUR', 'adh_id' => self::ID_START + 35], // Claire DUPONT (Responsable)
            ['equ_id' => self::ID_START + 51, 'equ_name' => 'Equipe ATCHOUM', 'adh_id' => self::ID_START + 41], // Hugo FONTAINE (Responsable)
            ['equ_id' => self::ID_START + 52, 'equ_name' => 'Equipe SIMPLET', 'adh_id' => self::ID_START + 41], // Hugo FONTAINE (Responsable)
            // Course LUTIN teams (3 teams)
            ['equ_id' => self::ID_START + 53, 'equ_name' => 'Equipe MARVELI', 'adh_id' => self::ID_START + 33], // Sandra MARVELI (Responsable)
            ['equ_id' => self::ID_START + 54, 'equ_name' => 'Equipe DUPONT', 'adh_id' => self::ID_START + 35], // Claire DUPONT (Responsable)
            ['equ_id' => self::ID_START + 55, 'equ_name' => 'Equipe MARTIN', 'adh_id' => self::ID_START + 30], // Julien MARTIN (Responsable)
            // Course ELFE teams (4 teams)
            ['equ_id' => self::ID_START + 56, 'equ_name' => 'Equipe LEFEBVRE', 'adh_id' => self::ID_START + 36], // Thomas LEFEBVRE (Responsable)
            ['equ_id' => self::ID_START + 57, 'equ_name' => 'Equipe MOREAU', 'adh_id' => self::ID_START + 37], // Sophie MOREAU (Responsable)
            ['equ_id' => self::ID_START + 58, 'equ_name' => 'Equipe FONTAINE', 'adh_id' => self::ID_START + 41], // Hugo FONTAINE (Responsable)
            ['equ_id' => self::ID_START + 59, 'equ_name' => 'Equipe PETIT', 'adh_id' => self::ID_START + 43], // Emma PETIT (Responsable)
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
     * Link users to teams via has_participate (10 teams: 3 Parcours B + 3 LUTIN + 4 ELFE)
     */
    private function linkUsersToTeams(array $userIds, array $teamIds): void
    {
        // Parcours B Teams
        // Team DORMEUR: Manager Claire DUPONT (635) + Participants: Claire DUPONT (635), Thomas LEFEBVRE (636)
        $team1Members = [self::ID_START + 35, self::ID_START + 36]; // Claire DUPONT, Thomas LEFEBVRE
        
        // Team ATCHOUM: Manager Hugo FONTAINE (641) + Participants: Hugo FONTAINE (641), Léa CARON (642)
        $team2Members = [self::ID_START + 41, self::ID_START + 42]; // Hugo FONTAINE, Léa CARON
        
        // Team SIMPLET: Manager Hugo FONTAINE (641) + Participants: Julie GARNIER (639), Marc ROUSSEAU (640 - using Thomas LEROY ID)
        $team3Members = [self::ID_START + 39, self::ID_START + 40]; // Julie GARNIER, Thomas LEROY

        // Course LUTIN Teams
        // Team MARVELI: Manager Sandra MARVELI (633) + Participant: Sandra MARVELI (633)
        $team4Members = [self::ID_START + 33]; // Sandra MARVELI
        
        // Equipe DUPONT: Responsable Claire DUPONT (635) + Participant: Claire DUPONT (635)
        $team5Members = [self::ID_START + 35]; // Claire DUPONT
        
        // Equipe MARTIN: Responsable Julien MARTIN (630) + Participant: Julien MARTIN (630)
        $team6Members = [self::ID_START + 30]; // Julien MARTIN

        // Course ELFE Teams
        // Equipe LEFEBVRE: Responsable Thomas LEFEBVRE (636) + Participant: Thomas LEFEBVRE (636)
        $team7Members = [self::ID_START + 36]; // Thomas LEFEBVRE
        
        // Equipe MOREAU: Responsable Sophie MOREAU (637) + Participant: Sophie MOREAU (637)
        $team8Members = [self::ID_START + 37]; // Sophie MOREAU
        
        // Equipe FONTAINE: Responsable Hugo FONTAINE (641) + Participant: Hugo FONTAINE (641)
        $team9Members = [self::ID_START + 41]; // Hugo FONTAINE
        
        // Equipe PETIT: Responsable Emma PETIT (643) + Participant: Emma PETIT (643)
        $team10Members = [self::ID_START + 43]; // Emma PETIT

        $assignments = [
            [$teamIds[0], $team1Members],  // Equipe DORMEUR
            [$teamIds[1], $team2Members],  // Equipe ATCHOUM
            [$teamIds[2], $team3Members],  // Equipe SIMPLET
            [$teamIds[3], $team4Members],  // Equipe MARVELI
            [$teamIds[4], $team5Members],  // Equipe DUPONT
            [$teamIds[5], $team6Members],  // Equipe MARTIN
            [$teamIds[6], $team7Members],  // Equipe LEFEBVRE
            [$teamIds[7], $team8Members],  // Equipe MOREAU
            [$teamIds[8], $team9Members],  // Equipe FONTAINE
            [$teamIds[9], $team10Members], // Equipe PETIT
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