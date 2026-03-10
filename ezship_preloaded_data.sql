-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 10, 2026 at 03:24 PM
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
  `cid` int(11) NOT NULL COMMENT 'Client ID',
  `cn` varchar(150) NOT NULL COMMENT 'Client Name',
  `cl` varchar(255) NOT NULL COMMENT 'Client Address'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `client`
--

INSERT INTO `client` (`cid`, `cn`, `cl`) VALUES
(6, 'Electronics Store', '123 Elec Ave, Toronto CA'),
(7, 'Bakery Store', '321 Bak Ave, Toronto CA');

-- --------------------------------------------------------

--
-- Table structure for table `driver`
--

CREATE TABLE `driver` (
  `did` int(11) NOT NULL COMMENT 'Driver ID',
  `dn` varchar(150) NOT NULL COMMENT 'Driver Name',
  `dmd` bigint(20) NOT NULL COMMENT 'Driver Maximum Parcel Deliverable',
  `dv` varchar(150) NOT NULL COMMENT 'Driver Vehicle'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `driver`
--

INSERT INTO `driver` (`did`, `dn`, `dmd`, `dv`) VALUES
(11, 'Julie Regruto', 729000, '2026, Mercedes Benz'),
(12, 'Luke Jenson', 27000, '2023, Ford Mustang'),
(13, 'Dustin Russell', 1728000, '2021, RAM 1500');

-- --------------------------------------------------------

--
-- Table structure for table `parcels`
--

CREATE TABLE `parcels` (
  `pid` int(11) NOT NULL COMMENT 'Parcel ID',
  `ps` bigint(20) NOT NULL COMMENT 'Parcel Size/Weight',
  `pd` varchar(255) NOT NULL COMMENT 'Parcel Description',
  `pv` decimal(10,2) NOT NULL COMMENT 'Parcel Value',
  `pt` varchar(50) NOT NULL COMMENT 'Parcel Type'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parcels`
--

INSERT INTO `parcels` (`pid`, `ps`, `pd`, `pv`, `pt`) VALUES
(16, 3375, 'GoPro Hero12 Black', 398.99, 'Electronics'),
(17, 27000, 'Keychron Q1 Pro', 218.99, 'Electronics'),
(18, 216000, 'HP LaserJet M234', 248.99, 'Electronics'),
(19, 729000, 'LG 27in 4K Monitor', 449.99, 'Electronics'),
(20, 1728000, '48in OLED Smart TV', 1299.99, 'Electronics'),
(21, 3375, 'Holiday Cookie Tin', 74.99, 'Perishable'),
(22, 27000, 'French Macaron Box', 119.99, 'Perishable'),
(23, 216000, 'Gourmet Cupcake Set', 96.99, 'Perishable'),
(24, 729000, 'Croissant Catering Tray', 125.99, 'Perishable'),
(25, 1728000, '3-Tire Custom Cake', 356.99, 'Perishable');

-- --------------------------------------------------------

--
-- Table structure for table `parcel_location`
--

CREATE TABLE `parcel_location` (
  `pid` int(11) NOT NULL COMMENT 'Parcel ID',
  `pl` int(11) NOT NULL COMMENT 'Current Location ID (client/warehouse/driver/recipient)',
  `recipient_type` enum('R','T') DEFAULT NULL COMMENT 'Recipient Type: R=Registered, T=Temporary',
  `driver_res` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parcel_location`
--

INSERT INTO `parcel_location` (`pid`, `pl`, `recipient_type`, `driver_res`) VALUES
(16, 8, NULL, NULL),
(17, 6, NULL, NULL),
(18, 9, NULL, 11),
(19, 14, 'R', 13),
(20, 13, NULL, 13),
(21, 7, NULL, NULL),
(22, 12, NULL, 12),
(23, 5, 'T', 13),
(24, 9, NULL, NULL),
(25, 10, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `parcel_routing`
--

CREATE TABLE `parcel_routing` (
  `pid` int(11) NOT NULL COMMENT 'Parcel ID',
  `cid` int(11) NOT NULL COMMENT 'Client ID',
  `wid` int(11) NOT NULL COMMENT 'Warehouse ID',
  `did` int(11) DEFAULT NULL COMMENT 'Driver ID (assigned)',
  `rid` int(11) NOT NULL COMMENT 'Recipient ID',
  `recipient_type` enum('R','T') DEFAULT NULL COMMENT 'Recipient Type: R=Registered, T=Temporary'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parcel_routing`
--

INSERT INTO `parcel_routing` (`pid`, `cid`, `wid`, `did`, `rid`, `recipient_type`) VALUES
(16, 6, 8, NULL, 14, 'R'),
(17, 6, 8, NULL, 14, 'R'),
(18, 6, 9, NULL, 14, 'R'),
(19, 6, 9, 13, 14, 'R'),
(20, 6, 10, 13, 14, 'R'),
(21, 7, 8, NULL, 15, 'R'),
(22, 7, 8, 12, 4, 'T'),
(23, 7, 9, 13, 5, 'T'),
(24, 7, 9, NULL, 6, 'T'),
(25, 7, 10, NULL, 7, 'T');

-- --------------------------------------------------------

--
-- Table structure for table `parcel_time`
--

CREATE TABLE `parcel_time` (
  `pid` int(11) NOT NULL COMMENT 'Parcel ID',
  `pct` datetime NOT NULL COMMENT 'Parcel Creation Time',
  `pwt` datetime DEFAULT NULL COMMENT 'Parcel Warehouse Time',
  `pdt` datetime DEFAULT NULL COMMENT 'Parcel Pickup Time',
  `prt` datetime DEFAULT NULL COMMENT 'Parcel Delivered Time'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parcel_time`
--

INSERT INTO `parcel_time` (`pid`, `pct`, `pwt`, `pdt`, `prt`) VALUES
(16, '2026-02-18 13:52:56', '2026-02-18 14:15:42', NULL, NULL),
(17, '2026-02-18 13:54:48', NULL, NULL, NULL),
(18, '2026-02-18 13:56:09', '2026-02-18 14:16:40', NULL, NULL),
(19, '2026-02-18 13:57:06', '2026-02-18 14:16:48', '2026-02-18 14:25:26', '2026-02-18 14:25:29'),
(20, '2026-02-18 13:58:18', '2026-02-18 14:17:54', '2026-02-18 14:26:06', NULL),
(21, '2026-02-18 14:02:38', NULL, NULL, NULL),
(22, '2026-02-18 14:07:03', '2026-02-18 14:15:52', '2026-02-18 14:24:49', NULL),
(23, '2026-02-18 14:08:15', '2026-02-18 14:16:55', '2026-02-18 14:25:41', '2026-02-18 14:25:47'),
(24, '2026-02-18 14:09:33', '2026-02-18 14:16:59', NULL, NULL),
(25, '2026-02-18 14:10:37', '2026-02-18 14:17:56', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `recipient`
--

CREATE TABLE `recipient` (
  `rid` int(11) NOT NULL COMMENT 'Recipient ID',
  `rl` varchar(255) NOT NULL COMMENT 'Recipient Address',
  `rn` varchar(150) NOT NULL COMMENT 'Recipient Name',
  `rdi` varchar(255) DEFAULT NULL COMMENT 'Delivery Instructions'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recipient`
--

INSERT INTO `recipient` (`rid`, `rl`, `rn`, `rdi`) VALUES
(14, '789 Divo Ave, Toronto CA', 'Divon Jones', 'Place in Drop-Box'),
(15, '987 Sum Sum Ave, Toronto CA', 'Summer Brown', 'Leave on Porch');

-- --------------------------------------------------------

--
-- Table structure for table `temp_recipient`
--

CREATE TABLE `temp_recipient` (
  `trid_num` int(10) UNSIGNED NOT NULL COMMENT 'Temporary Recipient ID',
  `rn` varchar(150) NOT NULL COMMENT 'Recipient Name',
  `rl` varchar(255) NOT NULL COMMENT 'Recipient Address',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Created At Timestamp'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `temp_recipient`
--

INSERT INTO `temp_recipient` (`trid_num`, `rn`, `rl`, `created_at`) VALUES
(4, 'Deonte Hill', '150 Deo St. Toronto CA', '2026-02-18 20:07:03'),
(5, 'Madison Hammans', '105 Maddy Ln. Toronto CA', '2026-02-18 20:08:15'),
(6, 'Robert Crook', '501 Robby Ave, Toronto CA', '2026-02-18 20:09:33'),
(7, 'Mike Morro', '510 Mikey St. Toronto CA', '2026-02-18 20:10:37');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `uid` int(11) NOT NULL COMMENT 'User ID',
  `username` varchar(50) NOT NULL COMMENT 'Username/Login',
  `password` varchar(255) NOT NULL COMMENT 'Hashed Password',
  `role` enum('client','warehouse','driver','recipient') NOT NULL COMMENT 'User Role',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Account Created At'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`uid`, `username`, `password`, `role`, `created_at`) VALUES
(6, 'Client_Electronics', '$2y$10$1vYHXd8r5TN1y2F55ZxxfeIwJxohXF6z3CkQ3wC4NBUXwv7YpvE4O', 'client', '2026-02-18 19:13:57'),
(7, 'Client_Bakery', '$2y$10$WHHwyYgAowo6NZaqhoR16.raZl8LIsAkYKdYojZqF7PNIpAcygIA6', 'client', '2026-02-18 19:14:44'),
(8, 'Warehouse_7-Eleven', '$2y$10$lYz5ymk1ccaV.Xl8mPyib.OHF5iQBSButXhKCA8qwF3xQslISUX3i', 'warehouse', '2026-02-18 19:24:44'),
(9, 'Warehouse_Esso-Gas', '$2y$10$8azFoTi.9/BQJNL3ifGhrenY2Q2i4L1szuMbfNXgy4yKpqgPjk8KG', 'warehouse', '2026-02-18 19:26:23'),
(10, 'Warehouse_Best-Buy', '$2y$10$urUsQvniOwM9M/W.gcQXeOAHWgK4sqT/HuNUZwfOQVaJ2UgLGNPlS', 'warehouse', '2026-02-18 19:27:31'),
(11, 'Driver_Jules', '$2y$10$g8dgGx1/lpVUgyzm91yWV./Fo9WM8AzPW3eWE6sYwfWwkrkMpKfoC', 'driver', '2026-02-18 19:41:32'),
(12, 'Driver_Luke', '$2y$10$s95mrPzm9mCpXLXsAXQApOHombxzRsgg8g7gijTAllwJuwJjOJeb.', 'driver', '2026-02-18 19:42:58'),
(13, 'Driver_Dustin', '$2y$10$iY3XsiMSTFE4o6.bl3PLhe38JIFM5Ab2xywseMXGdCN2hoegpzU46', 'driver', '2026-02-18 19:43:40'),
(14, 'Recipient_Divon', '$2y$10$nG/Qj5aVlKSgEcXfWZm0QeWI9gbC/uY8dBamzfrXIvhTuemTJAq5y', 'recipient', '2026-02-18 19:49:50'),
(15, 'Recipient_Summer', '$2y$10$vQ4Syb3iBtxfL.3V/L3dG.9FYHHun/JPThz6P.Lvvc/sArmU7s3Qm', 'recipient', '2026-02-18 19:50:56');

-- --------------------------------------------------------

--
-- Table structure for table `warehouse`
--

CREATE TABLE `warehouse` (
  `wid` int(11) NOT NULL COMMENT 'Warehouse ID',
  `wl` varchar(255) NOT NULL COMMENT 'Warehouse Location',
  `wn` varchar(150) NOT NULL COMMENT 'Warehouse Name',
  `wcs` bigint(20) NOT NULL COMMENT 'Current Storage',
  `wmps` bigint(20) NOT NULL COMMENT 'Max Parcel Size'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `warehouse`
--

INSERT INTO `warehouse` (`wid`, `wl`, `wn`, `wcs`, `wmps`) VALUES
(8, '456 7th St. Toronto CA', '7-Eleven 7th St.', 340000, 27000),
(9, '654 Es Cargo St. Toronto CA', 'Esso Gas Es Cargo St.', 1020000, 729000),
(10, '546 BB St. Toronto CA', 'Best-Buy BB St.', 3060000, 1728000);

-- --------------------------------------------------------

--
-- Table structure for table `warehouse_storage`
--

CREATE TABLE `warehouse_storage` (
  `wid` int(11) NOT NULL COMMENT 'Warehouse ID',
  `wp` int(11) NOT NULL DEFAULT 0 COMMENT 'Parcels Count',
  `wes` bigint(20) NOT NULL DEFAULT 0 COMMENT 'Existing Storage',
  `wcs` bigint(20) NOT NULL DEFAULT 0 COMMENT 'Current Storage'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `warehouse_storage`
--

INSERT INTO `warehouse_storage` (`wid`, `wp`, `wes`, `wcs`) VALUES
(8, 1, 336625, 3375),
(9, 2, 75000, 945000),
(10, 1, 1332000, 1728000);

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
  ADD PRIMARY KEY (`did`);

--
-- Indexes for table `parcels`
--
ALTER TABLE `parcels`
  ADD PRIMARY KEY (`pid`);

--
-- Indexes for table `parcel_location`
--
ALTER TABLE `parcel_location`
  ADD PRIMARY KEY (`pid`),
  ADD KEY `idx_driver_res` (`driver_res`);

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
-- Indexes for table `temp_recipient`
--
ALTER TABLE `temp_recipient`
  ADD PRIMARY KEY (`trid_num`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `uq_username` (`username`);

--
-- Indexes for table `warehouse`
--
ALTER TABLE `warehouse`
  ADD PRIMARY KEY (`wid`);

--
-- Indexes for table `warehouse_storage`
--
ALTER TABLE `warehouse_storage`
  ADD PRIMARY KEY (`wid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `parcels`
--
ALTER TABLE `parcels`
  MODIFY `pid` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Parcel ID', AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `temp_recipient`
--
ALTER TABLE `temp_recipient`
  MODIFY `trid_num` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Temporary Recipient ID', AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT COMMENT 'User ID', AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `parcel_location`
--
ALTER TABLE `parcel_location`
  ADD CONSTRAINT `fk_reserved_driver` FOREIGN KEY (`driver_res`) REFERENCES `users` (`uid`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
