/*
 * ConsuTrade - Admin JavaScript File
 * Author: Kamogelo Phale
 * 
 * This file handles admin-specific functionality:
 * - Admin dropdown toggle
 * - Mobile sidebar toggle (for responsive design)
 * - Dashboard statistics loading using PHP endpoints (same pattern as main.js)
 * 
 * Note: This file is only used in the admin dashboard area
 */

// Wait for the page to finish loading - same approach as main.js
document.addEventListener('DOMContentLoaded', function() {
    
    // ========== ADMIN DROPDOWN TOGGLE ==========
    // Took me a while to figure out how to close dropdown when clicking outside
    // Had to use event listeners properly - same pattern as main site dropdown
    
    var adminUserInfo = document.getElementById('adminUserInfo');
    var adminDropdownMenu = document.getElementById('adminDropdownMenu');
    
    if (adminUserInfo && adminDropdownMenu) {
        adminUserInfo.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevents the click from bubbling up and immediately closing
            adminDropdownMenu.classList.toggle('show');
        });
        
        // Close dropdown when clicking outside - learned this from main.js
        document.addEventListener('click', function(e) {
            if (adminUserInfo && adminDropdownMenu) {
                if (!adminUserInfo.contains(e.target) && !adminDropdownMenu.contains(e.target)) {
                    adminDropdownMenu.classList.remove('show');
                }
            }
        });
    }
    
    // ========== MOBILE SIDEBAR TOGGLE ==========
    // For responsive design - hides/shows sidebar on mobile devices
    // TODO: Add a hamburger button in the admin header for mobile if needed
    
    var adminMenuToggle = document.getElementById('adminMenuToggle');
    var adminSidebar = document.querySelector('.admin-sidebar');
    
    if (adminMenuToggle && adminSidebar) {
        adminMenuToggle.addEventListener('click', function() {
            adminSidebar.classList.toggle('active');
        });
    }
    
    // ========== LOAD DASHBOARD STATISTICS ==========
    // Only run this on the admin dashboard page
    // Uses fetch() to get data from PHP files - same pattern as cart functions in main.js
    
    if (window.location.pathname.includes('admin-dashboard.php')) {
        loadDashboardStats();
    }
    
    function loadDashboardStats() {
        // Fetch statistics from PHP file - just like get-cart.php in main.js
        fetch('get-stats.php')
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    // Update the stats cards with real data
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
            .catch(function(error) {
                console.log('Error loading stats:', error);
                // Set default values if fetch fails - better than showing nothing
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
    
    // TODO: Features I want to add later for admin:
    // 1. User search and filter functionality
    // 2. Bulk product management (delete, approve multiple at once)
    // 3. Order status update without page reload (AJAX)
    // 4. Real-time notification for new orders
});