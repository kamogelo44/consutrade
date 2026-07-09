<?php
$role_class = ($currentUser->getRole() === 'admin') ? 'admin' : 'seller';
$user_name = $currentUser->getFullName();
$user_role = $currentUser->getRole();
$profile_image_url = $currentUser->getProfileImageUrl();
$current_file = basename($_SERVER['PHP_SELF']);
$is_products_subpage = in_array($current_file, ['add-product.php', 'edit-product.php']);
$dashboard_home = ($user_role === 'admin') ? $baseUrl . 'admin/admin-dashboard.php' : $baseUrl . 'admin/seller-dashboard.php';
?>

<aside class="dashboard-sidebar <?php echo $role_class; ?>" id="dashboardSidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <a href="<?php echo $dashboard_home; ?>">Consu<span>Trade</span></a>
        </div>
        <button class="sidebar-close" id="sidebarClose">
            <span></span><span></span>
        </button>
    </div>

    <div class="sidebar-profile">
        <div class="sidebar-avatar">
            <img src="<?php echo $profile_image_url; ?>" alt="Avatar" onerror="this.src='<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg'">
        </div>
        <div class="sidebar-user-info">
            <span class="sidebar-name"><?php echo htmlspecialchars($user_name); ?></span>
            <span class="sidebar-role"><?php echo ucfirst($user_role); ?></span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <?php if ($user_role === 'admin'): ?>
                <li><a href="<?php echo $baseUrl; ?>admin/admin-dashboard.php" class="<?php echo $current_file == 'admin-dashboard.php' ? 'active' : ''; ?>"><img src="<?php echo $baseUrl; ?>images/icons/dashboard-svgrepo-com.svg" alt="Dashboard"> Dashboard</a></li>
                <li><a href="<?php echo $baseUrl; ?>admin/users.php" class="<?php echo $current_file == 'users.php' ? 'active' : ''; ?>"><img src="<?php echo $baseUrl; ?>images/icons/users-svgrepo-com.svg" alt="Users"> Users</a></li>
                <li><a href="<?php echo $baseUrl; ?>admin/all-products.php" class="<?php echo $current_file == 'all-products.php' ? 'active' : ''; ?>"><img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" alt="Products"> All Products</a></li>
                <li><a href="<?php echo $baseUrl; ?>admin/all-orders.php" class="<?php echo $current_file == 'all-orders.php' ? 'active' : ''; ?>"><img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" alt="Orders"> All Orders</a></li>
                <li><a href="<?php echo $baseUrl; ?>admin/flagged-listings.php" class="<?php echo $current_file == 'flagged-listings.php' ? 'active' : ''; ?>"><img src="<?php echo $baseUrl; ?>images/icons/warning-svgrepo-com.svg" alt="Flagged"> Flagged Listings</a></li>
            <?php else: ?>
                <li><a href="<?php echo $baseUrl; ?>admin/seller-dashboard.php" class="<?php echo $current_file == 'seller-dashboard.php' ? 'active' : ''; ?>"><img src="<?php echo $baseUrl; ?>images/icons/dashboard-svgrepo-com.svg" alt="Dashboard"> Dashboard</a></li>
                <li><a href="<?php echo $baseUrl; ?>admin/my-products.php" class="<?php echo ($current_file == 'my-products.php' || $is_products_subpage) ? 'active' : ''; ?>"><img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" alt="Products"> My Products</a></li>
                <li><a href="<?php echo $baseUrl; ?>orders.php?tab=seller" class="<?php echo $current_file == 'orders.php' ? 'active' : ''; ?>"><img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" alt="Orders"> Orders</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <a href="<?php echo $baseUrl; ?>profile.php" class="sidebar-footer-link">
            <img src="<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg" alt="Profile"> My Profile
        </a>
        <a href="<?php echo $baseUrl; ?>php/endpoints/auth/logout.php" class="sidebar-footer-link logout">
            <img src="<?php echo $baseUrl; ?>images/icons/logout-svgrepo-com.svg" alt="Logout"> Logout
        </a>
    </div>
</aside>

<button class="sidebar-mobile-toggle" id="sidebarToggle">
    <span></span><span></span><span></span>
</button>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script>
    var baseUrl = '<?php echo $baseUrl; ?>';
    var currentUserId = <?php echo $currentUser->getUserId(); ?>;
</script>
<script src="<?php echo $baseUrl; ?>js/lib/jquery-3.7.1.min.js"></script>
<script src="<?php echo $baseUrl; ?>js/core/utils.js"></script>
<script src="<?php echo $baseUrl; ?>js/core/ui.js"></script>
<script>
    $(function() {
        var $toggle = $('#sidebarToggle');
        var $sidebar = $('#dashboardSidebar');
        var $overlay = $('#sidebarOverlay');
        var $close = $('#sidebarClose');

        function open() {
            $sidebar.addClass('active');
            $overlay.addClass('active');
            $('body').css('overflow', 'hidden');
        }

        function close() {
            $sidebar.removeClass('active');
            $overlay.removeClass('active');
            $('body').css('overflow', '');
        }

        $toggle.on('click', open);
        $close.on('click', close);
        $overlay.on('click', close);
    });
</script>