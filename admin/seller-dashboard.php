<?php
/*
 * ConsuTrade - Seller Dashboard
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__) . '/init.php';

if (!$auth->isSeller()) {
    header('Location: ' . $baseUrl . 'admin/login.php');
    exit;
}

$user_id = $currentUser->getUserId();
$user_name = $currentUser->getFullName();
$profile_image = $currentUser->getProfileImageUrl();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/admin.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
</head>

<body class="seller-dashboard-page">

    <?php include 'includes/sidebar.php'; ?>

    <main class="seller-main-content">
        <div class="dashboard-content">
            <div class="welcome-section">
                <h1>Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h1>
                <p>Here's what's happening with your store today.</p>
            </div>

            <?php if (isset($_SESSION['flash'])): ?>
                <div class="flash-message" id="flashMessage"><?php echo $_SESSION['flash'];
                                                                unset($_SESSION['flash']); ?></div>
                <script>
                    setTimeout(function() {
                        var f = document.getElementById('flashMessage');
                        if (f) f.remove();
                    }, 5000);
                </script>
            <?php endif; ?>

            <?php if (isset($_SESSION['product_errors'])): ?>
                <div class="error-message" id="errorMessage"><?php echo implode(', ', $_SESSION['product_errors']);
                                                                unset($_SESSION['product_errors']); ?></div>
                <script>
                    setTimeout(function() {
                        var e = document.getElementById('errorMessage');
                        if (e) e.remove();
                    }, 5000);
                </script>
            <?php endif; ?>

            <div class="stats-grid-seller">
                <div class="stat-card">
                    <div class="stat-icon"><img src="<?php echo $baseUrl; ?>images/icons/cash-atm-svgrepo-com.svg" alt="Earnings"></div>
                    <div class="stat-info">
                        <h3>Total Earnings</h3>
                        <p class="stat-number" id="stat-earnings">R0.00</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" alt="Products"></div>
                    <div class="stat-info">
                        <h3>Total Products</h3>
                        <p class="stat-number" id="stat-products">0</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" alt="Orders"></div>
                    <div class="stat-info">
                        <h3>Pending Orders</h3>
                        <p class="stat-number pending" id="stat-pending">0</p>
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="section-card">
                    <div class="section-header">
                        <h2>My Listings</h2>
                        <a href="my-products.php" class="view-all-link">View All →</a>
                    </div>
                    <div class="listings-grid" id="listings-grid">
                        <div class="loading-spinner">Loading your products...</div>
                    </div>
                    <div class="add-product-btn-container">
                        <a href="add-product.php" class="add-product-btn">
                            <img src="<?php echo $baseUrl; ?>images/icons/add-svgrepo-com.svg" alt="Add">
                            <span>Add New Product</span>
                        </a>
                    </div>
                </div>

                <div class="section-card">
                    <div class="section-header">
                        <h2>Recent Orders</h2>
                        <a href="my-orders.php" class="view-all-link">View All →</a>
                    </div>
                    <div class="orders-list" id="orders-list">
                        <div class="loading-spinner">Loading recent orders...</div>
                    </div>
                </div>
            </div>

            <div class="store-summary-card">
                <div class="store-summary-header">
                    <div class="store-avatar">
                        <img src="<?php echo $profile_image; ?>" alt="Store Avatar" onerror="this.src='<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg'">
                    </div>
                    <div class="store-info">
                        <h3><?php echo htmlspecialchars($user_name); ?></h3>
                        <p class="store-role">Seller Account</p>
                        <span class="store-status active">Active</span>
                    </div>
                </div>
                <div class="store-summary-actions">
                    <a href="seller-profile.php" class="store-action-link">
                        <img src="<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg" alt="Profile">
                        Edit Profile
                    </a>
                    <a href="<?php echo $baseUrl; ?>php/endpoints/logout.php" class="store-action-link logout">
                        <img src="<?php echo $baseUrl; ?>images/icons/logout-svgrepo-com.svg" alt="Logout">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </main>

    <script src="<?php echo $baseUrl; ?>admin/js/dashboard.js"></script>
    <script>
        // Load seller statistics
        $.ajax({
            url: baseUrl + 'php/endpoints/get-user-stats.php?seller_id=' + <?php echo $user_id; ?>,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    $('#stat-earnings').text('R' + (data.total_sales || 0).toFixed(2));
                    $('#stat-products').text(data.total_products || 0);
                }
            }
        });

        // Load seller products
        $.ajax({
            url: baseUrl + 'php/endpoints/get-seller-products.php?seller_id=' + <?php echo $user_id; ?> + '&limit=6',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success && data.products && data.products.length > 0) {
                    var $grid = $('#listings-grid');
                    $grid.empty();

                    $.each(data.products, function(i, product) {
                        var imagePath = product.image;
                        if (imagePath && !imagePath.startsWith('http')) {
                            imagePath = baseUrl + imagePath;
                        }

                        var stockClass = '';
                        var stockText = '';
                        if (product.stock_quantity <= 0) {
                            stockClass = 'out-of-stock';
                            stockText = 'Out of Stock';
                        } else if (product.stock_quantity <= 5) {
                            stockClass = 'low-stock';
                            stockText = 'Low Stock';
                        }

                        $grid.append(`
                            <div class="product-card">
                                <div class="product-image">
                                    <img src="${imagePath || baseUrl + 'images/default-product.png'}" onerror="this.src='${baseUrl}images/default-product.png'">
                                </div>
                                <div class="product-details">
                                    <h4 class="product-title">${escapeHtml(product.name)}</h4>
                                    <p class="product-price">R ${parseFloat(product.price).toFixed(2)}</p>
                                    ${stockClass ? `<span class="stock-badge ${stockClass}">${stockText}</span>` : ''}
                                    <div class="product-actions">
                                        <a href="edit-product.php?id=${product.id}" class="edit-btn">Edit</a>
                                        <button class="delete-btn" onclick="deleteProduct(${product.id})">Delete</button>
                                    </div>
                                </div>
                            </div>
                        `);
                    });
                } else {
                    $('#listings-grid').html('<div class="empty-state"><p>No products yet. <a href="add-product.php">Add your first product</a></p></div>');
                }
            }
        });

        // Load recent orders
        $.ajax({
            url: baseUrl + 'php/endpoints/get-seller-recent-orders.php?limit=5',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success && data.orders && data.orders.length > 0) {
                    var $ordersList = $('#orders-list');
                    $ordersList.empty();

                    $.each(data.orders, function(i, order) {
                        var statusClass = 'status-' + order.status;
                        $ordersList.append(`
                            <div class="order-item" onclick="window.location.href='my-orders.php'">
                                <div class="order-info">
                                    <span class="order-number">#${order.id}</span>
                                    <span class="order-status ${statusClass}">${order.status}</span>
                                </div>
                                <div class="order-products">
                                    <span class="product-names">${escapeHtml(order.product_names || 'Products')}</span>
                                    <span class="product-count">${order.item_count} item(s)</span>
                                </div>
                                <div class="order-details">
                                    <span class="order-total">R ${parseFloat(order.total).toFixed(2)}</span>
                                    <span class="order-date">${order.created_at}</span>
                                </div>
                            </div>
                        `);
                    });
                } else {
                    $('#orders-list').html('<div class="empty-state"><p>No recent orders.</p></div>');
                }
            }
        });

        function deleteProduct(id) {
            if (confirm('Delete this product? This action cannot be undone.')) {
                $.ajax({
                    url: baseUrl + 'php/endpoints/delete-product.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        product_id: id
                    }),
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            showSuccessToast('Product deleted successfully');
                            location.reload();
                        } else {
                            showErrorToast(data.message || 'Error deleting product');
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