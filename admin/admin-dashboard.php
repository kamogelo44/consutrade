<?php
/*
 * ConsuTrade - Admin Dashboard
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__) . '/init.php';

// Check if admin is logged in using Auth class
if (!$auth->isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $current_user_id;
$user_data = $userRepo->getById($user_id);
$user_name = $user_data['full_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>css/style.css">
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>admin/css/sidebar-clean.css">
    <script src="<?php echo getBaseUrl(); ?>js/jquery-3.7.1.min.js"></script>
    <style>
        /* ========== ADMIN DASHBOARD SPECIFIC STYLES ========== */
        .admin-main-content { margin-left: 280px; padding: var(--spacing-xl); min-height: 100vh; background: var(--gray-bg); transition: margin-left var(--transition-normal); }
        .dashboard-content { max-width: 1400px; margin: 0 auto; }
        .welcome-section { margin-bottom: var(--spacing-xl); }
        .welcome-section h1 { font-size: var(--font-2xl); font-weight: var(--font-bold); color: var(--dark-bg); margin-bottom: var(--spacing-xs); }
        .welcome-section p { color: var(--gray-medium); }
        
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: var(--spacing-lg); margin-bottom: var(--spacing-xl); }
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
        
        .table-wrapper { overflow-x: auto; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: var(--spacing-sm) var(--spacing-md); text-align: left; border-bottom: 1px solid var(--border-light); }
        .data-table th { font-weight: var(--font-semibold); color: var(--gray-dark); background: var(--gray-bg-light); font-size: var(--font-sm); }
        .data-table tr:hover td { background: var(--gray-bg-light); }
        .loading-cell { text-align: center; padding: var(--spacing-xl); color: var(--gray-medium); }
        
        @media (max-width: 1200px) { .stats-grid { grid-template-columns: repeat(2, 1fr); gap: var(--spacing-md); } .stat-number { font-size: var(--font-2xl); } }
        @media (max-width: 1024px) { .admin-main-content { margin-left: 0; width: 100%; padding: var(--spacing-md); padding-top: 70px; } .dashboard-grid { grid-template-columns: 1fr; gap: var(--spacing-md); } .stats-grid { grid-template-columns: repeat(2, 1fr); } .data-table { min-width: 600px; } }
        @media (max-width: 768px) { .admin-main-content { padding: var(--spacing-md); padding-top: 70px; } .stats-grid { grid-template-columns: 1fr; gap: var(--spacing-sm); } .stat-card { padding: var(--spacing-md); } .stat-icon { width: 44px; height: 44px; } .stat-icon img { width: 22px; height: 22px; } .stat-number { font-size: var(--font-xl); } .section-card { padding: var(--spacing-md); } }
        @media (max-width: 480px) { .admin-main-content { padding: var(--spacing-sm); padding-top: 60px; } .welcome-section h1 { font-size: var(--font-xl); } .stat-number { font-size: var(--font-lg); } .section-header h2 { font-size: var(--font-base); } }
    </style>
</head>
<body class="admin-dashboard-page" style="margin:0;background:var(--gray-bg);">

<?php include 'includes/sidebar.php'; ?>

<main class="admin-main-content">
    <div class="dashboard-content">
        <div class="welcome-section">
            <h1>Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h1>
            <p>Here's what's happening with your marketplace today.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Total Revenue</h3>
                    <p class="stat-number" id="totalRevenue">R0.00</p>
                </div>
                <div class="stat-icon">
                    <img src="<?php echo getBaseUrl(); ?>images/icons/money-total-line-svgrepo-com.svg" alt="Revenue">
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Total Users</h3>
                    <p class="stat-number" id="totalUsers">--</p>
                </div>
                <div class="stat-icon">
                    <img src="<?php echo getBaseUrl(); ?>images/icons/users-svgrepo-com.svg" alt="Users">
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Total Products</h3>
                    <p class="stat-number" id="totalProducts">--</p>
                </div>
                <div class="stat-icon">
                    <img src="<?php echo getBaseUrl(); ?>images/icons/product-catalog-svgrepo-com.svg" alt="Products">
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Pending Orders</h3>
                    <p class="stat-number pending" id="pendingOrders">--</p>
                </div>
                <div class="stat-icon">
                    <img src="<?php echo getBaseUrl(); ?>images/icons/clock-svgrepo-com.svg" alt="Pending">
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="section-card">
                <div class="section-header">
                    <h2>Recent Users</h2>
                    <a href="users.php" class="view-all-link">View All →</a>
                </div>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr><th>Name</th><th>Email</th><th>Role</th><th>Joined</th></tr>
                        </thead>
                        <tbody id="recent-users-table">
                            <tr><td colspan="4" class="loading-cell">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <h2>Recent Orders</h2>
                    <a href="all-orders.php" class="view-all-link">View All →</a>
                </div>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr><th>Order #</th><th>Customer</th><th>Amount</th><th>Status</th><th>Date</th></tr>
                        </thead>
                        <tbody id="recent-orders-table">
                            <tr><td colspan="5" class="loading-cell">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
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