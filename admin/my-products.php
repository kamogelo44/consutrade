<?php
/*
 * ConsuTrade - My Products (Seller)
 * Author: Kamogelo Phale
 * 
 * Displays all products for the logged-in seller with filtering and search.
 * Allows sellers to edit, suspend, activate, or delete their products.
 * If a product was suspended by admin, the seller cannot reactivate it.
 */

require_once dirname(__DIR__) . '/init.php';

if (!$auth->isSeller()) {
    header('Location: login.php');
    exit;
}

$seller_id = $currentUser->getUserId();
$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$products = $productRepo->getSellerProducts($seller_id, $status, $search);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>My Products - ConsuTrade Seller</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/sidebar.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/dashboard-layout.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <style>
        /* ========== PAGE-SPECIFIC STYLES ONLY ========== */

        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-xl);
        }

        .add-product-btn {
            background: var(--primary-color);
            color: var(--white);
            padding: 10px 20px;
            border-radius: var(--radius-md);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: var(--font-medium);
            transition: all var(--transition-fast);
        }

        .add-product-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: var(--spacing-lg);
        }

        .product-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-light);
            overflow: hidden;
            transition: all var(--transition-fast);
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }

        .product-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background: var(--gray-bg);
        }

        .product-info {
            padding: var(--spacing-md);
        }

        .product-info h3 {
            font-size: var(--font-base);
            font-weight: var(--font-semibold);
            margin-bottom: 5px;
            color: var(--dark-bg);
        }

        .product-price {
            color: var(--primary-color);
            font-size: var(--font-xl);
            font-weight: bold;
            margin: 8px 0;
        }

        .product-stock {
            font-size: var(--font-sm);
            color: var(--gray-medium);
            margin: 4px 0;
        }

        .product-status {
            font-size: var(--font-sm);
            margin: 4px 0;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: var(--radius-round);
            font-size: var(--font-xs);
            font-weight: var(--font-medium);
        }

        .status-badge.active {
            background: var(--success-light);
            color: var(--success);
        }

        .status-badge.suspended {
            background: var(--warning-light);
            color: var(--warning);
        }

        .btn-group {
            display: flex;
            gap: var(--spacing-sm);
            margin-top: var(--spacing-md);
        }

        .btn-group a,
        .btn-group button {
            flex: 1;
            text-align: center;
            padding: 8px;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-size: var(--font-sm);
            cursor: pointer;
            transition: all var(--transition-fast);
            border: none;
        }

        .btn-edit {
            background: var(--primary-fade);
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }

        .btn-edit:hover {
            background: var(--primary-color);
            color: var(--white);
        }

        .btn-suspend {
            background: var(--warning-light);
            color: var(--warning);
            border: 1px solid var(--warning);
        }

        .btn-suspend:hover {
            background: var(--warning);
            color: var(--white);
        }

        .btn-activate {
            background: var(--success-light);
            color: var(--success);
            border: 1px solid var(--success);
        }

        .btn-activate:hover:not(:disabled) {
            background: var(--success);
            color: var(--white);
        }

        .btn-activate:disabled {
            background: var(--gray-bg-light);
            color: var(--gray-medium);
            border: 1px solid var(--border-light);
            cursor: not-allowed;
            opacity: 0.6;
        }

        .btn-delete {
            background: var(--error-light);
            color: var(--error);
            border: 1px solid var(--error);
        }

        .btn-delete:hover {
            background: var(--error);
            color: var(--white);
        }

        .admin-suspension-notice {
            background: var(--error-light);
            color: var(--error);
            padding: 8px 12px;
            border-radius: var(--radius-md);
            font-size: var(--font-sm);
            margin: 10px 0;
            display: flex;
            align-items: center;
            gap: 8px;
            border-left: 3px solid var(--error);
        }

        .suspension-reason {
            flex: 1;
            word-break: break-word;
        }

        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: var(--spacing-2xl);
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-light);
        }

        .empty-state img {
            opacity: 0.4;
            margin-bottom: var(--spacing-lg);
        }

        .empty-state h3 {
            font-size: var(--font-xl);
            font-weight: var(--font-semibold);
            margin-bottom: var(--spacing-sm);
            color: var(--dark-bg);
        }

        .empty-state p {
            color: var(--gray-medium);
            margin-bottom: var(--spacing-lg);
        }

        @media (max-width: 768px) {
            .action-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .products-grid {
                grid-template-columns: 1fr;
            }

            .btn-group {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .page-header h1 {
                font-size: var(--font-xl);
            }

            .product-info h3 {
                font-size: var(--font-sm);
            }

            .product-price {
                font-size: var(--font-md);
            }
        }
    </style>
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <main class="seller-main-content">
        <div class="dashboard-content">
            <div class="page-header">
                <h1>My Products</h1>
                <p>Manage your product listings</p>
            </div>

            <div class="action-bar">
                <a href="add-product.php" class="add-product-btn">
                    <span>+</span> Add Product
                </a>

                <div class="filter-group">
                    <label>Filter by Status:</label>
                    <select id="statusFilter">
                        <option value="all" <?php echo $status == 'all' ? 'selected' : ''; ?>>All Products</option>
                        <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="suspended" <?php echo $status == 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                </div>

                <div class="search-group">
                    <input type="text" id="searchInput" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                    <button id="searchBtn">
                        <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" alt="Search">
                    </button>
                    <?php if (!empty($search)): ?>
                        <button id="resetBtn" class="reset-btn">Reset</button>
                    <?php else: ?>
                        <button id="resetBtn" class="reset-btn" style="display: none;">Reset</button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="products-grid">
                <?php if (empty($products)): ?>
                    <div class="empty-state">
                        <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" width="64" height="64" alt="No products">
                        <h3>No products found</h3>
                        <p>You haven't listed any products yet.</p>
                        <a href="add-product.php" class="view-all-btn">Add Your First Product</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <img src="<?php echo $productRepo->getImageUrl($product['display_image'] ?? $product['image']); ?>" class="product-image" onerror="this.src='<?php echo $baseUrl; ?>images/default-product.png'">
                            <div class="product-info">
                                <h3><?php echo htmlspecialchars($product['title']); ?></h3>
                                <p class="product-price">R <?php echo number_format($product['price'], 2); ?></p>
                                <p class="product-stock">Stock: <?php echo $product['stock_quantity']; ?></p>
                                <p class="product-status">Status: <span class="status-badge <?php echo $product['status']; ?>"><?php echo ucfirst($product['status']); ?></span></p>

                                <?php if (isset($product['suspended_by']) && $product['suspended_by'] == 'admin'): ?>
                                    <div class="admin-suspension-notice">
                                        <img src="<?php echo $baseUrl; ?>images/icons/warning-svgrepo-com.svg" width="14" height="14" alt="Warning">
                                        <span class="suspension-reason">
                                            <?php if (!empty($product['suspended_reason'])): ?>
                                                Suspended by admin: <?php echo htmlspecialchars($product['suspended_reason']); ?>
                                            <?php else: ?>
                                                Suspended by admin. Only an admin can reactivate.
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                <?php endif; ?>

                                <div class="btn-group">
                                    <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn-edit">Edit</a>
                                    <?php if ($product['status'] == 'active'): ?>
                                        <button class="btn-suspend" onclick="toggleProductStatus(<?php echo $product['id']; ?>, 'active', function() { location.reload(); })">Suspend</button>
                                    <?php else: ?>
                                        <?php if (isset($product['suspended_by']) && $product['suspended_by'] == 'admin'): ?>
                                            <button class="btn-activate" disabled title="This product was suspended by an admin. Only an admin can reactivate it.">Activate</button>
                                        <?php else: ?>
                                            <button class="btn-activate" onclick="toggleProductStatus(<?php echo $product['id']; ?>, 'suspended', function() { location.reload(); })">Activate</button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <button class="btn-delete" onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo addslashes(htmlspecialchars($product['title'])); ?>', function() { location.reload(); })">Delete</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        var currentStatus = '<?php echo $status; ?>';
        var currentSearch = '<?php echo addslashes($search); ?>';

        $(function() {
            $('#statusFilter').on('change', function() {
                var newStatus = $(this).val();
                var url = '?status=' + newStatus;
                if (currentSearch) {
                    url += '&search=' + encodeURIComponent(currentSearch);
                }
                window.location.href = url;
            });

            $('#searchBtn').on('click', function() {
                var searchTerm = $('#searchInput').val().trim();
                var url = '?status=' + currentStatus;
                if (searchTerm) {
                    url += '&search=' + encodeURIComponent(searchTerm);
                }
                window.location.href = url;
            });

            $('#resetBtn').on('click', function() {
                window.location.href = '?status=' + currentStatus;
            });

            $('#searchInput').on('keypress', function(e) {
                if (e.which === 13) {
                    $('#searchBtn').click();
                }
            });
        });
    </script>
</body>

</html>