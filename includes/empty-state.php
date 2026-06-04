<?php

/**
 * ConsuTrade - Empty State Component
 * Author: Kamogelo Phale
 * 
 * Displays a friendly empty state message with optional action button
 * 
 * Usage:
 * $emptyIcon = 'shopping-cart-01-svgrepo-com.svg';
 * $emptyTitle = 'No orders found';
 * $emptyMessage = 'You haven\'t placed any orders yet.';
 * $emptyButtonText = 'Start Shopping';
 * $emptyButtonLink = 'product-listings.php';
 * include __DIR__ . '/includes/empty-state.php';
 * 
 * Or with custom button class:
 * $emptyButtonClass = 'btn-primary';
 * include __DIR__ . '/includes/empty-state.php';
 */

$emptyIcon = $emptyIcon ?? 'shopping-cart-01-svgrepo-com.svg';
$emptyTitle = $emptyTitle ?? 'No items found';
$emptyMessage = $emptyMessage ?? 'No items match your criteria.';
$emptyButtonText = $emptyButtonText ?? null;
$emptyButtonLink = $emptyButtonLink ?? null;
$emptyButtonClass = $emptyButtonClass ?? 'view-all-btn';
?>
<div class="empty-state">
    <img src="<?php echo $baseUrl; ?>images/icons/<?php echo $emptyIcon; ?>"
        width="64" height="64" alt="<?php echo htmlspecialchars($emptyTitle); ?>">
    <h3><?php echo htmlspecialchars($emptyTitle); ?></h3>
    <p><?php echo htmlspecialchars($emptyMessage); ?></p>
    <?php if ($emptyButtonText && $emptyButtonLink): ?>
        <a href="<?php echo $emptyButtonLink; ?>" class="<?php echo $emptyButtonClass; ?>">
            <?php echo htmlspecialchars($emptyButtonText); ?>
        </a>
    <?php endif; ?>
</div>