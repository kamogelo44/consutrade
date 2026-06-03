<?php
/*
 * ConsuTrade - My Orders (Buyer)
 * Author: Kamogelo Phale
 */

require_once __DIR__ . '/init.php';

// Check if user is logged in and is a buyer
if (!$isLoggedIn || !$currentUser instanceof Buyer) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

// Set breadcrumb
$breadcrumbItems = [
    ['url' => 'profile.php', 'label' => 'My Profile'],
    ['label' => 'My Orders']
];

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search_term = $_GET['search'] ?? '';

// Get orders using Buyer class method
$orders = $currentUser->getOrders($status_filter, $search_term);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <style>
        /* ========== MY ORDERS PAGE – PRODUCTION STYLES ========== */
        /* All base styles come from main.css / style.css – only page‑specific overrides below */

        /* ----- Page Header (ensures styling even if base h1/p are reset) ----- */
        .page-header {
            margin-bottom: var(--spacing-xl);
        }

        .page-header h1 {
            font-size: var(--font-3xl);
            font-weight: var(--font-bold);
            color: var(--dark-bg);
            margin: 0 0 var(--spacing-xs) 0;
            line-height: 1.2;
            font-family: inherit;
        }

        .page-header p {
            font-size: var(--font-md);
            color: var(--gray-medium);
            margin: 0;
            line-height: 1.4;
        }

        /* ----- Filters Bar ----- */
        .filters-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-2xl);
            padding-bottom: var(--spacing-md);
            border-bottom: 1px solid var(--border-light);
        }

        .status-filters {
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-sm);
        }

        .filter-btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: var(--radius-md);
            font-size: var(--font-sm);
            font-weight: var(--font-medium);
            background: var(--white);
            border: 1px solid var(--border-light);
            color: var(--gray-dark);
            text-decoration: none;
            transition: all var(--transition-fast);
        }

        .filter-btn:hover {
            background: var(--primary-fade);
            border-color: var(--primary-color);
        }

        .filter-btn.active {
            background: var(--primary-color);
            color: var(--white);
            border-color: var(--primary-color);
        }

        /* ----- Search Bar ----- */
        .search-bar {
            flex-shrink: 0;
        }

        .search-bar form {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .search-bar input[type="text"] {
            padding: 8px 12px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            font-size: var(--font-sm);
            width: 250px;
            transition: border-color var(--transition-fast);
        }

        .search-bar input[type="text"]:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .search-bar button {
            background: var(--primary-color);
            border: none;
            padding: 8px 12px;
            border-radius: var(--radius-md);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background var(--transition-fast);
        }

        .search-bar button:hover {
            background: var(--primary-dark);
        }

        .search-bar button img {
            filter: brightness(0) invert(1);
        }

        .clear-search {
            color: var(--error);
            font-size: var(--font-sm);
            text-decoration: none;
            margin-left: var(--spacing-sm);
            white-space: nowrap;
        }

        /* ----- Orders List & Cards ----- */
        .orders-list {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-lg);
        }

        .order-card {
            background: var(--white);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            overflow: hidden;
            transition: box-shadow var(--transition-fast);
        }

        .order-card:hover {
            box-shadow: var(--shadow-md);
            border-color: var(--primary-light);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: var(--spacing-sm);
            padding: var(--spacing-md) var(--spacing-lg);
            background: var(--gray-bg);
            border-bottom: 1px solid var(--border-light);
        }

        .order-info {
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-lg);
        }

        .order-number {
            font-weight: var(--font-bold);
            color: var(--dark-bg);
        }

        .order-date {
            color: var(--gray-medium);
            font-size: var(--font-sm);
        }

        .order-status-badge {
            padding: 4px 12px;
            border-radius: var(--radius-round);
            font-size: var(--font-xs);
            font-weight: var(--font-bold);
            text-transform: uppercase;
        }

        .order-status-badge.status-pending {
            background: var(--warning-light);
            color: var(--warning);
        }

        .order-status-badge.status-processing,
        .order-status-badge.status-shipped {
            background: var(--info-light);
            color: var(--info);
        }

        .order-status-badge.status-completed {
            background: var(--success-light);
            color: var(--success);
        }

        .order-status-badge.status-cancelled {
            background: var(--error-light);
            color: var(--error);
        }

        .order-body {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: var(--spacing-md);
            padding: var(--spacing-lg);
        }

        .seller-info {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-sm);
            flex: 2;
        }

        .seller-details,
        .product-details {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .seller-details span,
        .product-details span {
            color: var(--gray-dark);
            font-size: var(--font-sm);
        }

        .item-details span {
            color: var(--gray-medium);
            font-size: var(--font-xs);
        }

        .order-amount {
            text-align: right;
            flex: 1;
        }

        .amount-label {
            display: block;
            font-size: var(--font-xs);
            color: var(--gray-medium);
            margin-bottom: var(--spacing-xs);
        }

        .amount-value {
            display: block;
            font-size: var(--font-xl);
            font-weight: var(--font-bold);
            color: var(--primary-color);
        }

        .order-footer {
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-md);
            padding: var(--spacing-md) var(--spacing-lg);
            background: var(--gray-bg);
            border-top: 1px solid var(--border-light);
        }

        .order-footer button {
            padding: 8px 20px;
            border-radius: var(--radius-md);
            font-size: var(--font-sm);
            font-weight: var(--font-medium);
            cursor: pointer;
            border: none;
            transition: all var(--transition-fast);
        }

        .view-details-btn {
            background: var(--white);
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }

        .view-details-btn:hover {
            background: var(--primary-fade);
            transform: translateY(-2px);
        }

        .cancel-btn {
            background: var(--error);
            color: var(--white);
        }

        .cancel-btn:hover {
            background: var(--error-dark);
            transform: translateY(-2px);
        }

        .review-btn {
            background: var(--success);
            color: var(--white);
        }

        .review-btn:hover {
            background: var(--success-dark);
            transform: translateY(-2px);
        }

        .edit-review-btn {
            background: var(--info);
            color: var(--white);
        }

        .edit-review-btn:hover {
            background: var(--info-dark);
            transform: translateY(-2px);
        }

        /* ----- Modals (Order & Review) ----- */
        .order-modal,
        .review-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .order-modal.active,
        .review-modal.active {
            display: flex;
        }

        .order-modal-content,
        .review-modal-content {
            background: var(--white);
            border-radius: var(--radius-lg);
            max-width: 600px;
            width: 90%;
            max-height: 85vh;
            overflow-y: auto;
        }

        .review-modal-content {
            max-width: 500px;
        }

        .order-modal-header,
        .review-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-md) var(--spacing-lg);
            border-bottom: 1px solid var(--border-light);
        }

        .order-modal-header h2,
        .review-modal-header h2 {
            font-size: var(--font-xl);
            font-weight: var(--font-bold);
            margin: 0;
        }

        .order-modal-close,
        .review-modal-close {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: var(--gray-light);
            line-height: 1;
            padding: 0;
            width: 30px;
            height: 30px;
            transition: color var(--transition-fast);
        }

        .order-modal-close:hover,
        .review-modal-close:hover {
            color: var(--error);
        }

        .order-details-content {
            padding: var(--spacing-lg);
        }

        .order-modal-footer {
            padding: var(--spacing-lg);
            border-top: 1px solid var(--border-light);
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-md);
            justify-content: flex-end;
        }

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
            flex-shrink: 0;
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

        /* ----- Review Form (modal) ----- */
        .review-form-container {
            padding: var(--spacing-lg);
        }

        .review-info {
            margin-bottom: var(--spacing-md);
            color: var(--gray-dark);
            font-size: var(--font-sm);
        }

        .form-group {
            margin-bottom: var(--spacing-lg);
        }

        .form-group label {
            display: block;
            font-weight: var(--font-medium);
            margin-bottom: var(--spacing-sm);
            color: var(--gray-dark);
        }

        .rating-stars {
            display: flex;
            gap: var(--spacing-xs);
            margin-top: var(--spacing-xs);
        }

        .rating-stars .star {
            font-size: 32px;
            color: #ddd;
            cursor: pointer;
            transition: transform var(--transition-fast);
        }

        .rating-stars .star:hover {
            transform: scale(1.1);
        }

        .rating-stars .star.active {
            color: #ffc107;
        }

        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            font-size: var(--font-sm);
            font-family: inherit;
            resize: vertical;
            box-sizing: border-box;
            transition: border-color var(--transition-fast);
        }

        textarea:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .submit-review-btn {
            width: 100%;
            padding: 12px;
            background: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: var(--radius-md);
            font-weight: var(--font-bold);
            font-size: var(--font-md);
            cursor: pointer;
            transition: all var(--transition-fast);
            margin-top: var(--spacing-sm);
        }

        .submit-review-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 768px) {
            .orders-container {
                padding: var(--spacing-lg);
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

            .search-bar input[type="text"] {
                flex: 1;
                width: auto;
            }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .order-body {
                flex-direction: column;
                align-items: flex-start;
            }

            .order-amount {
                text-align: left;
                width: 100%;
            }

            .order-footer {
                flex-direction: column;
            }

            .order-footer button {
                width: 100%;
            }

            .info-row {
                flex-direction: column;
                gap: var(--spacing-xs);
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

        @media (max-width: 480px) {
            .orders-container {
                padding: var(--spacing-md);
            }

            .filter-btn {
                padding: 6px 12px;
                font-size: var(--font-xs);
            }

            .amount-value {
                font-size: var(--font-lg);
            }

            .order-modal-header h2,
            .review-modal-header h2 {
                font-size: var(--font-lg);
            }

            .rating-stars .star {
                font-size: 28px;
            }

            .order-modal-footer {
                flex-direction: column;
            }

            .order-modal-footer button {
                width: 100%;
            }

            .review-form-container {
                padding: var(--spacing-md);
            }
        }
    </style>
</head>

<body class="my-orders-page">

    <?php include 'includes/header.php'; ?>

    <main>
        <?php include 'includes/breadcrumb.php'; ?>

        <div class="orders-container">
            <div class="page-header">
                <h1>My Orders</h1>
                <p>Track and manage your purchase history</p>
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
                        <input type="text" name="search" placeholder="Search by order # or seller..." value="<?php echo htmlspecialchars($search_term); ?>">
                        <button type="submit">
                            <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" width="18" height="18" alt="Search">
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
                        <?php
                        $existing_review = $reviewRepo->getReviewByOrderAndBuyer($order['order_id'], $currentUser->getUserId());
                        $has_review = $existing_review !== null;
                        $existing_rating = $has_review ? $existing_review['rating'] : 0;
                        $existing_comment = $has_review ? addslashes($existing_review['comment']) : '';
                        ?>
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
                                <div class="seller-info">
                                    <div class="seller-details">
                                        <img src="<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg" width="20" height="20" alt="Seller">
                                        <span>Seller: <?php echo htmlspecialchars($order['seller_name']); ?></span>
                                    </div>
                                    <div class="product-details">
                                        <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" width="20" height="20" alt="Product">
                                        <span><?php echo htmlspecialchars($order['product_names']); ?></span>
                                    </div>
                                    <div class="item-details">
                                        <span><?php echo $order['item_count']; ?> item(s)</span>
                                    </div>
                                </div>

                                <div class="order-amount">
                                    <span class="amount-label">Total Amount</span>
                                    <span class="amount-value">R <?php echo number_format($order['total_price'], 2); ?></span>
                                </div>
                            </div>

                            <div class="order-footer">
                                <button class="view-details-btn" data-order-id="<?php echo $order['order_id']; ?>">View Details</button>
                                <?php if ($order['status'] === 'pending'): ?>
                                    <button class="cancel-btn" data-order-id="<?php echo $order['order_id']; ?>">Cancel Order</button>
                                <?php endif; ?>
                                <?php if ($order['status'] === 'completed'): ?>
                                    <?php if ($has_review): ?>
                                        <button class="review-btn edit-review-btn" data-order-id="<?php echo $order['order_id']; ?>" data-seller-id="<?php echo $order['seller_id']; ?>" data-seller-name="<?php echo addslashes($order['seller_name']); ?>" data-rating="<?php echo $existing_rating; ?>" data-comment="<?php echo $existing_comment; ?>">Edit Review</button>
                                    <?php else: ?>
                                        <button class="review-btn" data-order-id="<?php echo $order['order_id']; ?>" data-seller-id="<?php echo $order['seller_id']; ?>" data-seller-name="<?php echo addslashes($order['seller_name']); ?>">Leave Review</button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" width="64" height="64" alt="No orders">
                        <h3>No Orders Found</h3>
                        <p><?php echo !empty($search_term) ? 'No orders match your search criteria.' : 'You haven\'t placed any orders yet.'; ?></p>
                        <?php if (!empty($search_term)): ?>
                            <a href="?status=<?php echo $status_filter; ?>" class="clear-btn">Clear Search</a>
                        <?php else: ?>
                            <a href="product-listings.php" class="shop-btn">Start Shopping</a>
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
                <button class="order-modal-close">&times;</button>
            </div>
            <div id="orderModalBody" class="order-details-content">
                <div class="loading-spinner">Loading order details...</div>
            </div>
            <div id="orderModalFooter" class="order-modal-footer"></div>
        </div>
    </div>

    <!-- Review Modal -->
    <div id="reviewModal" class="review-modal">
        <div class="review-modal-content">
            <div class="review-modal-header">
                <h2 id="reviewModalTitle">Review Seller</h2>
                <button class="review-modal-close">&times;</button>
            </div>
            <div class="review-form-container">
                <p class="review-info">Rate your experience with <strong id="reviewSellerName"></strong></p>
                <form id="reviewForm">
                    <input type="hidden" id="reviewOrderId" name="order_id">
                    <input type="hidden" id="reviewSellerId" name="seller_id">
                    <input type="hidden" id="isEditMode" name="is_edit_mode" value="0">

                    <div class="form-group">
                        <label>Rating</label>
                        <div class="rating-stars">
                            <span class="star" data-rating="1">★</span>
                            <span class="star" data-rating="2">★</span>
                            <span class="star" data-rating="3">★</span>
                            <span class="star" data-rating="4">★</span>
                            <span class="star" data-rating="5">★</span>
                        </div>
                        <input type="hidden" id="reviewRating" name="rating" value="0">
                    </div>

                    <div class="form-group">
                        <label for="reviewComment">Your Review</label>
                        <textarea id="reviewComment" name="comment" rows="4" placeholder="Share your experience with this seller..."></textarea>
                    </div>

                    <button type="submit" class="submit-review-btn" id="submitReviewBtn">Submit Review</button>
                </form>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        var baseUrl = '<?php echo $baseUrl; ?>';

        // ========== CACHED DOM ELEMENTS ==========
        var $orderModal = null;
        var $orderModalBody = null;
        var $orderModalFooter = null;
        var $reviewModal = null;
        var $reviewForm = null;
        var $reviewRating = null;
        var $ratingStars = null;
        var $reviewComment = null;
        var $reviewOrderId = null;
        var $reviewSellerId = null;
        var $reviewSellerName = null;
        var $isEditMode = null;
        var $submitReviewBtn = null;
        var $viewDetailsBtns = null;
        var $cancelBtns = null;
        var $reviewBtns = null;
        var $editReviewBtns = null;

        function cacheMyOrdersElements() {
            $orderModal = $('#orderModal');
            $orderModalBody = $('#orderModalBody');
            $orderModalFooter = $('#orderModalFooter');
            $reviewModal = $('#reviewModal');
            $reviewForm = $('#reviewForm');
            $reviewRating = $('#reviewRating');
            $ratingStars = $('.rating-stars .star');
            $reviewComment = $('#reviewComment');
            $reviewOrderId = $('#reviewOrderId');
            $reviewSellerId = $('#reviewSellerId');
            $reviewSellerName = $('#reviewSellerName');
            $isEditMode = $('#isEditMode');
            $submitReviewBtn = $('#submitReviewBtn');
            $viewDetailsBtns = $('.view-details-btn');
            $cancelBtns = $('.cancel-btn');
            $reviewBtns = $('.review-btn:not(.edit-review-btn)');
            $editReviewBtns = $('.edit-review-btn');
        }

        function cancelOrder(orderId) {
            if (confirm('Are you sure you want to cancel this order?')) {
                $.ajax({
                    url: baseUrl + 'php/endpoints/cancel-order.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        order_id: orderId
                    }),
                    success: function(data) {
                        if (data.success) {
                            showSuccessToast('Order cancelled');
                            location.reload();
                        } else {
                            showErrorToast(data.message);
                        }
                    },
                    error: function() {
                        showErrorToast('Something went wrong');
                    }
                });
            }
        }

        function openOrderModal(orderId) {
            $orderModal.addClass('active');
            $orderModalBody.html('<div class="loading-spinner">Loading order details...</div>');
            $orderModalFooter.empty();

            $.ajax({
                url: baseUrl + 'php/endpoints/get-order-details.php?order_id=' + orderId,
                success: function(data) {
                    if (data.success && data.order) {
                        displayOrderDetails(data.order);
                    } else {
                        $orderModalBody.html('<p class="error">Unable to load order details.</p>');
                    }
                },
                error: function() {
                    $orderModalBody.html('<p class="error">Error loading order details.</p>');
                }
            });
        }

        function closeOrderModal() {
            $orderModal.removeClass('active');
        }

        function displayOrderDetails(order) {
            var itemsHtml = '';
            for (var i = 0; i < (order.items || []).length; i++) {
                var item = order.items[i];
                itemsHtml += '<div class="order-item">' +
                    '<div class="order-item-img"><img src="' + fixImageUrl(item.image_url) + '" onerror="this.src=\'' + baseUrl + 'images/default-product.png\'"></div>' +
                    '<div class="order-item-details"><h4>' + escapeHtml(item.product_name) + '</h4><p>Quantity: ' + item.quantity + '</p></div>' +
                    '<div class="order-item-price">R ' + parseFloat(item.price).toFixed(2) + '</div>' +
                    '</div>';
            }

            $orderModalBody.html(
                '<div class="order-info-section">' +
                '<div class="info-row"><span class="info-label">Order Number:</span><span class="info-value">#' + order.order_id + '</span></div>' +
                '<div class="info-row"><span class="info-label">Order Date:</span><span class="info-value">' + order.created_at + '</span></div>' +
                '<div class="info-row"><span class="info-label">Order Status:</span><span class="info-value status-' + order.status + '">' + (order.status ? order.status.toUpperCase() : 'UNKNOWN') + '</span></div>' +
                '<div class="info-row"><span class="info-label">Seller:</span><span class="info-value">' + escapeHtml(order.seller_name) + '</span></div>' +
                (order.shipping_address ? '<div class="info-row"><span class="info-label">Shipping Address:</span><span class="info-value">' + escapeHtml(order.shipping_address) + '</span></div>' : '') +
                '</div>' +
                '<h4>Order Items</h4><div class="order-items-list">' + (itemsHtml || '<p>No items found.</p>') + '</div>' +
                '<div class="order-total-section">' +
                '<div class="total-row"><span>Subtotal:</span><span>R ' + parseFloat(order.subtotal || 0).toFixed(2) + '</span></div>' +
                '<div class="total-row"><span>Delivery Fee:</span><span>R ' + parseFloat(order.delivery_fee || 0).toFixed(2) + '</span></div>' +
                '<div class="total-row grand-total"><span>Total:</span><span>R ' + parseFloat(order.total || 0).toFixed(2) + '</span></div>' +
                '</div>'
            );

            if (order.status === 'pending') {
                $orderModalFooter.html('<button class="cancel-btn" onclick="cancelOrder(' + order.order_id + ')">Cancel Order</button>');
            }
        }

        function resetRatingStars() {
            $reviewRating.val(0);
            $ratingStars.removeClass('active');
        }

        function setRating(rating) {
            $reviewRating.val(rating);
            $ratingStars.each(function(i) {
                $(this).toggleClass('active', i < rating);
            });
        }

        function openReviewModal(orderId, sellerId, sellerName) {
            $isEditMode.val('0');
            $submitReviewBtn.text('Submit Review');
            $reviewOrderId.val(orderId);
            $reviewSellerId.val(sellerId);
            $reviewSellerName.text(sellerName);
            $reviewComment.val('');
            resetRatingStars();
            $reviewModal.addClass('active');
        }

        function openEditReviewModal(orderId, sellerId, sellerName, rating, comment) {
            $isEditMode.val('1');
            $submitReviewBtn.text('Update Review');
            $reviewOrderId.val(orderId);
            $reviewSellerId.val(sellerId);
            $reviewSellerName.text(sellerName);
            $reviewComment.val(comment);
            setRating(rating);
            $reviewModal.addClass('active');
        }

        function closeReviewModal() {
            $reviewModal.removeClass('active');
            resetRatingStars();
        }

        function handleViewDetails() {
            $viewDetailsBtns.off('click').on('click', function() {
                openOrderModal($(this).data('order-id'));
            });
        }

        function handleCancelButtons() {
            $cancelBtns.off('click').on('click', function() {
                cancelOrder($(this).data('order-id'));
            });
        }

        function handleReviewButtons() {
            $reviewBtns.off('click').on('click', function() {
                openReviewModal($(this).data('order-id'), $(this).data('seller-id'), $(this).data('seller-name'));
            });
            $editReviewBtns.off('click').on('click', function() {
                openEditReviewModal($(this).data('order-id'), $(this).data('seller-id'), $(this).data('seller-name'), $(this).data('rating'), $(this).data('comment'));
            });
        }

        function handleReviewSubmit() {
            $reviewForm.off('submit').on('submit', function(e) {
                e.preventDefault();
                var rating = $reviewRating.val();
                if (rating == 0) {
                    alert('Please select a rating');
                    return;
                }
                var isEdit = $isEditMode.val() === '1';
                $.ajax({
                    url: baseUrl + (isEdit ? 'php/endpoints/update-review.php' : 'php/endpoints/submit-review.php'),
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        order_id: $reviewOrderId.val(),
                        seller_id: $reviewSellerId.val(),
                        rating: rating,
                        comment: $reviewComment.val()
                    }),
                    success: function(data) {
                        if (data.success) {
                            showSuccessToast(isEdit ? 'Review updated!' : 'Thank you for your review!');
                            closeReviewModal();
                            location.reload();
                        } else {
                            showErrorToast(data.message);
                        }
                    },
                    error: function() {
                        showErrorToast('Something went wrong');
                    }
                });
            });
        }

        function handleModalClicks() {
            $('.order-modal-close, .review-modal-close').off('click').on('click', function() {
                closeOrderModal();
                closeReviewModal();
            });
            $orderModal.off('click').on('click', function(e) {
                if ($(e.target).is($orderModal)) closeOrderModal();
            });
            $reviewModal.off('click').on('click', function(e) {
                if ($(e.target).is($reviewModal)) closeReviewModal();
            });
            $ratingStars.off('click').on('click', function() {
                setRating($(this).data('rating'));
            });
        }

        $(document).ready(function() {
            cacheMyOrdersElements();
            handleViewDetails();
            handleCancelButtons();
            handleReviewButtons();
            handleReviewSubmit();
            handleModalClicks();
        });
    </script>
</body>

</html>