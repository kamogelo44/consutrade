<?php
/*
 * ConsuTrade - Site Header Component
 * Author: Kamogelo Phale
 * 
 * Reusable header for main website pages (NOT for seller dashboard)
 * Seller dashboard has its own sidebar component
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL for the site
$baseUrl = "/www/consutrade/";

// Get current page for active link highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Check if user is logged in
$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

// Get user role for sell link handling
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : null;

// Get user's full name or default
$user_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'User';

// Get cart count (if user is logged in)
$cart_count = 0;
if ($is_logged_in && isset($_SESSION['user_id'])) {
    // Try to get from session first
    if (isset($_SESSION['cart_count'])) {
        $cart_count = $_SESSION['cart_count'];
    }
}
?>

<header class="site-header">
    <div class="header-container">
        <!-- Mobile Menu Toggle (Left side on mobile) -->
        <button class="mobile-menu-toggle" id="mobileMenuToggle">
            <span></span>
            <span></span>
            <span></span>
        </button>
        
        <!-- Logo -->
        <div class="logo">
            <a href="<?php echo $baseUrl; ?>index.php">
                Consu<span>Trade</span>
            </a>
        </div>
        
        <!-- Desktop Search Bar -->
        <div class="desktop-search">
            <form action="<?php echo $baseUrl; ?>product-listings.php" method="GET" class="search-wrapper">
                <input type="search" name="search" placeholder="Search for products..." aria-label="Search products" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit" class="search-btn">
                    <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" width="20" height="20" alt="Search">
                </button>
            </form>
        </div>
        
        <!-- Desktop Navigation -->
        <nav class="main-nav">
            <ul>
                <li><a href="<?php echo $baseUrl; ?>index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">Home</a></li>
                <li><a href="<?php echo $baseUrl; ?>product-listings.php" class="<?php echo $current_page == 'product-listings.php' ? 'active' : ''; ?>">Products</a></li>
                <li><a href="<?php echo $baseUrl; ?>sell.php" id="sell-link">Sell</a></li>
                <li><a href="<?php echo $baseUrl; ?>about.php" class="<?php echo $current_page == 'about.php' ? 'active' : ''; ?>">About</a></li>
                <li><a href="<?php echo $baseUrl; ?>contact.php" class="<?php echo $current_page == 'contact.php' ? 'active' : ''; ?>">Contact</a></li>
            </ul>
        </nav>
        
        <!-- Header Actions (Cart, Login/User Menu) -->
        <div class="header-actions">
            <!-- Desktop Cart Icon -->
            <a href="<?php echo $baseUrl; ?>cart.php" class="cart-icon desktop-cart">
                <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" width="24" height="24" alt="Cart">
                <span class="cart-count"><?php echo $cart_count; ?></span>
            </a>
            
            <?php if ($is_logged_in): ?>
                <div class="user-menu">
                    <button class="user-menu-btn" id="userMenuBtn">
                        <img src="<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg" alt="Profile" class="user-avatar-icon" onerror="this.style.display='none'">
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
                            <a href="<?php echo $baseUrl; ?>wishlist.php">Wishlist</a>
                        <?php endif; ?>
                        <hr>
                        <a href="<?php echo $baseUrl; ?>php/logout.php" class="logout-link">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <button class="login-btn" id="loginBtn">Login</button>
                <button class="signup-btn" id="registerBtn">Sign Up</button>
            <?php endif; ?>
            
            <!-- Mobile Header Icons (Search + Cart) - Visible only on mobile -->
            <div class="mobile-header-icons">
                <button class="mobile-search-icon" id="mobileSearchIcon">
                    <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" width="22" height="22" alt="Search">
                </button>
                <a href="<?php echo $baseUrl; ?>cart.php" class="mobile-header-cart">
                    <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" width="22" height="22" alt="Cart">
                    <span class="cart-count"><?php echo $cart_count; ?></span>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Mobile Search Container (expands below header) -->
    <div class="mobile-search-container" id="mobileSearchContainer">
        <form action="<?php echo $baseUrl; ?>product-listings.php" method="GET" class="search-wrapper">
            <input type="search" name="search" placeholder="Search for products..." aria-label="Search products" id="mobile-search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button type="submit" class="search-btn">
                <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" width="18" height="18" alt="Search">
            </button>
        </form>
    </div>
    
    <!-- Mobile Navigation (Sliding Panel from Left) -->
    <div class="mobile-nav" id="mobileNav">
        <!-- Mobile Menu Header with Logo and Close Button -->
        <div class="mobile-menu-header">
            <div class="mobile-menu-logo">
                <a href="<?php echo $baseUrl; ?>index.php">
                    Consu<span>Trade</span>
                </a>
            </div>
            <button class="side-menu-close" id="sideMenuClose">
                <span></span>
                <span></span>
            </button>
        </div>
        
        <!-- Mobile Profile Section (if logged in) -->
        <?php if ($is_logged_in): ?>
            <div class="mobile-profile-section">
                <div class="mobile-profile-info">
                    <img src="<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg" alt="Profile" class="mobile-profile-avatar" width="40" height="40">
                    <div class="mobile-profile-text">
                        <span class="mobile-profile-name"><?php echo htmlspecialchars($user_name); ?></span>
                        <span class="mobile-profile-role"><?php echo ucfirst($user_role); ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Mobile Navigation Links -->
        <ul class="mobile-nav-links">
            <li><a href="<?php echo $baseUrl; ?>index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">Home</a></li>
            <li><a href="<?php echo $baseUrl; ?>product-listings.php" class="<?php echo $current_page == 'product-listings.php' ? 'active' : ''; ?>">Products</a></li>
            <li><a href="<?php echo $baseUrl; ?>sell.php" id="sell-link-mobile">Sell</a></li>
            <li><a href="<?php echo $baseUrl; ?>about.php" class="<?php echo $current_page == 'about.php' ? 'active' : ''; ?>">About</a></li>
            <li><a href="<?php echo $baseUrl; ?>contact.php" class="<?php echo $current_page == 'contact.php' ? 'active' : ''; ?>">Contact</a></li>
            
            <!-- Cart in Mobile Menu -->
            <li>
                <a href="<?php echo $baseUrl; ?>cart.php" class="mobile-menu-cart">
                    <span>Shopping Cart</span>
                    <span class="cart-icon-wrapper">
                        <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" width="20" height="20" alt="Cart">
                        <span class="cart-count"><?php echo $cart_count; ?></span>
                    </span>
                </a>
            </li>
            
            <?php if ($is_logged_in): ?>
                <div class="mobile-menu-divider"></div>
                <?php if ($user_role === 'seller'): ?>
                    <li><a href="<?php echo $baseUrl; ?>admin/seller-dashboard.php">Dashboard</a></li>
                    <li><a href="<?php echo $baseUrl; ?>admin/my-products.php">My Products</a></li>
                    <li><a href="<?php echo $baseUrl; ?>admin/my-orders.php">Orders</a></li>
                <?php else: ?>
                    <li><a href="<?php echo $baseUrl; ?>profile.php">My Profile</a></li>
                    <li><a href="<?php echo $baseUrl; ?>my-orders.php">My Orders</a></li>
                    <li><a href="<?php echo $baseUrl; ?>wishlist.php">Wishlist</a></li>
                <?php endif; ?>
                <li><a href="<?php echo $baseUrl; ?>php/logout.php" class="mobile-logout-link">Logout</a></li>
            <?php else: ?>
                <div class="mobile-menu-divider"></div>
                <li><button class="mobile-nav-btn" id="mobile-login-btn">Login</button></li>
                <li><button class="mobile-nav-btn" id="mobile-register-btn">Sign Up</button></li>
            <?php endif; ?>
        </ul>
    </div>
    
    <!-- Mobile Menu Overlay -->
    <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>
</header>

<!-- Login Modal -->
<div id="login-modal" class="modal">
    <div class="modal-content">
        <button class="btn-close" aria-label="Close"></button>
        <div class="modal-header">
            <h1>Consu<span>Trade</span></h1>
            <p>Welcome back! Please login to your account</p>
        </div>
        
        <div id="login-error-container" class="error-container" style="display: none;">
            <p class="error-message"></p>
        </div>
        
        <form id="login-form" class="login-form" method="POST" action="<?php echo $baseUrl; ?>php/login.php">
            <div class="input-group">
                <label for="login-email">Email Address</label>
                <input type="email" id="login-email" name="email" placeholder="Enter your email address" required autocomplete="email">
                <span class="error-text" style="display: none;"></span>
            </div>
            
            <div class="input-group">
                <label for="login-password">Password</label>
                <div style="position: relative;">
                    <input type="password" id="login-password" name="password" 
                        placeholder="Enter your password" required 
                        autocomplete="current-password"
                        style="width: 100%; padding-right: 40px;">
                    <button type="button" 
                            onclick="togglePassword('login-password', this)" 
                            style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); 
                                background: none; border: none; cursor: pointer; padding: 5px; 
                                border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <img src="<?php echo $baseUrl; ?>images/icons/eye-open-svgrepo-com.svg" 
                            width="18" height="18" style="opacity: 0.6;">
                    </button>
                </div>
                <span class="error-text" style="display: none;"></span>
            </div>
            
            <div class="reset-pass">
                <a href="#">Forgot Password?</a>
            </div>
            
            <button type="submit" class="submit-btn">Login</button>
            
            <div class="register-link">
                Don't have an account? <a href="#" id="switch-to-register">Register here</a>
            </div>
        </form>
    </div>
</div>

<!-- Register Modal -->
<div id="register-modal" class="modal">
    <div class="modal-content">
        <button class="btn-close" aria-label="Close"></button>
        <div class="modal-header">
            <h1>Consu<span>Trade</span></h1>
            <p>Create your account to start buying and selling</p>
        </div>
        
        <div id="register-error-container" class="error-container" style="display: none;">
            <p class="error-message"></p>
        </div>
        
        <form id="register-form" class="register-form" method="POST" action="<?php echo $baseUrl; ?>php/register.php">
            <fieldset class="form-fields">
                <legend>Personal Information</legend>
                
                <div class="input-group">
                    <label for="register-full-name">Full Name</label>
                    <input type="text" id="register-full-name" name="full_name" placeholder="Enter your full name" required autocomplete="name">
                    <span class="error-text" style="display: none;"></span>
                </div>
                
                <div class="input-group">
                    <label for="register-email">Email Address</label>
                    <input type="email" id="register-email" name="email" placeholder="Enter your email address" required autocomplete="email">
                    <span class="error-text" style="display: none;"></span>
                </div>
                
                <div class="input-group">
                    <label for="register-phone">Phone Number</label>
                    <input type="tel" id="register-phone" name="phone" placeholder="Enter your phone number" required autocomplete="tel">
                    <span class="error-text" style="display: none;"></span>
                </div>
            </fieldset>
            
            <fieldset class="form-fields">
                <legend>Security</legend>
                
                <div class="input-group">
                    <label for="register-password">Password</label>
                    <div style="position: relative;">
                        <input type="password" id="register-password" name="password" 
                            placeholder="Create a password" required 
                            autocomplete="new-password"
                            style="width: 100%; padding-right: 40px;">
                        <button type="button" 
                                onclick="togglePassword('register-password', this)" 
                                style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); 
                                    background: none; border: none; cursor: pointer; padding: 5px;
                                    border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <img src="<?php echo $baseUrl; ?>images/icons/eye-open-svgrepo-com.svg" 
                                width="18" height="18" style="opacity: 0.6;">
                        </button>
                    </div>
                    <span class="error-text" style="display: none;"></span>
                </div>

                <div class="input-group">
                    <label for="register-confirm-password">Confirm Password</label>
                    <div style="position: relative;">
                        <input type="password" id="register-confirm-password" name="confirm_password" 
                            placeholder="Confirm your password" required 
                            autocomplete="new-password"
                            style="width: 100%; padding-right: 40px;">
                        <button type="button" 
                                onclick="togglePassword('register-confirm-password', this)" 
                                style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); 
                                    background: none; border: none; cursor: pointer; padding: 5px;
                                    border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <img src="<?php echo $baseUrl; ?>images/icons/eye-open-svgrepo-com.svg" 
                                width="18" height="18" style="opacity: 0.6;">
                        </button>
                    </div>
                    <span class="error-text" style="display: none;"></span>
                </div>
            </fieldset>
            
            <fieldset class="user-type">
                <legend>I want to...</legend>
                <div class="radio-buttons">
                    <input type="radio" id="buyer" name="role" value="buyer" checked>
                    <label for="buyer" class="radio-btn radio">
                        <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" alt="Buy">
                        Buy Products
                    </label>
                    
                    <input type="radio" id="seller" name="role" value="seller">
                    <label for="seller" class="radio-btn radio">
                        <img src="<?php echo $baseUrl; ?>images/icons/sell-svgrepo-com.svg" alt="Sell">
                        Sell Products
                    </label>
                </div>
            </fieldset>
            
            <button type="submit" class="submit-btn">Create Account</button>
            
            <div class="login-link">
                Already have an account? <a href="#" id="switch-to-login">Login here</a>
            </div>
        </form>
    </div>
</div>

<main>