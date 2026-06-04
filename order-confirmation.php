<?php
/*
 * ConsuTrade - Order Confirmation Page
 * Author: Kamogelo Phale
 */

require_once __DIR__ . '/init.php';
include __DIR__ . '/includes/session-vars.php';

$baseUrl = getBaseUrl();

if (!$isLoggedIn) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

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
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <main>
        <div class="confirmation-container">
            <div class="confirmation-card">
                <div class="confirmation-icon">
                    <img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" width="64" height="64" alt="Confirmed">
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
    <?php include 'includes/modal-errors.php'; ?>

    <script>
        $(function() {
            if (typeof updateCartCount === 'function') {
                updateCartCount();
            }
            $('.cart-count').text('0');
            if (window.sessionStorage) {
                sessionStorage.setItem('cart_count', 0);
            }
        });
    </script>
</body>

</html>