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
        /* ========== CONFIRMATION PAGE STYLES ========== */

        /* Main container */
        .confirmation-wrapper {
            max-width: 600px;
            margin: 0 auto;
            padding: 0 var(--spacing-xl) var(--spacing-xl) var(--spacing-xl);
            min-height: 60vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Confirmation card */
        .confirmation-card {
            background: var(--white);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: var(--spacing-xl);
            text-align: center;
            box-shadow: var(--shadow-md);
            width: 100%;
        }

        /* Success icon */
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

        /* Title */
        .confirmation-title {
            font-size: var(--font-3xl);
            font-weight: var(--font-bold);
            color: var(--success);
            margin-bottom: var(--spacing-md);
        }

        /* Message text */
        .confirmation-message {
            font-size: var(--font-lg);
            color: var(--dark-bg);
            margin-bottom: var(--spacing-sm);
        }

        /* Order number */
        .order-number {
            font-size: var(--font-xl);
            font-weight: var(--font-bold);
            color: var(--primary-color);
            margin: var(--spacing-md) 0;
            padding: var(--spacing-sm);
            background: var(--gray-bg-light);
            border-radius: var(--radius-md);
            display: inline-block;
        }

        /* Email note */
        .confirmation-email {
            font-size: var(--font-sm);
            color: var(--gray-medium);
            margin-bottom: var(--spacing-xl);
        }

        /* Action buttons */
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

        /* ===== RESPONSIVE ===== */
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

            .btn-primary,
            .btn-secondary {
                padding: 10px 20px;
                font-size: var(--font-sm);
            }
        }
    </style>
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <!-- Breadcrumb -->
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
            // Clear cart count from session and UI
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