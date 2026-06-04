<?php

/**
 * ConsuTrade - Order Status Badge Component
 * Author: Kamogelo Phale
 * 
 * Displays a styled badge for order status
 * 
 * Usage:
 * $status = 'pending'; // or 'processing', 'shipped', 'completed', 'cancelled'
 * include __DIR__ . '/includes/order-status-badge.php';
 * 
 * Or with custom label:
 * $status = 'pending';
 * $statusLabel = 'Awaiting Payment';
 * include __DIR__ . '/includes/order-status-badge.php';
 */

if (!isset($status)) {
    $status = 'pending';
}

$statusLabels = [
    'pending' => 'Pending',
    'processing' => 'Processing',
    'shipped' => 'Shipped',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled'
];

$label = isset($statusLabel) ? $statusLabel : ($statusLabels[$status] ?? ucfirst($status));
?>
<div class="order-status-badge status-<?php echo $status; ?>">
    <?php echo $label; ?>
</div>