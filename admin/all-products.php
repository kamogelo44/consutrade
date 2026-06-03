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
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/sidebar.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <style>
        .admin-main-content {
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

        .page-header {
            margin-bottom: var(--spacing-xl);
        }

        .page-header h1 {
            font-size: var(--font-2xl);
            font-weight: var(--font-bold);
            margin-bottom: var(--spacing-xs);
        }

        .page-header p {
            color: var(--gray-medium);
        }

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

        .filter-btn {
            padding: 8px 16px;
            border-radius: var(--radius-md);
            background: var(--white);
            border: 1px solid var(--border-light);
            color: var(--gray-dark);
            cursor: pointer;
            transition: all var(--transition-fast);
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

        .search-bar {
            display: flex;
            gap: var(--spacing-sm);
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
            padding: 8px 16px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: var(--font-medium);
            transition: all var(--transition-fast);
        }

        .search-bar button:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
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
            position: relative;
            width: 100%;
            height: 180px;
            background: var(--gray-bg);
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-active {
            background: var(--success);
            color: white;
        }

        .status-suspended {
            background: var(--warning);
            color: white;
        }

        .product-details {
            padding: var(--spacing-md);
        }

        .product-title {
            font-size: var(--font-base);
            font-weight: var(--font-semibold);
            margin-bottom: 5px;
        }

        .product-price {
            font-size: var(--font-xl);
            font-weight: bold;
            color: var(--primary-color);
            margin: 8px 0;
        }

        .product-seller,
        .product-stock,
        .product-date {
            font-size: var(--font-sm);
            color: var(--gray-medium);
            margin-bottom: 4px;
        }

        .stock-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            margin-top: 5px;
        }

        .stock-badge.out-of-stock {
            background: var(--error-light);
            color: var(--error);
        }

        .stock-badge.low-stock {
            background: var(--warning-light);
            color: var(--warning);
        }

        .product-actions {
            display: flex;
            gap: var(--spacing-sm);
            margin-top: var(--spacing-md);
        }

        .action-btn {
            flex: 1;
            padding: 6px;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-size: var(--font-xs);
            font-weight: var(--font-medium);
            transition: all var(--transition-fast);
        }

        .suspend-btn {
            background: var(--warning-light);
            color: var(--warning);
            border: 1px solid var(--warning);
        }

        .suspend-btn:hover {
            background: var(--warning);
            color: white;
        }

        .activate-btn {
            background: var(--success-light);
            color: var(--success);
            border: 1px solid var(--success);
        }

        .activate-btn:hover {
            background: var(--success);
            color: white;
        }

        .delete-btn {
            background: var(--error-light);
            color: var(--error);
            border: 1px solid var(--error);
        }

        .delete-btn:hover {
            background: var(--error);
            color: white;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: var(--spacing-sm);
            margin-top: var(--spacing-xl);
            flex-wrap: wrap;
        }

        .page-btn {
            padding: 8px 14px;
            border: 1px solid var(--border-light);
            background: var(--white);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all var(--transition-fast);
            font-size: var(--font-sm);
        }

        .page-btn:hover {
            background: var(--primary-fade);
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .page-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            cursor: default;
        }

        .page-dots {
            padding: 8px 4px;
            color: var(--gray-light);
        }

        .loading-spinner {
            text-align: center;
            padding: 40px;
            color: var(--gray-medium);
        }

        .empty-products {
            text-align: center;
            padding: 60px;
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-light);
        }

        @media (max-width: 1024px) {
            .admin-main-content {
                margin-left: 0;
                padding: var(--spacing-md);
                padding-top: 70px;
            }
        }

        @media (max-width: 768px) {
            .filters-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .status-filters {
                justify-content: center;
            }

            .search-bar {
                justify-content: center;
            }

            .search-bar input {
                width: 100%;
            }

            .products-grid {
                grid-template-columns: 1fr;
            }

            .product-actions {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .admin-main-content {
                padding: var(--spacing-sm);
                padding-top: 60px;
            }

            .page-header h1 {
                font-size: var(--font-xl);
            }

            .pagination {
                gap: var(--spacing-xs);
            }

            .page-btn {
                padding: 6px 10px;
                font-size: var(--font-xs);
            }
        }
    </style>
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
                    <button id="searchBtn">Search</button>
                    <button id="resetBtn" style="display: none;">Reset</button>
                </div>
            </div>

            <div class="products-grid" id="productsGrid">
                <div class="loading-spinner">Loading products...</div>
            </div>

            <div class="pagination" id="pagination"></div>
        </div>
    </main>

    <script>
        var $productsGrid = null,
            $pagination = null,
            $filterBtns = null,
            $searchBtn = null,
            $resetBtn = null,
            $searchInput = null,
            currentPage = 1,
            currentStatus = 'all',
            currentSearch = '',
            totalPages = 1;

        function cacheElements() {
            $productsGrid = $('#productsGrid');
            $pagination = $('#pagination');
            $filterBtns = $('.status-filters .filter-btn');
            $searchBtn = $('#searchBtn');
            $resetBtn = $('#resetBtn');
            $searchInput = $('#searchInput');
        }

        function loadProducts() {
            $productsGrid.html('<div class="loading-spinner">Loading products...</div>');
            $.ajax({
                url: baseUrl + 'php/endpoints/get-all-products.php',
                type: 'GET',
                dataType: 'json',
                data: {
                    page: currentPage,
                    status: currentStatus,
                    search: currentSearch
                },
                success: function(data) {
                    if (data.success && data.products && data.products.length) {
                        displayProducts(data.products);
                        totalPages = data.total_pages;
                        displayPagination();
                    } else {
                        $productsGrid.html('<div class="empty-products"><p>No products found.</p></div>');
                        $pagination.empty();
                    }
                },
                error: function() {
                    $productsGrid.html('<div class="empty-products" style="color: var(--error);">Error loading products.</div>');
                }
            });
        }

        function displayProducts(products) {
            $productsGrid.empty();
            $.each(products, function(i, product) {
                var imagePath = product.display_image || product.image;
                if (imagePath && !imagePath.startsWith('http')) {
                    imagePath = baseUrl + imagePath;
                }
                var stockBadge = '';
                if (product.stock_quantity <= 0) {
                    stockBadge = '<span class="stock-badge out-of-stock">Out of Stock</span>';
                } else if (product.stock_quantity <= 5) {
                    stockBadge = '<span class="stock-badge low-stock">Low Stock (' + product.stock_quantity + ')</span>';
                }
                var card = $('<div>').addClass('product-card');
                card.html(
                    '<div class="product-image">' +
                    '<img src="' + (imagePath || baseUrl + 'images/default-product.png') + '" alt="' + escapeHtml(product.name) + '" onerror="this.src=\'' + baseUrl + 'images/default-product.png\'">' +
                    '<div class="product-status-badge status-' + product.status + '">' + product.status + '</div>' +
                    '</div>' +
                    '<div class="product-details">' +
                    '<h3 class="product-title">' + escapeHtml(product.name) + '</h3>' +
                    '<p class="product-price">R ' + parseFloat(product.price).toFixed(2) + '</p>' +
                    '<p class="product-seller">Seller: ' + escapeHtml(product.seller_name) + '</p>' +
                    '<p class="product-stock">Stock: ' + product.stock_quantity + '</p>' +
                    stockBadge +
                    '<p class="product-date">Listed: ' + product.created_at + '</p>' +
                    '<div class="product-actions">' +
                    '<button class="action-btn ' + (product.status === 'active' ? 'suspend-btn' : 'activate-btn') + '" onclick="toggleProductStatus(' + product.id + ', \'' + product.status + '\', function() { loadProducts(); })">' + (product.status === 'active' ? 'Suspend' : 'Activate') + '</button>' +
                    '<button class="action-btn delete-btn" onclick="deleteProduct(' + product.id + ', function() { loadProducts(); })">Delete</button>' +
                    '</div>' +
                    '</div>'
                );
                $productsGrid.append(card);
            });
        }

        function displayPagination() {
            if (totalPages <= 1) {
                $pagination.empty();
                return;
            }
            var html = '';
            if (currentPage > 1) html += '<button class="page-btn" onclick="goToPage(' + (currentPage - 1) + ')">← Previous</button>';
            for (var i = 1; i <= totalPages; i++) {
                if (i === currentPage) {
                    html += '<button class="page-btn active" disabled>' + i + '</button>';
                } else if (Math.abs(i - currentPage) <= 2 || i === 1 || i === totalPages) {
                    html += '<button class="page-btn" onclick="goToPage(' + i + ')">' + i + '</button>';
                } else if (Math.abs(i - currentPage) === 3) {
                    html += '<span class="page-dots">...</span>';
                }
            }
            if (currentPage < totalPages) html += '<button class="page-btn" onclick="goToPage(' + (currentPage + 1) + ')">Next →</button>';
            $pagination.html(html);
        }

        function goToPage(page) {
            currentPage = page;
            loadProducts();
            $('html, body').animate({
                scrollTop: 0
            }, 'smooth');
        }

        $(function() {
            cacheElements();
            loadProducts();
            $filterBtns.on('click', function() {
                $filterBtns.removeClass('active');
                $(this).addClass('active');
                currentStatus = $(this).data('status');
                currentPage = 1;
                loadProducts();
            });
            $searchBtn.on('click', function() {
                currentSearch = $searchInput.val().trim();
                currentPage = 1;
                loadProducts();
                $resetBtn.toggle(!!currentSearch);
            });
            $resetBtn.on('click', function() {
                $searchInput.val('');
                currentSearch = '';
                currentPage = 1;
                loadProducts();
                $(this).hide();
            });
            $searchInput.on('keypress', function(e) {
                if (e.which === 13) $searchBtn.click();
            });
        });
    </script>

</body>

</html>