<?php
/*
 * ConsuTrade - Product Details Page
 * Author: Kamogelo Phale
 * 
 * Displays single product - uses AJAX to load data
 */

require_once __DIR__ . '/init.php';

$baseUrl = getBaseUrl();
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header('Location: product-listings.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Details - ConsuTrade</title>
    <meta name="author" content="Kamogelo Phale">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
</head>
<body>

<?php include 'includes/header.php'; ?>

<main class="product-details-main">
    <div class="product-details-container" data-product-id="<?php echo $product_id; ?>">
        <div id="product-details-content">
            <div class="loading-spinner">Loading product details...</div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<script src="<?php echo $baseUrl; ?>js/products.js"></script>

<script>
var productId = <?php echo $product_id; ?>;
var isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
var currentUserId = <?php echo $current_user_id ?: 0; ?>;
var currentUserRole = '<?php echo $current_user ? $current_user['role'] : ''; ?>';
var baseUrl = '<?php echo $baseUrl; ?>';
</script>

</body>
</html>