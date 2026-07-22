-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: db.sistema.taglogistica.com
-- Generation Time: Mar 31, 2026 at 04:53 PM
-- Server version: 8.0.41-0ubuntu0.24.04.1
-- PHP Version: 8.1.2-1ubuntu2.23

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `taglogdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `client_places`
--

CREATE TABLE `client_places` (
  `id` bigint UNSIGNED NOT NULL,
  `client_id` int NOT NULL,
  `place_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `zombie` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `client_places`
--

INSERT INTO `client_places` (`id`, `client_id`, `place_id`, `amount`, `zombie`, `created_at`, `updated_at`) VALUES
(1, 29, 11, 1200.00, 1, '2026-03-25 02:15:39', '2026-03-25 02:21:24'),
(2, 30, 14, 1500.00, 1, '2026-03-25 02:16:30', '2026-03-25 02:16:55'),
(3, 30, 11, 1500.00, 0, '2026-03-25 02:21:38', '2026-03-25 02:21:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `client_places`
--
ALTER TABLE `client_places`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `client_places_client_id_place_id_unique` (`client_id`,`place_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `client_places`
--
ALTER TABLE `client_places`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
