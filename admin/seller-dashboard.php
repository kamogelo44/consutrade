<?php
/*
 * ConsuTrade - Seller Dashboard
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__) . '/init.php';

// Use hasRole() instead of isSeller() - checks ALL roles, not just active
if (!$auth->hasRole('seller')) {
    header('Location: ' . $baseUrl . 'admin/login.php');
    exit;
}

// If user is logged in but active role is not seller, switch to seller
if (!$auth->isSeller()) {
    $auth->switchRole('seller');
    // Refresh the page to load with seller context
    header('Location: ' . $_SERVER['PHP_SELF']);
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Seller Dashboard - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/sidebar.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/dashboard-layout.css">
    <style>
        /* ========== SELLER DASHBOARD SPECIFIC STYLES ========== */

        /* Stats Grid - Seller (3 columns) */
        .stats-grid-seller {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-xl);
        }

        /* Products Grid for My Listings */
        .listings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
            max-height: 380px;
            overflow-y: auto;
        }

        .product-card {
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            padding: var(--spacing-sm);
            background: var(--white);
            transition: all var(--transition-fast);
            display: flex;
            flex-direction: column;
            height: 200px;
        }

        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-color);
        }

        .product-image {
            width: 100%;
            height: 100px;
            background: var(--gray-bg);
            border-radius: var(--radius-md);
            overflow: hidden;
            margin-bottom: 8px;
            flex-shrink: 0;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-details {
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex: 1;
            padding: 2px 0;
        }

        .product-title {
            font-size: var(--font-sm);
            font-weight: var(--font-semibold);
            color: var(--dark-bg);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin: 0;
        }

        .product-price {
            font-size: var(--font-md);
            font-weight: var(--font-bold);
            color: var(--primary-color);
            margin: 0;
        }

        .product-actions {
            display: flex;
            gap: var(--spacing-sm);
            margin-top: 6px;
            padding-top: 4px;
        }

        .edit-btn,
        .delete-btn {
            flex: 1;
            padding: 5px 6px;
            font-size: 10px;
            font-weight: var(--font-medium);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all var(--transition-fast);
            border: none;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }

        .edit-btn {
            background: var(--primary-fade);
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }

        .edit-btn:hover {
            background: var(--primary-color);
            color: var(--white);
        }

        .delete-btn {
            background: var(--error-light);
            color: var(--error);
            border: 1px solid var(--error);
        }

        .delete-btn:hover {
            background: var(--error);
            color: var(--white);
        }

        /* Add Product Button */
        .add-product-btn-container {
            margin-top: var(--spacing-lg);
            padding-top: var(--spacing-md);
            border-top: 1px solid var(--border-light);
        }

        .add-product-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-sm) var(--spacing-md);
            background: var(--primary-color);
            color: var(--white);
            border-radius: var(--radius-md);
            text-decoration: none;
            font-size: var(--font-md);
            font-weight: var(--font-medium);
            transition: all var(--transition-fast);
            width: 100%;
        }

        .add-product-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .add-product-btn img {
            width: 20px;
            height: 20px;
            filter: brightness(0) invert(1);
        }

        /* Orders List */
        .orders-list {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-sm);
            max-height: 380px;
            overflow-y: auto;
        }

        .order-item {
            background: var(--gray-bg-light);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: var(--spacing-sm);
            transition: all var(--transition-fast);
            border: 1px solid transparent;
            cursor: pointer;
        }

        .order-item:hover {
            transform: translateX(3px);
            border-color: var(--primary-light);
            background: var(--white);
        }

        .order-info {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            flex-wrap: wrap;
        }

        .order-number {
            font-weight: var(--font-bold);
            color: var(--primary-color);
            font-size: var(--font-md);
        }

        .order-status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: var(--radius-round);
            font-size: var(--font-xs);
            font-weight: var(--font-medium);
        }

        .order-status.status-pending {
            background: var(--warning-light);
            color: var(--warning);
        }

        .order-status.status-processing {
            background: var(--info-light);
            color: var(--info);
        }

        .order-status.status-shipped {
            background: var(--primary-fade);
            color: var(--primary-color);
        }

        .order-status.status-completed {
            background: var(--success-light);
            color: var(--success);
        }

        .order-status.status-cancelled {
            background: var(--error-light);
            color: var(--error);
        }

        .order-products {
            flex: 2;
            min-width: 150px;
        }

        .product-names {
            font-size: var(--font-sm);
            color: var(--gray-dark);
            display: block;
            line-height: 1.4;
        }

        .product-count {
            font-size: var(--font-xs);
            color: var(--gray-light);
            display: block;
            margin-top: var(--spacing-xs);
        }

        .order-details {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            min-width: 120px;
        }

        .order-total {
            font-weight: var(--font-bold);
            color: var(--dark-bg);
            font-size: var(--font-md);
        }

        .order-date {
            font-size: var(--font-xs);
            color: var(--gray-light);
            margin-top: var(--spacing-xs);
        }

        /* Store Summary Card */
        .store-summary-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            border: 1px solid var(--border-light);
            box-shadow: var(--shadow-sm);
            margin-top: var(--spacing-xl);
        }

        .store-summary-header {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
            padding-bottom: var(--spacing-md);
            border-bottom: 1px solid var(--border-light);
        }

        .store-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            overflow: hidden;
            background: var(--primary-fade);
        }

        .store-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .store-info h3 {
            font-size: var(--font-lg);
            font-weight: var(--font-semibold);
            color: var(--dark-bg);
            margin-bottom: var(--spacing-xs);
        }

        .store-role {
            font-size: var(--font-sm);
            color: var(--gray-medium);
            margin-bottom: var(--spacing-xs);
        }

        .store-status.active {
            display: inline-block;
            padding: 2px 10px;
            border-radius: var(--radius-round);
            font-size: var(--font-xs);
            background: var(--success-light);
            color: var(--success);
        }

        .store-summary-actions {
            display: flex;
            gap: var(--spacing-sm);
        }

        .store-action-link {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-sm);
            background: var(--gray-bg-light);
            border-radius: var(--radius-md);
            text-decoration: none;
            color: var(--gray-dark);
            font-size: var(--font-sm);
            transition: all var(--transition-fast);
        }

        .store-action-link:hover {
            background: var(--primary-fade);
            color: var(--primary-color);
        }

        .store-action-link.logout:hover {
            background: var(--error-light);
            color: var(--error);
        }

        .store-action-link img {
            width: 18px;
            height: 18px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .stats-grid-seller {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-grid-seller {
                grid-template-columns: 1fr;
                gap: var(--spacing-sm);
            }

            .order-item {
                flex-direction: column;
                text-align: center;
            }

            .order-info {
                justify-content: center;
            }

            .order-products {
                width: 100%;
                text-align: center;
            }

            .order-details {
                align-items: center;
            }

            .store-summary-header {
                flex-direction: column;
                text-align: center;
            }

            .store-summary-actions {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .page-header h1 {
                font-size: var(--font-xl);
            }

            .product-title {
                font-size: var(--font-xs);
            }

            .product-price {
                font-size: var(--font-sm);
            }
        }
    </style>
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
</head>

<body class="seller-dashboard-page">

    <?php include 'includes/sidebar.php'; ?>

    <main class="seller-main-content">
        <div class="dashboard-content">
            <div class="page-header">
                <h1>Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h1>
                <p>Here's what's happening with your store today.</p>
            </div>

            <?php if (isset($_SESSION['flash'])): ?>
                <div class="flash-message"><?php echo $_SESSION['flash'];
                                            unset($_SESSION['flash']); ?></div>
            <?php endif; ?>

            <!-- ========== STATISTICS CARDS ========== -->
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

            <!-- ========== DASHBOARD MAIN GRID ========== -->
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
                        <a href="seller-orders.php" class="view-all-link">View All →</a>
                    </div>
                    <div class="orders-list" id="recent-orders-list">
                        <div class="loading-spinner">Loading recent orders...</div>
                    </div>
                </div>
            </div>

            <!-- ========== STORE SUMMARY CARD ========== -->
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
                        <img src="<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg" alt="Profile"> Edit Profile
                    </a>
                    <a href="<?php echo $baseUrl; ?>php/endpoints/auth/logout.php" class="store-action-link logout">
                        <img src="<?php echo $baseUrl; ?>images/icons/logout-svgrepo-com.svg" alt="Logout"> Logout
                    </a>
                </div>
            </div>
        </div>
    </main>

    <script>
        // ============================================================
        // DOM CACHE - Store all jQuery selectors for performance
        // ============================================================

        /**
         * DOM element references for seller dashboard.
         * All elements are cached once and reused throughout the page.
         */
        var $statEarnings = null,
            $statProducts = null,
            $statPending = null,
            $listingsGrid = null,
            $recentOrdersList = null,
            $sellerSideMenu = null,
            $sellerMenuOverlay = null;

        /**
         * Caches all DOM elements used on the seller dashboard.
         * Called once on page load to store jQuery references.
         */
        function cacheElements() {
            $statEarnings = $('#stat-earnings');
            $statProducts = $('#stat-products');
            $statPending = $('#stat-pending');
            $listingsGrid = $('#listings-grid');
            $recentOrdersList = $('#recent-orders-list');
            $sellerSideMenu = $('#sellerSideMenu');
            $sellerMenuOverlay = $('#sellerMenuOverlay');
        }

        // ============================================================
        // LOAD SELLER DASHBOARD
        // ============================================================

        /**
         * Loads all seller dashboard data.
         * Uses functions from dashboard.js with cached elements.
         */
        function loadSellerDashboardData() {
            // Set loading states
            if ($statEarnings && $statEarnings.length) $statEarnings.text('Loading...');
            if ($statProducts && $statProducts.length) $statProducts.text('Loading...');
            if ($statPending && $statPending.length) $statPending.text('Loading...');

            // Use the functions from dashboard.js
            if (typeof loadSellerStats === 'function') {
                loadSellerStats();
            }

            if (typeof window.loadSellerProducts === 'function') {
                window.loadSellerProducts(4);
            }

            if (typeof loadSellerRecentOrders === 'function') {
                loadSellerRecentOrders(5);
            } else if (typeof window.loadSellerRecentOrders === 'function') {
                window.loadSellerRecentOrders(5);
            }
        }

        // ============================================================
        // SIDEBAR HANDLING - Using Cached Elements
        // ============================================================

        /**
         * Closes the sidebar when interacting with modals or action buttons.
         * Preserves the sidebar state so it can be reopened.
         */
        $(document).on('click', '[data-modal-open], .view-details-btn, .process-btn, .ship-btn, .complete-btn, .cancel-btn, .delete-btn, .edit-btn', function() {
            if ($sellerSideMenu && $sellerSideMenu.length && $sellerSideMenu.hasClass('active')) {
                $sellerSideMenu.data('was-open', true);
                $sellerSideMenu.removeClass('active');
                if ($sellerMenuOverlay && $sellerMenuOverlay.length) {
                    $sellerMenuOverlay.removeClass('active');
                }
            }
        });

        /**
         * Reopens the sidebar after a modal closes if it was open before.
         */
        $(document).on('click', '.modal-close, .btn-close, .order-modal-close', function() {
            if ($sellerSideMenu && $sellerSideMenu.length && $sellerSideMenu.data('was-open') === true) {
                $sellerSideMenu.addClass('active');
                if ($sellerMenuOverlay && $sellerMenuOverlay.length) {
                    $sellerMenuOverlay.addClass('active');
                }
                $sellerSideMenu.removeData('was-open');
            }
        });

        // ============================================================
        // DOCUMENT READY - Initialize Everything
        // ============================================================

        $(document).ready(function() {
            cacheElements();
            loadSellerDashboardData();
        });
    </script>

</body>

</html>