<?php
/*
 * ConsuTrade - Seller Dashboard
 * Author: Kamogelo Phale
 */

session_start();
require_once dirname(__DIR__) . '/php/config.php';
require_once dirname(__DIR__) . '/php/helpers.php';

$baseUrl = getBaseUrl();

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

// Get user data using helper
$user = getUserById($conn, $_SESSION['user_id']);
$profile_image = getUserProfileImage($user['profile_image'] ?? null);
$conn->close();

// Set current page for active sidebar link
$current_page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/seller-dashboard.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
</head>
<body class="seller-dashboard-page">

<?php include 'includes/seller-sidebar.php'; ?>

        <!-- User Profile Section -->
        <div class="user-profile-section">
            <div class="user-avatar">
                <img src="<?php echo $profile_image; ?>" alt="Profile" onerror="this.src='<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg'">
            </div>
            <div class="user-welcome">
                <h2>Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</h2>
                <p>Here's what's happening with your store today.</p>
            </div>
        </div>
        
        <!-- Flash Message -->
        <?php if (isset($_SESSION['flash'])): ?>
            <div class="flash-message" id="flashMessage"><?php echo $_SESSION['flash']; unset($_SESSION['flash']); ?></div>
            <script>setTimeout(function() { var f = document.getElementById('flashMessage'); if(f) f.remove(); }, 5000);</script>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['product_errors'])): ?>
            <div class="error-message" id="errorMessage"><?php echo implode(', ', $_SESSION['product_errors']); unset($_SESSION['product_errors']); ?></div>
            <script>setTimeout(function() { var e = document.getElementById('errorMessage'); if(e) e.remove(); }, 5000);</script>
        <?php endif; ?>
        
        <!-- Stats Section -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <img src="<?php echo $baseUrl; ?>images/icons/cash-atm-svgrepo-com.svg" alt="Earnings" class="stat-icon-img" onerror="this.style.display='none'">
                </div>
                <div class="stat-info">
                    <h3>Total Earnings</h3>
                    <p class="stat-number" id="stat-earnings">R0.00</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" alt="Products" class="stat-icon-img" onerror="this.style.display='none'">
                </div>
                <div class="stat-info">
                    <h3>Total Products</h3>
                    <p class="stat-number" id="stat-products">0</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" alt="Orders" class="stat-icon-img" onerror="this.style.display='none'">
                </div>
                <div class="stat-info">
                    <h3>Pending Orders</h3>
                    <p class="stat-number" id="stat-pending">0</p>
                </div>
            </div>
        </div>
        
        <!-- Dashboard Sections - Three columns layout -->
        <div class="dashboard-sections three-columns">
            <!-- My Listings Section -->
            <div class="section-card">
                <div class="section-header">
                    <h2>My Listings</h2>
                    <a href="my-products.php" class="view-all-link">View All →</a>
                </div>
                <div class="listings-grid" id="listings-grid">
                    <div class="loading-spinner">Loading your products...</div>
                </div>
                <div class="quick-actions">
                    <a href="add-product.php" class="quick-action-btn add-product-btn">
                        <img src="<?php echo $baseUrl; ?>images/icons/add-svgrepo-com.svg" alt="Add" onerror="this.style.display='none'">
                        <span>Add New Product</span>
                    </a>
                </div>
            </div>
            
            <!-- Recent Orders Section -->
            <div class="section-card">
                <div class="section-header">
                    <h2>Recent Orders</h2>
                    <a href="my-orders.php" class="view-all-link">View All →</a>
                </div>
                <div class="orders-list" id="recent-orders-list">
                    <p class="placeholder-text">No recent orders to display.</p>
                </div>
            </div>
            
            <!-- Profile & Settings Section -->
            <div class="section-card">
                <div class="section-header">
                    <h2>Profile & Settings</h2>
                </div>
                <div class="profile-shortcuts">
                    <a href="<?php echo $baseUrl; ?>admin/seller-profile.php" class="profile-shortcut-link">
                        <div class="profile-shortcut-icon">
                            <img src="<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg" alt="Profile" onerror="this.style.display='none'">
                        </div>
                        <div class="profile-shortcut-info">
                            <h3>My Profile</h3>
                            <p>View and edit your personal information</p>
                        </div>
                        <span class="profile-shortcut-arrow">→</span>
                    </a>
                    <a href="<?php echo $baseUrl; ?>admin/my-products.php" class="profile-shortcut-link">
                        <div class="profile-shortcut-icon">
                            <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" alt="Products" onerror="this.style.display='none'">
                        </div>
                        <div class="profile-shortcut-info">
                            <h3>Manage Products</h3>
                            <p>Add, edit or remove your products</p>
                        </div>
                        <span class="profile-shortcut-arrow">→</span>
                    </a>
                    <a href="<?php echo $baseUrl; ?>admin/my-orders.php" class="profile-shortcut-link">
                        <div class="profile-shortcut-icon">
                            <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" alt="Orders" onerror="this.style.display='none'">
                        </div>
                        <div class="profile-shortcut-info">
                            <h3>Order History</h3>
                            <p>Track and manage your orders</p>
                        </div>
                        <span class="profile-shortcut-arrow">→</span>
                    </a>
                    <a href="<?php echo $baseUrl; ?>php/logout.php" class="profile-shortcut-link logout-link">
                        <div class="profile-shortcut-icon">
                            <img src="<?php echo $baseUrl; ?>images/icons/logout-svgrepo-com.svg" alt="Logout" onerror="this.style.display='none'">
                        </div>
                        <div class="profile-shortcut-info">
                            <h3>Logout</h3>
                            <p>Sign out of your account</p>
                        </div>
                        <span class="profile-shortcut-arrow">→</span>
                    </a>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="<?php echo $baseUrl; ?>js/main.js"></script>
<script src="<?php echo $baseUrl; ?>admin/js/seller-dashboard.js"></script>
</body>
</html>