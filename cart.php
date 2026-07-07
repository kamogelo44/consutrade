<?php
/*
 * Shopping Cart Page - Displays user's cart items with quantity and stock awareness
 */

require_once __DIR__ . '/init.php';
include __DIR__ . '/includes/session-vars.php';
include __DIR__ . '/includes/functions.php';

$breadcrumbItems = [
    ['label' => 'Shopping Cart']
];

$cart_items = [];
$cart_totals = ['subtotal' => 0, 'delivery_fee' => 0, 'total' => 0];
$total_quantity = 0;

if ($isLoggedIn && isset($currentUser) && $currentUser->hasRole('buyer')) {
    $cart_items = $cartRepo->findByUser($currentUser->getUserId());
    $cart_totals = $cartService->calculateTotals($cart_items);
    $total_quantity = $cartRepo->countItems($currentUser->getUserId());
}

$page_js = 'cart.js';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - ConsuTrade</title>
    <meta name="description" content="View and manage your shopping cart items">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <main>
        <?php include 'includes/breadcrumb.php'; ?>

        <div class="cart-container">
            <div class="cart-header">
                <h1>My Cart (<span id="cart-item-count"><?php echo $total_quantity; ?></span> items)</h1>
            </div>

            <div id="cart-layout" style="display: <?php echo empty($cart_items) ? 'none' : 'flex'; ?>;">
                <div class="cart-grid">
                    <div class="table-wrapper">
                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Seller</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="cart-table-body"></tbody>
                        </table>
                    </div>
                    <div id="mobile-cart-items"></div>
                    <div class="order-summary">
                        <h2>Order Summary</h2>
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span class="sub-total-val">R <?php echo number_format($cart_totals['subtotal'], 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Delivery Fee</span>
                            <span class="deliv-fee-val">R <?php echo number_format($cart_totals['delivery_fee'], 2); ?></span>
                        </div>
                        <div class="summary-total">
                            <span>Total</span>
                            <span class="total-val">R <?php echo number_format($cart_totals['total'], 2); ?></span>
                        </div>
                        <button class="checkout-btn" id="checkoutBtn">Proceed to Checkout</button>
                        <button class="continue-shopping" id="continueBtn">Continue Shopping</button>
                        <div class="summary-footer">
                            <a href="https://www.payfast.co.za" class="payfast-badge" target="_blank" rel="noopener noreferrer">
                                <span>Secured with</span>
                                <img src="<?php echo $baseUrl; ?>images/icons/Payfast logo.svg" alt="PayFast">
                            </a>
                            <div class="security-text">
                                <img src="<?php echo $baseUrl; ?>images/icons/secure-card-svgrepo-com.svg" width="14" height="14" alt="Secure">
                                <span>Your payment is secure</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="empty-cart" style="display: <?php echo empty($cart_items) ? 'flex' : 'none'; ?>;">
                <div class="empty-state">
                    <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" width="64" height="64" alt="Empty cart">
                    <h3>Your cart is empty</h3>
                    <p>Looks like you haven't added anything yet</p>
                    <button class="shop-btn" id="browseBtn">Browse Products</button>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script>
        var initialCartData = {
            items: <?php echo json_encode($cart_items); ?>,
            subtotal: <?php echo json_encode($cart_totals['subtotal']); ?>,
            delivery_fee: <?php echo json_encode($cart_totals['delivery_fee']); ?>,
            total: <?php echo json_encode($cart_totals['total']); ?>
        };
    </script>

</body>

</html>