/*
 * ConsuTrade - Unified Dashboard JavaScript
 * Author: Kamogelo Phale
 * 
 * Handles both Admin and Seller dashboards
 * Centralized functions to avoid duplication across files
 */

// ========== GLOBAL VARIABLES ==========
var baseUrl = baseUrl || '';

// ========== TOAST NOTIFICATIONS (reuse from main.js if available) ==========
function showToast(message, type) {
    if (typeof window.showSuccessToast === 'function') {
        if (type === 'success') showSuccessToast(message);
        else if (type === 'error') showErrorToast(message);
        else showInfoToast(message);
    } else {
        alert(message);
    }
}

// ========== PRODUCT FUNCTIONS (global for reuse) ==========

/**
 * Edit product - redirect to edit page
 */
window.editProduct = function(productId) {
    if (productId) {
        window.location.href = baseUrl + 'admin/edit-product.php?id=' + productId;
    }
};

/**
 * Delete product with confirmation
 */
window.deleteProduct = function(productId) {
    if (!productId) return;
    
    if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
        var $btn = $('.delete-btn[data-id="' + productId + '"]');
        $btn.prop('disabled', true).text('Deleting...');
        
        $.ajax({
            url: baseUrl + 'admin/php/delete-product.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ product_id: productId }),
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    showToast('Product deleted successfully', 'success');
                    if (typeof window.loadSellerProducts === 'function') {
                        window.loadSellerProducts();
                    } else {
                        location.reload();
                    }
                } else {
                    showToast('Error: ' + data.message, 'error');
                    $btn.prop('disabled', false).text('Delete');
                }
            },
            error: function() {
                showToast('Something went wrong. Please try again.', 'error');
                $btn.prop('disabled', false).text('Delete');
            }
        });
    }
};

/**
 * View order details
 */
window.viewOrder = function(orderId) {
    if (orderId) {
        window.location.href = baseUrl + 'admin/my-orders.php?view=' + orderId;
    }
};

/**
 * Update order status
 */
window.updateOrderStatus = function(orderId, newStatus) {
    $.ajax({
        url: baseUrl + 'admin/php/update-order-status.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ order_id: orderId, status: newStatus }),
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                showToast('Error: ' + data.message, 'error');
            }
        },
        error: function() {
            showToast('Something went wrong', 'error');
        }
    });
};

// ========== SELLER PRODUCTS FUNCTION ==========
window.loadSellerProducts = function(limit = null) {
    var $grid = $('#listings-grid, #products-grid');
    if (!$grid.length) return;
    
    $grid.html('<div class="loading-spinner">Loading your products...</div>');
    
    var url = baseUrl + 'admin/php/get-seller-products.php';
    if (limit) {
        url += '?limit=' + limit;
    }
    
    $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success && data.products && data.products.length) {
                displaySellerProducts($grid, data.products);
            } else {
                var emptyHtml = '<div class="empty-listings">' +
                    '<p>You haven\'t listed any products yet.</p>' +
                    '<button class="add-listing-btn" onclick="window.location.href=\'' + baseUrl + 'admin/add-product.php\'">+ Add Your First Product</button>' +
                    '</div>';
                $grid.html(emptyHtml);
            }
        },
        error: function() {
            $grid.html('<p class="error">Error loading products. Please refresh the page.</p>');
        }
    });
};

/**
 * Display seller products in grid
 */
function displaySellerProducts($grid, products) {
    $grid.empty();
    
    $.each(products, function(i, product) {
        var imagePath = product.image || product.image_url;
        if (imagePath && !imagePath.startsWith('http') && !imagePath.startsWith('/') && !imagePath.startsWith(baseUrl)) {
            imagePath = baseUrl + imagePath;
        }
        
        var stockBadge = '';
        if (product.stock_quantity <= 0) {
            stockBadge = '<span class="stock-badge out-of-stock">Out of Stock</span>';
        } else if (product.stock_quantity <= 5) {
            stockBadge = '<span class="stock-badge low-stock">Only ' + product.stock_quantity + ' left</span>';
        } else {
            stockBadge = '<span class="stock-badge in-stock">In Stock (' + product.stock_quantity + ')</span>';
        }
        
        var card = $('<div>').addClass('listing-card product-card').attr('data-product-id', product.id);
        card.html(
            '<div class="listing-img product-image">' +
            '<img src="' + (imagePath || baseUrl + 'images/default-product.png') + '" alt="' + escapeHtml(product.name || product.title) + '" loading="lazy" onerror="this.src=\'' + baseUrl + 'images/default-product.png\'">' +
            '</div>' +
            '<div class="listing-info product-details">' +
            '<h3 class="listing-title product-title">' + escapeHtml(product.name || product.title) + '</h3>' +
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

// ========== HELPER FUNCTIONS ==========
function escapeHtml(text) {
    if (!text) return '';
    return $('<div>').text(text).html();
}

function capitalizeFirst(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function setText(id, value) {
    var el = document.getElementById(id);
    if (el) el.textContent = value;
}

// ========== ADMIN DASHBOARD FUNCTIONS ==========
function loadAdminStats() {
    $.ajax({
        url: baseUrl + 'php/get-user-stats.php',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                setText('totalUsers', data.total_users || 0);
                setText('totalBuyers', data.total_buyers || 0);
                setText('totalSellers', data.total_sellers || 0);
                setText('totalProducts', data.total_products || 0);
                setText('activeProducts', data.active_products || 0);
                setText('totalOrders', data.total_orders || 0);
                setText('pendingOrders', data.pending_orders || 0);
                setText('completedOrders', data.completed_orders || 0);
                setText('totalEarnings', 'R ' + (data.total_earnings || 0).toFixed(2));
            }
        },
        error: function() {
            console.log('Error loading admin stats');
        }
    });
}

function loadRecentUsers() {
    var $tbody = $('#recent-users-table');
    if (!$tbody.length) return;
    
    $.ajax({
        url: baseUrl + 'admin/php/get-recent-users.php',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success && data.users && data.users.length) {
                $tbody.empty();
                $.each(data.users, function(i, user) {
                    $tbody.append(
                        '<tr>' +
                        '<td>' + escapeHtml(user.full_name) + '</td>' +
                        '<td>' + escapeHtml(user.email) + '</td>' +
                        '<td><span class="role-badge role-' + user.role + '">' + capitalizeFirst(user.role) + '</span></td>' +
                        '<td>' + escapeHtml(user.created_at) + '</td>' +
                        '</tr>'
                    );
                });
            } else {
                $tbody.html('<tr><td colspan="4" style="text-align: center;">No users found</td></tr>');
            }
        },
        error: function() {
            $tbody.html('<tr><td colspan="4" style="text-align: center;">Error loading users</td></tr>');
        }
    });
}

function loadRecentOrders(limit = 5) {
    var $tbody = $('#recent-orders-table');
    if (!$tbody.length) return;
    
    $.ajax({
        url: baseUrl + 'admin/php/get-recent-orders.php?limit=' + limit,
        type: 'GET',
        dataType: 'json',
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
                $tbody.html('<tr><td colspan="5" style="text-align: center;">No orders found</td></tr>');
            }
        },
        error: function() {
            $tbody.html('<tr><td colspan="5" style="text-align: center;">Error loading orders</td></tr>');
        }
    });
}

// ========== SELLER DASHBOARD FUNCTIONS ==========
function loadSellerStats() {
    $.ajax({
        url: baseUrl + 'php/get-user-stats.php',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                setText('stat-earnings', 'R ' + parseFloat(data.total_earnings || 0).toFixed(2));
                setText('stat-products', data.total_products || 0);
                setText('stat-pending', data.pending_orders || 0);
                setText('stat-completed', data.completed_orders || 0);
                setText('stat-shipped', data.shipped_orders || 0);
                setText('stat-processing', data.processing_orders || 0);
                setText('stat-rating', data.avg_rating ? data.avg_rating + ' / 5' : 'No reviews yet');
            }
        },
        error: function() {
            console.log('Error loading seller stats');
        }
    });
}

function loadSellerRecentOrders(limit = 5) {
    var $ordersList = $('#recent-orders-list');
    if (!$ordersList.length) return;
    
    $.ajax({
        url: baseUrl + 'admin/php/get-seller-recent-orders.php?limit=' + limit,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success && data.orders && data.orders.length) {
                $ordersList.empty();
                $.each(data.orders, function(i, order) {
                    var statusClass = '';
                    if (order.status === 'pending') statusClass = 'status-pending';
                    else if (order.status === 'processing') statusClass = 'status-processing';
                    else if (order.status === 'shipped') statusClass = 'status-shipped';
                    else if (order.status === 'completed') statusClass = 'status-completed';
                    else if (order.status === 'cancelled') statusClass = 'status-cancelled';
                    
                    $ordersList.append(
                        '<div class="order-item" onclick="viewOrder(' + order.id + ')">' +
                        '<div class="order-info">' +
                        '<span class="order-number">#' + order.id + '</span>' +
                        '<span class="order-status ' + statusClass + '">' + capitalizeFirst(order.status) + '</span>' +
                        '</div>' +
                        '<div class="order-details">' +
                        '<span class="order-buyer">' + escapeHtml(order.buyer_name) + '</span>' +
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

function loadAdminDashboard() {
    loadAdminStats();
    loadRecentUsers();
    loadRecentOrders();
}

function loadSellerDashboard() {
    loadSellerStats();
    window.loadSellerProducts(4);
    loadSellerRecentOrders(5);
}

// ========== RECENT ORDERS FOR SELLER PAGE ==========
function loadAllSellerOrders(page = 1, status = 'all', search = '') {
    var $tbody = $('#orders-table-body');
    if (!$tbody.length) return;
    
    $tbody.html('<tr><td colspan="7" style="text-align: center;">Loading orders...</td></tr>');
    
    $.ajax({
        url: baseUrl + 'admin/php/get-seller-orders.php',
        type: 'GET',
        dataType: 'json',
        data: { page: page, status: status, search: search },
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
                        actionButtons = '<button class="action-btn process-btn" onclick="updateOrderStatus(' + order.id + ', \'processing\')">Process</button>';
                    } else if (order.status === 'processing') {
                        actionButtons = '<button class="action-btn ship-btn" onclick="updateOrderStatus(' + order.id + ', \'shipped\')">Mark Shipped</button>';
                    } else if (order.status === 'shipped') {
                        actionButtons = '<button class="action-btn complete-btn" onclick="updateOrderStatus(' + order.id + ', \'completed\')">Complete</button>';
                    } else if (order.status === 'completed') {
                        actionButtons = '<span class="completed-label">Completed</span>';
                    } else if (order.status === 'cancelled') {
                        actionButtons = '<span class="cancelled-label">Cancelled</span>';
                    }
                    
                    $tbody.append(
                        '<tr>' +
                        '<td>#' + order.id + '</td>' +
                        '<td>' + escapeHtml(order.buyer_name) + '</td>' +
                        '<td>' + order.items_count + '</td>' +
                        '<td>R ' + parseFloat(order.total).toFixed(2) + '</td>' +
                        '<td><span class="status-badge ' + statusClass + '">' + capitalizeFirst(order.status) + '</span></td>' +
                        '<td>' + order.created_at + '</td>' +
                        '<td class="action-buttons">' + actionButtons + '</td>' +
                        '</tr>'
                    );
                });
            } else {
                $tbody.html('<tr><td colspan="7" style="text-align: center;">No orders found</td></tr>');
            }
        },
        error: function() {
            $tbody.html('<tr><td colspan="7" style="text-align: center;">Error loading orders</td></tr>');
        }
    });
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
        $('body').addClass(prefix + '-menu-open').css('overflow', 'hidden');
    }
    
    function closeSidebar() {
        $sideMenu.removeClass('active');
        if ($overlay.length) $overlay.removeClass('active');
        $hamburger.css('opacity', '1').css('visibility', 'visible');
        $('body').removeClass(prefix + '-menu-open').css('overflow', '');
    }
    
    $hamburger.on('click', openSidebar);
    if ($closeBtn.length) $closeBtn.on('click', closeSidebar);
    if ($overlay.length) $overlay.on('click', closeSidebar);
    
    $('.' + prefix + '-sidebar-nav a, .' + prefix + '-sidebar-link').on('click', function() {
        if ($(window).width() <= 768) {
            closeSidebar();
        }
    });
}

// ========== USER DROPDOWN TOGGLE ==========
function initUserDropdown() {
    var $userInfo = $('#adminUserInfo, #sellerUserInfo');
    var $dropdownMenu = $('#adminDropdownMenu, #sellerDropdownMenu');
    
    if (!$userInfo.length || !$dropdownMenu.length) return;
    
    $userInfo.on('click', function(e) {
        e.stopPropagation();
        $dropdownMenu.toggleClass('show');
    });
    
    $(document).on('click', function(e) {
        if (!$userInfo.is(e.target) && !$dropdownMenu.is(e.target) && 
            !$userInfo.has(e.target).length && !$dropdownMenu.has(e.target).length) {
            $dropdownMenu.removeClass('show');
        }
    });
}

// ========== ORDER STATUS UPDATE FUNCTIONS ==========
window.processOrder = function(orderId) {
    if (confirm('Process this order?')) {
        updateOrderStatus(orderId, 'processing');
    }
};

window.shipOrder = function(orderId) {
    if (confirm('Mark this order as shipped?')) {
        updateOrderStatus(orderId, 'shipped');
    }
};

window.completeOrder = function(orderId) {
    if (confirm('Mark this order as completed?')) {
        updateOrderStatus(orderId, 'completed');
    }
};

window.cancelOrder = function(orderId) {
    if (confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
        updateOrderStatus(orderId, 'cancelled');
    }
};

// ========== DOCUMENT READY ==========
$(function() {
    initMobileSidebar('admin');
    initMobileSidebar('seller');
    initUserDropdown();
    
    // Check which dashboard we're on and load appropriate data
    if ($('#totalUsers').length || $('#recent-users-table').length) {
        loadAdminDashboard();
    }
    
    if ($('#stat-products').length || $('#listings-grid').length) {
        loadSellerDashboard();
    }
    
    // For orders page
    if ($('#orders-table-body').length) {
        loadAllSellerOrders();
    }
});