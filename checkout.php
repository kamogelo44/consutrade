<?php
/*
 * ConsuTrade - Checkout Page
 * Author: Kamogelo Phale
 * 
 * Displays order summary and PayFast payment form.
 * Checkout processing is handled by php/endpoints/place-order.php
 */

require_once __DIR__ . '/init.php';
include __DIR__ . '/includes/session-vars.php';

// Define PayFast constants if not already defined
if (!defined('PAYFAST_PROCESS_URL')) {
    define('PAYFAST_PROCESS_URL', 'https://sandbox.payfast.co.za/eng/process');
}

$breadcrumbItems = [
    ['url' => 'cart.php', 'label' => 'Shopping Cart'],
    ['label' => 'Checkout']
];

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
    <style>
        /* ========== CHECKOUT PAGE STYLES ========== */
        .checkout-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: var(--spacing-xl);
        }

        .checkout-container h1 {
            font-size: var(--font-3xl);
            font-weight: var(--font-bold);
            color: var(--dark-bg);
            margin-bottom: var(--spacing-xl);
            padding-bottom: var(--spacing-sm);
            border-bottom: 2px solid var(--primary-color);
            display: inline-block;
        }

        .checkout-layout {
            display: grid;
            grid-template-columns: 1fr 450px;
            gap: var(--spacing-xl);
            margin-top: var(--spacing-lg);
        }

        /* Order Summary */
        .order-summary {
            background: var(--white);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            box-shadow: var(--shadow-sm);
        }

        .order-summary h2 {
            font-size: var(--font-xl);
            font-weight: var(--font-bold);
            margin-bottom: var(--spacing-lg);
            padding-bottom: var(--spacing-sm);
            border-bottom: 1px solid var(--border-light);
            color: var(--dark-bg);
        }

        .checkout-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-sm) 0;
            border-bottom: 1px solid var(--border-light);
        }

        .item-info {
            display: flex;
            gap: var(--spacing-sm);
            align-items: baseline;
            flex-wrap: wrap;
        }

        .item-name {
            font-size: var(--font-md);
            color: var(--gray-dark);
        }

        .item-quantity {
            font-size: var(--font-sm);
            color: var(--gray-medium);
        }

        .item-price {
            font-weight: var(--font-bold);
            color: var(--primary-color);
        }

        .order-totals {
            margin-top: var(--spacing-lg);
            padding-top: var(--spacing-md);
            border-top: 1px solid var(--border-light);
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: var(--spacing-xs) 0;
            font-size: var(--font-md);
            color: var(--gray-medium);
        }

        .grand-total {
            font-size: var(--font-xl);
            font-weight: var(--font-bold);
            color: var(--primary-color);
            margin-top: var(--spacing-sm);
            padding-top: var(--spacing-sm);
            border-top: 1px solid var(--border-light);
        }

        /* Payment Section */
        .payment-section {
            background: var(--white);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            box-shadow: var(--shadow-sm);
        }

        .payment-section h2 {
            font-size: var(--font-xl);
            font-weight: var(--font-bold);
            margin-bottom: var(--spacing-lg);
            padding-bottom: var(--spacing-sm);
            border-bottom: 1px solid var(--border-light);
            color: var(--dark-bg);
        }

        .payment-method {
            margin-bottom: var(--spacing-lg);
        }

        .payment-option {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            padding: var(--spacing-md);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            background: var(--gray-bg-light);
        }

        .payment-option.active {
            border-color: var(--primary-color);
            background: var(--primary-fade);
        }

        .payment-option img {
            height: 30px;
            width: auto;
        }

        .payment-option p {
            margin: 0;
            color: var(--gray-dark);
            font-size: var(--font-sm);
        }

        .pay-now-btn {
            width: 100%;
            padding: 14px;
            background: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: var(--radius-md);
            font-size: var(--font-md);
            font-weight: var(--font-bold);
            cursor: pointer;
            transition: all var(--transition-normal);
            margin-top: var(--spacing-md);
        }

        .pay-now-btn:hover:not(:disabled) {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .pay-now-btn:disabled {
            background: var(--gray-light);
            cursor: not-allowed;
            transform: none;
        }

        .security-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-sm);
            margin-top: var(--spacing-lg);
            padding-top: var(--spacing-md);
            border-top: 1px solid var(--border-light);
            font-size: var(--font-sm);
            color: var(--gray-medium);
        }

        .security-badge img {
            width: 20px;
            height: 20px;
            opacity: 0.6;
        }

        /* Loading overlay */
        .checkout-loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            visibility: hidden;
            opacity: 0;
            transition: all var(--transition-fast);
        }

        .checkout-loading.active {
            visibility: visible;
            opacity: 1;
        }

        .loading-content {
            background: var(--white);
            padding: var(--spacing-xl);
            border-radius: var(--radius-lg);
            text-align: center;
        }

        .loading-spinner-small {
            width: 40px;
            height: 40px;
            border: 3px solid var(--border-light);
            border-top-color: var(--primary-color);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto var(--spacing-md);
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .checkout-container {
                padding: var(--spacing-lg);
            }

            .checkout-layout {
                grid-template-columns: 1fr;
                gap: var(--spacing-lg);
            }

            .checkout-container h1 {
                font-size: var(--font-2xl);
            }
        }

        @media (max-width: 480px) {
            .checkout-container {
                padding: var(--spacing-md);
            }

            .checkout-container h1 {
                font-size: var(--font-xl);
            }

            .order-summary,
            .payment-section {
                padding: var(--spacing-md);
            }

            .item-name {
                font-size: var(--font-sm);
            }
        }
    </style>
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <main class="checkout-container">
        <?php include 'includes/breadcrumb.php'; ?>
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
                        <img src="<?php echo $baseUrl; ?>images/icons/Payfast logo.svg" alt="PayFast">
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

                    <button type="submit" class="pay-now-btn" id="payNowBtn">Pay Now with PayFast</button>
                </form>

                <div class="security-badge">
                    <img src="<?php echo $baseUrl; ?>images/icons/secure-card-svgrepo-com.svg" alt="Secure">
                    <span>Your payment is secure. All transactions are encrypted.</span>
                </div>
            </div>
        </div>
    </main>

    <!-- Loading Overlay -->
    <div class="checkout-loading" id="checkoutLoading">
        <div class="loading-content">
            <div class="loading-spinner-small"></div>
            <p>Redirecting to PayFast...</p>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/modal-errors.php'; ?>

    <script>
        var baseUrl = '<?php echo $baseUrl; ?>';

        // ========== CACHED DOM ELEMENTS ==========
        var $payfastForm = null;
        var $payNowBtn = null;
        var $loadingOverlay = null;

        // ========== CACHE FUNCTION ==========
        function cacheCheckoutElements() {
            $payfastForm = $('#payfast-form');
            $payNowBtn = $('#payNowBtn');
            $loadingOverlay = $('#checkoutLoading');
        }

        // ========== SHOW LOADING STATE ==========
        function showLoading() {
            if ($loadingOverlay) {
                $loadingOverlay.addClass('active');
            }
        }

        // ========== HANDLE FORM SUBMIT ==========
        function handleFormSubmit() {
            $payfastForm.off('submit').on('submit', function(e) {
                // Disable button to prevent double submission
                if ($payNowBtn) {
                    $payNowBtn.prop('disabled', true);
                }

                // Show loading overlay
                showLoading();

                // Allow form to submit naturally
                return true;
            });
        }

        // ========== PREVENT BACK BUTTON AFTER CHECKOUT ==========
        function preventBackAfterCheckout() {
            window.history.pushState(null, null, window.location.href);
            $(window).on('popstate', function() {
                window.history.pushState(null, null, window.location.href);
            });
        }

        // ========== INITIALIZE ==========
        $(document).ready(function() {
            cacheCheckoutElements();
            handleFormSubmit();
            preventBackAfterCheckout();
        });
    </script>
</body>

</html>