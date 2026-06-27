<?php
require_once __DIR__ . '/init.php';
include __DIR__ . '/includes/session-vars.php';

// ============================================================
// Check if we have payment data from PayFast
// ============================================================
$hasPaymentData = !empty($_GET['payment_status']) || !empty($_GET['m_payment_id']) || !empty($_GET['order_id']);

$isSuccess = false;
$errorMessage = $_SESSION['payment_error'] ?? 'Payment issue';
$orderId = 0;
$order = null;
$transaction = null;
$pageTitle = 'Payment Status';

// ============================================================
// Try 1: Check by payment_id from URL
// ============================================================
if (!empty($_GET['m_payment_id'])) {
    $paymentId = $_GET['m_payment_id'];
    error_log("order-confirmation: looking for payment_id: $paymentId");
    $orderByPayment = $orderRepo->findByPaymentId($paymentId);
    if ($orderByPayment) {
        $orderId = $orderByPayment['order_id'];
        $userId = $currentUser->getUserId();
        $order = $orderService->findById($orderId, $userId, 'buyer');
        if ($order && $order['status'] !== 'pending') {
            $isSuccess = true;
            $transaction = $transactionRepo->findByOrderId($orderId);
            $pageTitle = 'Order Confirmed';
            unset($_SESSION['payment_error']);
            goto show_confirmation;
        }
    }
}

// ============================================================
// Try 2: Check by session checkout_data
// ============================================================
if (!$isSuccess && isset($_SESSION['checkout_data'])) {
    $orderId = $_SESSION['checkout_data']['primary_order_id'] ?? 0;
    $userId = $currentUser->getUserId();

    if ($orderId > 0) {
        error_log("order-confirmation: checking session order_id: $orderId");
        $order = $orderService->findById($orderId, $userId, 'buyer');
        if ($order && $order['status'] !== 'pending') {
            $isSuccess = true;
            $transaction = $transactionRepo->findByOrderId($orderId);
            $pageTitle = 'Order Confirmed';
            unset($_SESSION['checkout_data']);
            unset($_SESSION['payment_error']);
            goto show_confirmation;
        }
    }
}

// ============================================================
// Try 3: If we have checkout_data but order is still pending
// ============================================================
if (isset($_SESSION['checkout_data']) && !$isSuccess) {
    $orderId = $_SESSION['checkout_data']['primary_order_id'] ?? 0;
    if ($orderId > 0) {
        $errorMessage = 'Your payment is being processed. Please wait a moment...';
        goto show_confirmation;
    }
}

// ============================================================
// Fallback: No data found
// ============================================================
if (!$hasPaymentData) {
    header('Location: ' . $baseUrl . 'cart.php');
    exit;
}

// If we have payment data, get the status
$data = include __DIR__ . '/php/endpoints/checkout/get-payment-status.php';

if (!empty($data['redirect'])) {
    $redirectUrl = $data['redirect_url'] ?? ($data['url'] ?? $baseUrl . 'cart.php');
    header('Location: ' . $redirectUrl);
    exit;
}

$isSuccess = $data['success'] ?? false;
$errorMessage = $_SESSION['payment_error'] ?? $data['message'] ?? 'Payment issue';
$orderId = $data['order_id'] ?? ($data['order']['order_id'] ?? 0);
$order = $data['order'] ?? null;
$transaction = $data['transaction'] ?? null;
$pageTitle = $isSuccess ? 'Order Confirmed' : 'Payment Status';

unset($_SESSION['payment_error']);

show_confirmation:

$breadcrumbItems = [['label' => $pageTitle]];
$showRedirect = false;
$redirectDelay = 10;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <style>
        .confirmation-wrapper {
            max-width: 600px;
            margin: 0 auto;
            padding: 0 var(--spacing-xl) var(--spacing-xl) var(--spacing-xl);
            min-height: 60vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .confirmation-card {
            background: var(--white);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: var(--spacing-xl);
            text-align: center;
            box-shadow: var(--shadow-md);
            width: 100%;
        }

        .confirmation-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--spacing-lg) auto;
        }

        .confirmation-icon.success {
            background: var(--success-light);
        }

        .confirmation-icon.error {
            background: var(--error-light);
        }

        .confirmation-icon img {
            width: 48px;
            height: 48px;
        }

        .confirmation-title {
            font-size: var(--font-3xl);
            font-weight: var(--font-bold);
            margin-bottom: var(--spacing-md);
        }

        .confirmation-title.success {
            color: var(--success);
        }

        .confirmation-title.error {
            color: var(--error);
        }

        .confirmation-message {
            font-size: var(--font-lg);
            color: var(--dark-bg);
            margin-bottom: var(--spacing-sm);
            line-height: 1.6;
        }

        .confirmation-sub-message {
            font-size: var(--font-md);
            color: var(--gray-medium);
            margin-bottom: var(--spacing-md);
        }

        .order-number {
            font-size: var(--font-xl);
            font-weight: var(--font-bold);
            color: var(--primary-color);
            margin: var(--spacing-md) 0;
            padding: var(--spacing-sm) var(--spacing-lg);
            background: var(--gray-bg-light);
            border-radius: var(--radius-md);
            display: inline-block;
        }

        .transaction-details {
            text-align: left;
            margin: var(--spacing-lg) 0;
            padding: var(--spacing-md) var(--spacing-lg);
            background: var(--gray-bg-light);
            border-radius: var(--radius-md);
            border-left: 4px solid var(--success);
        }

        .transaction-details h3 {
            font-size: var(--font-md);
            font-weight: var(--font-semibold);
            margin-bottom: var(--spacing-md);
            color: var(--dark-bg);
            text-align: center;
        }

        .transaction-row {
            display: flex;
            justify-content: space-between;
            padding: var(--spacing-xs) 0;
            border-bottom: 1px solid var(--border-light);
            font-size: var(--font-sm);
        }

        .transaction-row:last-child {
            border-bottom: none;
        }

        .transaction-row .label {
            color: var(--gray-medium);
        }

        .transaction-row .value {
            font-weight: var(--font-medium);
            color: var(--dark-bg);
        }

        .status-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: var(--radius-round);
            font-size: var(--font-xs);
            font-weight: var(--font-medium);
        }

        .status-badge.completed {
            background: var(--success-light);
            color: var(--success);
        }

        .status-badge.processing {
            background: var(--info-light);
            color: var(--info);
        }

        .status-badge.pending {
            background: var(--warning-light);
            color: var(--warning);
        }

        .status-badge.failed {
            background: var(--error-light);
            color: var(--error);
        }

        .confirmation-actions {
            display: flex;
            gap: var(--spacing-md);
            justify-content: center;
            flex-wrap: wrap;
            margin-top: var(--spacing-md);
        }

        .btn-primary {
            display: inline-block;
            padding: 12px 28px;
            background: var(--primary-color);
            color: var(--white);
            text-decoration: none;
            border-radius: var(--radius-md);
            font-weight: var(--font-bold);
            transition: all var(--transition-fast);
            border: none;
            cursor: pointer;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-secondary {
            display: inline-block;
            padding: 12px 28px;
            background: transparent;
            color: var(--primary-color);
            text-decoration: none;
            border-radius: var(--radius-md);
            font-weight: var(--font-bold);
            border: 2px solid var(--primary-color);
            transition: all var(--transition-fast);
            cursor: pointer;
        }

        .btn-secondary:hover {
            background: var(--primary-fade);
            transform: translateY(-2px);
        }

        .error-details {
            background: var(--error-light);
            border: 1px solid var(--error);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            margin: var(--spacing-md) 0;
            color: var(--error-dark);
            font-size: var(--font-sm);
            text-align: left;
        }

        .error-details strong {
            display: block;
            margin-bottom: var(--spacing-xs);
        }

        .error-details ul {
            margin: 0;
            padding-left: var(--spacing-md);
        }

        .redirect-countdown {
            margin-top: var(--spacing-lg);
            padding-top: var(--spacing-md);
            border-top: 1px solid var(--border-light);
            font-size: var(--font-sm);
            color: var(--gray-medium);
        }

        .redirect-countdown .timer {
            font-weight: var(--font-bold);
            color: var(--primary-color);
        }

        @media (max-width: 600px) {
            .confirmation-wrapper {
                padding: 0 var(--spacing-md) var(--spacing-md) var(--spacing-md);
            }

            .confirmation-card {
                padding: var(--spacing-lg);
            }

            .confirmation-title {
                font-size: var(--font-2xl);
            }

            .confirmation-message {
                font-size: var(--font-md);
            }

            .order-number {
                font-size: var(--font-lg);
            }

            .confirmation-actions {
                flex-direction: column;
            }

            .btn-primary,
            .btn-secondary {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <?php include 'includes/breadcrumb.php'; ?>

    <main class="confirmation-wrapper">
        <div class="confirmation-card">
            <?php if ($isSuccess): ?>
                <!-- SUCCESS STATE -->
                <div class="confirmation-icon success">
                    <img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo.svg" alt="Success">
                </div>

                <h1 class="confirmation-title success">Order Confirmed!</h1>
                <p class="confirmation-message">Thank you for your purchase.</p>
                <p class="confirmation-sub-message">Your order has been received and is being processed.</p>

                <?php if ($orderId > 0): ?>
                    <div class="order-number">Order #<?php echo $orderId; ?></div>
                <?php endif; ?>

                <?php if ($transaction): ?>
                    <div class="transaction-details">
                        <h3>Payment Details</h3>
                        <div class="transaction-row">
                            <span class="label">Transaction Reference</span>
                            <span class="value"><?php echo htmlspecialchars($transaction->getPayfastRef()); ?></span>
                        </div>
                        <div class="transaction-row">
                            <span class="label">Payment Status</span>
                            <span class="value">
                                <span class="status-badge <?php echo $transaction->getStatus(); ?>">
                                    <?php echo ucfirst($transaction->getStatus()); ?>
                                </span>
                            </span>
                        </div>
                        <div class="transaction-row">
                            <span class="label">Amount Paid</span>
                            <span class="value">R <?php echo number_format($transaction->getAmount(), 2); ?></span>
                        </div>
                        <div class="transaction-row">
                            <span class="label">Paid On</span>
                            <span class="value"><?php echo date('d M Y, h:i A', strtotime($transaction->getPaidAt())); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="confirmation-actions">
                    <a href="<?php echo $baseUrl; ?>my-orders.php" class="btn-primary">View My Orders</a>
                    <a href="<?php echo $baseUrl; ?>product-listings.php" class="btn-secondary">Continue Shopping</a>
                </div>

            <?php else: ?>
                <!-- ERROR/PENDING STATE -->
                <div class="confirmation-icon error">
                    <img src="<?php echo $baseUrl; ?>images/icons/not-verified-svgrepo.svg" alt="Error">
                </div>

                <h1 class="confirmation-title error">Payment Issue</h1>
                <p class="confirmation-message" style="color: var(--error);">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </p>

                <div class="error-details">
                    <strong>What can I do?</strong>
                    <ul>
                        <li>Check your <a href="<?php echo $baseUrl; ?>my-orders.php">orders page</a> - your order may have been created</li>
                        <li>Try checking out again from your <a href="<?php echo $baseUrl; ?>cart.php">cart</a></li>
                        <li>Contact support if the issue persists</li>
                    </ul>
                </div>

                <?php if ($showRedirect): ?>
                    <div class="redirect-countdown" id="countdownContainer">
                        <p>You will be redirected to your cart in <span class="timer"><span id="countdownTimer"><?php echo $redirectDelay; ?></span>s</span></p>
                        <button class="btn-primary" id="redirectNowBtn" style="background: var(--error); margin-top: var(--spacing-sm);">Go to Cart Now</button>
                    </div>
                <?php endif; ?>

                <div class="confirmation-actions">
                    <a href="<?php echo $baseUrl; ?>cart.php" class="btn-primary">Return to Cart</a>
                    <a href="<?php echo $baseUrl; ?>my-orders.php" class="btn-secondary">View My Orders</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/modal-errors.php'; ?>

    <?php if ($showRedirect): ?>
        <script>
            (function() {
                var delay = <?php echo $redirectDelay; ?>;
                var timerElement = document.getElementById('countdownTimer');
                var redirectUrl = '<?php echo $baseUrl; ?>cart.php';

                function redirectNow() {
                    window.location.href = redirectUrl;
                }

                var interval = setInterval(function() {
                    delay--;
                    if (timerElement) {
                        timerElement.textContent = delay;
                    }
                    if (delay <= 0) {
                        clearInterval(interval);
                        redirectNow();
                    }
                }, 1000);

                var redirectBtn = document.getElementById('redirectNowBtn');
                if (redirectBtn) {
                    redirectBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        clearInterval(interval);
                        redirectNow();
                    });
                }
            })();
        </script>
    <?php endif; ?>
</body>

</html>