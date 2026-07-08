<?php
// includes/db.php

require_once __DIR__ . '/../config.php';

function getDBConnection()
{
    $host = DB_HOST;
    $db = DB_NAME;
    $user = DB_USER;
    $pass = DB_PASS;
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        $pdo->exec("SET time_zone = '+05:30'");
        return $pdo;
    } catch (\PDOException $e) {
        // If DB doesn't exist, we might want to catch that differently for installer
        // but for now, throw error.
        throw new \PDOException($e->getMessage(), (int) $e->getCode());
    }
}
?>