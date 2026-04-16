<?php 
session_start(); 

$baseUrl = "/www/consutrade/";

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header('Location: ' . $baseUrl . 'product-listings.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/products.css">
    <link rel="stylesheet" href="css/login-signup.css">
    <link rel="stylesheet" href="css/header.css">
    <title>Product Details - ConsuTrade</title>
</head>
<body>
    <!--Header-->
    <?php include 'header.php'; ?>
    
    <main>
        <section class="products-details" id="product-details-container">
            <div class="loading-spinner" style="text-align: center; padding: 60px;">Loading product details...</div>
        </section>
    </main>

    <!--Footer-->
    <?php include 'footer.php'; ?>
    
    <script>
    // Pass PHP session data to JavaScript
    var isLoggedIn = <?php echo isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true ? 'true' : 'false'; ?>;
    var currentUserId = <?php echo $_SESSION['user_id'] ?? 0; ?>;
    var currentUserRole = '<?php echo $_SESSION['role'] ?? ""; ?>';
    </script>
    <script src="js/main.js"></script>
    <script>
        // Get product ID from URL
        var productId = <?php echo $product_id; ?>;
        
        // Load product details when page loads
        document.addEventListener('DOMContentLoaded', function() {
            if (productId > 0) {
                loadProductDetails(productId);
            } else {
                showError('Product not found.');
            }
        });

        function loadProductDetails(id) {
            var container = document.getElementById('product-details-container');
            container.innerHTML = '<div class="loading-spinner" style="text-align: center; padding: 60px;">Loading product details...</div>';
            
            fetch('/www/consutrade/php/get-product.php?id=' + id)
                .then(function(response) { 
                    if (!response.ok) {
                        throw new Error('Server error: ' + response.status);
                    }
                    return response.json(); 
                })
                .then(function(data) {
                    if (data.success && data.product) {
                        displayProductDetails(data.product);
                    } else {
                        showError(data.error || 'Product not found.');
                    }
                })
                .catch(function(error) {
                    console.log('Error:', error);
                    showError('Unable to load product. Please try again later.');
                });
        }
        
        function showError(message) {
            var container = document.getElementById('product-details-container');
            container.innerHTML = `
                <div class="error-container" style="text-align: center; padding: 80px 20px; max-width: 500px; margin: 0 auto;">
                    <img src="/www/consutrade/images/icons/shopping-cart-01-svgrepo-com.svg" width="64px" height="64px" alt="Error" style="opacity: 0.5; margin-bottom: 20px;">
                    <h2 style="color: #f44336; margin-bottom: 10px; font-size: 24px;">Oops!</h2>
                    <p style="color: #666;">${escapeHtml(message)}</p>
                    <button onclick="window.location.href='/www/consutrade/product-listings.php'" style="margin-top: 20px; padding: 10px 24px; background-color: #FF6B00; color: white; border: none; border-radius: 8px; cursor: pointer;">Browse Products</button>
                </div>
            `;
        }

        function displayProductDetails(product) {
            var container = document.getElementById('product-details-container');
            
            // Fix image paths
            var mainImage = product.image;
            if (mainImage && !mainImage.startsWith('http') && !mainImage.startsWith('/')) {
                mainImage = '/www/consutrade/' + mainImage;
            }
            
            // Parse gallery images from JSON
            var galleryImages = [];
            if (product.gallery_images) {
                try {
                    galleryImages = JSON.parse(product.gallery_images);
                } catch(e) {
                    galleryImages = [];
                }
            }
            
            // Build all images array (main image + gallery images)
            var allImages = [mainImage];
            for (var i = 0; i < galleryImages.length; i++) {
                var thumbPath = galleryImages[i];
                if (thumbPath && !thumbPath.startsWith('http') && !thumbPath.startsWith('/')) {
                    thumbPath = '/www/consutrade/' + thumbPath;
                }
                allImages.push(thumbPath);
            }
            
            // Build gallery thumbnails HTML with active class
            var galleryHtml = '';
            for (var i = 0; i < 4; i++) {
                var thumbPath = allImages[i] || '/www/consutrade/images/default-product.png';
                var activeClass = (i === 0) ? 'active' : '';
                galleryHtml += `
                    <div class="small-img ${activeClass}" data-image-index="${i}" onclick="changeMainImage('${thumbPath}', ${i})">
                        <img src="${thumbPath}" alt="Product thumbnail" onerror="this.src='/www/consutrade/images/default-product.png'">
                    </div>
                `;
            }
            
            // Determine verification badge
            var verificationBadge = product.is_verified ? 
                '<div class="verified-badge"><img src="/www/consutrade/images/icons/verified-svgrepo-com.svg" width="20px" height="20px" alt="verification"><p>Verified Seller</p></div>' : 
                '<div class="not-verified-badge"><img src="/www/consutrade/images/icons/not-verified-svgrepo-com.svg" width="20px" height="20px" alt="not-verified"><p>Not Verified Seller</p></div>';
            
            // Build stars
            var rating = product.avg_rating || 0;
            var starsHtml = '';
            for (var i = 1; i <= 5; i++) {
                if (i <= rating) {
                    starsHtml += '<span class="star">★</span>';
                } else {
                    starsHtml += '<span class="star empty">★</span>';
                }
            }
            
            // Build condition HTML
            var conditionHtml = '';
            if (product.condition && product.condition !== '') {
                conditionHtml = `<p class="sub-head">Condition: <span class="condition">${escapeHtml(product.condition)}</span></p>`;
            }
            
            // Build location HTML
            var locationHtml = '';
            if (product.location) {
                locationHtml = `<p class="sub-head">Location: <span class="city">${escapeHtml(product.location)}</span></p>`;
            }
            
            // Check if this is the seller's own product
            var isOwnProduct = (currentUserRole === 'seller' && product.seller_id == currentUserId);
            var actionButtonsHtml = '';
            
            if (!isOwnProduct) {
                actionButtonsHtml = `
                    <button class="cart-btn" onclick="addToCart(${product.id}, '${escapeHtml(product.name).replace(/'/g, "\\'")}', ${product.price})">
                        <img src="/www/consutrade/images/icons/shopping-cart-01-svgrepo-com.svg" width="24px" height="24px" alt="Cart">
                        Add to Cart
                    </button>
                    <button class="buy-btn" onclick="buyNow(${product.id})">Buy Now</button>
                `;
            } else {
                actionButtonsHtml = `
                    <div class="own-product-message">
                        <p>You cannot purchase your own product.</p>
                    </div>
                `;
            }
            
            container.innerHTML = `
                <div class="breadcrumb">
                    <a href="/www/consutrade/index.php">Home</a>
                    <span> > </span>
                    <a href="/www/consutrade/product-listings.php">Product Listings</a>
                    <span> > </span>
                    <span>${escapeHtml(product.name)}</span>
                </div>
                
                <div class="top-items">
                    <div class="product-imgs">
                        <div class="main-img" id="main-image-container">
                            <img src="${mainImage}" alt="${escapeHtml(product.name)}" onerror="this.src='/www/consutrade/images/default-product.png'" id="main-product-image">
                        </div>
                        <div class="smaller-imgs" id="gallery-container">
                            ${galleryHtml}
                        </div>
                    </div>
                    
                    <div class="product-info">
                        <div class="price-desc">
                            <h1 class="details-prod-name">${escapeHtml(product.name)}</h1>
                            <p class="details-price">R ${parseFloat(product.price).toFixed(2)}</p>
                            <div class="cat-badge">
                                <p class="cat-name">${escapeHtml(product.category_name || 'General')}</p>
                            </div>
                        </div>
                        
                        <div class="description">
                            <p class="sub-head">Description</p>
                            <p class="des">${escapeHtml(product.description || 'No description available.')}</p>
                        </div>
                        
                        <div class="con-loc">
                            ${conditionHtml}
                            ${locationHtml}
                        </div>
                    </div>
                </div>
                
                <section class="review">
                    <div class="rev-container">
                        <div class="seller-profile">
                            <div class="profile-pic">
                                <img src="/www/consutrade/images/icons/profile-svgrepo-com.svg" width="24px" height="24px" alt="Seller Profile Picture">
                            </div>
                            <p class="seller-name">${escapeHtml(product.seller_name)}</p>
                        </div>
                        <div class="verification">
                            ${verificationBadge}
                        </div>
                        <div class="star-reviews">
                            <h1>Seller Reviews</h1>
                            ${starsHtml}
                            <p id="output">Rating: ${rating}/5 (${product.review_count || 0} reviews)</p>
                        </div>
                        <button class="view-profile" onclick="window.location.href='/www/consutrade/profile.php?seller_id=${product.seller_id}'">
                            View Seller Profile
                        </button>
                    </div>
                </section>

                <div class="actions">
                    <div class="actions-card">
                        <div class="avail">
                            <p><span class="num-avail">In Stock</span></p>
                        </div>
                        <div class="action-btns">
                            ${actionButtonsHtml}
                        </div>
                    </div>
                </div>
            `;
        }
        
        function changeMainImage(imagePath, selectedIndex) {
            var mainImage = document.getElementById('main-product-image');
            if (mainImage) {
                // Add fade effect for smooth transition
                mainImage.style.opacity = '0.5';
                mainImage.src = imagePath;
                mainImage.onload = function() {
                    mainImage.style.opacity = '1';
                };
                mainImage.onerror = function() {
                    mainImage.src = '/www/consutrade/images/default-product.png';
                    mainImage.style.opacity = '1';
                };
            }
            
            // Update active class on thumbnails
            var thumbnails = document.querySelectorAll('.small-img');
            for (var i = 0; i < thumbnails.length; i++) {
                thumbnails[i].classList.remove('active');
                if (i == selectedIndex) {
                    thumbnails[i].classList.add('active');
                }
            }
        }
        
        function buyNow(productId) {
            var productName = arguments[1];
            var productPrice = arguments[2];
            addToCart(productId, productName, productPrice);
            window.location.href = '/www/consutrade/cart.php';
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