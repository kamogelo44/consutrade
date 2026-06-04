<?php

/**
 * ConsuTrade - Order Amount Component
 * Author: Kamogelo Phale
 * 
 * Displays order total amount with label
 * 
 * Usage:
 * $totalPrice = $order['total_price'];
 * include __DIR__ . '/includes/order-amount.php';
 * 
 * Or with custom label:
 * $amountLabel = 'Grand Total';
 * $totalPrice = 1500.00;
 * include __DIR__ . '/includes/order-amount.php';
 */

$totalPrice = $totalPrice ?? 0;
$amountLabel = $amountLabel ?? 'Total Amount';
$amountClass = $amountClass ?? 'amount-value';
?>
<div class="order-amount">
    <span class="amount-label"><?php echo htmlspecialchars($amountLabel); ?></span>
    <span class="<?php echo $amountClass; ?>">R <?php echo number_format($totalPrice, 2); ?></span>
</div>