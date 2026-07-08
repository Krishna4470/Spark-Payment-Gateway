<?php
require_once 'config.php';
require_once 'includes/db.php';

$token = $_GET['token'] ?? '';
if (!$token) {
    die("Invalid Link");
}

$pdo = getDBConnection();

// 1. Verify Token
$stmt = $pdo->prepare("SELECT o.*, p.file_path, p.name as filename FROM product_orders o JOIN digital_products p ON o.product_id = p.id WHERE o.download_token = ?");
$stmt->execute([$token]);
$order = $stmt->fetch();

if (!$order) {
    die("Invalid Token");
}

if ($order['status'] !== 'completed') {
    die("Payment not verified");
}

// 2. Check Limits (Strict: Max 2 downloads - 1 immediate, 1 via email)
$maxDownloads = 2;
$currentCount = (int) $order['download_count'];
$source = $_GET['source'] ?? '';

if ($currentCount >= $maxDownloads) {
    die("Download limit exceeded. You have already downloaded this file $maxDownloads times.");
}

// Logic: First download is free (immediate). Second download requires Email Verification.
if ($currentCount >= 1 && $source !== 'email_code') {
    // If trying to access direct link again without code
    // Check if we can redirect to redeem page?
    // We don't have the code here to autofill.
    die("This download link was for one-time use. To download again, please use the Access Code sent to your email at <a href='redeem.php'>Redeem Page</a>.");
}


// 3. Serve File
$filePath = $order['file_path']; // This should be relative or absolute. In admin we stored '../uploads/...'
// Adjust path if needed. Admin script saved it as '../uploads/products/...' relative to dashboard/
// But duplicate logic: Admin: '../uploads/...'. Front: 'uploads/...'?
// Let's resolve the path relative to THIS file (root).
// Standardizing: Admin saved relative to dashboard (../uploads). That means 'uploads' is in Root.
// So $filePath is '../uploads/...'. We need 'uploads/...' relative to Root.
// Let's fix the stored path in Admin script? 
// Admin: `../uploads/products/`. If dashboard is in `/dashboard`, then `../` is root. So `root/uploads/products`.
// We are in `root/download.php`. So path should be `uploads/products`.
// The stored path in DB is `../uploads/products/filename`.
// Validation: realpath(__DIR__ . '/dashboard/' . $filePath) should work?
// Or better: strip `../` prefix.
$realPath = __DIR__ . '/' . str_replace('../', '', $filePath);

if (!file_exists($realPath)) {
    // Try original path just in case
    $realPath = $filePath;
    if (!file_exists($realPath)) {
        die("File not found on server. Please contact support.");
    }
}

// Update Count
$pdo->prepare("UPDATE product_orders SET download_count = download_count + 1 WHERE id = ?")->execute([$order['id']]);

// Force Download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($order['filename']) . '_' . basename($realPath) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($realPath));
readfile($realPath);
exit;
?>