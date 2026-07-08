<?php
// api/create_order.php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../includes/db.php';

$pdo = getDBConnection();

// Allow POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// Get POST Data
$token = $_POST['user_token'] ?? '';
$amount = $_POST['amount'] ?? 0;
$orderId = $_POST['order_id'] ?? '';
$redirectUrl = $_POST['redirect_url'] ?? '';
$custName = $_POST['customer_name'] ?? '';
$custMobile = $_POST['customer_mobile'] ?? '';
$remark1 = $_POST['remark1'] ?? '';
$remark2 = $_POST['remark2'] ?? '';

// Validate
if (empty($token) || empty($amount) || empty($orderId)) {
    echo json_encode(['status' => false, 'message' => 'Missing Required Fields (user_token, amount, order_id)']);
    exit;
}

// Verify Token
$stmt = $pdo->prepare("SELECT id FROM projects WHERE api_key = ?");
$stmt->execute([$token]);
$project = $stmt->fetch();

if (!$project) {
    echo json_encode(['status' => false, 'message' => 'Invalid API Token']);
    exit;
}

// Check if Order Exists
$check = $pdo->prepare("SELECT id FROM transactions WHERE order_id = ?");
$check->execute([$orderId]);
if ($check->rowCount() > 0) {
    echo json_encode(['status' => false, 'message' => 'Order ID already exists']);
    exit;
}

// Create Transaction
try {
    $sql = "INSERT INTO transactions (project_id, order_id, customer_id, amount, status, client_callback_url, created_at) VALUES (?, ?, ?, ?, 'PENDING', ?, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$project['id'], $orderId, $custMobile, $amount, $redirectUrl]);

    // Generate Payment URL
    // We point to pay.php with the order_id (we need to modify pay.php to handle this)
    // Or we stick to slug/link logic? 
    // The reference says "payment_url".
    // I will use pay.php?order_id=XYZ. I need to update pay.php to load this txn.

    $paymentUrl = BASE_URL . "/pay.php?order_id=" . urlencode($orderId);

    echo json_encode([
        'status' => true,
        'message' => 'Order Created Successfully',
        'result' => [
            'orderId' => $orderId,
            'payment_url' => $paymentUrl
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>