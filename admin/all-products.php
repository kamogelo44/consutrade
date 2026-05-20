<?php
/*
 * ConsuTrade - All Products (Admin)
 * Author: Kamogelo Phale
 * 
 * Displays all products on the marketplace for admin management
 */

require_once dirname(__DIR__) . '/init.php';

if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

$baseUrl = getBaseUrl();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Products - ConsuTrade Admin</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/dashboard-clean.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/sidebar-clean.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <script src="<?php echo $baseUrl; ?>js/main.js"></script>
    <script src="<?php echo $baseUrl; ?>admin/js/dashboard.js"></script>
    <script>var baseUrl = '<?php echo $baseUrl; ?>';</script>
    <style>
        /* Page specific styles */
        .filters-bar { display: flex; justify-content: space-between; margin-bottom: var(--spacing-lg); flex-wrap: wrap; gap: var(--spacing-md); align-items: center; }
        .status-filters { display: flex; gap: var(--spacing-sm); flex-wrap: wrap; }
        .filter-btn { padding: 8px 16px; border-radius: var(--radius-md); text-decoration: none; background: var(--white); border: 1px solid var(--border-light); color: var(--gray-dark); cursor: pointer; transition: all var(--transition-fast); }
        .filter-btn:hover { background: var(--primary-fade); border-color: var(--primary-color); color: var(--primary-color); }
        .filter-btn.active { background: var(--primary-color); color: var(--white); border-color: var(--primary-color); }
        .search-bar { display: flex; gap: var(--spacing-sm); }
        .search-bar input { padding: 8px 12px; border: 1px solid var(--border-light); border-radius: var(--radius-md); width: 250px; }
        .search-bar button { padding: 8px 16px; background: var(--primary-color); color: white; border: none; border-radius: var(--radius-md); cursor: pointer; }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: var(--spacing-lg); }
        .product-card { background: var(--white); border-radius: var(--radius-lg); border: 1px solid var(--border-light); overflow: hidden; transition: all var(--transition-fast); }
        .product-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }
        .product-image { position: relative; width: 100%; height: 180px; background: var(--gray-bg); }
        .product-image img { width: 100%; height: 100%; object-fit: cover; }
        .product-status-badge { position: absolute; top: 10px; right: 10px; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .status-active { background: #4caf50; color: white; }
        .status-suspended { background: #ff9800; color: white; }
        .product-details { padding: var(--spacing-md); }
        .product-title { font-size: var(--font-base); font-weight: var(--font-semibold); margin-bottom: 5px; }
        .product-price { font-size: var(--font-xl); font-weight: bold; color: var(--primary-color); margin: 8px 0; }
        .product-seller, .product-stock, .product-date { font-size: var(--font-sm); color: var(--gray-medium); margin-bottom: 4px; }
        .stock-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-top: 5px; }
        .stock-badge.out-of-stock { background: #ffebee; color: #f44336; }
        .stock-badge.low-stock { background: #fff3e0; color: #ff9800; }
        .product-actions { display: flex; gap: var(--spacing-sm); margin-top: var(--spacing-md); }
        .action-btn { flex: 1; padding: 6px; border: none; border-radius: var(--radius-md); cursor: pointer; font-size: var(--font-xs); font-weight: var(--font-medium); transition: all var(--transition-fast); }
        .suspend-btn { background: var(--warning-light); color: var(--warning); border: 1px solid var(--warning); }
        .suspend-btn:hover { background: var(--warning); color: white; }
        .activate-btn { background: var(--success-light); color: var(--success); border: 1px solid var(--success); }
        .activate-btn:hover { background: var(--success); color: white; }
        .delete-btn { background: var(--error-light); color: var(--error); border: 1px solid var(--error); }
        .delete-btn:hover { background: var(--error); color: white; }
        .pagination { display: flex; justify-content: center; gap: var(--spacing-sm); margin-top: var(--spacing-xl); flex-wrap: wrap; }
        .page-btn { padding: 8px 14px; border: 1px solid var(--border-light); background: var(--white); border-radius: var(--radius-md); cursor: pointer; }
        .page-btn.active { background: var(--primary-color); color: white; border-color: var(--primary-color); }
        .loading-spinner { text-align: center; padding: 40px; color: var(--gray-medium); }
        .empty-products { text-align: center; padding: 60px; background: var(--white); border-radius: var(--radius-lg); border: 1px solid var(--border-light); }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .products-grid { gap: var(--spacing-md); }
        }
        @media (max-width: 1024px) {
            .products-grid { grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); }
        }
        @media (max-width: 768px) {
            .filters-bar { flex-direction: column; align-items: stretch; }
            .status-filters { justify-content: center; }
            .search-bar { justify-content: center; }
            .products-grid { grid-template-columns: 1fr; }
            .product-actions { flex-direction: column; }
        }
        @media (max-width: 480px) {
            h1 { font-size: var(--font-xl); }
            .search-bar input { width: 100%; }
            .pagination { gap: var(--spacing-xs); }
            .page-btn { padding: 6px 10px; font-size: var(--font-xs); }
        }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<main class="admin-main-content">
    <div class="dashboard-content">
        <h1 style="margin-bottom: var(--spacing-xs); font-size: var(--font-2xl); font-weight: var(--font-bold)">All Products</h1>
        <p style="margin-bottom: var(--spacing-lg); color: var(--gray-medium);">Manage all products on the marketplace</p>

        <div class="filters-bar">
            <div class="status-filters">
                <button data-status="all" class="filter-btn active">All</button>
                <button data-status="active" class="filter-btn">Active</button>
                <button data-status="suspended" class="filter-btn">Suspended</button>
            </div>
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Search products...">
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
var currentPage = 1, currentStatus = 'all', currentSearch = '', totalPages = 1;

$(function() {
    loadProducts();
    
    $('.status-filters .filter-btn').on('click', function() {
        $('.status-filters .filter-btn').removeClass('active');
        $(this).addClass('active');
        currentStatus = $(this).data('status');
        currentPage = 1;
        loadProducts();
    });
    
    $('#searchBtn').on('click', function() {
        currentSearch = $('#searchInput').val().trim();
        currentPage = 1;
        loadProducts();
        $('#resetBtn').toggle(!!currentSearch);
    });
    
    $('#resetBtn').on('click', function() {
        $('#searchInput').val('');
        currentSearch = '';
        currentPage = 1;
        loadProducts();
        $(this).hide();
    });
    
    $('#searchInput').on('keypress', function(e) {
        if (e.which === 13) $('#searchBtn').click();
    });
});

function loadProducts() {
    $('#productsGrid').html('<div class="loading-spinner">Loading products...</div>');
    
    $.ajax({
        url: baseUrl + 'admin/php/get-all-products.php',
        type: 'GET',
        dataType: 'json',
        data: { page: currentPage, status: currentStatus, search: currentSearch },
        success: function(data) {
            if (data.success && data.products && data.products.length) {
                displayProducts(data.products);
                totalPages = data.total_pages;
                displayPagination();
            } else {
                $('#productsGrid').html('<div class="empty-products"><p>No products found.</p></div>');
                $('#pagination').empty();
            }
        },
        error: function() {
            $('#productsGrid').html('<div class="empty-products" style="color: var(--error);">Error loading products. Please refresh.</div>');
        }
    });
}

function displayProducts(products) {
    var $grid = $('#productsGrid');
    $grid.empty();
    
    $.each(products, function(i, product) {
        var imagePath = product.display_image || product.image;
        if (imagePath && !imagePath.startsWith('http') && !imagePath.startsWith('/')) {
            imagePath = baseUrl + imagePath;
        }
        
        var stockBadge = '';
        if (product.stock_quantity <= 0) {
            stockBadge = '<span class="stock-badge out-of-stock">Out of Stock</span>';
        } else if (product.stock_quantity <= 5) {
            stockBadge = '<span class="stock-badge low-stock">Low Stock (' + product.stock_quantity + ')</span>';
        }
        
        var card = $('<div>').addClass('product-card');
        card.html(`
            <div class="product-image">
                <img src="${imagePath || baseUrl + 'images/default-product.png'}" alt="${escapeHtml(product.name)}" onerror="this.src='${baseUrl}images/default-product.png'">
                <div class="product-status-badge status-${product.status}">${product.status}</div>
            </div>
            <div class="product-details">
                <h3 class="product-title">${escapeHtml(product.name)}</h3>
                <p class="product-price">R ${parseFloat(product.price).toFixed(2)}</p>
                <p class="product-seller">Seller: ${escapeHtml(product.seller_name)}</p>
                <p class="product-stock">Stock: ${product.stock_quantity}</p>
                ${stockBadge}
                <p class="product-date">Listed: ${product.created_at}</p>
                <div class="product-actions">
                    <button class="action-btn ${product.status === 'active' ? 'suspend-btn' : 'activate-btn'}" onclick="toggleProductStatus(${product.id}, '${product.status}')">
                        ${product.status === 'active' ? 'Suspend' : 'Activate'}
                    </button>
                    <button class="action-btn delete-btn" onclick="adminDeleteProduct(${product.id})">Delete</button>
                </div>
            </div>
        `);
        $grid.append(card);
    });
}

function displayPagination() {
    if (totalPages <= 1) { $('#pagination').empty(); return; }
    
    var html = '';
    if (currentPage > 1) html += `<button class="page-btn" onclick="goToPage(${currentPage - 1})">← Previous</button>`;
    
    for (var i = 1; i <= totalPages; i++) {
        if (i === currentPage) {
            html += `<button class="page-btn active" disabled>${i}</button>`;
        } else if (Math.abs(i - currentPage) <= 2 || i === 1 || i === totalPages) {
            html += `<button class="page-btn" onclick="goToPage(${i})">${i}</button>`;
        } else if (Math.abs(i - currentPage) === 3) {
            html += `<span class="page-dots">...</span>`;
        }
    }
    
    if (currentPage < totalPages) html += `<button class="page-btn" onclick="goToPage(${currentPage + 1})">Next →</button>`;
    $('#pagination').html(html);
}

function goToPage(page) { currentPage = page; loadProducts(); $('html, body').animate({ scrollTop: 0 }, 'smooth'); }

function toggleProductStatus(productId, currentStatus) {
    var newStatus = currentStatus === 'active' ? 'suspended' : 'active';
    if (confirm('Are you sure you want to ' + newStatus + ' this product?')) {
        $.ajax({
            url: baseUrl + 'admin/php/update-product-status.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ product_id: productId, status: newStatus }),
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    showSuccessToast(data.message || 'Product status updated');
                    loadProducts();
                } else {
                    showErrorToast('Error: ' + data.message);
                }
            },
            error: function() { showErrorToast('Something went wrong'); }
        });
    }
}

function adminDeleteProduct(productId) {
    if (confirm('Are you sure you want to delete this product? This cannot be undone.')) {
        $.ajax({
            url: baseUrl + 'admin/php/delete-product.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ product_id: productId }),
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    showSuccessToast(data.message || 'Product deleted successfully');
                    loadProducts();
                } else {
                    showErrorToast('Error: ' + data.message);
                }
            },
            error: function() { showErrorToast('Something went wrong'); }
        });
    }
}
</script>

</body>
</html>