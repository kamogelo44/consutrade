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
    <link rel="stylesheet" href="css/products.css">
    <link rel="stylesheet" href="css/login-signup.css">
    <link rel="stylesheet" href="css/header.css">
    
    <?php if (!empty($registerErrors)): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var modal = document.getElementById('register-modal');
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
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
        
        <?php foreach ($registerErrors as $field => $message): ?>
        var errorEl = document.getElementById('<?php echo $field; ?>-error');
        if (errorEl) {
            errorEl.textContent = '<?php echo addslashes($message); ?>';
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
    document.addEventListener('DOMContentLoaded', function() {
        var modal = document.getElementById('login-modal');
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        var emailInput = document.getElementById('login-email');
        if (emailInput) {
            emailInput.value = '<?php echo addslashes($loginEmail); ?>';
        }
        
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
    <?php include 'header.php'; ?>

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
                    <button class="browse-btn" id="secondary-btn" onclick="window.location.href='product-listings.php'">Shop Products</button>
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
            <div class="featured-header">
                <h1 class="section-heading">Recently Listed</h1>
                <a href="product-listings.php" class="view-all-link">View All Products →</a>
            </div>
            <div class="prod-grid" id="products-grid">
                <div class="loading-spinner" style="text-align: center; grid-column: 1/-1; padding: 40px;">
                    Loading products...
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
    <?php include 'footer.php'; ?>

    <script src="js/main.js"></script>
    <script>
    // Pass user data to JavaScript
    var currentUserId = <?php echo $_SESSION['user_id'] ?? 0; ?>;
    var currentUserRole = '<?php echo $_SESSION['role'] ?? ""; ?>';
    </script>

    <script src="js/products.js"></script>
    <script>
    // Load featured products when page loads
    document.addEventListener('DOMContentLoaded', function() {
        loadFeaturedProducts();
    });

    function loadFeaturedProducts() {
        var grid = document.getElementById('products-grid');
        if (!grid) return;
        
        grid.innerHTML = '<div class="loading-spinner">Loading products...</div>';
        
        fetch('/www/consutrade/php/get-products.php?page=1&limit=4')
            .then(function(response) { 
                if (!response.ok) {
                    throw new Error('HTTP error ' + response.status);
                }
                return response.json(); 
            })
            .then(function(data) {
                if (data.success && data.products && data.products.length > 0) {
                    // Show only first 4 products for homepage
                    var featuredProducts = data.products.slice(0, 4);
                    displayFeaturedProducts(featuredProducts);
                } else {
                    grid.innerHTML = '<p class="no-products">No products available yet.</p>';
                }
            })
            .catch(function(error) {
                console.log('Error:', error);
                grid.innerHTML = '<p class="error">Error loading products. Please refresh the page.</p>';
            });
    }

    function displayFeaturedProducts(products) {
        var grid = document.getElementById('products-grid');
        if (!grid) return;
        
        grid.innerHTML = '';
        
        for (var i = 0; i < products.length; i++) {
            var product = products[i];
            
            // Fix image path
            var imagePath = product.image;
            if (imagePath && !imagePath.startsWith('http') && !imagePath.startsWith('/')) {
                imagePath = '/www/consutrade/' + imagePath;
            }
            
            // Determine verification badge
            var verifiedBadge = product.is_verified ? 
                '<div class="verified-badge-card"><img src="/www/consutrade/images/icons/verified-svgrepo-com.svg" width="14px" height="14px" alt="Verified"><span>Verified Seller</span></div>' : 
                '<div class="unverified-badge-card"><img src="/www/consutrade/images/icons/not-verified-svgrepo-com.svg" width="14px" height="14px" alt="Not Verified"><span>Unverified</span></div>';
            
            // Determine condition badge
            var conditionClass = '';
            var conditionText = product.condition || 'Good';
            if (conditionText === 'New') conditionClass = 'new';
            else if (conditionText === 'Like New') conditionClass = 'like-new';
            else if (conditionText === 'Good') conditionClass = 'good';
            else if (conditionText === 'Fair') conditionClass = 'fair';
            
            var card = document.createElement('div');
            card.className = 'prod-card';
            card.style.cursor = 'pointer';
            card.addEventListener('click', (function(id) {
                return function() {
                    window.location.href = '/www/consutrade/product-details.php?id=' + id;
                };
            })(product.id));
            
            card.innerHTML = `
                <div class="img-container">
                    <img src="${imagePath}" alt="${escapeHtml(product.name)}" onerror="this.src='/www/consutrade/images/default-product.png'">
                    <div class="condition-badge ${conditionClass}">${conditionText}</div>
                </div>
                <div class="prod-info-container">
                    <h3 class="prod-name">${escapeHtml(product.name)}</h3>
                    <p class="prod-price">R ${parseFloat(product.price).toFixed(2)}</p>
                    <div class="seller-info">
                        <div class="seller-avatar">
                            <img src="/www/consutrade/images/icons/profile-svgrepo-com.svg" alt="Seller">
                        </div>
                        <div class="seller-details">
                            <p class="seller-name">${escapeHtml(product.seller_name)}</p>
                            <p class="location">
                                <img src="/www/consutrade/images/icons/pin-location-svgrepo-com.svg" width="10px" height="10px" alt="location">
                                ${escapeHtml(product.location)}
                            </p>
                        </div>
                        ${verifiedBadge}
                    </div>
                    <button class="add-to-cart-btn" onclick="event.stopPropagation(); addToCart(${product.id}, '${escapeHtml(product.name).replace(/'/g, "\\'")}', ${product.price})">
                        <img src="/www/consutrade/images/icons/shopping-cart-01-svgrepo-com.svg" alt="Cart">
                        Add to Cart
                    </button>
                    <div class="payment-badge">
                        <span>Secure payment via</span>
                        <img src="/www/consutrade/images/icons/Payfast logo.svg" alt="PayFast">
                    </div>
                </div>
            `;
            grid.appendChild(card);
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    </script>
</body>
</html>