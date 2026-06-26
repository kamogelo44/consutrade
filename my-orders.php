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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/dashboard-layout.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <style>
        .orders-container {
            width: 100%;
            margin: 0 auto;
            padding: var(--spacing-xl);
        }

        .search-bar {
            display: flex;
            gap: var(--spacing-sm);
            align-items: center;
        }

        .search-bar input {
            padding: 8px 12px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            width: 250px;
            font-size: var(--font-md);
        }

        .search-bar input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 107, 0, 0.1);
        }

        .search-bar button {
            padding: 8px 12px;
            background: var(--primary-color);
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all var(--transition-fast);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .search-bar button img {
            width: 16px;
            height: 16px;
            filter: brightness(0) invert(1);
        }

        .search-bar button:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .reset-search-btn {
            padding: 8px 16px;
            background: var(--gray-bg);
            color: var(--gray-dark);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: var(--font-medium);
            transition: all var(--transition-fast);
        }

        .reset-search-btn:hover {
            background: var(--gray-lighter);
            transform: translateY(-1px);
        }

        .action-buttons {
            display: flex;
            gap: var(--spacing-sm);
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            font-size: var(--font-xs);
            cursor: pointer;
            border: none;
            font-weight: var(--font-medium);
            transition: all var(--transition-fast);
        }

        .view-btn {
            background: var(--info-light);
            color: var(--info);
            border: 1px solid var(--info);
        }

        .view-btn:hover {
            background: var(--info);
            color: white;
            transform: translateY(-1px);
        }

        .cancel-btn {
            background: var(--error-light);
            color: var(--error);
            border: 1px solid var(--error);
        }

        .cancel-btn:hover {
            background: var(--error);
            color: white;
            transform: translateY(-1px);
        }

        .review-btn {
            background: var(--success-light);
            color: var(--success);
            border: 1px solid var(--success);
        }

        .review-btn:hover {
            background: var(--success);
            color: white;
            transform: translateY(-1px);
        }

        .edit-review-btn {
            background: var(--primary-fade);
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }

        .edit-review-btn:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-1px);
        }

        @media (max-width: 768px) {
            .orders-container {
                padding: var(--spacing-md);
                margin-top: 60px;
            }

            .filters-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .status-filters {
                justify-content: center;
            }

            .search-bar {
                justify-content: center;
            }

            .search-bar input {
                width: 100%;
            }

            .action-buttons {
                flex-direction: column;
            }

            .action-btn {
                width: 100%;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .page-header h1 {
                font-size: var(--font-xl);
            }
        }
    </style>
</head>

<body>

    <?php include 'includes/header.php'; ?>
    <?php include 'includes/breadcrumb.php'; ?>

    <main class="orders-container">
        <div class="page-header">
            <h1>My Orders</h1>
            <p>Track and manage your purchase history</p>
        </div>

        <div class="filters-bar">
            <div class="filter-group">
                <label>Filter by Status:</label>
                <select id="statusFilter">
                    <option value="all">All Orders</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="shipped">Shipped</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <div class="search-group">
                <input type="text" id="searchInput" placeholder="Search by order number...">
                <button id="searchBtn">
                    <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" alt="Search">
                </button>
                <button id="resetBtn" class="reset-btn" style="display: none;">Reset</button>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order Number</th>
                        <th>Seller</th>
                        <th>Items</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="ordersTable">
                    <tr>
                        <td colspan="7">
                            <div class="loading-spinner">Loading orders...</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="pagination" id="pagination"></div>
    </main>

    <!-- Order Details Modal -->
    <div id="orderModal" class="order-modal">
        <div class="order-modal-content">
            <div class="order-modal-header">
                <h2>Order Details</h2>
                <button class="order-modal-close" onclick="closeOrderModal()">&times;</button>
            </div>
            <div class="order-details-content" id="orderModalBody">
                <div class="loading-spinner">Loading order details...</div>
            </div>
            <div class="order-modal-footer" id="orderModalFooter"></div>
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

        // Order table variables
        var $ordersTable = null,
            $pagination = null,
            $filterBtns = null,
            $searchBtn = null,
            $resetBtn = null,
            $searchInput = null,
            currentPage = 1,
            currentStatus = 'all',
            currentSearch = '';

        // Review modal variables
        var $reviewModal = null,
            $reviewForm = null,
            $reviewRating = null,
            $ratingStars = null,
            $reviewComment = null,
            $reviewOrderId = null,
            $reviewSellerId = null,
            $reviewSellerName = null,
            $isEditMode = null,
            $submitReviewBtn = null;

        function cacheElements() {
            $ordersTable = $('#ordersTable');
            $pagination = $('#pagination');
            $filterBtns = $('.status-filters .filter-btn');
            $searchBtn = $('#searchBtn');
            $resetBtn = $('#resetBtn');
            $searchInput = $('#searchInput');

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
        }

        function loadBuyerOrders() {
            loadOrders(
                'php/endpoints/orders/get-my-orders.php',
                $ordersTable,
                $pagination,
                currentPage,
                currentStatus,
                currentSearch, 'buyer',
                function(newPage) {
                    currentPage = newPage;
                    loadBuyerOrders();
                    $('html, body').animate({
                        scrollTop: 0
                    }, 'smooth');
                }
            );
        }

        // Review functions
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
                            loadBuyerOrders();
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

        $(document).ready(function() {
            cacheElements();
            loadBuyerOrders();
            handleReviewSubmit();

            // Rating stars click
            $ratingStars.off('click').on('click', function() {
                setRating($(this).data('rating'));
            });

            // Filter buttons
            $filterBtns.on('click', function() {
                $filterBtns.removeClass('active');
                $(this).addClass('active');
                currentStatus = $(this).data('status');
                currentPage = 1;
                loadBuyerOrders();
            });

            // Search
            $searchBtn.on('click', function() {
                currentSearch = $searchInput.val().trim();
                currentPage = 1;
                loadBuyerOrders();
                $resetBtn.toggle(!!currentSearch);
            });

            $resetBtn.on('click', function() {
                $searchInput.val('');
                currentSearch = '';
                currentPage = 1;
                $filterBtns.removeClass('active');
                $filterBtns.filter('[data-status="all"]').addClass('active');
                currentStatus = 'all';
                loadBuyerOrders();
                $(this).hide();
            });

            $searchInput.on('keypress', function(e) {
                if (e.which === 13) $searchBtn.click();
            });

            $('#statusFilter').on('change', function() {
                currentStatus = $(this).val();
                currentPage = 1;
                loadBuyerOrders();
            });

            // Modals
            $('.order-modal-close').on('click', function() {
                closeOrderModal();
            });

            $('.review-modal-close').on('click', function() {
                closeReviewModal();
            });

            $('#orderModal').on('click', function(e) {
                if ($(e.target).is('#orderModal')) {
                    closeOrderModal();
                }
            });

            $('#reviewModal').on('click', function(e) {
                if ($(e.target).is('#reviewModal')) {
                    closeReviewModal();
                }
            });
        });
    </script>

</body>

</html>