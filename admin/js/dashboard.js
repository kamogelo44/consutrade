/*
 * ConsuTrade - Unified Dashboard JavaScript
 * Author: Kamogelo Phale
 * 
 * Handles both Admin and Seller dashboards
 * Includes shared modal functions for order details
 * 
 * Note: Relies on main.js for escapeHtml(), showSuccessToast(), showErrorToast(), updateOrderStatus()
 */

// ========== GLOBAL VARIABLES ==========
/** @type {string} Base URL for the site */
var baseUrl = baseUrl || '';

// ========== HELPER FUNCTIONS ==========

/**
 * Sets the text content of an element by ID
 * 
 * @param {string} id - Element ID
 * @param {*} value - Value to set as text
 * @returns {void}
 */
function setText(id, value) {
    $('#' + id).text(value);
}

/**
 * Generates HTML for an empty state message
 * 
 * @param {string} icon - Icon filename from images/icons/
 * @param {string} title - Heading text
 * @param {string} message - Description text
 * @param {string} buttonText - Button text (optional)
 * @param {string} buttonLink - Button link URL (optional)
 * @returns {string} HTML for empty state
 */
function getEmptyStateHTML(icon, title, message, buttonText, buttonLink) {
    var html = '<div class="empty-state">' +
        '<img src="' + baseUrl + 'images/icons/' + icon + '" width="64" height="64" alt="' + title + '">' +
        '<h3>' + escapeHtml(title) + '</h3>' +
        '<p>' + escapeHtml(message) + '</p>';
    
    if (buttonText && buttonLink) {
        html += '<a href="' + buttonLink + '" class="view-all-btn">' + escapeHtml(buttonText) + '</a>';
    }
    
    html += '</div>';
    return html;
}

// ========== PRODUCT MANAGEMENT FUNCTIONS (Shared between admin and seller) ==========

/**
 * Toggle product status (suspend/activate)
 * @param {number} productId - Product ID
 * @param {string} currentStatus - Current status ('active' or 'suspended')
 * @param {function} callback - Optional callback function after success
 */
window.toggleProductStatus = function(productId, currentStatus, callback) {
    var newStatus = currentStatus === 'active' ? 'suspended' : 'active';
    var action = newStatus === 'active' ? 'activate' : 'suspend';
    var confirmMsg = action === 'suspend' 
        ? 'Suspend this product? It will be hidden from buyers.' 
        : 'Activate this product? It will be visible to buyers.';
    
    if (!confirm(confirmMsg)) return;
    
    var requestData = { product_id: productId, status: newStatus };
    
    // Ask for reason if suspending (optional for sellers, recommended for admins)
    if (action === 'suspend') {
        var reason = prompt('Reason for suspension (optional):', '');
        if (reason !== null && reason.trim() !== '') {
            requestData.reason = reason.trim();
        }
    }
    
    $.ajax({
        url: baseUrl + 'php/endpoints/update-product-status.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(requestData),
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                showSuccessToast(data.message || 'Product status updated');
                if (typeof callback === 'function') {
                    callback();
                } else {
                    location.reload();
                }
            } else {
                showErrorToast('Error: ' + data.message);
            }
        },
        error: function() {
            showErrorToast('Something went wrong');
        }
    });
};

/**
 * Delete a product
 * @param {number} productId - Product ID
 * @param {string} productName - Product name for confirmation message
 * @param {function} callback - Optional callback function after success
 */
window.deleteProduct = function(productId, productName, callback) {
    var confirmMsg = productName 
        ? 'Delete "' + productName + '"? This action cannot be undone.'
        : 'Delete this product? This action cannot be undone.';
    
    if (confirm(confirmMsg)) {
        $.ajax({
            url: baseUrl + 'php/endpoints/delete-product.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ product_id: productId }),
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    showSuccessToast(data.message || 'Product deleted successfully');
                    if (typeof callback === 'function') {
                        callback();
                    } else {
                        location.reload();
                    }
                } else {
                    showErrorToast('Error: ' + data.message);
                }
            },
            error: function() {
                showErrorToast('Something went wrong');
            }
        });
    }
};

// ========== SHARED MODAL FUNCTIONS ==========

/**
 * Shortcut to process an order (set status to 'processing')
 * @param {number} orderId - Order ID to process
 */
window.processOrder = function(orderId) { 
    if (typeof updateOrderStatus === 'function') {
        updateOrderStatus(orderId, 'processing');
    }
};

/**
 * Shortcut to mark order as shipped (set status to 'shipped')
 * @param {number} orderId - Order ID to ship
 */
window.shipOrder = function(orderId) { 
    if (typeof updateOrderStatus === 'function') {
        updateOrderStatus(orderId, 'shipped');
    }
};

/**
 * Shortcut to complete an order (set status to 'completed')
 * @param {number} orderId - Order ID to complete
 */
window.completeOrder = function(orderId) { 
    if (typeof updateOrderStatus === 'function') {
        updateOrderStatus(orderId, 'completed');
    }
};

/**
 * Shortcut to cancel an order (set status to 'cancelled')
 * @param {number} orderId - Order ID to cancel
 */
window.cancelOrder = function(orderId) { 
    if (typeof updateOrderStatus === 'function') {
        updateOrderStatus(orderId, 'cancelled');
    }
};

/**
 * Redirects to edit product page
 * @param {number} productId - Product ID to edit
 */
window.editProduct = function(productId) {
    if (productId) {
        window.location.href = baseUrl + 'admin/edit-product.php?id=' + productId;
    }
};

/**
 * Redirects to order details page
 * @param {number} orderId - Order ID to view
 */
window.viewOrder = function(orderId) {
    if (orderId) {
        var path = window.location.pathname;
        if (path.includes('all-orders.php') || path.includes('admin-dashboard.php')) {
            window.location.href = baseUrl + 'admin/all-orders.php?view=' + orderId;
        } else {
            window.location.href = baseUrl + 'admin/seller-orders.php?view=' + orderId;
        }
    }
};

// ========== SELLER PRODUCTS FUNCTION ==========

/** @type {jQuery|null} Listings grid container */
var $listingsGrid = null;

function cacheSellerElements() {
    $listingsGrid = $('#listings-grid');
}

/**
 * Loads seller's products from the server
 * @param {number} [limit] - Optional limit for number of products to load
 */
window.loadSellerProducts = function(limit) {
    cacheSellerElements();
    if (!$listingsGrid.length) return;
    
    $listingsGrid.html('<div class="loading-spinner">Loading your products...</div>');
    var url = baseUrl + 'php/endpoints/get-seller-products.php?seller_id=' + currentUserId;
    if (limit) url += '&limit=' + limit;
    
    $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success && data.products && data.products.length) {
                displaySellerProducts(data.products);
            } else {
                // Enhanced empty state for seller products
                $listingsGrid.html(getEmptyStateHTML(
                    'product-catalog-svgrepo-com.svg',
                    'No products found',
                    'You haven\'t listed any products yet.',
                    'Add Your First Product',
                    baseUrl + 'admin/add-product.php'
                ));
            }
        },
        error: function() {
            $listingsGrid.html('<div class="error-message"><p>Error loading products. Please refresh the page.</p></div>');
        }
    });
};

function displaySellerProducts(products) {
    cacheSellerElements();
    $listingsGrid.empty();
    
    $.each(products, function(i, product) {
        var imagePath = fixImageUrl(product.display_image || product.image);
        var $card = $('<div>').addClass('product-card');
        $card.html(
            '<div class="product-image">' +
                '<img src="' + (imagePath) + '" alt="' + escapeHtml(product.name) + '" onerror="this.src=\'' + baseUrl + 'images/default-product.png\'">' +
            '</div>' +
            '<div class="product-details">' +
                '<h4 class="product-title">' + escapeHtml(product.name) + '</h4>' +
                '<p class="product-price">R ' + parseFloat(product.price).toFixed(2) + '</p>' +
                '<div class="product-actions">' +
                    '<a href="edit-product.php?id=' + product.id + '" class="edit-btn">Edit</a>' +
                    '<button class="delete-btn" onclick="deleteProduct(' + product.id + ', \'' + escapeHtml(product.name).replace(/'/g, "\\'") + '\', function() { location.reload(); })">Delete</button>' +
                '</div>' +
            '</div>'
        );
        $listingsGrid.append($card);
    });
}

// ========== ADMIN DASHBOARD FUNCTIONS ==========

/** @type {jQuery|null} Total revenue element */
var $totalRevenue = null;
var $totalUsers = null;
var $totalProducts = null;
var $pendingOrders = null;

function cacheAdminStatsElements() {
    $totalRevenue = $('#totalRevenue');
    $totalUsers = $('#totalUsers');
    $totalProducts = $('#totalProducts');
    $pendingOrders = $('#pendingOrders');
}

function loadAdminStats() {
    cacheAdminStatsElements();
    $.ajax({
        url: baseUrl + 'php/endpoints/get-user-stats.php',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                if ($totalRevenue.length) $totalRevenue.text('R ' + (data.total_revenue || 0).toFixed(2));
                if ($totalUsers.length) $totalUsers.text(data.total_users || 0);
                if ($totalProducts.length) $totalProducts.text(data.total_products || 0);
                if ($pendingOrders.length) $pendingOrders.text(data.pending_orders || 0);
            }
        },
        error: function() {}
    });
}

// ========== LOAD PENDING VERIFICATIONS ==========

var $pendingVerifications = null;
var $pendingNotice = null;
var $pendingMessage = null;

function cachePendingVerificationElements() {
    $pendingVerifications = $('#pendingVerifications');
    $pendingNotice = $('#pendingNotice');
    $pendingMessage = $('#pendingMessage');
}

function loadPendingVerifications() {
    cachePendingVerificationElements();
    $.ajax({
        url: baseUrl + 'php/endpoints/get-users.php?role=pending',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                var count = data.users ? data.users.length : 0;
                if ($pendingVerifications.length) $pendingVerifications.text(count);
                if (count > 0) {
                    if ($pendingNotice.length) $pendingNotice.show();
                    if ($pendingMessage.length) $pendingMessage.html('<strong>' + count + '</strong> seller(s) waiting for document verification.');
                } else {
                    if ($pendingNotice.length) $pendingNotice.hide();
                }
            }
        },
        error: function() {
            if ($pendingVerifications.length) $pendingVerifications.text('0');
        }
    });
}

function loadRecentUsers() {
    var $recentUsersTable = $('#recent-users-table');
    if (!$recentUsersTable.length) return;
    
    $.ajax({
        url: baseUrl + 'php/endpoints/get-users.php?recent=true',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success && data.users && data.users.length) {
                $recentUsersTable.empty();
                $.each(data.users, function(i, user) {
                    var roleClass = user.role === 'admin' ? 'role-admin' : (user.role === 'seller' ? 'role-seller' : 'role-buyer');
                    $recentUsersTable.append(
                        '<tr>' +
                            '<td>' + escapeHtml(user.full_name) + '</td>' +
                            '<td>' + escapeHtml(user.email) + '</td>' +
                            '<td><span class="role-badge ' + roleClass + '">' + capitalizeFirst(user.role) + '</span></td>' +
                            '<td>' + escapeHtml(user.created_at) + '</td>' +
                        '</tr>'
                    );
                });
            } else {
                $recentUsersTable.html('<tr><td colspan="4" class="empty-cell">No users found</td></tr>');
            }
        },
        error: function() {
            $recentUsersTable.html('<tr><td colspan="4" class="error-cell">Error loading users</td></tr>');
        }
    });
}

function loadRecentOrders(limit) {
    limit = limit || 5;
    var $recentOrdersTable = $('#recent-orders-table');
    if (!$recentOrdersTable.length) return;
    
    $.ajax({
        url: baseUrl + 'php/endpoints/get-recent-orders.php?limit=' + limit,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success && data.orders && data.orders.length) {
                $recentOrdersTable.empty();
                $.each(data.orders, function(i, order) {
                    var statusClass = getStatusClass(order.status);
                    $recentOrdersTable.append(
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
                $recentOrdersTable.html('<tr><td colspan="5" class="empty-cell">No orders found</td></tr>');
            }
        },
        error: function() {
            $recentOrdersTable.html('<tr><td colspan="5" class="error-cell">Error loading orders</td></tr>');
        }
    });
}

function loadAdminDashboard() {
    loadAdminStats();
    loadRecentUsers();
    loadRecentOrders(5);
    loadPendingVerifications();
}

// ========== SELLER DASHBOARD FUNCTIONS ==========

var $statEarnings = null;
var $statProducts = null;
var $statPending = null;

function cacheSellerStatsElements() {
    $statEarnings = $('#stat-earnings');
    $statProducts = $('#stat-products');
    $statPending = $('#stat-pending');
}

function loadSellerStats() {
    cacheSellerStatsElements();
    $.ajax({
        url: baseUrl + 'php/endpoints/get-user-stats.php?seller_id=' + currentUserId,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                if ($statEarnings.length) $statEarnings.text('R ' + parseFloat(data.total_sales || 0).toFixed(2));
                if ($statProducts.length) $statProducts.text(data.total_products || 0);
                if ($statPending.length) $statPending.text(data.pending_orders || 0);
            }
        },
        error: function() {}
    });
}

function loadSellerRecentOrders(limit) {
    limit = limit || 5;
    var $recentOrdersList = $('#recent-orders-list');
    if (!$recentOrdersList.length) return;
    
    $.ajax({
        url: baseUrl + 'php/endpoints/get-seller-recent-orders.php?limit=' + limit,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success && data.orders && data.orders.length > 0) {
                $recentOrdersList.empty();
                $.each(data.orders, function(i, order) {
                    var statusClass = getStatusClass(order.status);
                    var productNames = order.product_names || '';
                    if (productNames.length > 40) {
                        productNames = productNames.substring(0, 37) + '...';
                    }
                    $recentOrdersList.append(
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
                // Enhanced empty state for recent orders
                $recentOrdersList.html(getEmptyStateHTML(
                    'shopping-cart-01-svgrepo-com.svg',
                    'No recent orders',
                    'You haven\'t received any orders yet.',
                    'Browse Products',
                    baseUrl + 'product-listings.php'
                ));
            }
        },
        error: function() {
            $recentOrdersList.html('<div class="error-message"><p>Error loading orders. Please refresh the page.</p></div>');
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
    var $sidebarNavLinks = $('.' + prefix + '-sidebar-nav a, .' + prefix + '-sidebar-link');
    var $body = $('body');
    var $window = $(window);
    
    if (!$hamburger.length || !$sideMenu.length) return;
    
    function openSidebar() {
        $sideMenu.addClass('active');
        if ($overlay.length) $overlay.addClass('active');
        $hamburger.css('opacity', '0').css('visibility', 'hidden');
        $body.css('overflow', 'hidden');
    }
    
    function closeSidebar() {
        $sideMenu.removeClass('active');
        if ($overlay.length) $overlay.removeClass('active');
        $hamburger.css('opacity', '1').css('visibility', 'visible');
        $body.css('overflow', '');
    }
    
    $hamburger.on('click', openSidebar);
    if ($closeBtn.length) $closeBtn.on('click', closeSidebar);
    if ($overlay.length) $overlay.on('click', closeSidebar);
    
    $sidebarNavLinks.on('click', function() {
        if ($window.width() <= 1024) closeSidebar();
    });
}

var $adminSideMenu = null;
var $adminMenuOverlay = null;
var $sellerSideMenu = null;
var $sellerMenuOverlay = null;

function cacheModalSidebarHandlerElements() {
    $adminSideMenu = $('#adminSideMenu');
    $adminMenuOverlay = $('#adminMenuOverlay');
    $sellerSideMenu = $('#sellerSideMenu');
    $sellerMenuOverlay = $('#sellerMenuOverlay');
}

function initModalSidebarHandler() {
    cacheModalSidebarHandlerElements();
    var prefix = $('body').hasClass('admin-dashboard-page') ? 'admin' : 'seller';
    var $sideMenu = (prefix === 'admin') ? $adminSideMenu : $sellerSideMenu;
    var $menuOverlay = (prefix === 'admin') ? $adminMenuOverlay : $sellerMenuOverlay;
    var $viewDetailsBtns = $('.view-details-btn, .view-btn, [data-modal-open]');
    var $modalCloseBtns = $('.modal-close');
    
    $viewDetailsBtns.on('click', function() {
        if ($sideMenu.hasClass('active')) {
            $sideMenu.data('was-open', true);
            $sideMenu.removeClass('active');
            if ($menuOverlay.length) $menuOverlay.removeClass('active');
        }
    });
    
    $modalCloseBtns.on('click', function() {
        if ($sideMenu.data('was-open') === true) {
            $sideMenu.addClass('active');
            if ($menuOverlay.length) $menuOverlay.addClass('active');
            $sideMenu.removeData('was-open');
        }
    });
}

// ========== GALLERY IMAGE FUNCTIONS ==========

function removeGalleryImage(imageId, productId) {
    if (!confirm('Remove this image from the gallery? This action cannot be undone.')) {
        return;
    }
    var $galleryItem = $('.gallery-item[data-image-id="' + imageId + '"]');
    $.ajax({
        url: baseUrl + 'php/endpoints/remove-gallery-image.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ image_id: imageId, product_id: productId }),
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                $galleryItem.fadeOut(300, function() {
                    $(this).remove();
                    if (typeof showSuccessToast === 'function') {
                        showSuccessToast('Image removed successfully');
                    }
                });
            } else {
                if (typeof showErrorToast === 'function') {
                    showErrorToast(data.message || 'Could not remove image');
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

// ========== DOCUMENT READY ==========

var $totalUsersElement = null;
var $recentUsersTableElement = null;
var $statProductsElement = null;
var $listingsGridElement = null;

function cacheDashboardPageElements() {
    $totalUsersElement = $('#totalUsers');
    $recentUsersTableElement = $('#recent-users-table');
    $statProductsElement = $('#stat-products');
    $listingsGridElement = $('#listings-grid');
}

$(function() {
    cacheDashboardPageElements();
    
    initMobileSidebar('admin');
    initMobileSidebar('seller');
    initModalSidebarHandler();
    
    if ($totalUsersElement.length || $recentUsersTableElement.length) {
        loadAdminDashboard();
    }
    
    if ($statProductsElement.length || $listingsGridElement.length) {
        loadSellerDashboard();
    }
});