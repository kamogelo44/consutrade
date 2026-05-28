<?php
/*
 * ConsuTrade - Product Details Page
 * Author: Kamogelo Phale
 * 
 * Displays single product - uses AJAX to load detailed data
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

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header('Location: product-listings.php');
    exit;
}

// Use ProductRepository to get product data for breadcrumb
$productRepo = new ProductRepository($conn);
$productData = $productRepo->getProductForDisplay($product_id);

$product_name = $productData['title'] ?? 'Product Details';

// Set breadcrumb
$breadcrumbItems = [
    ['url' => 'product-listings.php', 'label' => 'All Products'],
    ['label' => htmlspecialchars($product_name)]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product_name); ?> - ConsuTrade</title>
    <meta name="author" content="Kamogelo Phale">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <style>
        /* ========== PRODUCT DETAILS PAGE SPECIFIC STYLES ========== */
        .product-details-main { width: 100%; max-width: 1400px; margin: 0 auto; padding: var(--spacing-xl); }
        .product-details-container { width: 100%; }
        
        /* ========== PRODUCT IMAGES GALLERY ========== */
        .top-items { display: flex; justify-content: space-between; gap: 40px; margin: 30px 0; }
        .product-imgs { flex: 1; max-width: 600px; width: 100%; }
        .main-img { background-color: var(--gray-lighter); width: 100%; height: auto; aspect-ratio: 4/3; margin-bottom: var(--spacing-md); border-radius: var(--radius-lg); overflow: hidden; }
        #main-product-image { transition: opacity 0.3s ease; width: 100%; height: 100%; object-fit: cover; }
        .main-img:hover img { transform: scale(1.02); }
        .smaller-imgs { display: grid; grid-template-columns: repeat(4, 1fr); gap: var(--spacing-md); width: 100%; }
        .small-img { background-color: var(--gray-lighter); width: 100%; aspect-ratio: 1/1; border-radius: var(--radius-md); overflow: hidden; cursor: pointer; }
        .small-img:hover { transform: translateY(-2px); box-shadow: var(--shadow-sm); }
        .small-img.active { border: 2px solid var(--primary-color); box-shadow: 0 0 0 2px rgba(255, 107, 0, 0.2); }
        .small-img img { width: 100%; height: 100%; object-fit: cover; }
        
        /* ========== PRODUCT INFO SECTION ========== */
        .product-info { display: flex; flex-direction: column; gap: var(--spacing-md); background-color: var(--white); padding: 30px; flex: 1; max-width: 500px; border: 1px solid var(--border-light); border-radius: var(--radius-lg); }
        .details-prod-name { font-size: var(--font-3xl); font-weight: var(--font-bold); color: var(--dark-bg); }
        .details-price { font-size: var(--font-4xl); font-weight: var(--font-bold); color: var(--primary-color); }
        .cat-badge { display: inline-flex; align-items: center; background-color: var(--primary-fade); border: 1px solid var(--primary-color); border-radius: var(--radius-round); padding: 4px 12px; width: auto; max-width: fit-content; }
        .cat-badge .cat-name { font-size: var(--font-xs); font-weight: var(--font-medium); color: var(--primary-color); white-space: nowrap; }
        .description { margin-top: var(--spacing-sm); }
        .description .sub-head { font-size: var(--font-lg); font-weight: var(--font-bold); margin-bottom: var(--spacing-sm); color: var(--dark-bg); }
        .description .des { font-size: var(--font-md); line-height: 1.5; color: var(--gray-medium); }
        .con-loc { display: flex; flex-direction: column; gap: var(--spacing-sm); }
        
        /* ========== SELLER REVIEWS SECTION ========== */
        .rev-container { display: flex; flex-direction: column; align-items: center; gap: var(--spacing-md); width: 100%; }
        .verified-badge, .not-verified-badge { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 6px 16px; border-radius: var(--radius-round); width: auto; min-width: 140px; height: 34px; }
        .verified-badge { background-color: var(--success-light); border: 1px solid var(--success); }
        .verified-badge p { font-size: var(--font-sm); font-weight: var(--font-medium); color: var(--success); margin: 0; }
        .not-verified-badge { background-color: var(--warning-light); border: 1px solid var(--warning); }
        .not-verified-badge p { font-size: var(--font-sm); font-weight: var(--font-medium); color: var(--warning); margin: 0; }
        .seller-profile { display: flex; flex-direction: column; align-items: center; gap: var(--spacing-sm); }
        .profile-pic { display: flex; justify-content: center; align-items: center; width: 60px; height: 60px; background-color: var(--white); border-radius: var(--radius-md); box-shadow: var(--shadow-sm); }
        .seller-name { text-align: center; font-size: var(--font-base); color: var(--dark-bg); font-weight: var(--font-bold); }
        .star-reviews { text-align: center; }
        .star-reviews h1 { font-size: var(--font-lg); font-weight: var(--font-bold); margin-bottom: var(--spacing-sm); color: var(--dark-bg); }
        .star { font-size: 30px; color: #ffc107; cursor: pointer; display: inline-block; }
        .star.empty { color: var(--border-light); }
        .view-profile { color: var(--gray-medium); width: 160px; height: 36px; border-radius: var(--radius-round); border: 1px solid var(--border-medium); background-color: var(--white); font-weight: var(--font-medium); cursor: pointer; }
        .view-profile:hover { background-color: var(--gray-bg); border-color: var(--primary-color); color: var(--primary-color); }
        
        /* ========== ACTION BUTTONS SECTION ========== */
        .actions { display: flex; justify-content: center; margin: 30px auto; padding: 0 var(--spacing-xl); }
        .actions-card { display: flex; flex-direction: column; justify-content: center; align-items: center; max-width: 600px; width: 100%; min-height: 280px; border: 1px solid var(--border-light); border-radius: var(--radius-lg); gap: var(--spacing-md); padding: 30px; background-color: var(--white); }
        .action-btns { display: flex; align-items: center; flex-direction: column; gap: var(--spacing-md); width: 100%; }
        .action-btns button { max-width: 400px; width: 100%; height: 52px; border-radius: var(--radius-md); font-weight: var(--font-bold); font-size: var(--font-base); display: flex; align-items: center; justify-content: center; gap: var(--spacing-sm); cursor: pointer; }
        .action-btns .cart-btn { color: var(--primary-color); background-color: var(--white); border: 2px solid var(--primary-color); }
        .action-btns .cart-btn:hover { background-color: var(--primary-fade); transform: translateY(-2px); }
        .action-btns .buy-btn { color: var(--white); background-color: var(--primary-color); border: none; }
        .action-btns .buy-btn:hover { background-color: var(--primary-dark); transform: translateY(-2px); }
        .action-btns .cart-btn.out-of-stock-btn { background-color: #ccc; cursor: not-allowed; opacity: 0.6; color: #666; border-color: #ccc; }
        
        /* ========== PAYMENT BADGE ========== */
        .payfast-badge { display: flex; align-items: center; justify-content: center; gap: var(--spacing-sm); margin-top: var(--spacing-lg); padding-top: var(--spacing-md); border-top: 1px solid var(--border-light); font-size: var(--font-sm); color: var(--gray-medium); }
        .payfast-badge img { height: 30px; width: auto; }
        
        /* ========== LOADING & ERROR STATES ========== */
        .loading-spinner { text-align: center; padding: 60px; color: var(--gray-medium); }
        .product-error-container { text-align: center; padding: 80px 20px; max-width: 500px; margin: 0 auto; background-color: var(--white); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); }
        .product-error-title { color: var(--error); margin-bottom: 10px; font-size: 24px; font-weight: var(--font-bold); }
        .product-error-action-btn { margin-top: 10px; padding: 10px 24px; background-color: var(--primary-color); color: var(--white); border: none; border-radius: var(--radius-md); cursor: pointer; }
        
        /* ========== RESPONSIVE ========== */
        @media (max-width: 992px) { .top-items { flex-direction: column; align-items: center; } .product-info { max-width: 100%; } }
        @media (max-width: 768px) { .product-details-main { padding: var(--spacing-lg); } .small-img { width: 70px; height: 70px; } .actions-card { padding: 20px; } }
        @media (max-width: 576px) { .details-prod-name { font-size: var(--font-xl); } .details-price { font-size: var(--font-2xl); } .small-img { width: 60px; height: 60px; } .action-btns button { height: 44px; font-size: var(--font-sm); } }
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<main class="product-details-main">
    <?php include 'includes/breadcrumb.php'; ?>
    
    <div class="product-details-container" data-product-id="<?php echo $product_id; ?>">
        <div id="product-details-content">
            <div class="loading-spinner">Loading product details...</div>
        </div>
    </div>
</main>

<?php $load_products_js = true; ?>
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
</body>
</html>