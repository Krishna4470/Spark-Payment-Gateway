<?php
// pay.php - Blue Spark Theme + One Time Link Logic + Timeout Modal
require_once 'config.php';
require_once 'includes/db.php';

$pdo = getDBConnection();

$slug = isset($_GET['slug']) ? $_GET['slug'] : '';
$orderId = isset($_GET['order_id']) ? $_GET['order_id'] : '';
$error = "";
$link = null;
$payAmount = 0;
$step = 'FORM';
$redirectUrl = '';

// --- MODE A: External API Order ---
if ($orderId) {
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE order_id = ?");
    $stmt->execute([$orderId]);
    $txn = $stmt->fetch();

    if (!$txn) {
        $error = "Invalid Order ID";
    } elseif ($txn['status'] == 'SUCCESS') {
        // Already Paid
        if (!empty($txn['client_callback_url'])) {
            header("Location: " . $txn['client_callback_url'] . "?status=SUCCESS&order_id=" . $orderId);
            exit;
        } else {
            $error = "Payment Already Completed";
        }
    } else {
        // Pending Payment
        $payAmount = $txn['amount'];
        $redirectUrl = $txn['client_callback_url'];
        $step = 'QR'; // Skip form, go straight to QR
    }
}
// --- MODE B: Internal Payment Link ---
elseif ($slug) {
    if ($slug == 'default') {
        $link = ['title' => 'Direct Payment', 'amount' => 0];
    } else {
        // Check if Active
        $stmt = $pdo->prepare("SELECT * FROM payment_links WHERE slug = ?");
        $stmt->execute([$slug]);
        $link = $stmt->fetch();
    }

    // Fix: If link has pre-defined amount, set it immediately
    if ($link && isset($link['amount']) && $link['amount'] > 0) {
        $payAmount = $link['amount'];
    }
}


// 2. Fetch Theme & Merchant
try {
    $stmt = $pdo->query("SELECT * FROM merchants WHERE is_default = 1 LIMIT 1");
    $merchant = $stmt->fetch();
    $mid = $merchant ? $merchant['mid'] : '';
    $vpa = $merchant ? $merchant['vpa'] : '';

    $s = $pdo->query("SELECT setting_key, setting_value FROM config_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    $themeTitle = isset($s['theme_title']) ? $s['theme_title'] : 'Spark';
    $themeColor = isset($s['theme_color']) ? $s['theme_color'] : '#3b82f6';
    $supportUrl = isset($s['support_url']) && !empty($s['support_url']) ? $s['support_url'] : '#';
    $activeGateway = isset($s['active_gateway']) ? $s['active_gateway'] : 'PAYTM';

} catch (Exception $e) {
}

// 3. Handle Form Submission (Only for Mode B - Internal Links needing amount)
if (
    !$error && !$orderId && ($_SERVER['REQUEST_METHOD'] === 'POST' || ($link && $link['amount'] > 0) ||
        (isset($_GET['amount']) && $_GET['amount'] > 0))
) {
    if (!$vpa && $activeGateway !== 'BHARATPE') {
        $error = "Merchant VPA Missing";
    } else {
        if ($payAmount == 0) {
            $payAmount = isset($_POST['amount']) ? $_POST['amount'] : (isset($_GET['amount']) ? $_GET['amount'] : 0);
        }

        if ($payAmount > 0) {
            $orderId = "PLNK" . time() . mt_rand(1000, 9999);
            $customerId = "CUST" . time();
            $linkId = ($slug && $slug != 'default') ? $link['id'] : NULL;

            $stmt = $pdo->prepare("INSERT INTO transactions (order_id, customer_id, amount, status, link_id) VALUES (?, ?, ?,
    'PENDING', ?)");
            $stmt->execute([$orderId, $customerId, $payAmount, $linkId]);

            $step = 'QR';
        }
    }
}

// 4. Generate QR (For both modes)
$qrUrl = '';
if ($step == 'QR' && !$error) {

    // Choose VPA based on Gateway
    $finalVpa = $vpa; // Default (Paytm)
    $finalNote = $themeTitle;
    $finalTr = $orderId;

    // If BharatPe is Active, fetch its VPA
    if (isset($activeGateway) && $activeGateway == 'BHARATPE') {
        $stmt = $pdo->query("SELECT Upiid, merchantId FROM bharatpe_tokens WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
        $bp = $stmt->fetch();
        if ($bp) {
            $finalVpa = $bp['Upiid'];
            $finalNote = "Payment for $orderId";
            // BharatPe specific params often used in their own app's QR
            // But standard UPI URI works for all. 
        }
    }

    // Standard UPI String for ALL Gateways
    if (strpos($finalVpa, 'http') === 0) {
        $error = "Configuration Error: Invalid VPA (Contains URL)";
        $step = 'ERROR';
    } else {
        $upiString = "upi://pay?pa=" . urlencode($finalVpa) . "&pn=" . urlencode($finalNote) . "&tr=" . urlencode($finalTr) . "&am=" . $payAmount . "&cu=INR&tn=" . urlencode($finalNote);
        $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=350x350&data=" . urlencode($upiString);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title><?= htmlspecialchars($themeTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #eff2f5;
            font-family: 'Segoe UI', sans-serif;
            -webkit-user-select: none;
            /* Safari */
            -ms-user-select: none;
            /* IE 10 and IE 11 */
            user-select: none;
            /* Standard syntax */
        }

        .spark-header {
            background:
                <?= $themeColor ?>
            ;
            /* Fallback if color is light? Maybe text shadow? For now assume user picks good colors */
        }

        /* Modal Animation */
        .modal {
            transition: opacity 0.3s ease;
            opacity: 0;
            pointer-events: none;
        }

        .modal.active {
            opacity: 1;
            pointer-events: auto;
        }

        .modal-content {
            transform: scale(0.95);
            transition: transform 0.3s ease;
        }

        .modal.active .modal-content {
            transform: scale(1);
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4">

    <!-- ERROR STATE -->
    <?php if ($error): ?>
        <div class="bg-white rounded-lg shadow-lg p-8 text-center max-w-md w-full">
            <div class="text-red-500 text-5xl mb-4"><i class="fas fa-times-circle"></i></div>
            <h2 class="text-xl font-bold text-gray-800 mb-2">Link Expired</h2>
            <p class="text-gray-500 text-sm"><?= $error ?></p>
        </div>

        <!-- AMOUNT FORM -->
    <?php elseif ($step == 'FORM'): ?>
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden">
            <!-- Blue Header -->
            <div class="spark-header p-4 flex justify-between items-center text-white">
                <div class="flex items-center gap-3">
                    <div class="bg-white rounded p-1 w-10 h-10 flex items-center justify-center">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/e/e1/UPI-Logo-vector.svg/1200px-UPI-Logo-vector.svg.png"
                            class="w-full">
                    </div>
                    <div>
                        <h3 class="font-bold text-sm leading-tight"><?= htmlspecialchars($themeTitle) ?></h3>
                        <span class="bg-blue-400 text-xs px-2 py-0.5 rounded-full flex items-center w-fit mt-0.5">
                            <i class="fas fa-check-circle mr-1 text-[10px]"></i> Verified
                        </span>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-xl font-bold">₹0.00</div>
                </div>
            </div>

            <div class="p-6">
                <form method="post">
                    <label class="block text-xs font-bold text-gray-500 mb-2 uppercase">Amount (INR)</label>
                    <div
                        class="flex items-center border rounded-md px-3 py-2 border-gray-300 focus-within:border-blue-500 transition mb-6">
                        <span class="text-gray-500 font-bold mr-2">₹</span>
                        <input type="number" step="0.01" name="amount"
                            class="w-full outline-none font-bold text-lg text-gray-700" placeholder="0.00" required
                            autofocus>
                    </div>

                    <button
                        class="w-full spark-header text-white font-bold py-3 rounded shadow hover:opacity-90 transition">
                        Pay Now
                    </button>
                </form>
            </div>
            <div class="bg-gray-50 h-2"></div>
        </div>

        <!-- QR CODE STATE -->
    <?php elseif ($step == 'QR'): ?>
        <div id="paymentCard" class="bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden relative">

            <!-- Blue Header -->
            <div class="spark-header p-4 flex justify-between items-center text-white">
                <div class="flex items-center gap-3">
                    <div class="bg-white rounded p-1 w-10 h-10 flex items-center justify-center">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/e/e1/UPI-Logo-vector.svg/1200px-UPI-Logo-vector.svg.png"
                            class="w-full">
                    </div>
                    <div>
                        <h3 class="font-bold text-sm leading-tight"><?= htmlspecialchars($themeTitle) ?></h3>
                        <span class="bg-blue-400 text-xs px-2 py-0.5 rounded-full flex items-center w-fit mt-0.5">
                            <i class="fas fa-check-circle mr-1 text-[10px]"></i> Verified
                        </span>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-xl font-bold">₹<?= number_format($payAmount, 2) ?></div>
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
                        <!-- Removed Orange O Overlay -->
                    </div>
                </div>

                <p class="text-gray-500 text-xs mb-4">Scan QR code with any UPI app</p>

                <div class="flex justify-center mb-4">
                    <img src="images/paylogo.png" class="h-10 object-contain">
                </div>
                <!-- Mobile Deep Link -->
                <a href="<?= $upiString ?>"
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
                    <span class="text-gray-800 font-bold">₹<?= number_format($payAmount, 2) ?></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Transaction ID:</span>
                    <span class="text-gray-800 font-mono text-xs truncate w-32 text-right"
                        id="pollingStatus">Checking...</span>
                </div>
            </div>

            <div class="bg-gray-50 p-3 text-center text-xs text-gray-400 border-t">
                All UPI Accepted<br>
                Need help? <a href="<?= htmlspecialchars($supportUrl) ?>" target="_blank"
                    class="text-green-600 font-semibold">Support</a>
            </div>
        </div>

        <!-- Timeout Modal -->
        <div id="timeoutModal" class="modal fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black bg-opacity-50"></div>
            <div class="modal-content bg-white rounded-lg shadow-2xl w-full max-w-sm p-6 text-center relative z-10">
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
            <p class="text-gray-500 text-sm mb-6">Redirecting...</p>
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

                    // Mark transaction as failed
                    const orderId = "<?= $orderId ?>";
                    const redirectUrl = "<?= $redirectUrl ?>";

                    fetch('api/mark_failed.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'order_id=' + orderId
                    }).then(() => {
                        // Redirect based on order type
                        if (redirectUrl) {
                            // API Order - redirect to client callback with failed status
                            window.location.href = redirectUrl + (redirectUrl.includes('?') ? '&' : '?') + 'status=FAILED&order_id=' + orderId;
                        } else {
                            // Payment Link - redirect to transaction details page
                            window.location.href = 'transaction-detail.php?order_id=' + orderId;
                        }
                    });

                    return;
                }
                let m = Math.floor(t / 60), s = t % 60;
                timerEl.innerText = `0${m}:${s < 10 ? '0' + s : s}`;
            }, 1000);

            // Polling
            const orderId = "<?= $orderId ?>";
            const redirectUrl = "<?= $redirectUrl ?>";

            setInterval(async () => {
                if (t <= 0) return; // Stop polling if expired
                try {
                    let res = await fetch('api/check_status_public.php?order_id=' + orderId);
                    let data = await res.json();
                    if (data.status === 'SUCCESS') {
                        document.getElementById('paymentCard').classList.add('hidden');
                        document.getElementById('successCard').classList.remove('hidden');

                        // Redirect logic
                        if (redirectUrl) {
                            // API Order - redirect to client callback
                            setTimeout(() => {
                                window.location.href = redirectUrl + (redirectUrl.includes('?') ? '&' : '?') + 'status=SUCCESS&order_id=' + orderId;
                            }, 1500);
                        } else {
                            // Payment Link - redirect to transaction details page
                            setTimeout(() => {
                                window.location.href = 'transaction-detail.php?order_id=' + orderId;
                            }, 2000);
                        }
                    }
                } catch (e) { }
            }, 3000);
        </script>
    <?php endif; ?>

    <!-- Security Scripts -->
    <script>
        // Disable Right Click
        document.addEventListener('contextmenu', event => event.preventDefault());

        // Disable Inspect Element & View Source
        document.onkeydown = function (e) {
            if (
                e.keyCode == 123 || // F12
                (e.ctrlKey && e.keyCode == 85) || // Ctrl+U
                (e.ctrlKey && e.shiftKey && e.keyCode == 73) || // Ctrl+Shift+I
                (e.ctrlKey && e.shiftKey && e.keyCode == 74) || // Ctrl+Shift+J
                (e.ctrlKey && e.shiftKey && e.keyCode == 67) // Ctrl+Shift+C
            ) {
                e.preventDefault();
                window.location.href = "https://www.google.com";
                return false;
            }
        }
    </script>
</body>

</html>