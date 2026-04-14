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
    <link rel="stylesheet" href="css/header.css">
    
    <?php if (!empty($registerErrors)): ?>
    <script>
    // Open the modal if there are errors
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
            <h1 class="section-heading">Recently Listed</h1>
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
        // Load featured products when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadFeaturedProducts();
        });

        function loadFeaturedProducts() {
            fetch('php/get-products.php')
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    var grid = document.getElementById('products-grid');
                    
                    if (data.success && data.products.length > 0) {
                        // Show only first 4 products for homepage
                        var featuredProducts = data.products.slice(0, 4);
                        displayFeaturedProducts(featuredProducts);
                    } else {
                        grid.innerHTML = '<p style="text-align: center; grid-column: 1/-1;">No products available yet.</p>';
                    }
                })
                .catch(function(error) {
                    console.log('Error:', error);
                    document.getElementById('products-grid').innerHTML = '<p style="text-align: center; grid-column: 1/-1; color: #f44336;">Error loading products. Please refresh the page.</p>';
                });
        }

        function displayFeaturedProducts(products) {
            var grid = document.getElementById('products-grid');
            grid.innerHTML = '';
            
            for (var i = 0; i < products.length; i++) {
                var product = products[i];
                
                // Determine which verification icon to show
                var verifiedIcon = product.is_verified ? 
                    '<img src="images/icons/verified-svgrepo-com.svg" width="24px" height="24px" alt="Verified">' : 
                    '<img src="images/icons/not-verified-svgrepo-com.svg" class="not-verified-icon" width="24px" height="24px" alt="Not Verified">';
                
                // Create the product card
                var card = document.createElement('div');
                card.className = 'prod-card';
                card.innerHTML = `
                    <a href="product-details.php?id=${product.id}" style="text-decoration: none; color: inherit;">
                        <div class="img-container">
                            <img src="${product.image}" alt="${product.name}">
                        </div>
                        <p class="prod-name">${escapeHtml(product.name)}</p>
                        <p class="prod-price">R ${parseFloat(product.price).toFixed(2)}</p>
                        <div class="seller-info">
                            <img src="images/icons/profile-svgrepo-com.svg" alt="Seller Profile Picture">
                            <p class="seller-name">Seller: ${escapeHtml(product.seller_name)}</p>
                            <p class="location">${escapeHtml(product.location)}</p>
                            ${verifiedIcon}
                        </div>
                    </a>
                    <button class="add-to-cart-btn" onclick="addToCart(${product.id}, '${escapeHtml(product.name).replace(/'/g, "\\'")}', ${product.price})">
                        Add to Cart
                    </button>
                `;
                grid.appendChild(card);
            }
        }

        // Helper function to escape HTML and prevent XSS attacks
        function escapeHtml(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>