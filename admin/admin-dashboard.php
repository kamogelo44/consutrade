<?php
/*
 * ConsuTrade - Admin Dashboard
 * Author: Kamogelo Phale
 * 
 * Main admin dashboard page
 */

require_once 'admin-header.php';
?>

<div class="dashboard-welcome">
    <h1>Admin Dashboard</h1>
    <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>! Here's what's happening with your marketplace today.</p>
</div>

<div class="admin-stats">
    <div class="stat-card">
        <h3>Total Users</h3>
        <p class="stat-number" id="totalUsers">0</p>
    </div>
    <div class="stat-card">
        <h3>Total Products</h3>
        <p class="stat-number" id="totalProducts">0</p>
    </div>
    <div class="stat-card">
        <h3>Total Orders</h3>
        <p class="stat-number" id="totalOrders">0</p>
    </div>
    <div class="stat-card">
        <h3>Pending Orders</h3>
        <p class="stat-number" id="pendingOrders">0</p>
    </div>
</div>

<!-- I will put admin dashboard content here -->

<?php
// Close the main content
?>
</main>

<script src="<?php echo $baseUrl; ?>js/admin.js"></script>
</body>
</html>