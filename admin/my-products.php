<?php
/*
 * ConsuTrade - My Products (Seller)
 * Author: Kamogelo Phale
 * 
 * Displays all products for the logged-in seller with management options
 */

require_once dirname(__DIR__) . '/init.php';

// Check if seller is logged in using centralized auth
if (!$is_logged_in || $current_user['role'] !== 'seller') {
    header('Location: login.php');
    exit;
}

$baseUrl = getBaseUrl();
$seller_id = $current_user_id;
$success_message = '';
$error_message = '';

// Handle status update (activate/suspend)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    $result = updateProductStatus($conn, $product_id, $seller_id, $action);
    
    if ($result['success']) {
        $success_message = $result['message'];
    } else {
        $error_message = $result['message'];
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get products using helper function
$products = getSellerProducts($conn, $seller_id, $status_filter, $search_term);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products - ConsuTrade Seller</title>
    <meta name="author" content="Kamogelo Phale">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/dashboard.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/my-products.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <script>
        var baseUrl = '<?php echo $baseUrl; ?>';
        var statusFilter = '<?php echo $status_filter; ?>';
        var searchTerm = '<?php echo addslashes($search_term); ?>';
    </script>
</head>
<body class="my-products-page seller-dashboard-page">

<?php include 'includes/sidebar.php'; ?>

<main class="dashboard-main">
    <div class="dashboard-content">
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
                <?php if (empty($products)): ?>
                    <div class="empty-products">
                        <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" alt="No products">
                        <h3>No Products Found</h3>
                        <p><?php echo !empty($search_term) ? 'No products match your search criteria.' : 'You haven\'t listed any products yet.'; ?></p>
                        <?php if (!empty($search_term)): ?>
                            <a href="?status=<?php echo $status_filter; ?>" class="clear-btn">Clear Search</a>
                        <?php else: ?>
                            <a href="add-product.php" class="add-btn">Add Your First Product</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="product-card" data-product-id="<?php echo $product['id']; ?>">
                            <div class="product-image">
                                <img src="<?php echo getProductImageUrl($product['image']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>" onerror="this.src='<?php echo $baseUrl; ?>images/default-product.png'">
                                <div class="product-status-badge status-<?php echo $product['status']; ?>">
                                    <?php echo ucfirst($product['status']); ?>
                                </div>
                            </div>
                            <div class="product-details">
                                <h3 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h3>
                                <p class="product-price">R <?php echo number_format($product['price'], 2); ?></p>
                                <p class="product-stock">Stock: <?php echo $product['stock_quantity']; ?></p>
                                <p class="product-date">Listed: <?php echo date('d M Y', strtotime($product['created_at'])); ?></p>
                                <div class="product-actions">
                                    <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="action-btn edit-btn" title="Edit">
                                        <img src="<?php echo $baseUrl; ?>images/icons/edit-svgrepo-com.svg" alt="Edit">
                                        <span>Edit</span>
                                    </a>
                                    <?php if ($product['status'] === 'active'): ?>
                                        <a href="?action=suspend&id=<?php echo $product['id']; ?>&status=<?php echo $status_filter; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>" 
                                           class="action-btn suspend-btn" 
                                           title="Suspend"
                                           onclick="return confirm('Are you sure you want to suspend this product?');">
                                            <img src="<?php echo $baseUrl; ?>images/icons/hide-svgrepo-com.svg" alt="Suspend">
                                            <span>Suspend</span>
                                        </a>
                                    <?php else: ?>
                                        <a href="?action=activate&id=<?php echo $product['id']; ?>&status=<?php echo $status_filter; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>" 
                                           class="action-btn activate-btn" 
                                           title="Activate"
                                           onclick="return confirm('Are you sure you want to activate this product?');">
                                            <img src="<?php echo $baseUrl; ?>images/icons/show-svgrepo-com.svg" alt="Activate">
                                            <span>Activate</span>
                                        </a>
                                    <?php endif; ?>
                                    <button type="button" class="action-btn delete-btn" title="Delete" onclick="deleteProductFromList(<?php echo $product['id']; ?>)">
                                        <img src="<?php echo $baseUrl; ?>images/icons/delete-svgrepo-com.svg" alt="Delete">
                                        <span>Delete</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script src="<?php echo $baseUrl; ?>js/main.js"></script>
<script src="<?php echo $baseUrl; ?>admin/js/dashboard.js"></script>
<script>
/*
 * ConsuTrade - My Products Functionality
 * Author: Kamogelo Phale
 */
function deleteProductFromList(productId) {
    if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
        $.ajax({
            url: baseUrl + 'admin/php/delete-product.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ product_id: productId }),
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    showSuccessToast(data.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showErrorToast('Error: ' + data.message);
                }
            },
            error: function() {
                showErrorToast('Something went wrong');
            }
        });
    }
}
</script>

</body>
</html>