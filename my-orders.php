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
    <meta name="author" content="Kamogelo Phale">

    <!-- Master Stylesheet -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>

    <style>
        /* ========== MY ORDERS PAGE SPECIFIC STYLES ========== */
        .orders-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: var(--spacing-xl);
        }

        .page-header {
            margin-bottom: var(--spacing-xl);
        }

        .page-header h1 {
            font-size: var(--font-3xl);
            font-weight: var(--font-bold);
            color: var(--dark-bg);
            margin-bottom: var(--spacing-xs);
        }

        .page-header p {
            color: var(--gray-medium);
            font-size: var(--font-md);
        }

        .filters-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-xl);
            padding-bottom: var(--spacing-md);
            border-bottom: 1px solid var(--border-light);
        }

        .status-filters {
            display: flex;
            gap: var(--spacing-sm);
            flex-wrap: wrap;
        }

        .status-filters .filter-btn {
            padding: 8px 16px;
            border-radius: var(--radius-round);
            font-size: var(--font-sm);
            font-weight: var(--font-medium);
            background: var(--white);
            border: 1px solid var(--border-light);
            color: var(--gray-dark);
            cursor: pointer;
            transition: all var(--transition-fast);
            text-decoration: none;
            display: inline-block;
        }

        .status-filters .filter-btn:hover {
            background: var(--primary-fade);
            border-color: var(--primary-color);
        }

        .status-filters .filter-btn.active {
            background: var(--primary-color);
            color: var(--white);
            border-color: var(--primary-color);
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
        }

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
            transition: all var(--transition-fast);
        }

        .order-card:hover {
            box-shadow: var(--shadow-md);
            border-color: var(--primary-light);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-md) var(--spacing-lg);
            background: var(--gray-bg);
            border-bottom: 1px solid var(--border-light);
            flex-wrap: wrap;
            gap: var(--spacing-sm);
        }

        .order-info {
            display: flex;
            gap: var(--spacing-lg);
            flex-wrap: wrap;
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

        .order-status-badge.status-processing {
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
            padding: var(--spacing-lg);
            flex-wrap: wrap;
            gap: var(--spacing-md);
        }

        .seller-info {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-sm);
            flex: 2;
        }

        .seller-info .seller-details,
        .seller-info .product-details {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .seller-info .seller-details span,
        .seller-info .product-details span {
            color: var(--gray-dark);
            font-size: var(--font-sm);
        }

        .item-details {
            margin-top: var(--spacing-xs);
        }

        .item-details span {
            color: var(--gray-medium);
            font-size: var(--font-xs);
        }

        .order-amount {
            text-align: right;
            flex: 1;
        }

        .order-amount .amount-label {
            display: block;
            font-size: var(--font-xs);
            color: var(--gray-medium);
            margin-bottom: var(--spacing-xs);
        }

        .order-amount .amount-value {
            display: block;
            font-size: var(--font-xl);
            font-weight: var(--font-bold);
            color: var(--primary-color);
        }

        .order-footer {
            display: flex;
            gap: var(--spacing-md);
            padding: var(--spacing-md) var(--spacing-lg);
            background: var(--gray-bg);
            border-top: 1px solid var(--border-light);
            flex-wrap: wrap;
        }

        .order-footer button {
            padding: 8px 20px;
            border-radius: var(--radius-md);
            font-size: var(--font-sm);
            font-weight: var(--font-medium);
            cursor: pointer;
            transition: all var(--transition-fast);
            border: none;
        }

        .view-details-btn {
            background: var(--white);
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }

        .view-details-btn:hover {
            background: var(--primary-fade);
            color: var(--primary-color);
            transform: translateY(-2px);
        }

        .cancel-btn {
            background: var(--error);
            color: var(--white);
        }

        .cancel-btn:hover {
            background: var(--error-dark);
            color: var(--white);
            transform: translateY(-2px);
        }

        .review-btn {
            background: var(--success);
            color: var(--white);
        }

        .review-btn:hover {
            background: var(--success-dark);
            color: var(--white);
            transform: translateY(-2px);
        }

        .edit-review-btn {
            background: var(--info);
            color: var(--white);
        }

        .edit-review-btn:hover {
            background: var(--info-dark);
            color: var(--white);
            transform: translateY(-2px);
        }

        .clear-btn,
        .shop-btn {
            display: inline-block;
            padding: 10px 24px;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: var(--font-medium);
        }

        .clear-btn {
            background: var(--error);
            color: var(--white);
        }

        .shop-btn {
            background: var(--primary-color);
            color: var(--white);
        }

        /* Modal Styles */
        .order-modal {
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

        /* Review Modal */
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

        .review-modal.active {
            display: flex;
        }

        .review-modal-content {
            background: var(--white);
            border-radius: var(--radius-lg);
            max-width: 500px;
            width: 90%;
        }

        .review-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-md) var(--spacing-lg);
            border-bottom: 1px solid var(--border-light);
        }

        .review-modal-header h2 {
            font-size: var(--font-xl);
            font-weight: var(--font-bold);
            margin: 0;
        }

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
        }

        .review-modal-close:hover {
            color: var(--error);
        }

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
            transition: all var(--transition-fast);
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

        @media (max-width: 768px) {
            .orders-container {
                padding: var(--spacing-lg);
            }

            .filters-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .search-bar form {
                width: 100%;
            }

            .search-bar input[type="text"] {
                flex: 1;
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

            .order-item {
                flex-wrap: wrap;
            }

            .order-item-price {
                width: 100%;
                margin-top: var(--spacing-xs);
            }
        }

        @media (max-width: 480px) {
            .orders-container {
                padding: var(--spacing-md);
            }

            .status-filters {
                gap: var(--spacing-xs);
            }

            .status-filters .filter-btn {
                padding: 6px 12px;
                font-size: var(--font-xs);
            }

            .rating-stars .star {
                font-size: 28px;
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
                        // Check if review already exists for this order
                        $existing_review = $reviewRepo->getReviewByOrderAndBuyer($order['order_id'], $currentUser->getUserId());
                        $has_review = $existing_review !== null;
                        $existing_rating = $has_review ? $existing_review['rating'] : 0;
                        $existing_comment = $has_review ? addslashes($existing_review['comment']) : '';
                        ?>
                        <div class="order-card" data-order-id="<?php echo $order['order_id']; ?>">
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
                                <button class="view-details-btn" onclick="openOrderModal(<?php echo $order['order_id']; ?>)">View Details</button>
                                <?php if ($order['status'] === 'pending'): ?>
                                    <button class="cancel-btn" onclick="cancelOrder(<?php echo $order['order_id']; ?>)">Cancel Order</button>
                                <?php endif; ?>
                                <?php if ($order['status'] === 'completed'): ?>
                                    <?php if ($has_review): ?>
                                        <button class="review-btn edit-review-btn" onclick="openEditReviewModal(<?php echo $order['order_id']; ?>, <?php echo $order['seller_id']; ?>, '<?php echo addslashes($order['seller_name']); ?>', <?php echo $existing_rating; ?>, '<?php echo $existing_comment; ?>')">Edit Review</button>
                                    <?php else: ?>
                                        <button class="review-btn" onclick="openReviewModal(<?php echo $order['order_id']; ?>, <?php echo $order['seller_id']; ?>, '<?php echo addslashes($order['seller_name']); ?>')">Leave Review</button>
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
                <button class="order-modal-close" onclick="closeOrderModal()">&times;</button>
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
                <button class="review-modal-close" onclick="closeReviewModal()">&times;</button>
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
                            <span class="star" data-rating="1" onclick="setRating(1)">★</span>
                            <span class="star" data-rating="2" onclick="setRating(2)">★</span>
                            <span class="star" data-rating="3" onclick="setRating(3)">★</span>
                            <span class="star" data-rating="4" onclick="setRating(4)">★</span>
                            <span class="star" data-rating="5" onclick="setRating(5)">★</span>
                        </div>
                        <input type="hidden" id="reviewRating" name="rating" value="0">
                    </div>

                    <div class="form-group">
                        <label for="reviewComment">Your Review</label>
                        <textarea id="reviewComment" name="comment" rows="4" placeholder="Share your experience with this seller... How was communication? Was the item as described? Would you buy from them again?"></textarea>
                    </div>

                    <button type="submit" class="submit-review-btn" id="submitReviewBtn">Submit Review</button>
                </form>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="<?php echo $baseUrl; ?>js/main.js"></script>
    <script>
        // Cancel order function
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

        // Review modal functions
        function openReviewModal(orderId, sellerId, sellerName) {
            $('#isEditMode').val('0');
            $('#reviewModalTitle').text('Review Seller');
            $('#submitReviewBtn').text('Submit Review');
            $('#reviewOrderId').val(orderId);
            $('#reviewSellerId').val(sellerId);
            $('#reviewSellerName').text(sellerName);
            $('#reviewComment').val('');
            resetRatingStars();
            $('#reviewModal').addClass('active');
        }

        function openEditReviewModal(orderId, sellerId, sellerName, existingRating, existingComment) {
            $('#isEditMode').val('1');
            $('#reviewModalTitle').text('Edit Review');
            $('#submitReviewBtn').text('Update Review');
            $('#reviewOrderId').val(orderId);
            $('#reviewSellerId').val(sellerId);
            $('#reviewSellerName').text(sellerName);
            $('#reviewComment').val(existingComment);
            setRating(existingRating);
            $('#reviewModal').addClass('active');
        }

        function closeReviewModal() {
            $('#reviewModal').removeClass('active');
            resetRatingStars();
        }

        function resetRatingStars() {
            $('#reviewRating').val(0);
            $('.rating-stars .star').removeClass('active');
        }

        function setRating(rating) {
            $('#reviewRating').val(rating);
            $('.rating-stars .star').each(function(index) {
                $(this).toggleClass('active', index < rating);
            });
        }

        $('#reviewForm').on('submit', function(e) {
            e.preventDefault();
            var rating = $('#reviewRating').val();
            if (rating == 0) {
                alert('Please select a rating');
                return;
            }
            var isEditMode = $('#isEditMode').val() === '1';
            var url = isEditMode ? baseUrl + 'php/endpoints/update-review.php' : baseUrl + 'php/endpoints/submit-review.php';
            $.ajax({
                url: url,
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    order_id: $('#reviewOrderId').val(),
                    seller_id: $('#reviewSellerId').val(),
                    rating: rating,
                    comment: $('#reviewComment').val()
                }),
                success: function(data) {
                    if (data.success) {
                        showSuccessToast(isEditMode ? 'Review updated!' : 'Thank you for your review!');
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

        $(window).on('click', function(e) {
            if ($(e.target).is('#orderModal')) closeOrderModal();
            if ($(e.target).is('#reviewModal')) closeReviewModal();
        });
    </script>

</body>

</html>