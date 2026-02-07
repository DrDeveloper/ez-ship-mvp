-- =========================================================
-- EZ SHIP - SEED TEST DATA (for verifying foreign keys)
-- Database: ezship
-- NOTE: This inserts minimal data to test relationships.
-- =========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Clear child tables first (dependency order)
DELETE FROM parcel_time;
DELETE FROM parcel_routing;
DELETE FROM parcel_location;
DELETE FROM warehouse_storage;
DELETE FROM parcels;
DELETE FROM recipient;
DELETE FROM driver;
DELETE FROM warehouse;
DELETE FROM client;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================
-- Clients
-- =========================================================
INSERT INTO client (cid, cn, cl) VALUES
(200001, 'Best Buy', '10 Old Stock Yards Rd, York, ON M6N 5G8, Canada'),
(200002, 'AutoZone', '4 Varley Crescent, North York, ON M3J 2B8, Canada');

-- =========================================================
-- Warehouses (wmps must exist in parcel_size)
-- =========================================================
INSERT INTO warehouse (wid, wl, wn, wcs, wmps) VALUES
(300001, '3587 Bathurst St, Toronto, ON M6A 2E2, Canada', '7-Eleven', 35000000, 'l'),
(300002, '537 Keele St, York, ON M6N 3E4, Canada', 'Esso Gas Station', 35000000, 'xl');

-- Optional: warehouse_storage snapshot rows (FK to warehouse)
INSERT INTO warehouse_storage (wid, wp, wes, wcs) VALUES
(300001, 0, 35000000, 0),
(300002, 0, 35000000, 0);

-- =========================================================
-- Drivers (dmd must exist in parcel_size)
-- =========================================================
INSERT INTO driver (did, dn, dmd, dv) VALUES
(400001, 'Bob Fisher', 'l', '2023 Toyota 4-Runner'),
(400002, 'Mary Allaire', 'm', '2021 Honda Civic');

-- =========================================================
-- Recipients
-- =========================================================
INSERT INTO recipient (rid, rl, rn, rdi) VALUES
(500001, '4 Deeside Ct, Etobicoke, ON M9W 2C9, Canada', 'Joe Shmoe', 'Leave in Drop Box'),
(500002, '123 Main St, Des Moines, IA 50309, USA', 'Taylor Smith', 'Signature Required');

-- =========================================================
-- Parcels (ps must exist in parcel_size)
-- =========================================================
INSERT INTO parcels (pid, ps, pb, pv, pt) VALUES
(100001, 's', '4.7L 0W-20 Motor Oil', 26.97, 'Flammable'),
(100002, 'm', '27" LG Monitor', 169.99, 'Fragile');

-- =========================================================
-- Parcel Location (FK to parcels)
-- =========================================================
INSERT INTO parcel_location (pid, pl) VALUES
(100001, 200002),
(100002, 400001);

-- =========================================================
-- Parcel Routing (FKs to parcels, client, warehouse, driver, recipient)
-- did is allowed to be NULL, so we’ll test both cases
-- =========================================================
INSERT INTO parcel_routing (pid, cid, wid, did, rid) VALUES
(100001, 200002, 300001, NULL,   500002),  -- no driver assigned yet
(100002, 200001, 300002, 400001, 500001);  -- driver assigned

-- =========================================================
-- Parcel Time (FK to parcels)
-- pct is required, others can be NULL
-- =========================================================
INSERT INTO parcel_time (pid, pct, pwt, pdt, prt) VALUES
(100001, '2026-01-26 14:25:00', NULL,               NULL,               NULL),
(100002, '2026-01-25 08:31:00', '2026-01-25 10:05:00', '2026-01-25 11:03:00', NULL);
