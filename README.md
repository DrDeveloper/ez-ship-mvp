Initialization for Github Repo

This guide explains how to set up the EZ Ship database locally using XAMPP and phpMyAdmin. It covers importing the base schema, loading test data, and resetting the database if needed.

PREREQUISITES:
XAMPP installed
Apache and MySQL running
phpMyAdmin available at http://localhost/phpmyadmin

CREATING THE DATABASE
Open phpMyAdmin
Click New in the left menu
Name the database ezship
Choose utf8mb4_general_ci as the collation
Click Create

IMPORTING INITIAL SCHEMA
Select the ezship database
Click the Import tab
Choose the file ezship_schema.sql
Click Go

This creates all tables and loads the required parcel size reference data. No shipping or client data is added at this stage.

IMPORTING TEST DATA
Select the ezship database
Click Import
Choose the file ezship_seed_test.sql
Click Go

This adds minimal test records to confirm foreign keys and table relationships are working correctly. The data is safe for testing, screenshots, and demos.

CLEARING OR RESETTING DATA

To clear test data but keep the schema, delete records from all tables except parcel_size, starting with child tables and working up to parent tables. This allows you to re-import the seed data without rebuilding the database.

To fully reset the database, drop the ezship database in phpMyAdmin, recreate it, then re-import ezship_schema.sql and optionally ezship_seed_test.sql.

RECOMMENDED WORKFLOW
Import the schema once
Import seed data only when testing
Clear or reset data before final demos
