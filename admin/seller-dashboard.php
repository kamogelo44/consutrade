<?php
/*
 * ConsuTrade - Seller Dashboard
 * Author: Kamogelo Phale
 * 
 * Main dashboard for sellers showing stats, recent products, and orders
 */

require_once dirname(__DIR__) . '/init.php';

// Check if seller is logged in using centralized auth
if (!isSellerLoggedIn()) {
    header('Location: login.php');
    exit;
}

$baseUrl = getBaseUrl();
$user_id = getCurrentSeller()['user_id'] ?? 0;

// Get user data using helper
$user = getUserById($conn, $user_id);
$profile_image = getUserProfileImage($user['profile_image'] ?? null);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard - ConsuTrade</title>
    <meta name="author" content="Kamogelo Phale">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/dashboard-clean.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/sidebar-clean.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <script>
        var baseUrl = '<?php echo $baseUrl; ?>';
    </script>
</head>
<body class="seller-dashboard-page">

<?php include 'includes/sidebar.php'; ?>

<!-- Main Content -->
<main class="seller-main-content">
    <div class="dashboard-content">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1>Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
            <p>Here's what's happening with your store today.</p>
        </div>

        <!-- Flash Message -->
        <?php if (isset($_SESSION['flash'])): ?>
            <div class="flash-message" id="flashMessage">
                <?php echo $_SESSION['flash']; unset($_SESSION['flash']); ?>
            </div>
            <script>
                setTimeout(function() {
                    var f = document.getElementById('flashMessage');
                    if (f) f.remove();
                }, 5000);
            </script>
        <?php endif; ?>

        <?php if (isset($_SESSION['product_errors'])): ?>
            <div class="error-message" id="errorMessage">
                <?php echo implode(', ', $_SESSION['product_errors']); unset($_SESSION['product_errors']); ?>
            </div>
            <script>
                setTimeout(function() {
                    var e = document.getElementById('errorMessage');
                    if (e) e.remove();
                }, 5000);
            </script>
        <?php endif; ?>

        <!-- Stats Section - 3 clean cards -->
        <div class="stats-grid-seller">
            <div class="stat-card">
                <div class="stat-icon">
                    <img src="<?php echo $baseUrl; ?>images/icons/cash-atm-svgrepo-com.svg" alt="Earnings">
                </div>
                <div class="stat-info">
                    <h3>Total Earnings</h3>
                    <p class="stat-number" id="stat-earnings">R0.00</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" alt="Products">
                </div>
                <div class="stat-info">
                    <h3>Total Products</h3>
                    <p class="stat-number" id="stat-products">0</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" alt="Orders">
                </div>
                <div class="stat-info">
                    <h3>Pending Orders</h3>
                    <p class="stat-number pending" id="stat-pending">0</p>
                </div>
            </div>
        </div>

        <!-- Dashboard Sections - 2 columns -->
        <div class="dashboard-grid">
            <!-- My Listings Section -->
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
        </div>

        <!-- Store Summary Card (Replaces Profile & Settings) -->
        <div class="store-summary-card">
            <div class="store-summary-header">
                <div class="store-avatar">
                    <img src="<?php echo $profile_image; ?>" alt="Store Avatar" onerror="this.src='<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg'">
                </div>
                <div class="store-info">
                    <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
                    <p class="store-role">Seller Account</p>
                    <span class="store-status active">Active</span>
                </div>
            </div>
            <div class="store-summary-actions">
                <a href="seller-profile.php" class="store-action-link">
                    <img src="<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg" alt="Profile">
                    Edit Profile
                </a>
                <a href="<?php echo $baseUrl; ?>admin/php/seller-logout.php" class="store-action-link logout">
                    <img src="<?php echo $baseUrl; ?>images/icons/logout-svgrepo-com.svg" alt="Logout">
                    Logout
                </a>
            </div>
        </div>
    </div>
</main>

<script src="<?php echo $baseUrl; ?>js/main.js"></script>
<script src="<?php echo $baseUrl; ?>admin/js/dashboard.js"></script>
<script>
// Close sidebar when modal opens, reopen when modal closes
$(document).on('click', '[data-modal-open], .view-details-btn, .process-btn, .ship-btn, .complete-btn, .cancel-btn, .delete-btn, .edit-btn', function() {
    var prefix = $('body').hasClass('admin-dashboard-page') ? 'admin' : 'seller';
    var $sideMenu = $('#' + prefix + 'SideMenu');
    if ($sideMenu.hasClass('active')) {
        $sideMenu.data('was-open', true);
        $sideMenu.removeClass('active');
        $('#' + prefix + 'MenuOverlay').removeClass('active');
    }
});

$(document).on('click', '.modal-close, .btn-close, .order-modal-close', function() {
    var prefix = $('body').hasClass('admin-dashboard-page') ? 'admin' : 'seller';
    var $sideMenu = $('#' + prefix + 'SideMenu');
    if ($sideMenu.data('was-open') === true) {
        $sideMenu.addClass('active');
        $('#' + prefix + 'MenuOverlay').addClass('active');
        $sideMenu.removeData('was-open');
    }
});
</script>
</body>
</html>