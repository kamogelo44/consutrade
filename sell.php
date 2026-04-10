<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ConsuTrade - Start Selling in South Africa</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/login-signup.css">
    <link rel="stylesheet" href="css/sell.css">
</head>
<body>
    <!--Header-->
    <?php include 'header.php'?>

    <!-- Registration Modal -->
    <div id="register-modal" class="modal">
        <div class="modal-content">
            <button type="button" class="btn-close"></button>
            <div class="modal-header">
                <h1>Consu<span>Trade</span></h1>
                <p>Join thousands of South African traders</p>
            </div>
            <form action="" method="post" class="register-form">
                <fieldset class="form-fields">
                    <legend>Create Account</legend>
                    <div class="input-group">
                        <label for="fullname">Full Name</label>
                        <input type="text" id="fullname" name="fullname" placeholder="Enter your full name..." required>
                    </div>
                    <div class="input-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email address" required>
                    </div>
                    <div class="input-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Create password" required>
                    </div>
                    <div class="input-group">
                        <label for="confirm-password">Confirm Password</label>
                        <input type="password" id="confirm-password" name="confirm_password" placeholder="Repeat your password" required>
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
            <form action="" method="post" class="login-form">
                <fieldset class="form-fields">
                    <legend>Login Account</legend>
                    <div class="input-group">
                        <label for="login-email">Email Address</label>
                        <input type="email" id="login-email" name="email" placeholder="Enter your email address" required>
                    </div>
                    <div class="input-group">
                        <label for="login-password">Password</label>
                        <input type="password" id="login-password" name="password" placeholder="Enter your password" required>
                    </div>
                </fieldset>
                <p class="reset-pass"><a href="#">Forgot your password?</a></p>
                <button type="submit" class="submit-btn">Login Account</button>
                <p class="register-link">Don't have an account? <a href="#register-modal">Register</a></p>
            </form>
        </div>
    </div>

    <main>
        <!-- Hero Section -->
        <section class="seller-hero">
            <div class="seller-hero-container">
                <h1 class="seller-hero-title">Start Selling on ConsuTrade Today</h1>
                <p class="seller-hero-subtitle">Reach buyers across South Africa with Africa's growing marketplace</p>
                <div class="seller-hero-buttons">
                    <button class="register-now-btn" id="seller-register-btn">Register Now</button>
                    <button class="login-now-btn" id="seller-login-btn">Login</button>
                </div>
            </div>
        </section>

        <!-- Why Sell With Us Section -->
        <section class="why-sell">
            <h2 class="section-heading">Why Sell With Us</h2>
            <div class="why-sell-container">
                <div class="why-sell-card">
                    <img src="images/icons/register-svgrepo-com.svg" width="48px" height="48px" alt="Free to join" class="why-sell-icon">
                    <h3>Free to Join</h3>
                    <p>No upfront costs. Create your seller account for free and start listing products immediately.</p>
                </div>
                <div class="why-sell-card">
                    <img src="images/icons/verified-svgrepo-com.svg" width="48px" height="48px" alt="Verified badge" class="why-sell-icon">
                    <h3>Verified Badge</h3>
                    <p>Get a verified seller badge after completing your profile, building trust with buyers.</p>
                </div>
                <div class="why-sell-card">
                    <img src="images/icons/delivery-svgrepo-com.svg" width="48px" height="48px" alt="Reach buyers" class="why-sell-icon">
                    <h3>Reach Buyers</h3>
                    <p>Connect with thousands of active buyers looking for products across South Africa.</p>
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

        <!-- What You Need Section -->
        <section class="requirements">
            <div class="requirements-container">
                <h2 class="section-heading">What You Need to Get Started</h2>
                <div class="requirements-list">
                    <div class="requirement-item">
                        <span class="requirement-icon"><img src="images/icons/valid-document-svgrepo-com.svg" width="32px" height="32px" alt="SA ID document"></span>
                        <p>Valid SA ID number</p>
                    </div>
                    <div class="requirement-item">
                        <span class="requirement-icon"><img src="images/icons/phone-number.svg" width="32px" height="32px" alt="Phone Number"></span>
                        <p>Phone number</p>
                    </div>
                    <div class="requirement-item">
                        <span class="requirement-icon"><img src="images/icons/Payfast logo.svg" width="32px" height="32px" alt="PayFast Logo"></span>
                        <p>PayFast account <span class="requirement-note">(free to create)</span></p>
                    </div>
                    <div class="requirement-item">
                        <span class="requirement-icon"><img src="images/icons/photos-filled-svgrepo-com.svg" width="32px" height="32px" alt="Product Photos"></span>
                        <p>Product photos</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Ready to Start Section -->
        <section class="ready-to-start">
            <div class="ready-container">
                <h2 class="ready-title">Ready to Start Selling?</h2>
                <p class="ready-subtitle">Join thousands of successful sellers on ConsuTrade</p>
                <button class="create-seller-btn" id="create-seller-btn">Create Your Seller Account</button>
            </div>
        </section>
    </main>

    <!--Footer-->
    <?php include 'footer.php'?>

    <script src="js/main.js"></script>
</body>
</html>