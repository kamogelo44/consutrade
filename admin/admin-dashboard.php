<?php
/*
 * ConsuTrade - Admin Dashboard
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__) . '/init.php';

if (!$auth->isAdmin()) {
    header('Location: login.php');
    exit;
}

$user_id = $currentUser->getUserId();
$user_name = $currentUser->getFullName();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Admin Dashboard - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/sidebar.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/dashboard-layout.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
</head>

<body class="admin-dashboard-page">

    <?php include 'includes/sidebar.php'; ?>

    <main class="admin-main-content">
        <div class="dashboard-content">
            <!-- Page Header with welcome section -->
            <div class="page-header">
                <h1>Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h1>
                <p>Here's what's happening with your marketplace today.</p>
            </div>

            <!-- Stats Grid - Using admin-stats-grid for 6 columns -->
            <div class="stats-grid admin-stats-grid">
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Total Revenue</h3>
                        <p class="stat-number" id="totalRevenue">R0.00</p>
                    </div>
                    <div class="stat-icon">
                        <img src="<?php echo $baseUrl; ?>images/icons/money-total-line-svgrepo-com.svg" alt="Revenue">
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Total Users</h3>
                        <p class="stat-number" id="totalUsers">--</p>
                    </div>
                    <div class="stat-icon">
                        <img src="<?php echo $baseUrl; ?>images/icons/users-svgrepo-com.svg" alt="Users">
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Total Products</h3>
                        <p class="stat-number" id="totalProducts">--</p>
                    </div>
                    <div class="stat-icon">
                        <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" alt="Products">
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Pending Orders</h3>
                        <p class="stat-number pending" id="pendingOrders">--</p>
                    </div>
                    <div class="stat-icon">
                        <img src="<?php echo $baseUrl; ?>images/icons/clock-svgrepo-com.svg" alt="Pending">
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Pending Verifications</h3>
                        <p class="stat-number pending" id="pendingVerifications">--</p>
                    </div>
                    <div class="stat-icon">
                        <img src="<?php echo $baseUrl; ?>images/icons/valid-document-svgrepo-com.svg" alt="Pending">
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Flagged Reports</h3>
                        <p class="stat-number pending" id="flaggedReports">--</p>
                    </div>
                    <div class="stat-icon">
                        <img src="<?php echo $baseUrl; ?>images/icons/warning-svgrepo-com.svg" alt="Flagged">
                    </div>
                </div>
            </div>

            <!-- Notice banners - Using notice-banner class -->
            <div class="notice-banner warning" id="pendingNotice" style="display: none;">
                <span id="pendingMessage"></span>
                <a href="users.php?role=seller&filter=pending" class="view-all-link">Review Now →</a>
            </div>
            <div class="notice-banner error" id="flaggedReportsNotice" style="display: none;">
                <span id="flaggedReportsMessage"></span>
                <a href="flagged-listings.php" class="view-all-link">Review Reports →</a>
            </div>

            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <div class="section-card">
                    <div class="section-header">
                        <h2>Recent Users</h2>
                        <a href="users.php" class="view-all-link">View All →</a>
                    </div>
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody id="recent-users-table">
                                <tr>
                                    <td colspan="4" class="loading-cell">Loading...</td>
                                </tr>
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
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody id="recent-orders-table">
                                <tr>
                                    <td colspan="5" class="loading-cell">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

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