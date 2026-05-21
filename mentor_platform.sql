-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 17, 2026 at 09:17 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mentor_platform`
--
CREATE DATABASE IF NOT EXISTS `mentor_platform` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `mentor_platform`;

-- --------------------------------------------------------

--
-- Table structure for table `contact_form_messages`
--

DROP TABLE IF EXISTS `contact_form_messages`;
CREATE TABLE `contact_form_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_form_messages`
--

INSERT INTO `contact_form_messages` (`id`, `name`, `email`, `subject`, `message`, `created_at`) VALUES
(1, 'Prince Kumar', 'kumarprince17860@gmail.com', 'C++', 'Fee Structure?', '2026-04-15 08:02:09');

-- --------------------------------------------------------

--
-- Table structure for table `goals`
--

DROP TABLE IF EXISTS `goals`;
CREATE TABLE `goals` (
  `goal_id` int(11) NOT NULL,
  `mentee_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('in_progress','completed','abandoned') DEFAULT 'in_progress',
  `target_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `goals`
--

INSERT INTO `goals` (`goal_id`, `mentee_id`, `title`, `description`, `status`, `target_date`, `created_at`) VALUES
(3, 34, 'Expertise in Coding', 'Masters in OOPS', 'in_progress', '2026-04-30', '2026-04-16 08:57:40');

-- --------------------------------------------------------

--
-- Table structure for table `goal_progress`
--

DROP TABLE IF EXISTS `goal_progress`;
CREATE TABLE `goal_progress` (
  `progress_id` int(11) NOT NULL,
  `goal_id` int(11) NOT NULL,
  `update_text` text NOT NULL,
  `progress_percentage` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `goal_progress`
--

INSERT INTO `goal_progress` (`progress_id`, `goal_id`, `update_text`, `progress_percentage`, `created_at`) VALUES
(1, 1, '', 6, '2026-04-09 07:49:54'),
(2, 2, '', 50, '2026-04-15 08:39:03'),
(3, 2, 'only coding left', 50, '2026-04-15 08:39:22'),
(4, 3, '', 45, '2026-04-16 08:57:48');

-- --------------------------------------------------------

--
-- Table structure for table `goal_resources`
--

DROP TABLE IF EXISTS `goal_resources`;
CREATE TABLE `goal_resources` (
  `goal_resource_id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `mentee_id` int(11) NOT NULL,
  `goal_id` int(11) DEFAULT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `goal_resources`
--

INSERT INTO `goal_resources` (`goal_resource_id`, `resource_id`, `mentee_id`, `goal_id`, `viewed_at`) VALUES
(1, 1, 34, NULL, '2026-04-16 08:56:56');

-- --------------------------------------------------------

--
-- Table structure for table `mentee_profiles`
--

DROP TABLE IF EXISTS `mentee_profiles`;
CREATE TABLE `mentee_profiles` (
  `mentee_id` int(11) NOT NULL,
  `goals` text DEFAULT NULL,
  `preferred_mentoring_topics` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mentee_profiles`
--

INSERT INTO `mentee_profiles` (`mentee_id`, `goals`, `preferred_mentoring_topics`) VALUES
(34, 'Expertise in Coding', 'OOPS');

-- --------------------------------------------------------

--
-- Table structure for table `mentor_profiles`
--

DROP TABLE IF EXISTS `mentor_profiles`;
CREATE TABLE `mentor_profiles` (
  `mentor_id` int(11) NOT NULL,
  `hourly_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `availability` text DEFAULT NULL,
  `total_sessions` int(11) DEFAULT 0,
  `rating` decimal(3,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mentor_profiles`
--

INSERT INTO `mentor_profiles` (`mentor_id`, `hourly_rate`, `availability`, `total_sessions`, `rating`) VALUES
(33, 399.00, 'Monday - Friday (10:00AM - 05:00PM)', 0, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `attachment_url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `sender_id`, `receiver_id`, `message`, `attachment_url`, `is_read`, `created_at`) VALUES
(5, 34, 33, 'HI SIR', NULL, 1, '2026-04-16 08:58:37'),
(6, 33, 34, 'Hello', NULL, 1, '2026-04-16 08:59:11');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('message','session','review','goal') NOT NULL,
  `content` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resources`
--

DROP TABLE IF EXISTS `resources`;
CREATE TABLE `resources` (
  `resource_id` int(11) NOT NULL,
  `mentor_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `category` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resources`
--

INSERT INTO `resources` (`resource_id`, `mentor_id`, `title`, `description`, `category`, `type`, `file_name`, `file_path`, `created_at`) VALUES
(1, 33, 'C++', 'OOPS', 'Notes', 'pdf', '1776329038_285_OOPS lecture notes Complete.pdf', 'uploads/resources/1776329038_285_OOPS lecture notes Complete.pdf', '2026-04-16 08:43:58');

-- --------------------------------------------------------

--
-- Table structure for table `resource_access`
--

DROP TABLE IF EXISTS `resource_access`;
CREATE TABLE `resource_access` (
  `access_id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `mentee_id` int(11) NOT NULL,
  `accessed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `resource_access`
--

INSERT INTO `resource_access` (`access_id`, `resource_id`, `mentee_id`, `accessed_at`) VALUES
(1, 1, 34, '2026-04-16 08:56:56');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `mentor_id` int(11) NOT NULL,
  `mentee_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `session_id` int(11) NOT NULL,
  `mentor_id` int(11) NOT NULL,
  `mentee_id` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
  `hourly_rate` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('mentor','mentee') NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `qualification` text DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `interests` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `password`, `role`, `first_name`, `last_name`, `profile_picture`, `qualification`, `skills`, `interests`, `location`, `created_at`, `updated_at`, `status`) VALUES
(33, 'kumarprince17860@gmail.com', '$2y$10$DVlvQEq0/WVKMx3AjrdnLuttnqHvauAStaP1fiU3KgBg.mxiEYSba', 'mentor', 'Prince', 'Kumar', 'profile_69e0a0dde11dd.jpg', 'file:///C:/Users/ASUS/Downloads/in.ac.pseb-HSCER-20231651272023.pdf', 'C++', '', 'Jalandhar , Punjab , India', '2026-04-16 08:36:51', '2026-04-16 09:26:18', 'approved'),
(34, 'rythemmehandru24@gmail.com', '$2y$10$cNP9WbTD1FJvB924fSG9tuGVmNGKQUZj4YauF5ixTxrstEokh6xR2', 'mentee', 'Rythem', 'Mehandru', 'profile_69e0a44b9206c.jpg', '', 'Coding', 'C Language', 'Jalandhar , Punjab', '2026-04-16 08:54:17', '2026-04-16 08:56:43', 'pending');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `contact_form_messages`
--
ALTER TABLE `contact_form_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `goals`
--
ALTER TABLE `goals`
  ADD PRIMARY KEY (`goal_id`),
  ADD KEY `goals_ibfk_1` (`mentee_id`);

--
-- Indexes for table `goal_progress`
--
ALTER TABLE `goal_progress`
  ADD PRIMARY KEY (`progress_id`),
  ADD KEY `goal_id` (`goal_id`);

--
-- Indexes for table `goal_resources`
--
ALTER TABLE `goal_resources`
  ADD PRIMARY KEY (`goal_resource_id`),
  ADD KEY `resource_id` (`resource_id`),
  ADD KEY `mentee_id` (`mentee_id`),
  ADD KEY `goal_id` (`goal_id`);

--
-- Indexes for table `mentee_profiles`
--
ALTER TABLE `mentee_profiles`
  ADD PRIMARY KEY (`mentee_id`);

--
-- Indexes for table `mentor_profiles`
--
ALTER TABLE `mentor_profiles`
  ADD PRIMARY KEY (`mentor_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `resources`
--
ALTER TABLE `resources`
  ADD PRIMARY KEY (`resource_id`),
  ADD KEY `mentor_id` (`mentor_id`);

--
-- Indexes for table `resource_access`
--
ALTER TABLE `resource_access`
  ADD PRIMARY KEY (`access_id`),
  ADD KEY `resource_id` (`resource_id`),
  ADD KEY `mentee_id` (`mentee_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `mentor_id` (`mentor_id`),
  ADD KEY `mentee_id` (`mentee_id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `mentor_id` (`mentor_id`),
  ADD KEY `mentee_id` (`mentee_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `contact_form_messages`
--
ALTER TABLE `contact_form_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `goals`
--
ALTER TABLE `goals`
  MODIFY `goal_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `goal_progress`
--
ALTER TABLE `goal_progress`
  MODIFY `progress_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `goal_resources`
--
ALTER TABLE `goal_resources`
  MODIFY `goal_resource_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resources`
--
ALTER TABLE `resources`
  MODIFY `resource_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `resource_access`
--
ALTER TABLE `resource_access`
  MODIFY `access_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `goals`
--
ALTER TABLE `goals`
  ADD CONSTRAINT `goals_ibfk_1` FOREIGN KEY (`mentee_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `goal_progress`
--
ALTER TABLE `goal_progress`
  ADD CONSTRAINT `goal_progress_ibfk_1` FOREIGN KEY (`goal_id`) REFERENCES `goals` (`goal_id`);

--
-- Constraints for table `goal_resources`
--
ALTER TABLE `goal_resources`
  ADD CONSTRAINT `goal_resources_ibfk_1` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`resource_id`),
  ADD CONSTRAINT `goal_resources_ibfk_2` FOREIGN KEY (`mentee_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `goal_resources_ibfk_3` FOREIGN KEY (`goal_id`) REFERENCES `goals` (`goal_id`);

--
-- Constraints for table `mentee_profiles`
--
ALTER TABLE `mentee_profiles`
  ADD CONSTRAINT `mentee_profiles_ibfk_1` FOREIGN KEY (`mentee_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `mentor_profiles`
--
ALTER TABLE `mentor_profiles`
  ADD CONSTRAINT `mentor_profiles_ibfk_1` FOREIGN KEY (`mentor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `resources`
--
ALTER TABLE `resources`
  ADD CONSTRAINT `resources_ibfk_1` FOREIGN KEY (`mentor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `resource_access`
--
ALTER TABLE `resource_access`
  ADD CONSTRAINT `resource_access_ibfk_1` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`resource_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `resource_access_ibfk_2` FOREIGN KEY (`mentee_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`mentor_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`mentee_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`session_id`);

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`mentor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sessions_ibfk_2` FOREIGN KEY (`mentee_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
