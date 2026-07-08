<?php
/**
 * BharatPe Integration Database Update Script
 * 
 * This script updates the database schema to support BharatPe payment gateway.
 * Run this file once to add necessary tables and settings.
 * 
 * Usage: Navigate to this file in your browser (e.g., yoursite.com/update.php)
 */

require_once 'includes/db.php';

// Prevent direct access in production (optional security)
// Uncomment the line below after running the update
// die("Update already completed. Please delete this file for security.");

try {
    $pdo = getDBConnection();

    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>BharatPe Integration - Database Update</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
            .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            h1 { color: #2563eb; border-bottom: 3px solid #2563eb; padding-bottom: 10px; }
            .success { background: #d1fae5; border-left: 4px solid #10b981; padding: 15px; margin: 10px 0; border-radius: 5px; }
            .error { background: #fee2e2; border-left: 4px solid #ef4444; padding: 15px; margin: 10px 0; border-radius: 5px; }
            .info { background: #dbeafe; border-left: 4px solid #3b82f6; padding: 15px; margin: 10px 0; border-radius: 5px; }
            .step { margin: 20px 0; padding: 15px; background: #f9fafb; border-radius: 5px; }
            .step-title { font-weight: bold; color: #1f2937; margin-bottom: 10px; }
            code { background: #e5e7eb; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
            .warning { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 10px 0; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>🚀 BharatPe Integration - Database Update</h1>
            <p>This script will update your database to support BharatPe payment gateway.</p>
    ";

    $updateLog = [];
    $hasErrors = false;

    // Step 1: Create bharatpe_tokens table
    echo "<div class='step'>";
    echo "<div class='step-title'>Step 1: Creating bharatpe_tokens table...</div>";
    try {
        $sql = "CREATE TABLE IF NOT EXISTS bharatpe_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_token VARCHAR(255) DEFAULT 'admin',
            merchantId VARCHAR(255),
            token TEXT,
            cookie TEXT,
            Upiid VARCHAR(255),
            is_active BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $pdo->exec($sql);
        echo "<div class='success'>✅ Table <code>bharatpe_tokens</code> created successfully!</div>";
        $updateLog[] = "bharatpe_tokens table created";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "<div class='info'>ℹ️ Table <code>bharatpe_tokens</code> already exists. Skipping...</div>";
            $updateLog[] = "bharatpe_tokens table already exists";
        } else {
            echo "<div class='error'>❌ Error creating bharatpe_tokens table: " . htmlspecialchars($e->getMessage()) . "</div>";
            $hasErrors = true;
        }
    }
    echo "</div>";

    // Step 2: Add active_gateway setting to config_settings
    echo "<div class='step'>";
    echo "<div class='step-title'>Step 2: Adding active_gateway configuration...</div>";
    try {
        // Check if config_settings table exists
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'config_settings'")->fetch();

        if ($tableCheck) {
            // Check if setting already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM config_settings WHERE setting_key = 'active_gateway'");
            $stmt->execute();
            $exists = $stmt->fetchColumn();

            if (!$exists) {
                $stmt = $pdo->prepare("INSERT INTO config_settings (setting_key, setting_value) VALUES ('active_gateway', 'PAYTM')");
                $stmt->execute();
                echo "<div class='success'>✅ Setting <code>active_gateway</code> added with default value 'PAYTM'</div>";
                $updateLog[] = "active_gateway setting added";
            } else {
                echo "<div class='info'>ℹ️ Setting <code>active_gateway</code> already exists. Skipping...</div>";
                $updateLog[] = "active_gateway setting already exists";
            }
        } else {
            echo "<div class='warning'>⚠️ Table <code>config_settings</code> does not exist. Creating it...</div>";

            $sql = "CREATE TABLE IF NOT EXISTS config_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            $pdo->exec($sql);

            // Now insert the setting
            $stmt = $pdo->prepare("INSERT INTO config_settings (setting_key, setting_value) VALUES ('active_gateway', 'PAYTM')");
            $stmt->execute();

            echo "<div class='success'>✅ Table <code>config_settings</code> created and <code>active_gateway</code> setting added!</div>";
            $updateLog[] = "config_settings table created with active_gateway";
        }
    } catch (PDOException $e) {
        echo "<div class='error'>❌ Error adding active_gateway setting: " . htmlspecialchars($e->getMessage()) . "</div>";
        $hasErrors = true;
    }
    echo "</div>";

    // Step 3: Verify merchants table exists
    echo "<div class='step'>";
    echo "<div class='step-title'>Step 3: Verifying merchants table...</div>";
    try {
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'merchants'")->fetch();

        if ($tableCheck) {
            echo "<div class='success'>✅ Table <code>merchants</code> exists.</div>";
            $updateLog[] = "merchants table verified";
        } else {
            echo "<div class='warning'>⚠️ Table <code>merchants</code> does not exist. Creating it...</div>";

            $sql = "CREATE TABLE IF NOT EXISTS merchants (
                id INT AUTO_INCREMENT PRIMARY KEY,
                mobile VARCHAR(20) NOT NULL,
                mid VARCHAR(100) NOT NULL,
                mkey VARCHAR(100) NOT NULL,
                vpa VARCHAR(100) NOT NULL,
                is_default BOOLEAN DEFAULT 0,
                status ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            $pdo->exec($sql);
            echo "<div class='success'>✅ Table <code>merchants</code> created successfully!</div>";
            $updateLog[] = "merchants table created";
        }
    } catch (PDOException $e) {
        echo "<div class='error'>❌ Error with merchants table: " . htmlspecialchars($e->getMessage()) . "</div>";
        $hasErrors = true;
    }
    echo "</div>";

    // Step 4: Revert Multi-User Data (CLEANUP)
    echo "<div class='step'>";
    echo "<div class='step-title'>Step 4: Reverting Multi-User Changes...</div>";
    try {
        // Drop Tables
        $tablesToDrop = ['user_subscriptions', 'plans', 'users'];
        foreach ($tablesToDrop as $table) {
            $pdo->exec("DROP TABLE IF EXISTS $table");
            echo "<div class='info'>🗑️ Dropped table <code>$table</code>.</div>";
        }

        // Drop Columns
        $tablesWithUserId = ['transactions', 'merchants', 'projects', 'bharatpe_tokens'];
        foreach ($tablesWithUserId as $table) {
            $tableExists = $pdo->query("SHOW TABLES LIKE '$table'")->fetch();
            if ($tableExists) {
                $cols = $pdo->query("SHOW COLUMNS FROM $table LIKE 'user_id'")->fetchAll();
                if (!empty($cols)) {
                    // Try to drop index first
                    try {
                        $pdo->exec("DROP INDEX idx_{$table}_user_id ON $table");
                    } catch (Exception $e) {
                    }

                    $pdo->exec("ALTER TABLE $table DROP COLUMN user_id");
                    echo "<div class='info'>🗑️ Removed <code>user_id</code> from <code>$table</code>.</div>";
                }
            }
        }

        echo "<div class='success'>✅ Database reverted to Single-User mode!</div>";
        $updateLog[] = "Reverted multi-user schema";

    } catch (PDOException $e) {
        echo "<div class='error'>❌ Error during cleanup: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    echo "</div>";

    // Summary
    echo "<div class='step'>";
    echo "<div class='step-title'>📊 Update Summary</div>";

    if (!$hasErrors) {
        echo "<div class='success'>";
        echo "<strong>✅ Database update completed successfully!</strong><br><br>";
        echo "<strong>Changes made:</strong><ul>";
        foreach ($updateLog as $log) {
            echo "<li>" . htmlspecialchars($log) . "</li>";
        }
        echo "</ul>";
        echo "<br><strong>Next Steps:</strong><br>";
        echo "1. Go to <code>Dashboard → Gateway Setup</code><br>";
        echo "2. Add your BharatPe credentials using the form<br>";
        echo "3. Set BharatPe as the active gateway if needed<br>";
        echo "4. <strong style='color: #dc2626;'>Delete this update.php file for security</strong>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<strong>⚠️ Update completed with some errors.</strong><br>";
        echo "Please check the error messages above and fix them manually.";
        echo "</div>";
    }
    echo "</div>";

    echo "</div></body></html>";

} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<strong>❌ Fatal Error:</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";
    echo "</div></body></html>";
}
?>