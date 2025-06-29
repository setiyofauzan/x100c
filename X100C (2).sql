-- phpMyAdmin SQL Dump
-- version 5.1.1deb5ubuntu1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 28, 2025 at 09:00 AM
-- Server version: 8.0.42-0ubuntu0.22.04.1
-- PHP Version: 8.1.2-1ubuntu2.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `X100C`
--

-- --------------------------------------------------------

--
-- Table structure for table `x100c_data`
--

CREATE TABLE `x100c_data` (
  `id` int NOT NULL,
  `device_id` int NOT NULL,
  `karyawan_id` int DEFAULT NULL,
  `timestamp` datetime NOT NULL,
  `param1` int NOT NULL,
  `param2` int NOT NULL,
  `param3` int NOT NULL,
  `param4` int NOT NULL,
  `jenis_absensi` enum('Masuk','Pulang','Istirahat','Kembali') DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `x100c_data`
--

INSERT INTO `x100c_data` (`id`, `device_id`, `karyawan_id`, `timestamp`, `param1`, `param2`, `param3`, `param4`, `jenis_absensi`, `keterangan`) VALUES
(71, 100, NULL, '2025-06-25 06:16:21', 1, 0, 1, 0, NULL, NULL),
(72, 100, NULL, '2025-06-25 11:55:24', 1, 1, 1, 0, NULL, NULL),
(73, 100, NULL, '2025-06-26 05:40:36', 1, 0, 1, 0, NULL, NULL),
(74, 100, NULL, '2025-06-26 15:47:39', 1, 1, 1, 0, NULL, NULL),
(75, 100, NULL, '2025-06-27 06:50:41', 1, 0, 1, 0, NULL, NULL),
(76, 100, NULL, '2025-06-27 15:03:16', 1, 1, 1, 0, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `x100c_data`
--
ALTER TABLE `x100c_data`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `x100c_data`
--
ALTER TABLE `x100c_data`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
