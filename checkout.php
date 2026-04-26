<?php
/*
 * ConsuTrade - Checkout Page
 * Author: Kamogelo Phale
 * 
 * This page handles the checkout process and redirects to PayFast
 */

require_once 'php/helpers.php';
startSession('user');

$baseUrl = getBaseUrl();

// Check if user is logged in
if (!isUserLoggedIn()) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get cart items
$cart_sql = "SELECT c.cart_id, c.product_id, c.quantity, 
             p.title as product_name, p.price, p.image_url,
             u.full_name as seller_name, u.user_id as seller_id
             FROM cart c
             JOIN products p ON c.product_id = p.product_id
             JOIN users u ON p.seller_id = u.user_id
             WHERE c.user_id = ?";

$cart_stmt = $conn->prepare($cart_sql);
$cart_stmt->bind_param('i', $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();

$cart_items = [];
$subtotal = 0;
$seller_ids = [];

while ($row = $cart_result->fetch_assoc()) {
    $item_total = $row['price'] * $row['quantity'];
    $subtotal += $item_total;
    $cart_items[] = $row;
    
    if (!in_array($row['seller_id'], $seller_ids)) {
        $seller_ids[] = $row['seller_id'];
    }
}

$cart_stmt->close();

// If cart is empty, redirect to shop
if (empty($cart_items)) {
    header('Location: ' . $baseUrl . 'cart.php');
    exit;
}

// Calculate totals
$delivery_fee = ($subtotal > 0 && $subtotal < 500) ? 50 : 0;
$total = $subtotal + $delivery_fee;

// Get user info for PayFast
$user_sql = "SELECT full_name, email, phone FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param('i', $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$user_stmt->close();

// Create orders in database BEFORE redirecting to PayFast
$order_ids = [];
$payment_id = time() . '_' . $user_id;

foreach ($seller_ids as $seller_id) {
    // Calculate this seller's subtotal
    $seller_subtotal = 0;
    foreach ($cart_items as $item) {
        if ($item['seller_id'] == $seller_id) {
            $seller_subtotal += $item['price'] * $item['quantity'];
        }
    }
    
    // Calculate delivery fee for this seller
    $seller_delivery = ($seller_subtotal > 0 && $seller_subtotal < 500) ? 50 : 0;
    $seller_total = $seller_subtotal + $seller_delivery;
    
    // Insert order
    $order_sql = "INSERT INTO orders (buyer_id, seller_id, total_price, status, payment_id, created_at) 
                  VALUES (?, ?, ?, 'pending', ?, NOW())";
    $order_stmt = $conn->prepare($order_sql);
    $order_stmt->bind_param('iids', $user_id, $seller_id, $seller_total, $payment_id);
    
    if ($order_stmt->execute()) {
        $order_id = $order_stmt->insert_id;
        $order_ids[] = $order_id;
        
        // Insert order items
        foreach ($cart_items as $item) {
            if ($item['seller_id'] == $seller_id) {
                $item_sql = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                             VALUES (?, ?, ?, ?)";
                $item_stmt = $conn->prepare($item_sql);
                $item_stmt->bind_param('iiid', $order_id, $item['product_id'], $item['quantity'], $item['price']);
                $item_stmt->execute();
                $item_stmt->close();
            }
        }
    }
    $order_stmt->close();
}

// Use the first order ID for the payment reference
$primary_order_id = !empty($order_ids) ? $order_ids[0] : 0;
$payment_reference = $primary_order_id . '_' . $user_id;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/header.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/animations.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/footer.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/checkout.css">
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
                        <span class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></span>
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
                <input type="hidden" name="merchant_id" value="<?php echo PAYFAST_MERCHANT_ID; ?>">
                <input type="hidden" name="merchant_key" value="<?php echo PAYFAST_MERCHANT_KEY; ?>">
                <input type="hidden" name="return_url" value="<?php echo getAbsoluteUrl('order-confirmation.php'); ?>">
                <input type="hidden" name="cancel_url" value="<?php echo getAbsoluteUrl('cart.php'); ?>">
                <input type="hidden" name="notify_url" value="<?php echo getAbsoluteUrl('php/payfast-notify.php'); ?>">
                
                <input type="hidden" name="m_payment_id" value="<?php echo $payment_reference; ?>">
                <input type="hidden" name="amount" value="<?php echo number_format($total, 2, '.', ''); ?>">
                <input type="hidden" name="item_name" value="ConsuTrade Order #<?php echo $primary_order_id; ?>">
                <input type="hidden" name="item_description" value="Order from ConsuTrade">
                
                <input type="hidden" name="name_first" value="<?php echo htmlspecialchars($user['full_name']); ?>">
                <input type="hidden" name="email_address" value="<?php echo htmlspecialchars($user['email']); ?>">
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