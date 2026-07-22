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
-- Table structure for table `client_place_booths`
--

CREATE TABLE `client_place_booths` (
  `id` bigint UNSIGNED NOT NULL,
  `client_place_id` int NOT NULL,
  `booth_id` int NOT NULL,
  `direction` enum('outbound','return') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'outbound',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `client_place_booths`
--

INSERT INTO `client_place_booths` (`id`, `client_place_id`, `booth_id`, `direction`, `created_at`, `updated_at`) VALUES
(2, 3, 29, 'return', '2026-04-01 00:05:33', '2026-04-01 00:05:33'),
(4, 3, 26, 'outbound', '2026-04-01 00:10:33', '2026-04-01 00:10:33'),
(7, 3, 17, 'return', '2026-04-01 00:11:54', '2026-04-01 00:11:54'),
(8, 3, 28, 'outbound', '2026-04-01 00:14:43', '2026-04-01 00:14:43'),
(9, 3, 29, 'outbound', '2026-04-01 00:14:46', '2026-04-01 00:14:46'),
(10, 3, 14, 'return', '2026-04-01 03:17:14', '2026-04-01 03:17:14');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `client_place_booths`
--
ALTER TABLE `client_place_booths`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `client_place_booths_unique` (`client_place_id`,`booth_id`,`direction`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `client_place_booths`
--
ALTER TABLE `client_place_booths`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
