/*
 * ConsuTrade - Seller Dashboard JavaScript
 * Author: Kamogelo Phale
 * 
 * This file handles seller dashboard specific functionality:
 * - Load seller statistics
 * - Load seller products
 * - Load recent orders
 * - Delete products
 * - Edit products
 * - Mobile sidebar toggle with span animation
 */

// ========== GLOBAL FUNCTIONS for onclick handlers ==========
// These need to be global so they can be called from dynamically created buttons

function editProduct(productId) {
    window.location.href = 'edit-product.php?id=' + productId;
}

function deleteProduct(productId) {
    var $btn = $('.delete-btn[onclick*="deleteProduct(' + productId + ')"]');
    
    // Prevent multiple clicks
    if ($btn.data('processing')) return;
    $btn.data('processing', true);
    
    if (confirm('Are you sure you want to delete this product?')) {
        $.ajax({
            url: '/www/consutrade/php/delete-product.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ product_id: productId }),
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    loadSellerProducts();
                } else {
                    alert('Error: ' + data.message);
                }
            },
            complete: function() {
                $btn.data('processing', false);
            }
        });
    } else {
        $btn.data('processing', false);
    }
}

 function loadSellerProducts() {
        var $grid = $('#listings-grid');
        if (!$grid.length) return;
        
        $grid.html('<div class="loading-spinner">Loading your products...</div>');
        
        $.ajax({
            url: '/www/consutrade/php/get-seller-products.php',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success && data.products && data.products.length > 0) {
                    displaySellerProducts(data.products);
                } else {
                    $grid.html(`
                        <div class="empty-listings">
                            <p>You haven't listed any products yet.</p>
                            <button class="add-listing-btn" onclick="window.location.href='add-product.php'">+ Add Your First Product</button>
                        </div>
                    `);
                }
            },
            error: function(xhr, status, error) {
                console.log('Error loading products:', error);
                $grid.html('<p class="error">Error loading products. Please refresh the page.</p>');
            }
        });
    }

function displaySellerProducts(products) {
    var $grid = $('#listings-grid');
    $grid.empty();
    
    var fragment = document.createDocumentFragment();
    
    $.each(products, function(index, product) {
        var imagePath = product.image;
        if (imagePath && !imagePath.startsWith('http') && !imagePath.startsWith('/')) {
            imagePath = '/www/consutrade/' + imagePath;
        }
        
        var card = $('<div>').addClass('listing-card');
        card.html(`
            <div class="listing-img">
                <img src="${imagePath}" alt="${escapeHtml(product.name)}" 
                     loading="lazy"
                     onerror="this.src='/www/consutrade/images/default-product.png'">
            </div>
            <div class="listing-info">
                <h3 class="listing-title">${escapeHtml(product.name)}</h3>
                <p class="listing-price">R ${parseFloat(product.price).toFixed(2)}</p>
                <p class="listing-status ${product.status}">${product.status}</p>
                <div class="listing-actions">
                    <button class="edit-btn" onclick="editProduct(${product.id})">Edit</button>
                    <button class="delete-btn" onclick="deleteProduct(${product.id})">Delete</button>
                </div>
            </div>
        `);
        
        fragment.appendChild(card[0]);
    });
    
    $grid.append(fragment);
}

$(document).ready(function() {
    
    // ========== MOBILE SIDEBAR TOGGLE with span animation ==========
    var $hamburger = $('#sellerHamburger');
    var $sideMenu = $('#sellerSideMenu');
    var $overlay = $('#sellerMenuOverlay');
    var $closeBtn = $('#sellerSidebarClose');
    
    function toggleSidebar() {
        // Toggle hamburger animation (turns into X)
        $hamburger.toggleClass('active');
        
        // Toggle sidebar visibility
        $sideMenu.toggleClass('active');
        
        // Toggle overlay
        $overlay.toggleClass('active');
        
        // Prevent body scroll when menu is open
        if ($sideMenu.hasClass('active')) {
            $('body').addClass('seller-menu-open');
            $('body').css('overflow', 'hidden');
        } else {
            $('body').removeClass('seller-menu-open');
            $('body').css('overflow', '');
        }
    }
    
    // Open/close with hamburger button
    $hamburger.on('click', toggleSidebar);
    
    // Close with close button (X) in sidebar
    $closeBtn.on('click', toggleSidebar);
    
    // Close with overlay click
    $overlay.on('click', toggleSidebar);
    
    // Close sidebar when clicking on a link (mobile only)
    $('.seller-sidebar-nav a, .seller-sidebar-link').on('click', function() {
        if ($(window).width() <= 768) {
            $hamburger.removeClass('active');
            $sideMenu.removeClass('active');
            $overlay.removeClass('active');
            $('body').removeClass('seller-menu-open');
            $('body').css('overflow', '');
        }
    });
    
    // ========== LOAD DASHBOARD STATS ==========
    function loadDashboardStats() {
        $.ajax({
            url: '/www/consutrade/php/get-user-stats.php',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    $('#stat-earnings').text('R' + parseFloat(data.total_earnings || 0).toFixed(2));
                    $('#stat-products').text(data.total_products || 0);
                    $('#stat-pending').text(data.pending_orders || 0);
                }
            },
            error: function(xhr, status, error) {
                console.log('Error loading dashboard stats:', error);
            }
        });
    }
    
    
    function loadRecentOrders() {
        var $ordersList = $('#recent-orders-list');
        if (!$ordersList.length) return;
        
        $.ajax({
            url: '/www/consutrade/php/get-recent-orders.php',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success && data.orders && data.orders.length > 0) {
                    $ordersList.empty();
                    $.each(data.orders, function(index, order) {
                        var orderItem = $('<div>').addClass('order-item');
                        orderItem.html(`
                            <p class="order-id">Order #${order.id}</p>
                            <p class="order-amount">R ${parseFloat(order.total).toFixed(2)}</p>
                            <p class="order-status ${order.status}">${order.status}</p>
                        `);
                        $ordersList.append(orderItem);
                    });
                } else {
                    $ordersList.html('<p class="placeholder-text">No recent orders to display.</p>');
                }
            },
            error: function(xhr, status, error) {
                console.log('Error loading orders:', error);
                $ordersList.html('<p class="placeholder-text">Error loading orders.</p>');
            }
        });
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        return $('<div>').text(text).html();
    }
    
    // Initial load
    loadDashboardStats();
    loadSellerProducts();
    loadRecentOrders();
});