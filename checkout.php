<?php
/*
 * ConsuTrade - Checkout Page
 * Author: Kamogelo Phale
 * 
 * This page handles the checkout process and redirects to PayFast
 */

require_once __DIR__ . '/init.php';

$baseUrl = getBaseUrl();

// Check if user is logged in using centralized auth
if (!$is_logged_in) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

$user_id = $current_user_id;

// Get cart items using helper function
$cart_items = getCartItems($conn, $user_id);

// If cart is empty, redirect to shop
if (empty($cart_items)) {
    header('Location: ' . $baseUrl . 'cart.php');
    exit;
}

// ========== STOCK VERIFICATION ==========
$stock_errors = verifyCartStock($conn, $cart_items);

if (!empty($stock_errors)) {
    $_SESSION['checkout_errors'] = $stock_errors;
    header('Location: ' . $baseUrl . 'cart.php');
    exit;
}

// Calculate totals using helper function
$totals = calculateCartTotals($cart_items);
$subtotal = $totals['subtotal'];
$delivery_fee = $totals['delivery_fee'];
$total = $totals['total'];

// Process checkout (creates orders, clears cart)
$checkout_result = processCheckout($conn, $user_id, $cart_items);

if (!$checkout_result['success']) {
    $_SESSION['checkout_errors'] = $checkout_result['errors'];
    header('Location: ' . $baseUrl . 'cart.php');
    exit;
}

// Get user info for PayFast
$user = getUserCheckoutInfo($conn, $user_id);

// Prepare PayFast data
$payfast_data = preparePayFastData([
    'payment_id' => $checkout_result['payment_id'],
    'primary_order_id' => $checkout_result['primary_order_id'],
    'total' => $total,
    'buyer_name' => $user['full_name'],
    'buyer_email' => $user['email']
], $baseUrl);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - ConsuTrade</title>
    <meta name="author" content="Kamogelo Phale">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/header.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/animations.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/footer.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/cart-checkout.css">
</head>
<body>

<?php include 'includes/header.php'; ?>

<main class="checkout-container">
    <h1>Checkout</h1>
    
    <div class="checkout-layout">
        <!-- Order Summary -->
        <div class="order-summary">
            <h2>Order Summary</h2>
            
            <?php foreach ($cart_items as $item): ?>
                <div class="checkout-item">
                    <div class="item-info">
                        <span class="item-name"><?php echo htmlspecialchars($item['title']); ?></span>
                        <span class="item-quantity">x<?php echo $item['quantity']; ?></span>
                    </div>
                    <div class="item-price">R <?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                </div>
            <?php endforeach; ?>
            
            <div class="order-totals">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>R <?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="total-row">
                    <span>Delivery Fee:</span>
                    <span>R <?php echo number_format($delivery_fee, 2); ?></span>
                </div>
                <div class="total-row grand-total">
                    <span>Total:</span>
                    <span>R <?php echo number_format($total, 2); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Payment Section -->
        <div class="payment-section">
            <h2>Payment Method</h2>
            
            <div class="payment-method">
                <div class="payment-option active">
                    <img src="<?php echo $baseUrl; ?>images/icons/Payfast logo.svg" alt="PayFast" width="100px">
                    <p>Secure payment via PayFast</p>
                </div>
            </div>
            
            <!-- PayFast Payment Form -->
            <form action="<?php echo PAYFAST_PROCESS_URL; ?>" method="post" id="payfast-form">
                <input type="hidden" name="merchant_id" value="<?php echo $payfast_data['merchant_id']; ?>">
                <input type="hidden" name="merchant_key" value="<?php echo $payfast_data['merchant_key']; ?>">
                <input type="hidden" name="return_url" value="<?php echo $payfast_data['return_url']; ?>">
                <input type="hidden" name="cancel_url" value="<?php echo $payfast_data['cancel_url']; ?>">
                <input type="hidden" name="notify_url" value="<?php echo $payfast_data['notify_url']; ?>">
                
                <input type="hidden" name="m_payment_id" value="<?php echo $payfast_data['m_payment_id']; ?>">
                <input type="hidden" name="amount" value="<?php echo $payfast_data['amount']; ?>">
                <input type="hidden" name="item_name" value="<?php echo $payfast_data['item_name']; ?>">
                <input type="hidden" name="item_description" value="<?php echo $payfast_data['item_description']; ?>">
                
                <input type="hidden" name="name_first" value="<?php echo htmlspecialchars($payfast_data['name_first']); ?>">
                <input type="hidden" name="email_address" value="<?php echo htmlspecialchars($payfast_data['email_address']); ?>">
                <?php if (!empty($user['phone'])): ?>
                <input type="hidden" name="cell_number" value="<?php echo htmlspecialchars($user['phone']); ?>">
                <?php endif; ?>
                
                <button type="submit" class="pay-now-btn">Pay Now with PayFast</button>
            </form>
            
            <div class="security-badge">
                <img src="<?php echo $baseUrl; ?>images/icons/secure-card-svgrepo-com.svg" width="20px" height="20px" alt="Secure">
                <span>Your payment is secure. All transactions are encrypted.</span>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
</body>
</html>