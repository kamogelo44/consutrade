<?php
/*
 * ConsuTrade - Seller Dashboard
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__) . '/init.php';

// Check if seller is logged in
if (!$auth->isSellerLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $current_user_id;
$user_data = $userRepo->getById($user_id);
$user_name = $user_data['full_name'] ?? 'Seller';
$profile_image = !empty($user_data['profile_image']) ? getBaseUrl() . $user_data['profile_image'] : getBaseUrl() . 'images/icons/profile-svgrepo-com.svg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>css/style.css">
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>admin/css/sidebar-clean.css">
    <style>
        /* ========== SELLER DASHBOARD SPECIFIC STYLES ========== */
        .seller-main-content { margin-left: 280px; padding: var(--spacing-xl); min-height: 100vh; background: var(--gray-bg); transition: margin-left var(--transition-normal); }
        .dashboard-content { max-width: 1400px; margin: 0 auto; }
        .welcome-section { margin-bottom: var(--spacing-xl); }
        .welcome-section h1 { font-size: var(--font-2xl); font-weight: var(--font-bold); color: var(--dark-bg); margin-bottom: var(--spacing-xs); }
        .welcome-section p { color: var(--gray-medium); }
        
        .stats-grid-seller { display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--spacing-lg); margin-bottom: var(--spacing-xl); }
        .stat-card { background: var(--white); border-radius: var(--radius-lg); padding: var(--spacing-lg); display: flex; justify-content: space-between; align-items: center; box-shadow: var(--shadow-sm); border: 1px solid var(--border-light); transition: all var(--transition-fast); }
        .stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .stat-info h3 { font-size: var(--font-sm); color: var(--gray-medium); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: var(--spacing-sm); }
        .stat-number { font-size: var(--font-3xl); font-weight: var(--font-bold); color: var(--primary-color); }
        .stat-number.pending { color: var(--warning); }
        .stat-icon { width: 52px; height: 52px; background: var(--primary-fade); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .stat-icon img { width: 28px; height: 28px; filter: brightness(0) saturate(100%) invert(48%) sepia(96%) saturate(1577%) hue-rotate(350deg); }
        
        .dashboard-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--spacing-lg); margin-bottom: var(--spacing-xl); }
        .section-card { background: var(--white); border-radius: var(--radius-lg); padding: var(--spacing-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border-light); }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-lg); padding-bottom: var(--spacing-sm); border-bottom: 2px solid var(--primary-color); }
        .section-header h2 { font-size: var(--font-lg); font-weight: var(--font-semibold); color: var(--dark-bg); }
        .view-all-link { color: var(--primary-color); text-decoration: none; font-size: var(--font-sm); transition: all var(--transition-fast); }
        .view-all-link:hover { transform: translateX(4px); }
        
        /* Product Listings Grid */
        .listings-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: var(--spacing-md); margin-bottom: var(--spacing-lg); max-height: 380px; overflow-y: auto; }
        .product-card { border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: var(--spacing-sm); background: var(--white); transition: all var(--transition-fast); display: flex; flex-direction: column; height: 100%; }
        .product-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); border-color: var(--primary-color); }
        .product-image { width: 100%; height: 130px; background: var(--gray-bg); border-radius: var(--radius-md); overflow: hidden; margin-bottom: var(--spacing-sm); flex-shrink: 0; }
        .product-image img { width: 100%; height: 100%; object-fit: cover; }
        .product-details { flex: 1; display: flex; flex-direction: column; }
        .product-title { font-size: var(--font-sm); font-weight: var(--font-semibold); margin-bottom: var(--spacing-xs); color: var(--dark-bg); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .product-price { font-size: var(--font-md); font-weight: var(--font-bold); color: var(--primary-color); margin-bottom: var(--spacing-sm); }
        .product-actions { margin-top: auto; display: flex; gap: var(--spacing-sm); }
        .stock-badge { display: inline-block; padding: 2px 8px; border-radius: var(--radius-round); font-size: var(--font-xs); font-weight: var(--font-medium); margin-bottom: var(--spacing-sm); }
        .stock-badge.out-of-stock { background: var(--error-light); color: var(--error); }
        .stock-badge.low-stock { background: var(--warning-light); color: var(--warning); }
        
        .edit-btn, .delete-btn { flex: 1; padding: 5px var(--spacing-sm); font-size: var(--font-xs); font-weight: var(--font-medium); border-radius: var(--radius-md); cursor: pointer; transition: all var(--transition-fast); border: none; }
        .edit-btn { background: var(--primary-fade); color: var(--primary-color); border: 1px solid var(--primary-color); }
        .edit-btn:hover { background: var(--primary-color); color: var(--white); }
        .delete-btn { background: var(--error-light); color: var(--error); border: 1px solid var(--error); }
        .delete-btn:hover { background: var(--error); color: var(--white); }
        
        .add-product-btn-container { margin-top: var(--spacing-lg); padding-top: var(--spacing-md); border-top: 1px solid var(--border-light); }
        .add-product-btn { display: flex; align-items: center; justify-content: center; gap: var(--spacing-sm); padding: var(--spacing-sm) var(--spacing-md); background: var(--primary-color); color: var(--white); border-radius: var(--radius-md); text-decoration: none; font-size: var(--font-md); font-weight: var(--font-medium); transition: all var(--transition-fast); width: 100%; }
        .add-product-btn:hover { background: var(--primary-dark); transform: translateY(-1px); }
        .add-product-btn img { width: 20px; height: 20px; filter: brightness(0) invert(1); }
        
        /* Orders List */
        .orders-list { display: flex; flex-direction: column; gap: var(--spacing-sm); max-height: 380px; overflow-y: auto; }
        .order-item { background: var(--gray-bg-light); border-radius: var(--radius-md); padding: var(--spacing-md); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--spacing-sm); transition: all var(--transition-fast); border: 1px solid transparent; cursor: pointer; }
        .order-item:hover { transform: translateX(3px); border-color: var(--primary-light); background: var(--white); }
        .order-info { display: flex; align-items: center; gap: var(--spacing-md); flex-wrap: wrap; }
        .order-number { font-weight: var(--font-bold); color: var(--primary-color); font-size: var(--font-md); }
        .order-products { flex: 2; min-width: 150px; }
        .product-names { font-size: var(--font-sm); color: var(--gray-dark); display: block; line-height: 1.4; }
        .product-count { font-size: var(--font-xs); color: var(--gray-light); display: block; margin-top: var(--spacing-xs); }
        .order-details { display: flex; flex-direction: column; align-items: flex-end; min-width: 120px; }
        .order-total { font-weight: var(--font-bold); color: var(--dark-bg); font-size: var(--font-md); }
        .order-date { font-size: var(--font-xs); color: var(--gray-light); margin-top: var(--spacing-xs); }
        
        .order-status { display: inline-block; padding: 2px 8px; border-radius: var(--radius-round); font-size: var(--font-xs); font-weight: var(--font-medium); }
        .order-status.status-pending { background: var(--warning-light); color: var(--warning); }
        .order-status.status-processing { background: var(--info-light); color: var(--info); }
        .order-status.status-shipped { background: var(--primary-fade); color: var(--primary-color); }
        .order-status.status-completed { background: var(--success-light); color: var(--success); }
        .order-status.status-cancelled { background: var(--error-light); color: var(--error); }
        
        /* Store Summary Card */
        .store-summary-card { background: var(--white); border-radius: var(--radius-lg); padding: var(--spacing-lg); border: 1px solid var(--border-light); box-shadow: var(--shadow-sm); margin-top: var(--spacing-xl); }
        .store-summary-header { display: flex; align-items: center; gap: var(--spacing-md); margin-bottom: var(--spacing-lg); padding-bottom: var(--spacing-md); border-bottom: 1px solid var(--border-light); }
        .store-avatar { width: 70px; height: 70px; border-radius: 50%; overflow: hidden; background: var(--primary-fade); }
        .store-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .store-info h3 { font-size: var(--font-lg); font-weight: var(--font-semibold); color: var(--dark-bg); margin-bottom: var(--spacing-xs); }
        .store-role { font-size: var(--font-sm); color: var(--gray-medium); margin-bottom: var(--spacing-xs); }
        .store-status.active { display: inline-block; padding: 2px 10px; border-radius: var(--radius-round); font-size: var(--font-xs); background: var(--success-light); color: var(--success); }
        .store-summary-actions { display: flex; gap: var(--spacing-sm); }
        .store-action-link { flex: 1; display: flex; align-items: center; justify-content: center; gap: var(--spacing-sm); padding: var(--spacing-sm); background: var(--gray-bg-light); border-radius: var(--radius-md); text-decoration: none; color: var(--gray-dark); font-size: var(--font-sm); transition: all var(--transition-fast); }
        .store-action-link:hover { background: var(--primary-fade); color: var(--primary-color); }
        .store-action-link.logout:hover { background: var(--error-light); color: var(--error); }
        .store-action-link img { width: 18px; height: 18px; }
        
        /* Responsive */
        @media (max-width: 1200px) { .stats-grid-seller { grid-template-columns: repeat(3, 1fr); } .stat-number { font-size: var(--font-2xl); } }
        @media (max-width: 1024px) { .seller-main-content { margin-left: 0; width: 100%; padding: var(--spacing-md); padding-top: 70px; } .dashboard-grid { grid-template-columns: 1fr; gap: var(--spacing-md); } .stats-grid-seller { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) { .seller-main-content { padding: var(--spacing-md); padding-top: 70px; } .stats-grid-seller { grid-template-columns: 1fr; gap: var(--spacing-sm); } .stat-card { padding: var(--spacing-md); } .stat-icon { width: 44px; height: 44px; } .stat-icon img { width: 22px; height: 22px; } .stat-number { font-size: var(--font-xl); } .section-card { padding: var(--spacing-md); } .order-item { flex-direction: column; text-align: center; } .order-info { justify-content: center; } .order-products { width: 100%; text-align: center; } .order-details { align-items: center; } .product-actions { flex-direction: column; } .edit-btn, .delete-btn { width: 100%; text-align: center; } .store-summary-header { flex-direction: column; text-align: center; } .store-summary-actions { flex-direction: column; } }
        @media (max-width: 480px) { .seller-main-content { padding: var(--spacing-sm); padding-top: 60px; } .welcome-section h1 { font-size: var(--font-xl); } .stat-number { font-size: var(--font-lg); } .section-header h2 { font-size: var(--font-base); } .product-title { font-size: var(--font-xs); } .product-price { font-size: var(--font-sm); } }
    </style>
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
            <div class="flash-message" id="flashMessage"><?php echo $_SESSION['flash']; unset($_SESSION['flash']); ?></div>
            <script>setTimeout(function() { var f = document.getElementById('flashMessage'); if (f) f.remove(); }, 5000);</script>
        <?php endif; ?>

        <?php if (isset($_SESSION['product_errors'])): ?>
            <div class="error-message" id="errorMessage"><?php echo implode(', ', $_SESSION['product_errors']); unset($_SESSION['product_errors']); ?></div>
            <script>setTimeout(function() { var e = document.getElementById('errorMessage'); if (e) e.remove(); }, 5000);</script>
        <?php endif; ?>

        <div class="stats-grid-seller">
            <div class="stat-card">
                <div class="stat-icon"><img src="<?php echo getBaseUrl(); ?>images/icons/cash-atm-svgrepo-com.svg" alt="Earnings"></div>
                <div class="stat-info"><h3>Total Earnings</h3><p class="stat-number" id="stat-earnings">R0.00</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><img src="<?php echo getBaseUrl(); ?>images/icons/product-catalog-svgrepo-com.svg" alt="Products"></div>
                <div class="stat-info"><h3>Total Products</h3><p class="stat-number" id="stat-products">0</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><img src="<?php echo getBaseUrl(); ?>images/icons/shopping-cart-01-svgrepo-com.svg" alt="Orders"></div>
                <div class="stat-info"><h3>Pending Orders</h3><p class="stat-number pending" id="stat-pending">0</p></div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="section-card">
                <div class="section-header"><h2>My Listings</h2><a href="my-products.php" class="view-all-link">View All →</a></div>
                <div class="listings-grid" id="listings-grid"><div class="loading-spinner">Loading your products...</div></div>
                <div class="add-product-btn-container"><a href="add-product.php" class="add-product-btn"><img src="<?php echo getBaseUrl(); ?>images/icons/add-svgrepo-com.svg" alt="Add"><span>Add New Product</span></a></div>
            </div>

            <div class="section-card">
                <div class="section-header"><h2>Recent Orders</h2><a href="my-orders.php" class="view-all-link">View All →</a></div>
                <div class="empty-state"><p>No recent orders to display.</p></div>
            </div>
        </div>

        <div class="store-summary-card">
            <div class="store-summary-header">
                <div class="store-avatar"><img src="<?php echo $profile_image; ?>" alt="Store Avatar" onerror="this.src='<?php echo getBaseUrl(); ?>images/icons/profile-svgrepo-com.svg'"></div>
                <div class="store-info"><h3><?php echo htmlspecialchars($user_name); ?></h3><p class="store-role">Seller Account</p><span class="store-status active">Active</span></div>
            </div>
            <div class="store-summary-actions">
                <a href="seller-profile.php" class="store-action-link"><img src="<?php echo getBaseUrl(); ?>images/icons/profile-svgrepo-com.svg" alt="Profile">Edit Profile</a>
                <a href="<?php echo getBaseUrl(); ?>php/endpoints/seller-logout.php" class="store-action-link logout"><img src="<?php echo getBaseUrl(); ?>images/icons/logout-svgrepo-com.svg" alt="Logout">Logout</a>
            </div>
        </div>
    </div>
</main>

<script src="<?php echo getBaseUrl(); ?>admin/js/dashboard.js"></script>
<script>
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