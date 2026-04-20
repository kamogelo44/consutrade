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
        <div class="stat-icon">
            <img src="<?php echo $baseUrl; ?>images/icons/users-svgrepo-com.svg" width="32px" height="32px" alt="Users">
        </div>
        <div class="stat-info">
            <h3>Total Users</h3>
            <p class="stat-number" id="totalUsers">--</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">
            <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" width="32px" height="32px" alt="Products">
        </div>
        <div class="stat-info">
            <h3>Total Products</h3>
            <p class="stat-number" id="totalProducts">--</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">
            <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" width="32px" height="32px" alt="Orders">
        </div>
        <div class="stat-info">
            <h3>Total Orders</h3>
            <p class="stat-number" id="totalOrders">--</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">
            <img src="<?php echo $baseUrl; ?>images/icons/clock-svgrepo-com.svg" width="32px" height="32px" alt="Pending">
        </div>
        <div class="stat-info">
            <h3>Pending Orders</h3>
            <p class="stat-number pending" id="pendingOrders">--</p>
        </div>
    </div>
</div>

<!-- Recent Activity Section -->
<div class="admin-recent">
    <div class="recent-card">
        <h2>Recent Users</h2>
        <div class="recent-table-wrapper">
            <table class="recent-table">
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
                        <td colspan="4" style="text-align: center;">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <a href="users.php" class="view-all-link">View All Users →</a>
    </div>

    <div class="recent-card">
        <h2>Recent Orders</h2>
        <div class="recent-table-wrapper">
            <table class="recent-table">
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
                        <td colspan="5" style="text-align: center;">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <a href="all-orders.php" class="view-all-link">View All Orders →</a>
    </div>
</div>

</main>

<script src="<?php echo $baseUrl; ?>admin/js/admin.js"></script>
<script>
    // Load dashboard data when page loads
    document.addEventListener('DOMContentLoaded', function() {
        loadDashboardStats();
        loadRecentUsers();
        loadRecentOrders();
    });

    function loadDashboardStats() {
        fetch('get-stats.php')
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    document.getElementById('totalUsers').textContent = data.total_users || 0;
                    document.getElementById('totalProducts').textContent = data.total_products || 0;
                    document.getElementById('totalOrders').textContent = data.total_orders || 0;
                    document.getElementById('pendingOrders').textContent = data.pending_orders || 0;
                }
            })
            .catch(function(error) {
                console.log('Error loading stats:', error);
            });
    }

    function loadRecentUsers() {
        fetch('get-recent-users.php')
            .then(function(response) { return response.json(); })
            .then(function(data) {
                var tbody = document.getElementById('recent-users-table');
                if (data.success && data.users.length > 0) {
                    tbody.innerHTML = '';
                    for (var i = 0; i < data.users.length; i++) {
                        var user = data.users[i];
                        var row = '<tr>' +
                            '<td>' + escapeHtml(user.full_name) + '</td>' +
                            '<td>' + escapeHtml(user.email) + '</td>' +
                            '<td><span class="role-badge role-' + user.role + '">' + ucfirst(user.role) + '</span></td>' +
                            '<td>' + user.created_at + '</td>' +
                            '</tr>';
                        tbody.innerHTML += row;
                    }
                } else {
                    tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">No users found</td></tr>';
                }
            })
            .catch(function(error) {
                console.log('Error loading users:', error);
            });
    }

    function loadRecentOrders() {
        fetch('get-recent-orders.php')
            .then(function(response) { return response.json(); })
            .then(function(data) {
                var tbody = document.getElementById('recent-orders-table');
                if (data.success && data.orders.length > 0) {
                    tbody.innerHTML = '';
                    for (var i = 0; i < data.orders.length; i++) {
                        var order = data.orders[i];
                        var row = '<tr>' +
                            '<td>#' + order.order_id + '</td>' +
                            '<td>' + escapeHtml(order.buyer_name) + '</td>' +
                            '<td>R ' + parseFloat(order.total_price).toFixed(2) + '</td>' +
                            '<td><span class="status-badge status-' + order.status + '">' + ucfirst(order.status) + '</span></td>' +
                            '<td>' + order.created_at + '</td>' +
                            '</tr>';
                        tbody.innerHTML += row;
                    }
                } else {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No orders found</td></tr>';
                }
            })
            .catch(function(error) {
                console.log('Error loading orders:', error);
            });
    }

    function ucfirst(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>
</body>
</html>