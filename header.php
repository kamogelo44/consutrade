<!-- header.php - Just the header and modals -->
<header>
    <!-- Left: Hamburger -->
    <button class="hamburger" id="hamburger">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <!-- Center: Logo -->
    <div class="logo"><a href="index.php">Consu<span>Trade</span></a></div>

    <!-- Mobile Header Icons - SIMPLIFIED (no profile dropdown) -->
    <div class="header-icons">
        <button class="mobile-search-icon" id="mobileSearchIcon">
            <img src="images/icons/search-svgrepo-com.svg" class="icon-white" width="22px" height="22px" alt="Search">
        </button>
        <a href="cart.php" class="mobile-header-cart">
            <img src="images/icons/shopping-cart-01-svgrepo-com.svg" class="icon-white" width="24px" height="24px" alt="Shopping Cart">
            <span class="cart-count">0</span>
        </a>
    </div>

    <!-- Desktop Search Form -->
    <form action="" method="get" class="desktop-search">
        <div class="search-wrapper">
            <input type="search" id="search" name="q" placeholder="Search for products...">
            <button class="search-btn" type="submit">
                <img src="images/icons/search-svgrepo-com.svg" width="24px" height="24px" alt="Search">
            </button>
        </div>
    </form>

    <!-- Desktop Navigation -->
    <nav class="nav-container" id="nav-menu">
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="product-listings.php">Shop</a></li>
            
            <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                <?php if ($_SESSION['role'] === 'seller'): ?>
                    <li><a href="seller-dashboard.php">Sell</a></li>
                <?php else: ?>
                    <li><a href="sell.php" id="sell-link">Sell</a></li>
                <?php endif; ?>
            <?php else: ?>
                <li><a href="sell.php">Sell</a></li>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                <li class="user-dropdown">
                    <div class="user-info">
                        <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                        <img src="images/icons/profile-svgrepo-com.svg" class="profile-icon" width="32px" height="32px" alt="Profile">
                        <button class="dropdown-toggle" id="desktopDropdownToggle">
                            <img src="images/icons/chevron-down-svgrepo-com.svg" class="icon-white" width="16px" height="16px" alt="Menu">
                        </button>
                    </div>
                    <ul class="dropdown-menu" id="desktopDropdownMenu">
                        <li><a href="profile.php">My Profile</a></li>
                        <li><a href="my-orders.php">My Orders</a></li>
                        <?php if ($_SESSION['role'] === 'seller'): ?>
                            <li><a href="seller-dashboard.php">Seller Dashboard</a></li>
                            <li><a href="my-products.php">My Products</a></li>
                        <?php endif; ?>
                        <li><a href="php/logout.php">Logout</a></li>
                    </ul>
                </li>
            <?php else: ?>
                <li><a href="" id="login">Login</a></li>
                <li><a href="#register-modal" id="register">Register</a></li>
            <?php endif; ?>
        </ul>
        
        <a href="cart.php" class="desktop-cart">
            <img src="images/icons/shopping-cart-01-svgrepo-com.svg" class="icon-white" width="24px" height="24px" alt="Shopping Cart">
            <span class="cart-count">0</span>
        </a>
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
                <a href="index.php">Consu<span>Trade</span></a>
            </div>
        </div>
        
        <!-- PROFILE SECTION INSIDE SIDE MENU-->
        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
            <div class="mobile-profile-section">
                <div class="mobile-profile-info">
                    <img src="images/icons/profile-svgrepo-com.svg" class="mobile-profile-avatar" width="40px" height="40px" alt="Profile">
                    <div class="mobile-profile-text">
                        <span class="mobile-profile-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                        <span class="mobile-profile-role"><?php echo ucfirst($_SESSION['role']); ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <ul class="mobile-nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="product-listings.php">Shop</a></li>
            
            <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                <?php if ($_SESSION['role'] === 'seller'): ?>
                    <li><a href="seller-dashboard.php">Sell</a></li>
                <?php else: ?>
                    <li><a href="sell.php" class="sell-link-mobile">Sell</a></li>
                <?php endif; ?>
            <?php else: ?>
                <li><a href="sell.php">Sell</a></li>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                <li class="mobile-menu-divider"></li>
                <li><a href="profile.php">My Profile</a></li>
                <li><a href="my-orders.php">My Orders</a></li>
                <?php if ($_SESSION['role'] === 'seller'): ?>
                    <li><a href="seller-dashboard.php">Seller Dashboard</a></li>
                    <li><a href="my-products.php">My Products</a></li>
                <?php endif; ?>
                <li class="mobile-menu-divider"></li>
                <li><a href="php/logout.php" class="mobile-logout-link">Logout</a></li>
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
                    <img src="images/icons/search-svgrepo-com.svg" width="20px" height="20px" alt="Search">
                </button>
            </div>
        </div>
        
        <a href="cart.php" class="mobile-menu-cart">
            <span class="cart-text">Cart</span>
            <div class="cart-icon-wrapper">
                <img src="images/icons/shopping-cart-01-svgrepo-com.svg" class="icon-white" width="24px" height="24px" alt="Shopping Cart">
                <span class="cart-count">0</span>
            </div>
        </a>
    </div>

    <!-- Mobile Expandable Search -->
    <div class="mobile-search-container" id="mobileSearchContainer">
        <form action="" method="get" class="mobile-search-form">
            <div class="search-wrapper">
                <input type="search" id="mobile-search" name="q" placeholder="Search for products...">
                <button class="search-btn" type="submit">
                    <img src="images/icons/search-svgrepo-com.svg" width="20px" height="20px" alt="Search">
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
        
        <form action="php/register.php" method="post" class="register-form" id="register-form">
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
                            <img src="images/icons/eye-open-svgrepo-com.svg" width="20" height="20" alt="Show password">
                        </button>
                    </div>
                    <small class="error-text" id="password-error"></small>
                </div>
                
                <div class="input-group password-group">
                    <label for="confirm-password">Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirm-password" name="confirm_password" placeholder="Repeat your password" required>
                        <button type="button" class="toggle-password" data-target="confirm-password">
                            <img src="images/icons/eye-open-svgrepo-com.svg" width="20" height="20" alt="Show password">
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
                        <img src="images/icons/buy-cash-finance-svgrepo-com.svg" width="20px" height="20px" alt="Buy icon">
                        Buy Products
                    </label>
                    
                    <input type="radio" id="sell" name="user_type" value="seller">
                    <label for="sell" class="radio-btn radio">
                        <img src="images/icons/sell-svgrepo-com.svg" width="20px" height="20px" alt="Sell icon">
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
        
        <form action="php/login.php" method="post" class="login-form">
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
                            <img src="images/icons/eye-open-svgrepo-com.svg" width="20" height="20" alt="Show password">
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