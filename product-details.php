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
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>
    
    <main>
        <section class="products-details" id="product-details-container" data-product-id="<?php echo $product_id; ?>">
            <div class="loading-spinner">Loading product details...</div>
        </section>
    </main>

    <!-- Footer (includes jQuery and main.js) -->
    <?php include 'includes/footer.php'; ?>
    
    <!-- Load products.js AFTER main.js (so addToCart is available) -->
    <script src="<?php echo $baseUrl; ?>js/products.js"></script>
    
    <script>
    // Pass PHP session data to JavaScript
    var isLoggedIn = <?php echo isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true ? 'true' : 'false'; ?>;
    var currentUserId = <?php echo $_SESSION['user_id'] ?? 0; ?>;
    var currentUserRole = '<?php echo $_SESSION['role'] ?? ""; ?>';
    var baseUrl = '<?php echo $baseUrl; ?>';
    var productId = <?php echo $product_id; ?>;
    
    $(document).ready(function() {
        if (productId > 0 && typeof loadProductDetails === 'function') {
            loadProductDetails(productId);
        } else if (productId > 0) {
            console.error('loadProductDetails function not found');
            $('#product-details-container').html('<div class="error-state">Error loading product details. Please refresh the page.</div>');
        }
    });
    </script>
</body>
</html>