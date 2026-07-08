<?php
// transaction-detail.php - Modern Transaction Details Page
require_once 'config.php';
require_once 'includes/db.php';

$pdo = getDBConnection();

// Get order ID from URL
$orderId = isset($_GET['order_id']) ? $_GET['order_id'] : '';

if (!$orderId) {
    header("Location: /");
    exit;
}

// Fetch transaction details
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE order_id = ?");
$stmt->execute([$orderId]);
$txn = $stmt->fetch();

if (!$txn) {
    header("Location: /");
    exit;
}

// Determine if payment was successful
$isSuccess = ($txn['status'] == 'SUCCESS');
$isFailed = ($txn['status'] == 'FAILED');

// Get active gateway to determine payment method
$activeGateway = $pdo->query("SELECT setting_value FROM config_settings WHERE setting_key = 'active_gateway'")->fetchColumn() ?: 'PAYTM';

// Try to get payer name from BharatPe if available
$payerName = "Customer";
$payerUPI = "N/A";

if ($activeGateway == 'BHARATPE' && !empty($txn['paytm_txn_id'])) {
    require_once 'includes/BharatPeHelper.php';
    $bpStmt = $pdo->query("SELECT * FROM bharatpe_tokens WHERE is_active = 1 LIMIT 1");
    $bp = $bpStmt->fetch();

    if ($bp) {
        $txns = BharatPeHelper::getTransactions($bp['merchantId'], $bp['token'], $bp['cookie']);
        if ($txns && is_array($txns)) {
            foreach ($txns as $t) {
                if ($t['bankReferenceNo'] == $txn['paytm_txn_id']) {
                    $payerName = isset($t['payerName']) ? $t['payerName'] : $payerName;
                    $payerUPI = isset($t['payerHandle']) ? $t['payerHandle'] : $payerUPI;
                    break;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - Transaction Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes checkmark {
            0% {
                stroke-dashoffset: 100;
            }

            100% {
                stroke-dashoffset: 0;
            }
        }

        @keyframes scaleIn {
            0% {
                transform: scale(0);
                opacity: 0;
            }

            50% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .checkmark-circle {
            stroke-dasharray: 166;
            stroke-dashoffset: 166;
            animation: checkmark 0.6s ease-in-out 0.2s forwards;
        }

        .checkmark-check {
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            animation: checkmark 0.3s ease-in-out 0.8s forwards;
        }

        .success-icon {
            animation: scaleIn 0.5s ease-out;
        }

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
            opacity: 0;
        }

        .delay-1 {
            animation-delay: 0.2s;
        }

        .delay-2 {
            animation-delay: 0.4s;
        }

        .delay-3 {
            animation-delay: 0.6s;
        }

        .delay-4 {
            animation-delay: 0.8s;
        }

        @keyframes confetti {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 1;
            }

            100% {
                transform: translateY(100vh) rotate(720deg);
                opacity: 0;
            }
        }

        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background: #f0f;
            animation: confetti 3s ease-out forwards;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 min-h-screen flex items-center justify-center p-4">

    <!-- Confetti Effect -->
    <div id="confetti-container" class="pointer-events-none"></div>

    <div class="max-w-2xl w-full">

        <!-- Status Icon -->
        <div class="text-center mb-8 success-icon">
            <?php if ($isSuccess): ?>
                <svg class="w-32 h-32 mx-auto" viewBox="0 0 52 52">
                    <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none" stroke="#10b981" stroke-width="2" />
                    <path class="checkmark-check" fill="none" stroke="#10b981" stroke-width="3"
                        d="M14.1 27.2l7.1 7.2 16.7-16.8" />
                </svg>
            <?php else: ?>
                <div class="w-32 h-32 mx-auto rounded-full bg-red-100 flex items-center justify-center">
                    <i class="fas fa-times text-6xl text-red-500"></i>
                </div>
            <?php endif; ?>
        </div>

        <!-- Main Card -->
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden fade-in-up delay-1">

            <!-- Header -->
            <div
                class="bg-gradient-to-r <?= $isSuccess ? 'from-green-500 to-emerald-600' : 'from-red-500 to-rose-600' ?> px-8 py-6 text-center">
                <h1 class="text-3xl font-bold text-white mb-2">
                    <?= $isSuccess ? 'Payment Successful!' : 'Payment Failed' ?>
                </h1>
                <p class="<?= $isSuccess ? 'text-green-100' : 'text-red-100' ?>">
                    <?= $isSuccess ? 'Your transaction has been completed successfully' : 'Transaction could not be completed within time limit' ?>
                </p>
            </div>

            <!-- Amount Display -->
            <div
                class="bg-gradient-to-br <?= $isSuccess ? 'from-green-50 to-emerald-50' : 'from-red-50 to-rose-50' ?> px-8 py-8 text-center border-b-4 <?= $isSuccess ? 'border-green-500' : 'border-red-500' ?> fade-in-up delay-2">
                <div class="text-sm text-gray-600 font-semibold uppercase tracking-wider mb-2">
                    <?= $isSuccess ? 'Amount Paid' : 'Amount' ?>
                </div>
                <div class="text-6xl font-bold <?= $isSuccess ? 'text-green-600' : 'text-red-600' ?> mb-2">₹
                    <?= number_format($txn['amount'], 2) ?>
                </div>
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-white rounded-full shadow-sm">
                    <span
                        class="w-2 h-2 <?= $isSuccess ? 'bg-green-500 animate-pulse' : 'bg-red-500' ?> rounded-full"></span>
                    <span class="text-sm text-gray-600 font-medium">
                        <?= $isSuccess ? 'Verified Payment' : 'Payment Not Completed' ?>
                    </span>
                </div>
            </div>

            <!-- Transaction Details -->
            <div class="px-8 py-8 space-y-6 fade-in-up delay-3">

                <?php if ($isFailed): ?>
                    <!-- Failed Message -->
                    <div class="bg-red-50 border-2 border-red-200 rounded-2xl p-6 text-center">
                        <i class="fas fa-exclamation-triangle text-4xl text-red-500 mb-3"></i>
                        <h3 class="text-xl font-bold text-red-700 mb-2">Payment Time Expired</h3>
                        <p class="text-gray-600 mb-4">The payment window has closed. Please create a new payment request to
                            try again.</p>
                        <div class="text-sm text-gray-500">
                            <i class="fas fa-info-circle"></i> Payments must be completed within 5 minutes
                        </div>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <!-- Payer Name -->
                    <div class="bg-gray-50 rounded-2xl p-5 border border-gray-100 hover:shadow-md transition-shadow">
                        <div class="flex items-start gap-4">
                            <div
                                class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-user text-blue-600 text-xl"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs text-gray-500 font-semibold uppercase tracking-wide mb-1">Payer Name
                                </div>
                                <div class="text-lg font-bold text-gray-800 truncate">
                                    <?= htmlspecialchars($payerName) ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- UPI Handle -->
                    <div class="bg-gray-50 rounded-2xl p-5 border border-gray-100 hover:shadow-md transition-shadow">
                        <div class="flex items-start gap-4">
                            <div
                                class="w-12 h-12 rounded-xl bg-purple-100 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-mobile-alt text-purple-600 text-xl"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs text-gray-500 font-semibold uppercase tracking-wide mb-1">UPI Handle
                                </div>
                                <div class="text-lg font-bold text-gray-800 truncate">
                                    <?= htmlspecialchars($payerUPI) ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transaction ID (UTR) -->
                    <div
                        class="bg-gray-50 rounded-2xl p-5 border border-gray-100 hover:shadow-md transition-shadow md:col-span-2">
                        <div class="flex items-start gap-4">
                            <div
                                class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-receipt text-green-600 text-xl"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs text-gray-500 font-semibold uppercase tracking-wide mb-1">
                                    Transaction ID (UTR)</div>
                                <div class="flex items-center gap-3">
                                    <div class="text-lg font-mono font-bold text-gray-800 truncate flex-1">
                                        <?= htmlspecialchars($txn['paytm_txn_id']) ?>
                                    </div>
                                    <button onclick="copyUTR()" id="copyUTRBtn"
                                        class="px-4 py-2 bg-white border-2 border-gray-200 rounded-lg hover:bg-gray-50 transition-all active:scale-95">
                                        <i class="far fa-copy text-gray-600"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order ID -->
                    <div class="bg-gray-50 rounded-2xl p-5 border border-gray-100 hover:shadow-md transition-shadow">
                        <div class="flex items-start gap-4">
                            <div
                                class="w-12 h-12 rounded-xl bg-orange-100 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-hashtag text-orange-600 text-xl"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs text-gray-500 font-semibold uppercase tracking-wide mb-1">Order ID
                                </div>
                                <div class="text-sm font-mono font-bold text-gray-800 truncate">
                                    <?= htmlspecialchars($txn['order_id']) ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="bg-gray-50 rounded-2xl p-5 border border-gray-100 hover:shadow-md transition-shadow">
                        <div class="flex items-start gap-4">
                            <div
                                class="w-12 h-12 rounded-xl bg-pink-100 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-credit-card text-pink-600 text-xl"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs text-gray-500 font-semibold uppercase tracking-wide mb-1">Payment
                                    Method</div>
                                <div class="text-lg font-bold text-gray-800">
                                    <?= $activeGateway == 'BHARATPE' ? 'BharatPe UPI' : 'Paytm UPI' ?>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Timestamp -->
                <div class="bg-blue-50 rounded-2xl p-5 border border-blue-100">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-clock text-blue-600 text-xl"></i>
                            <div>
                                <div class="text-xs text-gray-500 font-semibold uppercase tracking-wide">Payment Time
                                </div>
                                <div class="text-sm font-bold text-gray-800">
                                    <?= date('d M Y, h:i A', strtotime($txn['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="inline-flex items-center gap-2 px-3 py-1 bg-green-100 rounded-full">
                                <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                <span class="text-xs font-bold text-green-700">SUCCESS</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Footer Actions -->
            <div class="px-8 py-6 bg-gray-50 border-t border-gray-200 fade-in-up delay-4">
                <div class="flex flex-col sm:flex-row gap-3">
                    <button onclick="window.print()"
                        class="flex-1 flex items-center justify-center gap-2 px-6 py-3 bg-white border-2 border-gray-200 rounded-xl font-semibold text-gray-700 hover:bg-gray-50 transition-all active:scale-95">
                        <i class="fas fa-print"></i>
                        <span>Print Receipt</span>
                    </button>
                    <a href="/"
                        class="flex-1 flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl font-semibold text-white shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all active:translate-y-0">
                        <i class="fas fa-home"></i>
                        <span>Back to Home</span>
                    </a>
                </div>
            </div>

        </div>

        <!-- Footer Note -->
        <div class="text-center mt-6 fade-in-up delay-4">
            <p class="text-sm text-gray-600">
                <i class="fas fa-shield-alt text-green-500"></i>
                This is a secure transaction. Keep this receipt for your records.
            </p>
        </div>

    </div>

    <script>
        // Copy UTR function
        function copyUTR() {
            const utr = "<?= htmlspecialchars($txn['paytm_txn_id']) ?>";
            navigator.clipboard.writeText(utr).then(() => {
                const btn = document.getElementById('copyUTRBtn');
                const icon = btn.querySelector('i');

                icon.className = 'fas fa-check text-green-500';
                btn.classList.add('bg-green-50', 'border-green-500');

                setTimeout(() => {
                    icon.className = 'far fa-copy text-gray-600';
                    btn.classList.remove('bg-green-50', 'border-green-500');
                }, 2000);
            });
        }

        // Confetti effect
        function createConfetti() {
            const container = document.getElementById('confetti-container');
            const colors = ['#f0f', '#0ff', '#ff0', '#0f0', '#f00', '#00f'];

            for (let i = 0; i < 50; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti';
                    confetti.style.left = Math.random() * 100 + '%';
                    confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
                    confetti.style.animationDelay = Math.random() * 0.5 + 's';
                    confetti.style.animationDuration = (Math.random() * 2 + 2) + 's';
                    container.appendChild(confetti);

                    setTimeout(() => confetti.remove(), 3000);
                }, i * 30);
            }
        }

        // Trigger confetti on load (only for successful payments)
        window.addEventListener('load', () => {
            <?php if ($isSuccess): ?>
                setTimeout(createConfetti, 500);
            <?php endif; ?>
        });
    </script>

</body>

</html>