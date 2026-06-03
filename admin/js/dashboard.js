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
 * 
 * @example
 * setText('totalUsers', 42);
 */
function setText(id, value) {
    $('#' + id).text(value);
}

// ========== SHARED MODAL FUNCTIONS ==========
// Note: order modal functions (openOrderModal, closeOrderModal) are defined in main.js
// updateOrderStatus is also defined in main.js
// These shortcut functions call the main.js version

/**
 * Shortcut to process an order (set status to 'processing')
 * 
 * @param {number} orderId - Order ID to process
 * @returns {void}
 */
window.processOrder = function(orderId) { 
    if (typeof updateOrderStatus === 'function') {
        updateOrderStatus(orderId, 'processing');
    }
};

/**
 * Shortcut to mark order as shipped (set status to 'shipped')
 * 
 * @param {number} orderId - Order ID to ship
 * @returns {void}
 */
window.shipOrder = function(orderId) { 
    if (typeof updateOrderStatus === 'function') {
        updateOrderStatus(orderId, 'shipped');
    }
};

/**
 * Shortcut to complete an order (set status to 'completed')
 * 
 * @param {number} orderId - Order ID to complete
 * @returns {void}
 */
window.completeOrder = function(orderId) { 
    if (typeof updateOrderStatus === 'function') {
        updateOrderStatus(orderId, 'completed');
    }
};

/**
 * Shortcut to cancel an order (set status to 'cancelled')
 * 
 * @param {number} orderId - Order ID to cancel
 * @returns {void}
 */
window.cancelOrder = function(orderId) { 
    if (typeof updateOrderStatus === 'function') {
        updateOrderStatus(orderId, 'cancelled');
    }
};

// ========== PRODUCT FUNCTIONS ==========

/**
 * Redirects to edit product page
 * 
 * @param {number} productId - Product ID to edit
 * @returns {void}
 */
window.editProduct = function(productId) {
    if (productId) {
        window.location.href = baseUrl + 'admin/edit-product.php?id=' + productId;
    }
};

/**
 * Deletes a product via AJAX
 * 
 * @param {number} productId - Product ID to delete
 * @returns {void}
 * 
 * @fires AJAX POST request to delete-product.php
 * @fires showSuccessToast or showErrorToast on completion
 * @sideeffect Reloads seller products or refreshes page
 */
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

/**
 * Redirects to order details page
 * 
 * @param {number} orderId - Order ID to view
 * @returns {void}
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

/**
 * Caches seller dashboard DOM elements
 * 
 * @returns {void}
 */
function cacheSellerElements() {
    $listingsGrid = $('#listings-grid');
}

/**
 * Loads seller's products from the server
 * 
 * @param {number} [limit] - Optional limit for number of products to load
 * @returns {void}
 * 
 * @fires AJAX GET request to get-seller-products.php
 * @sideeffect Populates the listings grid with seller's products
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
                $listingsGrid.html('<div class="empty-state"><p>You haven\'t listed any products yet.</p></div>');
            }
        },
        error: function() {
            $listingsGrid.html('<p class="error">Error loading products. Please refresh the page.</p>');
        }
    });
};

/**
 * Displays seller products in the listings grid
 * 
 * @param {Array} products - Array of product objects
 * @returns {void}
 * 
 * @sideeffect Populates the listings grid with product cards
 */
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
            '<button class="delete-btn" onclick="deleteProduct(' + product.id + ')">Delete</button>' +
            '</div>' +
            '</div>'
        );
        $listingsGrid.append($card);
    });
}

// ========== ADMIN DASHBOARD FUNCTIONS ==========

/** @type {jQuery|null} Total revenue element */
var $totalRevenue = null;

/** @type {jQuery|null} Total users element */
var $totalUsers = null;

/** @type {jQuery|null} Total products element */
var $totalProducts = null;

/** @type {jQuery|null} Pending orders element */
var $pendingOrders = null;

/**
 * Caches admin stats DOM elements
 * 
 * @returns {void}
 */
function cacheAdminStatsElements() {
    $totalRevenue = $('#totalRevenue');
    $totalUsers = $('#totalUsers');
    $totalProducts = $('#totalProducts');
    $pendingOrders = $('#pendingOrders');
}

/**
 * Loads admin dashboard statistics from the server
 * 
 * @returns {void}
 * 
 * @fires AJAX GET request to get-user-stats.php
 * @sideeffect Updates dashboard stat displays
 */
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

/** @type {jQuery|null} Pending verifications count element */
var $pendingVerifications = null;

/** @type {jQuery|null} Pending notice container */
var $pendingNotice = null;

/** @type {jQuery|null} Pending message element */
var $pendingMessage = null;

/**
 * Caches pending verification DOM elements
 * 
 * @returns {void}
 */
function cachePendingVerificationElements() {
    $pendingVerifications = $('#pendingVerifications');
    $pendingNotice = $('#pendingNotice');
    $pendingMessage = $('#pendingMessage');
}

/**
 * Loads and displays pending seller verifications
 * 
 * @returns {void}
 * 
 * @fires AJAX GET request to get-users.php?role=pending
 * @sideeffect Updates verification count and shows/hides notice
 */
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

/**
 * Loads recent users for admin dashboard
 * 
 * @returns {void}
 * 
 * @fires AJAX GET request to get-users.php?recent=true
 * @sideeffect Populates recent users table
 */
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
                $recentUsersTable.html('<tr><td colspan="4" class="loading-cell">No users found</td></tr>');
            }
        },
        error: function() {
            $recentUsersTable.html('<tr><td colspan="4" class="loading-cell">Error loading users</td></tr>');
        }
    });
}

/**
 * Loads recent orders for admin dashboard
 * 
 * @param {number} [limit=5] - Number of orders to load
 * @returns {void}
 * 
 * @fires AJAX GET request to get-recent-orders.php
 * @sideeffect Populates recent orders table
 */
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
                $recentOrdersTable.html('<tr><td colspan="5" class="loading-cell">No orders found</td></tr>');
            }
        },
        error: function() {
            $recentOrdersTable.html('</table><td colspan="5" class="loading-cell">Error loading orders</td></tr>');
        }
    });
}

/**
 * Loads all admin dashboard components
 * 
 * @returns {void}
 * 
 * @fires loadAdminStats, loadRecentUsers, loadRecentOrders, loadPendingVerifications
 */
function loadAdminDashboard() {
    loadAdminStats();
    loadRecentUsers();
    loadRecentOrders(5);
    loadPendingVerifications();
}

// ========== SELLER DASHBOARD FUNCTIONS ==========

/** @type {jQuery|null} Seller earnings stat element */
var $statEarnings = null;

/** @type {jQuery|null} Seller products stat element */
var $statProducts = null;

/** @type {jQuery|null} Seller pending orders stat element */
var $statPending = null;

/**
 * Caches seller stats DOM elements
 * 
 * @returns {void}
 */
function cacheSellerStatsElements() {
    $statEarnings = $('#stat-earnings');
    $statProducts = $('#stat-products');
    $statPending = $('#stat-pending');
}

/**
 * Loads seller dashboard statistics
 * 
 * @returns {void}
 * 
 * @fires AJAX GET request to get-user-stats.php?seller_id
 * @sideeffect Updates seller stat displays
 */
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

/**
 * Loads seller's recent orders
 * 
 * @param {number} [limit=5] - Number of orders to load
 * @returns {void}
 * 
 * @fires AJAX GET request to get-seller-recent-orders.php
 * @sideeffect Populates recent orders list
 */
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
                $recentOrdersList.html('<div class="empty-state"><p>No recent orders to display.</p></div>');
            }
        },
        error: function() {
            $recentOrdersList.html('<div class="error-message"><p>Error loading orders. Please refresh the page.</p></div>');
        }
    });
}

/**
 * Loads all seller dashboard components
 * 
 * @returns {void}
 * 
 * @fires loadSellerStats, loadSellerProducts, loadSellerRecentOrders
 */
function loadSellerDashboard() {
    loadSellerStats();
    if (typeof window.loadSellerProducts === 'function') {
        window.loadSellerProducts(4);
    }
    loadSellerRecentOrders(5);
}

// ========== MOBILE SIDEBAR TOGGLE ==========

/**
 * Initializes mobile sidebar toggle functionality for admin/seller dashboards
 * 
 * @param {string} prefix - Sidebar prefix ('admin' or 'seller')
 * @returns {void}
 * 
 * @sideeffect Binds click events for hamburger menu and close button
 */
function initMobileSidebar(prefix) {
    var $hamburger = $('#' + prefix + 'Hamburger');
    var $sideMenu = $('#' + prefix + 'SideMenu');
    var $overlay = $('#' + prefix + 'MenuOverlay');
    var $closeBtn = $('#' + prefix + 'SidebarClose');
    var $sidebarNavLinks = $('.' + prefix + '-sidebar-nav a, .' + prefix + '-sidebar-link');
    var $body = $('body');
    var $window = $(window);
    
    if (!$hamburger.length || !$sideMenu.length) return;
    
    /**
     * Opens the sidebar
     * 
     * @returns {void}
     */
    function openSidebar() {
        $sideMenu.addClass('active');
        if ($overlay.length) $overlay.addClass('active');
        $hamburger.css('opacity', '0').css('visibility', 'hidden');
        $body.css('overflow', 'hidden');
    }
    
    /**
     * Closes the sidebar
     * 
     * @returns {void}
     */
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

/** @type {jQuery|null} Admin side menu element */
var $adminSideMenu = null;

/** @type {jQuery|null} Admin menu overlay element */
var $adminMenuOverlay = null;

/** @type {jQuery|null} Seller side menu element */
var $sellerSideMenu = null;

/** @type {jQuery|null} Seller menu overlay element */
var $sellerMenuOverlay = null;

/**
 * Caches sidebar handler DOM elements
 * 
 * @returns {void}
 */
function cacheModalSidebarHandlerElements() {
    $adminSideMenu = $('#adminSideMenu');
    $adminMenuOverlay = $('#adminMenuOverlay');
    $sellerSideMenu = $('#sellerSideMenu');
    $sellerMenuOverlay = $('#sellerMenuOverlay');
}

/**
 * Initializes modal sidebar handler to manage sidebar state when modals open
 * 
 * @returns {void}
 * 
 * @sideeffect Tracks sidebar state and restores it after modal closes
 */
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

/**
 * Removes a gallery image from a product
 * 
 * @param {number} imageId - Gallery image ID
 * @param {number} productId - Product ID
 * @returns {void}
 * 
 * @fires AJAX POST request to remove-gallery-image.php
 * @sideeffect Fades out and removes the gallery item
 */
function removeGalleryImage(imageId, productId) {
    if (!confirm('Remove this image from the gallery? This action cannot be undone.')) {
        return;
    }
    
    var $galleryItem = $('.gallery-item[data-image-id="' + imageId + '"]');
    
    $.ajax({
        url: baseUrl + 'php/endpoints/remove-gallery-image.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            image_id: imageId,
            product_id: productId
        }),
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

/** @type {jQuery|null} Total users element for dashboard detection */
var $totalUsersElement = null;

/** @type {jQuery|null} Recent users table element for dashboard detection */
var $recentUsersTableElement = null;

/** @type {jQuery|null} Stat products element for dashboard detection */
var $statProductsElement = null;

/** @type {jQuery|null} Listings grid element for dashboard detection */
var $listingsGridElement = null;

/**
 * Caches dashboard page detection elements
 * 
 * @returns {void}
 */
function cacheDashboardPageElements() {
    $totalUsersElement = $('#totalUsers');
    $recentUsersTableElement = $('#recent-users-table');
    $statProductsElement = $('#stat-products');
    $listingsGridElement = $('#listings-grid');
}

/**
 * Initializes the dashboard based on page type
 * 
 * @returns {void}
 * 
 * @fires initMobileSidebar, initModalSidebarHandler
 * @fires loadAdminDashboard or loadSellerDashboard based on page elements
 */
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