<?php
/*
 * ConsuTrade - Start Selling Page
 * Author: Kamogelo Phale
 * 
 * Landing page for potential sellers to learn about selling on ConsuTrade
 */

require_once __DIR__ . '/init.php';

$baseUrl = getBaseUrl();

// If user is already logged in as buyer, redirect to home (buyers can't access sell page)
if ($is_logged_in && $current_user['role'] === 'buyer') {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

// If seller is logged in, redirect to seller dashboard
if ($is_logged_in && $current_user['role'] === 'seller') {
    header('Location: ' . $baseUrl . 'admin/seller-dashboard.php');
    exit;
}

// If admin is logged in, redirect to admin dashboard
if ($is_logged_in && $current_user['role'] === 'admin') {
    header('Location: ' . $baseUrl . 'admin/admin-dashboard.php');
    exit;
}

// Only guests (not logged in) can see this page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Start Selling in South Africa - ConsuTrade</title>
    <meta name="author" content="Kamogelo Phale">
    <meta name="description" content="Join ConsuTrade and start selling your products to buyers across South Africa. Free to join, secure payments via PayFast.">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/animations.css">
    <link rel="stylesheet" href="css/login-signup.css">
    <link rel="stylesheet" href="css/sell.css">
    <link rel="stylesheet" href="css/products.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/footer.css">
</head>
<body>
    <!--Header-->
    <?php include 'includes/header.php'; ?>

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
                    <img src="images/icons/register-svgrepo-com.svg" width="48px" height="48px" alt="Free to join" class="why-sell-icon" loading="lazy">
                    <h3>Free to Join</h3>
                    <p>No upfront costs. Create your seller account for free and start listing products immediately.</p>
                </div>
                <div class="why-sell-card">
                    <img src="images/icons/verified-svgrepo-com.svg" width="48px" height="48px" alt="Verified badge" class="why-sell-icon" loading="lazy">
                    <h3>Verified Badge</h3>
                    <p>Get a verified seller badge after completing your profile, building trust with buyers.</p>
                </div>
                <div class="why-sell-card">
                    <img src="images/icons/delivery-svgrepo-com.svg" width="48px" height="48px" alt="Reach buyers" class="why-sell-icon" loading="lazy">
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
                    <img src="images/icons/register-svgrepo-com.svg" width="48px" height="48px" alt="" class="icon" loading="lazy">
                    <h2>Register</h2>
                    <p>Create your free seller account</p>
                </div>

                <img src="images/icons/right-arrow-1-svgrepo-com.svg" class="arrow" width="48px" height="48px" alt="arrow" loading="lazy">

                <div class="card">
                    <img src="images/icons/product-catalog-svgrepo-com.svg" width="48px" height="48px" alt="" class="icon" loading="lazy">
                    <h2>List</h2>
                    <p>Upload your products in minutes</p>
                </div>

                <img src="images/icons/right-arrow-1-svgrepo-com.svg" class="arrow" width="48px" height="48px" alt="arrow" loading="lazy">

                <div class="card">
                    <img src="images/icons/cash-atm-svgrepo-com.svg" width="48px" height="48px" alt="" class="icon" loading="lazy">
                    <h2>Get Paid</h2>
                    <p>Receive payments securely with 
                        <a href="https://www.payfast.co.za" class="payfast-badge" target="_blank" rel="noopener noreferrer">
                            <img src="images/icons/Payfast logo.svg" alt="PayFast icon" width="60" height="20" loading="lazy">
                        </a>
                    </p>
                </div>
            </div>
        </section>

        <!-- What You Need Section -->
        <section class="requirements">
            <div class="requirements-container">
                <h2 class="section-heading">What You Need to Get Started</h2>
                <div class="requirements-list">
                    <div class="requirement-item">
                        <span class="requirement-icon"><img src="images/icons/valid-document-svgrepo-com.svg" width="32px" height="32px" alt="SA ID document" loading="lazy"></span>
                        <p>Valid SA ID number</p>
                    </div>
                    <div class="requirement-item">
                        <span class="requirement-icon"><img src="images/icons/phone-number.svg" width="32px" height="32px" alt="Phone Number" loading="lazy"></span>
                        <p>Phone number</p>
                    </div>
                    <div class="requirement-item">
                        <span class="requirement-icon"><img src="images/icons/Payfast logo.svg" width="32px" height="32px" alt="PayFast Logo" loading="lazy"></span>
                        <p>PayFast account <span class="requirement-note">(free to create)</span></p>
                    </div>
                    <div class="requirement-item">
                        <span class="requirement-icon"><img src="images/icons/photos-filled-svgrepo-com.svg" width="32px" height="32px" alt="Product Photos" loading="lazy"></span>
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
    <?php include 'includes/footer.php'; ?>

    <script src="js/main.js"></script>
    <script>
    /*
     * ConsuTrade - Sell Page Functionality
     */
    $(document).ready(function() {
        // Register button - open registration modal with seller role selected
        $('#seller-register-btn, #create-seller-btn').on('click', function(e) {
            e.preventDefault();
            // Open registration modal
            openModal($('#register-modal'));
            // Pre-select seller role
            $('#seller').prop('checked', true);
            // Clear any existing errors
            clearRegisterErrors();
        });
        
        // Login button - open login modal
        $('#seller-login-btn').on('click', function(e) {
            e.preventDefault();
            openModal($('#login-modal'));
            clearLoginErrors();
        });
    });
    </script>
</body>
</html>