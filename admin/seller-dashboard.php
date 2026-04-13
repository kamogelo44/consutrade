<?php
/*
 * ConsuTrade - Seller Dashboard
 * Author: Kamogelo Phale
 * 
 * This is the main dashboard for sellers to manage their products and orders
 */

session_start();

// Set base URL for correct path resolution
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
    $pending_orders = $order_row['pending'];
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

$conn->close();
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
<body>
    <!-- Header -->
    <?php include dirname(__DIR__) . '/header.php'; ?>

    <main>
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
                    <?php if ($total_products > 0): ?>
                        <p class="placeholder-text">Loading your products...</p>
                    <?php else: ?>
                        <div class="empty-listings">
                            <p>You haven't listed any products yet.</p>
                            <button class="add-listing-btn" onclick="openAddListingModal()">+ Add Your First Product</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="right-col">
                <h1>Quick Actions</h1>
                <div class="q-actions">
                    <button class="add-listing-btn" onclick="openAddListingModal()">+ Add New Listing</button>
                    <button class="view-all-order" onclick="window.location.href='<?php echo $baseUrl; ?>admin/my-orders.php'">View All Orders</button>
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

    <!-- Add Listing Modal -->
    <div id="add-listing-modal" class="listing-modal">
        <div class="listing-modal-content">
            <div class="listing-modal-header">
                <h2>Add New Product</h2>
                <button class="listing-modal-close" onclick="closeAddListingModal()">&times;</button>
            </div>
            <form id="add-product-form" action="<?php echo $baseUrl; ?>php/add-product.php" method="post" enctype="multipart/form-data">
                <div class="listing-form-group">
                    <label for="product-title">Product Title</label>
                    <input type="text" id="product-title" name="title" required>
                </div>
                <div class="listing-form-group">
                    <label for="product-category">Category</label>
                    <select id="product-category" name="category_id" required>
                        <option value="">Select Category</option>
                        <option value="1">Clothing</option>
                        <option value="2">Electronics</option>
                        <option value="3">Food and Drinks</option>
                        <option value="4">Furniture</option>
                        <option value="5">Other</option>
                    </select>
                </div>
                <div class="listing-form-group">
                    <label for="product-price">Price (R)</label>
                    <input type="number" id="product-price" name="price" step="0.01" required>
                </div>
                <div class="listing-form-group">
                    <label for="product-description">Description</label>
                    <textarea id="product-description" name="description" rows="4"></textarea>
                </div>
                <div class="listing-form-group">
                    <label for="product-image">Product Image</label>
                    <input type="file" id="product-image" name="image" accept="image/*">
                </div>
                <button type="submit" class="listing-submit-btn">Add Product</button>
            </form>
        </div>
    </div>

    <script src="<?php echo $baseUrl; ?>js/main.js"></script>
    <script>
        function openAddListingModal() {
            document.getElementById('add-listing-modal').classList.add('active');
        }
        
        function closeAddListingModal() {
            document.getElementById('add-listing-modal').classList.remove('active');
        }
        
        // Close modal when clicking outside
        var modal = document.getElementById('add-listing-modal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeAddListingModal();
                }
            });
        }
    </script>
</body>
</html>