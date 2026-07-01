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

    <style>
        .featured .prod-grid .empty-state {
            grid-column: 1 / -1;
            width: 100%;
        }

        .trust .card {
            background-color: var(--primary-color);
            border: none;
            box-shadow: none;
        }

        .trust .card h2 {
            color: var(--white);
        }

        .trust .card p {
            color: rgba(255, 255, 255, 0.9);
        }

        .hero .search-container {
            display: none;
        }

        @media (max-width: 768px) {
            .trust {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <main class="content">
        <?php include 'includes/flash-message.php'; ?>

        <!-- Hero Section -->
        <section class="hero">
            <img src="images/hero-img.webp" alt="South African marketplace" class="hero-bg-image" width="1920" height="500">
            <div class="hero-overlay"></div>
            <div class="hero-container">
                <div class="txt-container">
                    <h1>Buy and Sell at the comfort of your home in South Africa.</h1>
                    <p>ConsuTrade connects informal traders with buyers across the country.</p>
                </div>
                <div class="btn-container">
                    <button class="sell-btn" id="primary-btn">Start Selling</button>
                    <button class="browse-btn" onclick="window.location.href='product-listings.php'">Shop Products</button>
                </div>
            </div>
        </section>

        <!-- How it works section -->
        <section class="how">
            <h1 class="section-heading">How it works</h1>
            <div class="how-container">
                <div class="card">
                    <img src="images/icons/register-svgrepo-com.svg" width="48" height="48" class="icon" loading="lazy">
                    <h2>Register</h2>
                    <p>Create your free account</p>
                </div>
                <img src="images/icons/right-arrow-1-svgrepo-com.svg" class="arrow" width="48" height="48" loading="lazy">
                <div class="card">
                    <img src="images/icons/product-catalog-svgrepo-com.svg" width="48" height="48" class="icon" loading="lazy">
                    <h2>List</h2>
                    <p>Upload your products in minutes</p>
                </div>
                <img src="images/icons/right-arrow-1-svgrepo-com.svg" class="arrow" width="48" height="48" loading="lazy">
                <div class="card">
                    <img src="images/icons/cash-atm-svgrepo-com.svg" width="48" height="48" class="icon" loading="lazy">
                    <h2>Get Paid</h2>
                    <p>Receive payments securely with PayFast</p>
                </div>
            </div>
        </section>

        <!-- Featured products section -->
        <section class="featured">
            <div class="featured-header">
                <h1 class="section-heading">Recently Listed</h1>
                <a href="product-listings.php" class="view-all-link">View All Products →</a>
            </div>
            <div class="prod-grid" id="featured-products-grid">
                <div class="loading-spinner">Loading products...</div>
            </div>
        </section>

        <!-- Trust banner -->
        <section class="trust">
            <div class="card">
                <img src="images/icons/secure-card-svgrepo-com.svg" style="filter: brightness(0) invert(1);" width="48" height="48" class="icon" loading="lazy">
                <h2>Secure Payments</h2>
                <p>PayFast protected</p>
            </div>
            <div class="card">
                <img src="images/icons/verified-svgrepo-com.svg" style="filter: brightness(0) invert(1);" width="48" height="48" class="icon" loading="lazy">
                <h2>Verified Sellers</h2>
                <p>All sellers are checked</p>
            </div>
            <div class="card">
                <img src="images/icons/delivery-svgrepo-com.svg" style="filter: brightness(0) invert(1);" width="48" height="48" class="icon" loading="lazy">
                <h2>Nationwide Delivery</h2>
                <p>We deliver across SA</p>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/modal-errors.php'; ?>

    <!-- Page specific Javascript-->
    <script>
        // Load featured products via AJAX using existing functions
        function loadFeaturedProducts() {
            var $grid = $('#featured-products-grid');
            $grid.html('<div class="loading-spinner">Loading products...</div>');

            $.ajax({
                url: baseUrl + 'php/endpoints/products/get-products.php?limit=4&page=1',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    if (data.success && data.products && data.products.length > 0) {
                        // Reuse displayProducts with container parameter
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

        // Handle Start Selling button
        $('#primary-btn').on('click', function() {
            var isLoggedIn = <?php echo json_encode($isLoggedIn); ?>;
            var hasSellerRole = <?php echo isset($currentUser) ? json_encode($currentUser->hasRole('seller')) : 'false'; ?>;

            if (isLoggedIn && hasSellerRole) {
                // User has seller role - go to dashboard
                window.location.href = baseUrl + 'admin/seller-dashboard.php';
            } else if (isLoggedIn) {
                // Logged in but no seller role (pure buyer) - go to sell.php info page
                window.location.href = baseUrl + 'sell.php';
            } else {
                // Not logged in - open registration modal with seller selected
                openModal($('#register-modal'));
                $('#seller').prop('checked', true);
                $('#register-modal .modal-header p').text('Create your account to start selling');
            }
        });

        // Load featured products when page is ready
        $(function() {
            loadFeaturedProducts();
        });
    </script>

</body>

</html>