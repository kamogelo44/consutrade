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
        .orders-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: var(--spacing-xl);
        }

        @media (max-width: 768px) {
            .orders-container {
                padding: var(--spacing-lg);
            }
        }

        @media (max-width: 480px) {
            .orders-container {
                padding: var(--spacing-md);
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
            $role = 'buyer';
            include __DIR__ . '/includes/orders-list.php';
            ?>
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