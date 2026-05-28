/*
 * ConsuTrade - Unified Dashboard JavaScript
 * Author: Kamogelo Phale
 * 
 * Handles both Admin and Seller dashboards
 * Includes shared modal functions for order details
 * 
 * Note: Relies on main.js for escapeHtml(), showSuccessToast(), showErrorToast()
 */

// ========== GLOBAL VARIABLES ==========
var baseUrl = baseUrl || '';

// ========== HELPER FUNCTIONS ==========
function setText(id, value) {
    $('#' + id).text(value);
}

function capitalizeFirst(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// ========== STATUS CLASS HELPER ==========
function getStatusClass(status) {
    switch(status) {
        case 'pending': return 'status-pending';
        case 'processing': return 'status-processing';
        case 'shipped': return 'status-shipped';
        case 'completed': return 'status-completed';
        case 'cancelled': return 'status-cancelled';
        default: return '';
    }
}

// ========== SHARED MODAL FUNCTIONS ==========
function openOrderModal(orderId) {
    var $modal = $('#orderModal');
    var $body = $('#orderModalBody');
    var $footer = $('#orderModalFooter');
    
    if (!$modal.length) return;
    
    $modal.addClass('active');
    $body.html('<div class="loading-spinner">Loading order details...</div>');
    $footer.empty();
    
    $.ajax({
        url: baseUrl + 'php/endpoints/get-order-details.php?order_id=' + orderId,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success && data.order) {
                displayOrderDetailsInModal(data.order);
            } else {
                $body.html('<p class="error" style="color: var(--error); text-align: center;">Unable to load order details.</p>');
                $footer.empty();
            }
        },
        error: function() {
            $body.html('<p class="error" style="color: var(--error); text-align: center;">Error loading order details.</p>');
            $footer.empty();
        }
    });
}

function displayOrderDetailsInModal(order) {
    var $body = $('#orderModalBody');
    var $footer = $('#orderModalFooter');
    var isAdmin = window.location.pathname.includes('all-orders.php');
    
    var itemsHtml = '';
    if (order.items && order.items.length > 0) {
        $.each(order.items, function(i, item) {
            var imagePath = item.image_url;
            if (imagePath && !imagePath.startsWith('http') && !imagePath.startsWith('/')) {
                imagePath = baseUrl + imagePath;
            }
            itemsHtml += `
                <div class="order-item">
                    <div class="order-item-img">
                        <img src="${imagePath || baseUrl + 'images/default-product.png'}" onerror="this.src='${baseUrl}images/default-product.png'">
                    </div>
                    <div class="order-item-details">
                        <h4>${escapeHtml(item.product_name)}</h4>
                        <p>Quantity: ${item.quantity}</p>
                    </div>
                    <div class="order-item-price">R ${parseFloat(item.price).toFixed(2)}</div>
                </div>
            `;
        });
    }
    
    $body.html(`
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
                <span class="info-value status-${order.status}">${order.status ? order.status.toUpperCase() : 'UNKNOWN'}</span>
            </div>
            ${isAdmin ? `
                <div class="info-row">
                    <span class="info-label">Customer:</span>
                    <span class="info-value">${escapeHtml(order.buyer_name || order.other_party_name || 'N/A')}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Seller:</span>
                    <span class="info-value">${escapeHtml(order.seller_name || order.other_party_name || 'N/A')}</span>
                </div>
            ` : `
                <div class="info-row">
                    <span class="info-label">Customer Name:</span>
                    <span class="info-value">${escapeHtml(order.buyer_name || order.other_party_name || 'N/A')}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Customer Email:</span>
                    <span class="info-value">${escapeHtml(order.buyer_email || 'N/A')}</span>
                </div>
            `}
            ${order.shipping_address ? `
            <div class="info-row">
                <span class="info-label">Shipping Address:</span>
                <span class="info-value">${escapeHtml(order.shipping_address)}</span>
            </div>
            ` : ''}
        </div>
        
        <h4>Order Items</h4>
        <div class="order-items-list">
            ${itemsHtml || '<p class="no-items">No items found.</p>'}
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
    `);
    
    var actionButtons = '';
    if (order.status === 'pending') {
        actionButtons = '<button class="process-btn" onclick="updateOrderStatus(' + order.order_id + ', \'processing\'); closeOrderModal();">Process Order</button>';
    } else if (order.status === 'processing') {
        actionButtons = '<button class="ship-btn" onclick="updateOrderStatus(' + order.order_id + ', \'shipped\'); closeOrderModal();">Mark as Shipped</button>';
    } else if (order.status === 'shipped') {
        actionButtons = '<button class="complete-btn" onclick="updateOrderStatus(' + order.order_id + ', \'completed\'); closeOrderModal();">Mark as Completed</button>';
    }
    if (order.status === 'pending' || order.status === 'processing') {
        actionButtons += '<button class="cancel-btn" onclick="updateOrderStatus(' + order.order_id + ', \'cancelled\'); closeOrderModal();">Cancel Order</button>';
    }
    
    $footer.html(actionButtons);
}

function closeOrderModal() {
    $('#orderModal').removeClass('active');
}

$(document).on('click', '#orderModal', function(e) {
    if (e.target === this) closeOrderModal();
});

function initModalSidebarHandler() {
    var prefix = $('body').hasClass('admin-dashboard-page') ? 'admin' : 'seller';
    var $sideMenu = $('#' + prefix + 'SideMenu');
    
    $(document).on('click', '.view-details-btn, .view-btn, [data-modal-open]', function() {
        if ($sideMenu.hasClass('active')) {
            $sideMenu.data('was-open', true);
            $sideMenu.removeClass('active');
            $('#' + prefix + 'MenuOverlay').removeClass('active');
        }
    });
    
    $(document).on('click', '.modal-close', function() {
        if ($sideMenu.data('was-open') === true) {
            $sideMenu.addClass('active');
            $('#' + prefix + 'MenuOverlay').addClass('active');
            $sideMenu.removeData('was-open');
        }
    });
}

// ========== PRODUCT FUNCTIONS ==========
window.editProduct = function(productId) {
    if (productId) {
        window.location.href = baseUrl + 'admin/edit-product.php?id=' + productId;
    }
};

window.deleteProduct = function(productId) {
    if (!productId) return;
    
    if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
        $.ajax({
            url: baseUrl + 'php/endpoints/delete-product.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ product_id: productId }),
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    if (typeof showSuccessToast === 'function') {
                        showSuccessToast('Product deleted successfully');
                    }
                    if (typeof window.loadSellerProducts === 'function') {
                        window.loadSellerProducts();
                    } else {
                        location.reload();
                    }
                } else {
                    if (typeof showErrorToast === 'function') {
                        showErrorToast('Error: ' + data.message);
                    }
                }
            },
            error: function() {
                if (typeof showErrorToast === 'function') {
                    showErrorToast('Something went wrong. Please try again.');
                }
            }
        });
    }
};

window.viewOrder = function(orderId) {
    if (orderId) {
        var path = window.location.pathname;
        if (path.includes('all-orders.php') || path.includes('admin-dashboard.php')) {
            window.location.href = baseUrl + 'admin/all-orders.php?view=' + orderId;
        } else {
            window.location.href = baseUrl + 'admin/my-orders.php?view=' + orderId;
        }
    }
};

window.updateOrderStatus = function(orderId, newStatus) {
    var confirmMsg = 'Are you sure you want to ' + newStatus + ' this order?';
    if (newStatus === 'cancelled') {
        confirmMsg = 'Are you sure you want to cancel this order? This action cannot be undone.';
    }
    
    if (confirm(confirmMsg)) {
        $.ajax({
            url: baseUrl + 'php/endpoints/update-order-status.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ order_id: orderId, status: newStatus }),
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    if (typeof showSuccessToast === 'function') {
                        showSuccessToast(data.message || 'Order status updated successfully!');
                    }
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    if (typeof showErrorToast === 'function') {
                        showErrorToast('Error: ' + (data.message || 'Unknown error'));
                    }
                }
            },
            error: function() {
                if (typeof showErrorToast === 'function') {
                    showErrorToast('Something went wrong. Please try again.');
                }
            }
        });
    }
};

window.processOrder = function(orderId) { updateOrderStatus(orderId, 'processing'); };
window.shipOrder = function(orderId) { updateOrderStatus(orderId, 'shipped'); };
window.completeOrder = function(orderId) { updateOrderStatus(orderId, 'completed'); };
window.cancelOrder = function(orderId) { updateOrderStatus(orderId, 'cancelled'); };

// ========== SELLER PRODUCTS FUNCTION ==========
window.loadSellerProducts = function(limit) {
    var $grid = $('#listings-grid, #products-grid');
    if (!$grid.length) return;
    
    $grid.html('<div class="loading-spinner">Loading your products...</div>');
    
    var url = baseUrl + 'php/endpoints/get-seller-products.php';
    if (limit) url += '?limit=' + limit;
    
    $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success && data.products && data.products.length) {
                displaySellerProducts($grid, data.products);
            } else {
                $grid.html('<div class="empty-state"><p>You haven\'t listed any products yet.</p></div>');
            }
        },
        error: function() {
            $grid.html('<p class="error">Error loading products. Please refresh the page.</p>');
        }
    });
};

function displaySellerProducts($grid, products) {
    $grid.empty();
    
    $.each(products, function(i, product) {
        var imagePath = product.display_image || product.image || product.image_url;
        if (imagePath && !imagePath.startsWith('http') && !imagePath.startsWith('/') && !imagePath.startsWith(baseUrl)) {
            imagePath = baseUrl + imagePath;
        }
        
        var stockBadge = '';
        if (product.stock_quantity <= 0) {
            stockBadge = '<span class="stock-badge out-of-stock">Out of Stock</span>';
        } else if (product.stock_quantity <= 5) {
            stockBadge = '<span class="stock-badge low-stock">Only ' + product.stock_quantity + ' left</span>';
        }
        
        var productTitle = product.title || product.name;
        
        var card = $('<div>').addClass('listing-card product-card');
        card.html(
            '<div class="listing-img product-image">' +
            '<img src="' + (imagePath || baseUrl + 'images/default-product.png') + '" alt="' + escapeHtml(productTitle) + '" loading="lazy" onerror="this.src=\'' + baseUrl + 'images/default-product.png\'">' +
            '</div>' +
            '<div class="listing-info product-details">' +
            '<h3 class="listing-title product-title">' + escapeHtml(productTitle) + '</h3>' +
            '<p class="listing-price product-price">R ' + parseFloat(product.price).toFixed(2) + '</p>' +
            stockBadge +
            '<div class="listing-actions product-actions">' +
            '<button class="edit-btn" onclick="editProduct(' + product.id + ')">Edit</button>' +
            '<button class="delete-btn" onclick="deleteProduct(' + product.id + ')">Delete</button>' +
            '</div>' +
            '</div>'
        );
        $grid.append(card);
    });
}

// ========== ADMIN DASHBOARD FUNCTIONS ==========
function loadAdminStats() {
    $.ajax({
        url: baseUrl + 'php/endpoints/get-user-stats.php',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                setText('totalRevenue', 'R ' + (data.total_earnings || 0).toFixed(2));
                setText('totalUsers', data.total_users || 0);
                setText('totalProducts', data.total_products || 0);
                setText('pendingOrders', data.pending_orders || 0);
            }
        },
        error: function() {}
    });
}

function loadRecentUsers() {
    var $tbody = $('#recent-users-table');
    if (!$tbody.length) return;
    
    $.ajax({
        url: baseUrl + 'php/endpoints/get-recent-users.php',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success && data.users && data.users.length) {
                $tbody.empty();
                $.each(data.users, function(i, user) {
                    var roleClass = user.role === 'admin' ? 'role-admin' : (user.role === 'seller' ? 'role-seller' : 'role-buyer');
                    $tbody.append(
                        '<tr>' +
                        '<td>' + escapeHtml(user.full_name) + '</td>' +
                        '<td>' + escapeHtml(user.email) + '</td>' +
                        '<td><span class="role-badge ' + roleClass + '">' + capitalizeFirst(user.role) + '</span></td>' +
                        '<td>' + escapeHtml(user.created_at) + '</td>' +
                        '</tr>'
                    );
                });
            } else {
                $tbody.html('<tr><td colspan="4" class="loading-cell">No users found</td></tr>');
            }
        },
        error: function() {
            $tbody.html('<tr><td colspan="4" class="loading-cell">Error loading users</td></tr>');
        }
    });
}

function loadRecentOrders(limit) {
    limit = limit || 5;
    var $tbody = $('#recent-orders-table');
    if (!$tbody.length) return;
    
    $.ajax({
        url: baseUrl + 'php/endpoints/get-recent-orders.php?limit=' + limit,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success && data.orders && data.orders.length) {
                $tbody.empty();
                $.each(data.orders, function(i, order) {
                    var statusClass = getStatusClass(order.status);
                    $tbody.append(
                        '<tr onclick="viewOrder(' + order.id + ')" style="cursor: pointer;">' +
                        '<td>#' + order.id + '</td>' +
                        '<td>' + escapeHtml(order.buyer_name) + '</td>' +
                        '<td>R ' + parseFloat(order.total).toFixed(2) + '</td>' +
                        '<td><span class="status-badge ' + statusClass + '">' + capitalizeFirst(order.status) + '</span></td>' +
                        '<td>' + escapeHtml(order.created_at) + '</td>' +
                        '</tr>'
                    );
                });
            } else {
                $tbody.html('<tr><td colspan="5" class="loading-cell">No orders found</td></tr>');
            }
        },
        error: function() {
            $tbody.html('<tr><td colspan="5" class="loading-cell">Error loading orders</td></tr>');
        }
    });
}

function loadAdminDashboard() {
    loadAdminStats();
    loadRecentUsers();
    loadRecentOrders(5);
}

// ========== SELLER DASHBOARD FUNCTIONS ==========
function loadSellerStats() {
    $.ajax({
        url: baseUrl + 'php/endpoints/get-user-stats.php',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                setText('stat-earnings', 'R ' + parseFloat(data.total_earnings || 0).toFixed(2));
                setText('stat-products', data.total_products || 0);
                setText('stat-pending', data.pending_orders || 0);
            }
        },
        error: function() {}
    });
}

function loadSellerRecentOrders(limit) {
    limit = limit || 5;
    var $ordersList = $('#recent-orders-list');
    if (!$ordersList.length) return;
    
    $.ajax({
        url: baseUrl + 'php/endpoints/get-seller-recent-orders.php?limit=' + limit,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success && data.orders && data.orders.length) {
                $ordersList.empty();
                $.each(data.orders, function(i, order) {
                    var statusClass = getStatusClass(order.status);
                    var productNames = order.product_names || '';
                    if (productNames.length > 40) {
                        productNames = productNames.substring(0, 37) + '...';
                    }
                    $ordersList.append(
                        '<div class="order-item" onclick="viewOrder(' + order.id + ')">' +
                        '<div class="order-info">' +
                        '<span class="order-number">#' + order.id + '</span>' +
                        '<span class="order-status ' + statusClass + '">' + capitalizeFirst(order.status) + '</span>' +
                        '</div>' +
                        '<div class="order-products">' +
                        '<span class="product-names" title="' + escapeHtml(order.product_names) + '">' + escapeHtml(productNames) + '</span>' +
                        '<span class="product-count">' + order.item_count + ' item(s)</span>' +
                        '</div>' +
                        '<div class="order-details">' +
                        '<span class="order-total">R ' + parseFloat(order.total).toFixed(2) + '</span>' +
                        '<span class="order-date">' + order.created_at + '</span>' +
                        '</div>' +
                        '</div>'
                    );
                });
            } else {
                $ordersList.html('<p class="placeholder-text">No recent orders to display.</p>');
            }
        },
        error: function() {
            $ordersList.html('<p class="placeholder-text">Error loading orders.</p>');
        }
    });
}

function loadSellerDashboard() {
    loadSellerStats();
    if (typeof window.loadSellerProducts === 'function') {
        window.loadSellerProducts(4);
    }
    loadSellerRecentOrders(5);
}

// ========== MOBILE SIDEBAR TOGGLE ==========
function initMobileSidebar(prefix) {
    var $hamburger = $('#' + prefix + 'Hamburger');
    var $sideMenu = $('#' + prefix + 'SideMenu');
    var $overlay = $('#' + prefix + 'MenuOverlay');
    var $closeBtn = $('#' + prefix + 'SidebarClose');
    
    if (!$hamburger.length || !$sideMenu.length) return;
    
    function openSidebar() {
        $sideMenu.addClass('active');
        if ($overlay.length) $overlay.addClass('active');
        $hamburger.css('opacity', '0').css('visibility', 'hidden');
        $('body').css('overflow', 'hidden');
    }
    
    function closeSidebar() {
        $sideMenu.removeClass('active');
        if ($overlay.length) $overlay.removeClass('active');
        $hamburger.css('opacity', '1').css('visibility', 'visible');
        $('body').css('overflow', '');
    }
    
    $hamburger.on('click', openSidebar);
    if ($closeBtn.length) $closeBtn.on('click', closeSidebar);
    if ($overlay.length) $overlay.on('click', closeSidebar);
    
    $('.' + prefix + '-sidebar-nav a, .' + prefix + '-sidebar-link').on('click', function() {
        if ($(window).width() <= 1024) closeSidebar();
    });
}

// ========== DOCUMENT READY ==========
$(function() {
    initMobileSidebar('admin');
    initMobileSidebar('seller');
    initModalSidebarHandler();
    
    if ($('#totalUsers').length || $('#recent-users-table').length) {
        loadAdminDashboard();
    }
    
    if ($('#stat-products').length || $('#listings-grid').length) {
        loadSellerDashboard();
    }
});