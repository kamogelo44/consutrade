<?php
/*
 * ConsuTrade - Admin Dashboard
 * Author: Kamogelo Phale
 * 
 * Main admin dashboard page showing marketplace statistics
 */

require_once dirname(__DIR__) . '/init.php';

// Check if admin is logged in using centralized auth
if (!$is_logged_in || $current_user['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$baseUrl = getBaseUrl();
$user_id = $current_user_id;

// Get user data
$user = getUserById($conn, $user_id);
$profile_image = getUserProfileImage($user['profile_image'] ?? null);

// Set current page for active sidebar link
$current_page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ConsuTrade</title>
    <meta name="author" content="Kamogelo Phale">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/dashboard.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <script>
        var baseUrl = '<?php echo $baseUrl; ?>';
    </script>
</head>
<body class="admin-dashboard-page">

<?php include 'includes/sidebar.php'; ?>

<main class="dashboard-main">
    <div class="dashboard-content">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h2>Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</h2>
            <p>Here's what's happening with your marketplace today.</p>
        </div>

        <!-- Stats Section -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <img src="<?php echo $baseUrl; ?>images/icons/users-svgrepo-com.svg" alt="Users" class="stat-icon-img">
                </div>
                <div class="stat-info">
                    <h3>Total Users</h3>
                    <p class="stat-number" id="totalUsers">--</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" alt="Products" class="stat-icon-img">
                </div>
                <div class="stat-info">
                    <h3>Total Products</h3>
                    <p class="stat-number" id="totalProducts">--</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" alt="Orders" class="stat-icon-img">
                </div>
                <div class="stat-info">
                    <h3>Total Orders</h3>
                    <p class="stat-number" id="totalOrders">--</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <img src="<?php echo $baseUrl; ?>images/icons/clock-svgrepo-com.svg" alt="Pending" class="stat-icon-img">
                </div>
                <div class="stat-info">
                    <h3>Pending Orders</h3>
                    <p class="stat-number pending" id="pendingOrders">--</p>
                </div>
            </div>
        </div>

        <!-- Stats Row 2 -->
        <div class="stats-grid" style="margin-top: 20px;">
            <div class="stat-card">
                <div class="stat-icon">
                    <img src="<?php echo $baseUrl; ?>images/icons/money-total-line-svgrepo-com.svg" alt="Revenue" class="stat-icon-img">
                </div>
                <div class="stat-info">
                    <h3>Total Revenue</h3>
                    <p class="stat-number" id="totalRevenue">R0.00</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <img src="<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg" alt="Sellers" class="stat-icon-img">
                </div>
                <div class="stat-info">
                    <h3>Total Sellers</h3>
                    <p class="stat-number" id="totalSellers">--</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" alt="Completed" class="stat-icon-img">
                </div>
                <div class="stat-info">
                    <h3>Completed Orders</h3>
                    <p class="stat-number" id="completedOrders">--</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" alt="Active Products" class="stat-icon-img">
                </div>
                <div class="stat-info">
                    <h3>Active Products</h3>
                    <p class="stat-number" id="activeProducts">--</p>
                </div>
            </div>
        </div>

        <!-- Dashboard Sections -->
        <div class="dashboard-sections">
            <!-- Recent Users Section -->
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
                            <tr><td colspan="4" style="text-align: center;">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Orders Section -->
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
                            <tr><td colspan="5" style="text-align: center;">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Quick Actions Section -->
            <div class="section-card">
                <div class="section-header">
                    <h2>Quick Actions</h2>
                </div>
                <div class="profile-shortcuts">
                    <a href="users.php" class="profile-shortcut-link">
                        <div class="profile-shortcut-icon">
                            <img src="<?php echo $baseUrl; ?>images/icons/users-svgrepo-com.svg" alt="Users">
                        </div>
                        <div class="profile-shortcut-info">
                            <h3>Manage Users</h3>
                            <p>View and manage all users</p>
                        </div>
                        <span class="profile-shortcut-arrow">→</span>
                    </a>
                    <a href="all-products.php" class="profile-shortcut-link">
                        <div class="profile-shortcut-icon">
                            <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" alt="Products">
                        </div>
                        <div class="profile-shortcut-info">
                            <h3>All Products</h3>
                            <p>Manage marketplace products</p>
                        </div>
                        <span class="profile-shortcut-arrow">→</span>
                    </a>
                    <a href="all-orders.php" class="profile-shortcut-link">
                        <div class="profile-shortcut-icon">
                            <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" alt="Orders">
                        </div>
                        <div class="profile-shortcut-info">
                            <h3>All Orders</h3>
                            <p>Track and manage orders</p>
                        </div>
                        <span class="profile-shortcut-arrow">→</span>
                    </a>
                    <a href="admin-profile.php" class="profile-shortcut-link">
                        <div class="profile-shortcut-icon">
                            <img src="<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg" alt="Profile">
                        </div>
                        <div class="profile-shortcut-info">
                            <h3>My Profile</h3>
                            <p>Update your account settings</p>
                        </div>
                        <span class="profile-shortcut-arrow">→</span>
                    </a>
                    <a href="<?php echo $baseUrl; ?>admin/php/admin-logout.php" class="profile-shortcut-link logout-link">
                        <div class="profile-shortcut-icon">
                            <img src="<?php echo $baseUrl; ?>images/icons/logout-svgrepo-com.svg" alt="Logout">
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
    </div>
</main>

<script src="<?php echo $baseUrl; ?>js/main.js"></script>
<script src="<?php echo $baseUrl; ?>admin/js/dashboard.js"></script>
</body>
</html>