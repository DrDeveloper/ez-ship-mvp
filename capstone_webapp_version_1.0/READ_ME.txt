Notes For Software Developers

Getting XAMPP (includes phpMyAdmin)

First, navigate to: https://www.apachefriends.org/
Download and install XAMPP for your OS.
Launch XAMPP Control Panel and Start:
Apache (Web Server) and MySQL (database server)

Everything should be operational now.



Where to place this folder and the files within it:
C:\xampp\htdocs


Setting up the Database

For database access and development, navigate to:
http://localhost/phpmyadmin

Instructions on setting up the database on your end:
1) Click "New" on the left-hand bar.
2) Enter Database name: "ezship"
3) Click Create
Importing the Database:
1) Download the most recent database from GitHub.
2) Click "Import" on the top bar.
3) Click Choose File and select the up-to-date "ezship.sql" file.

Create developer user privileges:
1) Click "Privileges" on the top bar
2) Click "Add user account."
3) Enter: 
	User Name: "ezship_dev"
	Host Name: Select "local" from the drop-down.
	Password: "dev_EZ_2026"
	Re-Type: "dev_EZ_2026"
4) Global Privileges: "Check all"
5) Click "Go."
