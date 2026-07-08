<?php
// api/check_status_public.php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');

$orderId = isset($_GET['order_id']) ? $_GET['order_id'] : '';
if (!$orderId) {
    echo json_encode(['status' => 'ERROR']);
    exit;
}

try {
    $pdo = getDBConnection();

    // 1. Check Local DB
    $stmt = $pdo->prepare("SELECT status, paytm_txn_id, project_id, link_id, amount, created_at FROM transactions WHERE order_id = ?");
    $stmt->execute([$orderId]);
    $txn = $stmt->fetch();

    if ($txn && $txn['status'] == 'SUCCESS') {
        echo json_encode(['status' => 'SUCCESS', 'paytm_txn_id' => $txn['paytm_txn_id']]);
        exit;
    }

    // 2. Poll Gateway
    // Get Active Gateway
    $activeGatewayStmt = $pdo->query("SELECT setting_value FROM config_settings WHERE setting_key = 'active_gateway'");
    $activeGateway = $activeGatewayStmt->fetchColumn() ?: 'PAYTM';

    if ($activeGateway === 'BHARATPE') {
        // --- BHARATPE VERIFICATION ---
        require_once __DIR__ . '/../includes/BharatPeHelper.php';

        $bpStmt = $pdo->query("SELECT * FROM bharatpe_tokens WHERE is_active = 1 LIMIT 1");
        $bp = $bpStmt->fetch();

        if ($bp) {
            // Fetch recent transactions from BharatPe
            $txns = BharatPeHelper::getTransactions($bp['merchantId'], $bp['token'], $bp['cookie']);

            // LOGGING FOR DEBUG
            $logMsg = date('Y-m-d H:i:s') . " - Order: $orderId - Amount: " . $txn['amount'] . "\nResponse: " . print_r($txns, true) . "\n\n";
            file_put_contents(__DIR__ . '/../bp_debug.log', $logMsg, FILE_APPEND);

            if ($txns && is_array($txns)) {

                // Get order creation timestamp
                $orderCreatedAt = strtotime($txn['created_at']);

                foreach ($txns as $t) {
                    // 1. Check Amount Matches (approx)
                    $amtMatch = (abs((float) $t['amount'] - (float) $txn['amount']) < 1.0);

                    // 2. Get Transaction Timestamp (BharatPe uses MILLISECONDS)
                    $txnTime = isset($t['paymentTimestamp']) ? ($t['paymentTimestamp'] / 1000) : 0;

                    // 3. CRITICAL: Transaction must be AFTER order creation
                    $isAfterOrder = ($txnTime > $orderCreatedAt);

                    // 4. Check if transaction is recent (within 30 mins of NOW for safety)
                    $isRecent = (time() - $txnTime) < 1800;

                    // 5. Check Order ID in Remarks/Description if available
                    // BharatPe fields: 'remarks', 'description', 'payerNote', 'bankReferenceNo'
                    $jsonTxn = json_encode($t);
                    $idMatch = (strpos($jsonTxn, (string) $orderId) !== false);

                    // Match only if:
                    // - Amount matches AND transaction is after order creation AND recent
                    // - OR Order ID is found in transaction data
                    if (($amtMatch && $isAfterOrder && $isRecent) || $idMatch) {

                        // Preventing duplicate usage
                        $existing = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE paytm_txn_id = ?");
                        $existing->execute([$t['bankReferenceNo']]);

                        if ($existing->fetchColumn() == 0) {
                            // SUCCESS
                            $txnId = $t['bankReferenceNo']; // UTR

                            $upd = $pdo->prepare("UPDATE transactions SET status = 'SUCCESS', paytm_txn_id = ? WHERE order_id = ?");
                            $upd->execute([$txnId, $orderId]);

                            // Expire Link if needed
                            if (!empty($txn['link_id'])) {
                                $pdo->prepare("UPDATE payment_links SET usage_limit = usage_limit - 1 WHERE id = ? AND usage_limit IS NOT NULL")->execute([$txn['link_id']]);
                                $pdo->prepare("UPDATE payment_links SET is_active = 0 WHERE id = ? AND usage_limit = 1")->execute([$txn['link_id']]);
                            }

                            file_put_contents(__DIR__ . '/../bp_debug.log', "MATCH FOUND: $txnId (Order: $orderId, TxnTime: $txnTime, OrderTime: $orderCreatedAt)\n", FILE_APPEND);

                            echo json_encode(['status' => 'SUCCESS', 'paytm_txn_id' => $txnId]);
                            exit;
                        }
                    }
                }
            }
        }
    } else {
        // --- PAYTM VERIFICATION (Legacy) ---
        // Fetch Default Merchant MID
        $mStmt = $pdo->query("SELECT mid FROM merchants WHERE is_default = 1 LIMIT 1");
        $merchant = $mStmt->fetch();
        $mid = $merchant ? $merchant['mid'] : '';

        // If no merchant found, fall back to settings
        if (!$mid) {
            $s = $pdo->query("SELECT setting_key, setting_value FROM config_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
            $mid = isset($s['paytm_mid']) ? $s['paytm_mid'] : '';
        }

        if ($mid) {
            $jsonData = json_encode(["MID" => $mid, "ORDERID" => $orderId]);
            $url = "https://securegw.paytm.in/order/status?JsonData=" . urlencode($jsonData);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            // ... (keep existing curl options) ... 
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
            $res = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($res, true);

            if (isset($data['STATUS']) && $data['STATUS'] == 'TXN_SUCCESS') {
                $txnId = isset($data['TXNID']) ? $data['TXNID'] : 'PAYTM_' . time();

                // Update DB
                $upd = $pdo->prepare("UPDATE transactions SET status = 'SUCCESS', paytm_txn_id = ? WHERE order_id = ?");
                $upd->execute([$txnId, $orderId]);

                // EXPIRE LINK logic
                if (!empty($txn['link_id'])) {
                    $pdo->prepare("UPDATE payment_links SET usage_limit = usage_limit - 1 WHERE id = ? AND usage_limit IS NOT NULL")->execute([$txn['link_id']]);
                    $pdo->prepare("UPDATE payment_links SET is_active = 0 WHERE id = ? AND usage_limit = 1")->execute([$txn['link_id']]);
                }

                echo json_encode(['status' => 'SUCCESS', 'paytm_txn_id' => $txnId]);
                exit;
            }
        }
    }

    echo json_encode(['status' => 'PENDING']);

} catch (Exception $e) {
    echo json_encode(['status' => 'ERROR']);
}
?>