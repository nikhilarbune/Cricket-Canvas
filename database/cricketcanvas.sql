-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 27, 2025 at 05:40 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cricketcanvas`
--

-- --------------------------------------------------------

--
-- Table structure for table `matches`
--

CREATE TABLE `matches` (
  `match_id` int(11) NOT NULL,
  `tournament_id` int(11) DEFAULT NULL,
  `team1_id` int(11) DEFAULT NULL,
  `team2_id` int(11) DEFAULT NULL,
  `match_date` datetime DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled') DEFAULT NULL,
  `winner_id` int(11) DEFAULT NULL,
  `venue` varchar(100) DEFAULT NULL,
  `round_number` int(11) DEFAULT NULL,
  `group_name` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `match_scores`
--

CREATE TABLE `match_scores` (
  `match_id` int(11) DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `runs` int(11) DEFAULT 0,
  `wickets` int(11) DEFAULT 0,
  `overs` decimal(4,1) DEFAULT 0.0,
  `extras` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `tournament_id` int(11) DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `payment_method` enum('bank_transfer','upi','cash') NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_proof` varchar(255) DEFAULT NULL,
  `payment_status` enum('pending','submitted','verified','rejected') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_reminders`
--

CREATE TABLE `payment_reminders` (
  `reminder_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `tournament_id` int(11) DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `team_id` int(11) NOT NULL,
  `team_name` varchar(100) NOT NULL,
  `captain_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `logo` varchar(255) DEFAULT NULL,
  `home_ground` varchar(100) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `established_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `max_members` int(11) DEFAULT 15,
  `team_status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teams`
--

INSERT INTO `teams` (`team_id`, `team_name`, `captain_id`, `created_at`, `logo`, `home_ground`, `city`, `established_date`, `description`, `contact_email`, `contact_phone`, `max_members`, `team_status`) VALUES
(1, 'BCA Boys', 1, '2024-12-16 15:13:29', '676048f7955df.png', 'Sadguru Gadage Maharaj College, Karad', 'Karad', NULL, '', 'nikhilarbune@gmail.com', '7885859568', 15, 'active'),
(2, 'BBA Boys', 1, '2024-12-16 18:42:23', '676097eb093b9.png', '', '', NULL, '', '', '', 15, 'active'),
(3, 'Dudhondi Boys ', 3, '2024-12-17 13:55:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 15, 'active'),
(5, 'BCA 2 Boys', 1, '2024-12-23 05:12:55', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 15, 'active'),
(6, 'DMK Boys', 5, '2025-02-04 16:47:22', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 15, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `team_members`
--

CREATE TABLE `team_members` (
  `team_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `join_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `team_members`
--

INSERT INTO `team_members` (`team_id`, `user_id`, `join_date`, `status`) VALUES
(1, 1, '2024-12-16 15:13:29', 'approved'),
(1, 3, '2024-12-16 15:24:22', 'approved'),
(1, 4, '2024-12-18 09:12:14', 'approved'),
(2, 1, '2024-12-16 18:42:23', 'approved'),
(2, 3, '2024-12-16 19:13:40', 'approved'),
(2, 4, '2024-12-18 09:12:12', 'approved'),
(3, 1, '2024-12-17 14:00:18', 'approved'),
(3, 3, '2024-12-17 13:55:46', 'approved'),
(3, 4, '2024-12-18 09:12:09', 'pending'),
(3, 5, '2025-02-04 16:46:11', 'pending'),
(5, 1, '2024-12-23 05:12:55', 'approved'),
(5, 5, '2025-02-04 16:46:08', 'pending'),
(6, 1, '2025-02-04 16:48:59', 'pending'),
(6, 5, '2025-02-04 16:47:22', 'approved');

-- --------------------------------------------------------

--
-- Table structure for table `tournaments`
--

CREATE TABLE `tournaments` (
  `tournament_id` int(11) NOT NULL,
  `tournament_name` varchar(100) NOT NULL,
  `organizer_id` int(11) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `max_teams` int(11) DEFAULT NULL,
  `format` enum('knockout','league','group_stage') NOT NULL,
  `status` enum('draft','open','registration_closed','ongoing','completed') DEFAULT 'draft',
  `description` text DEFAULT NULL,
  `registration_deadline` date NOT NULL,
  `min_teams` int(11) DEFAULT 4,
  `venue` varchar(100) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `entry_fee` decimal(10,2) DEFAULT 0.00,
  `prize_pool` decimal(10,2) DEFAULT 0.00,
  `rules` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_qr` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tournaments`
--

INSERT INTO `tournaments` (`tournament_id`, `tournament_name`, `organizer_id`, `start_date`, `end_date`, `max_teams`, `format`, `status`, `description`, `registration_deadline`, `min_teams`, `venue`, `city`, `entry_fee`, `prize_pool`, `rules`, `created_at`, `payment_qr`) VALUES
(1, 'SGM Premier League', 2, '2025-01-01', '2025-01-10', 8, 'group_stage', 'registration_closed', 'The SGM Premier League is a thrilling and highly anticipated cricket tournament organized by SGM College, Karad. This annual competition brings together cricket enthusiasts, budding players, and teams from various departments to compete in a fast-paced, friendly yet competitive environment.', '2024-12-25', 8, 'SGM College', 'Karad', 0.00, 1000.00, '1. Team Composition:\r\n- Each team must consist 11 players. \r\n- No player substitutions are allowed after registration, except in the case of injury (with umpire consent).\r\n2. Eligibility of Players:\r\n- Only SGM College students are allowed to participate. \r\n- Players must show their valid college ID at the time of the match.\r\n3. Timely Arrival:\r\n- Teams must be present at the venue 15 minutes prior to the scheduled start time. \r\n- A delay of more than 10 minutes will result in an automatic forfeit for the team.', '2024-12-16 16:38:28', NULL),
(2, 'Karad Premiere League', 2, '2024-12-20', '2024-12-30', 6, 'knockout', 'open', 'Testing ', '2024-12-19', 6, 'Shivaji Stadium Karad', 'Karad', 100.00, 5000.00, 'Testing', '2024-12-16 20:19:13', '67608b41295b1.jpg'),
(3, 'Indian Premier League', 2, '2024-12-21', '2024-12-25', 5, 'knockout', 'open', 'testing 2', '2024-12-20', 5, 'Main Stadium Mumbai', 'Mumbai', 100.00, 1000.00, 'testing 2', '2024-12-16 20:43:15', '676090e3eeb88.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `tournament_registrations`
--

CREATE TABLE `tournament_registrations` (
  `tournament_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tournament_standings`
--

CREATE TABLE `tournament_standings` (
  `tournament_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `matches_played` int(11) DEFAULT 0,
  `matches_won` int(11) DEFAULT 0,
  `matches_lost` int(11) DEFAULT 0,
  `points` int(11) DEFAULT 0,
  `net_run_rate` decimal(5,3) DEFAULT 0.000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tournament_teams`
--

CREATE TABLE `tournament_teams` (
  `tournament_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `payment_status` enum('pending','completed') DEFAULT 'pending',
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_proof` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tournament_teams`
--

INSERT INTO `tournament_teams` (`tournament_id`, `team_id`, `registration_date`, `status`, `payment_status`, `transaction_id`, `payment_proof`) VALUES
(1, 1, '2024-12-16 17:28:16', 'approved', '', NULL, NULL),
(1, 2, '2024-12-16 18:43:04', 'rejected', '', NULL, NULL),
(2, 1, '2024-12-16 20:30:50', 'approved', 'completed', '123456789012', '67609467a2b74.jpg'),
(2, 2, '2024-12-16 20:38:40', 'rejected', 'completed', '123456789012', '67609467a2b74.jpg'),
(2, 3, '2024-12-17 13:56:14', 'approved', 'completed', '123456789012', '676183bbefbb8.jpg'),
(2, 5, '2024-12-23 05:13:44', 'approved', 'completed', '123456789012', '6768f19e9d12f.png'),
(2, 6, '2025-02-04 16:50:33', 'approved', 'completed', '123456789012', '67a2457def762.png'),
(3, 1, '2024-12-16 20:43:41', 'approved', 'completed', '123456789012', '67609467a2b74.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('player','organizer','admin') NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `role`, `is_active`, `created_at`) VALUES
(1, 'Nikhil Arbune', 'nikhilarbune@gmail.com', '$2y$10$u2fO6rYKbrgYF4/koVIQCOYwS9Bz6DDSJsexUnaVaxG8gjXTUr7uC', 'player', 1, '2024-12-16 13:34:37'),
(2, 'Sanket Desai', 'desaisanket@gmail.com', '$2y$10$tcBYI6Jv7N15g4r8DBB1euIPrMvnnCg5xl.wMnS7k4VEH6xoPkWsW', 'organizer', 1, '2024-12-16 13:42:11'),
(3, 'Aniket Chavan', 'aniketchavan@gmail.com', '$2y$10$Au2O1Pd1z.iUJmPRC1lOo.zml2Xd95LimYo3koA2bHTIPPeOZIJhu', 'player', 1, '2024-12-16 15:19:01'),
(4, 'goverdhan shemane', 'govardhangs@gmail.com', '$2y$10$uxaxYsn85JbqrpPFCqYEbea2xE8LxPUls65NRFnuE2gkpdjUIMYl6', 'player', 1, '2024-12-18 09:11:31'),
(5, 'Sanket Patil', 'sanketpatil@gmail.com', '$2y$10$/QH2RYg1byQ6jT0dIzjB1e04LJDj8UvH.WXvj1lKnBy6As50hTChi', 'player', 1, '2025-02-04 16:45:20');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `matches`
--
ALTER TABLE `matches`
  ADD PRIMARY KEY (`match_id`),
  ADD KEY `winner_id` (`winner_id`),
  ADD KEY `fk_tournament` (`tournament_id`),
  ADD KEY `fk_team1` (`team1_id`),
  ADD KEY `fk_team2` (`team2_id`);

--
-- Indexes for table `match_scores`
--
ALTER TABLE `match_scores`
  ADD KEY `match_id` (`match_id`),
  ADD KEY `team_id` (`team_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `tournament_id` (`tournament_id`,`team_id`);

--
-- Indexes for table `payment_reminders`
--
ALTER TABLE `payment_reminders`
  ADD PRIMARY KEY (`reminder_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `tournament_id` (`tournament_id`,`team_id`);

--
-- Indexes for table `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`team_id`),
  ADD UNIQUE KEY `team_name` (`team_name`),
  ADD KEY `captain_id` (`captain_id`);

--
-- Indexes for table `team_members`
--
ALTER TABLE `team_members`
  ADD PRIMARY KEY (`team_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tournaments`
--
ALTER TABLE `tournaments`
  ADD PRIMARY KEY (`tournament_id`),
  ADD KEY `organizer_id` (`organizer_id`);

--
-- Indexes for table `tournament_registrations`
--
ALTER TABLE `tournament_registrations`
  ADD PRIMARY KEY (`tournament_id`,`team_id`),
  ADD KEY `team_id` (`team_id`);

--
-- Indexes for table `tournament_standings`
--
ALTER TABLE `tournament_standings`
  ADD PRIMARY KEY (`tournament_id`,`team_id`),
  ADD KEY `team_id` (`team_id`);

--
-- Indexes for table `tournament_teams`
--
ALTER TABLE `tournament_teams`
  ADD PRIMARY KEY (`tournament_id`,`team_id`),
  ADD KEY `team_id` (`team_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `matches`
--
ALTER TABLE `matches`
  MODIFY `match_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_reminders`
--
ALTER TABLE `payment_reminders`
  MODIFY `reminder_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `team_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tournaments`
--
ALTER TABLE `tournaments`
  MODIFY `tournament_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `matches`
--
ALTER TABLE `matches`
  ADD CONSTRAINT `fk_team1` FOREIGN KEY (`team1_id`) REFERENCES `teams` (`team_id`),
  ADD CONSTRAINT `fk_team2` FOREIGN KEY (`team2_id`) REFERENCES `teams` (`team_id`),
  ADD CONSTRAINT `fk_tournament` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`tournament_id`),
  ADD CONSTRAINT `matches_ibfk_1` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`tournament_id`),
  ADD CONSTRAINT `matches_ibfk_2` FOREIGN KEY (`team1_id`) REFERENCES `teams` (`team_id`),
  ADD CONSTRAINT `matches_ibfk_3` FOREIGN KEY (`team2_id`) REFERENCES `teams` (`team_id`),
  ADD CONSTRAINT `matches_ibfk_4` FOREIGN KEY (`winner_id`) REFERENCES `teams` (`team_id`);

--
-- Constraints for table `match_scores`
--
ALTER TABLE `match_scores`
  ADD CONSTRAINT `match_scores_ibfk_1` FOREIGN KEY (`match_id`) REFERENCES `matches` (`match_id`),
  ADD CONSTRAINT `match_scores_ibfk_2` FOREIGN KEY (`team_id`) REFERENCES `teams` (`team_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`tournament_id`,`team_id`) REFERENCES `tournament_teams` (`tournament_id`, `team_id`);

--
-- Constraints for table `payment_reminders`
--
ALTER TABLE `payment_reminders`
  ADD CONSTRAINT `payment_reminders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `payment_reminders_ibfk_2` FOREIGN KEY (`tournament_id`,`team_id`) REFERENCES `tournament_teams` (`tournament_id`, `team_id`);

--
-- Constraints for table `teams`
--
ALTER TABLE `teams`
  ADD CONSTRAINT `teams_ibfk_1` FOREIGN KEY (`captain_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `team_members`
--
ALTER TABLE `team_members`
  ADD CONSTRAINT `team_members_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`team_id`),
  ADD CONSTRAINT `team_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `tournaments`
--
ALTER TABLE `tournaments`
  ADD CONSTRAINT `tournaments_ibfk_1` FOREIGN KEY (`organizer_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `tournament_registrations`
--
ALTER TABLE `tournament_registrations`
  ADD CONSTRAINT `tournament_registrations_ibfk_1` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`tournament_id`),
  ADD CONSTRAINT `tournament_registrations_ibfk_2` FOREIGN KEY (`team_id`) REFERENCES `teams` (`team_id`);

--
-- Constraints for table `tournament_standings`
--
ALTER TABLE `tournament_standings`
  ADD CONSTRAINT `tournament_standings_ibfk_1` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`tournament_id`),
  ADD CONSTRAINT `tournament_standings_ibfk_2` FOREIGN KEY (`team_id`) REFERENCES `teams` (`team_id`);

--
-- Constraints for table `tournament_teams`
--
ALTER TABLE `tournament_teams`
  ADD CONSTRAINT `tournament_teams_ibfk_1` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`tournament_id`),
  ADD CONSTRAINT `tournament_teams_ibfk_2` FOREIGN KEY (`team_id`) REFERENCES `teams` (`team_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
