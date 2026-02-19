-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 17, 2026 at 03:39 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- =========================================
-- Database schema for `ezship`
-- =========================================

-- =========================================
-- Drop all tables if they exist to start fresh
-- =========================================
SET FOREIGN_KEY_CHECKS = 0;

-- Drop dependent tables first
DROP TABLE IF EXISTS `warehouse_storage`;
DROP TABLE IF EXISTS `parcel_time`;
DROP TABLE IF EXISTS `parcel_routing`;
DROP TABLE IF EXISTS `parcel_location`;
DROP TABLE IF EXISTS `temp_recipient`;
DROP TABLE IF EXISTS `recipient`;
DROP TABLE IF EXISTS `driver`;
DROP TABLE IF EXISTS `client`;
DROP TABLE IF EXISTS `parcels`;
DROP TABLE IF EXISTS `warehouse`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- --------------------------------------------------------
-- Table structure for table `client`
-- Stores client companies
-- Columns:
-- cid: client ID
-- cn: client name
-- cl: client location (address)
-- --------------------------------------------------------
CREATE TABLE `client` (
  `cid` int(11) NOT NULL COMMENT 'Client ID',
  `cn` varchar(150) NOT NULL COMMENT 'Client Name',
  `cl` varchar(255) NOT NULL COMMENT 'Client Address',
  PRIMARY KEY (`cid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `driver`
-- Stores drivers' details
-- Columns:
-- did: driver ID
-- dn: driver name
-- dmd: driver maximum deliverable (capacity)
-- dv: vehicle description
-- --------------------------------------------------------
CREATE TABLE `driver` (
  `did` int(11) NOT NULL COMMENT 'Driver ID',
  `dn` varchar(150) NOT NULL COMMENT 'Driver Name',
  `dmd` bigint(20) NOT NULL COMMENT 'Driver Mobile/ID',
  `dv` varchar(150) NOT NULL COMMENT 'Driver Vehicle',
  PRIMARY KEY (`did`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `parcels`
-- Stores parcel details
-- Columns:
-- pid: parcel ID
-- ps: parcel size/weight (numeric)
-- pd: parcel description
-- pv: parcel value
-- pt: parcel type (perishable, electronics, etc.)
-- --------------------------------------------------------
CREATE TABLE `parcels` (
  `pid` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Parcel ID',
  `ps` bigint(20) NOT NULL COMMENT 'Parcel Size/Weight',
  `pd` varchar(255) NOT NULL COMMENT 'Parcel Description',
  `pv` decimal(10,2) NOT NULL COMMENT 'Parcel Value',
  `pt` varchar(50) NOT NULL COMMENT 'Parcel Type',
  PRIMARY KEY (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `parcel_location`
-- Tracks the current location of each parcel
-- Columns:
-- pid: parcel ID (FK to parcels)
-- pl: location ID (warehouse or recipient)
-- recipient_type: 'R' = registered, 'T' = temporary
-- driver_res: driver reservation ID if currently reserved for delivery andy by which driver.
-- --------------------------------------------------------
CREATE TABLE `parcel_location` (
  `pid` int(11) NOT NULL COMMENT 'Parcel ID',
  `pl` int(11) NOT NULL COMMENT 'Current Location ID (client/warehouse/recipient/recipient)',
  `recipient_type` enum('R','T') DEFAULT NULL COMMENT 'Recipient Type: R=Registered, T=Temporary',
  `driver_res` int(11) DEFAULT NULL COMMENT 'Driver Reservation ID (did)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `parcel_routing`
-- Tracks routing information for parcels
-- Columns:
-- pid: parcel ID
-- cid: client ID
-- wid: warehouse ID
-- did: driver ID
-- rid: recipient ID
-- recipient_type: 'R' = registered, 'T' = temporary
-- --------------------------------------------------------
CREATE TABLE `parcel_routing` (
  `pid` int(11) NOT NULL COMMENT 'Parcel ID',
  `cid` int(11) NOT NULL COMMENT 'Client ID',
  `wid` int(11) NOT NULL COMMENT 'Warehouse ID',
  `did` int(11) DEFAULT NULL COMMENT 'Driver ID (assigned)',
  `rid` int(11) NOT NULL COMMENT 'Recipient ID',
  `recipient_type` enum('R','T') DEFAULT NULL COMMENT 'Recipient Type: R=Registered, T=Temporary',
  PRIMARY KEY (`pid`),
  KEY `idx_parcel_routing_client` (`cid`),
  KEY `idx_parcel_routing_warehouse` (`wid`),
  KEY `idx_parcel_routing_driver` (`did`),
  KEY `idx_parcel_routing_recipient` (`rid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `parcel_time`
-- Tracks important timestamps for parcels
-- Columns:
-- pid: parcel ID
-- pct: parcel creation time
-- pwt: parcel warehouse time
-- pdt: parcel pickup time
-- prt: parcel delivered time
-- --------------------------------------------------------
CREATE TABLE `parcel_time` (
  `pid` int(11) NOT NULL COMMENT 'Parcel ID',
  `pct` datetime NOT NULL COMMENT 'Parcel Creation Time',
  `pwt` datetime DEFAULT NULL COMMENT 'Parcel Warehouse Time',
  `pdt` datetime DEFAULT NULL COMMENT 'Parcel Pickup Time',
  `prt` datetime DEFAULT NULL COMMENT 'Parcel Delivered Time',
  PRIMARY KEY (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `recipient`
-- Stores registered recipient info
-- Columns:
-- rid: recipient ID
-- rl: recipient address/location
-- rn: recipient name
-- rdi: delivery instructions
-- --------------------------------------------------------
CREATE TABLE `recipient` (
  `rid` int(11) NOT NULL COMMENT 'Recipient ID',
  `rl` varchar(255) NOT NULL COMMENT 'Recipient Address',
  `rn` varchar(150) NOT NULL COMMENT 'Recipient Name',
  `rdi` varchar(255) DEFAULT NULL COMMENT 'Delivery Instructions',
  PRIMARY KEY (`rid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `temp_recipient`
-- Stores temporary recipient info
-- Columns:
-- trid_num: temporary recipient ID
-- rn: recipient name
-- rl: recipient address
-- created_at: timestamp when added
-- --------------------------------------------------------
CREATE TABLE `temp_recipient` (
  `trid_num` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Temporary Recipient ID',
  `rn` varchar(150) NOT NULL COMMENT 'Recipient Name',
  `rl` varchar(255) NOT NULL COMMENT 'Recipient Address',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Created At Timestamp',
  PRIMARY KEY (`trid_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `users`
-- Stores system users
-- Columns:
-- uid: user ID
-- username: login username
-- password: hashed password
-- role: user role (client, warehouse, driver, recipient)
-- created_at: account creation timestamp
-- --------------------------------------------------------
CREATE TABLE `users` (
  `uid` int(11) NOT NULL AUTO_INCREMENT COMMENT 'User ID',
  `username` varchar(50) NOT NULL COMMENT 'Username/Login',
  `password` varchar(255) NOT NULL COMMENT 'Hashed Password',
  `role` enum('client','warehouse','driver','recipient') NOT NULL COMMENT 'User Role',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Account Created At',
  PRIMARY KEY (`uid`),
  UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `warehouse`
-- Stores warehouse info
-- Columns:
-- wid: warehouse ID
-- wl: warehouse location
-- wn: warehouse name
-- wcs: warehouse current storage
-- wmps: warehouse max parcel size
-- --------------------------------------------------------
CREATE TABLE `warehouse` (
  `wid` int(11) NOT NULL COMMENT 'Warehouse ID',
  `wl` varchar(255) NOT NULL COMMENT 'Warehouse Location',
  `wn` varchar(150) NOT NULL COMMENT 'Warehouse Name',
  `wcs` bigint(20) NOT NULL COMMENT 'Current Storage',
  `wmps` bigint(20) NOT NULL COMMENT 'Max Parcel Size',
  PRIMARY KEY (`wid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `warehouse_storage`
-- Tracks warehouse storage details
-- Columns:
-- wid: warehouse ID
-- wp: warehouse parcels count
-- wes: warehouse existing storage
-- wcs: warehouse current storage
-- --------------------------------------------------------
CREATE TABLE `warehouse_storage` (
  `wid` int(11) NOT NULL COMMENT 'Warehouse ID',
  `wp` int(11) NOT NULL DEFAULT 0 COMMENT 'Parcels Count',
  `wes` bigint(20) NOT NULL DEFAULT 0 COMMENT 'Excess Storage',
  `wcs` bigint(20) NOT NULL DEFAULT 0 COMMENT 'Current Storage',
  PRIMARY KEY (`wid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
