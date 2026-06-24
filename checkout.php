<?php
/*
 * ConsuTrade - Checkout Page
 * Author: Kamogelo Phale
 */

require_once __DIR__ . '/init.php';
include __DIR__ . '/includes/session-vars.php';

$breadcrumbItems = [
    ['url' => 'cart.php', 'label' => 'Shopping Cart'],
    ['label' => 'Checkout']
];

if (!isset($_SESSION['checkout_data'])) {
    header('Location: ' . $baseUrl . 'cart.php');
    exit;
}

$data        = $_SESSION['checkout_data'];
$cart_items  = $data['cart_items'];
$subtotal    = $data['subtotal'];
$delivery_fee = $data['delivery_fee'];
$total       = $data['total'];

// Split buyer name for PayFast
$name_parts = explode(' ', $data['buyer_name'], 2);
$first_name = $name_parts[0];
$last_name = isset($name_parts[1]) ? $name_parts[1] : '';

$payfast_data = $cartRepo->preparePayFastData([
    'payment_id' => $data['payment_id'],
    'primary_order_id' => $data['primary_order_id'],
    'total' => $total,
    'buyer_name' => $data['buyer_name'],
    'buyer_email' => $data['buyer_email'],
], $baseUrl);

unset($_SESSION['checkout_data']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <style>
        .checkout-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 var(--spacing-xl) var(--spacing-xl) var(--spacing-xl);
        }

        .checkout-title {
            margin-top: var(--spacing-lg);
            margin-bottom: var(--spacing-xl);
        }

        .checkout-title h1 {
            font-size: var(--font-3xl);
            font-weight: var(--font-bold);
            color: var(--dark-bg);
            margin: 0;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: var(--spacing-xl);
        }

        .checkout-items {
            background: var(--white);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
        }

        .checkout-items h2 {
            font-size: var(--font-xl);
            font-weight: var(--font-bold);
            margin-bottom: var(--spacing-lg);
            padding-bottom: var(--spacing-sm);
            border-bottom: 2px solid var(--primary-color);
            color: var(--dark-bg);
        }

        .cart-item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-md) 0;
            border-bottom: 1px solid var(--border-light);
        }

        .cart-item-row:last-child {
            border-bottom: none;
        }

        .item-details {
            flex: 2;
        }

        .item-title {
            font-size: var(--font-md);
            font-weight: var(--font-semibold);
            color: var(--dark-bg);
            margin-bottom: var(--spacing-xs);
        }

        .item-meta {
            font-size: var(--font-xs);
            color: var(--gray-medium);
        }

        .item-total {
            font-size: var(--font-lg);
            font-weight: var(--font-bold);
            color: var(--primary-color);
            text-align: right;
        }

        .totals {
            margin-top: var(--spacing-lg);
            padding-top: var(--spacing-md);
            border-top: 1px solid var(--border-light);
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: var(--spacing-xs) 0;
            font-size: var(--font-md);
            color: var(--gray-dark);
        }

        .totals-row.grand-total {
            font-size: var(--font-xl);
            font-weight: var(--font-bold);
            color: var(--primary-color);
            margin-top: var(--spacing-sm);
            padding-top: var(--spacing-sm);
            border-top: 1px solid var(--border-light);
        }

        .payment-methods {
            background: var(--white);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
        }

        .payment-methods h2 {
            font-size: var(--font-xl);
            font-weight: var(--font-bold);
            margin-bottom: var(--spacing-lg);
            padding-bottom: var(--spacing-sm);
            border-bottom: 2px solid var(--primary-color);
            color: var(--dark-bg);
        }

        .payment-card {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            padding: var(--spacing-md);
            background: var(--gray-bg-light);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
        }

        .payment-card.active {
            border-color: var(--primary-color);
            background: var(--primary-fade);
        }

        .payment-icon {
            width: 50px;
            height: auto;
        }

        .payment-card p {
            margin: 0;
            font-size: var(--font-sm);
            color: var(--gray-dark);
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
            margin-bottom: var(--spacing-md);
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

        .security-note {
            text-align: center;
            font-size: var(--font-xs);
            color: var(--gray-medium);
            margin-top: var(--spacing-md);
            padding-top: var(--spacing-md);
            border-top: 1px solid var(--border-light);
        }

        .security-note img {
            width: 16px;
            height: 16px;
            vertical-align: middle;
            margin-right: var(--spacing-xs);
            opacity: 0.6;
        }

        .checkout-loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
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

        .loading-box {
            background: var(--white);
            padding: var(--spacing-xl);
            border-radius: var(--radius-lg);
            text-align: center;
            min-width: 200px;
        }

        .loading-box p {
            margin-top: var(--spacing-md);
            color: var(--gray-dark);
            font-size: var(--font-sm);
        }

        @media (max-width: 900px) {
            .checkout-grid {
                grid-template-columns: 1fr;
                gap: var(--spacing-lg);
            }

            .checkout-wrapper {
                padding: 0 var(--spacing-lg) var(--spacing-lg) var(--spacing-lg);
            }
        }

        @media (max-width: 600px) {
            .checkout-wrapper {
                padding: 0 var(--spacing-md) var(--spacing-md) var(--spacing-md);
            }

            .checkout-title h1 {
                font-size: var(--font-2xl);
            }

            .checkout-items,
            .payment-methods {
                padding: var(--spacing-md);
            }

            .cart-item-row {
                flex-direction: column;
                align-items: flex-start;
                gap: var(--spacing-xs);
            }

            .item-total {
                text-align: left;
            }
        }
    </style>
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <?php include 'includes/breadcrumb.php'; ?>

    <main class="checkout-wrapper">
        <div class="checkout-title">
            <h1>Checkout</h1>
        </div>

        <div class="checkout-grid">
            <!-- LEFT COLUMN: Order Items -->
            <div class="checkout-items">
                <h2>Order Summary</h2>

                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item-row">
                        <div class="item-details">
                            <div class="item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                            <div class="item-meta">Quantity: <?php echo $item['quantity']; ?></div>
                        </div>
                        <div class="item-total">R <?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                    </div>
                <?php endforeach; ?>

                <div class="totals">
                    <div class="totals-row">
                        <span>Subtotal</span>
                        <span>R <?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="totals-row">
                        <span>Delivery Fee</span>
                        <span>R <?php echo number_format($delivery_fee, 2); ?></span>
                    </div>
                    <div class="totals-row grand-total">
                        <span>Total</span>
                        <span>R <?php echo number_format($total, 2); ?></span>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: Payment -->
            <div class="payment-methods">
                <h2>Payment Method</h2>

                <div class="payment-card active">
                    <img src="<?php echo $baseUrl; ?>images/icons/Payfast logo.svg" alt="PayFast" class="payment-icon">
                    <p>Secure payment via PayFast<br>Credit cards, debit cards, or instant EFT</p>
                </div>

                <form action="<?php echo PAYFAST_PROCESS_URL; ?>" method="post" id="payfast-form">
                    <input type="hidden" name="merchant_id" value="<?php echo htmlspecialchars($payfast_data['merchant_id']); ?>">
                    <input type="hidden" name="merchant_key" value="<?php echo htmlspecialchars($payfast_data['merchant_key']); ?>">
                    <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($payfast_data['return_url']); ?>">
                    <input type="hidden" name="cancel_url" value="<?php echo htmlspecialchars($payfast_data['cancel_url']); ?>">
                    <input type="hidden" name="notify_url" value="<?php echo htmlspecialchars($payfast_data['notify_url']); ?>">
                    <input type="hidden" name="m_payment_id" value="<?php echo htmlspecialchars($payfast_data['m_payment_id']); ?>">
                    <input type="hidden" name="amount" value="<?php echo htmlspecialchars($payfast_data['amount']); ?>">
                    <input type="hidden" name="item_name" value="<?php echo htmlspecialchars($payfast_data['item_name']); ?>">
                    <input type="hidden" name="item_description" value="<?php echo htmlspecialchars($payfast_data['item_description']); ?>">
                    <input type="hidden" name="name_first" value="<?php echo htmlspecialchars($first_name); ?>">
                    <input type="hidden" name="name_last" value="<?php echo htmlspecialchars($last_name); ?>">
                    <input type="hidden" name="email_address" value="<?php echo htmlspecialchars($payfast_data['email_address']); ?>">
                    <?php if (!empty($data['buyer_phone'])): ?>
                        <input type="hidden" name="cell_number" value="<?php echo htmlspecialchars($data['buyer_phone']); ?>">
                    <?php endif; ?>

                    <button type="submit" class="pay-now-btn" id="payNowBtn">Pay Now with PayFast</button>
                </form>

                <div class="security-note">
                    <img src="<?php echo $baseUrl; ?>images/icons/secure-card-svgrepo-com.svg" alt="Secure">
                    Your payment is encrypted and secure. All transactions are processed by PayFast.
                </div>
            </div>
        </div>
    </main>

    <!-- Loading Overlay -->
    <div class="checkout-loading" id="checkoutLoading">
        <div class="loading-box">
            <div class="loading-spinner"></div>
            <p>Redirecting to PayFast...</p>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/modal-errors.php'; ?>

    <script>
        $(document).ready(function() {
            $('#payfast-form').on('submit', function() {
                $('#payNowBtn').prop('disabled', true);
                $('#checkoutLoading').addClass('active');
                return true;
            });

            window.history.pushState(null, null, window.location.href);
            $(window).on('popstate', function() {
                window.history.pushState(null, null, window.location.href);
            });
        });
    </script>
</body>

</html>