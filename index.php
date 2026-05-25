<?php
/*
 * ConsuTrade - Homepage
 * Author: Kamogelo Phale
 * 
 * Main landing page displaying featured products and site information
 */

require_once __DIR__ . '/init.php';

$baseUrl = getBaseUrl();

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
    <meta name="description" content="Buy and sell products from local South African traders. Secure payments with PayFast.">
    <meta name="author" content="Kamogelo Phale">
    
    <!-- Master Stylesheet (includes all CSS) -->
    <link rel="stylesheet" href="css/main.css">
    
    <style>
        /* ========== PAGE SPECIFIC STYLES ========== */
        
        /* Trust Section Cards - Override default card styles */
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
        
        /* Loading Spinner for Featured Products */
        .featured .loading-spinner {
            text-align: center;
            padding: 60px;
            color: var(--gray-medium);
            grid-column: 1 / -1;
        }
        
        /* Responsive */
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
    <!-- Flash Message -->
    <?php if ($flash): ?>
        <div class="flash-message">
            <?php echo htmlspecialchars($flash); ?>
        </div>
    <?php endif; ?>
    
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
                <button class="browse-btn" id="secondary-btn" onclick="window.location.href='product-listings.php'">Shop Products</button>
            </div>
        </div>
    </section>

    <!-- How it works section -->
    <section class="how">
        <h1 class="section-heading">How it works</h1>
        <div class="how-container">
            <div class="card">
                <img src="images/icons/register-svgrepo-com.svg" width="48" height="48" alt="" class="icon" loading="lazy">
                <h2>Register</h2>
                <p>Create your free seller account</p>
            </div>
            <img src="images/icons/right-arrow-1-svgrepo-com.svg" class="arrow" width="48" height="48" alt="arrow" loading="lazy">
            <div class="card">
                <img src="images/icons/product-catalog-svgrepo-com.svg" width="48" height="48" alt="" class="icon" loading="lazy">
                <h2>List</h2>
                <p>Upload your products in minutes</p>
            </div>
            <img src="images/icons/right-arrow-1-svgrepo-com.svg" class="arrow" width="48" height="48" alt="arrow" loading="lazy">
            <div class="card">
                <img src="images/icons/cash-atm-svgrepo-com.svg" width="48" height="48" alt="" class="icon" loading="lazy">
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
        <div class="prod-grid" id="products-grid">
            <div class="loading-spinner">Loading products...</div>
        </div>
    </section>

    <!-- Trust banner -->
    <section class="trust">
        <div class="card">
            <img src="images/icons/secure-card-svgrepo-com.svg" style="filter: brightness(0) invert(1);" width="48" height="48" alt="secure payments" class="icon" loading="lazy">
            <h2>Secure Payments</h2>
            <p>PayFast protected</p>
        </div>
        <div class="card">
            <img src="images/icons/verified-svgrepo-com.svg" style="filter: brightness(0) invert(1);" width="48" height="48" alt="verified" class="icon" loading="lazy">
            <h2>Verified Sellers</h2>
            <p>All sellers are checked</p>
        </div>
        <div class="card">
            <img src="images/icons/delivery-svgrepo-com.svg" style="filter: brightness(0) invert(1);" width="48" height="48" alt="delivery" class="icon" loading="lazy">
            <h2>Nationwide Delivery</h2>
            <p>We deliver across SA</p>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>

<?php if (!empty($registerErrors)): ?>
<script>
$(function() {
    openModal($('#register-modal'));
    
    <?php if (!empty($registerFormData['full_name'])): ?>
    $('#register-full-name').val(<?php echo json_encode($registerFormData['full_name']); ?>);
    <?php endif; ?>
    <?php if (!empty($registerFormData['email'])): ?>
    $('#register-email').val(<?php echo json_encode($registerFormData['email']); ?>);
    <?php endif; ?>
    <?php if (!empty($registerFormData['phone'])): ?>
    $('#register-phone').val(<?php echo json_encode($registerFormData['phone']); ?>);
    <?php endif; ?>
    
    <?php if (isset($registerErrors['general']) && !empty(trim($registerErrors['general']))): ?>
    $('#register-error-container').show().text(<?php echo json_encode(trim($registerErrors['general'])); ?>);
    <?php endif; ?>
    
    <?php foreach ($registerErrors as $field => $message): ?>
        <?php if ($field !== 'general' && !empty($message)): ?>
        var $input = $('#register-<?php echo $field; ?>');
        if ($input.length) {
            $input.closest('.input-group').addClass('error');
            $input.closest('.input-group').append('<small class="error-text"><?php echo addslashes($message); ?></small>');
        }
        <?php endif; ?>
    <?php endforeach; ?>
});
</script>
<?php endif; ?>

<?php if (!empty($loginErrors)): ?>
<script>
$(function() {
    openModal($('#login-modal'));
    
    <?php if (!empty($loginEmail)): ?>
    $('#login-email').val(<?php echo json_encode($loginEmail); ?>);
    <?php endif; ?>
    
    <?php if (isset($loginErrors['general']) && !empty(trim($loginErrors['general']))): ?>
    $('#login-error-container').show().text(<?php echo json_encode(trim($loginErrors['general'])); ?>);
    <?php endif; ?>
    
    <?php foreach ($loginErrors as $field => $message): ?>
        <?php if ($field !== 'general' && !empty($message)): ?>
        var $input = $('#login-<?php echo $field; ?>');
        if ($input.length) {
            $input.closest('.input-group').addClass('error');
            $input.closest('.input-group').append('<small class="error-text"><?php echo addslashes($message); ?></small>');
        }
        <?php endif; ?>
    <?php endforeach; ?>
});
</script>
<?php endif; ?>

<script>
$(function() {
    loadFeaturedProducts();

    function loadFeaturedProducts() {
        var $grid = $('#products-grid');
        if (!$grid.length) return;
        
        $grid.html('<div class="loading-spinner">Loading products...</div>');
        
        $.get(baseUrl + 'php/endpoints/get-products.php?page=1&limit=4', function(data) {
            if (data.success && data.products && data.products.length > 0) {
                var featuredProducts = data.products.slice(0, 4);
                displayFeaturedProducts(featuredProducts);
            } else {
                $grid.html('<p class="no-products">No products available yet.</p>');
            }
        }).fail(function() {
            $grid.html('<p class="error">Error loading products. Please refresh the page.</p>');
        });
    }

    function getSellerAvatar(profileImage) {
        if (profileImage && profileImage !== '') {
            if (profileImage.startsWith('http')) return profileImage;
            return baseUrl + profileImage;
        }
        return baseUrl + 'images/icons/profile-svgrepo-com.svg';
    }

    function displayFeaturedProducts(products) {
        var $grid = $('#products-grid');
        $grid.empty();
        
        $.each(products, function(i, product) {
            var imagePath = product.display_image || product.image;
            if (imagePath && !imagePath.startsWith('http') && !imagePath.startsWith('/')) {
                imagePath = baseUrl + imagePath;
            }
            
            var verifiedBadge = product.is_verified ? 
                '<div class="verified-badge-card"><img src="' + baseUrl + 'images/icons/verified-svgrepo-com.svg" width="14" height="14"><span>Verified Seller</span></div>' : 
                '<div class="unverified-badge-card"><img src="' + baseUrl + 'images/icons/not-verified-svgrepo-com.svg" width="14" height="14"><span>Unverified</span></div>';
            
            var conditionClass = '';
            var conditionText = product.condition || 'Good';
            if (conditionText === 'New') conditionClass = 'new';
            else if (conditionText === 'Like New') conditionClass = 'like-new';
            else if (conditionText === 'Good') conditionClass = 'good';
            else if (conditionText === 'Fair') conditionClass = 'fair';
            
            var $card = $('<div>').addClass('prod-card').css('cursor', 'pointer');
            $card.on('click', function() {
                window.location.href = baseUrl + 'product-details.php?id=' + product.id;
            });
            
            $card.html(`
                <div class="img-container">
                    <img src="${imagePath || baseUrl + 'images/default-product.png'}" alt="${escapeHtml(product.name)}" onerror="this.src='${baseUrl}images/default-product.png'">
                    <div class="condition-badge ${conditionClass}">${conditionText}</div>
                </div>
                <div class="prod-info-container">
                    <h3 class="prod-name">${escapeHtml(product.name)}</h3>
                    <p class="prod-price">R ${parseFloat(product.price).toFixed(2)}</p>
                    <div class="seller-info">
                        <div class="seller-avatar">
                            <img src="${getSellerAvatar(product.profile_image)}" alt="${escapeHtml(product.seller_name)}" onerror="this.src='${baseUrl}images/icons/profile-svgrepo-com.svg'">
                        </div>
                        <div class="seller-details">
                            <p class="seller-name">${escapeHtml(product.seller_name)}</p>
                            <p class="location">
                                <img src="${baseUrl}images/icons/pin-location-svgrepo-com.svg" width="10" height="10" alt="location">
                                ${escapeHtml(product.location || 'South Africa')}
                            </p>
                        </div>
                        ${verifiedBadge}
                    </div>
                    <button class="add-to-cart-btn" onclick="event.stopPropagation(); addToCart(${product.id}, '${escapeHtml(product.name).replace(/'/g, "\\'")}', ${product.price})">
                        <img src="${baseUrl}images/icons/shopping-cart-01-svgrepo-com.svg" width="16" height="16" alt="Cart">
                        Add to Cart
                    </button>
                    <div class="payment-badge">
                        <span>Secure payment via</span>
                        <img src="${baseUrl}images/icons/Payfast logo.svg" alt="PayFast" width="40" height="16">
                    </div>
                </div>
            `);
            $grid.append($card);
        });
    }
});
</script>

</body>
</html>