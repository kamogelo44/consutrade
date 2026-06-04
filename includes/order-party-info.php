<?php

/**
 * ConsuTrade - Order Party Information Component
 * Author: Kamogelo Phale
 * 
 * Displays customer or seller information for an order
 * 
 * Usage (for seller view - shows buyer):
 * $partyType = 'buyer';
 * $partyName = $order['buyer_name'];
 * $partyEmail = $order['buyer_email'] ?? '';
 * $itemCount = $order['item_count'];
 * include __DIR__ . '/includes/order-party-info.php';
 * 
 * Usage (for buyer view - shows seller):
 * $partyType = 'seller';
 * $partyName = $order['seller_name'];
 * $itemCount = $order['item_count'];
 * include __DIR__ . '/includes/order-party-info.php';
 * 
 * Usage (for admin view - shows both):
 * $showBothParties = true;
 * $buyerName = $order['buyer_name'];
 * $sellerName = $order['seller_name'];
 * $itemCount = $order['item_count'];
 * include __DIR__ . '/includes/order-party-info.php';
 */

$partyType = $partyType ?? 'customer';
$partyName = $partyName ?? '';
$partyEmail = $partyEmail ?? '';
$itemCount = $itemCount ?? 0;
$showBothParties = $showBothParties ?? false;
$buyerName = $buyerName ?? '';
$sellerName = $sellerName ?? '';
?>
<div class="customer-info">
    <?php if ($showBothParties): ?>
        <div class="customer-details">
            <span>Customer: <?php echo htmlspecialchars($buyerName); ?></span>
        </div>
        <div class="customer-details">
            <span>Seller: <?php echo htmlspecialchars($sellerName); ?></span>
        </div>
    <?php else: ?>
        <div class="customer-details">
            <span><?php echo ucfirst($partyType); ?>: <?php echo htmlspecialchars($partyName); ?></span>
        </div>
        <?php if (!empty($partyEmail)): ?>
            <div class="customer-details">
                <span>Email: <?php echo htmlspecialchars($partyEmail); ?></span>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    <div class="customer-details">
        <span>Items: <?php echo $itemCount; ?> item(s)</span>
    </div>
</div>