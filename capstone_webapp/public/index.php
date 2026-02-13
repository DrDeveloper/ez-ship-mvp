<?php
// Includes the configuration and database connection files.
require_once __DIR__ . '/../includes/config.php'; // Handles Global Variables and Starting Session.
require_once __DIR__ . '/../includes/db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= APP_NAME ?></title>
    <!-- Global CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/capstone_webapp/assets/css/style.css?v=<?= time() ?>">
</head>
<body>
<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/messages.php';
?>

<h1><?= APP_NAME ?></h1>
<p>Welcome To EZ-Ship Index Page</p>

</body>

<!-- Imports The Java Script File -->
<script src="/capstone_webapp/assets/js/main.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

</html>