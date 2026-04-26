/*
 * ConsuTrade - Unified Dashboard JavaScript
 * Author: Kamogelo Phale
 * 
 * Handles both Admin and Seller dashboards
 * All AJAX endpoints now point to admin/php/
 */

// ========== GLOBAL VARIABLES ==========
var baseUrl = baseUrl || '';

// ========== GLOBAL FUNCTIONS (defined before document ready) ==========

function editProduct(productId) {
    if (productId) {
        window.location.href = baseUrl + 'admin/edit-product.php?id=' + productId;
    }
}

function deleteProduct(productId) {
    if (!productId) return;
    
    if (confirm('Are you sure you want to delete this product?')) {
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
                    if (typeof window.loadSellerProducts === 'function') {
                        window.loadSellerProducts();
                    } else {
                        location.reload();
                    }
                } else {
                    alert('Error: ' + data.message);
                    $btn.prop('disabled', false).text('Delete');
                }
            },
            error: function() {
                alert('Something went wrong. Please try again.');
                $btn.prop('disabled', false).text('Delete');
            }
        });
    }
}

// ========== SELLER PRODUCTS FUNCTION (global for reload) ==========
window.loadSellerProducts = function() {
    var $grid = $('#listings-grid, #products-grid');
    if (!$grid.length) return;
    
    $grid.html('<div class="loading-spinner">Loading your products...</div>');
    
    $.ajax({
        url: baseUrl + 'admin/php/get-seller-products.php',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success && data.products && data.products.length) {
                displayProducts($grid, data.products);
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

function displayProducts($grid, products) {
    $grid.empty();
    
    $.each(products, function(i, product) {
        var imagePath = product.image;
        if (imagePath && !imagePath.startsWith('http') && !imagePath.startsWith('/') && !imagePath.startsWith(baseUrl)) {
            imagePath = baseUrl + imagePath;
        }
        
        var card = $('<div>').addClass('listing-card product-card').attr('data-product-id', product.id);
        card.html(
            '<div class="listing-img product-image">' +
            '<img src="' + (imagePath || baseUrl + 'images/default-product.png') + '" alt="' + escapeHtml(product.name) + '" loading="lazy" onerror="this.src=\'' + baseUrl + 'images/default-product.png\'">' +
            '</div>' +
            '<div class="listing-info product-details">' +
            '<h3 class="listing-title product-title">' + escapeHtml(product.name) + '</h3>' +
            '<p class="listing-price product-price">R ' + parseFloat(product.price).toFixed(2) + '</p>' +
            '<p class="listing-status ' + product.status + '">' + product.status + '</p>' +
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
                setText('totalProducts', data.total_products || 0);
                setText('totalOrders', data.total_orders || 0);
                setText('pendingOrders', data.pending_orders || 0);
            }
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
                        '<table>' +
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

function loadRecentOrders() {
    var $tbody = $('#recent-orders-table');
    if (!$tbody.length) return;
    
    $.ajax({
        url: baseUrl + 'admin/php/get-recent-orders.php',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success && data.orders && data.orders.length) {
                $tbody.empty();
                $.each(data.orders, function(i, order) {
                    $tbody.append(
                        '<tr>' +
                        '<td>#' + order.order_id + '</td>' +
                        '<td>' + escapeHtml(order.buyer_name) + '</td>' +
                        '<td>R ' + parseFloat(order.total_price).toFixed(2) + '</td>' +
                        '<td><span class="status-badge status-' + order.status + '">' + capitalizeFirst(order.status) + '</span></td>' +
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
            }
        }
    });
}

function loadSellerRecentOrders() {
    var $ordersList = $('#recent-orders-list');
    if (!$ordersList.length) return;
    
    $.ajax({
        url: baseUrl + 'admin/php/get-seller-recent-orders.php',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success && data.orders && data.orders.length) {
                $ordersList.empty();
                $.each(data.orders, function(i, order) {
                    $ordersList.append(
                        '<div class="order-item">' +
                        '<p class="order-id">Order #' + order.id + '</p>' +
                        '<p class="order-amount">R ' + parseFloat(order.total).toFixed(2) + '</p>' +
                        '<p class="order-status ' + order.status + '">' + order.status + '</p>' +
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
    window.loadSellerProducts();
    loadSellerRecentOrders();
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
    
    // Hamburger opens sidebar
    $hamburger.on('click', openSidebar);
    
    // Close button (X) closes sidebar
    if ($closeBtn.length) $closeBtn.on('click', closeSidebar);
    
    // Overlay closes sidebar
    if ($overlay.length) $overlay.on('click', closeSidebar);
    
    // Close sidebar when clicking a link on mobile
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

// ========== DOCUMENT READY ==========
$(document).ready(function() {
    initMobileSidebar('admin');
    initMobileSidebar('seller');
    initUserDropdown();
    
    // Check which dashboard we're on and load appropriate data
    if ($('#totalUsers').length) {
        loadAdminDashboard();
    }
    
    if ($('#stat-products').length || $('#listings-grid').length) {
        loadSellerDashboard();
    }
});