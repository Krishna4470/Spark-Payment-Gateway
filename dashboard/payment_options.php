<?php
// dashboard/payment_options.php
require_once '../includes/dashboard_utils.php';
checkAuth();

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    updateSetting('paytm_env', $_POST['env']);
    $message = "Environment Updated!";
}

$env = getSetting('paytm_env');

require_once 'layout.php';
?>

<div class="max-w-4xl mx-auto">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">Payment Options</h2>

    <?php if ($message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="bg-white p-8 rounded shadow-md">
        <h3 class="text-lg font-semibold mb-4">Environment Settings</h3>
        <p class="text-gray-600 mb-6">Switch between Test (Staging) and Production (Live) modes.</p>

        <form method="post">
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">Active Environment</label>
                <select name="env" class="w-full border p-3 rounded bg-gray-50">
                    <option value="TEST" <?= $env == 'TEST' ? 'selected' : '' ?>>TEST (Sandbox)</option>
                    <option value="PROD" <?= $env == 'PROD' ? 'selected' : '' ?>>PRODUCTION (Live)</option>
                </select>
            </div>

            <button type="submit" class="bg-gray-800 text-white px-6 py-3 rounded hover:bg-black shadow-lg font-bold">
                Update Environment
            </button>
        </form>
    </div>
</div>

<?php require_once 'layout_footer.php'; ?>