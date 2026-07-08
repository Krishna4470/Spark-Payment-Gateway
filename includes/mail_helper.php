<?php
// includes/mail_helper.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/dashboard_utils.php'; // Access getSetting

function sendOrderEmail($toEmail, $productName, $accessCode, $orderId)
{
    $mail = new PHPMailer(true);

    try {
        // Settings
        $host = getSetting('smtp_host');
        $user = getSetting('smtp_user');
        $pass = getSetting('smtp_pass');
        $port = getSetting('smtp_port'); // Default 587

        if (!$host || !$user || !$pass) {
            return false; // SMTP not configured
        }

        // Server settings
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = true;
        $mail->Username = $user;
        $mail->Password = $pass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $port ?: 587;

        // Recipients
        $mail->setFrom($user, 'Digital Downloads');
        $mail->addAddress($toEmail);

        // Content
        $redeemLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . dirname(dirname($_SERVER['PHP_SELF'])) . '/redeem.php';

        $mail->isHTML(true);
        $mail->Subject = "Your Download: $productName";
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-w-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
                <h2 style='color: #2563eb;'>Thank you for your purchase!</h2>
                <p>You have successfully purchased <strong>$productName</strong>.</p>
                
                <div style='background: #f9fafb; padding: 15px; border-radius: 5px; border: 1px solid #e5e7eb; margin: 20px 0;'>
                    <p style='margin: 0; font-size: 14px; color: #6b7280;'>Your Access Code:</p>
                    <h3 style='margin: 5px 0 0 0; font-size: 24px; color: #111827; letter-spacing: 2px;'>$accessCode</h3>
                    <p style='margin: 10px 0 0 0; font-size: 12px; color: #ef4444;'>Valid for one-time use only</p>
                </div>

                <p>To download your file, click the button below and enter your access code:</p>
                <a href='$redeemLink' style='display: inline-block; background: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Redeem Download</a>
                
                <p style='margin-top: 20px; font-size: 12px; color: #9ca3af;'>Order ID: #$orderId</p>
            </div>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>