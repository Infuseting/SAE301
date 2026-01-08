-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: mysql:3306
-- Generation Time: Jan 08, 2026 at 09:38 AM
-- Server version: 8.0.44
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `SAE301`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` bigint UNSIGNED NOT NULL,
  `log_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint UNSIGNED DEFAULT NULL,
  `causer_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `causer_id` bigint UNSIGNED DEFAULT NULL,
  `properties` json DEFAULT NULL,
  `batch_uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `age_categories`
--

CREATE TABLE `age_categories` (
  `id` bigint UNSIGNED NOT NULL,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `age_min` int NOT NULL,
  `age_max` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clubs`
--

CREATE TABLE `clubs` (
  `club_id` bigint UNSIGNED NOT NULL,
  `club_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `club_street` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `club_city` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `club_postal_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ffso_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'FFSO club ID',
  `description` text COLLATE utf8mb4_unicode_ci,
  `club_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Club logo/image path',
  `is_approved` tinyint(1) NOT NULL DEFAULT '0',
  `approved_by` bigint UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `club_invitations`
--

CREATE TABLE `club_invitations` (
  `id` bigint UNSIGNED NOT NULL,
  `club_id` bigint UNSIGNED NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `invited_by` bigint UNSIGNED NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'member',
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `club_user`
--

CREATE TABLE `club_user` (
  `id` bigint UNSIGNED NOT NULL,
  `club_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `role` enum('member','manager') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'member',
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `connected_accounts`
--

CREATE TABLE `connected_accounts` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `provider` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `secret` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `refresh_token` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `has_category`
--

CREATE TABLE `has_category` (
  `catpd_id` bigint UNSIGNED NOT NULL,
  `race_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `has_licence`
--

CREATE TABLE `has_licence` (
  `adh_id` bigint UNSIGNED NOT NULL,
  `club_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `has_participate`
--

CREATE TABLE `has_participate` (
  `id` bigint UNSIGNED NOT NULL,
  `adh_id` bigint UNSIGNED DEFAULT NULL,
  `equ_id` bigint UNSIGNED NOT NULL,
  `reg_id` bigint UNSIGNED DEFAULT NULL,
  `par_time` time DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `id_users` bigint UNSIGNED DEFAULT NULL,
  `is_leader` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inscriptions_payment`
--

CREATE TABLE `inscriptions_payment` (
  `pai_id` bigint UNSIGNED NOT NULL,
  `pai_date` date NOT NULL,
  `pai_is_paid` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leaderboards`
--

CREATE TABLE `leaderboards` (
  `cla_id` bigint UNSIGNED NOT NULL,
  `cla_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leaderboard_teams`
--

CREATE TABLE `leaderboard_teams` (
  `id` bigint UNSIGNED NOT NULL,
  `equ_id` bigint UNSIGNED NOT NULL,
  `race_id` bigint UNSIGNED NOT NULL,
  `average_temps` decimal(10,2) NOT NULL,
  `average_malus` decimal(10,2) DEFAULT '0.00',
  `average_temps_final` decimal(10,2) NOT NULL,
  `member_count` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leaderboard_users`
--

CREATE TABLE `leaderboard_users` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `race_id` bigint UNSIGNED NOT NULL,
  `temps` decimal(10,2) NOT NULL,
  `malus` decimal(10,2) DEFAULT '0.00',
  `temps_final` decimal(10,2) GENERATED ALWAYS AS ((`temps` + `malus`)) STORED,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medical_docs`
--

CREATE TABLE `medical_docs` (
  `doc_id` bigint UNSIGNED NOT NULL,
  `doc_num_pps` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `doc_end_validity` date NOT NULL,
  `doc_date_added` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `adh_id` bigint UNSIGNED NOT NULL,
  `adh_license` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `adh_end_validity` date NOT NULL,
  `adh_date_added` date NOT NULL,
  `id_users` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint UNSIGNED NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint UNSIGNED NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `param_categorie_age`
--

CREATE TABLE `param_categorie_age` (
  `id` bigint UNSIGNED NOT NULL,
  `race_id` bigint UNSIGNED NOT NULL,
  `age_categorie_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `param_runners`
--

CREATE TABLE `param_runners` (
  `pac_id` bigint UNSIGNED NOT NULL,
  `pac_nb_min` int NOT NULL,
  `pac_nb_max` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `param_teams`
--

CREATE TABLE `param_teams` (
  `pae_id` bigint UNSIGNED NOT NULL,
  `pae_nb_min` int NOT NULL,
  `pae_nb_max` int NOT NULL,
  `pae_team_count_max` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `param_type`
--

CREATE TABLE `param_type` (
  `typ_id` bigint UNSIGNED NOT NULL,
  `typ_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint UNSIGNED NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `price_age_category`
--

CREATE TABLE `price_age_category` (
  `catp_id` bigint UNSIGNED NOT NULL,
  `catp_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `catp_price` decimal(8,2) NOT NULL,
  `age_min` int NOT NULL,
  `age_max` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `races`
--

CREATE TABLE `races` (
  `race_id` bigint UNSIGNED NOT NULL,
  `race_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `race_description` text COLLATE utf8mb4_unicode_ci,
  `race_date_start` datetime NOT NULL,
  `race_date_end` datetime NOT NULL,
  `race_reduction` double DEFAULT NULL,
  `race_meal_price` double DEFAULT NULL,
  `price_major` decimal(10,2) DEFAULT NULL,
  `price_minor` decimal(10,2) DEFAULT NULL,
  `price_adherent` decimal(10,2) DEFAULT NULL,
  `race_duration_minutes` double DEFAULT NULL,
  `raid_id` bigint UNSIGNED DEFAULT NULL,
  `cla_id` bigint UNSIGNED DEFAULT NULL,
  `adh_id` bigint UNSIGNED NOT NULL,
  `pac_id` bigint UNSIGNED NOT NULL,
  `pae_id` bigint UNSIGNED NOT NULL,
  `race_difficulty` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `typ_id` bigint UNSIGNED NOT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `race_registrations`
--

CREATE TABLE `race_registrations` (
  `reg_id` bigint UNSIGNED NOT NULL,
  `race_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `equ_id` bigint UNSIGNED DEFAULT NULL,
  `is_temporary_team` tinyint(1) NOT NULL DEFAULT '0',
  `temporary_team_data` json DEFAULT NULL,
  `is_creator_participating` tinyint(1) NOT NULL DEFAULT '1',
  `is_team_leader` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('pending','confirmed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `amount_paid` decimal(10,2) DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `raids`
--

CREATE TABLE `raids` (
  `raid_id` bigint UNSIGNED NOT NULL,
  `raid_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `raid_description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `adh_id` bigint UNSIGNED NOT NULL,
  `clu_id` bigint UNSIGNED NOT NULL,
  `ins_id` bigint UNSIGNED NOT NULL,
  `raid_date_start` datetime NOT NULL,
  `raid_date_end` datetime NOT NULL,
  `raid_contact` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `raid_site_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `raid_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `raid_street` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `raid_city` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `raid_postal_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `raid_number` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registration`
--

CREATE TABLE `registration` (
  `reg_id` bigint UNSIGNED NOT NULL,
  `equ_id` bigint UNSIGNED NOT NULL,
  `race_id` bigint UNSIGNED NOT NULL,
  `pay_id` bigint UNSIGNED NOT NULL,
  `doc_id` bigint UNSIGNED NOT NULL,
  `reg_points` double NOT NULL DEFAULT '0',
  `reg_validated` tinyint(1) NOT NULL DEFAULT '0',
  `reg_dossard` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registration_period`
--

CREATE TABLE `registration_period` (
  `ins_id` bigint UNSIGNED NOT NULL,
  `ins_start_date` datetime NOT NULL,
  `ins_end_date` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint UNSIGNED NOT NULL,
  `role_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `equ_id` bigint UNSIGNED NOT NULL,
  `equ_name` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `equ_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `team_invitations`
--

CREATE TABLE `team_invitations` (
  `id` bigint UNSIGNED NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `equ_id` bigint UNSIGNED NOT NULL,
  `race_id` bigint UNSIGNED NOT NULL,
  `invited_by` bigint UNSIGNED NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','accepted','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `time`
--

CREATE TABLE `time` (
  `user_id` bigint UNSIGNED NOT NULL,
  `race_id` bigint UNSIGNED NOT NULL,
  `time_hours` double NOT NULL,
  `time_minutes` double NOT NULL,
  `time_seconds` double NOT NULL,
  `time_total_seconds` double NOT NULL,
  `time_rank` int NOT NULL,
  `time_rank_start` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `doc_id` bigint UNSIGNED DEFAULT NULL,
  `adh_id` bigint UNSIGNED DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `birth_date` date DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `two_factor_secret` text COLLATE utf8mb4_unicode_ci,
  `two_factor_recovery_codes` text COLLATE utf8mb4_unicode_ci,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_team_id` bigint UNSIGNED DEFAULT NULL,
  `profile_photo_path` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `password_is_set` tinyint(1) NOT NULL DEFAULT '1',
  `is_public` tinyint(1) NOT NULL DEFAULT '0',
  `description` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject` (`subject_type`,`subject_id`),
  ADD KEY `causer` (`causer_type`,`causer_id`),
  ADD KEY `activity_log_log_name_index` (`log_name`);

--
-- Indexes for table `age_categories`
--
ALTER TABLE `age_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `clubs`
--
ALTER TABLE `clubs`
  ADD PRIMARY KEY (`club_id`),
  ADD KEY `clubs_created_by_foreign` (`created_by`),
  ADD KEY `clubs_approved_by_foreign` (`approved_by`);

--
-- Indexes for table `club_invitations`
--
ALTER TABLE `club_invitations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `club_invitations_club_id_email_unique` (`club_id`,`email`),
  ADD UNIQUE KEY `club_invitations_token_unique` (`token`),
  ADD KEY `club_invitations_invited_by_foreign` (`invited_by`);

--
-- Indexes for table `club_user`
--
ALTER TABLE `club_user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `club_user_club_id_user_id_unique` (`club_id`,`user_id`),
  ADD KEY `club_user_user_id_foreign` (`user_id`);

--
-- Indexes for table `connected_accounts`
--
ALTER TABLE `connected_accounts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `has_category`
--
ALTER TABLE `has_category`
  ADD KEY `has_category_catpd_id_foreign` (`catpd_id`),
  ADD KEY `has_category_race_id_foreign` (`race_id`);

--
-- Indexes for table `has_licence`
--
ALTER TABLE `has_licence`
  ADD KEY `has_licence_adh_id_foreign` (`adh_id`),
  ADD KEY `has_licence_club_id_foreign` (`club_id`);

--
-- Indexes for table `has_participate`
--
ALTER TABLE `has_participate`
  ADD PRIMARY KEY (`id`),
  ADD KEY `has_participate_equ_id_foreign` (`equ_id`),
  ADD KEY `has_participate_adh_id_foreign` (`adh_id`),
  ADD KEY `has_participate_id_users_foreign` (`id_users`);

--
-- Indexes for table `inscriptions_payment`
--
ALTER TABLE `inscriptions_payment`
  ADD PRIMARY KEY (`pai_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `leaderboards`
--
ALTER TABLE `leaderboards`
  ADD PRIMARY KEY (`cla_id`);

--
-- Indexes for table `leaderboard_teams`
--
ALTER TABLE `leaderboard_teams`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_team_race` (`equ_id`,`race_id`),
  ADD KEY `race_id` (`race_id`);

--
-- Indexes for table `leaderboard_users`
--
ALTER TABLE `leaderboard_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_runner_race` (`user_id`,`race_id`),
  ADD KEY `race_id` (`race_id`);

--
-- Indexes for table `medical_docs`
--
ALTER TABLE `medical_docs`
  ADD PRIMARY KEY (`doc_id`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`adh_id`),
  ADD KEY `members_id_users_foreign` (`id_users`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `param_categorie_age`
--
ALTER TABLE `param_categorie_age`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `param_categorie_age_race_id_age_categorie_id_unique` (`race_id`,`age_categorie_id`),
  ADD KEY `param_categorie_age_age_categorie_id_foreign` (`age_categorie_id`);

--
-- Indexes for table `param_runners`
--
ALTER TABLE `param_runners`
  ADD PRIMARY KEY (`pac_id`);

--
-- Indexes for table `param_teams`
--
ALTER TABLE `param_teams`
  ADD PRIMARY KEY (`pae_id`);

--
-- Indexes for table `param_type`
--
ALTER TABLE `param_type`
  ADD PRIMARY KEY (`typ_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  ADD KEY `personal_access_tokens_expires_at_index` (`expires_at`);

--
-- Indexes for table `price_age_category`
--
ALTER TABLE `price_age_category`
  ADD PRIMARY KEY (`catp_id`);

--
-- Indexes for table `races`
--
ALTER TABLE `races`
  ADD PRIMARY KEY (`race_id`),
  ADD KEY `races_raid_id_foreign` (`raid_id`),
  ADD KEY `races_cla_id_foreign` (`cla_id`),
  ADD KEY `races_adh_id_foreign` (`adh_id`),
  ADD KEY `races_pac_id_foreign` (`pac_id`),
  ADD KEY `races_pae_id_foreign` (`pae_id`),
  ADD KEY `races_typ_id_foreign` (`typ_id`);

--
-- Indexes for table `race_registrations`
--
ALTER TABLE `race_registrations`
  ADD PRIMARY KEY (`reg_id`),
  ADD UNIQUE KEY `unique_race_user_registration` (`race_id`,`user_id`),
  ADD KEY `race_registrations_user_id_foreign` (`user_id`),
  ADD KEY `race_registrations_equ_id_foreign` (`equ_id`);

--
-- Indexes for table `raids`
--
ALTER TABLE `raids`
  ADD PRIMARY KEY (`raid_id`),
  ADD KEY `raids_adh_id_foreign` (`adh_id`),
  ADD KEY `raids_clu_id_foreign` (`clu_id`),
  ADD KEY `raids_ins_id_foreign` (`ins_id`);

--
-- Indexes for table `registration`
--
ALTER TABLE `registration`
  ADD PRIMARY KEY (`reg_id`),
  ADD KEY `registration_equ_id_foreign` (`equ_id`),
  ADD KEY `registration_race_id_foreign` (`race_id`),
  ADD KEY `registration_pay_id_foreign` (`pay_id`),
  ADD KEY `registration_doc_id_foreign` (`doc_id`);

--
-- Indexes for table `registration_period`
--
ALTER TABLE `registration_period`
  ADD PRIMARY KEY (`ins_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`equ_id`),
  ADD KEY `teams_user_id_foreign` (`user_id`);

--
-- Indexes for table `team_invitations`
--
ALTER TABLE `team_invitations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_email_race_invitation` (`email`,`race_id`),
  ADD UNIQUE KEY `team_invitations_token_unique` (`token`),
  ADD KEY `team_invitations_equ_id_foreign` (`equ_id`),
  ADD KEY `team_invitations_race_id_foreign` (`race_id`),
  ADD KEY `team_invitations_invited_by_foreign` (`invited_by`);

--
-- Indexes for table `time`
--
ALTER TABLE `time`
  ADD PRIMARY KEY (`user_id`,`race_id`),
  ADD KEY `time_race_id_foreign` (`race_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_doc_id_foreign` (`doc_id`),
  ADD KEY `users_adh_id_foreign` (`adh_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `age_categories`
--
ALTER TABLE `age_categories`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clubs`
--
ALTER TABLE `clubs`
  MODIFY `club_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `club_invitations`
--
ALTER TABLE `club_invitations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `club_user`
--
ALTER TABLE `club_user`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `connected_accounts`
--
ALTER TABLE `connected_accounts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `has_participate`
--
ALTER TABLE `has_participate`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inscriptions_payment`
--
ALTER TABLE `inscriptions_payment`
  MODIFY `pai_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leaderboards`
--
ALTER TABLE `leaderboards`
  MODIFY `cla_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leaderboard_teams`
--
ALTER TABLE `leaderboard_teams`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leaderboard_users`
--
ALTER TABLE `leaderboard_users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medical_docs`
--
ALTER TABLE `medical_docs`
  MODIFY `doc_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `adh_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `param_categorie_age`
--
ALTER TABLE `param_categorie_age`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `param_runners`
--
ALTER TABLE `param_runners`
  MODIFY `pac_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `param_teams`
--
ALTER TABLE `param_teams`
  MODIFY `pae_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `param_type`
--
ALTER TABLE `param_type`
  MODIFY `typ_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `price_age_category`
--
ALTER TABLE `price_age_category`
  MODIFY `catp_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `races`
--
ALTER TABLE `races`
  MODIFY `race_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `race_registrations`
--
ALTER TABLE `race_registrations`
  MODIFY `reg_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `raids`
--
ALTER TABLE `raids`
  MODIFY `raid_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registration`
--
ALTER TABLE `registration`
  MODIFY `reg_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registration_period`
--
ALTER TABLE `registration_period`
  MODIFY `ins_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `equ_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `team_invitations`
--
ALTER TABLE `team_invitations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `clubs`
--
ALTER TABLE `clubs`
  ADD CONSTRAINT `clubs_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `clubs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `club_invitations`
--
ALTER TABLE `club_invitations`
  ADD CONSTRAINT `club_invitations_club_id_foreign` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`club_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `club_invitations_invited_by_foreign` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `club_user`
--
ALTER TABLE `club_user`
  ADD CONSTRAINT `club_user_club_id_foreign` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`club_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `club_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `has_category`
--
ALTER TABLE `has_category`
  ADD CONSTRAINT `has_category_catpd_id_foreign` FOREIGN KEY (`catpd_id`) REFERENCES `price_age_category` (`catp_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `has_category_race_id_foreign` FOREIGN KEY (`race_id`) REFERENCES `races` (`race_id`) ON DELETE CASCADE;

--
-- Constraints for table `has_licence`
--
ALTER TABLE `has_licence`
  ADD CONSTRAINT `has_licence_adh_id_foreign` FOREIGN KEY (`adh_id`) REFERENCES `members` (`adh_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `has_licence_club_id_foreign` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`club_id`) ON DELETE CASCADE;

--
-- Constraints for table `has_participate`
--
ALTER TABLE `has_participate`
  ADD CONSTRAINT `has_participate_adh_id_foreign` FOREIGN KEY (`adh_id`) REFERENCES `members` (`adh_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `has_participate_equ_id_foreign` FOREIGN KEY (`equ_id`) REFERENCES `teams` (`equ_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `has_participate_id_users_foreign` FOREIGN KEY (`id_users`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leaderboard_teams`
--
ALTER TABLE `leaderboard_teams`
  ADD CONSTRAINT `leaderboard_teams_ibfk_1` FOREIGN KEY (`equ_id`) REFERENCES `teams` (`equ_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leaderboard_teams_ibfk_2` FOREIGN KEY (`race_id`) REFERENCES `races` (`race_id`) ON DELETE CASCADE;

--
-- Constraints for table `leaderboard_users`
--
ALTER TABLE `leaderboard_users`
  ADD CONSTRAINT `leaderboard_users_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leaderboard_users_ibfk_2` FOREIGN KEY (`race_id`) REFERENCES `races` (`race_id`) ON DELETE CASCADE;

--
-- Constraints for table `members`
--
ALTER TABLE `members`
  ADD CONSTRAINT `members_id_users_foreign` FOREIGN KEY (`id_users`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `param_categorie_age`
--
ALTER TABLE `param_categorie_age`
  ADD CONSTRAINT `param_categorie_age_age_categorie_id_foreign` FOREIGN KEY (`age_categorie_id`) REFERENCES `age_categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `param_categorie_age_race_id_foreign` FOREIGN KEY (`race_id`) REFERENCES `races` (`race_id`) ON DELETE CASCADE;

--
-- Constraints for table `races`
--
ALTER TABLE `races`
  ADD CONSTRAINT `races_adh_id_foreign` FOREIGN KEY (`adh_id`) REFERENCES `members` (`adh_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `races_cla_id_foreign` FOREIGN KEY (`cla_id`) REFERENCES `leaderboards` (`cla_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `races_pac_id_foreign` FOREIGN KEY (`pac_id`) REFERENCES `param_runners` (`pac_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `races_pae_id_foreign` FOREIGN KEY (`pae_id`) REFERENCES `param_teams` (`pae_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `races_raid_id_foreign` FOREIGN KEY (`raid_id`) REFERENCES `raids` (`raid_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `races_typ_id_foreign` FOREIGN KEY (`typ_id`) REFERENCES `param_type` (`typ_id`) ON DELETE CASCADE;

--
-- Constraints for table `race_registrations`
--
ALTER TABLE `race_registrations`
  ADD CONSTRAINT `race_registrations_equ_id_foreign` FOREIGN KEY (`equ_id`) REFERENCES `teams` (`equ_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `race_registrations_race_id_foreign` FOREIGN KEY (`race_id`) REFERENCES `races` (`race_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `race_registrations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `raids`
--
ALTER TABLE `raids`
  ADD CONSTRAINT `raids_adh_id_foreign` FOREIGN KEY (`adh_id`) REFERENCES `members` (`adh_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `raids_clu_id_foreign` FOREIGN KEY (`clu_id`) REFERENCES `clubs` (`club_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `raids_ins_id_foreign` FOREIGN KEY (`ins_id`) REFERENCES `registration_period` (`ins_id`) ON DELETE CASCADE;

--
-- Constraints for table `registration`
--
ALTER TABLE `registration`
  ADD CONSTRAINT `registration_doc_id_foreign` FOREIGN KEY (`doc_id`) REFERENCES `medical_docs` (`doc_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `registration_equ_id_foreign` FOREIGN KEY (`equ_id`) REFERENCES `teams` (`equ_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `registration_pay_id_foreign` FOREIGN KEY (`pay_id`) REFERENCES `inscriptions_payment` (`pai_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `registration_race_id_foreign` FOREIGN KEY (`race_id`) REFERENCES `races` (`race_id`) ON DELETE CASCADE;

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teams`
--
ALTER TABLE `teams`
  ADD CONSTRAINT `teams_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `team_invitations`
--
ALTER TABLE `team_invitations`
  ADD CONSTRAINT `team_invitations_equ_id_foreign` FOREIGN KEY (`equ_id`) REFERENCES `teams` (`equ_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `team_invitations_invited_by_foreign` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `team_invitations_race_id_foreign` FOREIGN KEY (`race_id`) REFERENCES `races` (`race_id`) ON DELETE CASCADE;

--
-- Constraints for table `time`
--
ALTER TABLE `time`
  ADD CONSTRAINT `time_race_id_foreign` FOREIGN KEY (`race_id`) REFERENCES `races` (`race_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `time_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_adh_id_foreign` FOREIGN KEY (`adh_id`) REFERENCES `members` (`adh_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `users_doc_id_foreign` FOREIGN KEY (`doc_id`) REFERENCES `medical_docs` (`doc_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
