<?php
// crons/expire_orders.php

// Adjust path to config based on folder structure
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

$pdo = getDBConnection();

echo "Running Cron: Expire Pending Orders...\n";

// Logic: Update Status to FAILED for PENDING orders older than 10 minutes
$sql = "UPDATE transactions 
        SET status = 'FAILED' 
        WHERE status = 'PENDING' 
        AND created_at < (NOW() - INTERVAL 10 MINUTE)";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$affected = $stmt->rowCount();

echo "Done. $affected orders marked as FAILED.\n";
?>