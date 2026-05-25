<?php
/*
 * ConsuTrade - Checkout Page
 * Author: Kamogelo Phale
 * 
 * Displays order summary and PayFast payment form.
 * Checkout processing is handled by php/endpoints/place-order.php
 */

require_once __DIR__ . '/init.php';

$baseUrl = getBaseUrl();

// Must have checkout data in session
if (!isset($_SESSION['checkout_data'])) {
    header('Location: ' . $baseUrl . 'cart.php');
    exit;
}

$data        = $_SESSION['checkout_data'];
$cart_items  = $data['cart_items'];
$subtotal    = $data['subtotal'];
$delivery_fee = $data['delivery_fee'];
$total       = $data['total'];

// Prepare PayFast form data
$payfast_data = $cartRepo->preparePayFastData([
    'payment_id'       => $data['payment_id'],
    'primary_order_id' => $data['primary_order_id'],
    'total'            => $total,
    'buyer_name'       => $data['buyer_name'],
    'buyer_email'      => $data['buyer_email'],
], $baseUrl);

// Clear checkout data after displaying (prevents double submission on refresh)
unset($_SESSION['checkout_data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - ConsuTrade</title>
    <meta name="author" content="Kamogelo Phale">
    
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
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
                <?php if (!empty($data['buyer_phone'])): ?>
                <input type="hidden" name="cell_number" value="<?php echo htmlspecialchars($data['buyer_phone']); ?>">
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