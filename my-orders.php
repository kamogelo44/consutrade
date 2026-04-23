<?php
/*
 * ConsuTrade - My Orders (Buyer)
 * Author: Kamogelo Phale
 * 
 * This page displays all orders for the logged-in buyer
 */

session_start();
require_once 'php/config.php';
require_once 'php/helpers.php';

$baseUrl = getBaseUrl();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// Query using order_items table
$sql = "SELECT o.order_id, o.total_price, o.status, o.created_at,
        u.full_name as seller_name, u.user_id as seller_id,
        (SELECT GROUP_CONCAT(DISTINCT p.title SEPARATOR ', ') 
         FROM order_items oi 
         JOIN products p ON oi.product_id = p.product_id 
         WHERE oi.order_id = o.order_id) as product_names,
        (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
        FROM orders o
        JOIN users u ON o.seller_id = u.user_id
        WHERE o.buyer_id = ?";

$params = [$user_id];
$types = "i";

if ($status_filter !== 'all') {
    $sql .= " AND o.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($search_term)) {
    $sql .= " AND (u.full_name LIKE ? OR o.order_id LIKE ?)";
    $search_param = "%$search_term%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$sql .= " ORDER BY o.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/animations.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/header.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/footer.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/login-signup.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/my-orders.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
</head>
<body class="my-orders-page">

<?php include 'includes/header.php'; ?>

<main>
    <div class="orders-container">
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
                            <button class="view-details-btn" onclick="viewOrderDetails(<?php echo $order['order_id']; ?>)">
                                View Details
                            </button>
                            <?php if ($order['status'] === 'pending'): ?>
                                <button class="cancel-btn" onclick="cancelOrder(<?php echo $order['order_id']; ?>)">
                                    Cancel Order
                                </button>
                            <?php endif; ?>
                            <?php if ($order['status'] === 'completed'): ?>
                                <button class="review-btn" onclick="leaveReview(<?php echo $order['order_id']; ?>, <?php echo $order['seller_id']; ?>, '<?php echo addslashes($order['seller_name']); ?>')">
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
            <button class="order-modal-close" onclick="closeOrderModal()">&times;</button>
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
            <button class="review-modal-close" onclick="closeReviewModal()">&times;</button>
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
var baseUrl = '<?php echo $baseUrl; ?>';
var currentOrderId = null;
var currentSellerId = null;

// NOTE: escapeHtml() is already defined in main.js - DO NOT REDEFINE

function viewOrderDetails(orderId) {
    var modal = document.getElementById('order-modal');
    var content = document.getElementById('order-details-content');
    
    modal.classList.add('active');
    content.innerHTML = '<div class="loading-spinner">Loading order details...</div>';
    
    $.get(baseUrl + 'php/get-order-details.php?order_id=' + orderId, function(data) {
        if (data.success) {
            displayOrderDetails(data.order);
        } else {
            content.innerHTML = '<p class="error">Error loading order details.</p>';
        }
    }).fail(function() {
        content.innerHTML = '<p class="error">Error loading order details.</p>';
    });
}

function displayOrderDetails(order) {
    var content = document.getElementById('order-details-content');
    
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
    
    content.innerHTML = `
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
                <span class="info-label">Seller:</span>
                <span class="info-value">${escapeHtml(order.seller_name)}</span>
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
    `;
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
                    alert('Order cancelled successfully!');
                    location.reload();
                } else {
                    alert('Error cancelling order: ' + data.message);
                }
            },
            error: function() {
                alert('Something went wrong');
            }
        });
    }
}

function leaveReview(orderId, sellerId, sellerName) {
    currentOrderId = orderId;
    currentSellerId = sellerId;
    
    document.getElementById('review-order-id').value = orderId;
    document.getElementById('review-seller-id').value = sellerId;
    document.getElementById('seller-name').textContent = sellerName;
    document.getElementById('review-modal').classList.add('active');
}

function closeOrderModal() {
    document.getElementById('order-modal').classList.remove('active');
}

function closeReviewModal() {
    document.getElementById('review-modal').classList.remove('active');
    resetRatingStars();
}

function resetRatingStars() {
    $('#review-rating').val(0);
    $('.rating-stars .star').removeClass('active');
}

// Rating stars functionality
$(document).ready(function() {
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
            alert('Please select a rating');
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
                    alert('Thank you for your review!');
                    closeReviewModal();
                } else {
                    alert('Error submitting review: ' + data.message);
                }
            },
            error: function() {
                alert('Something went wrong');
            }
        });
    });
});

// Close modal when clicking outside
var modal = document.getElementById('order-modal');
if (modal) {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeOrderModal();
        }
    });
}

var reviewModal = document.getElementById('review-modal');
if (reviewModal) {
    reviewModal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeReviewModal();
        }
    });
}
</script>
</body>
</html>