<?php
/*
 * ConsuTrade - Product Details Page
 * Author: Kamogelo Phale
 * 
 * Displays single product - uses AJAX to load data
 * Uses CSS variables from style.css
 */

require_once dirname(__DIR__) . '/init.php';

$baseUrl = getBaseUrl();
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header('Location: product-listings.php');
    exit;
}

include 'includes/header.php';
?>

<main class="product-details-main">
    <div class="product-details-container" data-product-id="<?php echo $product_id; ?>">
        <div id="product-details-content">
            <div class="loading-spinner">Loading product details...</div>
        </div>
    </div>
</main>
<script src="<?php echo $baseUrl; ?>js/main.js"></script>
<script src="<?php echo $baseUrl; ?>js/products.js"></script>

<?php include 'includes/footer.php'; ?>