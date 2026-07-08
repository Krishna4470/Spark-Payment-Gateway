<?php
// install.php - Modern Setup Wizard
error_reporting(E_ALL);
ini_set('display_errors', 1);

$step = 1;
$error = '';
$success = '';

// Helper to determine protocol http/https
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$defaultUrl = $protocol . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect Data
    $dbHost = $_POST['db_host'] ?? 'localhost';
    $dbName = $_POST['db_name'] ?? '';
    $dbUser = $_POST['db_user'] ?? '';
    $dbPass = $_POST['db_pass'] ?? '';

    $siteName = $_POST['site_name'] ?? 'My Payment Gateway';
    $siteUrl = $_POST['site_url'] ?? $defaultUrl;

    $adminName = $_POST['admin_name'] ?? 'Admin';
    $adminPass = $_POST['admin_pass'] ?? '';
    $secQuestion = $_POST['sec_question'] ?? '';
    $secAnswer = $_POST['sec_answer'] ?? '';

    // Validate
    if (empty($dbName) || empty($dbUser) || empty($adminPass) || empty($siteUrl)) {
        $error = "Please fill all required fields.";
    } else {
        try {
            // 1. Test Connection
            $pdo = new PDO("mysql:host=$dbHost", $dbUser, $dbPass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // 2. Create DB (if not exists) & Select
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$dbName`");

            // 3. Create Tables
            $sql = "
            CREATE TABLE IF NOT EXISTS projects (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                domain VARCHAR(255) NOT NULL,
                api_key VARCHAR(64) NOT NULL UNIQUE,
                webhook_url VARCHAR(255),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS merchants (
                id INT AUTO_INCREMENT PRIMARY KEY,
                mobile VARCHAR(20) NOT NULL,
                mid VARCHAR(100) NOT NULL,
                mkey VARCHAR(100) NOT NULL,
                vpa VARCHAR(100) NOT NULL,
                is_default BOOLEAN DEFAULT 0,
                status ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS transactions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                project_id INT NULL, 
                link_id INT NULL,
                order_id VARCHAR(100) NOT NULL,
                customer_id VARCHAR(100), 
                client_callback_url TEXT,
                txn_token VARCHAR(255),
                amount DECIMAL(10,2) NOT NULL,
                status ENUM('PENDING', 'SUCCESS', 'FAILED') DEFAULT 'PENDING',
                paytm_txn_id VARCHAR(100),
                paytm_response TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX (order_id)
            );

            CREATE TABLE IF NOT EXISTS payment_links (
                id INT AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(50) NOT NULL UNIQUE,
                title VARCHAR(100),
                amount DECIMAL(10,2) NULL,
                is_active BOOLEAN DEFAULT TRUE,
                expiry_date DATETIME NULL,
                usage_limit INT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS config_settings (
                setting_key VARCHAR(50) PRIMARY KEY,
                setting_value TEXT
            );

            CREATE TABLE IF NOT EXISTS webhook_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                transaction_id INT,
                payload TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS digital_products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                file_path VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                status ENUM('active', 'disabled') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS product_orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                customer_name VARCHAR(255) NOT NULL,
                customer_email VARCHAR(255) NOT NULL,
                customer_phone VARCHAR(20) NOT NULL,
                transaction_id VARCHAR(255),
                amount DECIMAL(10, 2) NOT NULL,
                status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
                download_token VARCHAR(255) UNIQUE,
                download_count INT DEFAULT 0,
                email_access_code VARCHAR(10) DEFAULT NULL,
                email_sent TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (product_id) REFERENCES digital_products(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS bharatpe_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_token VARCHAR(255) DEFAULT 'admin', 
                merchantId VARCHAR(255),
                token TEXT,
                cookie TEXT,
                Upiid VARCHAR(255),
                is_active BOOLEAN DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            ";
            $pdo->exec($sql);

            // 4. Insert Defaults
            $defaults = [
                'site_name' => $siteName,
                'admin_name' => $adminName,
                'admin_password' => $adminPass,
                'security_question' => $secQuestion,
                'security_answer' => $secAnswer,
                'paytm_env' => 'PROD',
                'theme_color' => '#3b82f6',
                'theme_title' => $siteName,
                'favicon_path' => ''
            ];

            $stmt = $pdo->prepare("INSERT IGNORE INTO config_settings (setting_key, setting_value) VALUES (?, ?)");
            foreach ($defaults as $k => $v) {
                $stmt->execute([$k, $v]);
            }

            // 5. Write config.php
            $configContent = "<?php
// config.php - Main Configuration

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database Credentials
define('DB_HOST', '$dbHost');
define('DB_NAME', '$dbName');
define('DB_USER', '$dbUser');
define('DB_PASS', '$dbPass');

// System URL
define('BASE_URL', '$siteUrl');

// Connect to DB to fetch Settings
try {
    \$dsn = \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=utf8mb4\";
    // Options for consistent PDO behaviour
    \$options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    \$pdo = new PDO(\$dsn, DB_USER, DB_PASS, \$options);
    
    // Fallback Settings Check
    // We don't fetch all here to avoid overhead, pages should fetch what they need
    // But for legacy pay.php compatibility we might define constants if needed. 
    // For now, minimal is best.

} catch (Exception \$e) {
   // Allowed to fail if DB not reachable
}

date_default_timezone_set('Asia/Kolkata');
?>";

            file_put_contents('config.php', $configContent);

            $step = 2; // Success Step

        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Wizard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">

    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden">

        <!-- Header -->
        <div class="bg-blue-600 px-8 py-6 text-white flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold">Installation Wizard</h1>
                <p class="text-blue-100 text-sm mt-1">Setup your Payment Gateway in 2 minutes</p>
            </div>
            <div class="text-3xl opacity-20"><i class="fas fa-rocket"></i></div>
        </div>

        <?php if ($step === 1): ?>
            <!-- Form Step -->
            <form method="post" class="p-8 space-y-8">

                <?php if ($error): ?>
                    <div
                        class="bg-red-50 text-red-600 p-4 rounded-lg text-sm font-semibold border border-red-100 flex items-center gap-2">
                        <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <!-- 1. Database Details -->
                <div class="space-y-4">
                    <h3 class="text-gray-800 font-bold border-b pb-2 flex items-center gap-2">
                        <i class="fas fa-database text-blue-500"></i> Database Configuration
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">DB Host</label>
                            <input type="text" name="db_host" value="localhost"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-blue-500"
                                required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">DB Name</label>
                            <input type="text" name="db_name" placeholder="e.g. u12345_paytm"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-blue-500"
                                required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">DB User</label>
                            <input type="text" name="db_user" placeholder="DB Username"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-blue-500"
                                required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">DB Password</label>
                            <input type="password" name="db_pass" placeholder="DB Password"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                <!-- 2. Website & Admin -->
                <div class="space-y-4">
                    <h3 class="text-gray-800 font-bold border-b pb-2 flex items-center gap-2">
                        <i class="fas fa-cogs text-indigo-500"></i> Website & Admin Setup
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="col-span-1 md:col-span-2">
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Website URL (Base
                                URL)</label>
                            <input type="url" name="site_url" value="<?= htmlspecialchars($defaultUrl) ?>"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500"
                                required>
                            <p class="text-xs text-gray-400 mt-1">Must include trailing slash (e.g. https://domain.com/)</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Website Name</label>
                            <input type="text" name="site_name" value="Paytm Gateway"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500"
                                required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Admin Name</label>
                            <input type="text" name="admin_name" value="Administrator"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500"
                                required>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Admin Password</label>
                            <input type="text" name="admin_pass" placeholder="Set a strong password"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-red-500"
                                required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Security Question</label>
                            <select name="sec_question"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500">
                                <option>What is your pet name?</option>
                                <option>What is your favorite color?</option>
                                <option>What city were you born in?</option>
                            </select>
                        </div>
                        <div class="col-span-1 md:col-span-2">
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Security Answer</label>
                            <input type="text" name="sec_answer" placeholder="Answer to recover password"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500"
                                required>
                        </div>
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-xl shadow-lg transform transition active:scale-95 flex items-center justify-center gap-2">
                        Install Now <i class="fas fa-arrow-right"></i>
                    </button>
                    <p class="text-center text-xs text-gray-400 mt-4">By installing, you agree to the software license.</p>
                </div>

            </form>

        <?php elseif ($step === 2): ?>
            <!-- Success Step -->
            <div class="p-12 text-center space-y-6">
                <div
                    class="w-24 h-24 bg-green-100 text-green-500 rounded-full flex items-center justify-center mx-auto text-5xl animate-bounce">
                    <i class="fas fa-check"></i>
                </div>

                <h2 class="text-3xl font-bold text-gray-800">Installation Successful!</h2>
                <p class="text-gray-600">Your configuration has been saved and the database is ready.</p>

                <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 text-left space-y-2 text-sm max-w-sm mx-auto">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Admin Login:</span>
                        <a href="dashboard/login.php" class="text-blue-600 font-bold hover:underline">Click Here</a>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Username:</span>
                        <span class="font-mono text-gray-800"><?= htmlspecialchars($adminName) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Password:</span>
                        <span class="font-mono text-gray-800"><?= htmlspecialchars($adminPass) ?></span>
                    </div>
                </div>

                <div class="pt-6">
                    <a href="dashboard/login.php"
                        class="inline-block bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-full shadow-lg transition transform hover:-translate-y-1">
                        Go to Login Page
                    </a>
                </div>

                <div class="bg-yellow-50 text-yellow-800 text-xs p-3 rounded mt-4">
                    <i class="fas fa-exclamation-triangle mr-1"></i> Please delete <strong>install.php</strong> file for
                    security.
                </div>
            </div>
        <?php endif; ?>

    </div>

</body>

</html>