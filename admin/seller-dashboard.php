<?php
/*
 * ConsuTrade - Seller Dashboard
 * Author: Kamogelo Phale
 * 
 * This is the main dashboard for sellers to manage their products and orders
 */

session_start();

$baseUrl = "/www/consutrade/";

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

// Check if user is a seller
if ($_SESSION['role'] !== 'seller') {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

// Include database connection
require_once dirname(__DIR__) . '/php/config.php';

// Get seller's stats
$total_products = 0;
$total_orders = 0;
$pending_orders = 0;
$total_earnings = 0;

// Get total products count
$product_sql = "SELECT COUNT(*) as count FROM products WHERE seller_id = ?";
$product_stmt = $conn->prepare($product_sql);
$product_stmt->bind_param('i', $_SESSION['user_id']);
$product_stmt->execute();
$product_result = $product_stmt->get_result();
if ($product_row = $product_result->fetch_assoc()) {
    $total_products = $product_row['count'];
}
$product_stmt->close();

// Get orders count
$order_sql = "SELECT COUNT(*) as count, SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending FROM orders WHERE seller_id = ?";
$order_stmt = $conn->prepare($order_sql);
$order_stmt->bind_param('i', $_SESSION['user_id']);
$order_stmt->execute();
$order_result = $order_stmt->get_result();
if ($order_row = $order_result->fetch_assoc()) {
    $total_orders = $order_row['count'];
    $pending_orders = $order_row['pending'] ?? 0;
}
$order_stmt->close();

// Get total earnings
$earnings_sql = "SELECT SUM(total_price) as total FROM orders WHERE seller_id = ? AND status = 'completed'";
$earnings_stmt = $conn->prepare($earnings_sql);
$earnings_stmt->bind_param('i', $_SESSION['user_id']);
$earnings_stmt->execute();
$earnings_result = $earnings_stmt->get_result();
if ($earnings_row = $earnings_result->fetch_assoc()) {
    $total_earnings = $earnings_row['total'] ?? 0;
}
$earnings_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ConsuTrade - Seller Dashboard</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/header.css">
    <link rel="stylesheet" href="css/seller-dashboard.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/login-signup.css">
</head>
<body class="dashboard-page">
    <!-- Header - using main header.php -->
    <?php include dirname(__DIR__) . '/header.php'; ?>

    <main>
        <!-- Flash Message -->
        <?php if (isset($_SESSION['flash'])): ?>
            <div class="flash-message"><?php echo $_SESSION['flash']; unset($_SESSION['flash']); ?></div>
        <?php endif; ?>

        <!-- Stats Section -->
        <section class="stats-sect">
            <div class="stats">
                <div class="stat-card">
                    <h2 class="stat-num">R<?php echo number_format($total_earnings, 2); ?></h2>
                    <p class="stat-name">Total Earnings</p>
                </div>

                <div class="stat-card">
                    <h2 class="stat-num"><?php echo $total_products; ?></h2>
                    <p class="stat-name">Active Listings</p>
                </div>

                <div class="stat-card">
                    <h2 class="stat-num"><?php echo $pending_orders; ?></h2>
                    <p class="stat-name">Pending Orders</p>
                </div>
            </div>
        </section>

        <!-- Listings Section -->
        <section class="listings-sect">
            <div class="left-col">
                <h1 class="sect-head">My Listings</h1>
                <div class="listings-grid" id="listings-grid">
                    <div class="loading-spinner">Loading your products...</div>
                </div>
            </div>

            <div class="right-col">
                <h1>Quick Actions</h1>
                <div class="q-actions">
                    <button class="add-listing-btn" onclick="window.location.href='add-product.php'">+ Add New Listing</button>
                    <button class="view-all-order" onclick="window.location.href='my-orders.php'">View All Orders</button>
                    <button class="Edit-my-profile" onclick="window.location.href='<?php echo $baseUrl; ?>profile.php'">Edit My Profile</button>
                </div>
                
                <div class="recent-orders">
                    <h2>Recent Orders</h2>
                    <div class="orders-list" id="recent-orders-list">
                        <p class="placeholder-text">No recent orders to display.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

<script src="<?php echo $baseUrl; ?>js/main.js"></script>
<script>
    // Load seller's products when page loads
    document.addEventListener('DOMContentLoaded', function() {
        loadSellerProducts();
        loadRecentOrders();
    });

    function loadSellerProducts() {
        fetch('/www/consutrade/php/get-seller-products.php')
            .then(function(response) { 
                if (!response.ok) {
                    throw new Error('HTTP error ' + response.status);
                }
                return response.json(); 
            })
            .then(function(data) {
                var grid = document.getElementById('listings-grid');
                
                if (data.success && data.products && data.products.length > 0) {
                    displaySellerProducts(data.products);
                } else {
                    grid.innerHTML = `
                        <div class="empty-listings">
                            <p>You haven't listed any products yet.</p>
                            <button class="add-listing-btn" onclick="window.location.href='add-product.php'">+ Add Your First Product</button>
                        </div>
                    `;
                }
            })
            .catch(function(error) {
                console.log('Error:', error);
                document.getElementById('listings-grid').innerHTML = '<p class="error">Error loading products. Please refresh the page.</p>';
            });
    }

    function displaySellerProducts(products) {
        var grid = document.getElementById('listings-grid');
        grid.innerHTML = '';
        
        for (var i = 0; i < products.length; i++) {
            var product = products[i];
            var card = document.createElement('div');
            card.className = 'listing-card';
            card.innerHTML = `
                <div class="listing-img">
                    <img src="${product.image}" alt="${escapeHtml(product.name)}" onerror="this.src='/www/consutrade/images/default-product.jpg'">
                </div>
                <div class="listing-info">
                    <h3 class="listing-title">${escapeHtml(product.name)}</h3>
                    <p class="listing-price">R ${parseFloat(product.price).toFixed(2)}</p>
                    <p class="listing-status ${product.status}">${product.status}</p>
                    <div class="listing-actions">
                        <button class="edit-btn" onclick="editProduct(${product.id})">Edit</button>
                        <button class="delete-btn" onclick="deleteProduct(${product.id})">Delete</button>
                    </div>
                </div>
            `;
            grid.appendChild(card);
        }
    }

    function loadRecentOrders() {
        fetch('/www/consutrade/php/get-recent-orders.php')
            .then(function(response) { return response.json(); })
            .then(function(data) {
                var ordersList = document.getElementById('recent-orders-list');
                
                if (data.success && data.orders && data.orders.length > 0) {
                    ordersList.innerHTML = '';
                    for (var i = 0; i < data.orders.length; i++) {
                        var order = data.orders[i];
                        var orderItem = document.createElement('div');
                        orderItem.className = 'order-item';
                        orderItem.innerHTML = `
                            <p class="order-id">Order #${order.id}</p>
                            <p class="order-amount">R ${parseFloat(order.total).toFixed(2)}</p>
                            <p class="order-status ${order.status}">${order.status}</p>
                        `;
                        ordersList.appendChild(orderItem);
                    }
                } else {
                    ordersList.innerHTML = '<p class="placeholder-text">No recent orders to display.</p>';
                }
            })
            .catch(function(error) {
                console.log('Error:', error);
            });
    }

    function editProduct(productId) {
        window.location.href = 'edit-product.php?id=' + productId;
    }

    function deleteProduct(productId) {
        if (confirm('Are you sure you want to delete this product?')) {
            fetch('/www/consutrade/php/delete-product.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ product_id: productId })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    loadSellerProducts();
                    alert('Product deleted successfully!');
                } else {
                    alert('Error deleting product: ' + data.message);
                }
            })
            .catch(function(error) {
                console.log('Error:', error);
                alert('Something went wrong');
            });
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Display flash message if exists
    <?php if (isset($_SESSION['flash'])): ?>
        var flashMsg = document.createElement('div');
        flashMsg.className = 'flash-message';
        flashMsg.textContent = '<?php echo $_SESSION['flash']; ?>';
        document.querySelector('main').insertBefore(flashMsg, document.querySelector('main').firstChild);
        setTimeout(function() { flashMsg.remove(); }, 5000);
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['product_errors'])): ?>
        var errorMsg = document.createElement('div');
        errorMsg.className = 'error-message';
        errorMsg.textContent = '<?php echo implode(', ', $_SESSION['product_errors']); ?>';
        document.querySelector('main').insertBefore(errorMsg, document.querySelector('main').firstChild);
        setTimeout(function() { errorMsg.remove(); }, 5000);
        <?php unset($_SESSION['product_errors']); ?>
    <?php endif; ?>
</script>
</body>
</html>