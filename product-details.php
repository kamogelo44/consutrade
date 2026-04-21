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
    <title>Product Details - ConsuTrade</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/products.css">
    <link rel="stylesheet" href="css/login-signup.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/animations.css">
    <link rel="stylesheet" href="css/footer.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>
    
    <main>
        <section class="products-details" id="product-details-container">
            <div class="loading-spinner">Loading product details...</div>
        </section>
    </main>

    <!-- Footer (includes jQuery and main.js) -->
    <?php include 'includes/footer.php'; ?>
    
    <script>
    // Pass PHP session data to JavaScript
    var isLoggedIn = <?php echo isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true ? 'true' : 'false'; ?>;
    var currentUserId = <?php echo $_SESSION['user_id'] ?? 0; ?>;
    var currentUserRole = '<?php echo $_SESSION['role'] ?? ""; ?>';
    var productId = <?php echo $product_id; ?>;
    
    $(document).ready(function() {
        if (productId > 0) {
            loadProductDetails(productId);
        } else {
            showError('Product not found.');
        }
    });

    function loadProductDetails(id) {
        var $container = $('#product-details-container');
        $container.html('<div class="loading-spinner">Loading product details...</div>');
        
        $.get(baseUrl + 'php/get-product.php?id=' + id, function(data) {
            if (data.success && data.product) {
                displayProductDetails(data.product);
            } else {
                showError(data.error || 'Product not found.');
            }
        }).fail(function() {
            showError('Unable to load product. Please try again later.');
        });
    }
    
    function showError(message) {
        var $container = $('#product-details-container');
        $container.html(`
            <div class="error-state">
                <img src="${baseUrl}images/icons/shopping-cart-01-svgrepo-com.svg" width="64" height="64" alt="Error" class="error-icon">
                <h2 class="error-title">Oops!</h2>
                <p class="error-message-text">${escapeHtml(message)}</p>
                <button class="error-action-btn" onclick="window.location.href='${baseUrl}product-listings.php'">Browse Products</button>
            </div>
        `);
    }

    function displayProductDetails(product) {
        var $container = $('#product-details-container');
        
        // Fix image paths
        var mainImage = product.image;
        if (mainImage && !mainImage.startsWith('http') && !mainImage.startsWith('/')) {
            mainImage = baseUrl + mainImage;
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
                thumbPath = baseUrl + thumbPath;
            }
            allImages.push(thumbPath);
        }
        
        // Build gallery thumbnails HTML with active class
        var galleryHtml = '';
        for (var i = 0; i < 4; i++) {
            var thumbPath = allImages[i] || baseUrl + 'images/default-product.png';
            var activeClass = (i === 0) ? 'active' : '';
            galleryHtml += `
                <div class="small-img ${activeClass}" data-image-index="${i}" onclick="changeMainImage('${thumbPath}', ${i})">
                    <img src="${thumbPath}" alt="Product thumbnail" onerror="this.src='${baseUrl}images/default-product.png'">
                </div>
            `;
        }
        
        // Determine verification badge
        var verificationBadge = product.is_verified ? 
            '<div class="verified-badge"><img src="' + baseUrl + 'images/icons/verified-svgrepo-com.svg" width="20" height="20" alt="verification"><p>Verified Seller</p></div>' : 
            '<div class="not-verified-badge"><img src="' + baseUrl + 'images/icons/not-verified-svgrepo-com.svg" width="20" height="20" alt="not-verified"><p>Not Verified Seller</p></div>';
        
        // Build stars
        var rating = product.avg_rating || 0;
        var starsHtml = '';
        for (var i = 1; i <= 5; i++) {
            starsHtml += (i <= rating) ? '<span class="star">★</span>' : '<span class="star empty">★</span>';
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
                    <img src="${baseUrl}images/icons/shopping-cart-01-svgrepo-com.svg" width="24" height="24" alt="Cart">
                    Add to Cart
                </button>
                <button class="buy-btn" onclick="buyNow(${product.id}, '${escapeHtml(product.name).replace(/'/g, "\\'")}', ${product.price})">Buy Now</button>
            `;
        } else {
            actionButtonsHtml = `
                <div class="own-product-message">
                    <p>You cannot purchase your own product.</p>
                </div>
            `;
        }
        
        $container.html(`
            <div class="breadcrumb">
                <a href="${baseUrl}index.php">Home</a>
                <span> > </span>
                <a href="${baseUrl}product-listings.php">Product Listings</a>
                <span> > </span>
                <span>${escapeHtml(product.name)}</span>
            </div>
            
            <div class="top-items">
                <div class="product-imgs">
                    <div class="main-img" id="main-image-container">
                        <img src="${mainImage}" alt="${escapeHtml(product.name)}" onerror="this.src='${baseUrl}images/default-product.png'" id="main-product-image">
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
                            <img src="${baseUrl}images/icons/profile-svgrepo-com.svg" width="24" height="24" alt="Seller Profile Picture">
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
                    <button class="view-profile" onclick="window.location.href='${baseUrl}seller-profile.php?id=${product.seller_id}'">
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
                    <div class="payfast-badge">
                        <img src="${baseUrl}images/icons/Payfast logo.svg" alt="PayFast" width="80">
                        <span>Secure payments by PayFast</span>
                    </div>
                </div>
            </div>
        `);
    }
    
    function changeMainImage(imagePath, selectedIndex) {
        var $mainImage = $('#main-product-image');
        $mainImage.css('opacity', '0.5');
        $mainImage.attr('src', imagePath);
        $mainImage.on('load', function() { $(this).css('opacity', '1'); });
        $mainImage.on('error', function() { $(this).attr('src', baseUrl + 'images/default-product.png').css('opacity', '1'); });
        
        $('.small-img').removeClass('active').each(function(i) {
            if (i == selectedIndex) $(this).addClass('active');
        });
    }
    
    function buyNow(productId, productName, productPrice) {
        addToCart(productId, productName, productPrice);
        window.location.href = baseUrl + 'cart.php';
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        return $('<div>').text(text).html();
    }
    </script>
</body>
</html>