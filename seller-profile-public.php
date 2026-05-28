<?php
/*
 * ConsuTrade - Public Seller Profile Page
 * Author: Kamogelo Phale
 */

require_once __DIR__ . '/init.php';

// Read register errors
$registerErrors = $_SESSION['register_errors'] ?? [];
$registerFormData = $_SESSION['register_form_data'] ?? [];
unset($_SESSION['register_errors'], $_SESSION['register_form_data']);

// Read login errors
$loginErrors = $_SESSION['login_errors'] ?? [];
$loginEmail = $_SESSION['login_email'] ?? '';
unset($_SESSION['login_errors'], $_SESSION['login_email']);

$seller_id = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : 0;

if ($seller_id <= 0 || !($seller = getSellerById($conn, $seller_id))) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

$profile_image = getUserProfileImage($seller['profile_image']);

// Update to remove Products from breadcrumb unless coming from a product
$from_product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$from_product_name = isset($_GET['product_name']) ? urldecode($_GET['product_name']) : '';

if ($from_product_id > 0 && $from_product_name) {
    $breadcrumbItems = [
        ['url' => 'product-listings.php', 'label' => 'Products'],
        ['url' => 'product-details.php?id=' . $from_product_id, 'label' => htmlspecialchars($from_product_name)],
        ['label' => htmlspecialchars($seller['full_name'])]
    ];
} else {
    $breadcrumbItems = [
        ['label' => htmlspecialchars($seller['full_name'])]
    ];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($seller['full_name']); ?> - Seller Profile | ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
<style>
    /* ========== PUBLIC SELLER PROFILE SPECIFIC STYLES ========== */
    .public-seller-profile-container { width: 100%; max-width: 100%; padding: var(--spacing-xl); min-height: calc(100vh - 200px); }
    
    .seller-public-header { display: flex; align-items: center; gap: var(--spacing-xl); background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); border-radius: var(--radius-lg); padding: var(--spacing-2xl) var(--spacing-xl); margin-bottom: var(--spacing-xl); color: var(--white); box-shadow: var(--shadow-md); }
    .seller-public-avatar { width: 120px; height: 120px; background: var(--white); border-radius: var(--radius-round); overflow: hidden; flex-shrink: 0; border: 4px solid rgba(255,255,255,0.3); box-shadow: var(--shadow-md); }
    .seller-public-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .seller-public-info { flex: 1; }
    .seller-public-info h1 { font-size: var(--font-3xl); font-weight: var(--font-bold); margin-bottom: var(--spacing-sm); color: var(--white); }
    .seller-public-meta { display: flex; align-items: center; gap: var(--spacing-md); flex-wrap: wrap; margin-bottom: var(--spacing-sm); }
    
    .verified-badge, .unverified-badge { display: inline-flex; align-items: center; gap: var(--spacing-xs); padding: var(--spacing-xs) var(--spacing-md); border-radius: var(--radius-round); font-size: var(--font-sm); font-weight: var(--font-medium); }
    .verified-badge { background-color: var(--success); color: var(--white); }
    .unverified-badge { background-color: var(--warning); color: var(--white); }
    .verified-badge img, .unverified-badge img { filter: brightness(0) invert(1); }
    
    .member-since { background: rgba(0,0,0,0.2); padding: var(--spacing-xs) var(--spacing-md); border-radius: var(--radius-round); font-size: var(--font-sm); color: var(--white); }
    .seller-location { display: inline-flex; align-items: center; gap: var(--spacing-xs); background: rgba(0,0,0,0.15); padding: var(--spacing-xs) var(--spacing-md); border-radius: var(--radius-round); font-size: var(--font-sm); width: fit-content; color: var(--white); }
    .seller-location img { filter: brightness(0) invert(1); }
    
    .seller-public-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--spacing-lg); margin-bottom: var(--spacing-2xl); }
    .stat-card { background: var(--white); border-radius: var(--radius-lg); padding: var(--spacing-xl); text-align: center; box-shadow: var(--shadow-sm); border: 1px solid var(--border-light); transition: transform var(--transition-fast), box-shadow var(--transition-fast); }
    .stat-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); border-color: var(--primary-light); }
    .stat-card h3 { font-size: var(--font-md); font-weight: var(--font-medium); color: var(--gray-medium); margin-bottom: var(--spacing-sm); text-transform: uppercase; letter-spacing: 0.5px; }
    .stat-number { font-size: var(--font-3xl); font-weight: var(--font-bold); color: var(--primary-color); }
    .stat-text { font-size: var(--font-base); font-weight: var(--font-medium); color: var(--gray-dark); }
    
    .seller-public-products { width: 100%; }
    .seller-public-products h2 { font-size: var(--font-2xl); font-weight: var(--font-bold); margin-bottom: var(--spacing-xl); padding-bottom: var(--spacing-sm); border-bottom: 3px solid var(--primary-color); color: var(--gray-dark); display: inline-block; }
    
    /* Product Grid for Seller Profile - Smaller cards */
    .seller-public-products .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: var(--spacing-lg);
    }
    
    /* Override product card styles for seller profile */
    .seller-public-products .prod-card .img-container { height: 180px; }
    .seller-public-products .prod-card .prod-name { font-size: var(--font-sm); min-height: 38px; }
    .seller-public-products .prod-card .prod-price { font-size: var(--font-xl); }
    .seller-public-products .seller-avatar { width: 32px; height: 32px; }
    .seller-public-products .seller-details .seller-name { font-size: var(--font-xs); }
    .seller-public-products .add-to-cart-btn { padding: 8px; font-size: var(--font-xs); margin-top: 8px; }
    .seller-public-products .verified-badge-card, 
    .seller-public-products .unverified-badge-card { padding: 2px 6px; font-size: 8px; }
    
    /* ========== RESPONSIVE ========== */
    @media (max-width: 992px) {
        .seller-public-products .products-grid { grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: var(--spacing-md); }
        .seller-public-products .prod-card .img-container { height: 160px; }
    }
    
    @media (max-width: 768px) {
        .public-seller-profile-container { padding: var(--spacing-lg); }
        .seller-public-header { flex-direction: column; text-align: center; padding: var(--spacing-xl) var(--spacing-lg); }
        .seller-public-meta { justify-content: center; }
        .seller-location { margin: var(--spacing-sm) auto 0; }
        .seller-public-stats { grid-template-columns: 1fr; gap: var(--spacing-md); }
        .stat-number { font-size: var(--font-2xl); }
        
        .seller-public-products .products-grid { grid-template-columns: repeat(2, 1fr); gap: var(--spacing-md); }
        .seller-public-products .prod-card .img-container { height: 150px; }
    }
    
    @media (max-width: 576px) {
        .seller-public-avatar { width: 100px; height: 100px; }
        .seller-public-info h1 { font-size: var(--font-2xl); }
        .stat-number { font-size: var(--font-xl); }
        
        .seller-public-products .products-grid { grid-template-columns: 1fr; }
        .seller-public-products .prod-card .img-container { height: 200px; }
        .seller-public-products .prod-card .prod-name { font-size: var(--font-base); }
        .seller-public-products .prod-card .prod-price { font-size: var(--font-lg); }
    }
</style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<main class="public-seller-profile-container">
    <?php include 'includes/breadcrumb.php'; ?>

    <div class="seller-public-header">
        <div class="seller-public-avatar">
            <img src="<?php echo $profile_image; ?>" alt="<?php echo htmlspecialchars($seller['full_name']); ?>">
        </div>
        <div class="seller-public-info">
            <h1><?php echo htmlspecialchars($seller['full_name']); ?></h1>
            <div class="seller-public-meta">
                <?php if ($seller['id_verified'] == 1): ?>
                    <span class="verified-badge"><img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" width="16" height="16" alt="Verified"> Verified Seller</span>
                <?php else: ?>
                    <span class="unverified-badge"><img src="<?php echo $baseUrl; ?>images/icons/not-verified-svgrepo-com.svg" width="16" height="16" alt="Unverified"> Unverified Seller</span>
                <?php endif; ?>
                <span class="member-since">Member since <?php echo formatDate($seller['created_at']); ?></span>
            </div>
            <?php if ($seller['location']): ?>
                <div class="seller-location">
                    <img src="<?php echo $baseUrl; ?>images/icons/pin-location-svgrepo-com.svg" width="14" height="14" alt="Location">
                    <?php echo htmlspecialchars($seller['location']); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="seller-public-stats">
        <div class="stat-card"><h3>Products</h3><p class="stat-number" id="stat-products">-</p></div>
        <div class="stat-card"><h3>Orders Completed</h3><p class="stat-number" id="stat-sales">-</p></div>
        <div class="stat-card"><h3>Member Since</h3><p class="stat-text"><?php echo formatDate($seller['created_at']); ?></p></div>
    </div>

    <div class="seller-public-products">
        <h2>Products from <?php echo htmlspecialchars($seller['full_name']); ?></h2>
        <div class="products-grid" id="products-grid"><div class="loading-spinner">Loading products...</div></div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<?php if (!empty($registerErrors)): ?>
<script>
$(function() {
    openModal($('#register-modal'));
    displayModalErrors('#register-modal', <?php echo json_encode($registerErrors); ?>, <?php echo json_encode($registerFormData); ?>);
});
</script>
<?php endif; ?>

<?php if (!empty($loginErrors)): ?>
<script>
$(function() {
    openModal($('#login-modal'));
    displayModalErrors('#login-modal', <?php echo json_encode($loginErrors); ?>, {email: <?php echo json_encode($loginEmail); ?>});
});
</script>
<?php endif; ?>

<!-- Load products.js for displayProducts function -->
<script src="<?php echo $baseUrl; ?>js/products.js"></script>

<script>
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
            url: baseUrl + 'php/endpoints/get-user-stats.php?seller_id=' + sellerId,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                $('#stat-products').text(data.success ? (data.total_products || 0) : '0');
                $('#stat-sales').text(data.success ? (data.total_sales || 0) : '0');
            },
            error: function() { $('#stat-products, #stat-sales').text('0'); }
        });
    }
    
    function loadSellerProducts() {
        var $grid = $('#products-grid');
        $grid.html('<div class="loading-spinner">Loading products...</div>');
        
        $.ajax({
            url: baseUrl + 'php/endpoints/get-seller-products.php?seller_id=' + sellerId,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success && data.products && data.products.length > 0) {
                    var productsWithSellerInfo = data.products.map(function(product) {
                        return {
                            id: product.id, name: product.name, price: product.price, image: product.image,
                            status: 'active', condition: product.condition || 'Good',
                            seller_name: '<?php echo addslashes($seller['full_name']); ?>',
                            location: '<?php echo addslashes($seller['location'] ?? 'Not specified'); ?>',
                            profile_image: '<?php echo addslashes($seller['profile_image'] ?? null); ?>',
                            is_verified: <?php echo $seller['id_verified'] == 1 ? 'true' : 'false'; ?>,
                            seller_id: sellerId, stock_quantity: product.stock_quantity || 1
                        };
                    });
                    if (typeof displayProducts === 'function') displayProducts(productsWithSellerInfo);
                    else $grid.html('<div class="error-message">Error displaying products.</div>');
                } else {
                    $grid.html('<div class="no-products"><p>This seller has no products yet.</p></div>');
                }
            },
            error: function() { $grid.html('<div class="error-message">Error loading products. Please try again.</div>'); }
        });
    }
});
</script>

</body>
</html>