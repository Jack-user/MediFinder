-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 08, 2025 at 06:23 AM
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
-- Database: `medifinder`
--

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `generic_name` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`id`, `name`, `generic_name`, `description`, `created_at`) VALUES
(1, 'Paracetamol', 'Acetaminophen', NULL, '2025-11-02 23:26:37'),
(2, 'Ibuprofen', 'Ibuprofen', NULL, '2025-11-02 23:26:37'),
(3, 'Amoxicillin', 'Amoxicillin', NULL, '2025-11-02 23:26:37'),
(4, 'Cetirizine', 'Cetirizine', NULL, '2025-11-02 23:26:37'),
(5, 'Metformin', 'Metformin', NULL, '2025-11-02 23:26:37'),
(6, 'Omeprazole', 'Omeprazole', NULL, '2025-11-02 23:26:37'),
(7, 'Dextromethorphan', 'Dextromethorphan', NULL, '2025-11-02 23:26:37'),
(8, 'Loratadine', 'Loratadine', NULL, '2025-11-02 23:26:37');

-- --------------------------------------------------------

--
-- Table structure for table `pharmacies`
--

CREATE TABLE `pharmacies` (
  `id` int(11) NOT NULL,
  `registration_id` int(11) DEFAULT NULL,
  `owner_user_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `business_name` varchar(200) DEFAULT NULL,
  `license_number` varchar(100) NOT NULL,
  `license_type` enum('pharmacy','drugstore','clinic','healthcare_network') NOT NULL,
  `address` text NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `email` varchar(190) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `verified_at` datetime DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pharmacies`
--

INSERT INTO `pharmacies` (`id`, `registration_id`, `owner_user_id`, `name`, `business_name`, `license_number`, `license_type`, `address`, `latitude`, `longitude`, `phone`, `email`, `is_active`, `verified_at`, `verified_by`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'Jackoy', 'JacknPoy', '123131231231231', 'healthcare_network', 'Bago City', 10.52771052, 122.84242630, '9123123123', '0', 1, '2025-11-02 23:36:27', 1, '2025-11-02 23:36:27', '2025-11-03 21:29:32');

-- --------------------------------------------------------

--
-- Table structure for table `pharmacy_inventory`
--

CREATE TABLE `pharmacy_inventory` (
  `id` int(11) NOT NULL,
  `pharmacy_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `price` decimal(10,2) DEFAULT NULL,
  `unit` varchar(50) DEFAULT 'unit',
  `expiry_date` date DEFAULT NULL,
  `last_updated` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pharmacy_inventory`
--

INSERT INTO `pharmacy_inventory` (`id`, `pharmacy_id`, `medicine_id`, `quantity`, `price`, `unit`, `expiry_date`, `last_updated`) VALUES
(1, 1, 1, 5, 10.00, 'unit', NULL, '2025-11-02 23:36:59');

-- --------------------------------------------------------

--
-- Table structure for table `pharmacy_registrations`
--

CREATE TABLE `pharmacy_registrations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `pharmacy_name` varchar(200) NOT NULL,
  `business_name` varchar(200) DEFAULT NULL,
  `license_number` varchar(100) NOT NULL,
  `license_type` enum('pharmacy','drugstore','clinic','healthcare_network') NOT NULL,
  `license_file_path` varchar(500) DEFAULT NULL,
  `address` text NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `phone` varchar(50) NOT NULL,
  `email` varchar(190) DEFAULT NULL,
  `owner_name` varchar(200) NOT NULL,
  `owner_contact` varchar(50) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pharmacy_registrations`
--

INSERT INTO `pharmacy_registrations` (`id`, `user_id`, `pharmacy_name`, `business_name`, `license_number`, `license_type`, `license_file_path`, `address`, `latitude`, `longitude`, `phone`, `email`, `owner_name`, `owner_contact`, `status`, `rejection_reason`, `reviewed_by`, `reviewed_at`, `created_at`) VALUES
(1, 2, 'Jackoy', 'JacknPoy', '123131231231231', 'healthcare_network', NULL, 'Bago City', 10.53108956, 122.84360111, '09123123123', '0', 'Jack Da Great', '09123123131', 'approved', NULL, 1, '2025-11-02 23:36:27', '2025-11-02 23:32:52');

-- --------------------------------------------------------

--
-- Table structure for table `reminders`
--

CREATE TABLE `reminders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `remind_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `uploads`
--

CREATE TABLE `uploads` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `extracted_text` mediumtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `uploads`
--

INSERT INTO `uploads` (`id`, `user_id`, `original_name`, `extracted_text`, `created_at`) VALUES
(1, 1, 'ChatGPT Image Oct 31, 2025, 08_07_03 PM.png', 'Dr. John Doe\n1234 Elm Street, Anytown, CA 12345\n555-123-4567\nRc Jane Smith\n4/26/2024\nAmoxicillin 500 mg\nTake one tablet by mouth three\ntimes a day for 7 days\nC foto. Le', '2025-11-02 22:47:56');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `role` varchar(225) NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role`, `name`, `email`, `password_hash`, `created_at`) VALUES
(1, 'admin', 'Test', 'test@gmail.com', '$2y$10$SRlNMbNTBiJEO5RnCSW/NO66oX7S5qaxMB.q0VbxQfNnwEqW7Dh3i', '2025-10-31 20:01:10'),
(2, 'pharmacy_owner', 'Jack Da Great', 'jack@gmail.com', '$2y$10$gJSmMk.0uWVwC45WuiMAeOHGmLINlCfvjRl/TsTRsjVdO4g1ywKFC', '2025-11-02 23:32:52'),
(3, 'patient', 'Louie', 'louie@gmail.com', '$2y$10$emPiTvF88ukHeQ3wXq6W6.oEKDqNAfPLsY14hHEXEk/aH0d2awo8O', '2025-11-02 23:38:22'),
(4, 'pharmacy_owner', 'Weng', 'weng@gmail.com', '$2y$10$zlYdfiflNfFdaofznzpFDu8McY/5ylqZlZQAF.nWdnIX9Q9EWIAuK', '2025-11-03 21:57:08');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_name` (`name`);

--
-- Indexes for table `pharmacies`
--
ALTER TABLE `pharmacies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `license_number` (`license_number`),
  ADD KEY `registration_id` (`registration_id`),
  ADD KEY `verified_by` (`verified_by`),
  ADD KEY `idx_location` (`latitude`,`longitude`),
  ADD KEY `idx_owner` (`owner_user_id`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `pharmacy_inventory`
--
ALTER TABLE `pharmacy_inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_pharmacy_medicine` (`pharmacy_id`,`medicine_id`),
  ADD KEY `idx_pharmacy` (`pharmacy_id`),
  ADD KEY `idx_medicine` (`medicine_id`),
  ADD KEY `idx_quantity` (`quantity`);

--
-- Indexes for table `pharmacy_registrations`
--
ALTER TABLE `pharmacy_registrations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reviewed_by` (`reviewed_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `reminders`
--
ALTER TABLE `reminders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `uploads`
--
ALTER TABLE `uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `pharmacies`
--
ALTER TABLE `pharmacies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pharmacy_inventory`
--
ALTER TABLE `pharmacy_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pharmacy_registrations`
--
ALTER TABLE `pharmacy_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reminders`
--
ALTER TABLE `reminders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `uploads`
--
ALTER TABLE `uploads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `pharmacies`
--
ALTER TABLE `pharmacies`
  ADD CONSTRAINT `pharmacies_ibfk_1` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pharmacies_ibfk_2` FOREIGN KEY (`registration_id`) REFERENCES `pharmacy_registrations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pharmacies_ibfk_3` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `pharmacy_inventory`
--
ALTER TABLE `pharmacy_inventory`
  ADD CONSTRAINT `pharmacy_inventory_ibfk_1` FOREIGN KEY (`pharmacy_id`) REFERENCES `pharmacies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pharmacy_inventory_ibfk_2` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pharmacy_registrations`
--
ALTER TABLE `pharmacy_registrations`
  ADD CONSTRAINT `pharmacy_registrations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pharmacy_registrations_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reminders`
--
ALTER TABLE `reminders`
  ADD CONSTRAINT `reminders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `uploads`
--
ALTER TABLE `uploads`
  ADD CONSTRAINT `uploads_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
