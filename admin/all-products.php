<?php
/*
 * ConsuTrade - All Products (Admin)
 * Author: Kamogelo Phale
 * 
 * Displays all products on the marketplace for admin management
 * Uses table layout with existing dashboard-layout.css styles
 */

require_once dirname(__DIR__) . '/init.php';

if (!$auth->isAdmin()) {
    header('Location: login.php');
    exit;
}

$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$products = $productRepo->findAll($status, $search, $limit, $offset);
$totalProducts = $productRepo->countForAdmin($status, $search);
$totalPages = ceil($totalProducts / $limit);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Products - ConsuTrade Admin</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/sidebar.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/dashboard-layout.css">
    <style>
        /* ========== PAGE-SPECIFIC STYLES ONLY ========== */
        /* These styles are NOT in dashboard-layout.css */

        /* Admin action buttons - specific to this page */
        .admin-actions {
            display: flex;
            gap: var(--spacing-xs);
            flex-wrap: wrap;
        }

        .admin-action-btn {
            padding: 4px 12px;
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: var(--font-xs);
            font-weight: var(--font-medium);
            transition: all var(--transition-fast);
            white-space: nowrap;
        }

        .admin-action-btn.suspend-btn {
            background: var(--warning-light);
            color: var(--warning);
            border: 1px solid var(--warning);
        }

        .admin-action-btn.suspend-btn:hover {
            background: var(--warning);
            color: white;
        }

        .admin-action-btn.activate-btn {
            background: var(--success-light);
            color: var(--success);
            border: 1px solid var(--success);
        }

        .admin-action-btn.activate-btn:hover {
            background: var(--success);
            color: white;
        }

        .admin-action-btn.delete-btn {
            background: var(--error-light);
            color: var(--error);
            border: 1px solid var(--error);
        }

        .admin-action-btn.delete-btn:hover {
            background: var(--error);
            color: white;
        }

        .admin-action-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .admin-action-btn:disabled:hover {
            transform: none;
        }

        /* Seller avatar in table */
        .seller-cell {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .seller-avatar-small {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
            background: var(--gray-bg);
        }

        .seller-name {
            font-size: var(--font-sm);
        }

        @media (max-width: 768px) {
            .admin-actions {
                flex-direction: column;
            }

            .admin-action-btn {
                width: 100%;
                text-align: center;
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

            .product-thumb {
                width: 40px;
                height: 40px;
            }
        }
    </style>
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <main class="admin-main-content">
        <div class="dashboard-content">
            <div class="page-header">
                <h1>All Products</h1>
                <p>Manage all products on the marketplace</p>
            </div>

            <!-- Filter Bar - Uses dashboard-layout.css styles -->
            <div class="filters-bar">
                <div class="filter-group">
                    <label>Filter by Status:</label>
                    <select id="statusFilter">
                        <option value="all" <?php echo $status == 'all' ? 'selected' : ''; ?>>All Products</option>
                        <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="suspended" <?php echo $status == 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                        <option value="deleted" <?php echo $status == 'deleted' ? 'selected' : ''; ?>>Deleted</option>
                    </select>
                </div>

                <div class="search-group">
                    <input type="text" id="searchInput" placeholder="Search products by name or seller..." value="<?php echo htmlspecialchars($search); ?>">
                    <button id="searchBtn">
                        <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" alt="Search">
                    </button>
                    <button id="resetBtn" class="reset-btn" <?php echo empty($search) ? 'style="display: none;"' : ''; ?>>Reset</button>
                </div>
            </div>

            <!-- Products Table - Uses dashboard-layout.css styles -->
            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" width="64" height="64" alt="No products">
                    <h3>No products found</h3>
                    <p><?php echo !empty($search) || $status !== 'all' ? 'No products match your filters.' : 'No products available on the platform.'; ?></p>
                    <button class="view-all-btn" onclick="resetAdminFilters()">Clear Filters</button>
                </div>
            <?php else: ?>
                <div class="products-table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Seller</th>
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
                                            <span class="product-name"><?php echo htmlspecialchars($product['name']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="seller-cell">
                                            <img src="<?php echo $productRepo->getImageUrl($product['seller_profile_image'] ?? ''); ?>"
                                                class="seller-avatar-small"
                                                onerror="this.src='<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg'">
                                            <span class="seller-name"><?php echo htmlspecialchars($product['seller_name']); ?></span>
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
                                    </td>
                                    <td>
                                        <div class="admin-actions">
                                            <?php if ($product['status'] !== 'deleted'): ?>
                                                <button class="admin-action-btn <?php echo $product['status'] === 'active' ? 'suspend-btn' : 'activate-btn'; ?>"
                                                    onclick="toggleProductStatus(<?php echo $product['id']; ?>, '<?php echo $product['status']; ?>', function() { location.reload(); })">
                                                    <?php echo $product['status'] === 'active' ? 'Suspend' : 'Activate'; ?>
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($product['status'] !== 'deleted'): ?>
                                                <button class="admin-action-btn delete-btn" onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo addslashes(htmlspecialchars($product['name'])); ?>', function() { location.reload(); })">Delete</button>
                                            <?php endif; ?>
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

        function resetAdminFilters() {
            window.location.href = '?status=all&page=1';
        }

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