<?php
/*
 * ConsuTrade - Public Seller Profile Page
 * Author: Kamogelo Phale
 * 
 * This page allows anyone to view a seller's public profile
 * Accessible via: seller-profile-public.php?seller_id=123
 */

require_once __DIR__ . '/init.php';

$baseUrl = getBaseUrl();

// Get seller ID from URL
$seller_id = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : 0;

if ($seller_id <= 0) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

// Get seller information using helper
$seller = getSellerById($conn, $seller_id);

if (!$seller) {
    // Seller not found
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

// Set profile image path using helper
$profile_image = getUserProfileImage($seller['profile_image']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($seller['full_name']); ?> - Seller Profile | ConsuTrade</title>
    <meta name="author" content="Kamogelo Phale">
    
    <!-- Master Stylesheet (includes all CSS) -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    
</head>
<body>

<?php include 'includes/header.php'; ?>

<main class="public-seller-profile-container">
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="<?php echo $baseUrl; ?>index.php">Home</a>
        <span> > </span>
        <a href="<?php echo $baseUrl; ?>product-listings.php">Products</a>
        <span> > </span>
        <span>Seller Profile</span>
    </div>

    <!-- Seller Profile Header -->
    <div class="seller-public-header">
        <div class="seller-public-avatar">
            <img src="<?php echo $profile_image; ?>" alt="<?php echo htmlspecialchars($seller['full_name']); ?>">
        </div>
        <div class="seller-public-info">
            <h1><?php echo htmlspecialchars($seller['full_name']); ?></h1>
            <div class="seller-public-meta">
                <?php if ($seller['id_verified'] == 1): ?>
                    <span class="verified-badge">
                        <img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" width="16px" height="16px" alt="Verified">
                        Verified Seller
                    </span>
                <?php else: ?>
                    <span class="unverified-badge">
                        <img src="<?php echo $baseUrl; ?>images/icons/not-verified-svgrepo-com.svg" width="16px" height="16px" alt="Unverified">
                        Unverified Seller
                    </span>
                <?php endif; ?>
                <span class="member-since">Member since <?php echo formatDate($seller['created_at']); ?></span>
            </div>
            <?php if ($seller['location']): ?>
                <p class="seller-location">
                    <img src="<?php echo $baseUrl; ?>images/icons/pin-location-svgrepo-com.svg" width="14px" height="14px" alt="Location">
                    <?php echo htmlspecialchars($seller['location']); ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Seller Stats Section -->
    <div class="seller-public-stats">
        <div class="stat-card">
            <h3>Products</h3>
            <p class="stat-number" id="stat-products">-</p>
        </div>
        <div class="stat-card">
            <h3>Orders Completed</h3>
            <p class="stat-number" id="stat-sales">-</p>
        </div>
        <div class="stat-card">
            <h3>Member Since</h3>
            <p class="stat-text"><?php echo formatDate($seller['created_at']); ?></p>
        </div>
    </div>

    <!-- Seller Products Section -->
    <div class="seller-public-products">
        <h2>Products from <?php echo htmlspecialchars($seller['full_name']); ?></h2>
        <div class="products-grid" id="products-grid">
            <div class="loading-spinner">Loading products...</div>
        </div>
    </div>
</main>

<script>
/*
 * ConsuTrade - Public Seller Profile Functionality
 * Author: Kamogelo Phale
 */
var baseUrl = '<?php echo $baseUrl; ?>';
var sellerId = <?php echo $seller_id; ?>;
var currentUserId = <?php echo $current_user_id ?: 0; ?>;
var currentUserRole = '<?php echo $current_user ? $current_user['role'] : ''; ?>';
var isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;

$(function() {
    loadSellerStats();
    loadSellerProducts();
    
    function loadSellerStats() {
        $.ajax({
            url: baseUrl + 'php/get-user-stats.php?seller_id=' + sellerId,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    $('#stat-products').text(data.total_products || 0);
                    $('#stat-sales').text(data.total_sales || 0);
                } else {
                    $('#stat-products').text('0');
                    $('#stat-sales').text('0');
                }
            },
            error: function() {
                $('#stat-products').text('0');
                $('#stat-sales').text('0');
            }
        });
    }
    
    function loadSellerProducts() {
        var $grid = $('#products-grid');
        $grid.html('<div class="loading-spinner">Loading products...</div>');
        
        $.ajax({
            url: baseUrl + 'php/get-seller-products.php?seller_id=' + sellerId,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success && data.products && data.products.length > 0) {
                    var productsWithSellerInfo = data.products.map(function(product) {
                        return {
                            id: product.id,
                            name: product.name,
                            price: product.price,
                            image: product.image,
                            status: product.status,
                            condition: product.condition || 'Good',
                            seller_name: '<?php echo addslashes($seller['full_name']); ?>',
                            location: '<?php echo addslashes($seller['location'] ?? 'Not specified'); ?>',
                            profile_image: '<?php echo addslashes($seller['profile_image'] ?? null); ?>',
                            is_verified: <?php echo $seller['id_verified'] == 1 ? 'true' : 'false'; ?>,
                            seller_id: sellerId
                        };
                    });
                    
                    if (typeof displayProducts === 'function') {
                        displayProducts(productsWithSellerInfo);
                    } else {
                        $grid.html('<div class="error-message">Error displaying products. Please refresh the page.</div>');
                    }
                } else {
                    $grid.html('<div class="no-products"><p>This seller has no products yet.</p></div>');
                }
            },
            error: function() {
                $grid.html('<div class="error-message">Error loading products. Please try again.</div>');
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>