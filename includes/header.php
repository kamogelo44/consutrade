<?php
/*
 * ConsuTrade - Site Header Component
 * Author: Kamogelo Phale
 * 
 * Simple text-only dropdown navigation for all users.
 */

$current_page = basename($_SERVER['PHP_SELF']);
$is_logged_in = $auth->isLoggedIn();

if ($is_logged_in && isset($currentUser)) {
    $user_name = $currentUser->getDisplayName();
    $user_profile_image = $currentUser->getProfileImageUrl();
    $user_roles = $currentUser->getRoles();
    $hasBuyerRole = in_array('buyer', $user_roles);
    $hasSellerRole = in_array('seller', $user_roles);
    $hasAdminRole = in_array('admin', $user_roles);
    $primaryRole = $currentUser->getPrimaryRole();
} else {
    $user_name = 'Account';
    $user_profile_image = $baseUrl . 'images/icons/profile-svgrepo-com.svg';
    $user_roles = [];
    $hasBuyerRole = false;
    $hasSellerRole = false;
    $hasAdminRole = false;
    $primaryRole = null;
}

$show_sell_link = !$is_logged_in;

if ($is_logged_in && isset($currentUser) && $currentUser->hasRole('buyer')) {
    $cart_count = $cartRepo->countItems($currentUser->getUserId());
} else {
    $cart_count = 0;
}
?>

<header class="site-header">
    <div class="header-container">
        <!-- Left Section: Logo + Desktop Nav -->
        <div class="header-left">
            <div class="logo">
                <a href="<?php echo $baseUrl; ?>index.php">Consu<span>Trade</span></a>
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="<?php echo $baseUrl; ?>index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">Home</a></li>
                    <li><a href="<?php echo $baseUrl; ?>product-listings.php" class="<?php echo $current_page == 'product-listings.php' ? 'active' : ''; ?>">Products</a></li>
                    <?php if ($show_sell_link): ?>
                        <li><a href="<?php echo $baseUrl; ?>sell.php">Sell</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo $baseUrl; ?>about.php" class="<?php echo $current_page == 'about.php' ? 'active' : ''; ?>">About</a></li>
                </ul>
            </nav>
        </div>

        <!-- Center Section: Search Bar -->
        <div class="header-center">
            <div class="search-wrapper">
                <form action="<?php echo $baseUrl; ?>search-results.php" method="GET">
                    <input type="search" name="search" placeholder="Search products..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit" class="search-btn">
                        <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" width="18" height="18" alt="Search">
                    </button>
                </form>
            </div>
        </div>

        <!-- Right Section: Cart + Account -->
        <div class="header-right">
            <!-- Cart -->
            <?php if (!$is_logged_in || $hasBuyerRole): ?>
                <a href="<?php echo $baseUrl; ?>cart.php" class="cart-link">
                    <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" width="22" height="22" alt="Cart">
                    <span class="cart-badge"><?php echo $cart_count; ?></span>
                </a>
            <?php endif; ?>

            <!-- Account Dropdown -->
            <?php if ($is_logged_in): ?>
                <div class="account-dropdown">
                    <button class="account-btn" id="accountBtn">
                        <img src="<?php echo $user_profile_image; ?>" alt="<?php echo htmlspecialchars($user_name); ?>" class="account-avatar" onerror="this.src='<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg'">
                        <span class="account-name"><?php echo htmlspecialchars($user_name); ?></span>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="dropdown-menu" id="accountDropdown">
                        <a href="<?php echo $baseUrl; ?>profile.php">My Profile</a>

                        <?php if ($hasBuyerRole): ?>
                            <a href="<?php echo $baseUrl; ?>orders.php">Orders</a>
                            <a href="<?php echo $baseUrl; ?>cart.php">My Cart</a>
                        <?php endif; ?>

                        <?php if ($hasSellerRole): ?>
                            <?php if ($hasBuyerRole): ?>
                                <hr><?php endif; ?>
                            <a href="<?php echo $baseUrl; ?>admin/seller-dashboard.php">Seller Dashboard</a>
                            <a href="<?php echo $baseUrl; ?>admin/my-products.php">My Products</a>
                            <a href="<?php echo $baseUrl; ?>orders.php">Orders</a>
                        <?php endif; ?>

                        <?php if ($hasAdminRole): ?>
                            <?php if ($hasBuyerRole || $hasSellerRole): ?>
                                <hr><?php endif; ?>
                            <a href="<?php echo $baseUrl; ?>admin/admin-dashboard.php">Admin Dashboard</a>
                            <a href="<?php echo $baseUrl; ?>admin/users.php">Users</a>
                            <a href="<?php echo $baseUrl; ?>admin/all-orders.php">All Orders</a>
                        <?php endif; ?>

                        <hr>
                        <a href="<?php echo $baseUrl; ?>php/endpoints/auth/logout.php" class="logout-link">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="auth-buttons">
                    <button class="login-btn" id="loginBtn">Login</button>
                    <button class="signup-btn" id="registerBtn">Sign Up</button>
                </div>
            <?php endif; ?>

            <!-- Mobile Search Toggle -->
            <button class="mobile-search-toggle" id="mobileSearchIcon">
                <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" width="20" height="20" alt="Search">
            </button>

            <!-- Mobile Menu Toggle -->
            <button class="menu-toggle" id="menuToggle">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>

    <!-- Mobile Search -->
    <div class="mobile-search" id="mobileSearch">
        <form action="<?php echo $baseUrl; ?>product-listings.php" method="GET">
            <input type="search" name="search" placeholder="Search products...">
            <button type="submit">
                <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" width="18" height="18" alt="Search">
            </button>
        </form>
    </div>

    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobileMenu">
        <div class="mobile-menu-header">
            <div class="mobile-logo">
                <a href="<?php echo $baseUrl; ?>index.php">Consu<span>Trade</span></a>
            </div>
            <button class="close-menu" id="closeMenu">
                <span></span><span></span>
            </button>
        </div>

        <?php if ($is_logged_in): ?>
            <div class="mobile-profile">
                <img src="<?php echo $user_profile_image; ?>" alt="<?php echo htmlspecialchars($user_name); ?>" class="mobile-profile-img" onerror="this.src='<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg'">
                <div class="mobile-profile-info">
                    <span class="mobile-profile-name"><?php echo htmlspecialchars($user_name); ?></span>
                    <span class="mobile-profile-email"><?php echo htmlspecialchars($currentUser->getEmail()); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <ul class="mobile-nav-links">
            <li><a href="<?php echo $baseUrl; ?>index.php">Home</a></li>
            <li><a href="<?php echo $baseUrl; ?>product-listings.php">Products</a></li>
            <?php if ($show_sell_link): ?>
                <li><a href="<?php echo $baseUrl; ?>sell.php">Sell</a></li>
            <?php endif; ?>
            <li><a href="<?php echo $baseUrl; ?>about.php">About</a></li>

            <?php if ($is_logged_in): ?>
                <li class="mobile-divider"></li>
                <li><a href="<?php echo $baseUrl; ?>profile.php">My Profile</a></li>

                <?php if ($hasBuyerRole): ?>
                    <li><a href="<?php echo $baseUrl; ?>orders.php">Orders</a></li>
                    <li><a href="<?php echo $baseUrl; ?>cart.php">My Cart</a></li>
                <?php endif; ?>

                <?php if ($hasSellerRole): ?>
                    <li><a href="<?php echo $baseUrl; ?>admin/seller-dashboard.php">Seller Dashboard</a></li>
                    <li><a href="<?php echo $baseUrl; ?>admin/my-products.php">My Products</a></li>
                    <li><a href="<?php echo $baseUrl; ?>orders.php">Orders</a></li>
                <?php endif; ?>

                <?php if ($hasAdminRole): ?>
                    <li><a href="<?php echo $baseUrl; ?>admin/admin-dashboard.php">Admin Dashboard</a></li>
                    <li><a href="<?php echo $baseUrl; ?>admin/users.php">Users</a></li>
                    <li><a href="<?php echo $baseUrl; ?>admin/all-orders.php">All Orders</a></li>
                <?php endif; ?>

                <li class="mobile-divider"></li>
                <li><a href="<?php echo $baseUrl; ?>php/endpoints/auth/logout.php" class="logout-link">Logout</a></li>
            <?php else: ?>
                <li class="mobile-divider"></li>
                <li><button class="mobile-login-btn" id="mobileLoginBtn">Login</button></li>
                <li><button class="mobile-signup-btn" id="mobileRegisterBtn">Sign Up</button></li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="menu-overlay" id="menuOverlay"></div>
</header>

<script>
    var currentUserRoles = <?php echo isset($currentUser) ? json_encode($user_roles) : '[]'; ?>;
    var isLoggedIn = <?php echo json_encode($is_logged_in); ?>;
    var currentUserRole = <?php echo isset($currentUser) ? json_encode($primaryRole) : 'null'; ?>;

    function hasRole(role) {
        return Array.isArray(currentUserRoles) && currentUserRoles.indexOf(role) !== -1;
    }
</script>

<!-- Login Modal -->
<div id="login-modal" class="modal">
    <div class="modal-content">
        <button class="btn-close"></button>
        <div class="modal-header">
            <h1>Consu<span>Trade</span></h1>
            <p>Welcome back! Please login to your account</p>
        </div>
        <div id="login-error-container" class="error-container" style="display: none;"></div>
        <form id="login-form" class="login-form" method="POST" action="<?php echo $baseUrl; ?>php/endpoints/auth/login.php">
            <input type="hidden" name="role_type" value="buyer">
            <div class="input-group">
                <label for="login-email">Email Address</label>
                <input type="email" id="login-email" name="email" placeholder="Enter your email address" required>
            </div>
            <div class="input-group">
                <label for="login-password">Password</label>
                <div class="password-field-wrapper">
                    <input type="password" id="login-password" name="password" placeholder="Enter your password" required>
                    <button type="button" class="password-toggle-btn" onclick="togglePassword('login-password', this)">
                        <img src="<?php echo $baseUrl; ?>images/icons/eye-open-svgrepo-com.svg" width="18" height="18">
                    </button>
                </div>
            </div>
            <div class="reset-pass"><a href="#">Forgot Password?</a></div>
            <button type="submit" class="submit-btn">Login</button>
            <div class="register-link">Don't have an account? <a href="#" id="switch-to-register">Register here</a></div>
        </form>
    </div>
</div>

<!-- Register Modal -->
<div id="register-modal" class="modal">
    <div class="modal-content">
        <button class="btn-close"></button>
        <div class="modal-header">
            <h1>Consu<span>Trade</span></h1>
            <p>Create your account to start buying and selling</p>
        </div>
        <div id="register-error-container" class="error-container" style="display: none;"></div>
        <form id="register-form" class="register-form" method="POST" action="<?php echo $baseUrl; ?>php/endpoints/auth/register.php">
            <div class="input-group">
                <label for="register-full-name">Full Name</label>
                <input type="text" id="register-full-name" name="full_name" placeholder="Enter your full name" required>
            </div>
            <div class="input-group">
                <label for="register-email">Email Address</label>
                <input type="email" id="register-email" name="email" placeholder="Enter your email address" required>
            </div>
            <div class="input-group">
                <label for="register-phone">Phone Number</label>
                <input type="tel" id="register-phone" name="phone" placeholder="Enter your phone number" required>
            </div>
            <div class="input-group">
                <label for="register-password">Password</label>
                <div class="password-field-wrapper">
                    <input type="password" id="register-password" name="password" placeholder="Create a password" required>
                    <button type="button" class="password-toggle-btn" onclick="togglePassword('register-password', this)">
                        <img src="<?php echo $baseUrl; ?>images/icons/eye-open-svgrepo-com.svg" width="18" height="18">
                    </button>
                </div>
            </div>
            <div class="input-group">
                <label for="register-confirm-password">Confirm Password</label>
                <div class="password-field-wrapper">
                    <input type="password" id="register-confirm-password" name="confirm_password" placeholder="Confirm your password" required>
                    <button type="button" class="password-toggle-btn" onclick="togglePassword('register-confirm-password', this)">
                        <img src="<?php echo $baseUrl; ?>images/icons/eye-open-svgrepo-com.svg" width="18" height="18">
                    </button>
                </div>
            </div>

            <fieldset class="user-type">
                <legend>I want to...</legend>
                <div class="radio-buttons">
                    <input type="radio" id="buyer" name="role" value="buyer" checked>
                    <label for="buyer" class="radio-btn radio">Buy Products</label>
                    <input type="radio" id="seller" name="role" value="seller">
                    <label for="seller" class="radio-btn radio">Sell Products</label>
                </div>
            </fieldset>

            <button type="submit" class="submit-btn">Create Account</button>
            <div class="login-link">Already have an account? <a href="#" id="switch-to-login">Login here</a></div>
        </form>
    </div>
</div>