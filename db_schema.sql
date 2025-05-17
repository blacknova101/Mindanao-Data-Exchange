-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 17, 2025 at 07:23 AM
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
-- Database: `mangasaydb`
--

-- --------------------------------------------------------

--
-- Table structure for table `administrator`
--

CREATE TABLE `administrator` (
  `admin_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `datasetaccesslogs`
--

CREATE TABLE `datasetaccesslogs` (
  `log_id` varchar(15) NOT NULL,
  `user_id` varchar(15) NOT NULL,
  `dataset_id` varchar(15) NOT NULL,
  `access_time` datetime DEFAULT current_timestamp(),
  `action` enum('View','Edit','Delete') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `datasetanalytics`
--

CREATE TABLE `datasetanalytics` (
  `analytics_id` int(15) NOT NULL,
  `dataset_id` int(15) NOT NULL,
  `total_downloads` int(11) DEFAULT 0,
  `last_accessed` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `datasetcategories`
--

CREATE TABLE `datasetcategories` (
  `category_id` int(15) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `datasetcomments`
--

CREATE TABLE `datasetcomments` (
  `comment_id` int(11) NOT NULL,
  `dataset_id` varchar(15) NOT NULL,
  `user_id` varchar(15) NOT NULL,
  `comment_text` varchar(150) NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `datasetlicensing`
--

CREATE TABLE `datasetlicensing` (
  `license_id` varchar(15) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(150) DEFAULT NULL,
  `url` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `datasetratings`
--

CREATE TABLE `datasetratings` (
  `rating_id` int(15) NOT NULL,
  `dataset_id` int(15) NOT NULL,
  `user_id` int(15) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `datasets`
--

CREATE TABLE `datasets` (
  `dataset_id` int(15) NOT NULL,
  `dataset_batch_id` int(25) NOT NULL,
  `user_id` int(15) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `start_period` date NOT NULL,
  `end_period` date NOT NULL,
  `category_id` int(15) NOT NULL,
  `source` varchar(255) NOT NULL,
  `link` varchar(255) NOT NULL,
  `location` varchar(100) NOT NULL,
  `visibility` enum('Private','Public') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `datasettags`
--

CREATE TABLE `datasettags` (
  `tag_id` varchar(15) NOT NULL,
  `dataset_id` varchar(15) NOT NULL,
  `tag_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `datasetversions`
--

CREATE TABLE `datasetversions` (
  `version_id` int(11) NOT NULL,
  `dataset_batch_id` int(25) NOT NULL,
  `version_number` varchar(20) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `start_period` date NOT NULL,
  `end_period` date NOT NULL,
  `category_id` int(15) NOT NULL,
  `source` varchar(255) NOT NULL,
  `link` varchar(255) NOT NULL,
  `location` varchar(100) NOT NULL,
  `created_by` int(15) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_current` tinyint(1) NOT NULL DEFAULT 1,
  `change_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dataset_access_requests`
--

CREATE TABLE `dataset_access_requests` (
  `request_id` bigint(20) UNSIGNED NOT NULL,
  `dataset_id` int(11) NOT NULL,
  `requester_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `request_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` varchar(20) NOT NULL,
  `reason` text DEFAULT NULL,
  `verification_document` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dataset_batches`
--

CREATE TABLE `dataset_batches` (
  `dataset_batch_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `organization_id` int(15) NOT NULL,
  `visibility` enum('Public','Private') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dataset_batch_analytics`
--

CREATE TABLE `dataset_batch_analytics` (
  `dataset_batch_id` int(11) NOT NULL,
  `total_views` int(11) NOT NULL DEFAULT 0,
  `total_downloads` int(11) NOT NULL DEFAULT 0,
  `last_accessed` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `location_id` varchar(15) NOT NULL,
  `name` varchar(50) NOT NULL,
  `country_code` varchar(20) NOT NULL,
  `region` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('in_app','email','both') NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `organizations`
--

CREATE TABLE `organizations` (
  `organization_id` int(15) NOT NULL,
  `name` varchar(50) NOT NULL,
  `type` enum('Academic','Government','Non-Profit','Commercial','Other') NOT NULL,
  `contact_email` varchar(50) DEFAULT NULL,
  `website_url` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `auto_accept` tinyint(1) DEFAULT 0,
  `created_by` int(15) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `organization_creation_requests`
--

CREATE TABLE `organization_creation_requests` (
  `request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(100) NOT NULL,
  `contact_email` varchar(255) NOT NULL,
  `website_url` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `admin_response` text DEFAULT NULL,
  `request_date` datetime NOT NULL,
  `reviewed_date` datetime DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `organization_membership_requests`
--

CREATE TABLE `organization_membership_requests` (
  `request_id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Pending','Approved','Rejected','Expired') DEFAULT 'Pending',
  `message` text DEFAULT NULL,
  `admin_response` text DEFAULT NULL,
  `expiration_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `organization_request_documents`
--

CREATE TABLE `organization_request_documents` (
  `document_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `document_path` varchar(255) NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sources`
--

CREATE TABLE `sources` (
  `source_id` varchar(15) NOT NULL,
  `name` varchar(50) NOT NULL,
  `organization_id` varchar(15) NOT NULL,
  `contact_email` varchar(50) DEFAULT NULL,
  `website_url` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(15) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `organization_id` int(15) DEFAULT NULL,
  `user_type` enum('Normal','with_organization') NOT NULL,
  `date_joined` datetime DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `role` enum('user','admin','moderator') DEFAULT 'user',
  `profile_picture` varchar(255) DEFAULT 'images/avatarIconunknown.jpg'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_notifications`
--

CREATE TABLE `user_notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `notification_type` varchar(50) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `last_activity` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `administrator`
--
ALTER TABLE `administrator`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `idx_email` (`email`);

--
-- Indexes for table `datasetaccesslogs`
--
ALTER TABLE `datasetaccesslogs`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `datasetanalytics`
--
ALTER TABLE `datasetanalytics`
  ADD PRIMARY KEY (`analytics_id`),
  ADD UNIQUE KEY `dataset_id` (`dataset_id`);

--
-- Indexes for table `datasetcategories`
--
ALTER TABLE `datasetcategories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `datasetcomments`
--
ALTER TABLE `datasetcomments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `datasetlicensing`
--
ALTER TABLE `datasetlicensing`
  ADD PRIMARY KEY (`license_id`);

--
-- Indexes for table `datasetratings`
--
ALTER TABLE `datasetratings`
  ADD PRIMARY KEY (`rating_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `dataset_id` (`dataset_id`);

--
-- Indexes for table `datasets`
--
ALTER TABLE `datasets`
  ADD PRIMARY KEY (`dataset_id`),
  ADD KEY `datasets_ibfk_1` (`user_id`),
  ADD KEY `dataset_batch_id` (`dataset_batch_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `datasettags`
--
ALTER TABLE `datasettags`
  ADD PRIMARY KEY (`tag_id`);

--
-- Indexes for table `datasetversions`
--
ALTER TABLE `datasetversions`
  ADD PRIMARY KEY (`version_id`),
  ADD KEY `dataset_batch_id` (`dataset_batch_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `dataset_access_requests`
--
ALTER TABLE `dataset_access_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD UNIQUE KEY `unique_request` (`dataset_id`,`requester_id`);

--
-- Indexes for table `dataset_batches`
--
ALTER TABLE `dataset_batches`
  ADD PRIMARY KEY (`dataset_batch_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `organization_id` (`organization_id`);

--
-- Indexes for table `dataset_batch_analytics`
--
ALTER TABLE `dataset_batch_analytics`
  ADD PRIMARY KEY (`dataset_batch_id`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`location_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `organizations`
--
ALTER TABLE `organizations`
  ADD PRIMARY KEY (`organization_id`);

--
-- Indexes for table `organization_creation_requests`
--
ALTER TABLE `organization_creation_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `organization_membership_requests`
--
ALTER TABLE `organization_membership_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD UNIQUE KEY `unique_org_request` (`organization_id`,`user_id`);

--
-- Indexes for table `organization_request_documents`
--
ALTER TABLE `organization_request_documents`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `sources`
--
ALTER TABLE `sources`
  ADD PRIMARY KEY (`source_id`),
  ADD KEY `organization_id` (`organization_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `organization_id` (`organization_id`);

--
-- Indexes for table `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `is_read` (`is_read`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `last_activity` (`last_activity`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `administrator`
--
ALTER TABLE `administrator`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `datasetanalytics`
--
ALTER TABLE `datasetanalytics`
  MODIFY `analytics_id` int(15) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `datasetcategories`
--
ALTER TABLE `datasetcategories`
  MODIFY `category_id` int(15) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `datasetcomments`
--
ALTER TABLE `datasetcomments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `datasetratings`
--
ALTER TABLE `datasetratings`
  MODIFY `rating_id` int(15) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `datasets`
--
ALTER TABLE `datasets`
  MODIFY `dataset_id` int(15) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `datasetversions`
--
ALTER TABLE `datasetversions`
  MODIFY `version_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dataset_access_requests`
--
ALTER TABLE `dataset_access_requests`
  MODIFY `request_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dataset_batches`
--
ALTER TABLE `dataset_batches`
  MODIFY `dataset_batch_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `organizations`
--
ALTER TABLE `organizations`
  MODIFY `organization_id` int(15) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `organization_creation_requests`
--
ALTER TABLE `organization_creation_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `organization_membership_requests`
--
ALTER TABLE `organization_membership_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `organization_request_documents`
--
ALTER TABLE `organization_request_documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(15) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_notifications`
--
ALTER TABLE `user_notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `datasetanalytics`
--
ALTER TABLE `datasetanalytics`
  ADD CONSTRAINT `datasetanalytics_ibfk_1` FOREIGN KEY (`dataset_id`) REFERENCES `datasets` (`dataset_id`);

--
-- Constraints for table `datasetversions`
--
ALTER TABLE `datasetversions`
  ADD CONSTRAINT `datasetversions_ibfk_1` FOREIGN KEY (`dataset_batch_id`) REFERENCES `dataset_batches` (`dataset_batch_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `datasetversions_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `datasetcategories` (`category_id`),
  ADD CONSTRAINT `datasetversions_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `dataset_batches`
--
ALTER TABLE `dataset_batches`
  ADD CONSTRAINT `dataset_batches_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`organization_id`);

--
-- Constraints for table `dataset_batch_analytics`
--
ALTER TABLE `dataset_batch_analytics`
  ADD CONSTRAINT `dataset_batch_analytics_ibfk_1` FOREIGN KEY (`dataset_batch_id`) REFERENCES `dataset_batches` (`dataset_batch_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `administrator` (`admin_id`);

--
-- Constraints for table `organization_creation_requests`
--
ALTER TABLE `organization_creation_requests`
  ADD CONSTRAINT `organization_creation_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `organization_creation_requests_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `organization_request_documents`
--
ALTER TABLE `organization_request_documents`
  ADD CONSTRAINT `organization_request_documents_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `organization_creation_requests` (`request_id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`organization_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
