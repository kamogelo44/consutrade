<?php
/*
 * ConsuTrade - All Orders (Admin)
 * Author: Kamogelo Phale
 * 
 * Displays all orders on the marketplace for admin management
 */

require_once dirname(__DIR__) . '/init.php';

// Check if admin is logged in using centralized auth
if (!$is_logged_in || $current_user['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$baseUrl = getBaseUrl();
$current_page = 'all-orders';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Orders - ConsuTrade Admin</title>
    <meta name="author" content="Kamogelo Phale">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/dashboard.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/my-orders.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <script>
        var baseUrl = '<?php echo $baseUrl; ?>';
        var currentPage = 1;
        var currentStatus = 'all';
        var currentSearch = '';
    </script>
</head>
<body class="admin-dashboard-page">

<?php include 'includes/sidebar.php'; ?>

<main class="dashboard-main">
    <div class="dashboard-content">
        <div class="dashboard-header">
            <h1>All Orders</h1>
            <p>View and manage all orders on the marketplace</p>
        </div>

        <!-- Filters Bar -->
        <div class="filters-bar" style="margin-bottom: 20px;">
            <div class="status-filters">
                <a href="#" data-status="all" class="filter-btn active">All Orders</a>
                <a href="#" data-status="pending" class="filter-btn">Pending</a>
                <a href="#" data-status="processing" class="filter-btn">Processing</a>
                <a href="#" data-status="shipped" class="filter-btn">Shipped</a>
                <a href="#" data-status="completed" class="filter-btn">Completed</a>
                <a href="#" data-status="cancelled" class="filter-btn">Cancelled</a>
            </div>
            <div class="search-bar">
                <input type="text" id="search-orders" placeholder="Search by order #, customer, or seller...">
                <button id="search-btn">Search</button>
                <button id="reset-btn" style="display: none;">Reset</button>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Seller</th>
                        <th>Items</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="orders-table">
                    <tr><td colspan="8" style="text-align: center;">Loading orders...</td></tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="pagination" id="pagination"></div>
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
<script src="<?php echo $baseUrl; ?>admin/js/dashboard.js"></script>
<script>
/*
 * ConsuTrade - Admin All Orders Functionality
 * Author: Kamogelo Phale
 */
$(document).ready(function() {
    loadAllOrders();
    
    // Status filter clicks
    $('.status-filters .filter-btn').on('click', function(e) {
        e.preventDefault();
        $('.status-filters .filter-btn').removeClass('active');
        $(this).addClass('active');
        currentStatus = $(this).data('status');
        currentPage = 1;
        loadAllOrders();
    });
    
    // Search button
    $('#search-btn').on('click', function() {
        currentSearch = $('#search-orders').val().trim();
        currentPage = 1;
        loadAllOrders();
        if (currentSearch) {
            $('#reset-btn').show();
        }
    });
    
    // Reset button
    $('#reset-btn').on('click', function() {
        $('#search-orders').val('');
        currentSearch = '';
        currentPage = 1;
        loadAllOrders();
        $(this).hide();
    });
    
    // Enter key in search
    $('#search-orders').on('keypress', function(e) {
        if (e.which === 13) {
            currentSearch = $(this).val().trim();
            currentPage = 1;
            loadAllOrders();
            if (currentSearch) {
                $('#reset-btn').show();
            }
        }
    });
    
    function loadAllOrders() {
        var $tbody = $('#orders-table');
        $tbody.html('<tr><td colspan="8" style="text-align: center;">Loading orders...</td></tr>');
        
        $.ajax({
            url: baseUrl + 'admin/php/get-all-orders.php',
            type: 'GET',
            dataType: 'json',
            data: {
                page: currentPage,
                status: currentStatus,
                search: currentSearch
            },
            success: function(data) {
                if (data.success && data.orders && data.orders.length) {
                    $tbody.empty();
                    $.each(data.orders, function(i, order) {
                        var statusClass = '';
                        if (order.status === 'pending') statusClass = 'status-pending';
                        else if (order.status === 'processing') statusClass = 'status-processing';
                        else if (order.status === 'shipped') statusClass = 'status-shipped';
                        else if (order.status === 'completed') statusClass = 'status-completed';
                        else if (order.status === 'cancelled') statusClass = 'status-cancelled';
                        
                        var actionButtons = '';
                        if (order.status === 'pending') {
                            actionButtons = '<button class="action-btn process-btn" onclick="updateOrderStatus(' + order.order_id + ', \'processing\')">Process</button>';
                        } else if (order.status === 'processing') {
                            actionButtons = '<button class="action-btn ship-btn" onclick="updateOrderStatus(' + order.order_id + ', \'shipped\')">Ship</button>';
                        } else if (order.status === 'shipped') {
                            actionButtons = '<button class="action-btn complete-btn" onclick="updateOrderStatus(' + order.order_id + ', \'completed\')">Complete</button>';
                        } else if (order.status === 'pending' || order.status === 'processing') {
                            actionButtons = '<button class="action-btn cancel-btn" onclick="updateOrderStatus(' + order.order_id + ', \'cancelled\')">Cancel</button>';
                        }
                        
                        $tbody.append(`
                            <tr>
                                <td>#${order.order_id}</td>
                                <td>${escapeHtml(order.buyer_name)}</td>
                                <td>${escapeHtml(order.seller_name)}</td>
                                <td>${order.item_count || 0}</td>
                                <td>R ${parseFloat(order.total_price).toFixed(2)}</td>
                                <td><span class="status-badge ${statusClass}">${order.status}</span></td>
                                <td>${order.created_at}</td>
                                <td class="action-buttons">
                                    <button class="action-btn view-btn" onclick="viewOrderDetails(${order.order_id})">View</button>
                                    ${actionButtons}
                                </td>
                            </tr>
                        `);
                    });
                    displayPagination(data.total_pages, data.current_page);
                } else {
                    $tbody.html('<tr><td colspan="8" style="text-align: center;">No orders found</td></tr>');
                    $('#pagination').empty();
                }
            },
            error: function() {
                $tbody.html('<tr><td colspan="8" style="text-align: center;">Error loading orders</td></tr>');
            }
        });
    }
    
    function displayPagination(totalPages, currentPageNum) {
        var $pagination = $('#pagination');
        if (totalPages <= 1) {
            $pagination.empty();
            return;
        }
        
        var html = '';
        if (currentPageNum > 1) {
            html += '<button class="page-btn" onclick="goToPage(' + (currentPageNum - 1) + ')">← Previous</button>';
        }
        
        for (var i = 1; i <= totalPages; i++) {
            if (i === currentPageNum) {
                html += '<button class="page-btn active" disabled>' + i + '</button>';
            } else if (Math.abs(i - currentPageNum) <= 2 || i === 1 || i === totalPages) {
                html += '<button class="page-btn" onclick="goToPage(' + i + ')">' + i + '</button>';
            } else if (Math.abs(i - currentPageNum) === 3) {
                html += '<span class="page-dots">...</span>';
            }
        }
        
        if (currentPageNum < totalPages) {
            html += '<button class="page-btn" onclick="goToPage(' + (currentPageNum + 1) + ')">Next →</button>';
        }
        
        $pagination.html(html);
    }
    
    window.goToPage = function(page) {
        currentPage = page;
        loadAllOrders();
        $('html, body').animate({ scrollTop: 0 }, 'smooth');
    };
    
    window.viewOrderDetails = function(orderId) {
        var modal = document.getElementById('order-modal');
        var content = document.getElementById('order-details-content');
        
        modal.classList.add('active');
        content.innerHTML = '<div class="loading-spinner">Loading order details...</div>';
        
        $.ajax({
            url: baseUrl + 'php/get-order-details.php?order_id=' + orderId,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success && data.order) {
                    displayOrderDetails(data.order);
                } else {
                    content.innerHTML = '<p class="error">Unable to load order details.</p>';
                }
            },
            error: function() {
                content.innerHTML = '<p class="error">Error loading order details.</p>';
            }
        });
    };
    
    function displayOrderDetails(order) {
        var content = document.getElementById('order-details-content');
        var itemsHtml = '';
        
        if (order.items && order.items.length > 0) {
            for (var i = 0; i < order.items.length; i++) {
                var item = order.items[i];
                var imagePath = item.image_url;
                if (imagePath && !imagePath.startsWith('http') && !imagePath.startsWith('/')) {
                    imagePath = baseUrl + imagePath;
                }
                itemsHtml += `
                    <div class="order-item">
                        <div class="order-item-img">
                            <img src="${imagePath || baseUrl + 'images/default-product.png'}" alt="${escapeHtml(item.product_name)}" onerror="this.src='${baseUrl}images/default-product.png'">
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
                    <span class="info-label">Customer:</span>
                    <span class="info-value">${escapeHtml(order.buyer_name || order.other_party_name || 'N/A')}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Seller:</span>
                    <span class="info-value">${escapeHtml(order.seller_name || order.other_party_name || 'N/A')}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Shipping Address:</span>
                    <span class="info-value">${escapeHtml(order.shipping_address) || 'Not provided'}</span>
                </div>
            </div>
            
            <h3>Order Items</h3>
            <div class="order-items-list">
                ${itemsHtml || '<p class="no-items">No items found for this order.</p>'}
            </div>
            
            <div class="order-total-section">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>R ${parseFloat(order.subtotal || 0).toFixed(2)}</span>
                </div>
                <div class="total-row">
                    <span>Delivery Fee:</span>
                    <span>R ${parseFloat(order.delivery_fee || 0).toFixed(2)}</span>
                </div>
                <div class="total-row grand-total">
                    <span>Total:</span>
                    <span>R ${parseFloat(order.total || 0).toFixed(2)}</span>
                </div>
            </div>
        `;
    }
    
    window.updateOrderStatus = function(orderId, newStatus) {
        var confirmMsg = 'Are you sure you want to ' + newStatus + ' this order?';
        if (newStatus === 'cancelled') {
            confirmMsg = 'Are you sure you want to cancel this order? This action cannot be undone.';
        }
        
        if (confirm(confirmMsg)) {
            $.ajax({
                url: baseUrl + 'admin/php/update-order-status.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ order_id: orderId, status: newStatus }),
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        showSuccessToast(data.message || 'Order status updated successfully!');
                        loadAllOrders();
                        closeOrderModal();
                    } else {
                        showErrorToast('Error: ' + (data.message || 'Unknown error'));
                    }
                },
                error: function() {
                    showErrorToast('Something went wrong. Please try again.');
                }
            });
        }
    };
    
    window.closeOrderModal = function() {
        var modal = document.getElementById('order-modal');
        if (modal) modal.classList.remove('active');
    };
    
    // Close modal when clicking outside
    var modal = document.getElementById('order-modal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeOrderModal();
            }
        });
    }
});
</script>

</body>
</html>