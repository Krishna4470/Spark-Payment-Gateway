<?php
// dashboard/index.php - Modern Analytics Dashboard
require_once '../includes/dashboard_utils.php';
require_once '../includes/db.php';
checkAuth();

$pdo = getDBConnection();

// --- 1. Fetch Aggregated Stats ---

// Totals
$totalVolume = $pdo->query("SELECT SUM(amount) FROM transactions WHERE status='SUCCESS'")->fetchColumn() ?: 0;
$totalTxns = $pdo->query("SELECT COUNT(*) FROM transactions WHERE status='SUCCESS'")->fetchColumn() ?: 0;
$totalFailed = $pdo->query("SELECT COUNT(*) FROM transactions WHERE status='FAILED'")->fetchColumn() ?: 0;

// Today's Stats
$todayStart = date('Y-m-d 00:00:00');
$todayVolume = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE status='SUCCESS' AND created_at >= ?");
$todayVolume->execute([$todayStart]);
$todayVolume = $todayVolume->fetchColumn() ?: 0;

// Success Rate
$totalAttempts = $totalTxns + $totalFailed + $pdo->query("SELECT COUNT(*) FROM transactions WHERE status='PENDING'")->fetchColumn();
$successRate = $totalAttempts > 0 ? round(($totalTxns / $totalAttempts) * 100, 1) : 0;

// Recent Activity
$recentTxns = $pdo->query("SELECT * FROM transactions ORDER BY id DESC LIMIT 5")->fetchAll();

// --- 2. Fetch Graph Data (Last 7 Days) ---
$dates = [];
$counts = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE status='SUCCESS' AND DATE(created_at) = ?");
    $stmt->execute([$date]);
    $amt = $stmt->fetchColumn() ?: 0;

    $dates[] = date('d M', strtotime($date));
    $counts[] = $amt;
}

require_once 'layout.php';
?>

<div class="max-w-7xl mx-auto space-y-8">

    <!-- Header -->
    <div>
        <h2 class="text-3xl font-bold text-gray-800 tracking-tight">Overview</h2>
        <p class="text-gray-500 text-sm mt-1">Welcome back, Administrator.</p>
    </div>

    <!-- KPI Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

        <!-- Total Volume Card -->
        <div
            class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl p-6 text-white shadow-lg shadow-blue-200 relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-10"><i class="fas fa-wallet text-6xl"></i></div>
            <p class="text-blue-100 text-xs font-bold uppercase tracking-wider mb-1">Total Earnings</p>
            <h3 class="text-3xl font-bold">₹<?= number_format($totalVolume) ?></h3>
            <div class="mt-4 flex items-center text-xs text-blue-100 bg-white/10 w-fit px-2 py-1 rounded">
                <i class="fas fa-chart-line mr-1"></i> Lifetime Volume
            </div>
        </div>

        <!-- Today's Volume Card -->
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-lg relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-green-50 rounded-full -mr-10 -mt-10"></div>
            <p class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-1">Today's Volume</p>
            <h3 class="text-3xl font-bold text-gray-800">₹<?= number_format($todayVolume) ?></h3>
            <p class="text-xs text-green-600 mt-2 font-bold"><i class="fas fa-arrow-up"></i> Collected Today</p>
        </div>

        <!-- Success Rate Card -->
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-lg relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-purple-50 rounded-full -mr-10 -mt-10"></div>
            <p class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-1">Success Rate</p>
            <h3 class="text-3xl font-bold text-gray-800"><?= $successRate ?>%</h3>
            <p class="text-xs text-purple-600 mt-2 font-bold"><i class="fas fa-check-circle"></i> Conversion</p>
        </div>

        <!-- Total Txns Card -->
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-lg relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-orange-50 rounded-full -mr-10 -mt-10"></div>
            <p class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-1">Total Transactions</p>
            <h3 class="text-3xl font-bold text-gray-800"><?= number_format($totalTxns) ?></h3>
            <p class="text-xs text-orange-600 mt-2 font-bold"><i class="fas fa-list-alt"></i> Successful Count</p>
        </div>

    </div>

    <!-- Main Content Area: Chart + Recent -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Chart Section (2/3) -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-xl border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="font-bold text-gray-800 text-lg">Revenue Trends</h3>
                <select class="text-xs bg-gray-50 border border-gray-200 rounded px-2 py-1 outline-none">
                    <option>Last 7 Days</option>
                </select>
            </div>
            <div class="relative h-72 w-full">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <!-- Recent Activity Feed (1/3) -->
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden flex flex-col">
            <div class="p-6 border-b border-gray-50 bg-gray-50/50 flex justify-between items-center">
                <h3 class="font-bold text-gray-800 text-lg">Recent Activity</h3>
                <a href="transactions.php" class="text-xs text-blue-600 font-bold hover:underline">View All</a>
            </div>
            <div class="flex-1 overflow-y-auto p-4 space-y-4">
                <?php if (count($recentTxns) > 0): ?>
                    <?php foreach ($recentTxns as $t): ?>
                        <div
                            class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-xl transition cursor-pointer border border-transparent hover:border-gray-100">
                            <div class="flex items-center gap-3">
                                <?php if ($t['status'] == 'SUCCESS'): ?>
                                    <div
                                        class="w-10 h-10 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-lg">
                                        <i class="fas fa-arrow-down"></i></div>
                                <?php elseif ($t['status'] == 'FAILED'): ?>
                                    <div
                                        class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center text-lg">
                                        <i class="fas fa-times"></i></div>
                                <?php else: ?>
                                    <div
                                        class="w-10 h-10 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center text-lg">
                                        <i class="fas fa-clock"></i></div>
                                <?php endif; ?>

                                <div>
                                    <p class="text-sm font-bold text-gray-800">
                                        <?= $t['customer_id'] ? substr($t['customer_id'], 0, 10) . '...' : 'Guest User' ?>
                                    </p>
                                    <p class="text-xs text-gray-400"><?= date('h:i A', strtotime($t['created_at'])) ?></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p
                                    class="text-sm font-bold <?= $t['status'] == 'FAILED' ? 'text-gray-400 line-through' : 'text-gray-800' ?>">
                                    ₹<?= number_format($t['amount']) ?>
                                </p>
                                <p
                                    class="text-[10px] font-bold <?= $t['status'] == 'SUCCESS' ? 'text-green-500' : ($t['status'] == 'FAILED' ? 'text-red-500' : 'text-yellow-500') ?>">
                                    <?= $t['status'] ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-gray-400 py-8">
                        <i class="fas fa-ghost text-4xl opacity-20 mb-2"></i>
                        <p class="text-sm">No recent transactions</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('revenueChart').getContext('2d');

    // Gradient
    let gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(59, 130, 246, 0.2)');
    gradient.addColorStop(1, 'rgba(59, 130, 246, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($dates) ?>,
            datasets: [{
                label: 'Volume (₹)',
                data: <?= json_encode($counts) ?>,
                borderColor: '#2563eb',
                backgroundColor: gradient,
                borderWidth: 3,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { borderDash: [5, 5], color: '#f3f4f6' },
                    ticks: { font: { family: 'Inter' } }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Inter' } }
                }
            }
        }
    });
</script>

<?php require_once 'layout_footer.php'; ?>