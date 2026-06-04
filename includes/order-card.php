<?php

/**
 * ConsuTrade - Order Card Component
 * Author: Kamogelo Phale
 * 
 * Complete order card for displaying in orders list
 * 
 * Usage (for buyer view):
 * $order = $orderData;
 * $role = 'buyer';
 * $hasReview = $has_review ?? false;
 * $existingRating = $existing_rating ?? 0;
 * $existingComment = $existing_comment ?? '';
 * include __DIR__ . '/includes/order-card.php';
 * 
 * Usage (for seller view):
 * $order = $orderData;
 * $role = 'seller';
 * include __DIR__ . '/includes/order-card.php';
 * 
 * Usage (for admin view):
 * $order = $orderData;
 * $role = 'admin';
 * include __DIR__ . '/includes/order-card.php';
 */

if (!isset($order)) {
    return;
}

$role = $role ?? 'buyer';
$orderId = $order['order_id'];
$orderStatus = $order['status'];
$orderDate = $order['created_at'];
$totalPrice = $order['total_price'];
$itemCount = $order['item_count'];

// Format date
$formattedDate = date('d M Y, h:i A', strtotime($orderDate));
?>
<div class="order-card">
    <div class="order-header">
        <div class="order-info">
            <span class="order-number">Order #<?php echo $orderId; ?></span>
            <span class="order-date"><?php echo $formattedDate; ?></span>
        </div>
        <?php
        $status = $orderStatus;
        include __DIR__ . '/order-status-badge.php';
        ?>
    </div>

    <div class="order-body">
        <?php if ($role === 'seller'): ?>
            <?php
            $partyType = 'buyer';
            $partyName = $order['buyer_name'];
            $partyEmail = $order['buyer_email'] ?? '';
            $itemCount = $itemCount;
            include __DIR__ . '/order-party-info.php';
            ?>
        <?php elseif ($role === 'admin'): ?>
            <?php
            $showBothParties = true;
            $buyerName = $order['buyer_name'];
            $sellerName = $order['seller_name'];
            $itemCount = $itemCount;
            include __DIR__ . '/order-party-info.php';
            ?>
        <?php else: ?>
            <?php
            $partyType = 'seller';
            $partyName = $order['seller_name'];
            $itemCount = $itemCount;
            include __DIR__ . '/order-party-info.php';
            ?>
        <?php endif; ?>

        <?php
        $totalPrice = $totalPrice;
        include __DIR__ . '/order-amount.php';
        ?>
    </div>

    <div class="order-footer">
        <button class="view-details-btn" onclick="openOrderModal(<?php echo $orderId; ?>)">View Details</button>

        <?php if ($role === 'buyer' && $orderStatus === 'pending'): ?>
            <button class="cancel-btn" onclick="cancelBuyerOrder(<?php echo $orderId; ?>)">Cancel Order</button>
        <?php endif; ?>

        <?php if ($role === 'seller'): ?>
            <?php if ($orderStatus === 'pending'): ?>
                <button class="process-btn" onclick="updateOrderStatus(<?php echo $orderId; ?>, 'processing')">Process Order</button>
            <?php elseif ($orderStatus === 'processing'): ?>
                <button class="ship-btn" onclick="updateOrderStatus(<?php echo $orderId; ?>, 'shipped')">Mark as Shipped</button>
            <?php elseif ($orderStatus === 'shipped'): ?>
                <button class="complete-btn" onclick="updateOrderStatus(<?php echo $orderId; ?>, 'completed')">Mark as Completed</button>
            <?php endif; ?>
            <?php if (in_array($orderStatus, ['pending', 'processing'])): ?>
                <button class="cancel-btn" onclick="updateOrderStatus(<?php echo $orderId; ?>, 'cancelled')">Cancel Order</button>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($role === 'admin'): ?>
            <?php if ($orderStatus === 'pending'): ?>
                <button class="process-btn" onclick="updateOrderStatus(<?php echo $orderId; ?>, 'processing')">Process</button>
            <?php elseif ($orderStatus === 'processing'): ?>
                <button class="ship-btn" onclick="updateOrderStatus(<?php echo $orderId; ?>, 'shipped')">Ship</button>
            <?php elseif ($orderStatus === 'shipped'): ?>
                <button class="complete-btn" onclick="updateOrderStatus(<?php echo $orderId; ?>, 'completed')">Complete</button>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($role === 'buyer' && $orderStatus === 'completed'): ?>
            <?php if (isset($hasReview) && $hasReview): ?>
                <button class="review-btn edit-review-btn"
                    data-order-id="<?php echo $orderId; ?>"
                    data-seller-id="<?php echo $order['seller_id']; ?>"
                    data-seller-name="<?php echo addslashes($order['seller_name']); ?>"
                    data-rating="<?php echo $existingRating ?? 0; ?>"
                    data-comment="<?php echo addslashes($existingComment ?? ''); ?>">
                    Edit Review
                </button>
            <?php else: ?>
                <button class="review-btn"
                    data-order-id="<?php echo $orderId; ?>"
                    data-seller-id="<?php echo $order['seller_id']; ?>"
                    data-seller-name="<?php echo addslashes($order['seller_name']); ?>">
                    Leave Review
                </button>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>