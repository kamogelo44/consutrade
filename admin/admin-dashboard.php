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
    <style>
        /* ========== PAGE-SPECIFIC STYLES ONLY ========== */

        /* Stats grid override for 6 columns */
        .admin-stats-grid {
            grid-template-columns: repeat(3, 1fr);
            gap: var(--spacing-xl);
        }

        /* Notice banner styles */
        .notice-banner {
            padding: var(--spacing-md);
            border-radius: var(--radius-lg);
            margin-bottom: var(--spacing-xl);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: var(--spacing-sm);
        }

        .notice-banner.warning {
            background: var(--warning-light);
        }

        .notice-banner.error {
            background: var(--error-light);
        }

        .notice-banner.info {
            background: var(--info-light);
        }

        .notice-banner .view-all-link {
            color: var(--primary-color);
            text-decoration: none;
            font-size: var(--font-sm);
            font-weight: var(--font-medium);
        }

        .notice-banner .view-all-link:hover {
            text-decoration: underline;
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 1200px) {
            .admin-stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: var(--spacing-lg);
            }
        }

        @media (max-width: 768px) {
            .admin-stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: var(--spacing-md);
            }

            .stat-card {
                padding: var(--spacing-md);
            }

            .stat-number {
                font-size: var(--font-lg);
            }

            .stat-icon {
                width: 40px;
                height: 40px;
            }

            .stat-icon img {
                width: 20px;
                height: 20px;
            }

            .notice-banner {
                flex-direction: column;
                text-align: center;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: var(--spacing-lg);
            }
        }

        @media (max-width: 480px) {
            .admin-stats-grid {
                grid-template-columns: 1fr;
            }

            .page-header h1 {
                font-size: var(--font-xl);
            }

            .stat-number {
                font-size: var(--font-base);
            }

            .stat-info h3 {
                font-size: 10px;
            }
        }
    </style>
</head>

<body class="admin-dashboard-page">

    <?php include 'includes/sidebar.php'; ?>

    <main class="admin-main-content">
        <div class="dashboard-content">
            <!-- Page Header -->
            <div class="page-header">
                <h1>Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h1>
                <p>Here's what's happening with your marketplace today.</p>
            </div>

            <!-- Stats Grid -->
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

            <!-- Notice Banners -->
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
        // ============================================================
        // DOM CACHE - For page-specific elements
        // ============================================================

        var $adminSideMenu = null,
            $adminMenuOverlay = null;

        function cacheSidebarElements() {
            $adminSideMenu = $('#adminSideMenu');
            $adminMenuOverlay = $('#adminMenuOverlay');
        }

        // ============================================================
        // SIDEBAR HANDLING
        // ============================================================

        $(document).on('click', '[data-modal-open], .view-details-btn, .process-btn, .ship-btn, .complete-btn, .cancel-btn, .delete-btn, .edit-btn', function() {
            if ($adminSideMenu && $adminSideMenu.length && $adminSideMenu.hasClass('active')) {
                $adminSideMenu.data('was-open', true);
                $adminSideMenu.removeClass('active');
                if ($adminMenuOverlay && $adminMenuOverlay.length) {
                    $adminMenuOverlay.removeClass('active');
                }
            }
        });

        $(document).on('click', '.modal-close, .btn-close, .order-modal-close', function() {
            if ($adminSideMenu && $adminSideMenu.length && $adminSideMenu.data('was-open') === true) {
                $adminSideMenu.addClass('active');
                if ($adminMenuOverlay && $adminMenuOverlay.length) {
                    $adminMenuOverlay.addClass('active');
                }
                $adminSideMenu.removeData('was-open');
            }
        });

        // ============================================================
        // DOCUMENT READY
        // ============================================================

        $(document).ready(function() {
            cacheSidebarElements();
            // dashboard.js handles the rest
        });
    </script>

</body>

</html>