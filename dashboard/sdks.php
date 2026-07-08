<?php
// dashboard/sdks.php
require_once '../includes/dashboard_utils.php';
require_once '../includes/db.php';
checkAuth();

$pdo = getDBConnection();

// Auto-generate zip if not exists (Optional fallback)
$zipPath = '../sdks/php-sdk.zip';

require_once 'layout.php';
?>

<div class="max-w-7xl mx-auto space-y-8">

    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-end gap-4">
        <div>
            <h2 class="text-3xl font-bold text-gray-800 tracking-tight">Developer SDKs</h2>
            <p class="text-gray-500 text-sm mt-1">Download official libraries to integrate payments easily.</p>
        </div>
    </div>

    <!-- SDK Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

        <!-- PHP SDK Card -->
        <div
            class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden group hover:shadow-2xl transition duration-300">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <div
                        class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-3xl shadow-sm">
                        <i class="fab fa-php"></i>
                    </div>
                    <?php if (file_exists($zipPath)): ?>
                        <span class="bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full font-bold">Available</span>
                    <?php else: ?>
                        <span class="bg-gray-100 text-gray-500 text-xs px-2 py-1 rounded-full font-bold">Coming Soon</span>
                    <?php endif; ?>
                </div>

                <h3 class="text-xl font-bold text-gray-800 mb-2">PHP Library</h3>
                <p class="text-gray-500 text-sm leading-relaxed mb-6">
                    Drop-in PHP class to handle Orders, Status Checks, and Redirects. Includes examples.
                </p>

                <div class="space-y-3">
                    <div class="flex items-center text-xs text-gray-500">
                        <i class="fas fa-check text-green-500 mr-2"></i> Easy Installation
                    </div>
                    <div class="flex items-center text-xs text-gray-500">
                        <i class="fas fa-check text-green-500 mr-2"></i> Secure Usage
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                <span class="text-xs font-bold text-gray-400">v1.0.0</span>
                <?php if (file_exists($zipPath)): ?>
                    <a href="<?= $zipPath ?>" download
                        class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-bold transition shadow-lg shadow-indigo-200">
                        <i class="fas fa-download"></i> Download
                    </a>
                <?php else: ?>
                    <button disabled
                        class="flex items-center gap-2 bg-gray-300 text-white px-4 py-2 rounded-lg text-sm font-bold cursor-not-allowed">
                        <i class="fas fa-lock"></i> Locked
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- WordPress Plugin Card -->
        <div
            class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden group hover:shadow-2xl transition duration-300">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <div
                        class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-3xl shadow-sm">
                        <i class="fab fa-wordpress"></i>
                    </div>
                    <span class="bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full font-bold">Available</span>
                </div>

                <h3 class="text-xl font-bold text-gray-800 mb-2">WordPress Plugin</h3>
                <p class="text-gray-500 text-sm leading-relaxed mb-6">
                    Official WordPress/WooCommerce plugin for seamless payment integration.
                </p>

                <div class="space-y-3">
                    <div class="flex items-center text-xs text-gray-500">
                        <i class="fas fa-check text-green-500 mr-2"></i> WooCommerce Ready
                    </div>
                    <div class="flex items-center text-xs text-gray-500">
                        <i class="fas fa-check text-green-500 mr-2"></i> Easy Setup
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                <span class="text-xs font-bold text-gray-400">v1.0.0</span>
                <a href="../sdks/sparkpay.php" download
                    class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-bold transition shadow-lg shadow-indigo-200">
                    <i class="fas fa-download"></i> Download
                </a>
            </div>
        </div>

        <!-- Node.js SDK (Coming Soon) -->
        <div
            class="bg-white rounded-2xl shadow hover:shadow-lg border border-gray-100 overflow-hidden opacity-60 grayscale hover:grayscale-0 transition duration-300">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <div
                        class="w-14 h-14 rounded-2xl bg-green-50 text-green-600 flex items-center justify-center text-3xl shadow-sm">
                        <i class="fab fa-node"></i>
                    </div>
                    <span class="bg-blue-100 text-blue-700 text-xs px-2 py-1 rounded-full font-bold">Planned</span>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Node.js</h3>
                <p class="text-gray-500 text-sm leading-relaxed mb-6">
                    Official NPM package for Node.js backends. Express/NestJS compatible.
                </p>
            </div>
        </div>

        <!-- Python SDK (Coming Soon) -->
        <div
            class="bg-white rounded-2xl shadow hover:shadow-lg border border-gray-100 overflow-hidden opacity-60 grayscale hover:grayscale-0 transition duration-300">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <div
                        class="w-14 h-14 rounded-2xl bg-yellow-50 text-yellow-600 flex items-center justify-center text-3xl shadow-sm">
                        <i class="fab fa-python"></i>
                    </div>
                    <span class="bg-blue-100 text-blue-700 text-xs px-2 py-1 rounded-full font-bold">Planned</span>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Python</h3>
                <p class="text-gray-500 text-sm leading-relaxed mb-6">
                    Python package for Django, Flask, and FastAPI integrations.
                </p>
            </div>
        </div>

    </div>

</div>

<?php require_once 'layout_footer.php'; ?>