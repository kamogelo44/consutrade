<?php
/*
 * ConsuTrade - Header Component
 * Author: Kamogelo Phale
 * 
 * This file contains the header HTML and navigation for all pages
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start(); 
}
$baseUrl = "/www/consutrade/";

// Determine if we're in the admin/dashboard area
$is_admin_area = strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;

// Determine current page for active links
$current_page = basename($_SERVER['REQUEST_URI']);
$current_page = strtok($current_page, '?');
$current_dashboard_page = '';

if ($is_admin_area) {
    if ($current_page === 'seller-dashboard.php') {
        $current_dashboard_page = 'Dashboard';
    } elseif ($current_page === 'my-orders.php') {
        $current_dashboard_page = 'Orders';
    } elseif ($current_page === 'promotions.php') {
        $current_dashboard_page = 'Promotion';
    } elseif ($current_page === 'inbox.php') {
        $current_dashboard_page = 'Inbox';
    } elseif ($current_page === 'admin-dashboard.php') {
        $current_dashboard_page = 'Admin Dashboard';
    }
}

// Determine user role
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$is_admin = ($user_role === 'admin');
$is_seller = ($user_role === 'seller');
$is_buyer = ($user_role === 'buyer');
$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
?>
<!-- header.php - Just the header and modals -->
<header>
    <!-- Left: Hamburger -->
    <button class="hamburger" id="hamburger">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <!-- Center: Logo -->
    <div class="logo"><a href="<?php echo $baseUrl; ?>index.php">Consu<span>Trade</span></a></div>

    <!-- Mobile Header Icons -->
    <div class="header-icons">
        <button class="mobile-search-icon" id="mobileSearchIcon">
            <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" class="icon-white" width="22px" height="22px" alt="Search">
        </button>
        <!-- Cart only shows on main website, not in admin area -->
        <?php if (!$is_admin_area): ?>
            <a href="<?php echo $baseUrl; ?>cart.php" class="mobile-header-cart">
                <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" class="icon-white" width="24px" height="24px" alt="Shopping Cart">
                <span class="cart-count">0</span>
            </a>
        <?php endif; ?>
    </div>

    <!-- Desktop Search Form -->
    <form action="" method="get" class="desktop-search">
        <div class="search-wrapper">
            <input type="search" id="search" name="q" placeholder="Search for products...">
            <button class="search-btn" type="submit">
                <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" width="24px" height="24px" alt="Search">
            </button>
        </div>
    </form>

    <!-- Desktop Navigation -->
    <nav class="nav-container" id="nav-menu">
        <ul class="nav-links">
            <!-- DIFFERENT NAVIGATION BASED ON LOCATION (admin area vs main site) -->
            <?php if ($is_admin_area): ?>
                <!-- ===== ADMIN AREA NAVIGATION (Dashboard links) ===== -->
                <?php if ($is_logged_in && $is_admin): ?>
                    <li><a href="<?php echo $baseUrl; ?>admin/admin-dashboard.php" class="<?php echo ($current_dashboard_page === 'Admin Dashboard') ? 'active' : ''; ?>">Admin Dashboard</a></li>
                    <li><a href="<?php echo $baseUrl; ?>admin/users.php">Manage Users</a></li>
                    <li><a href="<?php echo $baseUrl; ?>admin/all-products.php">All Products</a></li>
                    <li><a href="<?php echo $baseUrl; ?>admin/all-orders.php">All Orders</a></li>
                <?php elseif ($is_logged_in && $is_seller): ?>
                    <li><a href="<?php echo $baseUrl; ?>admin/seller-dashboard.php" class="<?php echo ($current_dashboard_page === 'Dashboard') ? 'active' : ''; ?>">Dashboard</a></li>
                    <li><a href="<?php echo $baseUrl; ?>admin/my-orders.php" class="<?php echo ($current_dashboard_page === 'Orders') ? 'active' : ''; ?>">Orders</a></li>
                    <li><a href="<?php echo $baseUrl; ?>admin/promotions.php" class="<?php echo ($current_dashboard_page === 'Promotion') ? 'active' : ''; ?>">Promotion</a></li>
                    <li><a href="<?php echo $baseUrl; ?>admin/inbox.php" class="<?php echo ($current_dashboard_page === 'Inbox') ? 'active' : ''; ?>">Inbox</a></li>
                <?php else: ?>
                    <!-- If someone tries to access admin area without login, show minimal nav -->
                    <li><a href="<?php echo $baseUrl; ?>index.php">Home</a></li>
                    <li><a href="<?php echo $baseUrl; ?>product-listings.php">Shop</a></li>
                <?php endif; ?>
            <?php else: ?>
                <!-- ===== MAIN WEBSITE NAVIGATION (Regular links for everyone) ===== -->
                <li><a href="<?php echo $baseUrl; ?>index.php">Home</a></li>
                <li><a href="<?php echo $baseUrl; ?>product-listings.php">Shop</a></li>
                
                <!-- Sell link - visible to guests and buyers (not shown to sellers on main site) -->
                <?php if (!$is_seller): ?>
                    <?php if ($is_logged_in && $is_buyer): ?>
                        <li><a href="<?php echo $baseUrl; ?>sell.php" id="sell-link">Sell</a></li>
                    <?php elseif (!$is_logged_in): ?>
                        <li><a href="<?php echo $baseUrl; ?>sell.php">Sell</a></li>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
            
            <!-- ===== USER DROPDOWN (visible when logged in) ===== -->
            <?php if ($is_logged_in): ?>
                <li class="user-dropdown">
                    <div class="user-info">
                        <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                        <img src="<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg" class="profile-icon" width="32px" height="32px" alt="Profile">
                        <button class="dropdown-toggle" id="desktopDropdownToggle">
                            <img src="<?php echo $baseUrl; ?>images/icons/chevron-down-svgrepo-com.svg" class="icon-white" width="16px" height="16px" alt="Menu">
                        </button>
                    </div>
                    <ul class="dropdown-menu" id="desktopDropdownMenu">
                        <!-- Common for all logged-in users -->
                        <li><a href="<?php echo $baseUrl; ?>profile.php">My Profile</a></li>
                        
                        <!-- Role-specific links (these work regardless of location) -->
                        <?php if ($is_admin): ?>
                            <li><a href="<?php echo $baseUrl; ?>admin/admin-dashboard.php">Admin Dashboard</a></li>
                        <?php elseif ($is_seller): ?>
                            <li><a href="<?php echo $baseUrl; ?>admin/seller-dashboard.php">Seller Dashboard</a></li>
                            <li><a href="<?php echo $baseUrl; ?>admin/my-products.php">My Products</a></li>
                            <li><a href="<?php echo $baseUrl; ?>admin/my-orders.php">My Orders</a></li>
                        <?php elseif ($is_buyer): ?>
                            <li><a href="<?php echo $baseUrl; ?>my-orders.php">My Orders</a></li>
                            <li><a href="<?php echo $baseUrl; ?>cart.php">My Cart</a></li>
                        <?php endif; ?>
                        
                        <li class="dropdown-divider"></li>
                        <li><a href="<?php echo $baseUrl; ?>php/logout.php">Logout</a></li>
                    </ul>
                </li>
            <?php else: ?>
                <li><a href="" id="login">Login</a></li>
                <li><a href="#register-modal" id="register">Register</a></li>
            <?php endif; ?>
        </ul>
        
        <!-- Cart Icon - Only shows on main website, not in admin area -->
        <?php if (!$is_admin_area): ?>
            <a href="<?php echo $baseUrl; ?>cart.php" class="desktop-cart">
                <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" class="icon-white" width="24px" height="24px" alt="Shopping Cart">
                <span class="cart-count">0</span>
            </a>
        <?php endif; ?>
    </nav>

    <!-- Mobile Side Menu Content -->
    <div class="mobile-side-menu" id="mobile-side-menu">
        <button class="side-menu-hamburger" id="sideMenuHamburger">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <div class="mobile-menu-header">
            <div class="mobile-menu-logo">
                <a href="<?php echo $baseUrl; ?>index.php">Consu<span>Trade</span></a>
            </div>
        </div>
        
        <?php if ($is_logged_in): ?>
            <div class="mobile-profile-section">
                <div class="mobile-profile-info">
                    <img src="<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg" class="mobile-profile-avatar" width="40px" height="40px" alt="Profile">
                    <div class="mobile-profile-text">
                        <span class="mobile-profile-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                        <span class="mobile-profile-role"><?php echo ucfirst($user_role); ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <ul class="mobile-nav-links">
            <!-- Mobile navigation based on LOCATION (admin area vs main site) -->
            <?php if ($is_admin_area): ?>
                <!-- Admin Area Mobile Navigation -->
                <?php if ($is_logged_in && $is_admin): ?>
                    <li><a href="<?php echo $baseUrl; ?>admin/admin-dashboard.php">Admin Dashboard</a></li>
                    <li><a href="<?php echo $baseUrl; ?>admin/users.php">Manage Users</a></li>
                    <li><a href="<?php echo $baseUrl; ?>admin/all-products.php">All Products</a></li>
                    <li><a href="<?php echo $baseUrl; ?>admin/all-orders.php">All Orders</a></li>
                <?php elseif ($is_logged_in && $is_seller): ?>
                    <li><a href="<?php echo $baseUrl; ?>admin/seller-dashboard.php">Dashboard</a></li>
                    <li><a href="<?php echo $baseUrl; ?>admin/my-orders.php">Orders</a></li>
                    <li><a href="<?php echo $baseUrl; ?>admin/promotions.php">Promotion</a></li>
                    <li><a href="<?php echo $baseUrl; ?>admin/inbox.php">Inbox</a></li>
                <?php else: ?>
                    <li><a href="<?php echo $baseUrl; ?>index.php">Home</a></li>
                    <li><a href="<?php echo $baseUrl; ?>product-listings.php">Shop</a></li>
                <?php endif; ?>
            <?php else: ?>
                <!-- Main Website Mobile Navigation -->
                <li><a href="<?php echo $baseUrl; ?>index.php">Home</a></li>
                <li><a href="<?php echo $baseUrl; ?>product-listings.php">Shop</a></li>
                
                <?php if (!$is_seller): ?>
                    <?php if ($is_logged_in && $is_buyer): ?>
                        <li><a href="<?php echo $baseUrl; ?>sell.php" class="sell-link-mobile">Sell</a></li>
                    <?php elseif (!$is_logged_in): ?>
                        <li><a href="<?php echo $baseUrl; ?>sell.php">Sell</a></li>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if ($is_logged_in): ?>
                <li class="mobile-menu-divider"></li>
                <li><a href="<?php echo $baseUrl; ?>profile.php">My Profile</a></li>
                
                <?php if ($is_admin): ?>
                    <li><a href="<?php echo $baseUrl; ?>admin/admin-dashboard.php">Admin Dashboard</a></li>
                <?php elseif ($is_seller): ?>
                    <li><a href="<?php echo $baseUrl; ?>admin/seller-dashboard.php">Seller Dashboard</a></li>
                    <li><a href="<?php echo $baseUrl; ?>admin/my-products.php">My Products</a></li>
                    <li><a href="<?php echo $baseUrl; ?>admin/my-orders.php">My Orders</a></li>
                <?php elseif ($is_buyer): ?>
                    <li><a href="<?php echo $baseUrl; ?>my-orders.php">My Orders</a></li>
                    <li><a href="<?php echo $baseUrl; ?>cart.php">My Cart</a></li>
                <?php endif; ?>
                
                <li class="mobile-menu-divider"></li>
                <li><a href="<?php echo $baseUrl; ?>php/logout.php" class="mobile-logout-link">Logout</a></li>
            <?php else: ?>
                <li class="mobile-menu-divider"></li>
                <li><a href="#login-modal" class="login-link-mobile">Login</a></li>
                <li><a href="#register-modal" class="register-link-mobile">Register</a></li>
            <?php endif; ?>
        </ul>
        
        <div class="mobile-menu-search">
            <div class="search-wrapper">
                <input type="search" id="mobile-menu-search" name="q" placeholder="Search for products...">
                <button class="search-btn" type="submit">
                    <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" width="20px" height="20px" alt="Search">
                </button>
            </div>
        </div>
        
        <!-- Cart - Only shows on main website -->
        <?php if (!$is_admin_area): ?>
            <a href="<?php echo $baseUrl; ?>cart.php" class="mobile-menu-cart">
                <span class="cart-text">Cart</span>
                <div class="cart-icon-wrapper">
                    <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" class="icon-white" width="24px" height="24px" alt="Shopping Cart">
                    <span class="cart-count">0</span>
                </div>
            </a>
        <?php endif; ?>
    </div>

    <!-- Mobile Expandable Search -->
    <div class="mobile-search-container" id="mobileSearchContainer">
        <form action="" method="get" class="mobile-search-form">
            <div class="search-wrapper">
                <input type="search" id="mobile-search" name="q" placeholder="Search for products...">
                <button class="search-btn" type="submit">
                    <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" width="20px" height="20px" alt="Search">
                </button>
            </div>
        </form>
    </div>

    <!-- Overlay -->
    <div class="menu-overlay" id="menu-overlay"></div>
</header>

<!-- Registration Modal -->
<div id="register-modal" class="modal">
    <div class="modal-content">
        <button type="button" class="btn-close"></button>
        
        <div class="modal-header">
            <h1>Consu<span>Trade</span></h1>
            <p>Join thousands of South African traders</p>
        </div>
        
        <div id="register-error-container" class="error-container" style="display: none;">
            <div class="error-message">Please fix the errors below</div>
        </div>
        
        <form action="<?php echo $baseUrl; ?>php/register.php" method="post" class="register-form" id="register-form">
            <fieldset class="form-fields">
                <legend>Create Account</legend>
                
                <div class="input-group">
                    <label for="fullname">Full Name</label>
                    <input type="text" id="fullname" name="fullname" placeholder="Enter your full name..." required>
                    <small class="error-text" id="fullname-error"></small>
                </div>
                
                <div class="input-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email address" required>
                    <small class="error-text" id="email-error"></small>
                </div>
                
                <div class="input-group password-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Create password" required>
                        <button type="button" class="toggle-password" data-target="password">
                            <img src="<?php echo $baseUrl; ?>images/icons/eye-open-svgrepo-com.svg" width="20" height="20" alt="Show password">
                        </button>
                    </div>
                    <small class="error-text" id="password-error"></small>
                </div>
                
                <div class="input-group password-group">
                    <label for="confirm-password">Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirm-password" name="confirm_password" placeholder="Repeat your password" required>
                        <button type="button" class="toggle-password" data-target="confirm-password">
                            <img src="<?php echo $baseUrl; ?>images/icons/eye-open-svgrepo-com.svg" width="20" height="20" alt="Show password">
                        </button>
                    </div>
                    <small class="error-text" id="confirm_password-error"></small>
                </div>
            </fieldset>
            
            <fieldset class="user-type">
                <legend>I want to:</legend>
                <div class="radio-buttons">
                    <input type="radio" id="buy" name="user_type" value="buyer" checked>
                    <label for="buy" class="radio-btn radio">
                        <img src="<?php echo $baseUrl; ?>images/icons/buy-cash-finance-svgrepo-com.svg" width="20px" height="20px" alt="Buy icon">
                        Buy Products
                    </label>
                    
                    <input type="radio" id="sell" name="user_type" value="seller">
                    <label for="sell" class="radio-btn radio">
                        <img src="<?php echo $baseUrl; ?>images/icons/sell-svgrepo-com.svg" width="20px" height="20px" alt="Sell icon">
                        Sell Products
                    </label>
                </div>
                <small class="error-text" id="role-error"></small>
            </fieldset>
            
            <button type="submit" class="submit-btn">Create Account</button>
            
            <p class="login-link">Already have an account? <a href="#login-modal">Login</a></p>
        </form>
    </div>
</div>

<!-- Login Modal -->
<div id="login-modal" class="modal">
    <div class="modal-content">
        <button type="button" class="btn-close"></button>
        
        <div class="modal-header">
            <h1>Consu<span>Trade</span></h1>
            <p>Welcome back to ConsuTrade</p>
        </div>

        <div id="login-error-container" class="error-container" style="display: none;">
            <div class="error-message">Please fix the errors below</div>
        </div>
        
        <form action="<?php echo $baseUrl; ?>php/login.php" method="post" class="login-form">
            <fieldset class="form-fields">
                <legend>Login Account</legend>
                <div class="input-group">
                    <label for="login-email">Email Address</label>
                    <input type="email" id="login-email" name="email" placeholder="Enter your email address" required>
                </div>
                <div class="input-group password-group">
                    <label for="login-password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="login-password" name="password" placeholder="Enter your password" required>
                        <button type="button" class="toggle-password" data-target="login-password">
                            <img src="<?php echo $baseUrl; ?>images/icons/eye-open-svgrepo-com.svg" width="20" height="20" alt="Show password">
                        </button>
                    </div>
                </div>
            </fieldset>
            <p class="reset-pass"><a href="#">Forgot your password?</a></p>
            <button type="submit" class="submit-btn">Login Account</button>
            <p class="register-link">Don't have an account? <a href="#register-modal">Register</a></p>
        </form>
    </div>
</div>