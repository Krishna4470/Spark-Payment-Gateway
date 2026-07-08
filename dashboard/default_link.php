<?php
// dashboard/default_link.php
// Ultra Modern & Premium Design
require_once '../includes/dashboard_utils.php';
checkAuth();

// Construct Base URL safely
$base = defined('BASE_URL') ? BASE_URL : (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$paymentLink = rtrim($base, '/') . "/pay.php?slug=default";

require_once 'layout.php';
?>

<style>
    @keyframes float {

        0%,
        100% {
            transform: translateY(0px);
        }

        50% {
            transform: translateY(-10px);
        }
    }

    @keyframes pulse-glow {

        0%,
        100% {
            opacity: 0.15;
        }

        50% {
            opacity: 0.35;
        }
    }

    .animate-float {
        animation: float 6s ease-in-out infinite;
    }

    .animate-pulse-glow {
        animation: pulse-glow 3s ease-in-out infinite;
    }

    .gradient-border {
        position: relative;
        background: linear-gradient(white, white) padding-box,
            linear-gradient(135deg, #667eea 0%, #764ba2 100%) border-box;
        border: 2px solid transparent;
    }

    .glass-effect {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }
</style>

<!-- Main Container with Background Pattern -->
<div class="min-h-[80vh] flex items-center justify-center py-12 px-4 relative overflow-hidden">

    <!-- Animated Background Elements -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-20 left-10 w-72 h-72 bg-blue-400/10 rounded-full blur-3xl animate-pulse-glow"></div>
        <div class="absolute bottom-20 right-10 w-96 h-96 bg-purple-400/10 rounded-full blur-3xl animate-pulse-glow"
            style="animation-delay: 1s;"></div>
    </div>

    <div class="max-w-2xl w-full relative z-10">

        <!-- Header Section -->
        <div class="text-center mb-10 animate-float">
            <div
                class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-blue-500 to-purple-600 shadow-2xl shadow-blue-500/30 mb-6">
                <i class="fas fa-link text-3xl text-white"></i>
            </div>
            <h1
                class="text-4xl md:text-5xl font-extrabold bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 bg-clip-text text-transparent mb-3">
                Default Payment Link
            </h1>
            <p class="text-gray-500 text-lg">Share this link to accept instant payments from anyone, anywhere</p>
        </div>

        <!-- Main Card -->
        <div class="relative group">
            <!-- Animated Glow Effect -->
            <div
                class="absolute -inset-1 bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 rounded-3xl blur-xl opacity-20 group-hover:opacity-40 transition duration-700 animate-pulse-glow">
            </div>

            <!-- Card Content -->
            <div class="relative glass-effect rounded-3xl p-8 md:p-10 shadow-2xl border border-white/50">

                <!-- Amount Input Section -->
                <div class="mb-8">
                    <label
                        class="block text-sm font-bold text-gray-600 uppercase tracking-wider mb-4 text-center flex items-center justify-center gap-2">
                        <i class="fas fa-rupee-sign text-blue-500"></i>
                        Set Amount (Optional)
                    </label>
                    <div class="relative max-w-sm mx-auto">
                        <div class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none">
                            <span class="text-3xl text-gray-400 font-bold">₹</span>
                        </div>
                        <input type="number" id="amountInput" placeholder="0" oninput="updateLink()"
                            class="w-full pl-16 pr-6 py-5 bg-gradient-to-br from-gray-50 to-gray-100 border-2 border-gray-200 focus:border-blue-500 focus:from-white focus:to-blue-50 rounded-2xl text-center text-4xl font-bold text-gray-800 placeholder-gray-300 outline-none transition-all duration-300 shadow-inner hover:shadow-lg">
                    </div>
                    <p class="text-xs text-gray-400 text-center mt-3">Leave blank for customer to enter amount</p>
                </div>

                <!-- Link Display Section -->
                <div
                    class="bg-gradient-to-br from-blue-50 to-purple-50 rounded-2xl p-4 border-2 border-blue-100 mb-6 transition-all duration-300 hover:shadow-lg">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex-shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center shadow-lg">
                            <i class="fas fa-qrcode text-white text-xl"></i>
                        </div>
                        <div class="flex-1 overflow-hidden">
                            <div class="text-xs text-gray-500 font-semibold mb-1 uppercase tracking-wide">Your Payment
                                Link</div>
                            <div class="truncate text-sm font-mono text-blue-600 font-semibold" id="linkDisplay">
                                <?= $paymentLink ?>
                            </div>
                        </div>
                        <button onclick="copyToClipboard()" id="copyBtn"
                            class="flex-shrink-0 w-12 h-12 bg-white hover:bg-blue-50 text-blue-600 rounded-xl shadow-md border-2 border-blue-100 transition-all duration-300 active:scale-95 hover:shadow-lg group/copy">
                            <i class="far fa-copy text-xl group-hover/copy:scale-110 transition-transform"></i>
                        </button>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="grid grid-cols-2 gap-4">
                    <a id="openLinkBtn" href="<?= $paymentLink ?>" target="_blank"
                        class="flex items-center justify-center gap-3 py-4 rounded-xl border-2 border-gray-200 bg-white text-gray-700 hover:bg-gray-50 hover:border-gray-300 font-semibold transition-all duration-300 active:scale-95 hover:shadow-lg group/open">
                        <i
                            class="fas fa-external-link-alt text-gray-400 group-hover/open:text-blue-500 transition-colors"></i>
                        <span>Open Link</span>
                    </a>
                    <button onclick="shareLink()"
                        class="flex items-center justify-center gap-3 py-4 rounded-xl bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 text-white font-semibold shadow-xl shadow-blue-500/30 hover:shadow-2xl hover:shadow-blue-500/40 hover:-translate-y-1 transition-all duration-300 active:translate-y-0 active:shadow-lg group/share">
                        <i class="fas fa-share-alt group-hover/share:rotate-12 transition-transform"></i>
                        <span>Share Now</span>
                    </button>
                </div>

                <!-- Quick Stats -->
                <div class="grid grid-cols-3 gap-4 mt-8 pt-8 border-t border-gray-200">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600 mb-1">∞</div>
                        <div class="text-xs text-gray-500 font-medium">Unlimited Use</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600 mb-1">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="text-xs text-gray-500 font-medium">Secure</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-pink-600 mb-1">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div class="text-xs text-gray-500 font-medium">Instant</div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Footer Info -->
        <div class="text-center mt-8 space-y-3">
            <div
                class="inline-flex items-center gap-2 px-4 py-2 bg-white/50 backdrop-blur-sm rounded-full border border-gray-200 shadow-sm">
                <i class="fas fa-shield-alt text-green-500"></i>
                <span class="text-sm text-gray-600 font-medium">Powered by Paytm & BharatPe</span>
            </div>
            <p class="text-xs text-gray-400">
                <i class="fas fa-info-circle"></i> This link never expires and can be used unlimited times
            </p>
        </div>

    </div>
</div>

<script>
    const baseLink = "<?= $paymentLink ?>";

    function updateLink() {
        const amount = document.getElementById('amountInput').value.trim();
        const display = document.getElementById('linkDisplay');
        const openBtn = document.getElementById('openLinkBtn');

        let newLink = baseLink;
        if (amount && parseFloat(amount) > 0) {
            newLink += "&amount=" + amount;
        }

        display.innerText = newLink;
        openBtn.href = newLink;
    }

    function copyToClipboard() {
        const text = document.getElementById('linkDisplay').innerText.trim();
        navigator.clipboard.writeText(text).then(() => {
            const btn = document.getElementById('copyBtn');
            const icon = btn.querySelector('i');

            // Success Animation
            icon.className = 'fas fa-check text-xl';
            btn.classList.add('bg-green-500', 'text-white', 'border-green-500', 'scale-110');

            // Haptic feedback (if supported)
            if (navigator.vibrate) navigator.vibrate(50);

            setTimeout(() => {
                icon.className = 'far fa-copy text-xl';
                btn.classList.remove('bg-green-500', 'text-white', 'border-green-500', 'scale-110');
            }, 2000);
        }).catch(() => {
            // Fallback for older browsers
            const textArea = document.createElement("textarea");
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);

            alert('Link copied to clipboard!');
        });
    }

    function shareLink() {
        const text = document.getElementById('linkDisplay').innerText.trim();
        const amount = document.getElementById('amountInput').value.trim();

        let shareText = '💳 Pay securely using this link:\n\n';
        if (amount && parseFloat(amount) > 0) {
            shareText = `💰 Payment Request: ₹${amount}\n\n` + shareText;
        }

        if (navigator.share) {
            navigator.share({
                title: 'Payment Link',
                text: shareText,
                url: text,
            }).catch(() => {
                // Fallback to WhatsApp
                fallbackShare(text, shareText);
            });
        } else {
            fallbackShare(text, shareText);
        }
    }

    function fallbackShare(url, text) {
        const whatsappUrl = 'https://wa.me/?text=' + encodeURIComponent(text + url);
        window.open(whatsappUrl, '_blank');
    }

    // Add smooth entrance animation
    document.addEventListener('DOMContentLoaded', () => {
        const card = document.querySelector('.glass-effect');
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';

        setTimeout(() => {
            card.style.transition = 'all 0.6s ease-out';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100);
    });
</script>

<?php require_once 'layout_footer.php'; ?>