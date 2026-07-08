<?php
// config.php - Main Configuration

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database Credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'u569302230_paytm'); // Change if needed
define('DB_USER', 'u569302230_paytm'); // Change if needed
define('DB_PASS', 'Saini@123#Krishna');     // Change if needed


// System URL
define('BASE_URL', 'https://docsuvidha.shorteasily.com/');

// Connect to DB to fetch Settings
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);

    $stmt = $pdo->query("SELECT setting_key, setting_value FROM config_settings");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Paytm Configuration (Load from DB preferred, but defaults here)
    define('PAYTM_ENVIRONMENT', 'TEST'); // 'TEST' or 'PROD'
    define('PAYTM_MERCHANT_KEY', 'OIsdYi13193686016483');
    define('PAYTM_MID', 'OIsdYi13193686016483');
    define('PAYTM_WEBSITE', 'WEBSTAGING'); // 'WEBSTAGING' for Test, 'DEFAULT' for Prod

} catch (Exception $e) {
    // Fallback if DB not ready (e.g. before install)
    define('PAYTM_ENVIRONMENT', 'TEST');
    define('PAYTM_MERCHANT_KEY', 'default');
    define('PAYTM_MID', 'default');
    define('PAYTM_WEBSITE', 'WEBSTAGING');
}

date_default_timezone_set('Asia/Kolkata');
?>