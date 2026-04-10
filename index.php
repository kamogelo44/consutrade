<?php
session_start();

// Read register errors
$registerErrors = $_SESSION['register_errors'] ?? [];
$registerFormData = $_SESSION['register_form_data'] ?? [];
unset($_SESSION['register_errors']);
unset($_SESSION['register_form_data']);
// Read login errors
$loginErrors = $_SESSION['login_errors'] ?? [];
$loginEmail = $_SESSION['login_email'] ?? '';
unset($_SESSION['login_errors']);
unset($_SESSION['login_email']);

// Read flash message
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ConsuTrade - Buy and Sell Across South Africa</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/login-signup.css">
    
    <?php if (!empty($registerErrors)): ?>
    <script>
    //Open the modal if there are errors
    document.addEventListener('DOMContentLoaded', function() {
        var modal = document.getElementById('register-modal');
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        // Fill in the form data that was submitted
        <?php if (!empty($registerFormData['fullname'])): ?>
        var fullnameInput = document.getElementById('fullname');
        if (fullnameInput) fullnameInput.value = '<?php echo addslashes($registerFormData['fullname']); ?>';
        <?php endif; ?>
        
        <?php if (!empty($registerFormData['email'])): ?>
        var emailInput = document.getElementById('email');
        if (emailInput) emailInput.value = '<?php echo addslashes($registerFormData['email']); ?>';
        <?php endif; ?>
        
        <?php if (!empty($registerFormData['role']) && $registerFormData['role'] == 'seller'): ?>
        var sellRadio = document.getElementById('sell');
        if (sellRadio) sellRadio.checked = true;
        <?php endif; ?>
        
        // Show error messages
        <?php foreach ($registerErrors as $field => $message): ?>
        var errorEl = document.getElementById('<?php echo $field; ?>-error');
        if (errorEl) {
            errorEl.textContent = '<?php echo addslashes($message); ?>';
            var inputGroup = errorEl.closest('.input-group');
            if (inputGroup) inputGroup.classList.add('error');
        }
        <?php endforeach; ?>
        
        // Show error container
        var errorContainer = document.getElementById('register-error-container');
        if (errorContainer) {
            errorContainer.style.display = 'block';
        }
    });
    </script>
    <?php endif; ?>

    <?php if (!empty($loginErrors)): ?>
    <script>
    // Open login modal if there are login errors
    document.addEventListener('DOMContentLoaded', function() {
        var modal = document.getElementById('login-modal');
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        // Pre-fill email
        var emailInput = document.getElementById('login-email');
        if (emailInput) {
            emailInput.value = '<?php echo addslashes($loginEmail); ?>';
        }
        
        // Show error messages
        <?php foreach ($loginErrors as $field => $message): ?>
        var errorEl = document.createElement('small');
        errorEl.className = 'error-text';
        errorEl.style.color = '#f44336';
        errorEl.style.display = 'block';
        errorEl.style.marginTop = '6px';
        errorEl.textContent = '<?php echo addslashes($message); ?>';
        
        var inputGroup = document.querySelector('#login-modal .input-group:has(#login-<?php echo $field; ?>)');
        if (inputGroup) {
            inputGroup.classList.add('error');
            inputGroup.appendChild(errorEl);
        }
        <?php endforeach; ?>
    });
    </script>
    <?php endif; ?>
</head>
<body>

    <!--Header-->
    <header>
        <!-- Left: Hamburger -->
        <button class="hamburger" id="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <!-- Center: Logo -->
        <div class="logo"><a href="index.php">Consu<span>Trade</span></a></div>

        <!-- Mobile Header Icons (visible when menu closed) -->
        <div class="header-icons">
            <button class="mobile-search-icon" id="mobileSearchIcon">
                <img src="images/icons/search-svgrepo-com.svg" class="icon-white" width="22px" height="22px" alt="Search">
            </button>
            <a href="cart.html" class="mobile-header-cart">
                <img src="images/icons/shopping-cart-01-svgrepo-com.svg" class="icon-white" width="24px" height="24px" alt="Shopping Cart">
                <span class="cart-count">0</span>
            </a>
        </div>

        <!-- Desktop Search Form -->
        <form action="" method="get" class="desktop-search">
            <div class="search-wrapper">
                <input type="search"
                    id="search"
                    name="q"
                    placeholder="Search for products...">
                <button class="search-btn" type="submit">
                    <img src="images/icons/search-svgrepo-com.svg" width="24px" height="24px" alt="Search">
                </button>
            </div>
        </form>

        <!-- Desktop Navigation -->
        <nav class="nav-container" id="nav-menu">
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="product-listings.html">Shop</a></li>
                <li><a href="sell.html">Sell</a></li>
                <li><a href="" id="login">Login</a></li>
                <li><a href="#register-modal" id="register">Register</a></li>
            </ul>
            <a href="cart.html" class="desktop-cart">
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
            
            <ul class="mobile-nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="product-listings.html">Shop</a></li>
                <li><a href="sell.html">Sell</a></li>
                <li><a href="#login-modal" class="login-link-mobile">Login</a></li>
                <li><a href="#register-modal" class="register-link-mobile">Register</a></li>
            </ul>
            
            <div class="mobile-menu-search">
                <div class="search-wrapper">
                    <input type="search"
                        id="mobile-menu-search"
                        name="q"
                        placeholder="Search for products...">
                    <button class="search-btn" type="submit">
                        <img src="images/icons/search-svgrepo-com.svg" width="20px" height="20px" alt="Search">
                    </button>
                </div>
            </div>
            
            <a href="cart.html" class="mobile-menu-cart">
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
                    <input type="search"
                        id="mobile-search"
                        name="q"
                        placeholder="Search for products...">
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
            
            <!-- Error message container -->
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

            <!-- Error container for login -->
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

    <main class="content">
        <?php if ($flash): ?>
        <div class="flash" style="background:#e6f4ea;border-left:4px solid #2e7d32;padding:12px 20px;margin:20px auto;max-width:1200px;font-size:14px;">
            <?php echo htmlspecialchars($flash); ?>
        </div>
        <?php endif; ?>
        
        <!--Hero section-->
        <section class="hero">
            <div class="hero-container">
                <div class="txt-container">
                    <h1>Buy and Sell at the comfort of your home in South Africa.</h1>
                    <p>ConsuTrade connects informal traders with buyers across the country.</p>
                </div>
                <div class="btn-container">
                    <button class="sell-btn" id="primary-btn">Start Selling</button>
                    <button class="browse-btn" id="secondary-btn" onclick="window.location.href='product-listings.html'">Shop Products</button>
                </div>
            </div>
        </section>
    
        <!--How it works section-->
        <section class="how">
            <h1 class="section-heading">How it works</h1>
            <div class="how-container">
                <div class="card">
                    <img src="images/icons/register-svgrepo-com.svg" width="48px" height="48px" alt="" class="icon">
                    <h2>Register</h2>
                    <p>Create your free seller account</p>
                </div>

                <img src="images/icons/right-arrow-1-svgrepo-com.svg" class="arrow" width="48px" height="48px" alt="arrow">

                <div class="card">
                    <img src="images/icons/product-catalog-svgrepo-com.svg" width="48px" height="48px" alt="" class="icon">
                    <h2>List</h2>
                    <p>Upload your products in minutes</p>
                </div>

                <img src="images/icons/right-arrow-1-svgrepo-com.svg" class="arrow" width="48px" height="48px" alt="arrow">

                <div class="card">
                    <img src="images/icons/cash-atm-svgrepo-com.svg" width="48px" height="48px" alt="" class="icon">
                    <h2>Get Paid</h2>
                    <p>Receive payments securely with <a href="https://www.payfast.co.za" target="_blank">PayFast</a></p>
                </div>
            </div>
        </section>
    
        <!--Featured products section-->
        <section class="featured">
            <h1 class="section-heading">Recently Listed</h1>
            <div class="prod-grid">
                <!-- Card 1 -->
                <div class="prod-card">
                    <div class="img-container">
                        <img src="" alt="Product Image">
                    </div>
                    <p class="prod-name">Product Name</p>
                    <p class="prod-price">R 0.00</p>
                    <div class="seller-info">
                        <img src="" alt="Seller Profile Picture">
                        <p class="seller-name">Seller: Gethro Molungsi</p>
                        <p class="location">Polokwane</p>
                        <img src="images/icons/verified-svgrepo-com.svg" width="24px" height="24px" alt="Verified">
                        <img src="images/icons/not-verified-svgrepo-com.svg" class="not-verified-icon" width="24px" height="24px" alt="Not Verified">
                    </div>
                </div>

                <!-- Card 2 -->
                <div class="prod-card">
                    <div class="img-container">
                        <img src="" alt="Product Image">
                    </div>
                    <p class="prod-name">Product Name</p>
                    <p class="prod-price">R 0.00</p>
                    <div class="seller-info">
                        <img src="" alt="Seller Profile Picture">
                        <p class="seller-name">Seller: Gethro Molungsi</p>
                        <p class="location">Polokwane</p>
                        <img src="images/icons/verified-svgrepo-com.svg" width="24px" height="24px" alt="Verified">
                        <img src="images/icons/not-verified-svgrepo-com.svg" class="not-verified-icon" width="24px" height="24px" alt="Not Verified">
                    </div>
                </div>

                <!-- Card 3 -->
                <div class="prod-card">
                    <div class="img-container">
                        <img src="" alt="Product Image">
                    </div>
                    <p class="prod-name">Product Name</p>
                    <p class="prod-price">R 0.00</p>
                    <div class="seller-info">
                        <img src="" alt="Seller Profile Picture">
                        <p class="seller-name">Seller: Gethro Molungsi</p>
                        <p class="location">Polokwane</p>
                        <img src="images/icons/verified-svgrepo-com.svg" width="24px" height="24px" alt="Verified">
                        <img src="images/icons/not-verified-svgrepo-com.svg" class="not-verified-icon" width="24px" height="24px" alt="Not Verified">
                    </div>
                </div>

                <!-- Card 4 -->
                <div class="prod-card">
                    <div class="img-container">
                        <img src="" alt="Product Image">
                    </div>
                    <p class="prod-name">Product Name</p>
                    <p class="prod-price">R 0.00</p>
                    <div class="seller-info">
                        <img src="" alt="Seller Profile Picture">
                        <p class="seller-name">Seller: Gethro Molungsi</p>
                        <p class="location">Polokwane</p>
                        <img src="images/icons/verified-svgrepo-com.svg" width="24px" height="24px" alt="Verified">
                        <img src="images/icons/not-verified-svgrepo-com.svg" class="not-verified-icon" width="24px" height="24px" alt="Not Verified">
                    </div>
                </div>
            </div>
        </section>
    
        <!--Trust banner-->
        <section class="trust">
            <div class="card">
                <img src="images/icons/secure-card-svgrepo-com.svg" style="filter: brightness(0) invert(1);" width="48px" height="48px" alt="secure payments" class="icon">
                <h2>Secure Payments</h2>
                <p>PayFast protected</p>
            </div>
            <div class="card">
                <img src="images/icons/verified-svgrepo-com.svg" style="filter: brightness(0) invert(1);" width="48px" height="48px" alt="verfied" class="icon">
                <h2>Verified Sellers</h2>
                <p>All sellers are checked</p>
            </div>
            <div class="card">
                <img src="images/icons/delivery-svgrepo-com.svg" style="filter: brightness(0) invert(1);" width="48px" height="48px" alt="delivery" class="icon">
                <h2>Nationwide Delivery</h2>
                <p>We deliver across SA</p>
            </div>
        </section>
    </main>

    <!--Footer-->
    <footer>
        <div class="footer-container">
            <div class="logo-tag">
                <div class="logo"><a href="index.php">Consu<span>Trade</span></a></div>
                <p class="footer-tagline">Built for South Africa's informal economy</p>
            </div>
            
            <div class="footer-links">
                <a href="index.php">Home</a>
                <a href="product-listings.html">Shop</a>
                <a href="">Sell</a>
                <a href="">About</a>
                <a href="">Terms</a>
                <a href="">Privacy</a>
                <a href="">Contact</a>
            </div>
        </div>
        
        <hr>
        
        <p class="copyright">© 2026 ConsuTrade. All rights reserved.</p>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>