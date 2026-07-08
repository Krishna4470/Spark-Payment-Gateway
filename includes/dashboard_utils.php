<?php
// includes/dashboard_utils.php

require_once __DIR__ . '/db.php';

session_start();

function checkAuth()
{
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header("Location: login.php");
        exit;
    }
}

function getSetting($key)
{
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT setting_value FROM config_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    return $stmt->fetchColumn();
}

function updateSetting($key, $value)
{
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("INSERT INTO config_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    return $stmt->execute([$key, $value, $value]);
}
?>