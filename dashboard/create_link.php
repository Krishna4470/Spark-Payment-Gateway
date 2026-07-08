<?php
// dashboard/create_link.php
require_once '../includes/dashboard_utils.php';
require_once '../includes/db.php';
checkAuth();

$pdo = getDBConnection();

// --- 1. Auto-Migration (Ensure Columns Exist) ---
try {
    $pdo->exec("ALTER TABLE payment_links ADD COLUMN expiry_date DATETIME NULL");
} catch (Exception $e) {
}
try {
    $pdo->exec("ALTER TABLE payment_links ADD COLUMN usage_limit INT NULL");
} catch (Exception $e) {
}

$message = "";
$msgType = "success";

// --- 2. Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Create Link
    if (isset($_POST['create_link'])) {
        $title = trim($_POST['title']);
        $amount = trim($_POST['amount']);
        $expiry = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : NULL;
        $limit = !empty($_POST['usage_limit']) ? $_POST['usage_limit'] : NULL;
        $slug = uniqid(); // Random Slug

        if ($title && $amount > 0) {
            $stmt = $pdo->prepare("INSERT INTO payment_links (slug, title, amount, expiry_date, usage_limit, is_active) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->execute([$slug, $title, $amount, $expiry, $limit]);
            $message = "Payment Link Created Successfully!";
        } else {
            $message = "Title and Valid Amount are required.";
            $msgType = "error";
        }
    }

    // Delete Link
    if (isset($_POST['delete_link'])) {
        $id = $_POST['link_id'];
        $pdo->prepare("DELETE FROM payment_links WHERE id = ?")->execute([$id]);
        $message = "Link Deleted Successfully.";
    }
}

// --- 3. Fetch Links ---
$links = $pdo->query("SELECT * FROM payment_links ORDER BY id DESC")->fetchAll();

require_once 'layout.php';
?>

<div class="max-w-7xl mx-auto space-y-8">

    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="text-3xl font-bold text-gray-800 tracking-tight">Create Link</h2>
            <p class="text-gray-500 text-sm mt-1">Generate fixed-amount payment links with expiry controls.</p>
        </div>
        <?php if ($message): ?>
            <div
                class="<?= $msgType == 'success' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200' ?> border px-4 py-2 rounded-lg shadow-sm text-sm font-semibold animate-fade-in flex items-center">
                <i class="fas <?= $msgType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-2"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Creation Form (1/3) -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden sticky top-24">
                <div class="px-6 py-4 border-b border-gray-50 bg-gray-50/50 flex items-center gap-2">
                    <div
                        class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-sm">
                        <i class="fas fa-magic"></i></div>
                    <h3 class="font-bold text-gray-800">New Payment Link</h3>
                </div>

                <form method="post" class="p-6 space-y-5">
                    <input type="hidden" name="create_link" value="1">

                    <!-- Title -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Purpose /
                            Title</label>
                        <input type="text" name="title" placeholder="e.g. eBook Sale" required
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-700 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    </div>

                    <!-- Amount -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Fixed Amount
                            (₹)</label>
                        <div class="relative">
                            <span class="absolute left-4 top-3 text-gray-400 font-bold">₹</span>
                            <input type="number" name="amount" placeholder="0.00" step="0.01" required
                                class="w-full pl-8 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-700 font-bold focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                        </div>
                    </div>

                    <!-- Usage Limit -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">User Limit
                            (Optional)</label>
                        <input type="number" name="usage_limit" placeholder="Max users allowed"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-700 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                        <p class="text-[10px] text-gray-400 mt-1">Leave empty for unlimited.</p>
                    </div>

                    <!-- Expiry -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Expiry Date
                            (Optional)</label>
                        <input type="datetime-local" name="expiry_date"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-700 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    </div>

                    <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl shadow-lg shadow-blue-200 transition transform hover:-translate-y-0.5">
                        Create Link
                    </button>
                </form>
            </div>
        </div>

        <!-- Links List (2/3) -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <h3 class="font-bold text-gray-800">Active Links</h3>
                    <span
                        class="bg-blue-100 text-blue-600 px-3 py-1 rounded-full text-xs font-bold"><?= count($links) ?>
                        Total</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500 font-bold">
                            <tr>
                                <th class="px-6 py-4">Link Details</th>
                                <th class="px-6 py-4">Config</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php if (count($links) > 0): ?>
                                <?php foreach ($links as $l): ?>
                                    <?php
                                    $linkUrl = BASE_URL . "/pay.php?slug=" . $l['slug'];
                                    $isExpired = !empty($l['expiry_date']) && strtotime($l['expiry_date']) < time();
                                    // Usage check would require querying transactions count, for now basic badge
                                    $limitText = $l['usage_limit'] ? $l['usage_limit'] . " Users" : "Unlimited";
                                    ?>
                                    <tr class="hover:bg-gray-50 transition group">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-gray-800"><?= htmlspecialchars($l['title']) ?></div>
                                            <div class="text-xs text-gray-400 mt-1 flex items-center gap-2">
                                                <span
                                                    class="bg-blue-50 text-blue-600 px-1.5 py-0.5 rounded border border-blue-100 font-mono">₹<?= $l['amount'] ?></span>
                                                <span>• Created <?= date('M d', strtotime($l['created_at'])) ?></span>
                                            </div>
                                        </td>

                                        <td class="px-6 py-4">
                                            <div class="space-y-1">
                                                <div class="text-xs text-gray-500">
                                                    <i class="fas fa-users w-4 text-center"></i> <?= $limitText ?>
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    <i class="fas fa-clock w-4 text-center"></i>
                                                    <?= $l['expiry_date'] ? date('M d, H:i', strtotime($l['expiry_date'])) : 'Never Expires' ?>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-6 py-4">
                                            <?php if ($isExpired): ?>
                                                <span
                                                    class="bg-red-100 text-red-700 px-2 py-1 rounded-full text-xs font-bold">Expired</span>
                                            <?php elseif ($l['is_active']): ?>
                                                <span
                                                    class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-bold">Active</span>
                                            <?php else: ?>
                                                <span
                                                    class="bg-gray-100 text-gray-500 px-2 py-1 rounded-full text-xs font-bold">Inactive</span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="px-6 py-4 text-right">
                                            <div
                                                class="flex items-center justify-end gap-2 opacity-100 md:opacity-0 group-hover:opacity-100 transition">
                                                <button onclick="copyToClipboard('<?= $linkUrl ?>', this)"
                                                    class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition"
                                                    title="Copy Link">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                                <form method="post" onsubmit="return confirm('Delete this link?')"
                                                    style="display:inline;">
                                                    <input type="hidden" name="delete_link" value="1">
                                                    <input type="hidden" name="link_id" value="<?= $l['id'] ?>">
                                                    <button
                                                        class="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
                                                        title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-gray-400">
                                        No active links found. Create one to get started.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

</div>

<script>
    function copyToClipboard(text, btn) {
        navigator.clipboard.writeText(text);
        const originalIcon = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        btn.classList.add('text-green-600');
        setTimeout(() => {
            btn.innerHTML = originalIcon;
            btn.classList.remove('text-green-600');
        }, 1500);
    }
</script>

<?php require_once 'layout_footer.php'; ?>