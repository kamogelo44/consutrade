<?php
/*
 * ConsuTrade - Unified Sidebar Component
 * Author: Kamogelo Phale
 * 
 * This sidebar is used for both Admin and Seller dashboards.
 * Mobile behavior: Hamburger opens sidebar, X button inside closes it
 */

$role_class = $_SESSION['role'] === 'admin' ? 'admin' : 'seller';
$profile_image = $profile_image ?? $baseUrl . 'images/icons/profile-svgrepo-com.svg';

// Map current page for active link highlighting
$current_file = basename($_SERVER['PHP_SELF']);

// Check if we're on a sub-page of My Products (keep active state)
$is_products_subpage = in_array($current_file, ['add-product.php', 'edit-product.php']);

// Dashboard home link based on role
$dashboard_home = ($_SESSION['role'] === 'admin') ? 'admin-dashboard.php' : 'seller-dashboard.php';
?>

<div class="<?php echo $role_class; ?>-sidebar" id="<?php echo $role_class; ?>SideMenu">
    <!-- Sidebar Header - Logo on left, Close button (X) on right -->
    <div class="<?php echo $role_class; ?>-sidebar-header">
        <div class="<?php echo $role_class; ?>-sidebar-logo">
            <a href="<?php echo $dashboard_home; ?>">Consu<span>Trade</span></a>
        </div>
        <!-- Close button (X) - only visible on mobile to close sidebar -->
        <button class="<?php echo $role_class; ?>-sidebar-close" id="<?php echo $role_class; ?>SidebarClose">
            <span></span><span></span>
        </button>
    </div>
    
    <!-- User Profile Section -->
    <div class="<?php echo $role_class; ?>-sidebar-profile">
        <div class="<?php echo $role_class; ?>-sidebar-avatar">
            <img src="<?php echo $profile_image; ?>" alt="Avatar" onerror="this.src='<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg'">
        </div>
        <div class="<?php echo $role_class; ?>-sidebar-user-info">
            <span class="<?php echo $role_class; ?>-sidebar-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></span>
            <span class="<?php echo $role_class; ?>-sidebar-role"><?php echo ucfirst($_SESSION['role'] ?? 'User'); ?></span>
        </div>
    </div>
    
    <!-- Navigation Menu -->
    <div class="<?php echo $role_class; ?>-sidebar-nav">
        <ul>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <!-- Admin Navigation -->
                <li>
                    <a href="admin-dashboard.php" class="<?php echo $current_file == 'admin-dashboard.php' ? 'active' : ''; ?>">
                        <img src="<?php echo $baseUrl; ?>images/icons/dashboard-svgrepo-com.svg" alt="Dashboard"> Dashboard
                    </a>
                </li>
                <li>
                    <a href="users.php" class="<?php echo $current_file == 'users.php' ? 'active' : ''; ?>">
                        <img src="<?php echo $baseUrl; ?>images/icons/users-svgrepo-com.svg" alt="Users"> Users
                    </a>
                </li>
                <li>
                    <a href="all-products.php" class="<?php echo $current_file == 'all-products.php' ? 'active' : ''; ?>">
                        <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" alt="Products"> All Products
                    </a>
                </li>
                <li>
                    <a href="all-orders.php" class="<?php echo $current_file == 'all-orders.php' ? 'active' : ''; ?>">
                        <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" alt="Orders"> All Orders
                    </a>
                </li>
            <?php else: ?>
                <!-- Seller Navigation -->
                <li>
                    <a href="seller-dashboard.php" class="<?php echo $current_file == 'seller-dashboard.php' ? 'active' : ''; ?>">
                        <img src="<?php echo $baseUrl; ?>images/icons/dashboard-svgrepo-com.svg" alt="Dashboard"> Dashboard
                    </a>
                </li>
                <li>
                    <a href="my-products.php" class="<?php echo ($current_file == 'my-products.php' || $is_products_subpage) ? 'active' : ''; ?>">
                        <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" alt="Products"> My Products
                    </a>
                </li>
                <li>
                    <a href="my-orders.php" class="<?php echo $current_file == 'my-orders.php' ? 'active' : ''; ?>">
                        <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" alt="Orders"> My Orders
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
    
    <!-- Footer Links -->
    <div class="<?php echo $role_class; ?>-sidebar-footer">
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="admin-profile.php" class="<?php echo $role_class; ?>-sidebar-link <?php echo $current_file == 'admin-profile.php' ? 'active' : ''; ?>">
                <img src="<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg" alt="Profile"> Profile Settings
            </a>
            <a href="php/admin-logout.php" class="<?php echo $role_class; ?>-sidebar-link logout">
                <img src="<?php echo $baseUrl; ?>images/icons/logout-svgrepo-com.svg" alt="Logout"> Logout
            </a>
        <?php else: ?>
            <a href="seller-profile.php" class="<?php echo $role_class; ?>-sidebar-link <?php echo $current_file == 'seller-profile.php' ? 'active' : ''; ?>">
                <img src="<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg" alt="Profile"> My Profile
            </a>
            <a href="php/seller-logout.php" class="<?php echo $role_class; ?>-sidebar-link logout">
                <img src="<?php echo $baseUrl; ?>images/icons/logout-svgrepo-com.svg" alt="Logout"> Logout
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Mobile Toggle Button (Hamburger) - Opens sidebar -->
<button class="<?php echo $role_class; ?>-mobile-toggle" id="<?php echo $role_class; ?>Hamburger">
    <span></span><span></span><span></span>
</button>

<!-- Menu Overlay - Dims background when sidebar is open -->
<div class="<?php echo $role_class; ?>-menu-overlay" id="<?php echo $role_class; ?>MenuOverlay"></div>

<!-- Main Content Container -->
<main class="<?php echo $role_class; ?>-main-content">