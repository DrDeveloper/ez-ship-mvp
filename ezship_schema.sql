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
DROP TABLE IF EXISTS parcel_size;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================
-- 1) FIXED LOOKUP TABLE: parcel_size
-- This table is configuration, not dummy data
-- =========================================================
CREATE TABLE parcel_size (
  ps VARCHAR(4) PRIMARY KEY,            -- xb, s, m, l, xl
  pas INT NOT NULL,                     -- Parcel Attributed Size (cubic cm)
  context_for_measurements VARCHAR(255) -- Human-readable dimensions
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Fixed size definitions
INSERT INTO parcel_size (ps, pas, context_for_measurements) VALUES
('xb', 3375,   '15cm x 15cm x 15cm'),
('s',  27000,  '30cm x 30cm x 30cm'),
('m',  216000, '60cm x 60cm x 60cm'),
('l',  729000, '90cm x 90cm x 90cm'),
('xl', 1728000,'120cm x 120cm x 120cm');

-- =========================================================
-- 2) CLIENT TABLE
-- =========================================================
CREATE TABLE client (
  cid INT PRIMARY KEY,
  cn VARCHAR(150) NOT NULL,
  cl VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- 3) WAREHOUSE TABLE
-- =========================================================
CREATE TABLE warehouse (
  wid INT PRIMARY KEY,
  wl VARCHAR(255) NOT NULL,
  wn VARCHAR(150) NOT NULL,
  wcs BIGINT NOT NULL,        -- Warehouse cubic size (cm³)
  wmps VARCHAR(4) NOT NULL,   -- Max parcel size allowed
  CONSTRAINT fk_warehouse_max_size
    FOREIGN KEY (wmps) REFERENCES parcel_size(ps)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- 4) DRIVER TABLE
-- =========================================================
CREATE TABLE driver (
  did INT PRIMARY KEY,
  dn VARCHAR(150) NOT NULL,
  dmd VARCHAR(4) NOT NULL,    -- Max deliverable size
  dv VARCHAR(150) NOT NULL,   -- Vehicle
  CONSTRAINT fk_driver_max_deliverable
    FOREIGN KEY (dmd) REFERENCES parcel_size(ps)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- 5) RECIPIENT TABLE
-- =========================================================
CREATE TABLE recipient (
  rid INT PRIMARY KEY,
  rl VARCHAR(255) NOT NULL,
  rn VARCHAR(150) NOT NULL,
  rdi VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- 6) PARCELS TABLE
-- =========================================================
CREATE TABLE parcels (
  pid INT PRIMARY KEY,
  ps VARCHAR(4) NOT NULL,       -- Parcel size
  pb VARCHAR(255) NOT NULL,     -- Description
  pv DECIMAL(10,2) NOT NULL,    -- Value
  pt VARCHAR(50) NOT NULL,      -- Type (Fragile, Flammable, etc.)
  CONSTRAINT fk_parcels_size
    FOREIGN KEY (ps) REFERENCES parcel_size(ps)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- 7) PARCEL_LOCATION TABLE
-- =========================================================
CREATE TABLE parcel_location (
  pid INT PRIMARY KEY,
  pl INT NOT NULL,
  CONSTRAINT fk_parcel_location_parcel
    FOREIGN KEY (pid) REFERENCES parcels(pid)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- 8) PARCEL_ROUTING TABLE
-- =========================================================
CREATE TABLE parcel_routing (
  pid INT PRIMARY KEY,
  cid INT NOT NULL,
  wid INT NOT NULL,
  did INT NULL,
  rid INT NOT NULL,
  CONSTRAINT fk_routing_parcel
    FOREIGN KEY (pid) REFERENCES parcels(pid)
    ON DELETE CASCADE,
  CONSTRAINT fk_routing_client
    FOREIGN KEY (cid) REFERENCES client(cid),
  CONSTRAINT fk_routing_warehouse
    FOREIGN KEY (wid) REFERENCES warehouse(wid),
  CONSTRAINT fk_routing_driver
    FOREIGN KEY (did) REFERENCES driver(did),
  CONSTRAINT fk_routing_recipient
    FOREIGN KEY (rid) REFERENCES recipient(rid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- 9) PARCEL_TIME TABLE
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
-- 10) WAREHOUSE_STORAGE TABLE
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
-- INDEXES
-- =========================================================
CREATE INDEX idx_parcel_routing_client ON parcel_routing(cid);
CREATE INDEX idx_parcel_routing_warehouse ON parcel_routing(wid);
CREATE INDEX idx_parcel_routing_driver ON parcel_routing(did);
CREATE INDEX idx_parcel_routing_recipient ON parcel_routing(rid);