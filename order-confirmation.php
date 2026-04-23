<?php
/*
 * ConsuTrade - Order Confirmation Page
 * Author: Kamogelo Phale
 * 
 * This page shows after successful payment
 */

session_start();

require_once "php/config.php";
require_once "php/helpers.php";

$baseUrl = getBaseUrl();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

// Get order ID from URL if available
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// If no order_id in URL, check if we just completed a payment
if ($order_id == 0 && isset($_GET['m_payment_id'])) {
    $parts = explode('_', $_GET['m_payment_id']);
    $order_id = (int)($parts[0] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/header.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/animations.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/footer.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/confirmation.css">
</head>
<body>

<?php include 'includes/header.php'; ?>

<main>
    <div class="confirmation-container">
        <div class="confirmation-card">
            <div class="confirmation-icon">
                <img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" width="64px" height="64px" alt="Confirmation Icon">
            </div>
            <h1 class="confirmation-title">Order Confirmed!</h1>
            <p class="confirmation-message">Thank you for your purchase. Your order has been received.</p>
            
            <?php if ($order_id > 0): ?>
                <p class="order-number">Order #<?php echo $order_id; ?></p>
            <?php endif; ?>
            
            <p class="confirmation-email">You will receive an email confirmation shortly.</p>
            
            <div class="confirmation-actions">
                <a href="<?php echo $baseUrl; ?>my-orders.php" class="btn-primary">View My Orders</a>
                <a href="<?php echo $baseUrl; ?>product-listings.php" class="btn-secondary">Continue Shopping</a>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<script>
// Update cart count to 0 after successful order
$(document).ready(function() {
    updateCartCount();
});
</script>
</body>
</html>