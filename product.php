<?php
require_once 'config.php';
require_once 'includes/db.php';
$pdo = getDBConnection();

$slug = $_GET['slug'] ?? '';
$orderId = $_GET['order_id'] ?? '';
$mode = $_GET['mode'] ?? 'view'; // view, pay, download

$product = null;
$error = '';

// 1. Fetch Product
if ($slug) {
    $stmt = $pdo->prepare("SELECT * FROM digital_products WHERE slug = ?");
    $stmt->execute([$slug]);
    $product = $stmt->fetch();
} elseif ($orderId) {
    // If we only have order_id, find the product
    $stmt = $pdo->prepare("SELECT p.*, o.id as db_order_id, o.status as order_status, o.download_token, o.customer_email, o.email_sent FROM product_orders o JOIN digital_products p ON o.product_id = p.id WHERE o.transaction_id = ?");
    $stmt->execute([$orderId]);
    $product = $stmt->fetch();

    if (!$product)
        $error = "Order not found.";
}

if (!$product && !$error) {
    $error = "Product not found.";
}

// 2. Handle Form Submission (Create Order)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode === 'view' && !$error) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    if ($name && $email && $phone) {
        // Create Transaction ID
        $txnId = 'PROD_' . time() . mt_rand(1000, 9999);
        $amount = $product['price'];

        try {
            // 1. Insert into Transactions (for Webhook/Status Check)
            // Assuming transactions table has: order_id, amount, status, created_at, description (optional)
            $stmt = $pdo->prepare("INSERT INTO transactions (order_id, amount, status, created_at) VALUES (?, ?, 'PENDING', NOW())");
            $stmt->execute([$txnId, $amount]);
            // $dbTxnId = $pdo->lastInsertId(); // Internal ID if needed - removed as per instruction

            // 2. Insert into Product Orders
            $token = bin2hex(random_bytes(16));
            $stmt = $pdo->prepare("INSERT INTO product_orders (product_id, customer_name, customer_email, customer_phone, transaction_id, amount, status, download_token) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)");
            $stmt->execute([$product['id'], $name, $email, $phone, $txnId, $amount, $token]);

            // Redirect to Pay Mode
            header("Location: product.php?order_id=$txnId&mode=pay");
            exit;

        } catch (Exception $e) {
            $error = "System Error: " . $e->getMessage();
        }
    } else {
        $error = "Please fill all fields.";
    }
}

// 3. Prepare View Data
$vpa = '';
if ($mode === 'pay' && !$error) {
    // Get Merchant VPA (Try Merchants table first, then Config)
    $stmt = $pdo->query("SELECT vpa FROM merchants WHERE is_default = 1 LIMIT 1");
    $vpa = $stmt->fetchColumn();

    if (!$vpa) {
        $stmt = $pdo->query("SELECT setting_value FROM config_settings WHERE setting_key = 'merchant_upi'");
        $vpa = $stmt->fetchColumn();
    }

    if (!$vpa)
        $error = "Merchant UPI not configured.";

    // Generate UPI Link
    // upi://pay?pa=...&pn=...&am=...&tr=...&tn=...
    $upiLink = "upi://pay?pa=$vpa&pn=Merchant&am={$product['price']}&tr=$orderId&tn=Purchase {$product['name']}";
    $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($upiLink);
}

// 4. Verify Download Mode
$emailSentMsg = false;
if ($mode === 'download' && $product) {
    // Check Status again to be sure
    if ($product['order_status'] !== 'completed') {
        // Double check DB
        $stmt = $pdo->prepare("SELECT status FROM transactions WHERE order_id = ?");
        $stmt->execute([$orderId]);
        $txnStatus = $stmt->fetchColumn();

        if ($txnStatus === 'SUCCESS') {
            // Sync status
            $pdo->prepare("UPDATE product_orders SET status = 'completed' WHERE transaction_id = ?")->execute([$orderId]);
            $product['order_status'] = 'completed';
        } else {
            // Redirect back to pay if not success
            header("Location: product.php?order_id=$orderId&mode=pay");
            exit;
        }
    }

    // handle Email Sending (One Time Code)
    // Use isset to handle missing column if DB not updated yet (graceful fallback)
    $emailSent = isset($product['email_sent']) ? $product['email_sent'] : 0;

    if ($emailSent == 0 && $product['order_status'] === 'completed') {
        require_once 'includes/mail_helper.php';
        $accessCode = mt_rand(100000, 999999);

        // Update DB first to avoid duplicate sends
        try {
            $stmt = $pdo->prepare("UPDATE product_orders SET email_access_code = ?, email_sent = 1 WHERE transaction_id = ?");
            $stmt->execute([$accessCode, $orderId]);

            // Send Email
            if (sendOrderEmail($product['customer_email'], $product['name'], $accessCode, $orderId)) {
                $emailSentMsg = true;
            }
        } catch (Exception $e) {
            // Ignore DB error if column missing, allow download to proceed
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= $product ? htmlspecialchars($product['name']) : 'Product Not Found' ?>
    </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">

    <?php if ($error): ?>
        <div class="bg-white p-8 rounded-xl shadow-lg text-center max-w-md w-full">
            <div class="text-red-500 text-5xl mb-4"><i class="fas fa-exclamation-circle"></i></div>
            <h2 class="text-xl font-bold text-gray-800 mb-2">Error</h2>
            <p class="text-gray-600">
                <?= htmlspecialchars($error) ?>
            </p>
            <a href="javascript:history.back()" class="mt-6 inline-block text-blue-600 font-medium hover:underline">Go
                Back</a>
        </div>
    <?php elseif ($mode === 'view'): ?>

        <?php if (isset($product['status']) && $product['status'] === 'disabled'): ?>
            <!-- PRODUCT UNAVAILABLE STATE -->
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden max-w-md w-full p-8 text-center">
                <div class="text-gray-300 text-6xl mb-6"><i class="fas fa-box-open"></i></div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Unavailable</h2>
                <p class="text-gray-500 mb-6">This product is currently not available for purchase.</p>
                <div class="text-sm text-gray-400"><?= htmlspecialchars($product['name']) ?></div>
            </div>
        <?php else: ?>
            <!-- VIEW & PURCHASE FORM -->
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden max-w-4xl w-full grid md:grid-cols-2">
                <div class="bg-gradient-to-br from-blue-600 to-indigo-700 p-8 text-white flex flex-col justify-center">
                    <div class="mb-6">
                        <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-medium uppercase tracking-wider">Digital
                            Product</span>
                    </div>
                    <h1 class="text-3xl font-bold mb-4">
                        <?= htmlspecialchars($product['name']) ?>
                    </h1>
                    <p class="text-blue-100 leading-relaxed mb-8 flex-grow">
                        <?= nl2br(htmlspecialchars($product['description'])) ?>
                    </p>
                    <div class="text-4xl font-bold">₹
                        <?= number_format($product['price'], 2) ?>
                    </div>
                </div>

                <div class="p-8">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">Enter Details to Buy</h2>
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Full Name</label>
                            <input type="text" name="name" required
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 transition-colors">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Email Address</label>
                            <input type="email" name="email" required
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 transition-colors">
                            <p class="text-xs text-gray-400 mt-1">Product will be sent to this email.</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Phone Number</label>
                            <input type="tel" name="phone" required
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 transition-colors">
                        </div>

                        <button type="submit"
                            class="w-full bg-blue-600 text-white font-bold py-4 rounded-lg hover:bg-blue-700 transition-transform active:scale-95 shadow-lg shadow-blue-500/30 mt-4">
                            Pay ₹
                            <?= number_format($product['price'], 2) ?>
                        </button>

                        <div class="text-center mt-4">
                            <p class="text-xs text-gray-400"><i class="fas fa-lock mr-1"></i> Secure Payment by UPI</p>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>


    <?php elseif ($mode === 'pay'): ?>
        <!-- QR CODE STATE (Matched to pay.php) -->
        <div id="paymentCard" class="bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden relative">

            <!-- Blue Header -->
            <div class="spark-header p-4 flex justify-between items-center text-white"
                style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);">
                <div class="flex items-center gap-3">
                    <div class="bg-white rounded p-1 w-10 h-10 flex items-center justify-center">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/e/e1/UPI-Logo-vector.svg/1200px-UPI-Logo-vector.svg.png"
                            class="w-full">
                    </div>
                    <div>
                        <h3 class="font-bold text-sm leading-tight"><?= htmlspecialchars($product['name']) ?></h3>
                        <span class="bg-blue-400 text-xs px-2 py-0.5 rounded-full flex items-center w-fit mt-0.5">
                            <i class="fas fa-check-circle mr-1 text-[10px]"></i> Verified
                        </span>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-xl font-bold">₹<?= number_format($product['price'], 2) ?></div>
                </div>
            </div>

            <!-- Red Timer -->
            <div class="bg-red-50 border-b border-red-100 text-red-600 text-center text-sm font-bold py-2">
                Complete payment in <span id="timer">05:00</span>
            </div>

            <!-- QR Section -->
            <div class="p-6 pb-2 text-center">
                <div class="inline-block p-1 border border-gray-200 rounded-lg shadow-sm mb-4">
                    <div class="relative">
                        <img src="<?= $qrUrl ?>" class="w-56 h-56 rounded-lg">
                    </div>
                </div>

                <p class="text-gray-500 text-xs mb-4">Scan QR code with any UPI app</p>

                <div class="flex justify-center mb-4">
                    <!-- Placeholder for logo if needed, or simple text -->
                    <div class="text-gray-400 text-xs">SECURE PAYMENT</div>
                </div>

                <!-- Mobile Deep Link -->
                <a href="<?= $upiLink ?>"
                    class="bg-blue-600 text-white w-full py-3 rounded-lg font-bold shadow mb-4 block md:hidden">
                    Open UPI App
                </a>
            </div>

            <!-- Detailed Footer -->
            <div class="bg-white border-t border-gray-100 px-6 py-4 space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Order ID:</span>
                    <span class="text-gray-800 font-mono">#<?= $orderId ?></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Order Amount:</span>
                    <span class="text-gray-800 font-bold">₹<?= number_format($product['price'], 2) ?></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Transaction ID:</span>
                    <span class="text-gray-800 font-mono text-xs truncate w-32 text-right"
                        id="pollingStatus">Checking...</span>
                </div>
            </div>

            <div class="bg-gray-50 p-3 text-center text-xs text-gray-400 border-t">
                All UPI Accepted<br>
                <span class="text-green-600 font-semibold">Secure Payment Gateway</span>
            </div>
        </div>

        <!-- Timeout Modal -->
        <div id="timeoutModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black bg-opacity-50"></div>
            <div
                class="bg-white rounded-lg shadow-2xl w-full max-w-sm p-6 text-center relative z-10 transition-transform transform scale-95">
                <div
                    class="w-16 h-16 rounded-full border-2 border-orange-300 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation text-orange-400 text-2xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Time Expired</h2>
                <p class="text-gray-500 text-sm mb-6">Payment session has ended</p>
                <button onclick="window.location.reload()"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded shadow font-bold transition">OK</button>
            </div>
        </div>

        <!-- Success Screen -->
        <div id="successCard" class="hidden bg-white rounded-lg shadow-xl w-full max-w-sm p-8 text-center">
            <div
                class="w-20 h-20 bg-green-100 text-green-500 rounded-full flex items-center justify-center mx-auto mb-6 text-4xl">
                <i class="fas fa-check"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Payment Successful</h2>
            <p class="text-gray-500 text-sm mb-6">Redirecting to download...</p>
        </div>

        <script>
            // Timer
            let t = 300; // 5 mins
            let timerEl = document.getElementById('timer');
            let interval = setInterval(() => {
                t--;
                if (t <= 0) {
                    clearInterval(interval);
                    timerEl.innerText = "00:00";
                    document.getElementById('timeoutModal').classList.remove('hidden');
                    return;
                }
                let m = Math.floor(t / 60), s = t % 60;
                timerEl.innerText = `0${m}:${s < 10 ? '0' + s : s}`;
            }, 1000);

            // Polling
            const orderId = "<?= $orderId ?>";

            setInterval(async () => {
                if (t <= 0) return; // Stop polling if expired
                try {
                    let res = await fetch('api/check_status_public.php?order_id=' + orderId);
                    let data = await res.json();
                    if (data.status === 'SUCCESS') {
                        document.getElementById('paymentCard').classList.add('hidden');
                        document.getElementById('successCard').classList.remove('hidden');

                        setTimeout(() => {
                            window.location.href = `product.php?order_id=${orderId}&mode=download`;
                        }, 1500);
                    }
                } catch (e) { }
            }, 3000);
        </script>

    <?php elseif ($mode === 'download'): ?>
        <!-- DOWNLOAD PAGE -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden max-w-md w-full text-center p-8">
            <div
                class="w-20 h-20 bg-green-100 text-green-500 rounded-full flex items-center justify-center text-4xl mx-auto mb-6">
                <i class="fas fa-check"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Payment Successful!</h2>
            <p class="text-gray-600 mb-8">Thank you for your purchase. You can now download your file.</p>

            <div class="bg-gray-50 rounded-lg p-4 mb-8 text-left border border-gray-100">
                <h3 class="font-bold text-gray-700 mb-1">
                    <?= htmlspecialchars($product['name']) ?>
                </h3>
                <p class="text-sm text-gray-500 truncate">
                    <?= htmlspecialchars($product['description']) ?>
                </p>
            </div>

            <a href="download.php?token=<?= htmlspecialchars($product['download_token']) ?>"
                class="block w-full bg-blue-600 text-white font-bold py-4 rounded-lg hover:bg-blue-700 transition-colors shadow-lg shadow-blue-500/30">
                <i class="fas fa-download mr-2"></i> Download File
            </a>
            <p class="text-xs text-gray-400 mt-4">Link will expire after limited uses.</p>
        </div>
    <?php endif; ?>

</body>

</html>