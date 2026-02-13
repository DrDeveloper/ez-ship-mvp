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
// Authenticates Session User has applicable role and begins session.
require_once __DIR__ . '/../includes/auth.php';

$authorized = enforceRole('driver');
?>

<?php if ($authorized): ?>
    <main class="content">
        <?php
        // Includes the subheader and messages assets, and stores session data variables for use in the page.
        require_once __DIR__ . '/../includes/subheader.php';
        $username = htmlspecialchars($_SESSION['username']);
        $role     = htmlspecialchars($_SESSION['role']);
        $userId   = $_SESSION['user_id'];

        // Write Logic Here

        require_once __DIR__ . '/../includes/messages.php';
        ?>

        <!-- Write HTML Here -->

    </main>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="/capstone_webapp/assets/js/main.js"></script>
</body>
</html>