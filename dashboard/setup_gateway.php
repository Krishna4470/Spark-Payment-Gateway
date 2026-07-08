<?php
// dashboard/setup_gateway.php
require_once '../includes/dashboard_utils.php';
require_once '../includes/db.php';
checkAuth();

$pdo = getDBConnection();

// --- 1. Auto-Migration: Create Tables if not exists ---
// Paytm Table
$pdo->exec("CREATE TABLE IF NOT EXISTS merchants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mobile VARCHAR(20) NOT NULL,
    mid VARCHAR(100) NOT NULL,
    mkey VARCHAR(100) NOT NULL,
    vpa VARCHAR(100) NOT NULL,
    is_default BOOLEAN DEFAULT 0,
    status ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// BharatPe Table
$pdo->exec("CREATE TABLE IF NOT EXISTS bharatpe_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_token VARCHAR(255) DEFAULT 'admin', 
    merchantId VARCHAR(255),
    token TEXT,
    cookie TEXT,
    Upiid VARCHAR(255),
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Ensure active_gateway setting exists
$pdo->exec("INSERT IGNORE INTO config_settings (setting_key, setting_value) VALUES ('active_gateway', 'PAYTM')");

$message = "";
$msgType = "success";

// --- 2. Handle Form Submissions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- UPDATE EXISTING GATEWAY ---
    if (isset($_POST['update_gateway'])) {
        $type = $_POST['gateway_type'];
        $editId = $_POST['edit_id'];

        if ($type === 'PAYTM') {
            $mobile = trim($_POST['paytm_mobile']);
            $mid = trim($_POST['paytm_mid']);
            $vpa = trim($_POST['paytm_vpa']);

            if ($mobile && $mid && $vpa) {
                $stmt = $pdo->prepare("UPDATE merchants SET mobile=?, mid=?, vpa=? WHERE id=?");
                $stmt->execute([$mobile, $mid, $vpa, $editId]);
                $message = "Paytm Merchant Updated Successfully!";
                header("Location: setup_gateway.php");
                exit;
            } else {
                $message = "All Paytm details are required.";
                $msgType = "error";
            }
        } elseif ($type === 'BHARATPE') {
            $mid = trim($_POST['bp_mid']);
            $token = trim($_POST['bp_token']);
            $cookie = trim($_POST['bp_cookie']);
            $vpa = trim($_POST['bp_vpa']);

            if (strpos($vpa, 'http') !== false) {
                $message = "Invalid VPA. Do not paste the URL. Please enter a valid UPI ID (e.g., user@upi).";
                $msgType = "error";
            } elseif ($mid && $token && $cookie && $vpa) {
                $stmt = $pdo->prepare("UPDATE bharatpe_tokens SET merchantId=?, token=?, cookie=?, Upiid=? WHERE id=?");
                $stmt->execute([$mid, $token, $cookie, $vpa, $editId]);
                $message = "BharatPe Account Updated!";
                header("Location: setup_gateway.php");
                exit;
            } else {
                $message = "All BharatPe details are required.";
                $msgType = "error";
            }
        }
    }

    // --- ADD NEW GATEWAY ---
    if (isset($_POST['save_gateway'])) {
        $type = $_POST['gateway_type']; // 'PAYTM' or 'BHARATPE'

        if ($type === 'PAYTM') {
            $mobile = trim($_POST['paytm_mobile']);
            $mid = trim($_POST['paytm_mid']);
            $vpa = trim($_POST['paytm_vpa']);

            if ($mobile && $mid && $vpa) {
                $count = $pdo->query("SELECT COUNT(*) FROM merchants")->fetchColumn();
                $isDefault = ($count == 0) ? 1 : 0; // First one is default

                $stmt = $pdo->prepare("INSERT INTO merchants (mobile, mid, mkey, vpa, is_default) VALUES (?, ?, '', ?, ?)");
                $stmt->execute([$mobile, $mid, $vpa, $isDefault]);
                $message = "Paytm Merchant Added Successfully!";

                if ($isDefault) {
                    $pdo->exec("UPDATE config_settings SET setting_value = 'PAYTM' WHERE setting_key = 'active_gateway'");
                }
            } else {
                $message = "All Paytm details are required.";
                $msgType = "error";
            }
        } elseif ($type === 'BHARATPE') {
            $mid = trim($_POST['bp_mid']);
            $token = trim($_POST['bp_token']);
            $cookie = trim($_POST['bp_cookie']);
            $vpa = trim($_POST['bp_vpa']);

            if (strpos($vpa, 'http') !== false) {
                $message = "Invalid VPA. Do not paste the URL. Please enter a valid UPI ID (e.g., user@upi).";
                $msgType = "error";
            } elseif ($mid && $token && $cookie && $vpa) {
                // ALWAYS INSERT NEW
                $count = $pdo->query("SELECT COUNT(*) FROM bharatpe_tokens")->fetchColumn();
                $isActive = ($count == 0) ? 1 : 0;

                $stmt = $pdo->prepare("INSERT INTO bharatpe_tokens (merchantId, token, cookie, Upiid, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$mid, $token, $cookie, $vpa, $isActive]);

                $message = "BharatPe Account Added!";

                if ($isActive) {
                    $pdo->exec("UPDATE config_settings SET setting_value = 'BHARATPE' WHERE setting_key = 'active_gateway'");
                }

            } else {
                $message = "All BharatPe details are required.";
                $msgType = "error";
            }
        }
    }

    // --- SET DEFAULT GATEWAY ---
    if (isset($_POST['set_default'])) {
        $targetType = $_POST['default_type']; // 'PAYTM' or 'BHARATPE'
        $targetId = $_POST['default_id'];

        if ($targetType === 'PAYTM') {
            $pdo->exec("UPDATE config_settings SET setting_value = 'PAYTM' WHERE setting_key = 'active_gateway'");
            $pdo->exec("UPDATE merchants SET is_default = 0");
            $stmt = $pdo->prepare("UPDATE merchants SET is_default = 1 WHERE id = ?");
            $stmt->execute([$targetId]);
            $message = "Active Gateway set to Paytm.";

        } elseif ($targetType === 'BHARATPE') {
            $pdo->exec("UPDATE config_settings SET setting_value = 'BHARATPE' WHERE setting_key = 'active_gateway'");
            $pdo->exec("UPDATE bharatpe_tokens SET is_active = 0"); // Deactivate all
            $stmt = $pdo->prepare("UPDATE bharatpe_tokens SET is_active = 1 WHERE id = ?"); // Activate target
            $stmt->execute([$targetId]);
            $message = "Active Gateway set to BharatPe.";
        }
    }

    // --- DELETE GATEWAY ---
    if (isset($_POST['delete_gateway'])) {
        $delId = $_POST['delete_id'];
        $delType = $_POST['delete_type'];

        if ($delType == 'PAYTM') {
            $pdo->prepare("DELETE FROM merchants WHERE id = ?")->execute([$delId]);
            $message = "Paytm Merchant Deleted.";
        } elseif ($delType == 'BHARATPE') {
            $pdo->prepare("DELETE FROM bharatpe_tokens WHERE id = ?")->execute([$delId]);
            $message = "BharatPe Account Deleted.";
        }
    }
}

// --- 3. Fetch Data ---
$merchants = $pdo->query("SELECT * FROM merchants ORDER BY id DESC")->fetchAll();
$bharatPeAccounts = $pdo->query("SELECT * FROM bharatpe_tokens ORDER BY id DESC")->fetchAll();
$activeGateway = $pdo->query("SELECT setting_value FROM config_settings WHERE setting_key = 'active_gateway'")->fetchColumn() ?: 'PAYTM';

// --- 4. Check if Editing ---
$editMode = false;
$editData = null;
$editType = null;

if (isset($_GET['edit_type']) && isset($_GET['edit_id'])) {
    $editType = $_GET['edit_type'];
    $editId = $_GET['edit_id'];
    $editMode = true;

    if ($editType == 'PAYTM') {
        $stmt = $pdo->prepare("SELECT * FROM merchants WHERE id = ?");
        $stmt->execute([$editId]);
        $editData = $stmt->fetch();
    } elseif ($editType == 'BHARATPE') {
        $stmt = $pdo->prepare("SELECT * FROM bharatpe_tokens WHERE id = ?");
        $stmt->execute([$editId]);
        $editData = $stmt->fetch();
    }
}

require_once 'layout.php';
?>

<div class="max-w-6xl mx-auto space-y-8">

    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Gateway Configuration</h2>
            <p class="text-gray-500 text-sm">Add new gateways and manage existing ones.</p>
        </div>
        <?php if ($message): ?>
            <div
                class="<?= $msgType == 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?> px-4 py-2 rounded-lg font-medium shadow-sm animate-fade-in">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- LEFT COLUMN: ADD GATEWAY FORM -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden sticky top-6">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-100">
                    <h3 class="font-bold text-gray-800">
                        <?= $editMode ? 'Edit Gateway' : 'Add New Gateway' ?>
                    </h3>
                </div>

                <form method="post" class="p-6 space-y-5">
                    <?php if ($editMode): ?>
                        <input type="hidden" name="update_gateway" value="1">
                        <input type="hidden" name="edit_id" value="<?= $_GET['edit_id'] ?>">
                    <?php else: ?>
                        <input type="hidden" name="save_gateway" value="1">
                    <?php endif; ?>

                    <!-- Gateway Type Selector -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Gateway Type</label>
                        <select name="gateway_type" id="gateway_type" onchange="toggleForm()" <?= $editMode ? 'disabled' : '' ?>
                            class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition font-semibold">
                            <option value="PAYTM" <?= ($editMode && $editType == 'PAYTM') ? 'selected' : '' ?>>Paytm
                            </option>
                            <option value="BHARATPE" <?= ($editMode && $editType == 'BHARATPE') ? 'selected' : '' ?>>
                                BharatPe</option>
                        </select>
                        <?php if ($editMode): ?>
                            <input type="hidden" name="gateway_type" value="<?= $editType ?>">
                        <?php endif; ?>
                    </div>

                    <!-- PAYTM FIELDS -->
                    <div id="paytm_fields" class="space-y-4 animate-fade-in">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Mobile</label>
                            <input type="text" name="paytm_mobile" placeholder="e.g. 9876543210"
                                value="<?= ($editMode && $editType == 'PAYTM') ? htmlspecialchars($editData['mobile']) : '' ?>"
                                class="w-full px-4 py-2 bg-gray-50 border rounded-lg focus:border-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">MID</label>
                            <input type="text" name="paytm_mid" placeholder="Paytm MID"
                                value="<?= ($editMode && $editType == 'PAYTM') ? htmlspecialchars($editData['mid']) : '' ?>"
                                class="w-full px-4 py-2 bg-gray-50 border rounded-lg focus:border-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">UPI VPA</label>
                            <input type="text" name="paytm_vpa" placeholder="paytm-qr...@paytm"
                                value="<?= ($editMode && $editType == 'PAYTM') ? htmlspecialchars($editData['vpa']) : '' ?>"
                                class="w-full px-4 py-2 bg-gray-50 border rounded-lg focus:border-blue-500 outline-none">
                        </div>
                    </div>

                    <!-- BHARATPE FIELDS -->
                    <div id="bharatpe_fields" class="space-y-4 hidden animate-fade-in">
                        <div class="bg-blue-50 border-l-4 border-blue-400 p-2 text-xs text-blue-700">
                            Use <code>extract_creds.js</code> to get these values.
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Auth Token</label>
                            <input type="text" name="bp_token" placeholder="Paste Auth Token"
                                value="<?= ($editMode && $editType == 'BHARATPE') ? htmlspecialchars($editData['token']) : '' ?>"
                                class="w-full px-4 py-2 bg-gray-50 border rounded-lg focus:border-blue-500 outline-none font-mono text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Cookie</label>
                            <textarea name="bp_cookie" rows="2" placeholder="Paste Cookie"
                                class="w-full px-4 py-2 bg-gray-50 border rounded-lg focus:border-blue-500 outline-none font-mono text-xs"><?= ($editMode && $editType == 'BHARATPE') ? htmlspecialchars($editData['cookie']) : '' ?></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Merchant ID</label>
                            <input type="text" name="bp_mid" placeholder="BharatPe MID"
                                value="<?= ($editMode && $editType == 'BHARATPE') ? htmlspecialchars($editData['merchantId']) : '' ?>"
                                class="w-full px-4 py-2 bg-gray-50 border rounded-lg focus:border-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">UPI VPA</label>
                            <input type="text" name="bp_vpa" placeholder="user@upi (No URL)"
                                value="<?= ($editMode && $editType == 'BHARATPE') ? htmlspecialchars($editData['Upiid']) : '' ?>"
                                class="w-full px-4 py-2 bg-gray-50 border rounded-lg focus:border-blue-500 outline-none">
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit"
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl shadow transition">
                            <?= $editMode ? 'Update Gateway' : 'Add Gateway' ?>
                        </button>
                        <?php if ($editMode): ?>
                            <a href="setup_gateway.php"
                                class="px-6 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-3 rounded-xl shadow transition flex items-center justify-center">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- RIGHT COLUMN: MANAGED LIST -->
        <div class="lg:col-span-2 space-y-6">

            <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800">Added Gateways</h3>
                    <span class="text-xs font-bold bg-green-100 text-green-700 px-2 py-1 rounded">Active:
                        <?= $activeGateway ?></span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-xs font-bold text-gray-500 uppercase border-b bg-gray-50">
                                <th class="px-6 py-3">Type</th>
                                <th class="px-6 py-3">Identifier</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">

                            <!-- PAYTM ROWS -->
                            <?php foreach ($merchants as $m): ?>
                                <?php $isActive = ($activeGateway == 'PAYTM' && $m['is_default']); ?>
                                <tr class="hover:bg-gray-50 transition <?= $isActive ? 'bg-blue-50/50' : '' ?>">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="w-6 h-6 rounded bg-blue-100 text-blue-600 flex items-center justify-center text-xs"><i
                                                    class="fas fa-wallet"></i></span>
                                            <span class="font-semibold text-gray-700">Paytm</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600 font-mono text-xs">
                                        <?= htmlspecialchars($m['mobile']) ?><br>
                                        <span class="text-gray-400"><?= htmlspecialchars($m['mid']) ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($isActive): ?>
                                            <span
                                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Active
                                            </span>
                                        <?php else: ?>
                                            <form method="post">
                                                <input type="hidden" name="set_default" value="1">
                                                <input type="hidden" name="default_type" value="PAYTM">
                                                <input type="hidden" name="default_id" value="<?= $m['id'] ?>">
                                                <button
                                                    class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-600 px-2 py-1 rounded transition">Set
                                                    Active</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <!-- Edit Button -->
                                            <a href="?edit_type=PAYTM&edit_id=<?= $m['id'] ?>"
                                                class="text-blue-500 hover:text-blue-700 transition text-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <!-- Delete Button -->
                                            <form method="post" onsubmit="return confirm('Delete this gateway?');"
                                                class="inline-block">
                                                <input type="hidden" name="delete_gateway" value="1">
                                                <input type="hidden" name="delete_type" value="PAYTM">
                                                <input type="hidden" name="delete_id" value="<?= $m['id'] ?>">
                                                <button type="submit"
                                                    class="text-red-400 hover:text-red-600 transition text-sm"
                                                    title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <!-- BHARATPE ROWS -->
                            <?php foreach ($bharatPeAccounts as $bp): ?>
                                <?php $isActive = ($activeGateway == 'BHARATPE' && $bp['is_active']); ?>
                                <tr class="hover:bg-gray-50 transition <?= $isActive ? 'bg-green-50/50' : '' ?>">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="w-6 h-6 rounded bg-green-100 text-green-600 flex items-center justify-center text-xs"><i
                                                    class="fas fa-qrcode"></i></span>
                                            <span class="font-semibold text-gray-700">BharatPe</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600 font-mono text-xs">
                                        <?= htmlspecialchars($bp['Upiid']) ?><br>
                                        <span class="text-gray-400"><?= htmlspecialchars($bp['merchantId']) ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($isActive): ?>
                                            <span
                                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Active
                                            </span>
                                        <?php else: ?>
                                            <form method="post">
                                                <input type="hidden" name="set_default" value="1">
                                                <input type="hidden" name="default_type" value="BHARATPE">
                                                <input type="hidden" name="default_id" value="<?= $bp['id'] ?>">
                                                <button
                                                    class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-600 px-2 py-1 rounded transition">Set
                                                    Active</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <!-- Edit Button -->
                                            <a href="?edit_type=BHARATPE&edit_id=<?= $bp['id'] ?>"
                                                class="text-blue-500 hover:text-blue-700 transition text-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <!-- Delete Button -->
                                            <form method="post" onsubmit="return confirm('Delete this gateway?');"
                                                class="inline-block">
                                                <input type="hidden" name="delete_gateway" value="1">
                                                <input type="hidden" name="delete_type" value="BHARATPE">
                                                <input type="hidden" name="delete_id" value="<?= $bp['id'] ?>">
                                                <button type="submit"
                                                    class="text-red-400 hover:text-red-600 transition text-sm"
                                                    title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (empty($merchants) && empty($bharatPeAccounts)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-8 text-gray-400">No gateways configured. Add one
                                        from the left.</td>
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
    function toggleForm() {
        const type = document.getElementById('gateway_type').value;
        const paytm = document.getElementById('paytm_fields');
        const bharat = document.getElementById('bharatpe_fields');

        if (type === 'PAYTM') {
            paytm.classList.remove('hidden');
            bharat.classList.add('hidden');
        } else {
            paytm.classList.add('hidden');
            bharat.classList.remove('hidden');
        }
    }
    // Init
    toggleForm();
</script>

<?php require_once 'layout_footer.php'; ?>