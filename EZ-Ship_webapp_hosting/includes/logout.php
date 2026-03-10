<?php
session_start();

ob_start(); // Start buffering to prevent 'headers already sent'
session_start();
$_SESSION = [];
session_destroy();

// Delete session cookie (important for full cleanup)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Redirect to login page
header("Location: /landing_page.php");
exit();