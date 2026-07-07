<?php
/*
 * ConsuTrade - My Orders (Unified)
 * Author: Kamogelo Phale
 * 
 * Single orders page that adapts to user's roles.
 * - Buyers see their purchase orders
 * - Sellers see their sales orders
 * - Users with both roles see tabs to switch
 */

require_once __DIR__ . '/init.php';
include __DIR__ . '/includes/session-vars.php';
include __DIR__ . '/includes/functions.php';

// Check maintenance mode (one line!)
checkMaintenanceMode();

// Check if user is logged in
if (!$isLoggedIn) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

// Determine which roles the user has
$hasBuyerRole = $currentUser->hasRole('buyer');
$hasSellerRole = $currentUser->hasRole('seller');

if (!$hasBuyerRole && !$hasSellerRole) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

// If user has both roles, show tabs
$showTabs = $hasBuyerRole && $hasSellerRole;

// Determine which tab to show by default
$defaultTab = 'buyer';
if (!$hasBuyerRole && $hasSellerRole) {
    $defaultTab = 'seller';
} elseif ($hasBuyerRole && !$hasSellerRole) {
    $defaultTab = 'buyer';
} elseif ($hasBuyerRole && $hasSellerRole) {
    $defaultTab = $_SESSION['active_order_tab'] ?? 'buyer';
}

// For seller view, use sidebar layout
$useSidebar = ($defaultTab === 'seller' && $hasSellerRole) || (!$hasBuyerRole && $hasSellerRole);

$breadcrumbItems = [
    ['url' => 'profile.php', 'label' => 'My Profile'],
    ['label' => 'My Orders']
];

// Get user info for header
$user_name = $currentUser->getDisplayName();
$profile_image = $currentUser->getProfileImageUrl();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - ConsuTrade</title>
    <!-- main.css imports everything: variables, reset, layout, components, orders.css, etc. -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <?php if ($useSidebar): ?>
        <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/sidebar.css">
        <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/dashboard-layout.css">
    <?php endif; ?>
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <style>
        /* ========== ONLY STYLES NOT IN main.css ========== */

        .orders-container {
            width: 100%;
            margin: 0 auto;
            padding: var(--spacing-xl);
        }

        .orders-container.seller-layout {
            padding: var(--spacing-xl);
            max-width: 1400px;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Back to Profile Link (for seller layout) */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
            color: var(--primary-color);
            text-decoration: none;
            margin-bottom: var(--spacing-md);
            font-weight: var(--font-medium);
            transition: all var(--transition-fast);
        }

        .back-link:hover {
            transform: translateX(-4px);
            text-decoration: underline;
        }

        .back-link img {
            width: 16px;
            height: 16px;
            filter: brightness(0) saturate(100%) invert(48%) sepia(96%) saturate(1577%) hue-rotate(350deg);
        }

        @media (max-width: 768px) {
            .orders-container {
                padding: var(--spacing-md);
                margin-top: 60px;
            }

            .orders-container.seller-layout {
                padding: var(--spacing-md);
                margin-top: 70px;
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

    <?php if ($useSidebar): ?>
        <?php include 'admin/includes/sidebar.php'; ?>
        <main class="seller-main-content">
        <?php else: ?>
            <?php include 'includes/header.php'; ?>
            <main class="orders-container">
            <?php endif; ?>

            <div class="dashboard-content">
                <div class="page-header">
                    <?php if ($useSidebar): ?>
                        <a href="<?php echo $baseUrl; ?>profile.php" class="back-link">
                            <img src="<?php echo $baseUrl; ?>images/icons/left-arrow-2-svgrepo-com.svg" alt="Back">
                            Back to Profile
                        </a>
                    <?php else: ?>
                        <?php include 'includes/breadcrumb.php'; ?>
                    <?php endif; ?>
                    <h1>My Orders</h1>
                    <p><?php echo $useSidebar ? 'Manage orders from your customers' : 'Track and manage your purchase history'; ?></p>
                </div>

                <!-- Role Tabs -->
                <?php if ($showTabs): ?>
                    <div class="order-tabs">
                        <button class="order-tab <?php echo $defaultTab === 'buyer' ? 'active' : ''; ?>" data-tab="buyer">
                            <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" alt="Buying" width="16" height="16">
                            Buying
                            <span class="tab-badge">Purchases</span>
                        </button>
                        <button class="order-tab <?php echo $defaultTab === 'seller' ? 'active' : ''; ?>" data-tab="seller">
                            <img src="<?php echo $baseUrl; ?>images/icons/product-catalog-svgrepo-com.svg" alt="Selling" width="16" height="16">
                            Selling
                            <span class="tab-badge">Sales</span>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Tab Content: Buyer Orders -->
                <div id="tab-buyer" class="tab-content <?php echo $defaultTab === 'buyer' || !$hasSellerRole ? 'active' : ''; ?>">
                    <div class="filters-bar">
                        <div class="filter-group">
                            <label>Filter by Status:</label>
                            <select id="buyerStatusFilter">
                                <option value="all">All Orders</option>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="shipped">Shipped</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>

                        <div class="search-group">
                            <input type="text" id="buyerSearchInput" placeholder="Search by order number...">
                            <button class="search-btn" data-type="buyer">
                                <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" alt="Search">
                            </button>
                            <button class="reset-btn" id="buyerResetBtn" style="display: none;">Reset</button>
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
                            <tbody id="buyerOrdersTable">
                                <tr>
                                    <td colspan="7">
                                        <div class="loading-spinner">Loading orders...</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="pagination" id="buyerPagination"></div>
                </div>

                <!-- Tab Content: Seller Orders -->
                <div id="tab-seller" class="tab-content <?php echo $defaultTab === 'seller' && $hasSellerRole ? 'active' : ''; ?>">
                    <div class="filters-bar">
                        <div class="filter-group">
                            <label>Filter by Status:</label>
                            <select id="sellerStatusFilter">
                                <option value="all">All Orders</option>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="shipped">Shipped</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>

                        <div class="search-group">
                            <input type="text" id="sellerSearchInput" placeholder="Search by order number or customer...">
                            <button class="search-btn" data-type="seller">
                                <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" alt="Search">
                            </button>
                            <button class="reset-btn" id="sellerResetBtn" style="display: none;">Reset</button>
                        </div>
                    </div>

                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Order Number</th>
                                    <th>Customer</th>
                                    <th>Items</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="sellerOrdersTable">
                                <tr>
                                    <td colspan="7">
                                        <div class="loading-spinner">Loading orders...</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="pagination" id="sellerPagination"></div>
                </div>
            </div>

            </main>

            <!-- Order Details Modal -->
            <div id="orderModal" class="order-modal">
                <div class="order-modal-content">
                    <div class="order-modal-header">
                        <h2>Order Details</h2>
                        <button type="button" class="btn-close order-modal-close" onclick="closeOrderModal()">&times;</button>
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
                        <button type="button" class="btn-close review-modal-close" onclick="closeReviewModal()">&times;</button>
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

                // ============================================================
                // BUYER ORDERS
                // ============================================================
                var $buyerTable = null,
                    $buyerPagination = null,
                    $buyerSearchInput = null,
                    $buyerStatusFilter = null,
                    $buyerResetBtn = null,
                    buyerPage = 1,
                    buyerStatus = 'all',
                    buyerSearch = '';

                // ============================================================
                // SELLER ORDERS
                // ============================================================
                var $sellerTable = null,
                    $sellerPagination = null,
                    $sellerSearchInput = null,
                    $sellerStatusFilter = null,
                    $sellerResetBtn = null,
                    sellerPage = 1,
                    sellerStatus = 'all',
                    sellerSearch = '';

                // ============================================================
                // REVIEW MODAL
                // ============================================================
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

                // ============================================================
                // CACHE ELEMENTS
                // ============================================================
                function cacheElements() {
                    // Buyer
                    $buyerTable = $('#buyerOrdersTable');
                    $buyerPagination = $('#buyerPagination');
                    $buyerSearchInput = $('#buyerSearchInput');
                    $buyerStatusFilter = $('#buyerStatusFilter');
                    $buyerResetBtn = $('#buyerResetBtn');

                    // Seller
                    $sellerTable = $('#sellerOrdersTable');
                    $sellerPagination = $('#sellerPagination');
                    $sellerSearchInput = $('#sellerSearchInput');
                    $sellerStatusFilter = $('#sellerStatusFilter');
                    $sellerResetBtn = $('#sellerResetBtn');

                    // Review
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

                // ============================================================
                // LOAD BUYER ORDERS
                // ============================================================
                function loadBuyerOrders() {
                    loadOrders(
                        'php/endpoints/orders/get-my-orders.php',
                        $buyerTable,
                        $buyerPagination,
                        buyerPage,
                        buyerStatus,
                        buyerSearch,
                        'buyer',
                        function(newPage) {
                            buyerPage = newPage;
                            loadBuyerOrders();
                            $('html, body').animate({
                                scrollTop: 0
                            }, 'smooth');
                        }
                    );
                }

                // ============================================================
                // LOAD SELLER ORDERS
                // ============================================================
                function loadSellerOrders() {
                    loadOrders(
                        'php/endpoints/orders/get-my-orders.php',
                        $sellerTable,
                        $sellerPagination,
                        sellerPage,
                        sellerStatus,
                        sellerSearch,
                        'seller',
                        function(newPage) {
                            sellerPage = newPage;
                            loadSellerOrders();
                            $('html, body').animate({
                                scrollTop: 0
                            }, 'smooth');
                        }
                    );
                }

                // ============================================================
                // REVIEW FUNCTIONS
                // ============================================================
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

                // ============================================================
                // TAB SWITCHING
                // ============================================================
                $('.order-tab').on('click', function() {
                    var tab = $(this).data('tab');

                    $('.order-tab').removeClass('active');
                    $(this).addClass('active');

                    $('.tab-content').removeClass('active');
                    $('#tab-' + tab).addClass('active');

                    // Store preference
                    $.ajax({
                        url: baseUrl + 'php/endpoints/auth/set-order-tab.php',
                        type: 'POST',
                        data: JSON.stringify({
                            tab: tab
                        }),
                        contentType: 'application/json'
                    });
                });

                // ============================================================
                // DOCUMENT READY
                // ============================================================
                $(document).ready(function() {
                    cacheElements();

                    // Load buyer orders if buyer tab is active or only buyer role
                    if ($('#tab-buyer').hasClass('active')) {
                        loadBuyerOrders();
                    }

                    // Load seller orders if seller tab is active
                    if ($('#tab-seller').hasClass('active')) {
                        loadSellerOrders();
                    }

                    handleReviewSubmit();

                    // Rating stars click
                    $ratingStars.on('click', function() {
                        setRating($(this).data('rating'));
                    });

                    // ========== BUYER FILTERS ==========
                    $buyerStatusFilter.on('change', function() {
                        buyerStatus = $(this).val();
                        buyerPage = 1;
                        loadBuyerOrders();
                    });

                    $('.search-btn[data-type="buyer"]').on('click', function() {
                        buyerSearch = $buyerSearchInput.val().trim();
                        buyerPage = 1;
                        loadBuyerOrders();
                        $buyerResetBtn.toggle(!!buyerSearch);
                    });

                    $buyerResetBtn.on('click', function() {
                        $buyerSearchInput.val('');
                        buyerSearch = '';
                        buyerPage = 1;
                        $buyerStatusFilter.val('all');
                        buyerStatus = 'all';
                        loadBuyerOrders();
                        $(this).hide();
                    });

                    $buyerSearchInput.on('keypress', function(e) {
                        if (e.which === 13) $('.search-btn[data-type="buyer"]').click();
                    });

                    // ========== SELLER FILTERS ==========
                    $sellerStatusFilter.on('change', function() {
                        sellerStatus = $(this).val();
                        sellerPage = 1;
                        loadSellerOrders();
                    });

                    $('.search-btn[data-type="seller"]').on('click', function() {
                        sellerSearch = $sellerSearchInput.val().trim();
                        sellerPage = 1;
                        loadSellerOrders();
                        $sellerResetBtn.toggle(!!sellerSearch);
                    });

                    $sellerResetBtn.on('click', function() {
                        $sellerSearchInput.val('');
                        sellerSearch = '';
                        sellerPage = 1;
                        $sellerStatusFilter.val('all');
                        sellerStatus = 'all';
                        loadSellerOrders();
                        $(this).hide();
                    });

                    $sellerSearchInput.on('keypress', function(e) {
                        if (e.which === 13) $('.search-btn[data-type="seller"]').click();
                    });

                    // ========== MODALS ==========
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