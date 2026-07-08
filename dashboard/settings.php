<?php
// dashboard/settings.php
require_once '../includes/dashboard_utils.php';
require_once '../includes/db.php';
checkAuth();

$pdo = getDBConnection();
$message = "";
$msgType = "success";

// --- Handle Unified Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_all'])) {

    $updated = false;

    // 1. Profile Settings
    if (isset($_POST['admin_name'])) {
        updateSetting('admin_name', trim($_POST['admin_name']));
        updateSetting('security_question', trim($_POST['security_question']));
        updateSetting('security_answer', trim($_POST['security_answer']));
        $updated = true;
    }

    // 2. Website Settings
    if (isset($_POST['site_name'])) {
        updateSetting('site_name', trim($_POST['site_name']));

        // Favicon
        if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] == 0) {
            $targetDir = "../images/";
            if (!is_dir($targetDir))
                mkdir($targetDir, 0755, true);
            $targetFile = $targetDir . "favicon.ico";
            $check = getimagesize($_FILES["favicon"]["tmp_name"]);
            if ($check !== false) {
                if (move_uploaded_file($_FILES["favicon"]["tmp_name"], $targetFile)) {
                    updateSetting('favicon_path', 'images/favicon.ico');
                }
            } else {
                $message = "Favicon must be an image.";
                $msgType = "error";
            }
        }
        $updated = true;
    }

    // 3. Password (Only if filled)
    if (!empty($_POST['new_pass'])) {
        $current = $_POST['current_pass'];
        $new = $_POST['new_pass'];
        $confirm = $_POST['confirm_pass'];
        $realPass = getSetting('admin_password');

        if ($current !== $realPass) {
            $message = "Incorrect Current Password. Other settings saved.";
            $msgType = "error";
        } elseif ($new !== $confirm) {
            $message = "New passwords do not match. Other settings saved.";
            $msgType = "error";
        } elseif (strlen($new) < 4) {
            $message = "Password too short. Other settings saved.";
            $msgType = "error";
        } else {
            updateSetting('admin_password', $new);
            $message = "Settings & Password Updated Successfully!";
        }
    } else {
        if ($updated && $msgType == 'success') {
            $message = "Settings Updated Successfully!";
        }
    }
}

// --- Fetch Current Data ---
$adminName = getSetting('admin_name') ?: 'Administrator';
$secQuestion = getSetting('security_question') ?: 'What is your pet name?';
$secAnswer = getSetting('security_answer') ?: '';
$siteName = getSetting('site_name') ?: 'Paytm Gateway';
$favicon = getSetting('favicon_path') ?: '';

require_once 'layout.php';
?>

<div class="max-w-6xl mx-auto space-y-8">

    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h2 class="text-3xl font-bold text-gray-800 tracking-tight">Settings</h2>
            <p class="text-gray-500 text-sm mt-1">Manage your profile, security, and site configuration.</p>
        </div>
        <?php if ($message): ?>
            <div
                class="<?= $msgType == 'success' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200' ?> border px-4 py-2 rounded-lg shadow-sm text-sm font-semibold animate-fade-in flex items-center">
                <i class="fas <?= $msgType == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> mr-2"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Unified Form -->
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="save_all" value="1">

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

            <!-- 1. Profile Settings -->
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-50 bg-gray-50/50 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center"><i
                            class="fas fa-user-edit"></i></div>
                    <h3 class="font-bold text-gray-800">Admin Profile</h3>
                </div>
                <div class="p-6 space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Display
                            Name</label>
                        <input type="text" name="admin_name" value="<?= htmlspecialchars($adminName) ?>"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Security
                                Question</label>
                            <select name="security_question"
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition cursor-pointer">
                                <option <?= $secQuestion == 'What is your pet name?' ? 'selected' : '' ?>>What is your pet
                                    name?</option>
                                <option <?= $secQuestion == 'What is your favorite color?' ? 'selected' : '' ?>>What is
                                    your favorite color?</option>
                                <option <?= $secQuestion == 'What city were you born in?' ? 'selected' : '' ?>>What city
                                    were you born in?</option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Answer</label>
                            <input type="text" name="security_answer" value="<?= htmlspecialchars($secAnswer) ?>"
                                placeholder="Your Answer"
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Website Settings -->
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-50 bg-gray-50/50 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center"><i
                            class="fas fa-globe"></i></div>
                    <h3 class="font-bold text-gray-800">Website Configuration</h3>
                </div>
                <div class="p-6 space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Website
                            Name</label>
                        <input type="text" name="site_name" value="<?= htmlspecialchars($siteName) ?>"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Favicon
                            Upload</label>
                        <div class="flex items-center gap-4">
                            <?php if ($favicon): ?>
                                <img src="../<?= $favicon ?>?v=<?= time() ?>"
                                    class="w-10 h-10 rounded border border-gray-200 p-1">
                            <?php endif; ?>
                            <input type="file" name="favicon" accept="image/*"
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Security (Password - Optional) -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-50 bg-gray-50/50 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-red-100 text-red-600 flex items-center justify-center"><i
                            class="fas fa-lock"></i></div>
                    <h3 class="font-bold text-gray-800">Change Password <span
                            class="text-xs font-normal text-gray-500 ml-2">(Leave empty to keep current)</span></h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Current
                            Password</label>
                        <input type="password" name="current_pass" placeholder="••••••"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">New
                            Password</label>
                        <input type="password" name="new_pass" placeholder="New Password"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Confirm
                            New</label>
                        <input type="password" name="confirm_pass" placeholder="Confirm Password"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    </div>
                </div>
            </div>

            <!-- 4. Cron Job Automation -->
            <div
                class="lg:col-span-2 bg-gradient-to-r from-gray-900 to-gray-800 rounded-2xl shadow-xl overflow-hidden text-white">
                <div class="px-6 py-4 border-b border-gray-700 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-green-500/20 text-green-400 flex items-center justify-center">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-white">Cron Job Setup</h3>
                        <p class="text-xs text-gray-400">Automate order status updates</p>
                    </div>
                </div>
                <div class="p-6 space-y-4">
                    <div
                        class="bg-black/30 rounded-xl p-4 border border-gray-700 font-mono text-sm break-all text-green-300 relative group">
                        wget -q -O - <?= BASE_URL ?>crons/expire_orders.php >/dev/null 2>&1
                        <button type="button"
                            onclick="navigator.clipboard.writeText('wget -q -O - <?= BASE_URL ?>crons/expire_orders.php >/dev/null 2>&1'); alert('Command Copied!');"
                            class="absolute top-2 right-2 text-gray-500 hover:text-white bg-gray-800 p-2 rounded transition opacity-0 group-hover:opacity-100">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-300">
                        <i class="fas fa-clock text-yellow-500"></i>
                        <span>Recommended Schedule: <strong class="text-white">Every 5 Minutes</strong> (*/5 * * *
                            *)</span>
                    </div>
                </div>
            </div>

        </div>

        <!-- Sticky Save Button -->
        <div class="sticky bottom-4 z-40 mt-8">
            <button type="submit"
                class="w-full max-w-md mx-auto block bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-4 rounded-full shadow-2xl transform hover:-translate-y-1 transition duration-200 border-4 border-white">
                Save All Changes
            </button>
        </div>

    </form>


    <!-- Footer Credits -->
    <div class="text-center py-8">
        <p class="text-gray-400 text-sm font-medium">
            Created With Love ❤️ By <a href="https://digispark-krishna.blogspot.com/" target="_blank"
                class="text-blue-500 hover:text-blue-600 hover:underline transition">Krishna</a>
        </p>
    </div>

</div>

<?php require_once 'layout_footer.php'; ?>