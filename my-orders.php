<?php
/*
 * ConsuTrade - My Orders (Buyer)
 * Author: Kamogelo Phale
 * 
 * This page displays all orders for the logged-in buyer
 */

require_once __DIR__ . '/init.php';

$baseUrl = getBaseUrl();

// Check if user is logged in using centralized auth
if (!$is_logged_in) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get orders using helper function
$orders = getBuyerOrders($conn, $current_user_id, $status_filter, $search_term);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - ConsuTrade</title>
    <meta name="author" content="Kamogelo Phale">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/animations.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/header.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/footer.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/login-signup.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/cart-checkout.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/products.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
</head>
<body class="my-orders-page">

<?php include 'includes/header.php'; ?>

<main>
    <div class="orders-container">
        <!-- Breadcrumb Navigation -->
        <div class="breadcrumb">
            <a href="<?php echo $baseUrl; ?>index.php">Home</a>
            <span class="breadcrumb-separator">›</span>
            <a href="<?php echo $baseUrl; ?>profile.php">My Profile</a>
            <span class="breadcrumb-separator">›</span>
            <span class="current-page">My Orders</span>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1>My Orders</h1>
            <p>Track and manage your purchase history</p>
        </div>

        <!-- Filters Bar -->
        <div class="filters-bar">
            <div class="status-filters">
                <a href="?status=all" class="filter-btn <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All Orders</a>
                <a href="?status=pending" class="filter-btn <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">Pending</a>
                <a href="?status=processing" class="filter-btn <?php echo $status_filter === 'processing' ? 'active' : ''; ?>">Processing</a>
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

        <!-- Orders List -->
        <div class="orders-list">
            <?php if (count($orders) > 0): ?>
                <?php foreach ($orders as $order): ?>
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
                            <button class="view-details-btn" data-order-id="<?php echo $order['order_id']; ?>">
                                View Details
                            </button>
                            <?php if ($order['status'] === 'pending'): ?>
                                <button class="cancel-btn" data-order-id="<?php echo $order['order_id']; ?>">
                                    Cancel Order
                                </button>
                            <?php endif; ?>
                            <?php if ($order['status'] === 'completed'): ?>
                                <button class="review-btn" data-order-id="<?php echo $order['order_id']; ?>" data-seller-id="<?php echo $order['seller_id']; ?>" data-seller-name="<?php echo htmlspecialchars($order['seller_name']); ?>">
                                    Leave Review
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-orders">
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
<div id="order-modal" class="order-modal">
    <div class="order-modal-content">
        <div class="order-modal-header">
            <h2>Order Details</h2>
            <button class="order-modal-close">&times;</button>
        </div>
        <div id="order-details-content" class="order-details-content">
            <div class="loading-spinner">Loading order details...</div>
        </div>
    </div>
</div>

<!-- Review Modal -->
<div id="review-modal" class="review-modal">
    <div class="review-modal-content">
        <div class="review-modal-header">
            <h2>Review Seller</h2>
            <button class="review-modal-close">&times;</button>
        </div>
        <div class="review-form-container">
            <p class="review-info">Rate your experience with <strong id="seller-name"></strong></p>
            <form id="review-form">
                <input type="hidden" id="review-order-id" name="order_id">
                <input type="hidden" id="review-seller-id" name="seller_id">
                
                <div class="form-group">
                    <label>Rating</label>
                    <div class="rating-stars">
                        <span class="star" data-rating="1">★</span>
                        <span class="star" data-rating="2">★</span>
                        <span class="star" data-rating="3">★</span>
                        <span class="star" data-rating="4">★</span>
                        <span class="star" data-rating="5">★</span>
                    </div>
                    <input type="hidden" id="review-rating" name="rating" value="0">
                </div>
                
                <div class="form-group">
                    <label for="review-comment">Your Review (Optional)</label>
                    <textarea id="review-comment" name="comment" rows="4" placeholder="Share your experience with this seller... How was communication? Was the item as described? Would you buy from them again?"></textarea>
                </div>
                
                <button type="submit" class="submit-review-btn">Submit Review</button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
/*
 * ConsuTrade - My Orders Functionality
 * Author: Kamogelo Phale
 */
var baseUrl = '<?php echo $baseUrl; ?>';
var currentUserId = <?php echo $current_user_id; ?>;
var currentUserRole = '<?php echo $current_user['role']; ?>';
var isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;

$(function() {
    // View order details
    $('.view-details-btn').on('click', function() {
        var orderId = $(this).data('order-id');
        viewOrderDetails(orderId);
    });
    
    // Cancel order
    $('.cancel-btn').on('click', function() {
        var orderId = $(this).data('order-id');
        cancelOrder(orderId);
    });
    
    // Leave review
    $('.review-btn').on('click', function() {
        var orderId = $(this).data('order-id');
        var sellerId = $(this).data('seller-id');
        var sellerName = $(this).data('seller-name');
        leaveReview(orderId, sellerId, sellerName);
    });
    
    // Close modals
    $('.order-modal-close, .review-modal-close').on('click', function() {
        closeOrderModal();
        closeReviewModal();
    });
    
    // Click outside to close
    $(window).on('click', function(e) {
        if ($(e.target).is('#order-modal')) {
            closeOrderModal();
        }
        if ($(e.target).is('#review-modal')) {
            closeReviewModal();
        }
    });
    
    // Rating stars
    $('.rating-stars .star').on('click', function() {
        var rating = $(this).data('rating');
        $('#review-rating').val(rating);
        
        $('.rating-stars .star').each(function(index) {
            if (index < rating) {
                $(this).addClass('active');
            } else {
                $(this).removeClass('active');
            }
        });
    });
    
    // Review form submission
    $('#review-form').on('submit', function(e) {
        e.preventDefault();
        
        var rating = $('#review-rating').val();
        if (rating == 0) {
            showErrorToast('Please select a rating');
            return;
        }
        
        var reviewData = {
            order_id: $('#review-order-id').val(),
            seller_id: $('#review-seller-id').val(),
            rating: rating,
            comment: $('#review-comment').val()
        };
        
        $.ajax({
            url: baseUrl + 'php/submit-review.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(reviewData),
            success: function(data) {
                if (data.success) {
                    showSuccessToast('Thank you for your review!');
                    closeReviewModal();
                } else {
                    showErrorToast('Error submitting review: ' + data.message);
                }
            },
            error: function() {
                showErrorToast('Something went wrong');
            }
        });
    });
});

function viewOrderDetails(orderId) {
    var $modal = $('#order-modal');
    var $content = $('#order-details-content');
    
    $modal.addClass('active');
    $content.html('<div class="loading-spinner">Loading order details...</div>');
    
    $.get(baseUrl + 'php/get-order-details.php?order_id=' + orderId, function(data) {
        if (data.success) {
            displayOrderDetails(data.order);
        } else {
            $content.html('<p class="error">Error loading order details.</p>');
        }
    }).fail(function() {
        $content.html('<p class="error">Error loading order details.</p>');
    });
}

function displayOrderDetails(order) {
    var $content = $('#order-details-content');
    
    var itemsHtml = '';
    if (order.items && order.items.length > 0) {
        $.each(order.items, function(index, item) {
            var imagePath = item.image_url;
            if (imagePath && !imagePath.startsWith('http') && !imagePath.startsWith('/')) {
                imagePath = baseUrl + imagePath;
            }
            itemsHtml += `
                <div class="order-item">
                    <div class="order-item-img">
                        <img src="${imagePath || baseUrl + 'images/default-product.png'}" alt="${escapeHtml(item.product_name)}">
                    </div>
                    <div class="order-item-details">
                        <h4>${escapeHtml(item.product_name)}</h4>
                        <p>Quantity: ${item.quantity}</p>
                    </div>
                    <div class="order-item-price">
                        R ${parseFloat(item.price).toFixed(2)}
                    </div>
                </div>
            `;
        });
    }
    
    $content.html(`
        <div class="order-info-section">
            <div class="info-row">
                <span class="info-label">Order Number:</span>
                <span class="info-value">#${order.order_id}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Order Date:</span>
                <span class="info-value">${order.created_at}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Order Status:</span>
                <span class="info-value status-${order.status}">${order.status.toUpperCase()}</span>
            </div>
            <div class="info-row">
                <span class="info-label">${currentUserRole === 'buyer' ? 'Seller' : 'Buyer'}:</span>
                <span class="info-value">${escapeHtml(order.other_party_name)}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Shipping Address:</span>
                <span class="info-value">${escapeHtml(order.shipping_address) || 'Not provided'}</span>
            </div>
        </div>
        
        <h3>Order Items</h3>
        <div class="order-items-list">
            ${itemsHtml}
        </div>
        
        <div class="order-total-section">
            <div class="total-row">
                <span>Subtotal:</span>
                <span>R ${parseFloat(order.subtotal).toFixed(2)}</span>
            </div>
            <div class="total-row">
                <span>Delivery Fee:</span>
                <span>R ${parseFloat(order.delivery_fee).toFixed(2)}</span>
            </div>
            <div class="total-row grand-total">
                <span>Total:</span>
                <span>R ${parseFloat(order.total).toFixed(2)}</span>
            </div>
        </div>
    `);
}

function cancelOrder(orderId) {
    if (confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
        $.ajax({
            url: baseUrl + 'php/cancel-order.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ order_id: orderId }),
            success: function(data) {
                if (data.success) {
                    showSuccessToast('Order cancelled successfully!');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showErrorToast('Error cancelling order: ' + data.message);
                }
            },
            error: function() {
                showErrorToast('Something went wrong');
            }
        });
    }
}

function leaveReview(orderId, sellerId, sellerName) {
    $('#review-order-id').val(orderId);
    $('#review-seller-id').val(sellerId);
    $('#seller-name').text(sellerName);
    $('#review-modal').addClass('active');
}

function closeOrderModal() {
    $('#order-modal').removeClass('active');
}

function closeReviewModal() {
    $('#review-modal').removeClass('active');
    resetRatingStars();
}

function resetRatingStars() {
    $('#review-rating').val(0);
    $('.rating-stars .star').removeClass('active');
}
</script>

</body>
</html>