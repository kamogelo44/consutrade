<?php
/*
 * ConsuTrade - Seller Sidebar Component
 * Author: Kamogelo Phale
 * 
 * Reusable sidebar for seller dashboard pages
 * Requires: $current_page variable to be set before including
 * 
 * Usage:
 *   $current_page = 'dashboard'; // or 'products', 'orders', 'add-product'
 *   include 'admin/includes/seller-sidebar.php';
 */
?>
<!-- Mobile Toggle Button with spans for smooth animation -->
<button class="seller-mobile-toggle" id="sellerHamburger">
    <span></span>
    <span></span>
    <span></span>
</button>

<!-- Main Dashboard Wrapper -->
<div class="seller-dashboard">
    <!-- Sidebar -->
    <aside class="seller-sidebar" id="sellerSideMenu">
        <div class="seller-sidebar-header">
            <div class="seller-sidebar-logo">
                <a href="<?php echo $baseUrl; ?>admin/seller-dashboard.php">Consu<span>TradeSeller</span></a>
            </div>
            <!-- Close button for mobile - using spans for smooth transition -->
            <button class="seller-sidebar-close" id="sellerSidebarClose">
                <span></span>
                <span></span>
            </button>
        </div>
        
        <nav class="seller-sidebar-nav">
            <ul>
                <li>
                    <a href="<?php echo $baseUrl; ?>admin/seller-dashboard.php" class="<?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                        <img src="<?php echo $baseUrl; ?>images/icons/dashboard-svgrepo-com.svg" width="20px" height="20px" alt="Dashboard" onerror="this.style.display='none'">
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo $baseUrl; ?>admin/my-products.php" class="<?php echo $current_page === 'products' ? 'active' : ''; ?>">
                        <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" width="20px" height="20px" alt="Products" onerror="this.style.display='none'">
                        <span>My Products</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo $baseUrl; ?>admin/my-orders.php" class="<?php echo $current_page === 'orders' ? 'active' : ''; ?>">
                        <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" width="20px" height="20px" alt="Orders" onerror="this.style.display='none'">
                        <span>My Orders</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo $baseUrl; ?>admin/add-product.php" class="<?php echo $current_page === 'add-product' ? 'active' : ''; ?>">
                        <img src="<?php echo $baseUrl; ?>images/icons/add-svgrepo-com.svg" width="20px" height="20px" alt="Add Product" onerror="this.style.display='none'">
                        <span>Add Product</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <div class="seller-sidebar-footer">
            <a href="<?php echo $baseUrl; ?>profile.php" class="seller-sidebar-link">
                <img src="<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg" alt="Profile" width="20px" height="20px" onerror="this.style.display='none'">
                <span>My Profile</span>
            </a>
            <a href="<?php echo $baseUrl; ?>php/logout.php" class="seller-sidebar-link logout">
                <img src="<?php echo $baseUrl; ?>images/icons/logout-svgrepo-com.svg" alt="Logout" width="20px" height="20px" onerror="this.style.display='none'">
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- Overlay for mobile -->
    <div class="seller-menu-overlay" id="sellerMenuOverlay"></div>

    <!-- Main Content -->
    <main class="seller-main-content">