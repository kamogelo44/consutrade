<?php
/*
 * ConsuTrade - Homepage
 * Author: Kamogelo Phale
 * 
 * Main landing page displaying featured products and site information
 * Uses components: header.php, footer.php, product-card (via functions.php)
 */

require_once __DIR__ . '/init.php';
include __DIR__ . '/includes/session-vars.php';
include __DIR__ . '/includes/functions.php';
$load_products_js = true;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ConsuTrade - Buy and Sell Across South Africa</title>
    <meta name="description" content="Buy and sell products from local South African traders. Secure payments with PayFast.">

    <!-- CSS Files -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">

    <!-- Lucide Icons CDN for icons we don't have locally -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <main class="content">
        <?php include 'includes/flash-message.php'; ?>

        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-grid">
                <div class="hero-content">
                    <div class="hero-badge">
                        <span class="pulse-dot"></span>
                        South Africa's C2C Marketplace
                    </div>
                    <h1>Buy and sell with <span class="hero-highlight">real people</span> in your community</h1>
                    <p class="hero-subtitle">
                        The trusted platform for informal traders and buyers. No fake profiles. No scams. Just verified sellers and secure PayFast payments.
                    </p>
                    <div class="hero-actions">
                        <button class="sell-btn" id="primary-btn">
                            Start Selling Today
                            <span class="btn-subtitle">Free to list — no monthly fees</span>
                        </button>
                        <button class="browse-btn" onclick="window.location.href='product-listings.php'">
                            Browse Products
                        </button>
                    </div>
                    <div class="hero-trust">
                        <div class="hero-trust-item">
                            <img src="images/icons/Payfast logo.svg" alt="PayFast Secure Payments" width="80" height="24">
                        </div>
                        <div class="hero-trust-item">
                            <img src="images/icons/verified-svgrepo-com.svg" alt="Verified Sellers" width="20" height="20" style="filter: brightness(0) invert(1);">
                            <span>Verified sellers you can trust</span>
                        </div>
                    </div>
                </div>
                <div class="hero-visual">
                    <div class="hero-card hero-card--1">
                        <div class="mini-product">
                            <div class="mini-product-icon">
                                <img src="images/icons/product-catalog-svgrepo-com.svg" alt="Product" width="28" height="28">
                            </div>
                            <div class="mini-product-info">
                                <span>Handmade Baskets</span>
                                <strong>R350</strong>
                                <small class="verified-tag">
                                    <img src="images/icons/verified-svgrepo-com.svg" width="12" height="12" alt="Verified" style="vertical-align: middle;">
                                    Verified Seller
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="hero-card hero-card--2">
                        <div class="mini-product">
                            <div class="mini-product-icon">
                                <img src="images/icons/product-catalog-svgrepo-com.svg" alt="Product" width="28" height="28">
                            </div>
                            <div class="mini-product-info">
                                <span>Fresh Vegetables</span>
                                <strong>R85</strong>
                                <small class="verified-tag">
                                    <img src="images/icons/verified-svgrepo-com.svg" width="12" height="12" alt="Verified" style="vertical-align: middle;">
                                    Verified Seller
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="hero-card hero-card--3">
                        <div class="mini-product">
                            <div class="mini-product-icon">
                                <img src="images/icons/product-catalog-svgrepo-com.svg" alt="Product" width="28" height="28">
                            </div>
                            <div class="mini-product-info">
                                <span>Shweshwe Dress</span>
                                <strong>R580</strong>
                                <small class="verified-tag">
                                    <img src="images/icons/verified-svgrepo-com.svg" width="12" height="12" alt="Verified" style="vertical-align: middle;">
                                    Verified Seller
                                </small>
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
                        <!--  UserPlus icon -->
                        <i data-lucide="user-plus" style="width: 48px; height: 48px; color: var(--primary-color);"></i>
                    </div>
                    <h3>Create Your Account</h3>
                    <p>Sign up for free as a buyer or seller. Sellers verify with SA ID to earn a trusted badge.</p>
                </div>
                <div class="process-card" data-step="02">
                    <div class="process-icon">
                        <!-- product-catalog icon fits "list products" -->
                        <img src="images/icons/product-catalog-svgrepo-com.svg" width="48" height="48" alt="List or Browse" loading="lazy">
                    </div>
                    <h3>List or Browse</h3>
                    <p>Sellers upload products. Buyers browse listings from verified traders near them.</p>
                </div>
                <div class="process-card" data-step="03">
                    <div class="process-icon">
                        <!-- cash-atm icon fits "get paid" / "trade" -->
                        <img src="images/icons/cash-atm-svgrepo-com.svg" width="48" height="48" alt="Trade Safely" loading="lazy">
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
                <a href="product-listings.php" class="view-all-link">
                    View All Products
                    <!-- ArrowRight icon -->
                    <i data-lucide="arrow-right" style="width: 16px; height: 16px;"></i>
                </a>
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
                        <p>Every seller verifies their identity with SA ID. Look for the verified badge — no fake profiles, no scammers.</p>
                    </div>
                    <div class="trust-card">
                        <div class="trust-icon-wrapper">
                            <img src="images/icons/Payfast logo.svg" alt="PayFast" class="trust-partner-logo" width="120" height="32">
                        </div>
                        <h3>PayFast Protected</h3>
                        <p>Payments go through PayFast. No cash-in-envelope risks like on Facebook Marketplace.</p>
                    </div>
                    <div class="trust-card">
                        <div class="trust-icon-wrapper">
                            <i data-lucide="package-search" class="trust-icon" style="width: 48px; height: 48px;"></i>
                        </div>
                        <h3>Order Tracking</h3>
                        <p>Track every order from purchase to completion. Know exactly where your order stands at all times.</p>
                    </div>
                </div>
                <a href="about.php" class="learn-more-link">
                    Learn more about how ConsuTrade works
                    <i data-lucide="arrow-right" style="width: 16px; height: 16px; vertical-align: middle;"></i>
                </a>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/modal-errors.php'; ?>

    <!-- Initialize Lucide icons -->
    <script>
        lucide.createIcons();
    </script>

    <!-- Page specific Javascript-->
    <script>
        function loadFeaturedProducts() {
            var $grid = $('#featured-products-grid');
            $grid.html('<div class="loading-spinner">Loading products...</div>');

            $.ajax({
                url: baseUrl + 'php/endpoints/products/get-products.php?limit=4&page=1',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    if (data.success && data.products && data.products.length > 0) {
                        displayProducts(data.products, '#featured-products-grid');
                    } else {
                        showFeaturedEmptyState();
                    }
                },
                error: function() {
                    $grid.html(
                        '<div class="empty-state">' +
                        '<img src="' + baseUrl + 'images/icons/error-svgrepo-com.svg" width="64" height="64" alt="Error" loading="lazy">' +
                        '<h3>Could not load products</h3>' +
                        '<p>Please refresh the page to try again.</p>' +
                        '</div>'
                    );
                }
            });
        }

        function showFeaturedEmptyState() {
            $('#featured-products-grid').html(
                '<div class="empty-state">' +
                '<img src="' + baseUrl + 'images/icons/product-catalog-svgrepo-com.svg" width="64" height="64" alt="No products">' +
                '<h3>No products yet</h3>' +
                '<p>Be the first to list a product on ConsuTrade!</p>' +
                '<a href="' + baseUrl + 'sell.php" class="view-all-btn" style="display: inline-block;">Start Selling</a>' +
                '</div>'
            );
        }

        $('#primary-btn').on('click', function() {
            var isLoggedIn = <?php echo json_encode($isLoggedIn); ?>;
            var hasSellerRole = <?php echo isset($currentUser) ? json_encode($currentUser->hasRole('seller')) : 'false'; ?>;

            if (isLoggedIn && hasSellerRole) {
                window.location.href = baseUrl + 'admin/seller-dashboard.php';
            } else if (isLoggedIn) {
                window.location.href = baseUrl + 'sell.php';
            } else {
                openModal($('#register-modal'));
                $('#seller').prop('checked', true);
                $('#register-modal .modal-header p').text('Create your account to start selling');
            }
        });

        $(function() {
            loadFeaturedProducts();
        });
    </script>

</body>

</html>