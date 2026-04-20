/*
 * ConsuTrade - Admin JavaScript File
 * Author: Kamogelo Phale
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ========== ADMIN DROPDOWN TOGGLE ==========
    var adminUserInfo = document.getElementById('adminUserInfo');
    var adminDropdownMenu = document.getElementById('adminDropdownMenu');
    
    if (adminUserInfo && adminDropdownMenu) {
        adminUserInfo.addEventListener('click', function(e) {
            e.stopPropagation();
            adminDropdownMenu.classList.toggle('show');
        });
        
        document.addEventListener('click', function(e) {
            if (adminUserInfo && adminDropdownMenu) {
                if (!adminUserInfo.contains(e.target) && !adminDropdownMenu.contains(e.target)) {
                    adminDropdownMenu.classList.remove('show');
                }
            }
        });
    }
    
    // ========== MOBILE MENU TOGGLE ==========
    var hamburger = document.getElementById('hamburger');
    var sideMenuHamburger = document.getElementById('sideMenuHamburger');
    var mobileSideMenu = document.getElementById('mobileSideMenu');
    var menuOverlay = document.getElementById('menuOverlay');
    
    function toggleMenu() {
        hamburger.classList.toggle('active');
        if (sideMenuHamburger) sideMenuHamburger.classList.toggle('active');
        mobileSideMenu.classList.toggle('active');
        menuOverlay.classList.toggle('active');
        
        if (mobileSideMenu.classList.contains('active')) {
            document.body.classList.add('admin-menu-open');
            document.body.style.overflow = 'hidden';
        } else {
            document.body.classList.remove('admin-menu-open');
            document.body.style.overflow = '';
        }
    }
    
    if (hamburger && mobileSideMenu && menuOverlay) {
        hamburger.addEventListener('click', toggleMenu);
        if (sideMenuHamburger) sideMenuHamburger.addEventListener('click', toggleMenu);
        menuOverlay.addEventListener('click', toggleMenu);
        
        document.querySelectorAll('.mobile-nav-links a').forEach(function(link) {
            link.addEventListener('click', toggleMenu);
        });
    }
    
    // ========== LOAD DASHBOARD STATISTICS ==========
    if (window.location.pathname.includes('admin-dashboard.php')) {
        loadDashboardStats();
        loadRecentUsers();
        loadRecentOrders();
    }
    
    function loadDashboardStats() {
        fetch('get-stats.php')
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    var totalUsers = document.getElementById('totalUsers');
                    var totalProducts = document.getElementById('totalProducts');
                    var totalOrders = document.getElementById('totalOrders');
                    var pendingOrders = document.getElementById('pendingOrders');
                    
                    if (totalUsers) totalUsers.textContent = data.total_users || 0;
                    if (totalProducts) totalProducts.textContent = data.total_products || 0;
                    if (totalOrders) totalOrders.textContent = data.total_orders || 0;
                    if (pendingOrders) pendingOrders.textContent = data.pending_orders || 0;
                }
            })
            .catch(function() {
                var totalUsers = document.getElementById('totalUsers');
                var totalProducts = document.getElementById('totalProducts');
                var totalOrders = document.getElementById('totalOrders');
                var pendingOrders = document.getElementById('pendingOrders');
                
                if (totalUsers) totalUsers.textContent = '--';
                if (totalProducts) totalProducts.textContent = '--';
                if (totalOrders) totalOrders.textContent = '--';
                if (pendingOrders) pendingOrders.textContent = '--';
            });
    }
    
    function loadRecentUsers() {
        fetch('get-recent-users.php')
            .then(function(response) { return response.json(); })
            .then(function(data) {
                var tbody = document.getElementById('recent-users-table');
                if (!tbody) return;
                
                if (data.success && data.users && data.users.length > 0) {
                    tbody.innerHTML = '';
                    for (var i = 0; i < data.users.length; i++) {
                        var user = data.users[i];
                        var row = '<tr>' +
                            '<td>' + escapeHtml(user.full_name) + '</td>' +
                            '<td>' + escapeHtml(user.email) + '</td>' +
                            '<td><span class="role-badge role-' + user.role + '">' + capitalizeFirst(user.role) + '</span></td>' +
                            '<td>' + user.created_at + '</td>' +
                            '</tr>';
                        tbody.innerHTML += row;
                    }
                } else {
                    tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">No users found</td></tr>';
                }
            })
            .catch(function() {
                var tbody = document.getElementById('recent-users-table');
                if (tbody) {
                    tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">Error loading users</td></tr>';
                }
            });
    }
    
    function loadRecentOrders() {
        fetch('get-recent-orders.php')
            .then(function(response) { return response.json(); })
            .then(function(data) {
                var tbody = document.getElementById('recent-orders-table');
                if (!tbody) return;
                
                if (data.success && data.orders && data.orders.length > 0) {
                    tbody.innerHTML = '';
                    for (var i = 0; i < data.orders.length; i++) {
                        var order = data.orders[i];
                        var row = '<tr>' +
                            '<td>#' + order.order_id + '</td>' +
                            '<td>' + escapeHtml(order.buyer_name) + '</td>' +
                            '<td>R ' + parseFloat(order.total_price).toFixed(2) + '</td>' +
                            '<td><span class="status-badge status-' + order.status + '">' + capitalizeFirst(order.status) + '</span></td>' +
                            '<td>' + order.created_at + '</td>' +
                            '</tr>';
                        tbody.innerHTML += row;
                    }
                } else {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No orders found</td></tr>';
                }
            })
            .catch(function() {
                var tbody = document.getElementById('recent-orders-table');
                if (tbody) {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">Error loading orders</td></tr>';
                }
            });
    }
    
    function capitalizeFirst(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});