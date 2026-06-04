<?php

/**
 * Reusable Order Status Badge Component
 * 
 * Usage:
 * include __DIR__ . '/includes/order-status-badge.php';
 * 
 * Expects: $status (string: pending, processing, shipped, completed, cancelled)
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

$label = $statusLabels[$status] ?? ucfirst($status);
?>
<div class="order-status-badge status-<?php echo $status; ?>">
    <?php echo $label; ?>
</div>