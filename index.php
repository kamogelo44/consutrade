<?php
/*
 * ConsuTrade - Homepage
 * Author: Kamogelo Phale
 * 
 * Main landing page displaying featured products and site information
 */

require_once __DIR__ . '/init.php';
include __DIR__ . '/includes/session-vars.php';
include __DIR__ . '/includes/functions.php';

// Get 3 real products for hero display
$heroProducts = $productRepo->findAll('active', '', 3, 0);

$load_products_js = true;
$page_js = 'index.js';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ConsuTrade - Buy and Sell Across South Africa</title>
    <meta name="description" content="Buy and sell products from local South African traders. Secure payments with PayFast.">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <main class="content">
        <?php include 'includes/flash-message.php'; ?>

        <?php if (isset($_GET['verified']) && $_GET['verified'] === 'pending'): ?>
            <div class="verification-notice">
                <img src="images/icons/email-svgrepo-com.svg" width="20" height="20" alt="Email">
                <span>Account created! Please check your email to verify your account before logging in.</span>
            </div>
        <?php endif; ?>

        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-grid">
                <div class="hero-content">
                    <h1>Your spaza shop, <span class="hero-highlight">online</span></h1>
                    <p class="hero-subtitle">
                        Buy and sell with real people in your community. Verified sellers, PayFast payments, no scams.
                    </p>
                    <div class="hero-actions">
                        <a href="product-listings.php" class="hero-btn hero-btn-primary">Browse Products</a>
                        <button class="hero-btn hero-btn-secondary" id="primary-btn">Start Selling</button>
                    </div>
                    <div class="hero-trust">
                        <img src="images/icons/Payfast logo.svg" alt="PayFast" width="70" height="20">
                        <span class="hero-trust-divider"></span>
                        <img src="images/icons/verified-svgrepo-com.svg" alt="Verified" width="16" height="16" style="filter: brightness(0) invert(1);">
                        <span>Verified sellers</span>
                    </div>
                </div>
                <div class="hero-visual">
                    <div class="hero-visual-card" id="hero-products">
                        <!-- Products load here via AJAX -->
                        <div class="hero-visual-row skeleton-row">
                            <div class="skeleton skeleton-image"></div>
                            <div style="flex:1;">
                                <div class="skeleton skeleton-text" style="width:70%;"></div>
                                <div class="skeleton skeleton-text" style="width:40%;margin-top:4px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- How it works section -->
        <section class="how">
            <div class="section-header">
                <h2 class="section-heading">How It Works</h2>
                <p class="section-subtitle">Three simple steps to start buying and selling safely</p>
            </div>
            <div class="how-container">
                <div class="process-card" data-step="01">
                    <div class="process-icon">
                        <img src="images/icons/register-svgrepo-com.svg" width="48" height="48" alt="Register" loading="lazy">
                    </div>
                    <h3>Create Your Account</h3>
                    <p>Sign up for free as a buyer or seller. Sellers verify with SA ID to earn a trusted badge.</p>
                </div>
                <div class="process-card" data-step="02">
                    <div class="process-icon">
                        <img src="images/icons/product-catalog-svgrepo-com.svg" width="48" height="48" alt="Browse" loading="lazy">
                    </div>
                    <h3>List or Browse</h3>
                    <p>Sellers upload products. Buyers browse listings from verified traders near them.</p>
                </div>
                <div class="process-card" data-step="03">
                    <div class="process-icon">
                        <img src="images/icons/cash-atm-svgrepo-com.svg" width="48" height="48" alt="Trade" loading="lazy">
                    </div>
                    <h3>Trade with Confidence</h3>
                    <p>Pay securely through PayFast. Both buyer and seller are protected on every transaction.</p>
                </div>
            </div>
        </section>

        <!-- Featured products section -->
        <section class="featured">
            <div class="featured-header">
                <div>
                    <h2 class="section-heading">Latest from the Community</h2>
                    <p class="section-subtitle">Recently listed by verified South African traders</p>
                </div>
                <a href="product-listings.php" class="view-all-link">View All Products →</a>
            </div>
            <div class="prod-grid" id="featured-products-grid">
                <div class="loading-spinner">Loading products...</div>
            </div>
        </section>

        <!-- Trust section -->
        <section class="trust">
            <div class="trust-container">
                <div class="trust-header">
                    <h2>The Safer Way to Trade</h2>
                    <p>What makes ConsuTrade different from WhatsApp and Facebook Marketplace</p>
                </div>
                <div class="trust-grid">
                    <div class="trust-card">
                        <div class="trust-icon-wrapper">
                            <img src="images/icons/verified-svgrepo-com.svg" alt="Verified Sellers" class="trust-icon" width="48" height="48">
                        </div>
                        <h3>Verified Sellers</h3>
                        <p>Every seller verifies their identity with SA ID. No fake profiles, no scammers.</p>
                    </div>
                    <div class="trust-card">
                        <div class="trust-icon-wrapper">
                            <img src="images/icons/Payfast logo.svg" alt="PayFast" class="trust-partner-logo" width="120" height="32">
                        </div>
                        <h3>PayFast Protected</h3>
                        <p>All payments go through PayFast — South Africa's trusted payment gateway. No cash-in-envelope risks.</p>
                    </div>
                    <div class="trust-card">
                        <div class="trust-icon-wrapper">
                            <img src="images/icons/product-catalog-svgrepo-com.svg" alt="Order Tracking" class="trust-icon" width="48" height="48">
                        </div>
                        <h3>Order Tracking</h3>
                        <p>Track every order from purchase to completion. Know exactly where your order stands.</p>
                    </div>
                </div>
                <a href="about.php" class="learn-more-link">Learn more about how ConsuTrade works →</a>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/modal-errors.php'; ?>

</body>

</html>