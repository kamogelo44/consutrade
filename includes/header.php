<?php
/*
 * ConsuTrade - Site Header Component
 * Author: Kamogelo Phale
 * 
 * Uses modular components for consistent structure
 */

$current_page = basename($_SERVER['PHP_SELF']);
$is_logged_in = $auth->isLoggedIn();

if ($is_logged_in && isset($currentUser) && $currentUser instanceof Buyer) {
    $user_name = $currentUser->getDisplayName();
    $user_profile_image = $currentUser->getProfileImageUrl();
    $is_buyer = true;
} else {
    $user_name = 'Account';
    $user_profile_image = $baseUrl . 'images/icons/profile-svgrepo-com.svg';
    $is_buyer = false;
}

$show_sell_link = !$is_logged_in;

if ($is_logged_in && isset($currentUser) && $currentUser instanceof Buyer) {
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
            <a href="<?php echo $baseUrl; ?>cart.php" class="cart-link">
                <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" width="22" height="22" alt="Cart">
                <span class="cart-badge"><?php echo $cart_count; ?></span>
            </a>

            <!-- Account Dropdown or Login -->
            <?php if ($is_logged_in && $is_buyer): ?>
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
                        <a href="<?php echo $baseUrl; ?>my-orders.php">My Orders</a>
                        <hr>
                        <a href="<?php echo $baseUrl; ?>php/endpoints/auth/logout.php">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="auth-buttons">
                    <button class="login-btn" id="loginBtn">Login</button>
                    <button class="signup-btn" id="registerBtn">Sign Up</button>
                </div>
            <?php endif; ?>

            <!-- Mobile Menu Toggle -->
            <button class="menu-toggle" id="menuToggle">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>

    <!-- Mobile Search (hidden on desktop) -->
    <div class="mobile-search" id="mobileSearch">
        <form action="<?php echo $baseUrl; ?>product-listings.php" method="GET">
            <input type="search" name="search" placeholder="Search products...">
            <button type="submit">
                <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" width="18" height="18" alt="Search">
            </button>
        </form>
    </div>

    <!-- Mobile Menu (hidden on desktop) -->
    <div class="mobile-menu" id="mobileMenu">
        <div class="mobile-menu-header">
            <div class="mobile-logo">
                <a href="<?php echo $baseUrl; ?>index.php">Consu<span>Trade</span></a>
            </div>
            <button class="close-menu" id="closeMenu">
                <span></span><span></span>
            </button>
        </div>

        <?php if ($is_logged_in && $is_buyer): ?>
            <div class="mobile-profile">
                <img src="<?php echo $user_profile_image; ?>" alt="<?php echo htmlspecialchars($user_name); ?>" class="mobile-profile-img" onerror="this.src='<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg'">
                <div class="mobile-profile-info">
                    <span class="mobile-profile-name"><?php echo htmlspecialchars($user_name); ?></span>
                    <span class="mobile-profile-email"><?php echo htmlspecialchars($currentUser->getEmail()); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <ul class="mobile-nav-links">
            <li><a href="<?php echo $baseUrl; ?>index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">Home</a></li>
            <li><a href="<?php echo $baseUrl; ?>product-listings.php" class="<?php echo $current_page == 'product-listings.php' ? 'active' : ''; ?>">Products</a></li>
            <?php if ($show_sell_link): ?>
                <li><a href="<?php echo $baseUrl; ?>sell.php" class="<?php echo $current_page == 'sell.php' ? 'active' : ''; ?>">Sell</a></li>
            <?php endif; ?>
            <li><a href="<?php echo $baseUrl; ?>about.php" class="<?php echo $current_page == 'about.php' ? 'active' : ''; ?>">About</a></li>
            <li><a href="<?php echo $baseUrl; ?>cart.php" class="<?php echo $current_page == 'cart.php' ? 'active' : ''; ?>">Cart <?php if ($cart_count > 0): ?><span class="mobile-cart-count"><?php echo $cart_count; ?></span><?php endif; ?></a></li>

            <?php if ($is_logged_in && $is_buyer): ?>
                <li class="mobile-divider"></li>
                <li><a href="<?php echo $baseUrl; ?>profile.php" class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">My Profile</a></li>
                <li><a href="<?php echo $baseUrl; ?>my-orders.php" class="<?php echo $current_page == 'my-orders.php' ? 'active' : ''; ?>">My Orders</a></li>
                <li><a href="<?php echo $baseUrl; ?>php/endpoints/auth/logout.php">Logout</a></li>
            <?php else: ?>
                <li class="mobile-divider"></li>
                <li><button class="mobile-login-btn" id="mobileLoginBtn">Login</button></li>
                <li><button class="mobile-signup-btn" id="mobileRegisterBtn">Sign Up</button></li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="menu-overlay" id="menuOverlay"></div>
</header>


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