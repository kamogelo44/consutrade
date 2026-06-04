<?php
/*
 * ConsuTrade - My Orders (Buyer)
 * Author: Kamogelo Phale
 */

require_once __DIR__ . '/init.php';
include __DIR__ . '/includes/session-vars.php';
include __DIR__ . '/includes/functions.php';

if (!$isLoggedIn || !$currentUser instanceof Buyer) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

$breadcrumbItems = [
    ['url' => 'profile.php', 'label' => 'My Profile'],
    ['label' => 'My Orders']
];

$status_filter = $_GET['status'] ?? 'all';
$search_term = $_GET['search'] ?? '';
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
        /* Base styles (keep existing styles) */
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
            margin: 0 0 var(--spacing-xs) 0;
        }

        .page-header p {
            font-size: var(--font-md);
            color: var(--gray-medium);
            margin: 0;
        }

        /* Filter bar styles */
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

        /* Search bar styles */
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

        /* Orders list styles */
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

        .customer-info {
            flex: 2;
        }

        .customer-details {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-xs);
        }

        .customer-details span {
            color: var(--gray-dark);
            font-size: var(--font-sm);
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

        .empty-state {
            text-align: center;
            padding: var(--spacing-2xl);
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-light);
        }

        .empty-state img {
            opacity: 0.5;
            margin-bottom: var(--spacing-lg);
        }

        .empty-state h3 {
            margin-bottom: var(--spacing-sm);
            color: var(--dark-bg);
        }

        .empty-state p {
            color: var(--gray-medium);
            margin-bottom: var(--spacing-lg);
        }

        /* Modal Styles */
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
        }
    </style>
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <main>
        <?php include 'includes/breadcrumb.php'; ?>

        <div class="orders-container">
            <div class="page-header">
                <h1>My Orders</h1>
                <p>Track and manage your purchase history</p>
            </div>

            <?php
            // Use the orders list component
            $role = 'buyer';
            include __DIR__ . '/includes/orders-list.php';
            ?>
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
    <?php include 'includes/modal-errors.php'; ?>

    <script>
        var baseUrl = '<?php echo $baseUrl; ?>';

        // Cached DOM elements
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
        var $editReviewBtns = null;

        function cacheMyOrdersElements() {
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
            $editReviewBtns = $('.edit-review-btn');
        }

        function cancelBuyerOrder(orderId) {
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

        function handleReviewButtons() {
            $('.review-btn:not(.edit-review-btn)').off('click').on('click', function() {
                openReviewModal($(this).data('order-id'), $(this).data('seller-id'), $(this).data('seller-name'));
            });
            $editReviewBtns.off('click').on('click', function() {
                openEditReviewModal(
                    $(this).data('order-id'),
                    $(this).data('seller-id'),
                    $(this).data('seller-name'),
                    $(this).data('rating'),
                    $(this).data('comment')
                );
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
            $('#orderModal').off('click').on('click', function(e) {
                if ($(e.target).is('#orderModal')) closeOrderModal();
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
            handleReviewButtons();
            handleReviewSubmit();
            handleModalClicks();
        });
    </script>
</body>

</html>