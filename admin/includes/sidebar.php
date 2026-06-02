<?php
/*
 * ConsuTrade - Unified Sidebar Component
 * Author: Kamogelo Phale
 */

if (!isset($currentUser) || !$currentUser instanceof User) {
    $role_class = 'seller';
    $user_name = 'User';
    $user_role = 'seller';
    $profile_image_url = $baseUrl . 'images/icons/profile-svgrepo-com.svg';
} else {
    $role_class = $currentUser->getRole() === 'admin' ? 'admin' : 'seller';
    $user_name = $currentUser->getFullName();
    $user_role = $currentUser->getRole();
    $profile_image_url = $currentUser->getProfileImageUrl();
}

$current_file = basename($_SERVER['PHP_SELF']);
$is_products_subpage = in_array($current_file, ['add-product.php', 'edit-product.php']);
$dashboard_home = ($user_role === 'admin') ? $baseUrl . 'admin/admin-dashboard.php' : $baseUrl . 'admin/seller-dashboard.php';
?>

<div class="<?php echo $role_class; ?>-sidebar" id="<?php echo $role_class; ?>SideMenu">
    <div class="<?php echo $role_class; ?>-sidebar-header">
        <div class="<?php echo $role_class; ?>-sidebar-logo">
            <a href="<?php echo $dashboard_home; ?>">Consu<span>Trade</span></a>
        </div>
        <button class="<?php echo $role_class; ?>-sidebar-close" id="<?php echo $role_class; ?>SidebarClose">
            <span></span><span></span>
        </button>
    </div>

    <div class="<?php echo $role_class; ?>-sidebar-profile">
        <div class="<?php echo $role_class; ?>-sidebar-avatar">
            <img src="<?php echo $profile_image_url; ?>" alt="Avatar" onerror="this.src='<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg'">
        </div>
        <div class="<?php echo $role_class; ?>-sidebar-user-info">
            <span class="<?php echo $role_class; ?>-sidebar-name"><?php echo htmlspecialchars($user_name); ?></span>
            <span class="<?php echo $role_class; ?>-sidebar-role"><?php echo ucfirst($user_role); ?></span>
        </div>
    </div>

    <div class="<?php echo $role_class; ?>-sidebar-nav">
        <ul>
            <?php if ($user_role === 'admin'): ?>
                <li><a href="<?php echo $baseUrl; ?>admin/admin-dashboard.php" class="<?php echo $current_file == 'admin-dashboard.php' ? 'active' : ''; ?>"><img src="<?php echo $baseUrl; ?>images/icons/dashboard-svgrepo-com.svg" alt="Dashboard"> Dashboard</a></li>
                <li><a href="<?php echo $baseUrl; ?>admin/users.php" class="<?php echo $current_file == 'users.php' ? 'active' : ''; ?>"><img src="<?php echo $baseUrl; ?>images/icons/users-svgrepo-com.svg" alt="Users"> Users</a></li>
                <li><a href="<?php echo $baseUrl; ?>admin/all-products.php" class="<?php echo $current_file == 'all-products.php' ? 'active' : ''; ?>"><img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" alt="Products"> All Products</a></li>
                <li><a href="<?php echo $baseUrl; ?>admin/all-orders.php" class="<?php echo $current_file == 'all-orders.php' ? 'active' : ''; ?>"><img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" alt="Orders"> All Orders</a></li>
            <?php else: ?>
                <li><a href="<?php echo $baseUrl; ?>admin/seller-dashboard.php" class="<?php echo $current_file == 'seller-dashboard.php' ? 'active' : ''; ?>"><img src="<?php echo $baseUrl; ?>images/icons/dashboard-svgrepo-com.svg" alt="Dashboard"> Dashboard</a></li>
                <li><a href="<?php echo $baseUrl; ?>admin/my-products.php" class="<?php echo ($current_file == 'my-products.php' || $is_products_subpage) ? 'active' : ''; ?>"><img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" alt="Products"> My Products</a></li>
                <li><a href="<?php echo $baseUrl; ?>admin/my-orders.php" class="<?php echo $current_file == 'my-orders.php' ? 'active' : ''; ?>"><img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" alt="Orders"> My Orders</a></li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="<?php echo $role_class; ?>-sidebar-footer">
        <?php if ($user_role === 'admin'): ?>
            <a href="<?php echo $baseUrl; ?>admin/admin-profile.php" class="<?php echo $role_class; ?>-sidebar-link <?php echo $current_file == 'admin-profile.php' ? 'active' : ''; ?>">
                <img src="<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg" alt="Profile"> Profile Settings
            </a>
        <?php else: ?>
            <a href="<?php echo $baseUrl; ?>admin/seller-profile.php" class="<?php echo $role_class; ?>-sidebar-link <?php echo $current_file == 'seller-profile.php' ? 'active' : ''; ?>">
                <img src="<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg" alt="Profile"> Profile Settings
            </a>
        <?php endif; ?>
        <a href="<?php echo $baseUrl; ?>php/endpoints/logout.php" class="<?php echo $role_class; ?>-sidebar-link logout">
            <img src="<?php echo $baseUrl; ?>images/icons/logout-svgrepo-com.svg" alt="Logout"> Logout
        </a>
    </div>
</div>

<button class="<?php echo $role_class; ?>-mobile-toggle" id="<?php echo $role_class; ?>Hamburger">
    <span></span><span></span><span></span>
</button>

<div class="<?php echo $role_class; ?>-menu-overlay" id="<?php echo $role_class; ?>MenuOverlay"></div>

<script src="<?php echo $baseUrl; ?>js/main.js"></script>
<script>
    var baseUrl = '<?php echo $baseUrl; ?>';
    var currentUserId = <?php echo isset($currentUser) ? $currentUser->getUserId() : 0; ?>;
    var currentUserRole = '<?php echo isset($currentUser) ? $currentUser->getRole() : ''; ?>';
    var isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
</script>
<script src="<?php echo $baseUrl; ?>admin/js/dashboard.js"></script>