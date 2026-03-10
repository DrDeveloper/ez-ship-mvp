<?php
define('APP_NAME', 'EZ-SHIP');

// Force HTTPS
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit();
}

// Force secure cookies with proper domain and SameSite settings
$cookieParams = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => $cookieParams['lifetime'],
    'path'     => $cookieParams['path'],
    'domain'   => 'ezship.yzz.me',  // <- important!
    'secure'   => true,            // forces HTTPS only
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
