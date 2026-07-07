<?php
/*
 * ConsuTrade - All Products (Admin)
 * Author: Kamogelo Phale
 * 
 * Displays all products on the marketplace for admin management
 * Uses AJAX for loading, filtering, and pagination.
 */

require_once dirname(__DIR__) . '/init.php';
// Check maintenance mode (one line!)
checkMaintenanceMode();

if (!$auth->isAdmin()) {
    header('Location: login.php');
    exit;
}
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
        /* These are NOT in dashboard-layout.css */

        /* Admin action buttons - unique to this page */
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

        /* Seller cell - unique to admin product listing */
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

        /* Loading/error states */
        .loading-cell,
        .error-cell {
            text-align: center;
            padding: var(--spacing-2xl) !important;
            color: var(--gray-medium);
        }

        .error-cell {
            color: var(--error);
        }

        @media (max-width: 768px) {
            .admin-actions {
                flex-direction: column;
            }

            .admin-action-btn {
                width: 100%;
                text-align: center;
            }

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

            .seller-avatar-small {
                width: 24px;
                height: 24px;
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

            <!-- Filter Bar - Uses dashboard-layout.css -->
            <div class="filters-bar">
                <div class="filter-group">
                    <label>Filter by Status:</label>
                    <select id="statusFilter">
                        <option value="all">All Products</option>
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                        <option value="deleted">Deleted</option>
                    </select>
                </div>

                <div class="search-group">
                    <input type="text" id="searchInput" placeholder="Search products by name or seller...">
                    <button id="searchBtn">
                        <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" alt="Search">
                    </button>
                    <button id="resetBtn" class="reset-btn" style="display: none;">Reset</button>
                </div>
            </div>

            <!-- Products Table -->
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
                    <tbody id="productsTable">
                        <tr>
                            <td colspan="6" class="loading-cell">Loading products...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pagination" id="pagination"></div>

            <!-- Product Count -->
            <div class="product-count" id="productCount"></div>
        </div>
    </main>

    <script>
        // ============================================================
        // PAGE STATE
        // ============================================================
        var currentPage = 1;
        var currentStatus = 'all';
        var currentSearch = '';
        var totalPages = 1;

        // ============================================================
        // DOM CACHE
        // ============================================================
        var $productsTable = $('#productsTable');
        var $pagination = $('#pagination');
        var $productCount = $('#productCount');
        var $statusFilter = $('#statusFilter');
        var $searchInput = $('#searchInput');
        var $searchBtn = $('#searchBtn');
        var $resetBtn = $('#resetBtn');

        // ============================================================
        // LOAD PRODUCTS
        // ============================================================
        function loadProducts() {
            $productsTable.html('<tr><td colspan="6" class="loading-cell">Loading products...</td></tr>');

            $.ajax({
                url: baseUrl + 'php/endpoints/products/get-all-products.php',
                type: 'GET',
                dataType: 'json',
                data: {
                    page: currentPage,
                    status: currentStatus,
                    search: currentSearch
                },
                timeout: 10000,
                success: function(data) {
                    if (data.success && data.products && data.products.length) {
                        renderProducts(data.products);
                        totalPages = data.total_pages || 1;
                        renderPagination();
                        updateProductCount(data.products.length, data.total_products || 0);
                    } else {
                        showEmptyState();
                        $pagination.empty();
                        $productCount.text('');
                    }
                },
                error: function(xhr, status) {
                    console.warn('Failed to load products:', status);
                    $productsTable.html('<tr><td colspan="6" class="error-cell">Error loading products. Please refresh the page.</td></tr>');
                    $pagination.empty();
                    $productCount.text('');
                }
            });
        }

        // ============================================================
        // RENDER PRODUCTS
        // ============================================================
        function renderProducts(products) {
            $productsTable.empty();

            for (var i = 0; i < products.length; i++) {
                var p = products[i];

                // Product image
                var imagePath = fixImageUrl(p.display_image || p.image);
                var productThumb = '<img src="' + imagePath + '" class="product-thumb" onerror="this.src=\'' + baseUrl + 'images/default-product.png\'">';

                // Seller avatar
                var sellerImage = p.seller_profile_image || '';
                var sellerImageUrl = sellerImage ? fixImageUrl(sellerImage) : baseUrl + 'images/icons/profile-svgrepo-com.svg';

                // Stock badge
                var stockClass = 'stock-high';
                if (p.stock_quantity <= 0) stockClass = 'stock-low';
                else if (p.stock_quantity <= 5) stockClass = 'stock-medium';

                // Status badge
                var statusClass = p.status;
                var statusLabel = p.status.charAt(0).toUpperCase() + p.status.slice(1);

                // Action buttons
                var actionsHtml = '<div class="admin-actions">';
                if (p.status !== 'deleted') {
                    var actionClass = p.status === 'active' ? 'suspend-btn' : 'activate-btn';
                    var actionLabel = p.status === 'active' ? 'Suspend' : 'Activate';
                    actionsHtml += '<button class="admin-action-btn ' + actionClass + '" onclick="toggleProductStatus(' + p.id + ', \'' + p.status + '\', function() { loadProducts(); })">' + actionLabel + '</button>';
                    actionsHtml += '<button class="admin-action-btn delete-btn" onclick="deleteProduct(' + p.id + ', function() { loadProducts(); })">Delete</button>';
                }
                actionsHtml += '</div>';

                var row = '<tr>' +
                    '<td><div class="product-cell">' + productThumb + '<span class="product-name">' + escapeHtml(p.name) + '</span></div></td>' +
                    '<td><div class="seller-cell"><img src="' + sellerImageUrl + '" class="seller-avatar-small" onerror="this.src=\'' + baseUrl + 'images/icons/profile-svgrepo-com.svg\'"><span class="seller-name">' + escapeHtml(p.seller_name) + '</span></div></td>' +
                    '<td class="price-cell">R ' + parseFloat(p.price).toFixed(2) + '</td>' +
                    '<td><span class="stock-cell ' + stockClass + '">' + p.stock_quantity + '</span></td>' +
                    '<td><span class="status-badge ' + statusClass + '">' + statusLabel + '</span></td>' +
                    '<td>' + actionsHtml + '</td>' +
                    '</tr>';

                $productsTable.append(row);
            }
        }

        // ============================================================
        // EMPTY STATE
        // ============================================================
        function showEmptyState() {
            var title = currentSearch ? 'No products found' : 'No products available';
            var message = currentSearch ? 'No products matching "' + escapeHtml(currentSearch) + '"' : 'No products available on the platform.';

            var resetHtml = '';
            if (currentSearch !== '' || currentStatus !== 'all') {
                resetHtml = '<button class="view-all-btn" onclick="resetFilters()">Clear Filters</button>';
            }

            $productsTable.html(
                '<tr><td colspan="6" style="text-align: center; padding: 60px;">' +
                '<div class="empty-state">' +
                '<img src="' + baseUrl + 'images/icons/product-catalog-svgrepo-com.svg" width="64" height="64" alt="No products" style="opacity: 0.4;">' +
                '<h3>' + title + '</h3>' +
                '<p>' + message + '</p>' +
                resetHtml +
                '</div>' +
                '</td></tr>'
            );
        }

        // ============================================================
        // PAGINATION
        // ============================================================
        function renderPagination() {
            if (totalPages <= 1) {
                $pagination.empty();
                return;
            }

            var html = '';
            if (currentPage > 1) {
                html += '<button class="page-btn" data-page="' + (currentPage - 1) + '">‹ Prev</button>';
            }

            for (var i = 1; i <= totalPages; i++) {
                if (i === currentPage) {
                    html += '<button class="page-btn active" disabled>' + i + '</button>';
                } else if (Math.abs(i - currentPage) <= 2 || i === 1 || i === totalPages) {
                    html += '<button class="page-btn" data-page="' + i + '">' + i + '</button>';
                } else if (Math.abs(i - currentPage) === 3) {
                    html += '<span class="page-dots">...</span>';
                }
            }

            if (currentPage < totalPages) {
                html += '<button class="page-btn" data-page="' + (currentPage + 1) + '">Next ›</button>';
            }

            $pagination.html(html);
            $pagination.find('.page-btn[data-page]').off('click').on('click', function() {
                var page = parseInt($(this).data('page'));
                if (!isNaN(page)) {
                    currentPage = page;
                    loadProducts();
                    $('html, body').animate({
                        scrollTop: 0
                    }, 'smooth');
                }
            });
        }

        // ============================================================
        // PRODUCT COUNT
        // ============================================================
        function updateProductCount(shown, total) {
            $productCount.text('Showing ' + shown + ' of ' + total + ' product(s)');
        }

        // ============================================================
        // RESET FILTERS
        // ============================================================
        function resetFilters() {
            $statusFilter.val('all');
            $searchInput.val('');
            currentStatus = 'all';
            currentSearch = '';
            currentPage = 1;
            $resetBtn.hide();
            loadProducts();
        }

        // ============================================================
        // EVENT LISTENERS
        // ============================================================
        $(function() {
            // Status filter
            $statusFilter.on('change', function() {
                currentStatus = $(this).val();
                currentPage = 1;
                loadProducts();
            });

            // Search
            $searchBtn.on('click', function() {
                currentSearch = $searchInput.val().trim();
                currentPage = 1;
                loadProducts();
                $resetBtn.toggle(!!currentSearch);
            });

            $searchInput.on('keypress', function(e) {
                if (e.which === 13) {
                    $searchBtn.click();
                }
            });

            $searchInput.on('input', function() {
                if ($(this).val().trim() !== '') {
                    $resetBtn.show();
                } else {
                    $resetBtn.hide();
                }
            });

            // Reset
            $resetBtn.on('click', resetFilters);

            // Load initial data
            loadProducts();
        });
    </script>

</body>

</html>