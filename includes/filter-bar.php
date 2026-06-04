<?php

/**
 * ConsuTrade - Filter Bar Component
 * Author: Kamogelo Phale
 * 
 * Displays status filter buttons for orders or products
 * 
 * Usage:
 * $currentStatus = $_GET['status'] ?? 'all';
 * $statuses = [
 *     'all' => 'All Orders',
 *     'pending' => 'Pending',
 *     'processing' => 'Processing',
 *     'shipped' => 'Shipped',
 *     'completed' => 'Completed',
 *     'cancelled' => 'Cancelled'
 * ];
 * include __DIR__ . '/includes/filter-bar.php';
 * 
 * For products page:
 * $statuses = [
 *     'all' => 'All Products',
 *     'active' => 'Active',
 *     'suspended' => 'Suspended'
 * ];
 * include __DIR__ . '/includes/filter-bar.php';
 */

$currentStatus = $currentStatus ?? 'all';
$statuses = $statuses ?? [
    'all' => 'All',
    'pending' => 'Pending',
    'processing' => 'Processing',
    'shipped' => 'Shipped',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled'
];
$filterBaseUrl = $filterBaseUrl ?? '';
?>
<div class="status-filters">
    <?php foreach ($statuses as $key => $label): ?>
        <a href="<?php echo $filterBaseUrl; ?>?status=<?php echo urlencode($key); ?>"
            class="filter-btn <?php echo $currentStatus === $key ? 'active' : ''; ?>">
            <?php echo htmlspecialchars($label); ?>
        </a>
    <?php endforeach; ?>
</div>