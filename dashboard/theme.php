<?php
// dashboard/theme.php
require_once '../includes/dashboard_utils.php';
checkAuth();

$message = "";
$msgType = "success";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_theme'])) {
        $title = trim($_POST['theme_title']);
        $color = trim($_POST['theme_color']);
        $support = trim($_POST['support_url']);

        updateSetting('theme_title', $title);
        updateSetting('theme_color', $color);
        updateSetting('support_url', $support);

        $message = "Theme settings updated successfully!";
    }
}

// Fetch Current Settings
$title = getSetting('theme_title') ?: 'Pay Zero';
$color = getSetting('theme_color') ?: '#3b82f6';
$support = getSetting('support_url') ?: '';

require_once 'layout.php';
?>

<div class="max-w-5xl mx-auto space-y-8">

    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="text-3xl font-bold text-gray-800 tracking-tight">Payment Theme</h2>
            <p class="text-gray-500 text-sm mt-1">Customize the look and feel of your payment page.</p>
        </div>
        <?php if ($message): ?>
            <div
                class="bg-green-50 text-green-700 border border-green-200 px-4 py-2 rounded-lg shadow-sm text-sm font-semibold animate-fade-in flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Left: Settings Form -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-50 bg-gray-50/50 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-blue-100/50 text-blue-600 flex items-center justify-center">
                        <i class="fas fa-paint-brush"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg">Visual Settings</h3>
                </div>

                <form method="post" class="p-8 space-y-6">
                    <input type="hidden" name="save_theme" value="1">

                    <!-- Brand Name -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Page Title
                            (Brand Name)</label>
                        <div class="relative group">
                            <div
                                class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-500 transition">
                                <i class="fas fa-tag"></i>
                            </div>
                            <input type="text" name="theme_title" value="<?= htmlspecialchars($title) ?>" required
                                class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-700 font-medium focus:outline-none focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-50 transition shadow-inner">
                        </div>
                        <p class="text-xs text-gray-400 mt-2">Displayed on the payment page header.</p>
                    </div>

                    <!-- Header Color -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Header
                            Background Color</label>
                        <div class="flex items-center gap-4">
                            <div class="relative flex-1 group">
                                <div
                                    class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-500 transition">
                                    <i class="fas fa-palette"></i>
                                </div>
                                <input type="text" id="colorText" value="<?= htmlspecialchars($color) ?>" readonly
                                    class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-700 font-mono font-medium focus:outline-none transition shadow-inner cursor-not-allowed">
                            </div>
                            <input type="color" name="theme_color" id="colorPicker"
                                value="<?= htmlspecialchars($color) ?>"
                                class="h-12 w-20 p-1 bg-white border border-gray-200 rounded-xl cursor-pointer shadow-sm hover:shadow-md transition"
                                onchange="updateColorText(this.value)">
                        </div>
                    </div>

                    <!-- Support Link -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Need Help?
                            Support Link</label>
                        <div class="relative group">
                            <div
                                class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-500 transition">
                                <i class="fas fa-link"></i>
                            </div>
                            <input type="url" name="support_url" value="<?= htmlspecialchars($support) ?>"
                                placeholder="https://t.me/yourusername"
                                class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-700 font-medium focus:outline-none focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-50 transition shadow-inner">
                        </div>
                        <p class="text-xs text-gray-400 mt-2">Enter Telegram link, WhatsApp link, or support page URL.
                        </p>
                    </div>

                    <!-- Save Button -->
                    <div class="pt-4">
                        <button type="submit"
                            class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-4 rounded-xl shadow-lg shadow-blue-200 transform hover:-translate-y-0.5 transition duration-200 flex items-center justify-center gap-2">
                            <span>Save Customization</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>

                </form>
            </div>
        </div>

        <!-- Right: Mobile Preview -->
        <div class="lg:col-span-1">
            <div class="sticky top-24">
                <div
                    class="bg-gray-900 rounded-[2.5rem] border-[8px] border-gray-800 shadow-2xl overflow-hidden relative max-w-[300px] mx-auto h-[600px] flex flex-col">
                    <!-- Notch -->
                    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-32 h-6 bg-gray-800 rounded-b-xl z-20"></div>

                    <!-- Preview Screen -->
                    <div class="flex-1 bg-gray-50 flex flex-col relative overflow-hidden">

                        <!-- Header Preview -->
                        <div id="previewHeader"
                            class="h-40 p-6 flex flex-col justify-end text-white relative transition-colors duration-300"
                            style="background-color: <?= htmlspecialchars($color) ?>;">
                            <div class="flex items-center gap-3 mb-2">
                                <div
                                    class="w-10 h-10 bg-white rounded-lg flex items-center justify-center text-blue-600 font-bold shadow-sm">
                                    <i class="fas fa-bolt"></i>
                                </div>
                                <div>
                                    <div class="text-xs opacity-80">Payment to</div>
                                    <div id="previewTitle" class="font-bold text-lg leading-tight">
                                        <?= htmlspecialchars($title) ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="flex-1 p-4 -mt-6">
                            <div
                                class="bg-white rounded-xl shadow-lg p-4 h-full flex flex-col items-center justify-center space-y-4">
                                <div class="w-32 h-32 bg-gray-100 rounded-lg animate-pulse"></div>
                                <div class="w-3/4 h-4 bg-gray-100 rounded animate-pulse"></div>
                                <div class="w-1/2 h-4 bg-gray-100 rounded animate-pulse"></div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="p-4 text-center text-[10px] text-gray-400 border-t border-gray-100">
                            Need help? <span class="text-green-600 font-bold">Support</span>
                        </div>

                    </div>
                </div>
                <p class="text-center text-gray-400 text-xs mt-4">Live Preview</p>
            </div>
        </div>

    </div>

</div>

<script>
    function updateColorText(val) {
        document.getElementById('colorText').value = val;
        document.getElementById('previewHeader').style.backgroundColor = val;
    }

    // Live Title Update
    document.querySelector('input[name="theme_title"]').addEventListener('input', function (e) {
        document.getElementById('previewTitle').innerText = e.target.value || 'Page Title';
    });
</script>

<?php require_once 'layout_footer.php'; ?>