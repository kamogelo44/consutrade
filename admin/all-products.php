<?php
/*
 * ConsuTrade - All Products (Admin)
 * Author: Kamogelo Phale
 * 
 * Displays all products on the marketplace for admin management
 */

require_once dirname(__DIR__) . '/init.php';

// Check if admin is logged in using centralized auth
if (!$is_logged_in || $current_user['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$baseUrl = getBaseUrl();
$current_page = 'all-products';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Products - ConsuTrade Admin</title>
    <meta name="author" content="Kamogelo Phale">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/dashboard.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/my-products.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <script>
        var baseUrl = '<?php echo $baseUrl; ?>';
        var currentStatus = 'all';
        var currentSearch = '';
        var currentPage = 1;
    </script>
</head>
<body class="admin-dashboard-page">

<?php include 'includes/sidebar.php'; ?>

<main class="dashboard-main">
    <div class="dashboard-content">
        <div class="dashboard-header">
            <h1>All Products</h1>
            <p>Manage all products on the marketplace</p>
        </div>

        <!-- Filters Bar -->
        <div class="filters-bar" style="margin-bottom: 20px;">
            <div class="status-filters">
                <a href="#" data-status="all" class="filter-btn <?php echo 'all' === 'all' ? 'active' : ''; ?>">All</a>
                <a href="#" data-status="active" class="filter-btn">Active</a>
                <a href="#" data-status="suspended" class="filter-btn">Suspended</a>
            </div>
            <div class="search-bar">
                <input type="text" id="search-products" placeholder="Search products...">
                <button id="search-btn">Search</button>
                <button id="reset-btn" style="display: none;">Reset</button>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="products-grid" id="products-grid">
            <div class="loading-spinner">Loading products...</div>
        </div>
        
        <!-- Pagination -->
        <div class="pagination" id="pagination"></div>
    </div>
</main>

<script src="<?php echo $baseUrl; ?>js/main.js"></script>
<script src="<?php echo $baseUrl; ?>admin/js/dashboard.js"></script>
<script>
/*
 * ConsuTrade - Admin All Products Functionality
 * Author: Kamogelo Phale
 */
$(document).ready(function() {
    loadAllProducts();
    
    // Status filter clicks
    $('.status-filters .filter-btn').on('click', function(e) {
        e.preventDefault();
        $('.status-filters .filter-btn').removeClass('active');
        $(this).addClass('active');
        currentStatus = $(this).data('status');
        currentPage = 1;
        loadAllProducts();
    });
    
    // Search button
    $('#search-btn').on('click', function() {
        currentSearch = $('#search-products').val().trim();
        currentPage = 1;
        loadAllProducts();
        if (currentSearch) {
            $('#reset-btn').show();
        }
    });
    
    // Reset button
    $('#reset-btn').on('click', function() {
        $('#search-products').val('');
        currentSearch = '';
        currentPage = 1;
        loadAllProducts();
        $(this).hide();
    });
    
    // Enter key in search
    $('#search-products').on('keypress', function(e) {
        if (e.which === 13) {
            currentSearch = $(this).val().trim();
            currentPage = 1;
            loadAllProducts();
            if (currentSearch) {
                $('#reset-btn').show();
            }
        }
    });
    
    function loadAllProducts() {
        var $grid = $('#products-grid');
        $grid.html('<div class="loading-spinner">Loading products...</div>');
        
        $.ajax({
            url: baseUrl + 'admin/php/get-all-products.php',
            type: 'GET',
            dataType: 'json',
            data: {
                page: currentPage,
                status: currentStatus,
                search: currentSearch
            },
            success: function(data) {
                if (data.success && data.products && data.products.length) {
                    displayProducts($grid, data.products);
                    displayPagination(data.total_pages, data.current_page);
                } else {
                    $grid.html('<div class="empty-products"><p>No products found.</p></div>');
                    $('#pagination').empty();
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
    
    function displayPagination(totalPages, currentPageNum) {
        var $pagination = $('#pagination');
        if (totalPages <= 1) {
            $pagination.empty();
            return;
        }
        
        var html = '';
        if (currentPageNum > 1) {
            html += '<button class="page-btn" onclick="goToPage(' + (currentPageNum - 1) + ')">← Previous</button>';
        }
        
        for (var i = 1; i <= totalPages; i++) {
            if (i === currentPageNum) {
                html += '<button class="page-btn active" disabled>' + i + '</button>';
            } else if (Math.abs(i - currentPageNum) <= 2 || i === 1 || i === totalPages) {
                html += '<button class="page-btn" onclick="goToPage(' + i + ')">' + i + '</button>';
            } else if (Math.abs(i - currentPageNum) === 3) {
                html += '<span class="page-dots">...</span>';
            }
        }
        
        if (currentPageNum < totalPages) {
            html += '<button class="page-btn" onclick="goToPage(' + (currentPageNum + 1) + ')">Next →</button>';
        }
        
        $pagination.html(html);
    }
    
    window.goToPage = function(page) {
        currentPage = page;
        loadAllProducts();
        $('html, body').animate({ scrollTop: 0 }, 'smooth');
    };
    
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
                        showSuccessToast(data.message || 'Product status updated');
                        loadAllProducts();
                    } else {
                        showErrorToast('Error: ' + data.message);
                    }
                },
                error: function() {
                    showErrorToast('Something went wrong');
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
                        showSuccessToast(data.message || 'Product deleted successfully');
                        loadAllProducts();
                    } else {
                        showErrorToast('Error: ' + data.message);
                    }
                },
                error: function() {
                    showErrorToast('Something went wrong');
                }
            });
        }
    };
});
</script>

</body>
</html>