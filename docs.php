<?php
// docs.php - Public API Documentation
require_once 'config.php';
$baseUrl = BASE_URL;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Developer API Documentation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        dark: {
                            900: '#111827',
                            800: '#1f2937',
                            700: '#374151',
                        }
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50 min-h-screen text-gray-800">

    <!-- Header -->
    <header class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 py-4 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-lg bg-blue-600 flex items-center justify-center font-bold text-white shadow-lg shadow-blue-500/30 text-xl">
                    <i class="fas fa-code"></i>
                </div>
                <div>
                    <h1 class="font-bold text-xl tracking-tight text-gray-900">Developer Docs</h1>
                    <p class="text-xs text-gray-500">Official Payment Gateway API</p>
                </div>
            </div>

            <div
                class="bg-blue-50 text-blue-700 px-4 py-2 rounded-lg text-sm font-mono border border-blue-100 flex items-center gap-2">
                <span class="font-bold">BASE_URL:</span>
                <span>
                    <?= $baseUrl ?>
                </span>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8 space-y-8">

        <!-- API Key Section -->
        <div class="bg-gradient-to-r from-gray-900 to-gray-800 rounded-2xl shadow-xl overflow-hidden text-white p-8">
            <h3 class="text-lg font-bold mb-2 flex items-center gap-2">
                <i class="fas fa-key text-yellow-400"></i> Authentication
            </h3>
            <p class="text-gray-400 text-sm mb-6">Include your unique API Key in every request payload (parameter: <code
                    class="text-blue-300">user_token</code>). You can find this key in your Admin Dashboard.</p>

            <div class="bg-black/30 border border-gray-700 rounded-xl p-4 flex items-center justify-between">
                <code class="font-mono text-xl tracking-wide text-green-400">Your API KEY</code>
                <span class="text-xs text-gray-500 italic">Private Credential</span>
            </div>
        </div>

        <!-- 1. Create Order API -->
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
            <div class="px-8 py-6 border-b border-gray-50 flex items-center gap-3 bg-gray-50/50">
                <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded font-bold text-xs">POST</span>
                <h3 class="font-bold text-gray-800 text-lg">Create Payment Order</h3>
                <span class="text-gray-400 text-sm font-mono ml-auto">/api/create_order.php</span>
            </div>

            <div class="p-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Left: Params -->
                <div class="space-y-6">
                    <p class="text-gray-600 text-sm">Initiate a transaction. You will receive a <code
                            class="bg-gray-100 px-1 rounded">payment_url</code> in response, where you should redirect
                        the
                        user.</p>

                    <h4 class="font-bold text-gray-800 text-sm uppercase tracking-wider">Parameters</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                                <tr>
                                    <th class="p-3">Field</th>
                                    <th class="p-3">Type</th>
                                    <th class="p-3">Required</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr>
                                    <td class="p-3 font-mono text-blue-600">user_token</td>
                                    <td class="p-3">String</td>
                                    <td class="p-3 text-red-500 font-bold">Yes</td>
                                </tr>
                                <tr>
                                    <td class="p-3 font-mono text-blue-600">amount</td>
                                    <td class="p-3">Decimal</td>
                                    <td class="p-3 text-red-500 font-bold">Yes</td>
                                </tr>
                                <tr>
                                    <td class="p-3 font-mono text-blue-600">order_id</td>
                                    <td class="p-3">String</td>
                                    <td class="p-3 text-red-500 font-bold">Yes</td>
                                </tr>
                                <tr>
                                    <td class="p-3 font-mono text-blue-600">redirect_url</td>
                                    <td class="p-3">URL</td>
                                    <td class="p-3 text-gray-400">Optional</td>
                                </tr>
                                <tr>
                                    <td class="p-3 font-mono text-blue-600">customer_name</td>
                                    <td class="p-3">String</td>
                                    <td class="p-3 text-gray-400">Optional</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Right: Code Example -->
                <div class="bg-gray-900 rounded-xl p-6 overflow-hidden relative group">
                    <div class="flex justify-between items-center mb-4 border-b border-gray-700 pb-2">
                        <span class="text-gray-400 text-xs font-bold">PHP Example</span>
                    </div>
                    <pre class="text-xs font-mono text-green-300 overflow-x-auto">
$postData = [
    'user_token' => 'Your API KEY',
    'amount' => '100',
    'order_id' => 'ORD_' . time(),
    'redirect_url' => 'https://yoursite.com/callback',
    'customer_name' => 'John Doe'
];

$ch = curl_init('<?= $baseUrl ?>/api/create_order.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
</pre>
                </div>

                <!-- Response Blocks -->
                <div class="col-span-1 lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                    <div>
                        <h5 class="text-xs font-bold text-green-600 uppercase mb-2">Success Response</h5>
                        <pre class="bg-green-50 border border-green-100 p-4 rounded-lg text-xs font-mono text-gray-700">
{
    "status": true,
    "message": "Order Created Successfully",
    "result": {
        "orderId": "ORD_123456",
        "payment_url": "<?= $baseUrl ?>/pay.php?order_id=ORD_123456"
    }
}</pre>
                    </div>
                    <div>
                        <h5 class="text-xs font-bold text-red-600 uppercase mb-2">Error Response</h5>
                        <pre class="bg-red-50 border border-red-100 p-4 rounded-lg text-xs font-mono text-gray-700">
{
    "status": false,
    "message": "Invalid API Token"
}</pre>
                    </div>
                </div>

            </div>
        </div>


        <!-- 2. Check Status API -->
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
            <div class="px-8 py-6 border-b border-gray-50 flex items-center gap-3 bg-gray-50/50">
                <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded font-bold text-xs">POST</span>
                <h3 class="font-bold text-gray-800 text-lg">Check PayIN Status</h3>
                <span class="text-gray-400 text-sm font-mono ml-auto">/api/check_order_status.php</span>
            </div>

            <div class="p-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Left: Params -->
                <div class="space-y-6">
                    <p class="text-gray-600 text-sm">Verify the final status of a transaction server-to-server.</p>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                                <tr>
                                    <th class="p-3">Field</th>
                                    <th class="p-3">Type</th>
                                    <th class="p-3">Required</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr>
                                    <td class="p-3 font-mono text-blue-600">user_token</td>
                                    <td class="p-3">String</td>
                                    <td class="p-3 text-red-500 font-bold">Yes</td>
                                </tr>
                                <tr>
                                    <td class="p-3 font-mono text-blue-600">order_id</td>
                                    <td class="p-3">String</td>
                                    <td class="p-3 text-red-500 font-bold">Yes</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Right: Code Example -->
                <div class="bg-gray-900 rounded-xl p-6 overflow-hidden relative group">
                    <div class="flex justify-between items-center mb-4 border-b border-gray-700 pb-2">
                        <span class="text-gray-400 text-xs font-bold">PHP Example</span>
                    </div>
                    <pre class="text-xs font-mono text-green-300 overflow-x-auto">
$postData = [
    'user_token' => 'Your API KEY',
    'order_id' => 'ORD_123456'
];
// ... curl init to /api/check_order_status.php
</pre>
                </div>

                <!-- Response -->
                <div class="col-span-1 lg:col-span-2">
                    <h5 class="text-xs font-bold text-green-600 uppercase mb-2">Success Response</h5>
                    <pre class="bg-green-50 border border-green-100 p-4 rounded-lg text-xs font-mono text-gray-700">
{
    "status": true,
    "message": "Transaction Successfully",
    "result": {
        "txnStatus": "SUCCESS",
        "orderId": "ORD_123456",
        "amount": "100.00",
        "date": "2026-01-17 21:00:00",
        "utr": "123456789012"
    }
}</pre>
                </div>

            </div>
        </div>


    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-12 py-8 text-center text-sm text-gray-500">
        <p>&copy;
            <?= date('Y') ?> Payment Gateway Docs. All rights reserved.
        </p>
    </footer>

</body>

</html>