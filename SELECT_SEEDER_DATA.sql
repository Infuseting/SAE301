-- ============================================================
-- SAE301 - SELECT Queries for DemoDataSeeder Verification
-- ============================================================
-- Execute these queries to view all data inserted by DemoDataSeeder
-- ID Range: 600-700
-- ============================================================

-- ============================================================
-- 1. MEMBERS (Adhérents) - IDs 630-648
-- ============================================================
SELECT 
    adh_id,
    adh_license,
    adh_end_validity,
    adh_date_added,
    created_at
FROM `members`
WHERE `adh_id` BETWEEN 630 AND 648
ORDER BY `adh_id`;

-- ============================================================
-- 2. MEDICAL DOCUMENTS - IDs 660-680
-- ============================================================
SELECT 
    doc_id,
    doc_num_pps,
    doc_end_validity,
    doc_date_added,
    created_at
FROM `medical_docs`
WHERE `doc_id` BETWEEN 660 AND 680
ORDER BY `doc_id`;

-- ============================================================
-- 3. USERS (Utilisateurs) - IDs 630-648
-- ============================================================
SELECT 
    id,
    first_name,
    last_name,
    email,
    birth_date,
    address,
    phone,
    active,
    adh_id,
    doc_id,
    email_verified_at
FROM `users`
WHERE `id` BETWEEN 630 AND 648
ORDER BY `id`;

-- ============================================================
-- 4. CLUBS - IDs 601-604
-- ============================================================
SELECT 
    club_id,
    club_name,
    club_street,
    club_city,
    club_postal_code,
    ffso_id,
    description,
    is_approved,
    created_by,
    approved_by,
    approved_at,
    created_at
FROM `clubs`
WHERE `club_id` BETWEEN 601 AND 604
ORDER BY `club_id`;

-- ============================================================
-- 5. TEAMS (Équipes) - IDs 650-652
-- ============================================================
SELECT 
    t.equ_id,
    t.equ_name,
    t.user_id,
    t.adh_id,
    CONCAT(u.first_name, ' ', u.last_name) as responsible_name,
    t.created_at
FROM `teams` t
LEFT JOIN `users` u ON t.user_id = u.id
WHERE t.equ_id BETWEEN 650 AND 652
ORDER BY t.equ_id;

-- ============================================================
-- 6. TEAM PARTICIPANTS (has_participate)
-- ============================================================
SELECT 
    hp.equ_id,
    t.equ_name,
    hp.adh_id,
    CONCAT(u.first_name, ' ', u.last_name) as participant_name,
    u.email,
    hp.par_time,
    hp.created_at
FROM `has_participate` hp
JOIN `teams` t ON hp.equ_id = t.equ_id
LEFT JOIN `users` u ON hp.id_users = u.id
WHERE hp.equ_id BETWEEN 650 AND 652
ORDER BY hp.equ_id, hp.adh_id;

-- ============================================================
-- 7. RAIDS - IDs 610-611
-- ============================================================
SELECT 
    r.raid_id,
    r.raid_name,
    r.raid_description,
    r.raid_date_start,
    r.raid_date_end,
    r.raid_city,
    r.raid_postal_code,
    r.raid_contact,
    r.raid_site_url,
    r.clu_id,
    c.club_name,
    r.adh_id,
    CONCAT(u.first_name, ' ', u.last_name) as organizer_name,
    r.created_at
FROM `raids` r
LEFT JOIN `clubs` c ON r.clu_id = c.club_id
LEFT JOIN `members` m ON r.adh_id = m.adh_id
LEFT JOIN `users` u ON m.adh_id = u.adh_id
WHERE r.raid_id BETWEEN 610 AND 611
ORDER BY r.raid_id;

-- ============================================================
-- 8. RACES (Courses) - IDs 620-623
-- ============================================================
SELECT 
    rc.race_id,
    rc.race_name,
    rc.race_date_start,
    rc.race_date_end,
    rc.race_duration_minutes,
    rc.race_meal_price,
    rc.race_reduction,
    rd.raid_name,
    CONCAT(u.first_name, ' ', u.last_name) as organizer_name,
    rc.created_at
FROM `races` rc
LEFT JOIN `raids` rd ON rc.raid_id = rd.raid_id
LEFT JOIN `members` m ON rc.adh_id = m.adh_id
LEFT JOIN `users` u ON m.adh_id = u.adh_id
WHERE rc.race_id BETWEEN 620 AND 623
ORDER BY rc.race_id;

-- ============================================================
-- 9. TIMES/RESULTS (time) - IDs 670+
-- ============================================================
SELECT 
    t.user_id,
    CONCAT(u.first_name, ' ', u.last_name) as runner_name,
    t.race_id,
    rc.race_name,
    t.time_hours,
    t.time_minutes,
    t.time_seconds,
    t.time_total_seconds,
    t.time_rank,
    t.time_rank_start,
    t.created_at
FROM `time` t
LEFT JOIN `users` u ON t.user_id = u.id
LEFT JOIN `races` rc ON t.race_id = rc.race_id
WHERE t.user_id BETWEEN 630 AND 648
  AND t.race_id BETWEEN 620 AND 623
ORDER BY t.race_id, t.time_rank;

-- ============================================================
-- 10. STATISTICS SUMMARY
-- ============================================================
SELECT 
    'Members' as table_name,
    COUNT(*) as record_count
FROM `members`
WHERE `adh_id` BETWEEN 630 AND 648
UNION ALL
SELECT 
    'Medical Docs',
    COUNT(*)
FROM `medical_docs`
WHERE `doc_id` BETWEEN 660 AND 680
UNION ALL
SELECT 
    'Users',
    COUNT(*)
FROM `users`
WHERE `id` BETWEEN 630 AND 648
UNION ALL
SELECT 
    'Clubs',
    COUNT(*)
FROM `clubs`
WHERE `club_id` BETWEEN 601 AND 604
UNION ALL
SELECT 
    'Teams',
    COUNT(*)
FROM `teams`
WHERE `equ_id` BETWEEN 650 AND 652
UNION ALL
SELECT 
    'Team Participants',
    COUNT(*)
FROM `has_participate`
WHERE `equ_id` BETWEEN 650 AND 652
UNION ALL
SELECT 
    'Raids',
    COUNT(*)
FROM `raids`
WHERE `raid_id` BETWEEN 610 AND 611
UNION ALL
SELECT 
    'Races',
    COUNT(*)
FROM `races`
WHERE `race_id` BETWEEN 620 AND 623
UNION ALL
SELECT 
    'Times/Results',
    COUNT(*)
FROM `time`
WHERE `user_id` BETWEEN 630 AND 648
  AND `race_id` BETWEEN 620 AND 623;

-- ============================================================
-- 11. DETAILED VIEW: Raids with their Races
-- ============================================================
SELECT 
    rd.raid_id,
    rd.raid_name,
    rd.raid_city,
    rd.raid_date_start,
    rd.raid_date_end,
    CONCAT(u_raid.first_name, ' ', u_raid.last_name) as raid_organizer,
    rc.race_id,
    rc.race_name,
    rc.race_date_start,
    rc.race_date_end,
    rc.race_duration_minutes,
    CONCAT(u_race.first_name, ' ', u_race.last_name) as race_organizer
FROM `raids` rd
LEFT JOIN `races` rc ON rd.raid_id = rc.raid_id
LEFT JOIN `members` m_raid ON rd.adh_id = m_raid.adh_id
LEFT JOIN `users` u_raid ON m_raid.adh_id = u_raid.adh_id
LEFT JOIN `members` m_race ON rc.adh_id = m_race.adh_id
LEFT JOIN `users` u_race ON m_race.adh_id = u_race.adh_id
WHERE rd.raid_id BETWEEN 610 AND 611
ORDER BY rd.raid_id, rc.race_id;

-- ============================================================
-- 12. DETAILED VIEW: Teams with their Participants
-- ============================================================
SELECT 
    t.equ_id,
    t.equ_name,
    CONCAT(u_resp.first_name, ' ', u_resp.last_name) as team_responsible,
    CONCAT(u_part.first_name, ' ', u_part.last_name) as participant_name,
    u_part.email as participant_email,
    u_part.phone as participant_phone
FROM `teams` t
LEFT JOIN `users` u_resp ON t.user_id = u_resp.id
LEFT JOIN `has_participate` hp ON t.equ_id = hp.equ_id
LEFT JOIN `users` u_part ON hp.id_users = u_part.id
WHERE t.equ_id BETWEEN 650 AND 652
ORDER BY t.equ_id, participant_name;

-- ============================================================
-- 13. DETAILED VIEW: Clubs with their Raids
-- ============================================================
SELECT 
    c.club_id,
    c.club_name,
    c.club_city,
    c.ffso_id,
    r.raid_id,
    r.raid_name,
    r.raid_date_start,
    CONCAT(u.first_name, ' ', u.last_name) as raid_organizer
FROM `clubs` c
LEFT JOIN `raids` r ON c.club_id = r.clu_id
LEFT JOIN `members` m ON r.adh_id = m.adh_id
LEFT JOIN `users` u ON m.adh_id = u.adh_id
WHERE c.club_id BETWEEN 601 AND 604
ORDER BY c.club_id, r.raid_date_start;

-- ============================================================
-- 14. LEADERBOARD: Top 10 Times per Race
-- ============================================================
SELECT 
    rc.race_name,
    CONCAT(u.first_name, ' ', u.last_name) as runner_name,
    CONCAT(
        LPAD(t.time_hours, 2, '0'), ':',
        LPAD(t.time_minutes, 2, '0'), ':',
        LPAD(t.time_seconds, 2, '0')
    ) as time_formatted,
    t.time_total_seconds,
    t.time_rank
FROM `time` t
JOIN `users` u ON t.user_id = u.id
JOIN `races` rc ON t.race_id = rc.race_id
WHERE t.race_id BETWEEN 620 AND 623
  AND t.time_rank <= 10
ORDER BY rc.race_id, t.time_rank;

-- ============================================================
-- 15. USERS WITH ALL THEIR DETAILS
-- ============================================================
SELECT 
    u.id,
    CONCAT(u.first_name, ' ', u.last_name) as full_name,
    u.email,
    u.phone,
    u.birth_date,
    u.address,
    u.active,
    m.adh_license,
    m.adh_end_validity,
    md.doc_num_pps,
    md.doc_end_validity,
    GROUP_CONCAT(DISTINCT t.equ_name ORDER BY t.equ_name SEPARATOR ', ') as teams
FROM `users` u
LEFT JOIN `members` m ON u.adh_id = m.adh_id
LEFT JOIN `medical_docs` md ON u.doc_id = md.doc_id
LEFT JOIN `has_participate` hp ON u.id = hp.id_users
LEFT JOIN `teams` t ON hp.equ_id = t.equ_id
WHERE u.id BETWEEN 630 AND 648
GROUP BY u.id
ORDER BY u.id;

-- ============================================================
-- 16. PARAM TABLES (Default IDs used by seeder)
-- ============================================================
-- Param Runners
SELECT 
    pac_id,
    pac_nb_min,
    pac_nb_max,
    created_at
FROM `param_runners`
WHERE pac_id = 1;

-- Param Teams
SELECT 
    pae_id,
    pae_nb_min,
    pae_nb_max,
    pae_team_count_max,
    created_at
FROM `param_teams`
WHERE pae_id = 1;

-- Param Type
SELECT 
    typ_id,
    typ_name,
    created_at
FROM `param_type`
WHERE typ_id = 1;

-- Registration Period
SELECT 
    ins_id,
    ins_start_date,
    ins_end_date,
    created_at
FROM `registration_period`
WHERE ins_id = 1;
