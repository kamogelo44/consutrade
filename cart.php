<?php
/*
 * ConsuTrade - Shopping Cart Page
 * Author: Kamogelo Phale
 * 
 * Displays user's cart items with quantity and stock awareness
 * Uses CartRepository for cart data and main.js for rendering
 */

require_once __DIR__ . '/init.php';
include __DIR__ . '/includes/session-vars.php';
include __DIR__ . '/includes/functions.php';

$breadcrumbItems = [
    ['label' => 'Shopping Cart']
];

// Get cart data for initial load (passed to JavaScript)
$cart_items = [];
$cart_totals = ['subtotal' => 0, 'delivery_fee' => 0, 'total' => 0];
$total_quantity = 0;

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
    <meta name="description" content="View and manage your shopping cart items">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <style>
        /* ========== CART PAGE STYLES (Page-specific only) ========== */
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

        /* Cart Item Styles - matches main.js displayCartItems output */
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

        .verified-badge-cart,
        .unverified-badge-cart {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: var(--radius-round);
            font-size: 10px;
        }

        .verified-badge-cart {
            background: var(--success-light);
            border: 1px solid var(--success);
            color: var(--success);
        }

        .unverified-badge-cart {
            background: var(--warning-light);
            border: 1px solid var(--warning);
            color: var(--warning);
        }

        .item-price {
            font-size: var(--font-lg);
            font-weight: var(--font-bold);
            color: var(--primary-color);
        }

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

        .item-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 12px;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--gray-bg);
            border-radius: var(--radius-md);
            padding: 4px;
        }

        .qty-decrease,
        .qty-increase {
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

        .qty-decrease:hover,
        .qty-increase:hover {
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

        .remove-btn {
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

        .remove-btn:hover {
            background: var(--error);
            color: var(--white);
        }

        .remove-btn:hover img {
            filter: brightness(0) invert(1);
        }

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
                margin: 0 auto;
            }

            .item-actions {
                grid-column: 1;
                flex-direction: column;
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

            <!-- Cart layout container - populated by main.js displayCartItems() -->
            <div id="cart-layout" style="display: <?php echo empty($cart_items) ? 'none' : 'flex'; ?>;">
                <div class="cart-grid">
                    <!-- Desktop cart table body -->
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

                    <!-- Mobile cart items container -->
                    <div id="mobile-cart-items"></div>

                    <!-- Order Summary -->
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

            <!-- Empty cart state -->
            <div id="empty-cart" style="display: <?php echo empty($cart_items) ? 'flex' : 'none'; ?>;">
                <div class="empty-state">
                    <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" width="64" height="64" alt="Empty cart">
                    <h3>Your cart is empty</h3>
                    <p>Looks like you haven't added anything yet</p>
                    <button class="view-all-btn" id="browseBtn">Browse Products</button>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Pass cart data to JavaScript for initial rendering
        var initialCartData = {
            items: <?php echo json_encode($cart_items); ?>,
            subtotal: <?php echo json_encode($cart_totals['subtotal']); ?>,
            delivery_fee: <?php echo json_encode($cart_totals['delivery_fee']); ?>,
            total: <?php echo json_encode($cart_totals['total']); ?>
        };

        // Cache DOM elements for better performance
        var $cartLayout = null;
        var $emptyCart = null;
        var $cartItemCount = null;

        function cacheCartPageElements() {
            $cartLayout = $('#cart-layout');
            $emptyCart = $('#empty-cart');
            $cartItemCount = $('#cart-item-count');
        }

        // Update cart count display (uses existing function from footer)
        function updateCartCountFromCache(count) {
            if ($cartItemCount && $cartItemCount.length) {
                $cartItemCount.text(count);
            }
            // Use existing function from main.js/footer
            if (typeof updateCartCountDisplay === 'function') {
                updateCartCountDisplay(count);
            }
            if (window.sessionStorage) {
                sessionStorage.setItem('cart_count', count);
            }
        }

        // Load cart with caching
        function loadCartWithCache() {
            cacheCartPageElements();

            // Try to load from session storage first
            var cachedCart = null;
            if (window.sessionStorage) {
                var cachedCartStr = sessionStorage.getItem('cart_data');
                if (cachedCartStr) {
                    try {
                        cachedCart = JSON.parse(cachedCartStr);
                    } catch (e) {}
                }
            }

            if (cachedCart && cachedCart.items && cachedCart.items.length > 0) {
                // Use existing displayCartItems from main.js
                if (typeof displayCartItems === 'function') {
                    displayCartItems(cachedCart);
                }
                if (typeof updateOrderSummary === 'function') {
                    updateOrderSummary(cachedCart);
                }
                if ($cartLayout && $cartLayout.length) {
                    $cartLayout.css('display', 'flex');
                }
                if ($emptyCart && $emptyCart.length) {
                    $emptyCart.css('display', 'none');
                }
                updateCartCountFromCache(cachedCart.items.length);
            } else if (initialCartData.items && initialCartData.items.length > 0) {
                // Use existing functions from main.js
                if (typeof displayCartItems === 'function') {
                    displayCartItems(initialCartData);
                }
                if (typeof updateOrderSummary === 'function') {
                    updateOrderSummary(initialCartData);
                }
                if ($cartLayout && $cartLayout.length) {
                    $cartLayout.css('display', 'flex');
                }
                if ($emptyCart && $emptyCart.length) {
                    $emptyCart.css('display', 'none');
                }
                updateCartCountFromCache(initialCartData.items.length);

                if (window.sessionStorage) {
                    sessionStorage.setItem('cart_data', JSON.stringify(initialCartData));
                }
            }

            // Silent refresh - uses existing updateCartCount from main.js
            if (typeof updateCartCount === 'function') {
                updateCartCount();
            }

            // Refresh cart data silently
            $.ajax({
                url: baseUrl + 'php/endpoints/get-cart.php',
                type: 'GET',
                dataType: 'json',
                cache: true,
                success: function(data) {
                    if (data.success && data.items) {
                        var currentCount = $cartItemCount ? parseInt($cartItemCount.text()) : 0;
                        if (data.items.length !== currentCount) {
                            if (typeof displayCartItems === 'function') {
                                displayCartItems(data);
                            }
                            if (typeof updateOrderSummary === 'function') {
                                updateOrderSummary(data);
                            }
                            updateCartCountFromCache(data.items.length);
                            if (window.sessionStorage) {
                                sessionStorage.setItem('cart_data', JSON.stringify(data));
                            }
                        }
                    }
                }
            });
        }

        $(document).ready(function() {
            cacheCartPageElements();
            loadCartWithCache();

            // Bind navigation buttons
            $('#checkoutBtn').on('click', function() {
                window.location.href = baseUrl + 'checkout.php';
            });

            $('#continueBtn, #browseBtn').on('click', function() {
                window.location.href = baseUrl + 'product-listings.php';
            });
        });
    </script>
</body>

</html>