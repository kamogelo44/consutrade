<?php
/*
 * ConsuTrade - All Products (Admin)
 * Author: Kamogelo Phale
 * 
 * Displays all products on the marketplace for admin management
 */

require_once dirname(__DIR__) . '/init.php';

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
        /* Products page specific styles only */

        /* Filters Bar */
        .filters-bar {
            display: flex;
            justify-content: space-between;
            margin-bottom: var(--spacing-lg);
            flex-wrap: wrap;
            gap: var(--spacing-md);
            align-items: center;
        }

        .status-filters {
            display: flex;
            gap: var(--spacing-sm);
            flex-wrap: wrap;
        }

        /* Search Bar with icon button */
        .search-bar {
            display: flex;
            gap: var(--spacing-sm);
            align-items: center;
        }

        .search-bar input {
            padding: 8px 12px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            width: 250px;
            font-size: var(--font-md);
        }

        .search-bar input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 107, 0, 0.1);
        }

        .search-bar button {
            padding: 8px 12px;
            background: var(--primary-color);
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all var(--transition-fast);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .search-bar button img {
            width: 16px;
            height: 16px;
            filter: brightness(0) invert(1);
        }

        .search-bar button:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .reset-search-btn {
            padding: 8px 16px;
            background: var(--gray-bg);
            color: var(--gray-dark);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: var(--font-medium);
            transition: all var(--transition-fast);
        }

        .reset-search-btn:hover {
            background: var(--gray-lighter);
            transform: translateY(-1px);
        }

        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: var(--spacing-xl);
        }

        /* Admin status badge - replaces condition badge on admin view */
        .admin-status-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            padding: 4px 10px;
            border-radius: var(--radius-round);
            font-size: 11px;
            font-weight: var(--font-bold);
            color: white;
            z-index: 2;
        }

        .admin-status-badge.active {
            background: var(--success);
        }

        .admin-status-badge.suspended {
            background: var(--warning);
        }

        /* Admin action buttons */
        .admin-actions {
            display: flex;
            gap: var(--spacing-sm);
            margin-top: 12px;
        }

        .admin-action-btn {
            flex: 1;
            padding: 8px;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-size: var(--font-xs);
            font-weight: var(--font-medium);
            transition: all var(--transition-fast);
            text-align: center;
        }

        .suspend-btn {
            background: var(--warning-light);
            color: var(--warning);
            border: 1px solid var(--warning);
        }

        .suspend-btn:hover {
            background: var(--warning);
            color: white;
            transform: translateY(-2px);
        }

        .activate-btn {
            background: var(--success-light);
            color: var(--success);
            border: 1px solid var(--success);
        }

        .activate-btn:hover {
            background: var(--success);
            color: white;
            transform: translateY(-2px);
        }

        .delete-btn {
            background: var(--error-light);
            color: var(--error);
            border: 1px solid var(--error);
        }

        .delete-btn:hover {
            background: var(--error);
            color: white;
            transform: translateY(-2px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .filters-bar {
                flex-direction: column;
                align-items: stretch;
                gap: var(--spacing-md);
            }

            .status-filters {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: var(--spacing-sm);
            }

            .status-filters .filter-btn {
                padding: 6px 12px;
                font-size: var(--font-xs);
            }

            .search-bar {
                display: flex;
                justify-content: stretch;
                width: 100%;
            }

            .search-bar input {
                flex: 1;
                width: auto;
            }

            .products-grid {
                grid-template-columns: 1fr;
            }

            .admin-actions {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .page-header h1 {
                font-size: var(--font-xl);
            }

            .status-filters {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: var(--spacing-sm);
                width: 100%;
            }

            .status-filters .filter-btn {
                text-align: center;
                width: 100%;
            }

            .prod-name {
                font-size: var(--font-sm);
            }

            .prod-price {
                font-size: var(--font-lg);
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

            <div class="filters-bar">
                <div class="status-filters">
                    <button data-status="all" class="filter-btn active">All</button>
                    <button data-status="active" class="filter-btn">Active</button>
                    <button data-status="suspended" class="filter-btn">Suspended</button>
                </div>
                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Search products by name or seller...">
                    <button id="searchBtn">
                        <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" width="16" height="16" alt="Search">
                    </button>
                    <button id="resetBtn" class="reset-search-btn" style="display: none;">Reset</button>
                </div>
            </div>

            <div class="products-grid" id="productsGrid">
                <div class="loading-spinner">Loading products...</div>
            </div>

            <div class="pagination" id="pagination"></div>
        </div>
    </main>

    <script>
        // Admin Products page state
        var adminProductsGrid = null,
            adminPagination = null,
            adminFilterBtns = null,
            adminSearchBtn = null,
            adminResetBtn = null,
            adminSearchInput = null,
            adminCurrentPage = 1,
            adminCurrentStatus = 'all',
            adminCurrentSearch = '',
            adminTotalPages = 1;

        function cacheAdminElements() {
            adminProductsGrid = $('#productsGrid');
            adminPagination = $('#pagination');
            adminFilterBtns = $('.status-filters .filter-btn');
            adminSearchBtn = $('#searchBtn');
            adminResetBtn = $('#resetBtn');
            adminSearchInput = $('#searchInput');
        }

        function resetAdminFilters() {
            adminSearchInput.val('');
            adminCurrentSearch = '';
            adminCurrentPage = 1;
            adminFilterBtns.removeClass('active');
            adminFilterBtns.filter('[data-status="all"]').addClass('active');
            adminCurrentStatus = 'all';
            loadAdminProducts();
            adminResetBtn.hide();
        }

        function loadAdminProducts() {
            adminProductsGrid.html('<div class="loading-spinner">Loading products...</div>');

            $.ajax({
                url: baseUrl + 'php/endpoints/get-all-products.php',
                type: 'GET',
                dataType: 'json',
                data: {
                    page: adminCurrentPage,
                    status: adminCurrentStatus,
                    search: adminCurrentSearch
                },
                success: function(data) {
                    if (data.success && data.products && data.products.length) {
                        renderAdminProducts(data.products);
                        adminTotalPages = data.total_pages;
                        if (typeof renderPagination === 'function') {
                            renderPagination(adminPagination, adminCurrentPage, adminTotalPages, function(page) {
                                adminCurrentPage = page;
                                loadAdminProducts();
                                $('html, body').animate({
                                    scrollTop: 0
                                }, 'smooth');
                            });
                        } else {
                            adminPagination.empty();
                        }
                    } else {
                        showAdminEmptyState();
                        adminPagination.empty();
                    }
                },
                error: function() {
                    adminProductsGrid.html('<div class="error-cell" style="text-align: center; padding: 60px;">Error loading products. Please refresh the page.</div>');
                }
            });
        }

        function renderAdminProducts(products) {
            adminProductsGrid.empty();

            for (var i = 0; i < products.length; i++) {
                var p = products[i];
                var imagePath = fixImageUrl(p.display_image || p.image);

                var stockBadgeHtml = '';
                if (p.stock_quantity <= 0) {
                    stockBadgeHtml = '<div class="out-of-stock-badge-card">Out of Stock</div>';
                } else if (p.stock_quantity <= 5) {
                    stockBadgeHtml = '<div class="low-stock-badge-card">Only ' + p.stock_quantity + ' left</div>';
                }

                var adminStatusBadge = '<div class="admin-status-badge ' + p.status + '">' + p.status.toUpperCase() + '</div>';

                var sellerAvatar = p.seller_profile_image ?
                    fixImageUrl(p.seller_profile_image, 'images/icons/profile-svgrepo-com.svg') :
                    baseUrl + 'images/icons/profile-svgrepo-com.svg';

                var isSellerVerified = p.seller_is_verified === 1;

                var sellerBadge = isSellerVerified ?
                    '<div class="verified-badge-card"><img src="' + baseUrl + 'images/icons/verified-svgrepo-com.svg" width="14" height="14"><span>Verified Seller</span></div>' :
                    '<div class="unverified-badge-card"><img src="' + baseUrl + 'images/icons/not-verified-svgrepo-com.svg" width="14" height="14"><span>Not Verified</span></div>';

                var sellerLocation = p.seller_location || 'South Africa';

                var $card = $('<div>').addClass('prod-card');
                $card.html(
                    '<div class="img-container">' +
                    '<img src="' + imagePath + '" alt="' + escapeHtml(p.name) + '" onerror="this.src=\'' + baseUrl + 'images/default-product.png\'">' +
                    stockBadgeHtml +
                    adminStatusBadge +
                    '</div>' +
                    '<div class="prod-info-container">' +
                    '<h3 class="prod-name">' + escapeHtml(p.name) + '</h3>' +
                    '<p class="prod-price">R ' + parseFloat(p.price).toFixed(2) + '</p>' +
                    '<div class="seller-info">' +
                    '<div class="seller-avatar">' +
                    '<img src="' + sellerAvatar + '" alt="' + escapeHtml(p.seller_name) + '" onerror="this.src=\'' + baseUrl + 'images/icons/profile-svgrepo-com.svg\'">' +
                    '</div>' +
                    '<div class="seller-details">' +
                    '<p class="seller-name">' + escapeHtml(p.seller_name) + '</p>' +
                    '<p class="location">' +
                    '<img src="' + baseUrl + 'images/icons/pin-location-svgrepo-com.svg" width="10" height="10" alt="location">' +
                    escapeHtml(sellerLocation) +
                    '</p>' +
                    '</div>' +
                    sellerBadge +
                    '</div>' +
                    '<div class="admin-actions">' +
                    '<button class="admin-action-btn ' + (p.status === 'active' ? 'suspend-btn' : 'activate-btn') + '" onclick="toggleProductStatus(' + p.id + ', \'' + p.status + '\', function() { loadAdminProducts(); })">' + (p.status === 'active' ? 'Suspend' : 'Activate') + '</button>' +
                    '<button class="admin-action-btn delete-btn" onclick="deleteProduct(' + p.id + ', function() { loadAdminProducts(); })">Delete</button>' +
                    '</div>' +
                    '</div>'
                );
                adminProductsGrid.append($card);
            }
        }

        function showAdminEmptyState() {
            var resetButtonHtml = '';
            if (adminCurrentSearch !== '' || adminCurrentStatus !== 'all') {
                resetButtonHtml = '<button onclick="resetAdminFilters()" class="view-all-btn" style="background: var(--primary-color); color: white; padding: 10px 24px; border-radius: 8px; border: none; cursor: pointer; margin-top: 16px;">Clear Filters</button>';
            }

            adminProductsGrid.html(
                '<div class="empty-state" style="text-align: center; padding: 60px;">' +
                '<img src="' + baseUrl + 'images/icons/product-catalog-svgrepo-com.svg" width="64" height="64" alt="No products" style="opacity: 0.4;">' +
                '<h3>' + (adminCurrentSearch ? 'No products found' : 'No products available') + '</h3>' +
                '<p>' + (adminCurrentSearch ? 'No products matching "' + escapeHtml(adminCurrentSearch) + '"' : 'No products available on the platform') + '</p>' +
                resetButtonHtml +
                '</div>'
            );
            adminPagination.empty();
        }

        $(function() {
            cacheAdminElements();
            loadAdminProducts();

            adminFilterBtns.on('click', function() {
                adminFilterBtns.removeClass('active');
                $(this).addClass('active');
                adminCurrentStatus = $(this).data('status');
                adminCurrentPage = 1;
                loadAdminProducts();
            });

            adminSearchBtn.on('click', function() {
                adminCurrentSearch = adminSearchInput.val().trim();
                adminCurrentPage = 1;
                loadAdminProducts();
                adminResetBtn.toggle(!!adminCurrentSearch);
            });

            adminResetBtn.on('click', function() {
                resetAdminFilters();
            });

            adminSearchInput.on('keypress', function(e) {
                if (e.which === 13) adminSearchBtn.click();
            });
        });
    </script>

</body>

</html>