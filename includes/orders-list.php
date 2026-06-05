<?php

/**
 * ConsuTrade - Orders List Component
 * Author: Kamogelo Phale
 * 
 * Complete orders list with filtering and search
 * 
 * Usage:
 * $orders = $ordersArray;
 * $role = 'buyer'; // or 'seller' or 'admin'
 * $status_filter = $_GET['status'] ?? 'all';
 * $search_term = $_GET['search'] ?? '';
 * include __DIR__ . '/includes/orders-list.php';
 */

$orders = $orders ?? [];
$role = $role ?? 'buyer';
$status_filter = $status_filter ?? 'all';
$search_term = $search_term ?? '';
$hasSearchTerm = !empty($search_term);
$baseUrl = $baseUrl ?? '';
?>

<div class="filters-bar">
    <div class="status-filters">
        <a href="?status=all" class="filter-btn <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All Orders</a>
        <a href="?status=pending" class="filter-btn <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">Pending</a>
        <a href="?status=processing" class="filter-btn <?php echo $status_filter === 'processing' ? 'active' : ''; ?>">Processing</a>
        <a href="?status=shipped" class="filter-btn <?php echo $status_filter === 'shipped' ? 'active' : ''; ?>">Shipped</a>
        <a href="?status=completed" class="filter-btn <?php echo $status_filter === 'completed' ? 'active' : ''; ?>">Completed</a>
        <a href="?status=cancelled" class="filter-btn <?php echo $status_filter === 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
    </div>

    <div class="search-bar">
        <form method="GET" action="">
            <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
            <input type="text" name="search" placeholder="Search by order number or <?php echo $role === 'seller' ? 'customer...' : 'seller...'; ?>" value="<?php echo htmlspecialchars($search_term); ?>">
            <button type="submit">
                <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" width="16" height="16" alt="Search">
            </button>
            <?php if (!empty($search_term)): ?>
                <a href="?status=<?php echo $status_filter; ?>" class="clear-search">Clear</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="orders-list">
    <?php if (count($orders) > 0): ?>
        <?php foreach ($orders as $order): ?>
            <div class="order-card">
                <div class="order-header">
                    <div class="order-info">
                        <span class="order-number">Order #<?php echo $order['order_id']; ?></span>
                        <span class="order-date"><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></span>
                    </div>
                    <div class="order-status-badge status-<?php echo $order['status']; ?>">
                        <?php echo ucfirst($order['status']); ?>
                    </div>
                </div>

                <div class="order-body">
                    <div class="customer-info">
                        <?php if ($role === 'seller'): ?>
                            <div class="customer-details">
                                <strong>Buyer:</strong> <span><?php echo htmlspecialchars($order['buyer_name']); ?></span>
                            </div>
                            <div class="customer-details">
                                <strong>Email:</strong> <span><?php echo htmlspecialchars($order['buyer_email']); ?></span>
                            </div>
                            <?php if (!empty($order['shipping_address'])): ?>
                                <div class="customer-details">
                                    <strong>Shipping:</strong> <span><?php echo htmlspecialchars($order['shipping_address']); ?></span>
                                </div>
                            <?php endif; ?>
                        <?php elseif ($role === 'buyer'): ?>
                            <div class="customer-details">
                                <strong>Seller:</strong> <span><?php echo htmlspecialchars($order['seller_name']); ?></span>
                            </div>
                            <?php if (!empty($order['seller_location'])): ?>
                                <div class="customer-details">
                                    <strong>Location:</strong> <span><?php echo htmlspecialchars($order['seller_location']); ?></span>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="customer-details">
                                <strong>Buyer:</strong> <span><?php echo htmlspecialchars($order['buyer_name']); ?></span>
                            </div>
                            <div class="customer-details">
                                <strong>Seller:</strong> <span><?php echo htmlspecialchars($order['seller_name']); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="customer-details">
                            <strong>Items:</strong> <span><?php echo $order['item_count']; ?> item(s)</span>
                        </div>
                    </div>

                    <div class="order-amount">
                        <span class="amount-label">Total Amount</span>
                        <span class="amount-value">R <?php echo number_format($order['total_price'], 2); ?></span>
                    </div>
                </div>

                <div class="order-footer">
                    <button class="view-details-btn" onclick="openOrderModal(<?php echo $order['order_id']; ?>)">View Details</button>

                    <?php if ($role === 'seller'): ?>
                        <?php if ($order['status'] === 'pending'): ?>
                            <button class="process-btn" onclick="updateOrderStatus(<?php echo $order['order_id']; ?>, 'processing')">Process Order</button>
                        <?php endif; ?>
                        <?php if ($order['status'] === 'processing'): ?>
                            <button class="ship-btn" onclick="updateOrderStatus(<?php echo $order['order_id']; ?>, 'shipped')">Mark as Shipped</button>
                        <?php endif; ?>
                        <?php if ($order['status'] === 'shipped'): ?>
                            <button class="complete-btn" onclick="updateOrderStatus(<?php echo $order['order_id']; ?>, 'completed')">Mark as Completed</button>
                        <?php endif; ?>
                        <?php if (in_array($order['status'], ['pending', 'processing'])): ?>
                            <button class="cancel-btn" onclick="updateOrderStatus(<?php echo $order['order_id']; ?>, 'cancelled')">Cancel Order</button>
                        <?php endif; ?>
                    <?php elseif ($role === 'buyer'): ?>
                        <?php if ($order['status'] === 'pending' && ($order['can_cancel'] ?? true)): ?>
                            <button class="cancel-btn" onclick="cancelBuyerOrder(<?php echo $order['order_id']; ?>)">Cancel Order</button>
                        <?php endif; ?>
                        <?php if (($order['status'] === 'completed' || $order['status'] === 'cancelled') && !($order['has_review'] ?? false)): ?>
                            <button class="review-btn" data-order-id="<?php echo $order['order_id']; ?>" data-seller-id="<?php echo $order['seller_id']; ?>" data-seller-name="<?php echo htmlspecialchars($order['seller_name']); ?>">Write a Review</button>
                        <?php endif; ?>
                        <?php if (($order['status'] === 'completed' || $order['status'] === 'cancelled') && ($order['has_review'] ?? false)): ?>
                            <button class="edit-review-btn" data-order-id="<?php echo $order['order_id']; ?>" data-seller-id="<?php echo $order['seller_id']; ?>" data-seller-name="<?php echo htmlspecialchars($order['seller_name']); ?>" data-rating="<?php echo $order['review_rating'] ?? 0; ?>" data-comment="<?php echo htmlspecialchars($order['review_comment'] ?? ''); ?>">Edit Review</button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" width="64" height="64" alt="No orders">
            <h3>No Orders Found</h3>
            <p><?php echo $hasSearchTerm ? 'No orders match your search criteria.' : ($role === 'buyer' ? 'You haven\'t placed any orders yet.' : 'You have not received any orders yet.'); ?></p>
            <?php if ($hasSearchTerm): ?>
                <a href="?status=<?php echo $status_filter; ?>" class="view-all-btn">Clear Search</a>
            <?php else: ?>
                <a href="<?php echo $role === 'buyer' ? 'product-listings.php' : ($role === 'seller' ? 'seller-dashboard.php' : 'admin-dashboard.php'); ?>" class="view-all-btn">
                    <?php echo $role === 'buyer' ? 'Start Shopping' : 'Back to Dashboard'; ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>