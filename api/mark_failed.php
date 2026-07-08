<?php
// api/mark_failed.php - Mark transaction as failed on timeout
require_once '../includes/db.php';
header('Content-Type: application/json');

$orderId = isset($_POST['order_id']) ? $_POST['order_id'] : '';

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Order ID required']);
    exit;
}

try {
    $pdo = getDBConnection();

    // Check if transaction exists and is still pending
    $stmt = $pdo->prepare("SELECT status FROM transactions WHERE order_id = ?");
    $stmt->execute([$orderId]);
    $txn = $stmt->fetch();

    if (!$txn) {
        echo json_encode(['success' => false, 'message' => 'Transaction not found']);
        exit;
    }

    if ($txn['status'] == 'SUCCESS') {
        echo json_encode(['success' => false, 'message' => 'Transaction already completed']);
        exit;
    }

    // Mark as FAILED
    $stmt = $pdo->prepare("UPDATE transactions SET status = 'FAILED' WHERE order_id = ?");
    $stmt->execute([$orderId]);

    echo json_encode(['success' => true, 'message' => 'Transaction marked as failed']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>