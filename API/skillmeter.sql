-- phpMyAdmin SQL Dump
-- version 4.9.5deb2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 27, 2025 at 07:15 AM
-- Server version: 8.0.42-0ubuntu0.20.04.1
-- PHP Version: 8.3.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `skillmeter`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `activity_type` varchar(50)  NOT NULL,
  `activity_details` text ,
  `ip_address` varchar(45)  DEFAULT NULL,
  `user_agent` text ,
  `base_lang` varchar(5)  NOT NULL DEFAULT 'en',
  `active` varchar(5)  NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB ;

-- --------------------------------------------------------

--
-- Table structure for table `ai_generated_content`
--

DROP TABLE IF EXISTS `ai_generated_content`;
CREATE TABLE `ai_generated_content` (
  `id` int UNSIGNED NOT NULL,
  `request_id` int UNSIGNED NOT NULL,
  `question_id` int UNSIGNED DEFAULT NULL,
  `content_type` enum('question','option','explanation','feedback')  NOT NULL,
  `content_text` text  NOT NULL,
  `metadata` json DEFAULT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT '0',
  `base_lang` varchar(5)  NOT NULL DEFAULT 'en',
  `active` varchar(5)  NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB ;

--
-- Dumping data for table `ai_generated_content`
--

INSERT INTO `ai_generated_content` (`id`, `request_id`, `question_id`, `content_type`, `content_text`, `metadata`, `is_used`, `base_lang`, `active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, NULL, 'question', 'Which Python keyword is used to define a function?', '{\"topic\": \"Python Basics\", \"difficulty\": \"Beginner\", \"model_version\": \"gpt-4\", \"generation_time\": 2.4}', 1, 'en', '1', '2025-07-26 06:37:30', NULL, NULL),
(2, 1, 101, 'option', 'def', '{\"is_correct\": true, \"option_order\": 1}', 0, 'en', '1', '2025-07-26 06:37:30', NULL, NULL),
(3, 1, 101, 'option', 'function', '{\"is_correct\": false, \"option_order\": 2}', 0, 'en', '1', '2025-07-26 06:37:30', NULL, NULL),
(4, 1, 101, 'option', 'define', '{\"is_correct\": false, \"option_order\": 3}', 0, 'en', '1', '2025-07-26 06:37:30', NULL, NULL),
(5, 1, 101, 'option', 'func', '{\"is_correct\": false, \"option_order\": 4}', 0, 'en', '1', '2025-07-26 06:37:30', NULL, NULL),
(6, 2, 101, 'explanation', 'The \"def\" keyword in Python is short for \"define\" and is used to declare functions. Other options are invalid Python syntax for function declaration.', '{\"style\": \"technical\", \"reading_level\": \"grade 8\"}', 0, 'en', '1', '2025-07-26 06:37:30', NULL, NULL),
(7, 3, NULL, 'feedback', 'Your answer demonstrates good understanding but could benefit from more specific examples. Consider mentioning the return keyword for clarity.', '{\"response_to\": \"Candidate#42\", \"rubric_score\": 7.5, \"areas_for_improvement\": [\"examples\", \"precision\"]}', 0, 'en', '1', '2025-07-26 06:37:30', NULL, NULL),
(8, 4, NULL, 'question', '¿Qué método de JavaScript se utiliza para unir dos arrays?', '{\"topic\": \"JavaScript Arrays\", \"difficulty\": \"Intermediate\", \"english_version\": \"Which JavaScript method joins two arrays?\"}', 0, 'es', '1', '2025-07-26 06:37:30', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ai_generation_requests`
--

DROP TABLE IF EXISTS `ai_generation_requests`;
CREATE TABLE `ai_generation_requests` (
  `id` int UNSIGNED NOT NULL,
  `requested_by` int UNSIGNED NOT NULL,
  `request_type` enum('question','answer','explanation','feedback')  NOT NULL,
  `parameters` json NOT NULL,
  `status` enum('pending','processing','completed','failed')  NOT NULL DEFAULT 'pending',
  `completed_at` timestamp NULL DEFAULT NULL,
  `result_count` int UNSIGNED DEFAULT NULL,
  `error_message` text ,
  `base_lang` varchar(5)  NOT NULL DEFAULT 'en',
  `active` varchar(5)  NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB ;

--
-- Dumping data for table `ai_generation_requests`
--

INSERT INTO `ai_generation_requests` (`id`, `requested_by`, `request_type`, `parameters`, `status`, `completed_at`, `result_count`, `error_message`, `base_lang`, `active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'question', '{\"tags\": [\"python\", \"OOP\"], \"count\": 5, \"topic\": \"Python programming\", \"difficulty\": \"Intermediate\", \"question_type\": \"MCQ_Single\"}', 'completed', NULL, 5, NULL, 'en', '1', '2025-07-26 06:29:19', NULL, NULL),
(2, 2, 'answer', '{\"style\": \"concise\", \"question_id\": 42}', 'failed', '2025-07-26 06:30:08', NULL, 'AI service timeout - please try again later', 'en', '1', '2025-07-26 06:30:08', NULL, NULL),
(3, 3, 'explanation', '{\"complexity\": \"detailed\", \"question_id\": 15}', 'pending', NULL, NULL, NULL, 'en', '1', '2025-07-26 06:30:08', NULL, NULL),
(4, 4, 'feedback', '{\"rubric\": {\"clarity\": 8, \"accuracy\": 10}, \"answer_text\": \"The capital of France is Paris\"}', 'processing', NULL, NULL, NULL, 'fr', '1', '2025-07-26 06:30:08', NULL, NULL),
(5, 1, 'question', '{\"topics\": [\"JavaScript\", \"React\"], \"formats\": [\"MCQ_Single\", \"True_False\"], \"count_per_topic\": 3, \"difficulty_levels\": [\"Beginner\", \"Intermediate\"]}', 'completed', '2025-07-26 05:30:08', 12, NULL, 'en', '1', '2025-07-26 06:30:08', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cheating_data`
--

DROP TABLE IF EXISTS `cheating_data`;
CREATE TABLE `cheating_data` (
  `id` int UNSIGNED NOT NULL,
  `user_id` tinyint UNSIGNED NOT NULL COMMENT 'user type will be candidate only',
  `test_id` varchar(255)  NOT NULL,
  `process_time` timestamp NOT NULL,
  `event_type` varchar(200)  DEFAULT NULL,
  `confidence` varchar(50)  DEFAULT NULL,
  `duration_seconds` varchar(100)  DEFAULT NULL,
  `face_detected` tinyint(1) NOT NULL DEFAULT '1',
  `Screenshot_Path` varchar(5)  NOT NULL DEFAULT 'en',
  `base_lang` varchar(5)  NOT NULL DEFAULT 'en',
  `active` varchar(5)  NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB ;

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
CREATE TABLE `companies` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(100)  NOT NULL,
  `industry` varchar(50)  DEFAULT NULL,
  `size` varchar(20)  DEFAULT NULL,
  `website` varchar(255)  DEFAULT NULL,
  `base_lang` varchar(5)  NOT NULL DEFAULT 'en',
  `active` varchar(5)  NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB ;

-- --------------------------------------------------------

--
-- Table structure for table `difficulty_levels`
--

DROP TABLE IF EXISTS `difficulty_levels`;
CREATE TABLE `difficulty_levels` (
  `id` tinyint UNSIGNED NOT NULL,
  `name` varchar(20)  NOT NULL,
  `description` varchar(100)  DEFAULT NULL,
  `weight` tinyint UNSIGNED NOT NULL,
  `base_lang` varchar(5)  NOT NULL DEFAULT 'en',
  `active` varchar(5)  NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB ;

--
-- Dumping data for table `difficulty_levels`
--

INSERT INTO `difficulty_levels` (`id`, `name`, `description`, `weight`, `base_lang`, `active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Beginner', 'Basic knowledge questions', 1, 'en', '1', '2025-07-26 04:11:46', NULL, NULL),
(2, 'Intermediate', 'Moderate difficulty questions', 2, 'en', '1', '2025-07-26 04:11:46', NULL, NULL),
(3, 'Advanced', 'Challenging questions for experts', 3, 'en', '1', '2025-07-26 04:11:46', NULL, NULL),
(4, 'Expert', 'Very difficult questions', 4, 'en', '1', '2025-07-26 04:11:46', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `title` varchar(100)  NOT NULL,
  `message` text  NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `notification_type` enum('test_invite','test_completed','ai_generation','system')  NOT NULL,
  `related_id` int UNSIGNED DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `base_lang` varchar(5)  NOT NULL DEFAULT 'en',
  `active` varchar(5)  NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB ;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens` (
  `id` bigint UNSIGNED NOT NULL,
  `tokenable_type` varchar(255)  NOT NULL,
  `tokenable_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255)  NOT NULL,
  `token` varchar(64)  NOT NULL,
  `abilities` text ,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB ;

--
-- Dumping data for table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'App\\Models\\User', 1, 'auth_token', 'ac87b086f7cfc3e81658acc4a36b4eb63efac8b4cf30b7155157d3efc0d36c6d', '[\"*\"]', NULL, NULL, '2025-07-26 01:24:59', '2025-07-26 01:24:59');

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

DROP TABLE IF EXISTS `questions`;
CREATE TABLE `questions` (
  `id` int UNSIGNED NOT NULL,
  `question_text` text  NOT NULL,
  `type_id` tinyint UNSIGNED NOT NULL,
  `category_id` int UNSIGNED DEFAULT NULL,
  `difficulty_id` tinyint UNSIGNED DEFAULT NULL,
  `created_by` int UNSIGNED NOT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT '0',
  `is_ai_generated` tinyint(1) NOT NULL DEFAULT '0',
  `explanation` text ,
  `time_limit_seconds` smallint UNSIGNED DEFAULT NULL,
  `max_score` tinyint UNSIGNED NOT NULL DEFAULT '1',
  `base_lang` varchar(5)  NOT NULL DEFAULT 'en',
  `active` varchar(5)  NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB ;

-- --------------------------------------------------------

--
-- Table structure for table `question_categories`
--

DROP TABLE IF EXISTS `question_categories`;
CREATE TABLE `question_categories` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(50)  NOT NULL,
  `description` varchar(255)  DEFAULT NULL,
  `parent_category_id` int UNSIGNED DEFAULT NULL,
  `created_by` int UNSIGNED DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `base_lang` varchar(5)  NOT NULL DEFAULT 'en',
  `active` varchar(5)  NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB ;

-- --------------------------------------------------------

--
-- Table structure for table `question_options`
--

DROP TABLE IF EXISTS `question_options`;
CREATE TABLE `question_options` (
  `id` int UNSIGNED NOT NULL,
  `question_id` int UNSIGNED NOT NULL,
  `option_text` text  NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT '0',
  `option_order` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `explanation` text ,
  `base_lang` varchar(5)  NOT NULL DEFAULT 'en',
  `active` varchar(5)  NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB ;

-- --------------------------------------------------------

--
-- Table structure for table `question_responses`
--

DROP TABLE IF EXISTS `question_responses`;
CREATE TABLE `question_responses` (
  `id` int UNSIGNED NOT NULL,
  `attempt_id` int UNSIGNED NOT NULL,
  `question_id` int UNSIGNED NOT NULL,
  `question_type_id` tinyint UNSIGNED NOT NULL,
  `response_text` text ,
  `response_options` json DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `max_score` decimal(5,2) NOT NULL,
  `time_spent_seconds` int UNSIGNED DEFAULT NULL,
  `feedback` text ,
  `graded_by` int UNSIGNED DEFAULT NULL,
  `graded_at` timestamp NULL DEFAULT NULL,
  `base_lang` varchar(5)  NOT NULL DEFAULT 'en',
  `active` varchar(5)  NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB ;

-- --------------------------------------------------------

--
-- Table structure for table `question_tags`
--

DROP TABLE IF EXISTS `question_tags`;
CREATE TABLE `question_tags` (
  `id` int UNSIGNED NOT NULL,
  `tag_id` int UNSIGNED NOT NULL,
  `base_lang` varchar(5)  NOT NULL DEFAULT 'en',
  `active` varchar(5)  NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB ;

-- --------------------------------------------------------

--
-- Table structure for table `question_types`
--

DROP TABLE IF EXISTS `question_types`;
CREATE TABLE `question_types` (
  `id` tinyint UNSIGNED NOT NULL,
  `name` varchar(30)  NOT NULL,
  `description` varchar(100)  DEFAULT NULL,
  `has_options` tinyint(1) NOT NULL DEFAULT '0',
  `has_text_answer` tinyint(1) NOT NULL DEFAULT '0',
  `is_scorable` tinyint(1) NOT NULL DEFAULT '1',
  `base_lang` varchar(5)  NOT NULL DEFAULT 'en',
  `active` varchar(5)  NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB ;

--
-- Dumping data for table `question_types`
--

INSERT INTO `question_types` (`id`, `name`, `description`, `has_options`, `has_text_answer`, `is_scorable`, `base_lang`, `active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'MCQ_Single', 'Multiple Choice - Single Answer', 1, 0, 1, 'en', '1', '2025-07-26 04:11:46', NULL, NULL),
(2, 'MCQ_Multiple', 'Multiple Choice - Multiple Answers', 1, 0, 1, 'en', '1', '2025-07-26 04:11:46', NULL, NULL),
(3, 'True_False', 'True or False', 1, 0, 1, 'en', '1', '2025-07-26 04:11:46', NULL, NULL),
(4, 'Short_Answer', 'Short text answer', 0, 1, 0, 'en', '1', '2025-07-26 04:11:46', NULL, NULL),
(5, 'Essay', 'Long form text answer', 0, 1, 0, 'en', '1', '2025-07-26 04:11:46', NULL, NULL),
(6, 'Code_Snippet', 'Programming/code question', 0, 1, 0, 'en', '1', '2025-07-26 04:11:46', NULL, NULL),
(7, 'Fill_Blank', 'Fill in the blank', 0, 1, 1, 'en', '1', '2025-07-26 04:11:46', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `recruiter_profiles`
--

DROP TABLE IF EXISTS `recruiter_profiles`;
CREATE TABLE `recruiter_profiles` (
  `id` int UNSIGNED NOT NULL,
  `company_id` int UNSIGNED DEFAULT NULL,
  `job_title` varchar(50)  DEFAULT NULL,
  `department` varchar(50)  DEFAULT NULL,
  `phone` varchar(20)  DEFAULT NULL,
  `base_lang` varchar(5)  NOT NULL DEFAULT 'en',
  `active` varchar(5)  NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB ;

-- --------------------------------------------------------

--
-- Table structure for table `response_selected_options`
--

DROP TABLE IF EXISTS `response_selected_options`;
CREATE TABLE `response_selected_options` (
  `id` int UNSIGNED NOT NULL,
  `option_id` int UNSIGNED NOT NULL,
  `is_correct` tinyint(1) NOT NULL,
  `base_lang` varchar(5)  NOT NULL DEFAULT 'en',
  `active` varchar(5)  NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB ;

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
CREATE TABLE `tags` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(50)  NOT NULL,
  `description` varchar(100)  DEFAULT NULL,
  `base_lang` varchar(5)  NOT NULL DEFAULT 'en',
  `active` varchar(5)  NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB ;

-- --------------------------------------------------------

--
-- Table structure for table `tests`
--

DROP TABLE IF EXISTS `tests`;
CREATE TABLE `tests` (
  `id` int UNSIGNED NOT NULL,
  `title` varchar(100)  NOT NULL,
  `description` text ,
  `created_by` int UNSIGNED NOT NULL,
  `time_limit_minutes` smallint UNSIGNED DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `pass_threshold` tinyint UNSIGNED DEFAULT NULL,
  `show_score` tinyint(1) NOT NULL DEFAULT '1',
  `show_answers` tinyint(1) NOT NULL DEFAULT '0',
  `randomize_questions` tinyint(1) NOT NULL DEFAULT '0',
  `allow_backtracking` tinyint(1) NOT NULL DEFAULT '1',
  `instructions` text ,
  `base_lang` varchar(5)  NOT NULL DEFAULT 'en',
  `active` varchar(5)  NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB ;

-- --------------------------------------------------------

--
-- Table structure for table `test_attempts`
--

DROP TABLE IF EXISTS `test_attempts`;
CREATE TABLE `test_attempts` (
  `id` int UNSIGNED NOT NULL,
  `test_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `invitation_id` int UNSIGNED DEFAULT NULL,
  `started_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `time_spent_seconds` int UNSIGNED DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `max_score` decimal(5,2) NOT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `is_passed` tinyint(1) DEFAULT NULL,
  `ip_address` varchar(45)  DEFAULT NULL,
  `user_agent` text ,
  `proctoring_flags` json DEFAULT NULL,
  `base_lang` varchar(5)  NOT NULL DEFAULT 'en',
  `active` varchar(5)  NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB ;

-- --------------------------------------------------------

--
-- Table structure for table `test_invitations`
--

DROP TABLE IF EXISTS `test_invitations`;
CREATE TABLE `test_invitations` (
  `id` int UNSIGNED NOT NULL,
  `test_id` int UNSIGNED NOT NULL,
  `invited_by` int UNSIGNED NOT NULL,
  `invitee_email` varchar(255)  NOT NULL,
  `token` varchar(64)  NOT NULL,
  `expires_at` timestamp NOT NULL,
  `status` enum('sent','opened','started','completed','expired')  NOT NULL DEFAULT 'sent',
  `sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `first_opened_at` timestamp NULL DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `custom_message` text ,
  `base_lang` varchar(5)  NOT NULL DEFAULT 'en',
  `active` varchar(5)  NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB ;

-- --------------------------------------------------------

--
-- Table structure for table `test_questions`
--

DROP TABLE IF EXISTS `test_questions`;
CREATE TABLE `test_questions` (
  `id` int UNSIGNED NOT NULL,
  `test_id` int UNSIGNED NOT NULL,
  `question_id` int UNSIGNED NOT NULL,
  `question_order` int UNSIGNED NOT NULL DEFAULT '0',
  `section_name` varchar(50)  DEFAULT NULL,
  `weight` tinyint UNSIGNED NOT NULL DEFAULT '1',
  `base_lang` varchar(5)  NOT NULL DEFAULT 'en',
  `active` varchar(5)  NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL,
  `user_type` varchar(200) NOT NULL comment 'admin,recruiter,candidate,self_assessor',
  `email` varchar(255)  NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4  NOT NULL,
  `first_name` varchar(50)  DEFAULT NULL,
  `last_name` varchar(50)  DEFAULT NULL,
  `company_name` varchar(100)  DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login` timestamp NULL DEFAULT NULL,
  `base_lang` varchar(5)  NOT NULL DEFAULT 'en',
  `active` varchar(5)  NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB ;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `user_type`, `email`, `password`, `first_name`, `last_name`, `company_name`, `is_active`, `last_login`, `base_lang`, `active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'admin', 'admin@email.com', '$2y$10$wQjq1uoNQ1n7Gu0.UHjl/.6v9JFSEpvhR8efuJAKzu.c7b.3e45oC', NULL, NULL, NULL, 1, NULL, 'en', '1', '2025-07-26 01:19:18', '2025-07-26 01:19:18', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_types`
--

DROP TABLE IF EXISTS `user_types`;
CREATE TABLE `user_types` (
  `id` tinyint UNSIGNED NOT NULL,
  `type_name` varchar(20)  NOT NULL,
  `description` varchar(100)  DEFAULT NULL,
  `base_lang` varchar(5)  NOT NULL DEFAULT 'en',
  `active` varchar(5)  NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB ;

-- 
-- Dumping data for table `user_types`
--

INSERT INTO `user_types` (`id`, `type_name`, `description`, `base_lang`, `active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'admin', 'Platform administrators with full access', 'en', '1', '2025-07-26 04:09:56', NULL, NULL),
(2, 'recruiter', 'Recruiters who create tests and manage candidates', 'en', '1', '2025-07-26 04:09:56', NULL, NULL),
(3, 'candidate', 'Individuals taking tests', 'en', '1', '2025-07-26 04:09:56', NULL, NULL),
(4, 'self_assessor', 'Individuals taking self-assessment tests', 'en', '1', '2025-07-26 04:09:56', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_activity_type` (`activity_type`);

--
-- Indexes for table `ai_generated_content`
--
ALTER TABLE `ai_generated_content`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_request` (`request_id`),
  ADD KEY `idx_question` (`question_id`);

--
-- Indexes for table `ai_generation_requests`
--
ALTER TABLE `ai_generation_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_requester` (`requested_by`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `difficulty_levels`
--
ALTER TABLE `difficulty_levels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_read_status` (`is_read`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_difficulty` (`difficulty_id`),
  ADD KEY `idx_creator` (`created_by`);
ALTER TABLE `questions` ADD FULLTEXT KEY `ft_question_text` (`question_text`);

--
-- Indexes for table `question_categories`
--
ALTER TABLE `question_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_parent_category` (`parent_category_id`);

--
-- Indexes for table `question_options`
--
ALTER TABLE `question_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_question` (`question_id`);

--
-- Indexes for table `question_responses`
--
ALTER TABLE `question_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_attempt` (`attempt_id`),
  ADD KEY `idx_question` (`question_id`);

--
-- Indexes for table `question_tags`
--
ALTER TABLE `question_tags`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `question_types`
--
ALTER TABLE `question_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `recruiter_profiles`
--
ALTER TABLE `recruiter_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_company` (`company_id`);

--
-- Indexes for table `response_selected_options`
--
ALTER TABLE `response_selected_options`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `tests`
--
ALTER TABLE `tests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_creator` (`created_by`);
ALTER TABLE `tests` ADD FULLTEXT KEY `ft_test_title` (`title`);

--
-- Indexes for table `test_attempts`
--
ALTER TABLE `test_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_test` (`test_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_invitation` (`invitation_id`);

--
-- Indexes for table `test_invitations`
--
ALTER TABLE `test_invitations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_test` (`test_id`),
  ADD KEY `idx_inviter` (`invited_by`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `test_questions`
--
ALTER TABLE `test_questions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_test_question` (`test_id`,`question_id`),
  ADD KEY `idx_test` (`test_id`),
  ADD KEY `idx_question` (`question_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `user_types`
--
ALTER TABLE `user_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `type_name` (`type_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ai_generated_content`
--
ALTER TABLE `ai_generated_content`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `ai_generation_requests`
--
ALTER TABLE `ai_generation_requests`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `difficulty_levels`
--
ALTER TABLE `difficulty_levels`
  MODIFY `id` tinyint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `question_categories`
--
ALTER TABLE `question_categories`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `question_options`
--
ALTER TABLE `question_options`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `question_responses`
--
ALTER TABLE `question_responses`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `question_tags`
--
ALTER TABLE `question_tags`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `question_types`
--
ALTER TABLE `question_types`
  MODIFY `id` tinyint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `response_selected_options`
--
ALTER TABLE `response_selected_options`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tests`
--
ALTER TABLE `tests`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `test_attempts`
--
ALTER TABLE `test_attempts`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `test_invitations`
--
ALTER TABLE `test_invitations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `test_questions`
--
ALTER TABLE `test_questions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_types`
--
ALTER TABLE `user_types`
  MODIFY `id` tinyint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
