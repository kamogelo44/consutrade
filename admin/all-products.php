<?php
/*
 * ConsuTrade - All Products (Admin)
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__) . '/php/helpers.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

$baseUrl = getBaseUrl();

// Get database connection
require_once dirname(__DIR__) . '/php/config.php';

$current_page = 'all-products';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Products - ConsuTrade Admin</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/dashboard.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/my-products.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <script>
        var baseUrl = '<?php echo $baseUrl; ?>';
    </script>
</head>
<body class="admin-dashboard-page">

<?php include 'includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="dashboard-header">
    <h1>All Products</h1>
    <p>Manage all products on the marketplace</p>
</div>

<div class="filters-bar" style="margin-bottom: 20px;">
    <div class="status-filters">
        <a href="?status=all" class="filter-btn">All</a>
        <a href="?status=active" class="filter-btn">Active</a>
        <a href="?status=suspended" class="filter-btn">Suspended</a>
    </div>
    <div class="search-bar">
        <input type="text" id="search-products" placeholder="Search products...">
        <button onclick="searchProducts()">Search</button>
    </div>
</div>

<div class="products-grid" id="products-grid">
    <div class="loading-spinner">Loading products...</div>
</div>

</main>
</div>

<script src="<?php echo $baseUrl; ?>js/main.js"></script>
<script src="<?php echo $baseUrl; ?>admin/js/dashboard.js"></script>
<script>
$(document).ready(function() {
    loadAllProducts();
    
    function loadAllProducts() {
        var $grid = $('#products-grid');
        $grid.html('<div class="loading-spinner">Loading products...</div>');
        
        $.ajax({
            url: baseUrl + 'admin/php/get-all-products.php',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success && data.products && data.products.length) {
                    displayProducts($grid, data.products);
                } else {
                    $grid.html('<div class="empty-products"><p>No products found.</p></div>');
                }
            },
            error: function() {
                $grid.html('<div class="error-message">Error loading products</div>');
            }
        });
    }
    
    function displayProducts($grid, products) {
        $grid.empty();
        
        $.each(products, function(i, product) {
            var imagePath = product.image;
            if (imagePath && !imagePath.startsWith('http') && !imagePath.startsWith('/')) {
                imagePath = baseUrl + imagePath;
            }
            
            var card = $('<div>').addClass('product-card');
            card.html(`
                <div class="product-image">
                    <img src="${imagePath || baseUrl + 'images/default-product.png'}" alt="${escapeHtml(product.name)}">
                    <div class="product-status-badge status-${product.status}">${product.status}</div>
                </div>
                <div class="product-details">
                    <h3 class="product-title">${escapeHtml(product.name)}</h3>
                    <p class="product-price">R ${parseFloat(product.price).toFixed(2)}</p>
                    <p class="product-seller">Seller: ${escapeHtml(product.seller_name)}</p>
                    <p class="product-date">Listed: ${product.created_at}</p>
                    <div class="product-actions">
                        <button class="action-btn suspend-btn" onclick="toggleProductStatus(${product.id}, '${product.status}')">
                            ${product.status === 'active' ? 'Suspend' : 'Activate'}
                        </button>
                        <button class="action-btn delete-btn" onclick="adminDeleteProduct(${product.id})">Delete</button>
                    </div>
                </div>
            `);
            $grid.append(card);
        });
    }
    
    window.toggleProductStatus = function(productId, currentStatus) {
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
                        loadAllProducts();
                    } else {
                        alert('Error: ' + data.message);
                    }
                },
                error: function() {
                    alert('Something went wrong');
                }
            });
        }
    };
    
    window.adminDeleteProduct = function(productId) {
        if (confirm('Are you sure you want to delete this product? This cannot be undone.')) {
            $.ajax({
                url: baseUrl + 'admin/php/delete-product.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ product_id: productId }),
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        loadAllProducts();
                    } else {
                        alert('Error: ' + data.message);
                    }
                },
                error: function() {
                    alert('Something went wrong');
                }
            });
        }
    };
});
</script>
</body>
</html>