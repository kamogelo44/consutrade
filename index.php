<?php
/*
 * ConsuTrade - Homepage
 * Author: Kamogelo Phale
 */

require_once __DIR__ . '/init.php';
include __DIR__ . '/includes/session-vars.php';
include __DIR__ . '/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ConsuTrade - Buy and Sell Across South Africa</title>
    <meta name="description" content="Buy and sell products from local South African traders. Secure payments with PayFast.">
    <link rel="stylesheet" href="css/main.css">
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

        <section class="how">
            <h1 class="section-heading">How it works</h1>
            <div class="how-container">
                <div class="card"><img src="images/icons/register-svgrepo-com.svg" width="48" height="48" class="icon" loading="lazy">
                    <h2>Register</h2>
                    <p>Create your free account</p>
                </div>
                <img src="images/icons/right-arrow-1-svgrepo-com.svg" class="arrow" width="48" height="48" loading="lazy">
                <div class="card"><img src="images/icons/product-catalog-svgrepo-com.svg" width="48" height="48" class="icon" loading="lazy">
                    <h2>List</h2>
                    <p>Upload your products in minutes</p>
                </div>
                <img src="images/icons/right-arrow-1-svgrepo-com.svg" class="arrow" width="48" height="48" loading="lazy">
                <div class="card"><img src="images/icons/cash-atm-svgrepo-com.svg" width="48" height="48" class="icon" loading="lazy">
                    <h2>Get Paid</h2>
                    <p>Receive payments securely with PayFast</p>
                </div>
            </div>
        </section>

        <section class="featured">
            <div class="featured-header">
                <h1 class="section-heading">Recently Listed</h1>
                <a href="product-listings.php" class="view-all-link">View All Products →</a>
            </div>
            <div class="prod-grid" id="products-grid">
                <?php
                // Get featured products as Product objects
                $featuredProducts = $productRepo->getPublicProductObjects(['limit' => 4]);

                if (!empty($featuredProducts)):
                    foreach ($featuredProducts as $product):
                        // Get seller for this product
                        $seller = $userRepo->findById($product->getSellerId());
                        // Render the product card using the reusable function
                        echo renderProductCard($product, $seller);
                    endforeach;
                else:
                ?>
                    <div class="empty-state">
                        <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" width="64" height="64" alt="No products">
                        <h3>No products yet</h3>
                        <p>Be the first to list a product on ConsuTrade!</p>
                        <a href="sell.php" class="view-all-btn" style="display: inline-block;">Start Selling</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="trust">
            <div class="card"><img src="images/icons/secure-card-svgrepo-com.svg" style="filter: brightness(0) invert(1);" width="48" height="48" class="icon" loading="lazy">
                <h2>Secure Payments</h2>
                <p>PayFast protected</p>
            </div>
            <div class="card"><img src="images/icons/verified-svgrepo-com.svg" style="filter: brightness(0) invert(1);" width="48" height="48" class="icon" loading="lazy">
                <h2>Verified Sellers</h2>
                <p>All sellers are checked</p>
            </div>
            <div class="card"><img src="images/icons/delivery-svgrepo-com.svg" style="filter: brightness(0) invert(1);" width="48" height="48" class="icon" loading="lazy">
                <h2>Nationwide Delivery</h2>
                <p>We deliver across SA</p>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/modal-errors.php'; ?>

    <script>
        $('#primary-btn').on('click', function() {
            var isLoggedIn = <?php echo json_encode($isLoggedIn); ?>;
            var currentUserRole = <?php echo isset($currentUser) ? json_encode($currentUser->getRole()) : 'null'; ?>;

            if (isLoggedIn && currentUserRole === 'seller') {
                window.location.href = baseUrl + 'admin/seller-dashboard.php';
            } else if (isLoggedIn && currentUserRole === 'buyer') {
                window.location.href = baseUrl + 'sell.php';
            } else {
                openModal($('#register-modal'));
                $('#seller').prop('checked', true);
            }
        });
    </script>

</body>

</html>