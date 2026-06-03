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
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/sidebar.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <style>
        /* ========== SELLER DASHBOARD LAYOUT ========== */

        /**
         * Main content area for seller dashboard
         * Offset by sidebar width (280px) on desktop
         */
        .seller-main-content {
            margin-left: 280px;
            padding: var(--spacing-xl);
            min-height: 100vh;
            background: var(--gray-bg);
            transition: margin-left var(--transition-normal);
        }

        .dashboard-content {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* ========== PAGE HEADER ========== */

        .page-header {
            margin-bottom: var(--spacing-xl);
        }

        .page-header h1 {
            font-size: var(--font-2xl);
            font-weight: var(--font-bold);
            margin-bottom: var(--spacing-xs);
            color: var(--dark-bg);
        }

        .page-header p {
            color: var(--gray-medium);
        }

        /* ========== FLASH MESSAGES ========== */

        .flash-message,
        .error-message {
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
            text-align: center;
        }

        .flash-message {
            background: var(--success-light);
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .error-message {
            background: var(--error-light);
            color: var(--error);
            border-left: 4px solid var(--error);
        }

        /* ========== ACTION BAR (Add Product, Filters, Search) ========== */

        .action-bar {
            display: flex;
            justify-content: space-between;
            margin-bottom: var(--spacing-lg);
            flex-wrap: wrap;
            gap: var(--spacing-md);
            align-items: center;
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

        .add-product-btn span {
            font-size: 18px;
        }

        /* ========== FILTER BUTTONS ========== */

        .filter-buttons {
            display: flex;
            gap: var(--spacing-sm);
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 16px;
            border-radius: var(--radius-md);
            text-decoration: none;
            color: var(--gray-dark);
            background: var(--white);
            border: 1px solid var(--border-light);
            transition: all var(--transition-fast);
            display: inline-block;
            font-size: var(--font-sm);
        }

        .filter-btn:hover {
            background: var(--primary-fade);
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .filter-btn.active {
            background: var(--primary-color);
            color: var(--white);
            border-color: var(--primary-color);
        }

        /* ========== SEARCH FORM ========== */

        .search-form {
            display: flex;
            gap: var(--spacing-sm);
        }

        .search-form input {
            padding: 8px 12px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-light);
            width: 200px;
            font-size: var(--font-sm);
        }

        .search-form input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .search-form button {
            padding: 8px 12px;
            background: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .search-form button img {
            width: 16px;
            height: 16px;
            filter: brightness(0) invert(1);
        }

        .clear-search {
            padding: 8px 16px;
            background: var(--gray-bg-light);
            border-radius: var(--radius-md);
            text-decoration: none;
            color: var(--gray-dark);
            font-size: var(--font-sm);
        }

        /* ========== PRODUCTS GRID ========== */

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

        /* ========== PRODUCT INFORMATION ========== */

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

        .status-active {
            background: var(--success-light);
            color: var(--success);
        }

        .status-suspended {
            background: var(--warning-light);
            color: var(--warning);
        }

        /* ========== ACTION BUTTON GROUP ========== */

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

        .btn-activate:hover {
            background: var(--success);
            color: var(--white);
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

        /* ========== EMPTY STATE (Full Width) ========== */

        .empty-state {
            grid-column: 1 / -1;
        }

        /* ========== ADMIN SUSPENSION NOTICE ========== */

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

        .admin-suspension-notice img {
            flex-shrink: 0;
        }

        .suspension-reason {
            flex: 1;
            word-break: break-word;
        }

        /* ========== DISABLED BUTTON STATE ========== */

        .btn-activate:disabled {
            background: var(--gray-bg-light);
            color: var(--gray-medium);
            border: 1px solid var(--border-light);
            cursor: not-allowed;
            opacity: 0.6;
        }

        /* ========== RESPONSIVE STYLES ========== */

        @media (max-width: 1024px) {
            .seller-main-content {
                margin-left: 0;
                width: 100%;
                padding: var(--spacing-md);
                padding-top: 70px;
            }
        }

        @media (max-width: 768px) {
            .seller-main-content {
                padding: var(--spacing-md);
                padding-top: 70px;
            }

            .action-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-buttons {
                justify-content: center;
            }

            .search-form {
                width: 100%;
            }

            .search-form input {
                flex: 1;
            }

            .products-grid {
                grid-template-columns: 1fr;
            }

            .btn-group {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .seller-main-content {
                padding: var(--spacing-sm);
                padding-top: 60px;
            }

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

            <?php if (isset($_SESSION['flash'])): ?>
                <div class="flash-message"><?php echo $_SESSION['flash'];
                                            unset($_SESSION['flash']); ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-message"><?php echo $_SESSION['error'];
                                            unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <div class="action-bar">
                <a href="add-product.php" class="add-product-btn"><span>+</span> Add Product</a>
                <div class="filter-buttons">
                    <a href="?status=all" class="filter-btn <?php echo $status == 'all' ? 'active' : ''; ?>">All</a>
                    <a href="?status=active" class="filter-btn <?php echo $status == 'active' ? 'active' : ''; ?>">Active</a>
                    <a href="?status=suspended" class="filter-btn <?php echo $status == 'suspended' ? 'active' : ''; ?>">Suspended</a>
                </div>
                <form method="GET" class="search-form">
                    <input type="hidden" name="status" value="<?php echo $status; ?>">
                    <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit">
                        <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" width="16" height="16" alt="Search">
                    </button>
                    <?php if (!empty($search)): ?>
                        <a href="?status=<?php echo $status; ?>" class="clear-search">Clear</a>
                    <?php endif; ?>
                </form>
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
                            <img src="<?php echo $productRepo->getProductImageUrl($product['display_image'] ?? $product['image']); ?>" class="product-image" onerror="this.src='<?php echo $baseUrl; ?>images/default-product.png'">
                            <div class="product-info">
                                <h3><?php echo htmlspecialchars($product['title']); ?></h3>
                                <p class="product-price">R <?php echo number_format($product['price'], 2); ?></p>
                                <p class="product-stock">Stock: <?php echo $product['stock_quantity']; ?></p>
                                <p class="product-status">Status: <span class="status-badge status-<?php echo $product['status']; ?>"><?php echo ucfirst($product['status']); ?></span></p>

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
                                            <button class="btn-activate" disabled>Activate</button>
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
</body>

</html>