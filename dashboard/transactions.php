<?php
// dashboard/transactions.php
require_once '../includes/dashboard_utils.php';
require_once '../includes/db.php';
checkAuth();

$pdo = getDBConnection();
$currentPageUrl = basename($_SERVER['PHP_SELF']);

// --- Pagination Setup ---
$limit = 15; // Max transactions per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1)
    $page = 1;
$offset = ($page - 1) * $limit;

// --- Filtering Logic ---
$whereClauses = [];
$params = [];

// 1. Search (Order ID or Amount)
if (!empty($_GET['search'])) {
    $search = $_GET['search'];
    $whereClauses[] = "(order_id LIKE ? OR amount LIKE ? OR customer_id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// 2. Status Filter
if (!empty($_GET['status']) && in_array($_GET['status'], ['SUCCESS', 'PENDING', 'FAILED'])) {
    $whereClauses[] = "status = ?";
    $params[] = $_GET['status'];
}

// 3. Date Filter
if (!empty($_GET['date'])) {
    $whereClauses[] = "DATE(created_at) = ?";
    $params[] = $_GET['date'];
}

// Build Count Query (for pagination)
$countSql = "SELECT COUNT(*) FROM transactions";
if (!empty($whereClauses)) {
    $countSql .= " WHERE " . implode(" AND ", $whereClauses);
}
$stmtCount = $pdo->prepare($countSql);
$stmtCount->execute($params);
$totalRecords = $stmtCount->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Build Main Query
$sql = "SELECT * FROM transactions";
if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(" AND ", $whereClauses);
}
$sql .= " ORDER BY id DESC LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

require_once 'layout.php';
?>

<div class="max-w-7xl mx-auto space-y-8">

    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-end gap-4">
        <div>
            <h2 class="text-3xl font-bold text-gray-800 tracking-tight">Transactions</h2>
            <p class="text-gray-500 text-sm mt-1">View and manage your recent payments.</p>
        </div>

        <!-- Filters Toolbar -->
        <form method="get"
            class="flex flex-wrap items-center gap-3 bg-white p-2 rounded-xl shadow-sm border border-gray-100">

            <!-- Search -->
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                    placeholder="Search Order ID..."
                    class="pl-9 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm w-48 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
            </div>

            <!-- Date -->
            <input type="date" name="date" value="<?= htmlspecialchars($_GET['date'] ?? '') ?>"
                class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">

            <!-- Status -->
            <select name="status"
                class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 transition cursor-pointer">
                <option value="">All Status</option>
                <option value="SUCCESS" <?= ($_GET['status'] ?? '') == 'SUCCESS' ? 'selected' : '' ?>>Success</option>
                <option value="PENDING" <?= ($_GET['status'] ?? '') == 'PENDING' ? 'selected' : '' ?>>Pending</option>
                <option value="FAILED" <?= ($_GET['status'] ?? '') == 'FAILED' ? 'selected' : '' ?>>Failed</option>
            </select>

            <!-- Submit -->
            <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-bold transition shadow-md shadow-blue-200">
                Filter
            </button>

            <?php if (!empty($_GET)): ?>
                <a href="transactions.php" class="text-gray-400 hover:text-red-500 px-2 transition" title="Clear Filters">
                    <i class="fas fa-times"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr
                        class="bg-gray-50/50 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500 font-bold">
                        <th class="px-6 py-5">Date & Time</th>
                        <th class="px-6 py-5">Order Details</th>
                        <th class="px-6 py-5">Customer Info</th>
                        <th class="px-6 py-5">Amount</th>
                        <th class="px-6 py-5">Status</th>
                        <th class="px-6 py-5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if (count($transactions) > 0): ?>
                        <?php foreach ($transactions as $t): ?>
                            <tr class="hover:bg-blue-50/30 transition duration-200 group">

                                <!-- Date -->
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold text-gray-700">
                                        <?= date('M d, Y', strtotime($t['created_at'])) ?>
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        <?= date('h:i A', strtotime($t['created_at'])) ?>
                                    </div>
                                </td>

                                <!-- Order ID -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="font-mono text-xs text-blue-600 bg-blue-50 px-2 py-1 rounded border border-blue-100">
                                            <?= $t['order_id'] ?>
                                        </span>
                                        <?php if (!empty($t['paytm_txn_id'])): ?>
                                            <span title="PG Ref: <?= $t['paytm_txn_id'] ?>"
                                                class="text-xs text-gray-400 cursor-help">
                                                <i class="fas fa-info-circle"></i>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Customer -->
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-600">
                                        <?= htmlspecialchars($t['customer_id'] ?: 'Guest') ?>
                                    </div>
                                </td>

                                <!-- Amount -->
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold text-gray-800">
                                        ₹
                                        <?= number_format($t['amount'], 2) ?>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4">
                                    <?php
                                    $statusClasses = [
                                        'SUCCESS' => 'bg-green-100 text-green-700 border-green-200',
                                        'PENDING' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                                        'FAILED' => 'bg-red-50 text-red-700 border-red-200'
                                    ];
                                    $sClass = $statusClasses[$t['status']] ?? 'bg-gray-100 text-gray-600';
                                    $icon = match ($t['status']) {
                                        'SUCCESS' => 'fa-check',
                                        'PENDING' => 'fa-clock',
                                        'FAILED' => 'fa-times',
                                        default => 'fa-question'
                                    };
                                    ?>
                                    <span
                                        class="px-3 py-1 rounded-full text-xs font-bold border flex items-center w-fit gap-1.5 <?= $sClass ?>">
                                        <i class="fas <?= $icon ?>"></i>
                                        <?= $t['status'] ?>
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right">
                                    <button onclick="openTxnModal(<?= htmlspecialchars(json_encode($t)) ?>)"
                                        class="text-gray-400 hover:text-blue-600 p-2 rounded-full hover:bg-blue-50 transition"
                                        title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-400">
                                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                        <i class="fas fa-search text-2xl opacity-50"></i>
                                    </div>
                                    <p class="text-lg font-medium text-gray-500">No transactions found</p>
                                    <p class="text-sm">Try adjusting your filters or search terms.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        <?php if ($totalPages > 1): ?>
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                <span class="text-sm text-gray-500">
                    Showing page <span class="font-bold text-gray-800"><?= $page ?></span> of <span
                        class="font-bold text-gray-800"><?= $totalPages ?></span>
                </span>

                <div class="flex items-center gap-1">
                    <?php
                    // Helper to generate pagination URLs
                    function getPageUrl($pageNum)
                    {
                        $params = $_GET;
                        $params['page'] = $pageNum;
                        return '?' . http_build_query($params);
                    }
                    ?>

                    <!-- Previous Button -->
                    <?php if ($page > 1): ?>
                        <a href="<?= getPageUrl($page - 1) ?>"
                            class="px-3 py-1 rounded-lg border border-gray-200 bg-white text-gray-600 text-sm hover:bg-gray-50 hover:text-blue-600 transition">
                            <i class="fas fa-chevron-left mr-1"></i> Prev
                        </a>
                    <?php else: ?>
                        <span
                            class="px-3 py-1 rounded-lg border border-gray-100 bg-gray-50 text-gray-300 text-sm cursor-not-allowed">
                            <i class="fas fa-chevron-left mr-1"></i> Prev
                        </span>
                    <?php endif; ?>

                    <!-- Numbered Links -->
                    <?php
                    $range = 2; // Number of pages to show around current page
                    for ($i = 1; $i <= $totalPages; $i++):
                        if ($i == 1 || $i == $totalPages || ($i >= $page - $range && $i <= $page + $range)):
                            ?>
                            <a href="<?= getPageUrl($i) ?>" class="w-8 h-8 flex items-center justify-center rounded-lg text-sm font-medium transition
                       <?= $i == $page
                           ? 'bg-blue-600 text-white shadow-md shadow-blue-200'
                           : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 hover:text-blue-600' ?>">
                                <?= $i ?>
                            </a>
                        <?php elseif ($i == $page - $range - 1 || $i == $page + $range + 1): ?>
                            <span class="w-8 h-8 flex items-center justify-center text-gray-400">...</span>
                        <?php endif; endfor; ?>

                    <!-- Next Button -->
                    <?php if ($page < $totalPages): ?>
                        <a href="<?= getPageUrl($page + 1) ?>"
                            class="px-3 py-1 rounded-lg border border-gray-200 bg-white text-gray-600 text-sm hover:bg-gray-50 hover:text-blue-600 transition">
                            Next <i class="fas fa-chevron-right ml-1"></i>
                        </a>
                    <?php else: ?>
                        <span
                            class="px-3 py-1 rounded-lg border border-gray-100 bg-gray-50 text-gray-300 text-sm cursor-not-allowed">
                            Next <i class="fas fa-chevron-right ml-1"></i>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Transaction Detail Modal -->
<div id="txnModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeTxnModal()"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md p-4">
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden transform transition-all">

            <!-- Modal Header -->
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h3 class="text-lg font-bold text-gray-800">Transaction Details</h3>
                <button onclick="closeTxnModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Modal Content -->
            <div class="p-6 space-y-4">

                <!-- Amount Hero -->
                <div class="text-center mb-6">
                    <div class="text-gray-500 text-xs uppercase tracking-wide mb-1">Total Amount</div>
                    <div class="text-3xl font-bold text-gray-900" id="modalAmount">₹0.00</div>
                    <div id="modalStatusBadge"
                        class="inline-block mt-2 px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-600">
                        PENDING
                    </div>
                </div>

                <!-- Grid Details -->
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <div class="text-gray-400 text-xs">Order ID</div>
                        <div class="font-mono text-gray-800 font-medium truncate" id="modalOrderId">-</div>
                    </div>
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <div class="text-gray-400 text-xs">Date</div>
                        <div class="font-medium text-gray-800" id="modalDate">-</div>
                    </div>
                    <div class="col-span-2 bg-gray-50 p-3 rounded-lg">
                        <div class="text-gray-400 text-xs">Payment Gateway (PG) Ref</div>
                        <div class="font-mono text-gray-800 break-all text-xs" id="modalPgRef">-</div>
                    </div>

                    <div class="col-span-2 border-t border-gray-100 pt-4 mt-2">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500">Customer ID</span>
                            <span class="font-medium text-gray-800" id="modalCustId">-</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Footer Actions -->
            <div class="bg-gray-50 px-6 py-4 text-right">
                <button onclick="closeTxnModal()"
                    class="text-sm font-bold text-gray-600 hover:text-gray-800">Close</button>
            </div>

        </div>
    </div>
</div>

<script>
    function openTxnModal(data) {
        // Populate Data
        document.getElementById('modalAmount').innerText = '₹' + parseFloat(data.amount).toFixed(2);
        document.getElementById('modalOrderId').innerText = data.order_id;
        document.getElementById('modalDate').innerText = new Date(data.created_at).toLocaleString();
        document.getElementById('modalCustId').innerText = data.customer_id || 'Guest';
        document.getElementById('modalPgRef').innerText = data.paytm_txn_id || 'Not Available';

        // Status Logic
        const badge = document.getElementById('modalStatusBadge');
        badge.innerText = data.status;
        badge.className = 'inline-block mt-2 px-3 py-1 rounded-full text-xs font-bold ';

        if (data.status === 'SUCCESS') badge.classList.add('bg-green-100', 'text-green-700');
        else if (data.status === 'FAILED') badge.classList.add('bg-red-100', 'text-red-700');
        else badge.classList.add('bg-yellow-50', 'text-yellow-700');

        // Show Modal
        document.getElementById('txnModal').classList.remove('hidden');
    }

    function closeTxnModal() {
        document.getElementById('txnModal').classList.add('hidden');
    }
</script>

<?php require_once 'layout_footer.php'; ?>