<?php
// dashboard/forgot_password.php
require_once '../includes/dashboard_utils.php';
require_once '../includes/db.php';

$message = "";
$msgType = "error";

// Fetch Security Settings
$question = getSetting('security_question');
$realAnswer = getSetting('security_answer');

// Default fallback if not set
if (!$question)
    $question = "What is your pet name?"; // Fallback
// If answer is not set, they can't reset. Ideally this shouldn't happen if setup flow is correct.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answer = trim($_POST['answer']);
    $newPass = $_POST['new_pass'];

    if (empty($realAnswer)) {
        $message = "Security answer not set. Please contact support or reset via database.";
    } elseif (strtolower($answer) !== strtolower($realAnswer)) {
        $message = "Incorrect Answer.";
    } elseif (strlen($newPass) < 4) {
        $message = "Password too short.";
    } else {
        updateSetting('admin_password', $newPass);
        $message = "Password Reset Successful! Redirecting...";
        $msgType = "success";
        echo "<script>setTimeout(() => window.location.href='login.php', 2000);</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Reset Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gray-900 h-screen flex justify-center items-center p-4">
    <div
        class="bg-gray-800 p-8 rounded-2xl shadow-2xl w-full max-w-sm text-white border border-gray-700 relative overflow-hidden">

        <!-- Back Button -->
        <a href="login.php" class="absolute top-4 left-4 text-gray-500 hover:text-white transition">
            <i class="fas fa-arrow-left"></i>
        </a>

        <div class="text-center mb-8">
            <h2 class="text-xl font-bold tracking-tight text-gray-200">Security Check</h2>
            <p class="text-gray-500 text-xs mt-1">Answer your security question to reset password.</p>
        </div>

        <?php if ($message): ?>
            <div
                class="<?= $msgType == 'success' ? 'bg-green-500/10 border-green-500/50 text-green-200' : 'bg-red-500/10 border-red-500/50 text-red-200' ?> border px-4 py-3 rounded-lg text-sm mb-6 text-center">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-4">

            <!-- Question Display -->
            <div class="bg-gray-700/50 p-3 rounded-lg border border-gray-600 text-center">
                <p class="text-xs text-blue-400 font-bold uppercase mb-1">Security Question</p>
                <p class="text-sm font-medium">
                    <?= htmlspecialchars($question) ?>
                </p>
            </div>

            <div>
                <input type="text" name="answer" placeholder="Your Answer" required
                    class="w-full bg-gray-700 border border-gray-600 p-3 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:border-blue-500 transition">
            </div>

            <div>
                <input type="password" name="new_pass" placeholder="New Password" required
                    class="w-full bg-gray-700 border border-gray-600 p-3 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:border-blue-500 transition">
                <p class="text-[10px] text-gray-500 mt-1 ml-1">Must be at least 4 characters</p>
            </div>

            <button type="submit"
                class="w-full bg-gray-600 hover:bg-gray-500 text-white p-3 rounded-xl font-bold shadow-lg transition mt-4">
                Reset Password
            </button>
        </form>

    </div>
</body>

</html>