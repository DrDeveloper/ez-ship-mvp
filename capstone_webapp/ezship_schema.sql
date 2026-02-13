-- =========================================================
-- EZ SHIP MVP DATABASE SCHEMA
-- Includes fixed reference data for parcel_size
-- Database: ezship
-- Engine: InnoDB
-- Charset: utf8mb4
-- =========================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS parcel_time;
DROP TABLE IF EXISTS parcel_routing;
DROP TABLE IF EXISTS parcel_location;
DROP TABLE IF EXISTS warehouse_storage;
DROP TABLE IF EXISTS parcels;
DROP TABLE IF EXISTS recipient;
DROP TABLE IF EXISTS driver;
DROP TABLE IF EXISTS warehouse;
DROP TABLE IF EXISTS client;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS temp_recipient;

SET FOREIGN_KEY_CHECKS = 1;

-- Removed the parcel size table.
-- =========================================================
-- 0) USERS TABLE (NEW)
-- =========================================================
CREATE TABLE users (
  uid INT(11) NOT NULL AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('client','warehouse','driver','recipient') NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (uid),
  UNIQUE KEY uq_username (username)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- 1) CLIENT TABLE
-- =========================================================
CREATE TABLE client (
  cid INT PRIMARY KEY,
  cn VARCHAR(150) NOT NULL,
  cl VARCHAR(255) NOT NULL,
  CONSTRAINT fk_client_user
    FOREIGN KEY (cid) REFERENCES users(uid)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- 2) WAREHOUSE TABLE
-- =========================================================
CREATE TABLE warehouse (
  wid INT PRIMARY KEY,
  wl VARCHAR(255) NOT NULL,
  wn VARCHAR(150) NOT NULL,
  wcs BIGINT NOT NULL,
  wmps BIGINT NOT NULL,

  CONSTRAINT fk_warehouse_user
    FOREIGN KEY (wid) REFERENCES users(uid)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- 3) DRIVER TABLE
-- =========================================================
CREATE TABLE driver (
  did INT PRIMARY KEY,
  dn VARCHAR(150) NOT NULL,
  dmd BIGINT NOT NULL,
  dv VARCHAR(150) NOT NULL,

  CONSTRAINT fk_driver_user
    FOREIGN KEY (did) REFERENCES users(uid)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- 4) RECIPIENT TABLE
-- =========================================================
CREATE TABLE recipient (
  rid INT PRIMARY KEY,
  rl VARCHAR(255) NOT NULL,
  rn VARCHAR(150) NOT NULL,
  rdi VARCHAR(255) NULL,

  CONSTRAINT fk_recipient_user
    FOREIGN KEY (rid) REFERENCES users(uid)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- 5) PARCELS TABLE
-- =========================================================
CREATE TABLE parcels (
  pid INT PRIMARY KEY AUTO_INCREMENT,
  ps BIGINT NOT NULL,       -- Parcel size
  pd VARCHAR(255) NOT NULL,     -- Description
  pv DECIMAL(10,2) NOT NULL,    -- Value
  pt VARCHAR(50) NOT NULL      -- Type (Fragile, Flammable, etc.)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- 6) PARCEL_LOCATION TABLE
-- =========================================================
CREATE TABLE parcel_location (
  pid INT PRIMARY KEY,
  pl INT NOT NULL,
  recipient_type ENUM('R','T') NULL DEFAULT NULL, 
  CONSTRAINT fk_parcel_location_parcel
    FOREIGN KEY (pid) REFERENCES parcels(pid)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- 7) PARCEL_ROUTING TABLE
-- =========================================================
CREATE TABLE parcel_routing (
  pid INT PRIMARY KEY,
  cid INT NOT NULL,
  wid INT NOT NULL,
  did INT NULL,
  rid INT NULL,
  recipient_type ENUM('R','T') NULL DEFAULT NULL,
  CONSTRAINT fk_routing_parcel
    FOREIGN KEY (pid) REFERENCES parcels(pid)
    ON DELETE CASCADE,
  CONSTRAINT fk_routing_client
    FOREIGN KEY (cid) REFERENCES client(cid),
  CONSTRAINT fk_routing_warehouse
    FOREIGN KEY (wid) REFERENCES warehouse(wid),
  CONSTRAINT fk_routing_driver
    FOREIGN KEY (did) REFERENCES driver(did)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =========================================================
-- 8) PARCEL_TIME TABLE
-- =========================================================
CREATE TABLE parcel_time (
  pid INT PRIMARY KEY,
  pct DATETIME NOT NULL,   -- Creation time
  pwt DATETIME NULL,       -- Warehouse drop-off
  pdt DATETIME NULL,       -- Driver pickup
  prt DATETIME NULL,       -- Recipient delivery
  CONSTRAINT fk_time_parcel
    FOREIGN KEY (pid) REFERENCES parcels(pid)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- 9) WAREHOUSE_STORAGE TABLE
-- =========================================================
CREATE TABLE warehouse_storage (
  wid INT PRIMARY KEY,
  wp INT NOT NULL DEFAULT 0,      -- Number of parcels
  wes BIGINT NOT NULL DEFAULT 0,  -- Excess storage
  wcs BIGINT NOT NULL DEFAULT 0,  -- Current storage
  CONSTRAINT fk_storage_warehouse
    FOREIGN KEY (wid) REFERENCES warehouse(wid)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =========================================================
-- 10) TEMP_RECIPIENT TABLE
-- =========================================================

CREATE TABLE `temp_recipient` (
  `trid_num` int(10) UNSIGNED NOT NULL,
  `rn` varchar(150) NOT NULL,
  `rl` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
ALTER TABLE `temp_recipient`
  ADD PRIMARY KEY (`trid_num`);
ALTER TABLE `temp_recipient`
  MODIFY `trid_num` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;


-- =========================================================
-- INDEXES
-- =========================================================
CREATE INDEX idx_parcel_routing_client ON parcel_routing(cid);
CREATE INDEX idx_parcel_routing_warehouse ON parcel_routing(wid);
CREATE INDEX idx_parcel_routing_driver ON parcel_routing(did);
CREATE INDEX idx_parcel_routing_recipient ON parcel_routing(rid);