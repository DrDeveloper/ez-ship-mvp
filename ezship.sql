-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 11, 2026 at 01:21 AM
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
-- Database: `ezship`
--

-- --------------------------------------------------------

--
-- Table structure for table `client`
--

CREATE TABLE `client` (
  `cid` int(11) NOT NULL,
  `cn` varchar(150) NOT NULL,
  `cl` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `driver`
--

CREATE TABLE `driver` (
  `did` int(11) NOT NULL,
  `dn` varchar(150) NOT NULL,
  `dmd` varchar(4) NOT NULL,
  `dv` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parcels`
--

CREATE TABLE `parcels` (
  `pid` int(11) NOT NULL,
  `ps` varchar(4) NOT NULL,
  `pb` varchar(255) NOT NULL,
  `pv` decimal(10,2) NOT NULL,
  `pt` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parcel_location`
--

CREATE TABLE `parcel_location` (
  `pid` int(11) NOT NULL,
  `pl` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parcel_routing`
--

CREATE TABLE `parcel_routing` (
  `pid` int(11) NOT NULL,
  `cid` int(11) NOT NULL,
  `wid` int(11) NOT NULL,
  `did` int(11) DEFAULT NULL,
  `rid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parcel_size`
--

CREATE TABLE `parcel_size` (
  `ps` varchar(4) NOT NULL,
  `pas` int(11) NOT NULL,
  `context_for_measurements` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parcel_size`
--

INSERT INTO `parcel_size` (`ps`, `pas`, `context_for_measurements`) VALUES
('l', 729000, '90cm x 90cm x 90cm'),
('m', 216000, '60cm x 60cm x 60cm'),
('s', 27000, '30cm x 30cm x 30cm'),
('xb', 3375, '15cm x 15cm x 15cm'),
('xl', 1728000, '120cm x 120cm x 120cm');

-- --------------------------------------------------------

--
-- Table structure for table `parcel_time`
--

CREATE TABLE `parcel_time` (
  `pid` int(11) NOT NULL,
  `pct` datetime NOT NULL,
  `pwt` datetime DEFAULT NULL,
  `pdt` datetime DEFAULT NULL,
  `prt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recipient`
--

CREATE TABLE `recipient` (
  `rid` int(11) NOT NULL,
  `rl` varchar(255) NOT NULL,
  `rn` varchar(150) NOT NULL,
  `rdi` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `uid` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('client','warehouse','driver','recipient') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `warehouse`
--

CREATE TABLE `warehouse` (
  `wid` int(11) NOT NULL,
  `wl` varchar(255) NOT NULL,
  `wn` varchar(150) NOT NULL,
  `wcs` bigint(20) NOT NULL,
  `wmps` varchar(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `warehouse_storage`
--

CREATE TABLE `warehouse_storage` (
  `wid` int(11) NOT NULL,
  `wp` int(11) NOT NULL DEFAULT 0,
  `wes` bigint(20) NOT NULL DEFAULT 0,
  `wcs` bigint(20) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `client`
--
ALTER TABLE `client`
  ADD PRIMARY KEY (`cid`);

--
-- Indexes for table `driver`
--
ALTER TABLE `driver`
  ADD PRIMARY KEY (`did`),
  ADD KEY `fk_driver_max_deliverable` (`dmd`);

--
-- Indexes for table `parcels`
--
ALTER TABLE `parcels`
  ADD PRIMARY KEY (`pid`),
  ADD KEY `fk_parcels_size` (`ps`);

--
-- Indexes for table `parcel_location`
--
ALTER TABLE `parcel_location`
  ADD PRIMARY KEY (`pid`);

--
-- Indexes for table `parcel_routing`
--
ALTER TABLE `parcel_routing`
  ADD PRIMARY KEY (`pid`),
  ADD KEY `idx_parcel_routing_client` (`cid`),
  ADD KEY `idx_parcel_routing_warehouse` (`wid`),
  ADD KEY `idx_parcel_routing_driver` (`did`),
  ADD KEY `idx_parcel_routing_recipient` (`rid`);

--
-- Indexes for table `parcel_size`
--
ALTER TABLE `parcel_size`
  ADD PRIMARY KEY (`ps`);

--
-- Indexes for table `parcel_time`
--
ALTER TABLE `parcel_time`
  ADD PRIMARY KEY (`pid`);

--
-- Indexes for table `recipient`
--
ALTER TABLE `recipient`
  ADD PRIMARY KEY (`rid`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `warehouse`
--
ALTER TABLE `warehouse`
  ADD PRIMARY KEY (`wid`),
  ADD KEY `fk_warehouse_max_size` (`wmps`);

--
-- Indexes for table `warehouse_storage`
--
ALTER TABLE `warehouse_storage`
  ADD PRIMARY KEY (`wid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `driver`
--
ALTER TABLE `driver`
  ADD CONSTRAINT `fk_driver_max_deliverable` FOREIGN KEY (`dmd`) REFERENCES `parcel_size` (`ps`);

--
-- Constraints for table `parcels`
--
ALTER TABLE `parcels`
  ADD CONSTRAINT `fk_parcels_size` FOREIGN KEY (`ps`) REFERENCES `parcel_size` (`ps`);

--
-- Constraints for table `parcel_location`
--
ALTER TABLE `parcel_location`
  ADD CONSTRAINT `fk_parcel_location_parcel` FOREIGN KEY (`pid`) REFERENCES `parcels` (`pid`) ON DELETE CASCADE;

--
-- Constraints for table `parcel_routing`
--
ALTER TABLE `parcel_routing`
  ADD CONSTRAINT `fk_routing_client` FOREIGN KEY (`cid`) REFERENCES `client` (`cid`),
  ADD CONSTRAINT `fk_routing_driver` FOREIGN KEY (`did`) REFERENCES `driver` (`did`),
  ADD CONSTRAINT `fk_routing_parcel` FOREIGN KEY (`pid`) REFERENCES `parcels` (`pid`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_routing_recipient` FOREIGN KEY (`rid`) REFERENCES `recipient` (`rid`),
  ADD CONSTRAINT `fk_routing_warehouse` FOREIGN KEY (`wid`) REFERENCES `warehouse` (`wid`);

--
-- Constraints for table `parcel_time`
--
ALTER TABLE `parcel_time`
  ADD CONSTRAINT `fk_time_parcel` FOREIGN KEY (`pid`) REFERENCES `parcels` (`pid`) ON DELETE CASCADE;

--
-- Constraints for table `warehouse`
--
ALTER TABLE `warehouse`
  ADD CONSTRAINT `fk_warehouse_max_size` FOREIGN KEY (`wmps`) REFERENCES `parcel_size` (`ps`);

--
-- Constraints for table `warehouse_storage`
--
ALTER TABLE `warehouse_storage`
  ADD CONSTRAINT `fk_storage_warehouse` FOREIGN KEY (`wid`) REFERENCES `warehouse` (`wid`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
