<?php
// api/verify_order.php

header('Content-Type: application/json');
require_once '../includes/db.php';

$headers = getallheaders();
$apiKey = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;

if (!$apiKey) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Missing API Key']);
    exit;
}

try {
    $pdo = getDBConnection();

    // Check Project
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE api_key = ?");
    $stmt->execute([$apiKey]);
    $project = $stmt->fetch();

    if (!$project) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Invalid API Key']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['order_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Missing order_id']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE order_id = ? AND project_id = ?");
    $stmt->execute([$input['order_id'], $project['id']]);
    $txn = $stmt->fetch();

    if (!$txn) {
        echo json_encode(['status' => 'error', 'message' => 'Order not found']);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'order_id' => $txn['order_id'],
            'amount' => $txn['amount'],
            'status' => $txn['status'],
            'paytm_txn_id' => $txn['paytm_txn_id'],
            'date' => $txn['created_at']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>