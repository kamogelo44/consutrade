<?php
/*
 * ConsuTrade - My Products (Seller)
 * Author: Kamogelo Phale
 */

session_start();

$baseUrl = "/www/consutrade/";

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'seller') {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

require_once dirname(__DIR__) . '/php/config.php';

$seller_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';
$current_page = 'products';

// Handle status update (activate/suspend)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    $check_sql = "SELECT product_id, status FROM products WHERE product_id = ? AND seller_id = ? AND status != 'deleted'";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('ii', $product_id, $seller_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $product_data = $check_result->fetch_assoc();
        $new_status = ($action === 'activate') ? 'active' : 'suspended';
        
        if (($action === 'activate' && $product_data['status'] === 'active') ||
            ($action === 'suspend' && $product_data['status'] === 'suspended')) {
            $error_message = ($action === 'activate') ? 'Product is already active.' : 'Product is already suspended.';
        } else {
            $status_sql = "UPDATE products SET status = ? WHERE product_id = ? AND seller_id = ?";
            $status_stmt = $conn->prepare($status_sql);
            $status_stmt->bind_param('sii', $new_status, $product_id, $seller_id);
            
            if ($status_stmt->execute()) {
                $success_message = ($action === 'activate') ? 'Product activated successfully!' : 'Product suspended successfully!';
            } else {
                $error_message = 'Failed to update product status.';
            }
            $status_stmt->close();
        }
    } else {
        $error_message = 'Product not found or does not belong to you.';
    }
    $check_stmt->close();
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products - ConsuTrade Seller</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/seller-dashboard.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/my-products.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
</head>
<body class="my-products-page seller-dashboard-page">

<?php include 'includes/seller-sidebar.php'; ?>

        <div class="products-container">
            <div class="page-header">
                <h1>My Products</h1>
                <p>Manage all your product listings</p>
            </div>

            <?php if ($success_message): ?>
                <div class="success-message"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="action-bar">
                <a href="add-product.php" class="add-product-btn">
                    <img src="<?php echo $baseUrl; ?>images/icons/add-svgrepo-com.svg" alt="Add">
                    Add New Product
                </a>
                
                <div class="filters">
                    <div class="status-filters">
                        <a href="?status=all<?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>" class="filter-btn <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All</a>
                        <a href="?status=active<?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>" class="filter-btn <?php echo $status_filter === 'active' ? 'active' : ''; ?>">Active</a>
                        <a href="?status=suspended<?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>" class="filter-btn <?php echo $status_filter === 'suspended' ? 'active' : ''; ?>">Suspended</a>
                    </div>
                    
                    <div class="search-bar">
                        <form method="GET" action="" id="filter-form">
                            <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                            <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search_term); ?>">
                            <button type="submit">
                                <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" width="16px" height="16px" alt="Search">
                            </button>
                            <?php if (!empty($search_term)): ?>
                                <a href="?status=<?php echo $status_filter; ?>" class="clear-search">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <div class="products-grid" id="products-grid">
                <div class="loading-spinner">Loading products...</div>
            </div>
        </div>
    </main>
</div>

<script src="<?php echo $baseUrl; ?>js/main.js"></script>
<script src="<?php echo $baseUrl; ?>admin/js/seller-dashboard.js"></script>
<script>
var baseUrl = '<?php echo $baseUrl; ?>';
var sellerId = <?php echo $seller_id; ?>;
var statusFilter = '<?php echo $status_filter; ?>';
var searchTerm = '<?php echo addslashes($search_term); ?>';

// ========== GLOBAL FUNCTIONS (accessible from onclick) ==========
function confirmDeleteProduct(productId) {
    if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
        var $card = $('.product-card[data-product-id="' + productId + '"]');
        $card.css('opacity', '0.5');
        
        $.ajax({
            url: baseUrl + 'php/delete-product.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ product_id: productId }),
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    $card.fadeOut(300, function() {
                        $(this).remove();
                        if ($('.product-card').length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    alert('Error: ' + data.message);
                    $card.css('opacity', '1');
                }
            },
            error: function() {
                alert('Something went wrong. Please try again.');
                $card.css('opacity', '1');
            }
        });
    }
}

// ========== DOM READY - Initialize page ==========
$(document).ready(function() {
    loadProducts();
    
    function loadProducts() {
        var $grid = $('#products-grid');
        $grid.html('<div class="loading-spinner">Loading products...</div>');
        
        $.ajax({
            url: baseUrl + 'php/get-seller-products.php?seller_id=' + sellerId,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success && data.products && data.products.length > 0) {
                    var filteredProducts = data.products.filter(function(product) {
                        var matchesStatus = (statusFilter === 'all') || (product.status === statusFilter);
                        var matchesSearch = !searchTerm || 
                            product.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                            product.id.toString().includes(searchTerm);
                        return matchesStatus && matchesSearch;
                    });
                    
                    if (filteredProducts.length > 0) {
                        displayProducts(filteredProducts);
                    } else {
                        $grid.html(`
                            <div class="empty-products">
                                <img src="${baseUrl}images/icons/product-catalog-svgrepo-com.svg" alt="No products">
                                <h3>No Products Found</h3>
                                <p>No products match your current filters.</p>
                                <a href="?status=all" class="clear-btn">Clear Filters</a>
                            </div>
                        `);
                    }
                } else {
                    $grid.html(`
                        <div class="empty-products">
                            <img src="${baseUrl}images/icons/product-catalog-svgrepo-com.svg" alt="No products">
                            <h3>No Products Found</h3>
                            <p>You haven't listed any products yet.</p>
                            <a href="add-product.php" class="add-btn">Add Your First Product</a>
                        </div>
                    `);
                }
            },
            error: function() {
                $('#products-grid').html('<div class="error-message">Error loading products. Please refresh the page.</div>');
            }
        });
    }
    
    function displayProducts(products) {
        var $grid = $('#products-grid');
        $grid.empty();
        
        $.each(products, function(index, product) {
            var imagePath = product.image;
            if (imagePath && !imagePath.startsWith('http') && !imagePath.startsWith('/')) {
                imagePath = baseUrl + imagePath;
            }
            
            // Use escapeHtml from main.js (global)
            var card = $('<div>').addClass('product-card').attr('data-product-id', product.id);
            card.html(`
                <div class="product-image">
                    <img src="${imagePath}" alt="${escapeHtml(product.name)}">
                    <div class="product-status-badge status-${product.status}">
                        ${product.status.charAt(0).toUpperCase() + product.status.slice(1)}
                    </div>
                </div>
                <div class="product-details">
                    <h3 class="product-title">${escapeHtml(product.name)}</h3>
                    <p class="product-price">R ${parseFloat(product.price).toFixed(2)}</p>
                    <p class="product-date">Listed: ${new Date(product.created_at).toLocaleDateString()}</p>
                    <div class="product-actions">
                        <a href="edit-product.php?id=${product.id}" class="action-btn edit-btn" title="Edit">
                            <img src="${baseUrl}images/icons/edit-svgrepo-com.svg" alt="Edit">
                            <span>Edit</span>
                        </a>
                        ${product.status === 'active' ? 
                            `<a href="?action=suspend&id=${product.id}&status=${statusFilter}${searchTerm ? '&search=' + encodeURIComponent(searchTerm) : ''}" 
                               class="action-btn suspend-btn" 
                               title="Suspend"
                               onclick="return confirm('Are you sure you want to suspend this product?');">
                                <img src="${baseUrl}images/icons/hide-svgrepo-com.svg" alt="Suspend">
                                <span>Suspend</span>
                            </a>` : 
                            `<a href="?action=activate&id=${product.id}&status=${statusFilter}${searchTerm ? '&search=' + encodeURIComponent(searchTerm) : ''}" 
                               class="action-btn activate-btn" 
                               title="Activate"
                               onclick="return confirm('Are you sure you want to activate this product?');">
                                <img src="${baseUrl}images/icons/show-svgrepo-com.svg" alt="Activate">
                                <span>Activate</span>
                            </a>`
                        }
                        <button type="button" class="action-btn delete-btn" title="Delete" onclick="deleteProduct(${product.id})">
                            <img src="${baseUrl}images/icons/delete-svgrepo-com.svg" alt="Delete">
                            <span>Delete</span>
                        </button>
                    </div>
                </div>
            `);
            $grid.append(card);
        });
    }
});
</script>