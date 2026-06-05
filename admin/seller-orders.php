<?php
/*
 * ConsuTrade - Seller Orders Page
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__) . '/init.php';
include dirname(__DIR__) . '/includes/session-vars.php';
include dirname(__DIR__) . '/includes/functions.php';

if (!$auth->isSeller()) {
    header('Location: login.php');
    exit;
}

$seller_id = $currentUser->getUserId();

$status_filter = $_GET['status'] ?? 'all';
$search_term = $_GET['search'] ?? '';

$orders = $orderRepo->getSellerOrders($seller_id, $status_filter, $search_term);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Seller Orders - ConsuTrade</title>

    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/sidebar.css">

    <style>
        .seller-main-content {
            margin-left: 280px;
            padding: var(--spacing-xl);
            min-height: 100vh;
            background: var(--gray-bg);
            transition: margin-left var(--transition-normal);
        }

        .dashboard-content {
            max-width: 1400px;
            margin: 0 auto;
        }

        .orders-list {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-lg);
        }

        @media (max-width: 1024px) {
            .seller-main-content {
                margin-left: 0;
                width: 100%;
                padding: var(--spacing-md);
                padding-top: 70px;
            }
        }

        @media (max-width: 768px) {
            .seller-main-content {
                padding: var(--spacing-md);
                padding-top: 70px;
            }
        }

        @media (max-width: 480px) {
            .seller-main-content {
                padding: var(--spacing-sm);
                padding-top: 60px;
            }
        }
    </style>
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <main class="seller-main-content">
        <div class="dashboard-content">
            <div class="page-header">
                <h1>Seller Orders</h1>
                <p>Manage and track all customer orders</p>
            </div>

            <div class="filters-bar">
                <?php
                $currentStatus = $status_filter;
                $statuses = [
                    'all' => 'All Orders',
                    'pending' => 'Pending',
                    'processing' => 'Processing',
                    'shipped' => 'Shipped',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled'
                ];
                $filterBaseUrl = '';
                include dirname(__DIR__) . '/includes/filter-bar.php';
                ?>

                <div class="search-bar">
                    <form method="GET" action="">
                        <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                        <input type="text" name="search" placeholder="Search by order number or customer..." value="<?php echo htmlspecialchars($search_term); ?>">
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
                                    <div class="customer-details">
                                        <span>Buyer: <?php echo htmlspecialchars($order['buyer_name']); ?></span>
                                    </div>
                                    <div class="customer-details">
                                        <span>Email: <?php echo htmlspecialchars($order['buyer_email']); ?></span>
                                    </div>
                                    <div class="customer-details">
                                        <span>Items: <?php echo $order['item_count']; ?> item(s)</span>
                                    </div>
                                </div>

                                <div class="order-amount">
                                    <span class="amount-label">Total Amount</span>
                                    <span class="amount-value">R <?php echo number_format($order['total_price'], 2); ?></span>
                                </div>
                            </div>

                            <div class="order-footer">
                                <button class="view-details-btn" onclick="openOrderModal(<?php echo $order['order_id']; ?>)">View Details</button>
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
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" width="64" height="64" alt="No orders">
                        <h3>No Orders Found</h3>
                        <p><?php echo !empty($search_term) ? 'No orders match your search criteria.' : 'You have not received any orders yet.'; ?></p>
                        <?php if (!empty($search_term)): ?>
                            <a href="?status=<?php echo $status_filter; ?>" class="view-all-btn">Clear Search</a>
                        <?php else: ?>
                            <a href="seller-dashboard.php" class="view-all-btn">Back to Dashboard</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <div id="orderModal" class="order-modal">
        <div class="order-modal-content">
            <div class="order-modal-header">
                <h2>Order Details</h2>
                <button class="order-modal-close" onclick="closeOrderModal()">&times;</button>
            </div>
            <div id="orderModalBody" class="order-details-content">
                <div class="loading-spinner">Loading order details...</div>
            </div>
            <div id="orderModalFooter" class="order-modal-footer"></div>
        </div>
    </div>

</body>

</html>