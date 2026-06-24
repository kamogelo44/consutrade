<?php
/*
 * ConsuTrade - Order Confirmation Page
 * Author: Kamogelo Phale
 * 
 * Shown to customers after successful payment
 */

require_once __DIR__ . '/init.php';
include __DIR__ . '/includes/session-vars.php';

if (!$isLoggedIn) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// Fallback: extract order_id from m_payment_id (format: orderId_userId_timestamp)
if ($order_id == 0 && isset($_GET['m_payment_id'])) {
    $parts = explode('_', $_GET['m_payment_id']);
    $order_id = (int)($parts[0] ?? 0);
}

// Get transaction for this order
$transaction = null;
if ($order_id > 0) {
    $transaction = $transactionRepo->findByOrderId($order_id);
}

$breadcrumbItems = [
    ['label' => 'Order Confirmation']
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - ConsuTrade</title>
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
            background: var(--success-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--spacing-lg) auto;
        }

        .confirmation-icon img {
            width: 48px;
            height: 48px;
            filter: brightness(0) saturate(100%) invert(48%) sepia(96%) saturate(500%);
        }

        .confirmation-title {
            font-size: var(--font-3xl);
            font-weight: var(--font-bold);
            color: var(--success);
            margin-bottom: var(--spacing-md);
        }

        .confirmation-message {
            font-size: var(--font-lg);
            color: var(--dark-bg);
            margin-bottom: var(--spacing-sm);
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

        .confirmation-email {
            font-size: var(--font-sm);
            color: var(--gray-medium);
            margin-bottom: var(--spacing-xl);
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

        .transaction-row .value .status-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: var(--radius-round);
            font-size: var(--font-xs);
            font-weight: var(--font-medium);
        }

        .transaction-row .value .status-badge.completed {
            background: var(--success-light);
            color: var(--success);
        }

        .transaction-row .value .status-badge.pending {
            background: var(--warning-light);
            color: var(--warning);
        }

        .transaction-row .value .status-badge.failed {
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
        }

        .btn-secondary:hover {
            background: var(--primary-fade);
            transform: translateY(-2px);
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

            .transaction-details {
                padding: var(--spacing-sm) var(--spacing-md);
            }

            .transaction-row {
                font-size: var(--font-xs);
                flex-direction: column;
                align-items: center;
                gap: 2px;
                padding: var(--spacing-sm) 0;
            }

            .btn-primary,
            .btn-secondary {
                padding: 10px 20px;
                font-size: var(--font-sm);
                width: 100%;
                text-align: center;
            }

            .confirmation-actions {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <?php include 'includes/breadcrumb.php'; ?>

    <main class="confirmation-wrapper">
        <div class="confirmation-card">
            <div class="confirmation-icon">
                <img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" alt="Confirmed">
            </div>

            <h1 class="confirmation-title">Order Confirmed!</h1>
            <p class="confirmation-message">Thank you for your purchase.</p>
            <p class="confirmation-message" style="font-size: var(--font-md); color: var(--gray-medium);">Your order has been received and is being processed.</p>

            <?php if ($order_id > 0): ?>
                <div class="order-number">Order #<?php echo $order_id; ?></div>
            <?php endif; ?>

            <?php if ($transaction): ?>
                <div class="transaction-details">
                    <h3>💳 Payment Details</h3>
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
            <?php else: ?>
                <div class="transaction-details" style="border-left-color: var(--warning);">
                    <h3>⏳ Payment Processing</h3>
                    <p style="color: var(--gray-medium); font-size: var(--font-sm); margin: 0;">
                        Your payment is being confirmed. You will receive a confirmation email shortly.
                    </p>
                </div>
            <?php endif; ?>

            <p class="confirmation-email">A confirmation email has been sent to your registered email address.</p>

            <div class="confirmation-actions">
                <a href="<?php echo $baseUrl; ?>my-orders.php" class="btn-primary">View My Orders</a>
                <a href="<?php echo $baseUrl; ?>product-listings.php" class="btn-secondary">Continue Shopping</a>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/modal-errors.php'; ?>

    <script>
        $(function() {
            if (typeof updateCartCount === 'function') {
                updateCartCount();
            } else {
                $('.cart-count, .cart-badge, #cart-item-count').text('0');
                if (window.sessionStorage) {
                    sessionStorage.setItem('cart_count', 0);
                }
            }
        });
    </script>
</body>

</html>