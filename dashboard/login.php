<?php
// dashboard/login.php
require_once '../includes/dashboard_utils.php';

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    header("Location: index.php");
    exit;
}

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $realPass = getSetting('admin_password');

    if ($password === $realPass) {
        $_SESSION['logged_in'] = true;
        header("Location: index.php");
        exit;
        $error = "Incorrect Password";
    }
}
$siteName = getSetting('site_name') ?: 'Paytm Gateway';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Login - <?= htmlspecialchars($siteName) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gray-900 h-screen flex justify-center items-center p-4">
    <div class="bg-gray-800 p-8 rounded-2xl shadow-2xl w-full max-w-sm text-white border border-gray-700">

        <div class="text-center mb-8">
            <div
                class="w-16 h-16 bg-blue-600 rounded-xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-blue-500/50">
                <i class="fas fa-bolt text-3xl"></i>
            </div>
            <h2 class="text-2xl font-bold tracking-tight"><?= htmlspecialchars($siteName) ?></h2>
            <p class="text-gray-400 text-sm mt-1">Admin Access</p>
        </div>

        <?php if ($error): ?>
            <div
                class="bg-red-500/10 border border-red-500/50 text-red-200 px-4 py-3 rounded-lg text-sm mb-6 text-center animate-pulse">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-4">
            <div>
                <input type="password" name="password" placeholder="Enter Password" required
                    class="w-full bg-gray-700 border border-gray-600 p-3 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition">
            </div>

            <button type="submit"
                class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white p-3 rounded-xl font-bold shadow-lg shadow-blue-500/30 transition transform hover:-translate-y-0.5">
                Login
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="forgot_password.php" class="text-gray-500 text-sm hover:text-gray-300 transition">Forgot
                Password?</a>
        </div>
    </div>
</body>

</html>