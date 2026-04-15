<?php
/*
 * ConsuTrade - My Orders (Seller)
 * Author: Kamogelo Phale
 * 
 * This page displays all orders for the logged-in seller
 */

session_start();

$baseUrl = "/www/consutrade/";

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

// Check if user is a seller
if ($_SESSION['role'] !== 'seller') {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

// Include database connection
require_once dirname(__DIR__) . '/php/config.php';

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$sql = "SELECT o.order_id, o.total_price, o.status, o.created_at,
        u.full_name as buyer_name, u.email as buyer_email
        FROM orders o
        JOIN users u ON o.buyer_id = u.user_id
        WHERE o.seller_id = ?";

$params = [$_SESSION['user_id']];
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
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - ConsuTrade Seller</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/header.css">
    <link rel="stylesheet" href="<?php echo $baseUrl;?>css/login-signup.css">
    <link rel="stylesheet" href="css/my-orders.css">
</head>
<body class="my-orders-page">
    <!-- Header -->
    <?php include dirname(__DIR__) . '/header.php'; ?>

    <main>
        <div class="orders-container">
            <!-- Page Header -->
            <div class="page-header">
                <h1>My Orders</h1>
                <p>Manage and track all customer orders</p>
            </div>

            <!-- Filters Bar -->
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
                        <input type="text" name="search" placeholder="Search by order # or customer..." value="<?php echo htmlspecialchars($search_term); ?>">
                        <button type="submit">
                            <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" width="18px" height="18px" alt="Search">
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
                                <div class="customer-info">
                                    <div class="customer-details">
                                        <img src="<?php echo $baseUrl; ?>images/icons/profile-svgrepo-com.svg" width="20px" height="20px" alt="Customer">
                                        <span><?php echo htmlspecialchars($order['buyer_name']); ?></span>
                                    </div>
                                    <div class="customer-details">
                                        <img src="<?php echo $baseUrl; ?>images/icons/email-svgrepo-com.svg" width="20px" height="20px" alt="Email">
                                        <span><?php echo htmlspecialchars($order['buyer_email']); ?></span>
                                    </div>
                                    <div class="customer-details">
                                        <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" width="20px" height="20px" alt="Items">
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
                                    <button class="process-btn" onclick="updateOrderStatus(<?php echo $order['order_id']; ?>, 'processing')">
                                        Process Order
                                    </button>
                                <?php endif; ?>
                                <?php if ($order['status'] === 'processing'): ?>
                                    <button class="ship-btn" onclick="updateOrderStatus(<?php echo $order['order_id']; ?>, 'shipped')">
                                        Mark as Shipped
                                    </button>
                                <?php endif; ?>
                                <?php if ($order['status'] === 'shipped'): ?>
                                    <button class="complete-btn" onclick="updateOrderStatus(<?php echo $order['order_id']; ?>, 'completed')">
                                        Mark as Completed
                                    </button>
                                <?php endif; ?>
                                <?php if (in_array($order['status'], ['pending', 'processing'])): ?>
                                    <button class="cancel-btn" onclick="updateOrderStatus(<?php echo $order['order_id']; ?>, 'cancelled')">
                                        Cancel Order
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-orders">
                        <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" width="64px" height="64px" alt="No orders">
                        <h3>No Orders Found</h3>
                        <p><?php echo !empty($search_term) ? 'No orders match your search criteria.' : 'You haven\'t received any orders yet.'; ?></p>
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

    <script src="<?php echo $baseUrl; ?>js/main.js"></script>
    <script>
        function viewOrderDetails(orderId) {
            var modal = document.getElementById('order-modal');
            var content = document.getElementById('order-details-content');
            
            modal.classList.add('active');
            content.innerHTML = '<div class="loading-spinner">Loading order details...</div>';
            
            fetch('/www/consutrade/php/get-order-details.php?order_id=' + orderId)
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.success) {
                        displayOrderDetails(data.order);
                    } else {
                        content.innerHTML = '<p class="error">Error loading order details.</p>';
                    }
                })
                .catch(function(error) {
                    console.log('Error:', error);
                    content.innerHTML = '<p class="error">Error loading order details.</p>';
                });
        }
        
        function displayOrderDetails(order) {
            var content = document.getElementById('order-details-content');
            var itemsHtml = '';
            
            for (var i = 0; i < order.items.length; i++) {
                var item = order.items[i];
                itemsHtml += `
                    <div class="order-item">
                        <div class="order-item-img">
                            <img src="${item.image}" alt="${item.product_name}" onerror="this.src='/www/consutrade/images/default-product.jpg'">
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
                        <span class="info-label">Customer Name:</span>
                        <span class="info-value">${escapeHtml(order.buyer_name)}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Customer Email:</span>
                        <span class="info-value">${escapeHtml(order.buyer_email)}</span>
                    </div>
                    ${order.shipping_address ? `
                    <div class="info-row">
                        <span class="info-label">Shipping Address:</span>
                        <span class="info-value">${escapeHtml(order.shipping_address)}</span>
                    </div>
                    ` : ''}
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
                
                <div class="order-actions">
                    ${order.status === 'pending' ? '<button class="process-btn" onclick="updateOrderStatus(' + order.order_id + ', \'processing\')">Process Order</button>' : ''}
                    ${order.status === 'processing' ? '<button class="ship-btn" onclick="updateOrderStatus(' + order.order_id + ', \'shipped\')">Mark as Shipped</button>' : ''}
                    ${order.status === 'shipped' ? '<button class="complete-btn" onclick="updateOrderStatus(' + order.order_id + ', \'completed\')">Mark as Completed</button>' : ''}
                    ${order.status === 'pending' || order.status === 'processing' ? '<button class="cancel-btn" onclick="updateOrderStatus(' + order.order_id + ', \'cancelled\')">Cancel Order</button>' : ''}
                </div>
            `;
        }
        
        function updateOrderStatus(orderId, newStatus) {
            var confirmMsg = 'Are you sure you want to ' + newStatus + ' this order?';
            if (newStatus === 'cancelled') {
                confirmMsg = 'Are you sure you want to cancel this order? This action cannot be undone.';
            }
            
            if (confirm(confirmMsg)) {
                fetch('/www/consutrade/php/update-order-status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        order_id: orderId,
                        status: newStatus
                    })
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.success) {
                        alert('Order status updated successfully!');
                        location.reload();
                    } else {
                        alert('Error updating order status: ' + data.message);
                    }
                })
                .catch(function(error) {
                    console.log('Error:', error);
                    alert('Something went wrong');
                });
            }
        }
        
        function closeOrderModal() {
            var modal = document.getElementById('order-modal');
            modal.classList.remove('active');
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Close modal when clicking outside
        var modal = document.getElementById('order-modal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeOrderModal();
                }
            });
        }
    </script>
</body>
</html>