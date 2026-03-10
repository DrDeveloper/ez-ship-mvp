<?php
require_once __DIR__ . '/config.php';

$DB_HOST = 'sql302.yzz.me';
$DB_NAME = 'yzzme_41180370_ezship';
$DB_USER = 'yzzme_41180370';
$DB_PASS = 'Quant047';

try {
    $db = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // throw exceptions
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database connection failed: ' . $e->getMessage());
}

