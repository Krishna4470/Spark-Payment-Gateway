<?php
// api/check_order_status.php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../includes/db.php';

$pdo = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$token = $_POST['user_token'] ?? '';
$orderId = $_POST['order_id'] ?? '';

if (empty($token) || empty($orderId)) {
    echo json_encode(['status' => false, 'message' => 'Missing Fields']);
    exit;
}

// Verify Token
$stmt = $pdo->prepare("SELECT id FROM projects WHERE api_key = ?");
$stmt->execute([$token]);
if (!$stmt->fetch()) {
    echo json_encode(['status' => false, 'message' => 'Invalid API Token']);
    exit;
}

// Fetch Transaction
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE order_id = ?");
$stmt->execute([$orderId]);
$txn = $stmt->fetch();

if (!$txn) {
    echo json_encode(['status' => false, 'message' => 'Order Not Found']);
    exit;
}

echo json_encode([
    'status' => true,
    'message' => 'Transaction Successfully',
    'result' => [
        'txnStatus' => $txn['status'],
        'orderId' => $txn['order_id'],
        'amount' => $txn['amount'],
        'date' => $txn['created_at'],
        'utr' => $txn['paytm_txn_id'] // Assuming we store UTR here
    ]
]);
?>