<?php
/*
 * ConsuTrade - My Products (Seller)
 * Author: Kamogelo Phale
 * 
 * Displays all products for the logged-in seller with filtering and search.
 * Uses table layout with pagination for efficient product management.
 */

require_once dirname(__DIR__) . '/init.php';
// Check maintenance mode (one line!)
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
        /* These styles are unique to this page and not in any global CSS */

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

        .empty-state .view-all-btn {
            display: inline-block;
            padding: 10px 24px;
            background: var(--primary-color);
            color: var(--white);
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: var(--font-bold);
            transition: all var(--transition-fast);
        }

        .empty-state .view-all-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .products-table-wrapper {
            overflow-x: auto;
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-light);
            box-shadow: var(--shadow-sm);
        }

        .products-table-wrapper table {
            width: 100%;
            min-width: 700px;
            border-collapse: collapse;
        }

        .products-table-wrapper th {
            text-align: left;
            padding: var(--spacing-md);
            background: var(--gray-bg);
            font-weight: var(--font-semibold);
            border-bottom: 2px solid var(--border-light);
            font-size: var(--font-sm);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .products-table-wrapper td {
            padding: var(--spacing-md);
            border-bottom: 1px solid var(--border-light);
            vertical-align: middle;
        }

        .products-table-wrapper tr:hover td {
            background: var(--gray-bg-light);
        }

        .products-table-wrapper tr:last-child td {
            border-bottom: none;
        }

        @media (max-width: 768px) {
            .products-table-wrapper table {
                min-width: 600px;
            }
        }

        @media (max-width: 480px) {
            .products-table-wrapper table {
                min-width: 500px;
            }

            .products-table-wrapper th,
            .products-table-wrapper td {
                padding: var(--spacing-sm);
                font-size: var(--font-xs);
            }

            .page-header h1 {
                font-size: var(--font-xl);
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

            <!-- Action Bar - Uses orders.css classes -->
            <div class="filters-bar">
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
                    <button id="resetBtn" class="reset-btn" <?php echo empty($search) ? 'style="display: none;"' : ''; ?>>Reset</button>
                </div>

                <div class="action-bar-right">
                    <a href="add-product.php" class="add-product-btn">
                        <span>+</span> Add Product
                    </a>
                </div>
            </div>

            <!-- Products Table -->
            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" width="64" height="64" alt="No products">
                    <h3>No products found</h3>
                    <p><?php echo !empty($search) || $status !== 'all' ? 'No products match your filters.' : 'You haven\'t listed any products yet.'; ?></p>
                    <a href="add-product.php" class="view-all-btn">Add Your First Product</a>
                </div>
            <?php else: ?>
                <div class="products-table-wrapper">
                    <table>
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
                                                onerror="this.src='<?php echo $baseUrl; ?>images/default-product.png'">
                                            <span class="product-name"><?php echo htmlspecialchars($product['title']); ?></span>
                                        </div>
                                    </td>
                                    <td class="price-cell">R <?php echo number_format($product['price'], 2); ?></td>
                                    <td>
                                        <span class="stock-cell <?php echo $product['stock_quantity'] <= 0 ? 'stock-low' : ($product['stock_quantity'] <= 5 ? 'stock-medium' : 'stock-high'); ?>">
                                            <?php echo $product['stock_quantity']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $product['status']; ?>">
                                            <?php echo ucfirst($product['status']); ?>
                                        </span>
                                        <?php if (isset($product['suspended_by']) && $product['suspended_by'] == 'admin'): ?>
                                            <div class="admin-suspension-notice">
                                                <img src="<?php echo $baseUrl; ?>images/icons/warning-svgrepo-com.svg" alt="Warning">
                                                <span>Admin suspended</span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="products-action-btn edit">Edit</a>
                                            <?php if ($product['status'] == 'active'): ?>
                                                <button class="products-action-btn suspend" onclick="toggleProductStatus(<?php echo $product['id']; ?>, 'active', function() { location.reload(); })">Suspend</button>
                                            <?php else: ?>
                                                <?php if (isset($product['suspended_by']) && $product['suspended_by'] == 'admin'): ?>
                                                    <button class="products-action-btn activate" disabled title="Suspended by admin. Only an admin can reactivate.">Activate</button>
                                                <?php else: ?>
                                                    <button class="products-action-btn activate" onclick="toggleProductStatus(<?php echo $product['id']; ?>, 'suspended', function() { location.reload(); })">Activate</button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <button class="products-action-btn delete" onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo addslashes(htmlspecialchars($product['title'])); ?>', function() { location.reload(); })">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>" class="page-btn">‹ Prev</a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>"
                                class="page-btn <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>" class="page-btn">Next ›</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="product-count">
                    Showing <?php echo count($products); ?> of <?php echo $totalProducts; ?> product(s)
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        var currentStatus = '<?php echo $status; ?>';
        var currentSearch = '<?php echo addslashes($search); ?>';
        var currentPage = <?php echo $page; ?>;

        $(function() {
            $('#statusFilter').on('change', function() {
                var newStatus = $(this).val();
                var url = '?status=' + newStatus + '&page=1';
                if (currentSearch) {
                    url += '&search=' + encodeURIComponent(currentSearch);
                }
                window.location.href = url;
            });

            $('#searchBtn').on('click', function() {
                var searchTerm = $('#searchInput').val().trim();
                var url = '?status=' + currentStatus + '&page=1';
                if (searchTerm) {
                    url += '&search=' + encodeURIComponent(searchTerm);
                }
                window.location.href = url;
            });

            $('#resetBtn').on('click', function() {
                window.location.href = '?status=' + currentStatus + '&page=1';
            });

            $('#searchInput').on('keypress', function(e) {
                if (e.which === 13) {
                    $('#searchBtn').click();
                }
            });

            $('#searchInput').on('input', function() {
                if ($(this).val().trim() !== '') {
                    $('#resetBtn').show();
                } else {
                    $('#resetBtn').hide();
                }
            });
        });
    </script>
</body>

</html>