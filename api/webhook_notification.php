<?php
// api/webhook_notification.php
// URL to Configure in Android SMS Reader App: https://yoursite.com/api/webhook_notification.php

require_once '../includes/db.php';

// Accept JSON or POST
$input = json_decode(file_get_contents('php://input'), true);
if (!$input)
    $input = $_POST;

try {
    $pdo = getDBConnection();

    // Log it
    $stmt = $pdo->prepare("INSERT INTO webhook_logs (payload) VALUES (?)");
    $stmt->execute([json_encode($input)]);

    // Parse Data
    // Different apps send different formats. We look for 'amount' and 'utr'/'ref' matches.
    // Ideally user app sends: { "body": "Rs 100 received from ... Ref 123456" } OR { "amount": 100, "utr": "123456" }

    // LOGIC: Since we don't have the exact UTR in our DB (UPI generates it after), 
    // we match by AMOUNT + RECENT PENDING TIME. This is "Soft Matching".
    // Better: If 'description' contains our ORDER ID? (Unlikely UPI allows passing notes in all apps).

    // We will assume the Android App sends "amount" and "utr".
    $amount = isset($input['amount']) ? $input['amount'] : 0;
    $utr = isset($input['utr']) ? $input['utr'] : (isset($input['ref']) ? $input['ref'] : '');

    if ($amount > 0) {
        // Find a recent PENDING transaction with this amount
        // Look back 15 minutes
        $stmt = $pdo->prepare("SELECT id FROM transactions WHERE amount = ? AND status = 'PENDING' AND created_at >= (NOW() - INTERVAL 15 MINUTE) ORDER BY id DESC LIMIT 1");
        $stmt->execute([$amount]);
        $txn = $stmt->fetch();

        if ($txn) {
            $upd = $pdo->prepare("UPDATE transactions SET status = 'SUCCESS', paytm_txn_id = ? WHERE id = ?");
            $upd->execute([$utr, $txn['id']]);
            echo "Matched Txn ID: " . $txn['id'];
        } else {
            echo "No matching pending txn found for amount $amount";
        }
    } else {
        echo "Invalid Data";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>