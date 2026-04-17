<?php
/*
 * ConsuTrade - Checkout Page
 * Author: Kamogelo Phale
 * 
 * This page handles the checkout process and redirects to PayFast
 */

session_start();

$baseUrl = "/www/consutrade/";

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

require_once 'php/config.php';

$user_id = $_SESSION['user_id'];

// Get cart items
$cart_sql = "SELECT c.cart_id, c.product_id, c.quantity, 
             p.title as product_name, p.price, p.image_url,
             u.full_name as seller_name
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

while ($row = $cart_result->fetch_assoc()) {
    $item_total = $row['price'] * $row['quantity'];
    $subtotal += $item_total;
    $cart_items[] = $row;
}

$cart_stmt->close();

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
$conn->close();

// If cart is empty, redirect to shop
if (empty($cart_items)) {
    header('Location: ' . $baseUrl . 'cart.php');
    exit;
}

// Generate unique order ID for PayFast
$order_id = time() . '_' . $user_id;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - ConsuTrade</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/checkout.css">
</head>
<body>
    <?php include 'header.php'; ?>

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
                <form action="https://sandbox.payfast.co.za/eng/process" method="post" id="payfast-form">
                    <input type="hidden" name="merchant_id" value="10000100">
                    <input type="hidden" name="merchant_key" value="46f0cd694581a">
                    <input type="hidden" name="return_url" value="<?php echo $baseUrl; ?>order-confirmation.php">
                    <input type="hidden" name="cancel_url" value="<?php echo $baseUrl; ?>cart.php">
                    <input type="hidden" name="notify_url" value="<?php echo $baseUrl; ?>php/payfast-notify.php">
                    
                    <input type="hidden" name="m_payment_id" value="<?php echo $order_id; ?>">
                    <input type="hidden" name="amount" value="<?php echo number_format($total, 2, '.', ''); ?>">
                    <input type="hidden" name="item_name" value="ConsuTrade Order">
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

    <?php include 'footer.php'; ?>
    <script src="js/main.js"></script>
</body>
</html>