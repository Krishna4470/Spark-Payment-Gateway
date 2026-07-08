<?php
require_once '../includes/dashboard_utils.php';
checkAuth();

$pdo = getDBConnection();
$error = '';
$success = '';

// Handle Product Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_product') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);

    // File Upload Logic
    if (isset($_FILES['product_file']) && $_FILES['product_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/products/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = time() . '_' . basename($_FILES['product_file']['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['product_file']['tmp_name'], $targetPath)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
            $slug .= '-' . substr(md5(uniqid()), 0, 6); // Ensure uniqueness

            try {
                $stmt = $pdo->prepare("INSERT INTO digital_products (name, description, price, file_path, slug) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $description, $price, $targetPath, $slug]);
                $success = "Product added successfully!";
            } catch (PDOException $e) {
                $error = "Database Error: " . $e->getMessage();
            }
        } else {
            $error = "Failed to upload file.";
        }
    } else {
        $error = "Please select a valid file.";
    }
}

// Handle SMTP Settings Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_smtp') {
    $host = trim($_POST['smtp_host']);
    $user = trim($_POST['smtp_user']);
    $pass = trim($_POST['smtp_pass']);
    $port = trim($_POST['smtp_port']);

    updateSetting('smtp_host', $host);
    updateSetting('smtp_user', $user);
    updateSetting('smtp_pass', $pass);
    updateSetting('smtp_port', $port);

    $success = "SMTP Settings updated successfully!";
}

// Fetch Products
$products = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM digital_products ORDER BY created_at DESC");
    $stmt->execute();
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    // Table might not exist yet if script wasn't run
    $error = "Error fetching products. Ensure database migration is run.";
}

require_once 'layout.php';
?>

<!-- Content -->
<!-- Header with Action -->
<div class="flex justify-between items-center mb-6">
    <h2 class="text-xl font-bold text-gray-800">Sales Dashboard</h2>
    <div class="flex gap-2">
        <button onclick="document.getElementById('emailModal').classList.remove('hidden')"
            class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-900 transition-colors">
            <i class="fas fa-cog mr-2"></i> Configure Email
        </button>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 mb-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4">Add Digital Product</h2>

    <?php if ($error): ?>
        <div class="bg-red-50 text-red-600 p-3 rounded-lg mb-4 text-sm"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="bg-green-50 text-green-600 p-3 rounded-lg mb-4 text-sm"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-4">
        <input type="hidden" name="action" value="add_product">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Product Name</label>
                <input type="text" name="name" required
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 transition-colors">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Price (₹)</label>
                <input type="number" step="0.01" name="price" required
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 transition-colors">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea name="description" rows="3"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 transition-colors"></textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Upload File</label>
            <div class="relative">
                <input type="file" name="product_file" required class="block w-full text-sm text-gray-500
                  file:mr-4 file:py-2 file:px-4
                  file:rounded-full file:border-0
                  file:text-sm file:font-semibold
                  file:bg-blue-50 file:text-blue-700
                  hover:file:bg-blue-100
                "
                    onchange="document.getElementById('fileNameDisplay').innerText = this.files[0] ? this.files[0].name : ''">
            </div>
            <p id="fileNameDisplay" class="text-xs text-gray-500 mt-1 pl-2"></p>
        </div>

        <button type="submit"
            class="bg-blue-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-blue-700 transition-colors">
            Add Product
        </button>
    </form>
</div>

<!-- Products List -->
<div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4">Your Products</h2>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-gray-100 text-sm text-gray-500">
                    <th class="py-3 font-medium">Name</th>
                    <th class="py-3 font-medium">File</th>
                    <th class="py-3 font-medium">Price</th>
                    <th class="py-3 font-medium">Share Link</th>
                    <th class="py-3 font-medium">Date</th>
                </tr>
            </thead>
            <tbody class="text-sm text-gray-700">
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $prod): ?>
                        <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                            <td class="py-3 font-medium"><?= htmlspecialchars($prod['name']) ?></td>
                            <td class="py-3 text-gray-500 text-xs truncate max-w-xs">
                                <i class="fas fa-file mr-1"></i> <?= basename($prod['file_path']) ?>
                            </td>
                            <td class="py-3">₹<?= number_format($prod['price'], 2) ?></td>
                            <td class="py-3">
                                <div class="flex items-center gap-2">
                                    <input type="text" readonly
                                        value="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . dirname(dirname($_SERVER['PHP_SELF'])) . '/product.php?slug=' . $prod['slug'] ?>"
                                        class="bg-gray-100 text-xs px-2 py-1 rounded border border-gray-200 w-48 text-gray-500 select-all">
                                    <button onclick="navigator.clipboard.writeText(this.previousElementSibling.value)"
                                        class="text-blue-600 hover:text-blue-700 text-xs font-medium">Copy</button>
                                </div>
                            </td>
                            <td class="py-3 text-gray-500"><?= date('M d, Y', strtotime($prod['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="py-8 text-center text-gray-400">No products added yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Email Config Modal -->
<div id="emailModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
    aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
            onclick="document.getElementById('emailModal').classList.add('hidden')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div
            class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="post" class="p-6">
                <input type="hidden" name="action" value="save_smtp">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Email Configuration (SMTP)
                    </h3>
                    <button type="button" onclick="document.getElementById('emailModal').classList.add('hidden')"
                        class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">SMTP Host</label>
                        <input type="text" name="smtp_host" value="<?= htmlspecialchars(getSetting('smtp_host')) ?>"
                            placeholder="smtp.gmail.com"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm border p-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">SMTP Port</label>
                        <input type="text" name="smtp_port"
                            value="<?= htmlspecialchars(getSetting('smtp_port') ?: '587') ?>" placeholder="587"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm border p-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">SMTP User/Email</label>
                        <input type="text" name="smtp_user" value="<?= htmlspecialchars(getSetting('smtp_user')) ?>"
                            placeholder="you@gmail.com"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm border p-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">SMTP Password</label>
                        <input type="password" name="smtp_pass" value="<?= htmlspecialchars(getSetting('smtp_pass')) ?>"
                            placeholder="App Password"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm border p-2">
                    </div>
                </div>

                <div class="mt-5 sm:mt-6">
                    <button type="submit"
                        class="inline-flex justify-center w-full rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:text-sm">
                        Save Configuration
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'layout_footer.php'; ?>