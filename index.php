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

$baseUrl = "/www/consutrade/";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ConsuTrade - Buy and Sell Across South Africa</title>
    <meta name="description" content="Buy and sell products from local South African traders. Secure payments with PayFast.">
    
    <!-- Preconnect for external domains -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="dns-prefetch" href="https://fonts.googleapis.com">
    
    <!-- Preload hero images for different device sizes (LCP optimization) -->
    <link rel="preload" as="image" href="images/hero-img.webp" type="image/webp" media="(min-width: 992px)" fetchpriority="high">
    <link rel="preload" as="image" href="images/hero-img-tablets.webp" type="image/webp" media="(min-width: 768px) and (max-width: 991px)" fetchpriority="high">
    <link rel="preload" as="image" href="images/hero-img-phones.webp" type="image/webp" media="(max-width: 767px)" fetchpriority="high">
    
    <!-- Preload critical CSS -->
    <link rel="preload" as="style" href="css/style.css">
    <link rel="preload" as="style" href="css/header.css">
    <link rel="preload" as="style" href="css/animations.css">
    
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/products.css">
    <link rel="stylesheet" href="css/login-signup.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/footer.css">
    
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    
    <?php if (!empty($registerErrors)): ?>
    <script>
    $(document).ready(function() {
        var modal = document.getElementById('register-modal');
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        <?php if (!empty($registerFormData['full_name'])): ?>
        var fullnameInput = document.getElementById('register-full-name');
        if (fullnameInput) fullnameInput.value = <?php echo json_encode($registerFormData['full_name']); ?>;
        <?php endif; ?>
        
        <?php if (!empty($registerFormData['email'])): ?>
        var emailInput = document.getElementById('register-email');
        if (emailInput) emailInput.value = <?php echo json_encode($registerFormData['email']); ?>;
        <?php endif; ?>
        
        <?php if (!empty($registerFormData['role']) && $registerFormData['role'] == 'seller'): ?>
        var sellRadio = document.getElementById('seller');
        if (sellRadio) sellRadio.checked = true;
        <?php endif; ?>
        
        <?php foreach ($registerErrors as $field => $message): ?>
        var errorEl = document.getElementById('<?php echo $field; ?>-error');
        if (errorEl) {
            errorEl.textContent = <?php echo json_encode($message); ?>;
            var inputGroup = errorEl.closest('.input-group');
            if (inputGroup) inputGroup.classList.add('error');
        }
        <?php endforeach; ?>
        
        var errorContainer = document.getElementById('register-error-container');
        if (errorContainer) {
            errorContainer.style.display = 'block';
        }
    });
    </script>
    <?php endif; ?>

    <?php if (!empty($loginErrors)): ?>
    <script>
    $(document).ready(function() {
        var modal = document.getElementById('login-modal');
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        var emailInput = document.getElementById('login-email');
        if (emailInput) {
            emailInput.value = <?php echo json_encode($loginEmail); ?>;
        }
        
        <?php foreach ($loginErrors as $field => $message): ?>
        var errorEl = document.createElement('small');
        errorEl.className = 'error-text';
        errorEl.style.color = '#f44336';
        errorEl.style.display = 'block';
        errorEl.style.marginTop = '6px';
        errorEl.textContent = <?php echo json_encode($message); ?>;
        
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

<?php include 'includes/header.php'; ?>

<main class="content">
    <?php if ($flash): ?>
    <div class="flash" style="background:#e6f4ea;border-left:4px solid #2e7d32;padding:12px 20px;margin:20px auto;max-width:1200px;font-size:14px;">
        <?php echo htmlspecialchars($flash); ?>
    </div>
    <?php endif; ?>
    
    <!-- Hero section with optimized image loading -->
    <section class="hero">
        <picture>
            <source srcset="images/hero-img.webp" type="image/webp" media="(min-width: 992px)">
            <source srcset="images/hero-img-tablets.webp" type="image/webp" media="(min-width: 768px)">
            <source srcset="images/hero-img-phones.webp" type="image/webp" media="(max-width: 767px)">
            <img src="images/hero-img.webp" alt="South African marketplace" class="hero-bg-image" fetchpriority="high" width="1920" height="500">
        </picture>
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
                    <a href="https://www.payfast.co.za" class="payfast-badge" target="_blank">
                        <img src="images/icons/Payfast logo.svg" alt="PayFast icon" width="60" height="20" loading="lazy">
                    </a>
                </p>
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
            <img src="images/icons/secure-card-svgrepo-com.svg" style="filter: brightness(0) invert(1);" width="48px" height="48px" alt="secure payments" class="icon" loading="lazy">
            <h2>Secure Payments</h2>
            <p>PayFast protected</p>
        </div>
        <div class="card">
            <img src="images/icons/verified-svgrepo-com.svg" style="filter: brightness(0) invert(1);" width="48px" height="48px" alt="verified" class="icon" loading="lazy">
            <h2>Verified Sellers</h2>
            <p>All sellers are checked</p>
        </div>
        <div class="card">
            <img src="images/icons/delivery-svgrepo-com.svg" style="filter: brightness(0) invert(1);" width="48px" height="48px" alt="delivery" class="icon" loading="lazy">
            <h2>Nationwide Delivery</h2>
            <p>We deliver across SA</p>
        </div>
    </section>
</main>

<script src="js/products.js"></script>
<script>
$(document).ready(function() {
    loadFeaturedProducts();

    function loadFeaturedProducts() {
        var $grid = $('#products-grid');
        if (!$grid.length) return;
        
        $grid.html('<div class="loading-spinner">Loading products...</div>');
        
        $.get(baseUrl + 'php/get-products.php?page=1&limit=4', function(data) {
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

    function displayFeaturedProducts(products) {
        var $grid = $('#products-grid');
        $grid.empty();
        
        $.each(products, function(i, product) {
            // Fix image path
            var imagePath = product.image;
            if (imagePath && !imagePath.startsWith('http') && !imagePath.startsWith('/')) {
                imagePath = baseUrl + imagePath;
            }
            
            // Determine verification badge
            var verifiedBadge = product.is_verified ? 
                '<div class="verified-badge-card"><img src="' + baseUrl + 'images/icons/verified-svgrepo-com.svg" width="14px" height="14px" alt="Verified"><span>Verified Seller</span></div>' : 
                '<div class="unverified-badge-card"><img src="' + baseUrl + 'images/icons/not-verified-svgrepo-com.svg" width="14px" height="14px" alt="Not Verified"><span>Unverified</span></div>';
            
            // Determine condition badge
            var conditionClass = '';
            var conditionText = product.condition || 'Good';
            if (conditionText === 'New') conditionClass = 'new';
            else if (conditionText === 'Like New') conditionClass = 'like-new';
            else if (conditionText === 'Good') conditionClass = 'good';
            else if (conditionText === 'Fair') conditionClass = 'fair';
            
            // Use eager loading for first 4 images, lazy for others
            var loadingAttr = (i < 4) ? 'eager' : 'lazy';
            
            var $card = $('<div>').addClass('prod-card').css('cursor', 'pointer');
            $card.on('click', function() {
                window.location.href = baseUrl + 'product-details.php?id=' + product.id;
            });
            
            $card.html(`
                <div class="img-container">
                    <img src="${imagePath}" alt="${escapeHtml(product.name)}" 
                         width="280" height="280"
                         loading="${loadingAttr}"
                         onerror="this.src='${baseUrl}images/default-product.png'">
                    <div class="condition-badge ${conditionClass}">${conditionText}</div>
                </div>
                <div class="prod-info-container">
                    <h3 class="prod-name">${escapeHtml(product.name)}</h3>
                    <p class="prod-price">R ${parseFloat(product.price).toFixed(2)}</p>
                    <div class="seller-info">
                        <div class="seller-avatar">
                            <img src="${baseUrl}images/icons/profile-svgrepo-com.svg" alt="Seller" width="32" height="32" loading="lazy">
                        </div>
                        <div class="seller-details">
                            <p class="seller-name">${escapeHtml(product.seller_name)}</p>
                            <p class="location">
                                <img src="${baseUrl}images/icons/pin-location-svgrepo-com.svg" width="10px" height="10px" alt="location" loading="lazy">
                                ${escapeHtml(product.location)}
                            </p>
                        </div>
                        ${verifiedBadge}
                    </div>
                    <button class="add-to-cart-btn" onclick="event.stopPropagation(); addToCart(${product.id}, '${escapeHtml(product.name).replace(/'/g, "\\'")}', ${product.price})">
                        <img src="${baseUrl}images/icons/shopping-cart-01-svgrepo-com.svg" alt="Cart" width="16" height="16" loading="lazy">
                        Add to Cart
                    </button>
                    <div class="payment-badge">
                        <span>Secure payment via</span>
                        <img src="${baseUrl}images/icons/Payfast logo.svg" alt="PayFast" width="40" height="16" loading="lazy">
                    </div>
                </div>
            `);
            $grid.append($card);
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        return $('<div>').text(text).html();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>