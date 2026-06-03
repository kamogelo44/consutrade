<?php
/*
 * ConsuTrade - Shopping Cart Page
 * Author: Kamogelo Phale
 * 
 * Displays user's cart items with quantity and stock awareness
 */

require_once __DIR__ . '/init.php';

// Read register errors
$registerErrors = $_SESSION['register_errors'] ?? [];
$registerFormData = $_SESSION['register_form_data'] ?? [];
unset($_SESSION['register_errors'], $_SESSION['register_form_data']);

// Read login errors
$loginErrors = $_SESSION['login_errors'] ?? [];
$loginEmail = $_SESSION['login_email'] ?? '';
unset($_SESSION['login_errors'], $_SESSION['login_email']);

$breadcrumbItems = [
    ['label' => 'Shopping Cart']
];

// Initialize cart variables
$cart_items = [];
$cart_totals = ['subtotal' => 0, 'delivery_fee' => 0, 'total' => 0];
$total_quantity = 0;

// If user is logged in, load cart using CartRepository
if ($isLoggedIn && $currentUser instanceof Buyer) {
    $cart_items = $cartRepo->getCartItems($currentUser->getUserId());
    $cart_totals = $cartRepo->calculateCartTotals($cart_items);
    $total_quantity = $cartRepo->getCartCount($currentUser->getUserId());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <style>
        /* ========== CART PAGE STYLES - ONLY CART-SPECIFIC ========== */

        /* Cart Layout */
        .cart-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px var(--spacing-xl);
        }

        .cart-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .cart-header h1 {
            font-size: var(--font-3xl);
            font-weight: var(--font-bold);
            color: var(--dark-bg);
            padding-bottom: var(--spacing-sm);
            border-bottom: 2px solid var(--primary-color);
            display: inline-block;
        }

        /* Cart Grid */
        .cart-grid {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
            align-items: start;
        }

        .cart-items {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Cart Item Card */
        .cart-item {
            display: grid;
            grid-template-columns: 100px 1fr auto;
            gap: 20px;
            background: var(--white);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            transition: all var(--transition-fast);
        }

        .cart-item:hover {
            box-shadow: var(--shadow-md);
        }

        /* Product Image */
        .item-image {
            width: 100px;
            height: 100px;
            background: var(--gray-bg);
            border-radius: var(--radius-md);
            overflow: hidden;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Product Details */
        .item-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .item-title {
            font-size: var(--font-base);
            font-weight: var(--font-bold);
            color: var(--dark-bg);
            margin: 0;
        }

        .item-seller {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .seller-name {
            font-size: var(--font-sm);
            color: var(--gray-medium);
        }

        /* Cart Badges */
        .verified-badge,
        .unverified-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: var(--radius-round);
            font-size: 10px;
        }

        .verified-badge img,
        .unverified-badge img {
            width: 10px;
            height: 10px;
        }

        .verified-badge {
            background: var(--success-light);
            border: 1px solid var(--success);
            color: var(--success);
        }

        .unverified-badge {
            background: var(--warning-light);
            border: 1px solid var(--warning);
            color: var(--warning);
        }

        .item-price {
            font-size: var(--font-lg);
            font-weight: var(--font-bold);
            color: var(--primary-color);
        }

        /* Stock Status with Icons */
        .stock-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: var(--radius-md);
            font-size: var(--font-xs);
            font-weight: var(--font-medium);
            width: fit-content;
        }

        .stock-status img {
            width: 12px;
            height: 12px;
        }

        .stock-status.in-stock {
            background: var(--success-light);
            color: var(--success-dark);
        }

        .stock-status.low-stock {
            background: var(--warning-light);
            color: var(--warning-dark);
        }

        .stock-status.out-of-stock {
            background: var(--error-light);
            color: var(--error-dark);
        }

        /* Item Actions */
        .item-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 12px;
        }

        /* Quantity Controls */
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--gray-bg);
            border-radius: var(--radius-md);
            padding: 4px;
        }

        .qty-btn {
            width: 32px;
            height: 32px;
            background: var(--white);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 18px;
            font-weight: bold;
            transition: all var(--transition-fast);
        }

        .qty-btn:hover {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: var(--white);
        }

        .qty-input {
            width: 45px;
            height: 32px;
            text-align: center;
            border: none;
            background: transparent;
            font-size: var(--font-md);
        }

        /* Remove Item */
        .remove-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: transparent;
            border: 1px solid var(--error);
            color: var(--error);
            cursor: pointer;
            font-size: var(--font-sm);
            font-weight: var(--font-medium);
            padding: 6px 14px;
            border-radius: var(--radius-sm);
            transition: all var(--transition-fast);
        }

        .remove-item:hover {
            background: var(--error);
            color: var(--white);
        }

        .remove-item:hover img {
            filter: brightness(0) invert(1);
        }

        /* Order Summary */
        .order-summary {
            background: var(--gray-bg);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: 20px;
            position: sticky;
            top: 100px;
        }

        .order-summary h2 {
            font-size: var(--font-xl);
            font-weight: var(--font-bold);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-light);
            text-align: center;
            color: var(--dark-bg);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            color: var(--gray-medium);
            font-size: var(--font-md);
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-top: 1px solid var(--border-light);
            font-size: var(--font-xl);
            font-weight: var(--font-bold);
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        /* Buttons */
        .checkout-btn {
            width: 100%;
            padding: 14px;
            background: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: var(--radius-md);
            font-weight: var(--font-bold);
            cursor: pointer;
            margin-top: 15px;
            transition: all var(--transition-normal);
        }

        .checkout-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .continue-shopping {
            width: 100%;
            padding: 12px;
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            border-radius: var(--radius-md);
            font-weight: var(--font-bold);
            cursor: pointer;
            margin-top: 10px;
            transition: all var(--transition-normal);
        }

        .continue-shopping:hover {
            background: var(--primary-color);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* Summary Footer */
        .summary-footer {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid var(--border-light);
        }

        .payfast-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: var(--font-sm);
            color: var(--gray-medium);
            text-decoration: none;
            transition: all var(--transition-fast);
        }

        .payfast-badge:hover {
            color: var(--primary-color);
        }

        .payfast-badge img {
            height: 20px;
            width: auto;
        }

        .security-text {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: var(--font-xs);
            color: var(--gray-light);
            margin-top: 10px;
        }

        .security-text img {
            width: 14px;
            height: 14px;
            opacity: 0.6;
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 768px) {
            .cart-container {
                padding: 15px var(--spacing-md);
            }

            .cart-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .order-summary {
                position: static;
            }
        }

        @media (max-width: 640px) {
            .cart-item {
                grid-template-columns: 80px 1fr;
                gap: 15px;
            }

            .item-actions {
                grid-column: 2 / 3;
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                margin-top: 10px;
            }

            .item-image {
                width: 80px;
                height: 80px;
            }

            .item-title {
                font-size: var(--font-sm);
            }

            .item-price {
                font-size: var(--font-md);
            }
        }

        @media (max-width: 480px) {
            .cart-container {
                padding: 10px var(--spacing-sm);
            }

            .cart-item {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .item-image {
                width: 100px;
                height: 100px;
                margin: 0 auto;
            }

            .item-details {
                text-align: center;
            }

            .item-seller {
                justify-content: center;
            }

            .item-actions {
                grid-column: 1;
                flex-direction: column;
                align-items: stretch;
            }

            .quantity-control {
                justify-content: center;
            }

            .cart-header h1 {
                font-size: var(--font-xl);
            }
        }
    </style>
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <main>
        <?php include 'includes/breadcrumb.php'; ?>

        <div class="cart-container">
            <div class="cart-header">
                <h1>My Cart (<span id="cart-item-count"><?php echo $total_quantity; ?></span> items)</h1>
            </div>

            <?php if (empty($cart_items)): ?>
                <div class="empty-state">
                    <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" width="64" height="64" alt="Empty cart">
                    <h3>Your cart is empty</h3>
                    <p>Looks like you haven't added anything yet</p>
                    <button class="view-all-btn" id="browseBtn">Browse Products</button>
                </div>
            <?php else: ?>
                <div class="cart-grid">
                    <!-- Cart Items Section -->
                    <div class="cart-items" id="cart-items">
                        <?php foreach ($cart_items as $item): ?>
                            <?php $isVerified = $item['is_verified'] ?? false; ?>
                            <div class="cart-item" data-cart-id="<?php echo $item['cart_id']; ?>" data-product-id="<?php echo $item['product_id']; ?>">
                                <div class="item-image">
                                    <img src="<?php echo $item['image_url']; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" onerror="this.src='<?php echo $baseUrl; ?>images/default-product.png'">
                                </div>

                                <div class="item-details">
                                    <h3 class="item-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                                    <div class="item-seller">
                                        <span class="seller-name"><?php echo htmlspecialchars($item['seller_name']); ?></span>
                                        <?php if ($isVerified): ?>
                                            <span class="verified-badge">
                                                <img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" width="10" height="10" alt="Verified">
                                                Verified
                                            </span>
                                        <?php else: ?>
                                            <span class="unverified-badge">
                                                <img src="<?php echo $baseUrl; ?>images/icons/not-verified-svgrepo-com.svg" width="10" height="10" alt="Unverified">
                                                Unverified
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-price">R <?php echo number_format($item['price'], 2); ?></div>

                                    <!-- Stock Status with Icons -->
                                    <?php if ($item['stock_quantity'] <= 0): ?>
                                        <div class="stock-status out-of-stock">
                                            <img src="<?php echo $baseUrl; ?>images/icons/close-svgrepo-com.svg" width="12" height="12" alt="Out of stock">
                                            <span>Out of stock</span>
                                        </div>
                                    <?php elseif ($item['stock_quantity'] <= 5): ?>
                                        <div class="stock-status low-stock">
                                            <img src="<?php echo $baseUrl; ?>images/icons/warning-svgrepo-com.svg" width="12" height="12" alt="Low stock">
                                            <span>Only <?php echo $item['stock_quantity']; ?> left</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="stock-status in-stock">
                                            <img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" width="12" height="12" alt="In stock">
                                            <span>In stock</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="item-actions">
                                    <div class="quantity-control">
                                        <button class="qty-btn qty-decrease" data-cart-id="<?php echo $item['cart_id']; ?>">-</button>
                                        <input type="number" class="qty-input" value="<?php echo $item['quantity']; ?>"
                                            min="1" max="<?php echo min(99, $item['stock_quantity']); ?>"
                                            data-cart-id="<?php echo $item['cart_id']; ?>">
                                        <button class="qty-btn qty-increase" data-cart-id="<?php echo $item['cart_id']; ?>">+</button>
                                    </div>
                                    <button class="remove-item" data-product-id="<?php echo $item['product_id']; ?>">
                                        <img src="<?php echo $baseUrl; ?>images/icons/delete-svgrepo-com.svg" width="14" height="14" alt="Remove">
                                        Remove
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Order Summary Section -->
                    <div class="order-summary">
                        <h2>Order Summary</h2>
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span>R <?php echo number_format($cart_totals['subtotal'], 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Delivery Fee</span>
                            <span>R <?php echo number_format($cart_totals['delivery_fee'], 2); ?></span>
                        </div>
                        <div class="summary-total">
                            <span>Total</span>
                            <span>R <?php echo number_format($cart_totals['total'], 2); ?></span>
                        </div>

                        <button class="checkout-btn" id="checkoutBtn">Proceed to Checkout</button>
                        <button class="continue-shopping" id="continueBtn">Continue Shopping</button>

                        <div class="summary-footer">
                            <a href="https://www.payfast.co.za" class="payfast-badge" target="_blank">
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
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        var baseUrl = '<?php echo $baseUrl; ?>';

        // ========== CACHED DOM ELEMENTS ==========
        var $cartItems = null;
        var $qtyIncrease = null;
        var $qtyDecrease = null;
        var $qtyInput = null;
        var $removeItems = null;
        var $checkoutBtn = null;
        var $continueBtn = null;
        var $browseBtn = null;

        // ========== CACHE FUNCTION ==========
        function cacheCartElements() {
            $cartItems = $('#cart-items');
            $qtyIncrease = $('.qty-increase');
            $qtyDecrease = $('.qty-decrease');
            $qtyInput = $('.qty-input');
            $removeItems = $('.remove-item');
            $checkoutBtn = $('#checkoutBtn');
            $continueBtn = $('#continueBtn');
            $browseBtn = $('#browseBtn');
        }

        // ========== UPDATE CART QUANTITY ==========
        function updateCartQuantity(cartId, quantity) {
            $.ajax({
                url: baseUrl + 'php/endpoints/update-cart.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    cart_id: cartId,
                    quantity: quantity
                }),
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Something went wrong. Please try again.');
                }
            });
        }

        // ========== HANDLE QUANTITY INCREASE ==========
        function handleQuantityIncrease() {
            $qtyIncrease.off('click').on('click', function() {
                var $btn = $(this);
                var cartId = $btn.data('cart-id');
                var $input = $qtyInput.filter('[data-cart-id="' + cartId + '"]');
                var current = parseInt($input.val());
                var max = parseInt($input.attr('max'));
                if (!isNaN(current) && current < max) {
                    $input.val(current + 1);
                    updateCartQuantity(cartId, current + 1);
                }
            });
        }

        // ========== HANDLE QUANTITY DECREASE ==========
        function handleQuantityDecrease() {
            $qtyDecrease.off('click').on('click', function() {
                var $btn = $(this);
                var cartId = $btn.data('cart-id');
                var $input = $qtyInput.filter('[data-cart-id="' + cartId + '"]');
                var current = parseInt($input.val());
                if (!isNaN(current) && current > 1) {
                    $input.val(current - 1);
                    updateCartQuantity(cartId, current - 1);
                }
            });
        }

        // ========== HANDLE QUANTITY INPUT CHANGE ==========
        function handleQuantityInputChange() {
            $qtyInput.off('change').on('change', function() {
                var $input = $(this);
                var cartId = $input.data('cart-id');
                var quantity = parseInt($input.val());
                var max = parseInt($input.attr('max'));
                if (isNaN(quantity) || quantity < 1) quantity = 1;
                if (quantity > max) {
                    quantity = max;
                    $input.val(max);
                    alert('Only ' + max + ' available in stock.');
                }
                updateCartQuantity(cartId, quantity);
            });
        }

        // ========== HANDLE REMOVE ITEMS ==========
        function handleRemoveItems() {
            $removeItems.off('click').on('click', function() {
                var $btn = $(this);
                var productId = $btn.data('product-id');
                if (confirm('Remove this item from your cart?')) {
                    removeFromCart(productId);
                }
            });
        }

        // ========== HANDLE NAVIGATION ==========
        function handleNavigation() {
            if ($checkoutBtn.length) {
                $checkoutBtn.off('click').on('click', function() {
                    window.location.href = baseUrl + 'checkout.php';
                });
            }

            if ($continueBtn.length) {
                $continueBtn.off('click').on('click', function() {
                    window.location.href = baseUrl + 'product-listings.php';
                });
            }

            if ($browseBtn.length) {
                $browseBtn.off('click').on('click', function() {
                    window.location.href = baseUrl + 'product-listings.php';
                });
            }
        }

        // ========== INITIALIZE ==========
        $(document).ready(function() {
            cacheCartElements();
            handleQuantityIncrease();
            handleQuantityDecrease();
            handleQuantityInputChange();
            handleRemoveItems();
            handleNavigation();
        });
    </script>
</body>

</html>