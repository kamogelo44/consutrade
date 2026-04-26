<?php
/*
 * ConsuTrade - Admin Dashboard
 * Author: Kamogelo Phale
 * 
 * Main admin dashboard page
 */

require_once dirname(__DIR__) . '/php/helpers.php';

// isAdminLoggedIn() starts the session automatically
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}


$baseUrl = getBaseUrl();

// Get database connection
require_once dirname(__DIR__) . '/php/config.php';

// Get user data
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
    <title>Admin Dashboard - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/dashboard.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <script>
        var baseUrl = '<?php echo $baseUrl; ?>';
    </script>
</head>
<body class="admin-dashboard-page">

<?php include 'includes/sidebar.php'; ?>

<!-- Main Content -->
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

</main> <!-- Close main tag opened in sidebar.php -->
</div> <!-- Close dashboard div opened in sidebar.php -->

<script src="<?php echo $baseUrl; ?>js/main.js"></script>
<script src="<?php echo $baseUrl; ?>admin/js/dashboard.js"></script>
</body>
</html>