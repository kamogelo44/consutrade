<?php
/*
 * ConsuTrade - My Products (Seller)
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__) . '/init.php';
checkMaintenanceMode();

if (!$auth->isSeller()) {
    header('Location: login.php');
    exit;
}

$seller_id = $currentUser->getUserId();
$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$products = $productRepo->findBySeller($seller_id, $status, $search, $limit, $offset);
$totalProducts = $productRepo->countBySeller($seller_id, $status, $search);
$totalPages = ceil($totalProducts / $limit);

$load_dashboard_js = true;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/sidebar.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/dashboard-layout.css">
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <main class="seller-main-content">
        <div class="dashboard-content">
            <div class="page-header">
                <h1>My Products</h1>
                <p>Manage your product listings</p>
            </div>

            <div class="filters-bar">
                <div class="filter-group">
                    <select id="statusFilter">
                        <option value="all" <?php echo $status == 'all' ? 'selected' : ''; ?>>All Products</option>
                        <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="suspended" <?php echo $status == 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                </div>
                <div class="search-group">
                    <input type="text" id="searchInput" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                    <button id="searchBtn">
                        <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" alt="Search" width="16" height="16">
                    </button>
                </div>
                <a href="add-product.php" class="add-product-btn">+ Add Product</a>
            </div>

            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" width="64" height="64" alt="No products">
                    <h3>No products found</h3>
                    <p><?php echo !empty($search) || $status !== 'all' ? 'No products match your filters.' : 'You haven\'t listed any products yet.'; ?></p>
                    <a href="add-product.php" class="view-all-btn">Add Your First Product</a>
                </div>
            <?php else: ?>
                <div class="products-table-wrap">
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <div class="product-cell">
                                            <img src="<?php echo $productRepo->getImageUrl($product['display_image'] ?? $product['image']); ?>"
                                                class="product-thumb"
                                                onerror="this.src='<?php echo $baseUrl; ?>images/default-product.png'"
                                                width="40" height="40" loading="lazy">
                                            <span class="product-name"><?php echo htmlspecialchars($product['title']); ?></span>
                                        </div>
                                    </td>
                                    <td class="price-cell">R <?php echo number_format($product['price'], 2); ?></td>
                                    <td>
                                        <span class="stock-badge <?php echo $product['stock_quantity'] <= 0 ? 'out' : ($product['stock_quantity'] <= 5 ? 'low' : 'good'); ?>">
                                            <?php echo $product['stock_quantity']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $product['status']; ?>">
                                            <?php echo ucfirst($product['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="product-actions">
                                            <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="action-btn edit-btn">Edit</a>
                                            <?php if ($product['status'] == 'active'): ?>
                                                <button class="action-btn suspend-btn" onclick="toggleProductStatus(<?php echo $product['id']; ?>, 'active', function() { location.reload(); })">Suspend</button>
                                            <?php else: ?>
                                                <button class="action-btn activate-btn" onclick="toggleProductStatus(<?php echo $product['id']; ?>, 'suspended', function() { location.reload(); })">Activate</button>
                                            <?php endif; ?>
                                            <button class="action-btn delete-btn" onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo addslashes(htmlspecialchars($product['title'])); ?>', function() { location.reload(); })">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>" class="page-btn">← Prev</a>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>" class="page-btn <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <a href="?status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>" class="page-btn">Next →</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <p class="product-count">Showing <?php echo count($products); ?> of <?php echo $totalProducts; ?> product(s)</p>
            <?php endif; ?>
        </div>
    </main>

    <script src="<?php echo $baseUrl; ?>admin/js/dashboard.js"></script>

    <script>
        $(function() {
            $('#statusFilter').on('change', function() {
                var url = '?status=' + this.value + '&page=1';
                if (currentSearch) url += '&search=' + encodeURIComponent(currentSearch);
                window.location.href = url;
            });

            $('#searchBtn').on('click', function() {
                var term = $('#searchInput').val().trim();
                var url = '?status=' + currentStatus + '&page=1';
                if (term) url += '&search=' + encodeURIComponent(term);
                window.location.href = url;
            });

            $('#searchInput').on('keypress', function(e) {
                if (e.which === 13) $('#searchBtn').click();
            });
        });

        var currentStatus = '<?php echo $status; ?>';
        var currentSearch = '<?php echo addslashes($search); ?>';
    </script>
</body>

</html>