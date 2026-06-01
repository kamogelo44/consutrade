<?php
/*
 * ConsuTrade - My Orders (Seller)
 * Author: Kamogelo Phale
 * 
 * Displays all orders for the logged-in seller
 */

require_once dirname(__DIR__) . '/init.php';

if (!$auth->isSellerLoggedIn()) {
    header('Location: login.php');
    exit;
}

$baseUrl = getBaseUrl();
$seller_id = $current_user_id;

$status_filter = $_GET['status'] ?? 'all';
$search_term = $_GET['search'] ?? '';

// Use OrderRepository to get orders
$orders = $orderRepo->getSellerOrders($seller_id, $status_filter, $search_term);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - ConsuTrade Seller</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/sidebar.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <style>
        /* ========== DASHBOARD LAYOUT ========== */
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

        /* Page Header */
        .page-header {
            margin-bottom: var(--spacing-xl);
        }

        .page-header h1 {
            font-size: var(--font-2xl);
            font-weight: var(--font-bold);
            margin-bottom: var(--spacing-xs);
        }

        .page-header p {
            color: var(--gray-medium);
        }

        /* Filters Bar */
        .filters-bar {
            display: flex;
            justify-content: space-between;
            margin-bottom: var(--spacing-lg);
            flex-wrap: wrap;
            gap: var(--spacing-md);
            align-items: center;
        }

        .status-filters {
            display: flex;
            gap: var(--spacing-sm);
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 16px;
            border-radius: var(--radius-md);
            text-decoration: none;
            background: var(--white);
            border: 1px solid var(--border-light);
            color: var(--gray-dark);
            transition: all var(--transition-fast);
        }

        .filter-btn:hover {
            background: var(--primary-fade);
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .filter-btn.active {
            background: var(--primary-color);
            color: var(--white);
            border-color: var(--primary-color);
        }

        /* Search Bar */
        .search-bar form {
            display: flex;
            gap: var(--spacing-sm);
        }

        .search-bar input {
            padding: 8px 12px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            width: 250px;
        }

        .search-bar input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .search-bar button {
            padding: 8px 16px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .clear-search {
            padding: 8px 16px;
            background: var(--gray-bg-light);
            border-radius: var(--radius-md);
            text-decoration: none;
            color: var(--gray-dark);
        }

        /* Orders List */
        .orders-list {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-lg);
        }

        /* Order Card */
        .order-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-light);
            overflow: visible;
            transition: all var(--transition-fast);
        }

        .order-card:hover {
            box-shadow: var(--shadow-md);
        }

        /* Order Header */
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-md);
            background: var(--gray-bg-light);
            border-bottom: 1px solid var(--border-light);
            flex-wrap: wrap;
            gap: var(--spacing-sm);
        }

        .order-number {
            font-weight: var(--font-bold);
            color: var(--primary-color);
            font-size: var(--font-lg);
        }

        .order-date {
            color: var(--gray-medium);
            font-size: var(--font-sm);
            margin-left: var(--spacing-sm);
        }

        /* Order Status Badge */
        .order-status-badge {
            padding: 4px 12px;
            border-radius: var(--radius-round);
            font-size: var(--font-xs);
            font-weight: var(--font-medium);
        }

        .order-status-badge.status-pending {
            background: var(--warning-light);
            color: var(--warning);
        }

        .order-status-badge.status-processing {
            background: var(--info-light);
            color: var(--info);
        }

        .order-status-badge.status-shipped {
            background: var(--primary-fade);
            color: var(--primary-color);
        }

        .order-status-badge.status-completed {
            background: var(--success-light);
            color: var(--success);
        }

        .order-status-badge.status-cancelled {
            background: var(--error-light);
            color: var(--error);
        }

        /* Order Body */
        .order-body {
            padding: var(--spacing-md);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: var(--spacing-md);
        }

        .customer-info {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-sm);
            flex: 2;
        }

        .customer-details {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            font-size: var(--font-sm);
        }

        .order-amount {
            text-align: right;
            flex: 1;
        }

        .amount-label {
            font-size: var(--font-sm);
            color: var(--gray-medium);
            display: block;
            margin-bottom: var(--spacing-xs);
        }

        .amount-value {
            font-size: var(--font-2xl);
            font-weight: var(--font-bold);
            color: var(--primary-color);
        }

        /* Order Footer */
        .order-footer {
            padding: var(--spacing-md);
            border-top: 1px solid var(--border-light);
            display: flex;
            gap: var(--spacing-sm);
            flex-wrap: wrap;
            background: var(--white);
        }

        .order-footer button {
            padding: 10px 20px;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: var(--font-medium);
            font-size: var(--font-sm);
            transition: all var(--transition-fast);
            border: none;
            flex: 0 0 auto;
        }

        .view-details-btn {
            background: var(--info-light);
            color: var(--info);
            border: 1px solid var(--info);
        }

        .view-details-btn:hover {
            background: var(--info);
            color: white;
            transform: translateY(-2px);
        }

        .process-btn {
            background: var(--warning-light);
            color: var(--warning);
            border: 1px solid var(--warning);
        }

        .process-btn:hover {
            background: var(--warning);
            color: white;
            transform: translateY(-2px);
        }

        .ship-btn {
            background: var(--primary-fade);
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }

        .ship-btn:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .complete-btn {
            background: var(--success-light);
            color: var(--success);
            border: 1px solid var(--success);
        }

        .complete-btn:hover {
            background: var(--success);
            color: white;
            transform: translateY(-2px);
        }

        .cancel-btn {
            background: var(--error-light);
            color: var(--error);
            border: 1px solid var(--error);
        }

        .cancel-btn:hover {
            background: var(--error);
            color: white;
            transform: translateY(-2px);
        }

        /* Empty State */
        .empty-orders {
            text-align: center;
            padding: 60px var(--spacing-xl);
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-light);
        }

        .empty-orders img {
            opacity: 0.5;
            margin-bottom: var(--spacing-lg);
        }

        .empty-orders h3 {
            font-size: var(--font-xl);
            font-weight: var(--font-bold);
            margin-bottom: var(--spacing-sm);
        }

        .empty-orders p {
            color: var(--gray-medium);
            margin-bottom: var(--spacing-lg);
        }

        .clear-btn,
        .back-btn {
            display: inline-block;
            padding: 10px 24px;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: var(--radius-md);
        }

        /* ========== ORDER MODAL STYLES ========== */
        .order-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: var(--z-modal);
            justify-content: center;
            align-items: center;
        }

        .order-modal.active {
            display: flex;
        }

        .order-modal-content {
            background: var(--white);
            border-radius: var(--radius-lg);
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .order-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-md) var(--spacing-lg);
            border-bottom: 1px solid var(--border-light);
        }

        .order-modal-header h2 {
            font-size: var(--font-xl);
            font-weight: var(--font-bold);
            margin: 0;
        }

        .order-modal-close {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: var(--gray-light);
            line-height: 1;
            padding: 0;
            width: 30px;
            height: 30px;
        }

        .order-modal-close:hover {
            color: var(--error);
        }

        .order-details-content {
            padding: var(--spacing-lg);
        }

        .order-modal-footer {
            padding: var(--spacing-lg);
            border-top: 1px solid var(--border-light);
            display: flex;
            gap: var(--spacing-md);
            justify-content: flex-end;
            background: var(--white);
            border-radius: 0 0 var(--radius-lg) var(--radius-lg);
        }

        /* Order Items Styles */
        .order-info-section {
            margin-bottom: var(--spacing-lg);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: var(--spacing-xs) 0;
            border-bottom: 1px solid var(--border-light);
        }

        .info-label {
            font-weight: var(--font-medium);
            color: var(--gray-dark);
        }

        .info-value {
            color: var(--gray-medium);
        }

        .order-items-list {
            margin: var(--spacing-lg) 0;
        }

        .order-item {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            padding: var(--spacing-sm) 0;
            border-bottom: 1px solid var(--border-light);
        }

        .order-item-img {
            width: 60px;
            height: 60px;
            background: var(--gray-bg);
            border-radius: var(--radius-md);
            overflow: hidden;
        }

        .order-item-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .order-item-details {
            flex: 1;
        }

        .order-item-details h4 {
            font-size: var(--font-sm);
            font-weight: var(--font-medium);
            margin-bottom: var(--spacing-xs);
        }

        .order-item-price {
            font-weight: var(--font-bold);
            color: var(--primary-color);
        }

        .order-total-section {
            margin-top: var(--spacing-lg);
            padding-top: var(--spacing-md);
            border-top: 2px solid var(--border-light);
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: var(--spacing-xs) 0;
        }

        .grand-total {
            font-weight: var(--font-bold);
            font-size: var(--font-lg);
            color: var(--dark-bg);
        }

        .status-pending {
            color: var(--warning);
        }

        .status-processing {
            color: var(--info);
        }

        .status-shipped {
            color: var(--primary-color);
        }

        .status-completed {
            color: var(--success);
        }

        .status-cancelled {
            color: var(--error);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .seller-main-content {
                margin-left: 0;
                width: 100%;
                padding: var(--spacing-md);
                padding-top: 70px;
            }

            .order-body {
                flex-wrap: wrap;
            }

            .customer-info {
                flex: 1;
            }
        }

        @media (max-width: 768px) {
            .seller-main-content {
                padding: var(--spacing-md);
                padding-top: 70px;
            }

            .filters-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .status-filters {
                justify-content: center;
            }

            .search-bar form {
                width: 100%;
            }

            .search-bar input {
                flex: 1;
            }

            .order-body {
                flex-direction: column;
                align-items: flex-start;
            }

            .customer-info {
                width: 100%;
            }

            .order-amount {
                text-align: left;
                width: 100%;
                margin-top: var(--spacing-sm);
                padding-top: var(--spacing-sm);
                border-top: 1px dashed var(--border-light);
            }

            .order-footer {
                justify-content: center;
            }

            .order-modal-content {
                width: 95%;
            }

            .info-row {
                flex-direction: column;
                gap: var(--spacing-xs);
            }
        }

        @media (max-width: 480px) {
            .seller-main-content {
                padding: var(--spacing-sm);
                padding-top: 60px;
            }

            .order-footer {
                flex-direction: column;
            }

            .order-footer button {
                width: 100%;
            }

            .amount-value {
                font-size: var(--font-xl);
            }

            .order-header {
                flex-direction: column;
                text-align: center;
            }

            .order-modal-footer {
                flex-direction: column;
            }

            .order-modal-footer button {
                width: 100%;
            }

            .order-item {
                flex-wrap: wrap;
            }

            .order-item-price {
                width: 100%;
                margin-top: var(--spacing-xs);
                text-align: right;
            }
        }
    </style>
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <main class="seller-main-content">
        <div class="dashboard-content">
            <div class="page-header">
                <h1>My Orders</h1>
                <p>Manage and track all customer orders</p>
            </div>

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
                        <input type="text" name="search" placeholder="Search by order number or customer..." value="<?php echo htmlspecialchars($search_term); ?>">
                        <button type="submit"><img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" width="16" height="16" alt="Search"> Search</button>
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
                                <div>
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
                                        <img src="<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg" width="18" height="18" alt="Customer">
                                        <span><?php echo htmlspecialchars($order['buyer_name']); ?></span>
                                    </div>
                                    <div class="customer-details">
                                        <img src="<?php echo $baseUrl; ?>images/icons/email-svgrepo-com.svg" width="18" height="18" alt="Email">
                                        <span><?php echo htmlspecialchars($order['buyer_email']); ?></span>
                                    </div>
                                    <div class="customer-details">
                                        <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" width="18" height="18" alt="Items">
                                        <span><?php echo $order['item_count']; ?> item(s)</span>
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
                    <div class="empty-orders">
                        <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" width="64" height="64" alt="No orders">
                        <h3>No Orders Found</h3>
                        <p><?php echo !empty($search_term) ? 'No orders match your search criteria.' : 'You have not received any orders yet.' ?></p>
                        <?php if (!empty($search_term)): ?>
                            <a href="?status=<?php echo $status_filter; ?>" class="clear-btn">Clear Search</a>
                        <?php else: ?>
                            <a href="seller-dashboard.php" class="back-btn">Back to Dashboard</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Order Details Modal -->
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

    <script src="<?php echo $baseUrl; ?>admin/js/dashboard.js"></script>
</body>

</html>