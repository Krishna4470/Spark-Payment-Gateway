<?php
// dashboard/layout.php
$currentPage = basename($_SERVER['PHP_SELF']);

function isActive($page, $current)
{
    return $page === $current ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/30' : 'text-gray-400 hover:bg-white/5 hover:text-white';
}
?>
<?php
// layout.php
$siteNameLink = getSetting('site_name') ?: 'Paytm Gateway';
$favIconLink = getSetting('favicon_path') ?: '';
$siteInitial = strtoupper(substr($siteNameLink, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php if ($favIconLink): ?>
        <link rel="shortcut icon" href="../<?= htmlspecialchars($favIconLink) ?>?v=<?= time() ?>" type="image/x-icon">
    <?php endif; ?>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        dark: {
                            900: '#111827',
                            800: '#1f2937',
                            700: '#374151',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        /* Custom Scrollbar for Sidebar */
        .custom-scroll::-webkit-scrollbar {
            width: 5px;
        }

        .custom-scroll::-webkit-scrollbar-track {
            background: #1f2937;
        }

        .custom-scroll::-webkit-scrollbar-thumb {
            background: #4b5563;
            border-radius: 10px;
        }

        .sidebar-transition {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>
</head>

<body class="bg-gray-50 font-sans text-gray-800 antialiased">

    <!-- Mobile Header / Hamburger -->
    <div
        class="md:hidden flex items-center justify-between bg-dark-900 text-white p-4 fixed top-0 left-0 right-0 z-50 shadow-md h-16">
        <div class="flex items-center gap-3">
            <div
                class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center font-bold text-white shadow-lg shadow-blue-500/30">
                <?= $siteInitial ?>
            </div>
            <span class="font-bold text-lg tracking-tight">
                <?= htmlspecialchars($siteNameLink) ?>
            </span>
        </div>
        <button onclick="toggleSidebar()" class="text-gray-300 hover:text-white transition focus:outline-none">
            <i class="fas fa-bars text-2xl"></i>
        </button>
    </div>

    <!-- Overlay Backprop -->
    <div id="sidebarOverlay" onclick="toggleSidebar()"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 hidden md:hidden transition-opacity duration-300"></div>

    <!-- Sidebar Container -->
    <aside id="sidebar"
        class="fixed top-0 left-0 z-50 h-screen w-64 bg-dark-900 border-r border-gray-800 flex flex-col transition-transform duration-300 -translate-x-full md:translate-x-0 shadow-2xl md:shadow-none">

        <!-- Brand / Logo -->
        <div class="h-16 flex items-center px-6 border-b border-gray-800/50 bg-dark-900 shrink-0">
            <div
                class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center font-bold text-white shadow-lg shadow-blue-500/30 mr-3">
                <?= $siteInitial ?>
            </div>
            <span class="font-bold text-white text-xl tracking-tight">
                <?= htmlspecialchars($siteNameLink) ?>
            </span>
        </div>

        <!-- Mobile Close Actions -->
        <div class="md:hidden absolute top-4 right-4">
            <!-- Close button handled by overlay click usually, but added for explicit action -->
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 overflow-y-auto custom-scroll py-6 px-3 space-y-8">

            <!-- Dashboard -->
            <div class="mb-6">
                <ul class="space-y-1">
                    <li>
                        <a href="index.php"
                            class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 group <?= isActive('index.php', $currentPage) ?>">
                            <i class="fas fa-home w-5 text-center mr-3 group-hover:scale-110 transition-transform"></i>
                            Dashboard
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Group 1 -->
            <div>
                <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Gateway</p>
                <ul class="space-y-1">
                    <li>
                        <a href="setup_gateway.php"
                            class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 group <?= isActive('setup_gateway.php', $currentPage) ?>">
                            <i
                                class="fas fa-qrcode w-5 text-center mr-3 group-hover:scale-110 transition-transform"></i>
                            Connect UPI QR
                        </a>
                    </li>
                    <li>
                        <a href="theme.php"
                            class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 group <?= isActive('theme.php', $currentPage) ?>">
                            <i
                                class="fas fa-paint-brush w-5 text-center mr-3 group-hover:scale-110 transition-transform"></i>
                            Payment Theme
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Group 2 -->
            <div>
                <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Developers</p>
                <ul class="space-y-1">
                    <li>
                        <a href="api_setup.php"
                            class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 group <?= isActive('api_setup.php', $currentPage) ?>">
                            <i class="fas fa-code w-5 text-center mr-3 group-hover:scale-110 transition-transform"></i>
                            API Keys & Docs
                        </a>
                    </li>
                    <li>
                        <a href="sdks.php"
                            class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 group <?= isActive('sdks.php', $currentPage) ?>">
                            <i
                                class="fas fa-box-open w-5 text-center mr-3 group-hover:scale-110 transition-transform"></i>
                            Download SDKs
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Group 3 -->
            <div>
                <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Transactions</p>
                <ul class="space-y-1">
                    <li>
                        <a href="create_link.php"
                            class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 group <?= isActive('create_link.php', $currentPage) ?>">
                            <i
                                class="fas fa-plus-circle w-5 text-center mr-3 group-hover:scale-110 transition-transform"></i>
                            Create Link
                        </a>
                    </li>
                    <li>
                        <a href="default_link.php"
                            class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 group <?= isActive('default_link.php', $currentPage) ?>">
                            <i class="fas fa-link w-5 text-center mr-3 group-hover:scale-110 transition-transform"></i>
                            Default Link
                        </a>
                    </li>
                    <li>
                        <a href="transactions.php"
                            class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 group <?= isActive('transactions.php', $currentPage) ?>">
                            <i
                                class="fas fa-exchange-alt w-5 text-center mr-3 group-hover:scale-110 transition-transform"></i>
                            Transactions
                        </a>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Group Products -->
            <div>
                <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Products</p>
                <ul class="space-y-1">
                    <li>
                        <a href="sell-products.php"
                            class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 group <?= isActive('sell-products.php', $currentPage) ?>">
                            <i
                                class="fas fa-shopping-bag w-5 text-center mr-3 group-hover:scale-110 transition-transform"></i>
                            Sell Products
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Group 4 -->
            <div>
                <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Settings</p>
                <ul class="space-y-1">
                    <li>
                        <a href="settings.php"
                            class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 group <?= isActive('settings.php', $currentPage) ?>">
                            <i class="fas fa-cog w-5 text-center mr-3 group-hover:scale-110 transition-transform"></i>
                            Settings
                        </a>
                    </li>
                    <li>
                        <a href="logout.php"
                            class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium text-red-400 hover:bg-red-500/10 hover:text-red-300 transition-all duration-200 group">
                            <i
                                class="fas fa-sign-out-alt w-5 text-center mr-3 group-hover:scale-110 transition-transform"></i>
                            Logout
                        </a>
                    </li>
                </ul>
            </div>

        </nav>

        <!-- Footer -->
        <div class="p-4 border-t border-gray-800 bg-dark-900 shrink-0">
            <div class="flex items-center gap-3 px-2">
                <div
                    class="w-8 h-8 rounded-full bg-gradient-to-tr from-green-400 to-blue-500 flex items-center justify-center text-xs font-bold text-white uppercase">
                    A
                </div>
                <div class="overflow-hidden">
                    <p class="text-xs font-medium text-white truncate">Administrator</p>
                    <p class="text-[10px] text-green-400 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span> Online
                    </p>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="min-h-screen md:ml-64 pt-16 md:pt-0 transition-all duration-300">
        <!-- Main content padding container -->
        <div class="p-4 md:p-8 space-y-6">
            <script>
                function toggleSidebar() {
                    const sidebar = document.getElementById('sidebar');
                    const overlay = document.getElementById('sidebarOverlay');

                    // Check if hidden (transform is set via CSS classes)
                    if (sidebar.classList.contains('-translate-x-full')) {
                        // Open
                        sidebar.classList.remove('-translate-x-full');
                        overlay.classList.remove('hidden');
                    } else {
                        // Close
                        sidebar.classList.add('-translate-x-full');
                        overlay.classList.add('hidden');
                    }
                }
            </script>