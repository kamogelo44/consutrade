<?php
/*
 * ConsuTrade - Site Header Component
 * Author: Kamogelo Phale
 * 
 * Reusable header component with dynamic cart count and user menu
 */

$baseUrl = getBaseUrl();
$current_page = basename($_SERVER['PHP_SELF']);

// Use Auth class for all user data
$is_logged_in = $auth->isLoggedIn();
$current_user = $auth->getCurrentUser();

$user_role = null;
$user_name = 'User';
$user_profile_image = $baseUrl . 'images/icons/profile-svgrepo-com.svg';

if ($is_logged_in && $current_user) {
    $user_role = $current_user['role'] ?? null;
    $user_name = $current_user['full_name'] ?? 'User';
    if (!empty($current_user['profile_image'])) {
        $user_profile_image = $baseUrl . $current_user['profile_image'];
    }
}

$show_sell_link = !$is_logged_in;
?>

<header class="site-header">
    <div class="header-container">
        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" id="mobileMenuToggle">
            <span></span><span></span><span></span>
        </button>
        
        <!-- Logo -->
        <div class="logo">
            <a href="<?php echo $baseUrl; ?>index.php">Consu<span>Trade</span></a>
        </div>
        
        <!-- Desktop Search -->
        <div class="desktop-search">
            <form action="<?php echo $baseUrl; ?>search-results.php" method="GET" class="search-wrapper">
                <input type="search" name="search" placeholder="Search for products..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit" class="search-btn">
                    <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" width="20" height="20" alt="Search">
                </button>
            </form>
        </div>
        
        <!-- Main Navigation -->
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
        
        <!-- Header Actions -->
        <div class="header-actions">
            <!-- Desktop Cart -->
            <a href="<?php echo $baseUrl; ?>cart.php" class="cart-icon desktop-cart">
                <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" width="24" height="24" alt="Cart">
                <span class="cart-count">0</span>
            </a>
            
            <!-- User Menu (Logged In) -->
            <?php if ($is_logged_in): ?>
                <div class="user-menu">
                    <button class="user-menu-btn" id="userMenuBtn">
                        <img src="<?php echo $user_profile_image; ?>" alt="Profile" class="user-avatar-icon" onerror="this.src='<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg'">
                        <span><?php echo htmlspecialchars($user_name); ?></span>
                        <svg class="dropdown-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="user-dropdown" id="userDropdown">
                        <?php if ($user_role === 'seller'): ?>
                            <a href="<?php echo $baseUrl; ?>admin/seller-dashboard.php">Dashboard</a>
                            <a href="<?php echo $baseUrl; ?>admin/my-products.php">My Products</a>
                            <a href="<?php echo $baseUrl; ?>admin/my-orders.php">Orders</a>
                        <?php else: ?>
                            <a href="<?php echo $baseUrl; ?>profile.php">My Profile</a>
                            <a href="<?php echo $baseUrl; ?>my-orders.php">My Orders</a>
                        <?php endif; ?>
                        <hr>
                        <a href="<?php echo $baseUrl; ?>php/endpoints/logout.php" class="logout-link">Logout</a>
                    </div>
                </div>
            
            <!-- Login/Signup Buttons (Not Logged In) -->
            <?php else: ?>
                <button class="login-btn" id="loginBtn">Login</button>
                <button class="signup-btn" id="registerBtn">Sign Up</button>
            <?php endif; ?>
            
            <!-- Mobile Header Icons -->
            <div class="mobile-header-icons">
                <button class="mobile-search-icon" id="mobileSearchIcon">
                    <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" width="22" height="22" alt="Search">
                </button>
                <a href="<?php echo $baseUrl; ?>cart.php" class="mobile-header-cart">
                    <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" width="22" height="22" alt="Cart">
                    <span class="cart-count">0</span>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Mobile Search Container -->
    <div class="mobile-search-container" id="mobileSearchContainer">
        <form action="<?php echo $baseUrl; ?>search-results.php" method="GET" class="search-wrapper">
            <input type="search" name="search" placeholder="Search for products..." id="mobile-search">
            <button type="submit" class="search-btn">
                <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" width="18" height="18" alt="Search">
            </button>
        </form>
    </div>
    
    <!-- Mobile Navigation Menu -->
    <div class="mobile-nav" id="mobileNav">
        <div class="mobile-menu-header">
            <div class="mobile-menu-logo">
                <a href="<?php echo $baseUrl; ?>index.php">Consu<span>Trade</span></a>
            </div>
            <button class="side-menu-close" id="sideMenuClose">
                <span></span><span></span>
            </button>
        </div>
        
        <!-- Mobile Profile Section (Logged In) -->
        <?php if ($is_logged_in): ?>
            <div class="mobile-profile-section">
                <div class="mobile-profile-info">
                    <img src="<?php echo $user_profile_image; ?>" alt="Profile" class="mobile-profile-avatar" width="40" height="40" onerror="this.src='<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg'">
                    <div class="mobile-profile-text">
                        <span class="mobile-profile-name"><?php echo htmlspecialchars($user_name); ?></span>
                        <span class="mobile-profile-role"><?php echo ucfirst($user_role); ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Mobile Navigation Links -->
        <ul class="mobile-nav-links">
            <li><a href="<?php echo $baseUrl; ?>index.php">Home</a></li>
            <li><a href="<?php echo $baseUrl; ?>product-listings.php">Products</a></li>
            <?php if ($show_sell_link): ?>
                <li><a href="<?php echo $baseUrl; ?>sell.php">Sell</a></li>
            <?php endif; ?>
            <li><a href="<?php echo $baseUrl; ?>about.php">About</a></li>
            <li>
                <a href="<?php echo $baseUrl; ?>cart.php" class="mobile-menu-cart">
                    <span>Cart</span>
                    <span class="cart-count">0</span>
                </a>
            </li>
            
            <?php if ($is_logged_in): ?>
                <li class="mobile-menu-divider"></li>
                <?php if ($user_role === 'seller'): ?>
                    <li><a href="<?php echo $baseUrl; ?>admin/seller-dashboard.php">Dashboard</a></li>
                    <li><a href="<?php echo $baseUrl; ?>admin/my-products.php">My Products</a></li>
                    <li><a href="<?php echo $baseUrl; ?>admin/my-orders.php">Orders</a></li>
                <?php else: ?>
                    <li><a href="<?php echo $baseUrl; ?>profile.php">My Profile</a></li>
                    <li><a href="<?php echo $baseUrl; ?>my-orders.php">My Orders</a></li>
                <?php endif; ?>
                <li><a href="<?php echo $baseUrl; ?>php/endpoints/logout.php">Logout</a></li>
            <?php else: ?>
                <li class="mobile-menu-divider"></li>
                <li><button class="mobile-nav-btn" id="mobile-login-btn">Login</button></li>
                <li><button class="mobile-nav-btn" id="mobile-register-btn">Sign Up</button></li>
            <?php endif; ?>
        </ul>
    </div>
    
    <!-- Mobile Menu Overlay -->
    <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>
</header>

<!-- Login & Registration Modals (shown only when user is not logged in) -->
<?php if (!$is_logged_in): ?>
    <!-- Login Modal -->
    <div id="login-modal" class="modal">
        <div class="modal-content">
            <button class="btn-close"></button>
            <div class="modal-header">
                <h1>Consu<span>Trade</span></h1>
                <p>Welcome back! Please login to your account</p>
            </div>
            <div id="login-error-container" class="error-container" style="display: none;"></div>
            <form id="login-form" class="login-form" method="POST" action="<?php echo $baseUrl; ?>php/endpoints/login.php">
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
            <form id="register-form" class="register-form" method="POST" action="<?php echo $baseUrl; ?>php/endpoints/register.php">
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
<?php endif; ?>