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
    <title>Shopping Cart - ConsuTrade</title>
    <meta name="description" content="View and manage your shopping cart items">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <style>
        /* ============================================
           CART PAGE STYLES - Matches JS output exactly
           ============================================ */

        .cart-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: var(--spacing-xl);
            min-height: 400px;
        }

        .cart-header {
            text-align: center;
            margin-bottom: var(--spacing-2xl);
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
            gap: var(--spacing-xl);
            align-items: start;
        }

        /* ===== DESKTOP TABLE STYLES ===== */
        .table-wrapper {
            width: 100%;
            overflow-x: auto;
            overflow-y: visible;
            -webkit-overflow-scrolling: touch;
        }

        .cart-table {
            width: 100%;
            min-width: 700px;
            border-collapse: collapse;
            background: var(--white);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .cart-table th {
            text-align: left;
            padding: var(--spacing-md);
            background: var(--gray-bg);
            font-weight: var(--font-semibold);
            color: var(--gray-dark);
            border-bottom: 1px solid var(--border-light);
            font-size: var(--font-md);
        }

        .cart-table td {
            padding: var(--spacing-md);
            border-bottom: 1px solid var(--border-light);
            vertical-align: middle;
        }

        .cart-product-wrapper {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }

        .cart-img-container {
            width: 70px;
            height: 70px;
            flex-shrink: 0;
            background: var(--gray-bg);
            border-radius: var(--radius-md);
            overflow: hidden;
        }

        .cart-img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .cart-prod-info {
            flex: 1;
        }

        .cart-prod-info .prod-name {
            font-size: var(--font-md);
            font-weight: var(--font-semibold);
            color: var(--dark-bg);
            margin: 0;
            line-height: 1.4;
        }

        .seller-cart-info {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .seller-cart-info .seller-name {
            font-size: var(--font-sm);
            color: var(--gray-dark);
            margin: 0;
        }

        .verification {
            display: flex;
        }

        .verified-badge-cart,
        .unverified-badge-cart {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: var(--radius-round);
            font-size: 10px;
            width: fit-content;
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

        .price-cell {
            font-size: var(--font-lg);
            font-weight: var(--font-bold);
            color: var(--primary-color);
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            background: var(--gray-bg);
            border-radius: var(--radius-md);
            padding: 4px;
            width: fit-content;
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
            font-weight: var(--font-bold);
            transition: all var(--transition-fast);
        }

        .qty-decrease:hover,
        .qty-increase:hover {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: var(--white);
        }

        .qty-input {
            width: 50px;
            height: 32px;
            text-align: center;
            border: none;
            background: transparent;
            font-size: var(--font-md);
        }

        .stock-warning {
            display: block;
            font-size: var(--font-xs);
            color: var(--warning);
            margin-top: 4px;
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

        /* ===== MOBILE CARD STYLES ===== */
        #mobile-cart-items {
            display: none;
        }

        .cart-card {
            background: var(--white);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: var(--spacing-md);
            margin-bottom: var(--spacing-md);
            box-shadow: var(--shadow-sm);
        }

        .cart-card-header {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-md);
            padding-bottom: var(--spacing-sm);
            border-bottom: 1px solid var(--border-light);
        }

        .cart-card-img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: var(--radius-md);
            background: var(--gray-bg);
        }

        .cart-card-header h4 {
            font-size: var(--font-md);
            font-weight: var(--font-semibold);
            color: var(--dark-bg);
            margin: 0 0 4px 0;
        }

        .cart-card-header .seller-name {
            font-size: var(--font-sm);
            color: var(--gray-dark);
            margin: 0 0 6px 0;
        }

        .cart-card-body {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: var(--spacing-md);
        }

        .cart-card-price {
            font-size: var(--font-lg);
            font-weight: var(--font-bold);
            color: var(--primary-color);
        }

        .cart-card-body .quantity-controls {
            margin: 0;
        }

        .cart-card-body .remove-btn {
            padding: 6px 12px;
        }

        /* ===== ORDER SUMMARY ===== */
        .order-summary {
            background: var(--gray-bg);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            position: sticky;
            top: 100px;
        }

        .order-summary h2 {
            font-size: var(--font-xl);
            font-weight: var(--font-bold);
            margin-bottom: var(--spacing-lg);
            padding-bottom: var(--spacing-sm);
            border-bottom: 1px solid var(--border-light);
            text-align: center;
            color: var(--dark-bg);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: var(--spacing-sm) 0;
            color: var(--gray-medium);
            font-size: var(--font-md);
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            padding: var(--spacing-md) 0;
            border-top: 1px solid var(--border-light);
            font-size: var(--font-xl);
            font-weight: var(--font-bold);
            color: var(--primary-color);
            margin-bottom: var(--spacing-md);
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
            margin-top: var(--spacing-md);
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
            margin-top: var(--spacing-sm);
            transition: all var(--transition-normal);
        }

        .continue-shopping:hover {
            background: var(--primary-color);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .summary-footer {
            margin-top: var(--spacing-lg);
            padding-top: var(--spacing-md);
            border-top: 1px solid var(--border-light);
        }

        .payfast-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-sm);
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
            margin-top: var(--spacing-sm);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .cart-container {
                padding: var(--spacing-md);
            }

            .cart-grid {
                grid-template-columns: 1fr;
                gap: var(--spacing-lg);
            }

            .order-summary {
                position: static;
            }

            .cart-table {
                display: none;
            }

            #mobile-cart-items {
                display: block;
            }
        }

        @media (max-width: 640px) {
            .cart-card-body {
                flex-direction: column;
                align-items: stretch;
            }

            .cart-card-body .quantity-controls {
                justify-content: center;
            }

            .cart-card-body .remove-btn {
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .cart-container {
                padding: var(--spacing-sm);
            }

            .cart-header h1 {
                font-size: var(--font-xl);
            }

            .cart-card-header {
                flex-direction: column;
                text-align: center;
            }

            .cart-card-header .verification {
                justify-content: center;
            }

            .cart-card-body {
                flex-direction: column;
                align-items: center;
                gap: var(--spacing-sm);
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
        // Cart page specific data only - global vars already in footer.php
        var initialCartData = {
            items: <?php echo json_encode($cart_items); ?>,
            subtotal: <?php echo json_encode($cart_totals['subtotal']); ?>,
            delivery_fee: <?php echo json_encode($cart_totals['delivery_fee']); ?>,
            total: <?php echo json_encode($cart_totals['total']); ?>
        };

        function loadCart() {
            var $cartLayout = $('#cart-layout');
            var $emptyCart = $('#empty-cart');
            var $cartItemCount = $('#cart-item-count');

            if (initialCartData.items && initialCartData.items.length > 0) {
                if (typeof displayCartItems === 'function') displayCartItems(initialCartData);
                if (typeof updateOrderSummary === 'function') updateOrderSummary(initialCartData);
                $cartLayout.css('display', 'flex');
                $emptyCart.css('display', 'none');
                $cartItemCount.text(initialCartData.items.length);
            } else {
                $cartLayout.css('display', 'none');
                $emptyCart.css('display', 'flex');
                $cartItemCount.text('0');
            }
        }

        $(document).ready(function() {
            loadCart();

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