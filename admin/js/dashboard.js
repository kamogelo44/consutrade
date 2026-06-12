// dashboard.js - admin and seller dashboard specific functions
// Author: Kamogelo Phale
// Note: main.js is already loaded on all pages, so utility functions like 
// escapeHtml, fixImageUrl, showSuccessToast, renderPagination, etc. are already available.
// This file only contains dashboard-specific functionality.

var baseUrl = baseUrl || '';

/* ========== Helpers for dashboard ========== */
function getUserRoleClass(role) {
    switch(role) {
        case 'admin': return 'role-admin';
        case 'seller': return 'role-seller';
        case 'buyer': return 'role-buyer';
        default: return 'role-buyer';
    }
}

// ========== PRODUCT MANAGEMENT ==========

// Toggle product status (suspend/activate)
window.toggleProductStatus = function(productId, currentStatus, callback) {
    var newStatus = currentStatus == 'active' ? 'suspended' : 'active';
    var action = newStatus == 'active' ? 'activate' : 'suspend';
    var confirmMsg = action == 'suspend' 
        ? 'Suspend this product? It will be hidden from buyers.' 
        : 'Activate this product? It will be visible to buyers.';
    
    if (!confirm(confirmMsg)) return;
    
    var requestData = { product_id: productId, status: newStatus };
    
    if (action == 'suspend') {
        var reason = prompt('Reason for suspension (optional):', '');
        if (reason !== null && reason.trim() != '') {
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
                if (typeof callback == 'function') {
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

// Delete a product
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
                    if (typeof callback == 'function') {
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

// Order action wrappers - these call updateOrderStatus from main.js
window.processOrder = function(orderId) { 
    updateOrderStatus(orderId, 'processing');
};

window.shipOrder = function(orderId) { 
    updateOrderStatus(orderId, 'shipped');
};

window.completeOrder = function(orderId) { 
    updateOrderStatus(orderId, 'completed');
};

window.cancelOrder = function(orderId) { 
    updateOrderStatus(orderId, 'cancelled');
};

window.editProduct = function(productId) {
    if (productId) {
        window.location.href = baseUrl + 'admin/edit-product.php?id=' + productId;
    }
};

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

// ========== SELLER PRODUCTS ==========

// Load seller's products (for seller dashboard)
window.loadSellerProducts = function(limit) {
    var $grid = $('#listings-grid');
    if (!$grid.length) return;
    
    $grid.html('<div class="loading-spinner">Loading your products...</div>');
    var url = baseUrl + 'php/endpoints/get-seller-products.php?seller_id=' + currentUserId;
    if (limit) url += '&limit=' + limit;
    
    $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success && data.products && data.products.length) {
                $grid.empty();
                for (var i = 0; i < data.products.length; i++) {
                    var product = data.products[i];
                    var imagePath = fixImageUrl(product.display_image || product.image);
                    var $card = $('<div>').addClass('product-card');
                    $card.html(
                        '<div class="product-image">' +
                            '<img src="' + imagePath + '" alt="' + escapeHtml(product.name) + '" onerror="this.src=\'' + baseUrl + 'images/default-product.png\'">' +
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
                    $grid.append($card);
                }
            } else {
                $grid.html(getEmptyStateHTML(
                    'product-catalog-svgrepo-com.svg',
                    'No products found',
                    'You haven\'t listed any products yet.',
                    'Add Your First Product',
                    baseUrl + 'admin/add-product.php'
                ));
            }
        },
        error: function() {
            $grid.html('<div class="error-message"><p>Error loading products. Please refresh the page.</p></div>');
        }
    });
};

// ========== ADMIN DASHBOARD ==========

function loadAdminStats() {
    $.ajax({
        url: baseUrl + 'php/endpoints/get-user-stats.php',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                $('#totalRevenue').text('R ' + (data.total_revenue || 0).toFixed(2));
                $('#totalUsers').text(data.total_users || 0);
                $('#totalProducts').text(data.total_products || 0);
                $('#pendingOrders').text(data.pending_orders || 0);
            }
        }
    });
}

function loadFlaggedReportsCount() {
    var $flaggedReports = $('#flaggedReports');
    if (!$flaggedReports.length) return;
    
    $.ajax({
        url: baseUrl + 'php/endpoints/get-flagged-listings.php',
        type: 'GET',
        dataType: 'json',
        data: { page: 1, limit: 1 },
        success: function(data) {
            if (data.success) {
                var count = data.total || 0;
                $flaggedReports.text(count);
                
                if (count > 0) {
                    $('#flaggedReportsNotice').show();
                    $('#flaggedReportsMessage').text(count + ' product ' + (count == 1 ? 'report requires' : 'reports require') + ' your attention.');
                } else {
                    $('#flaggedReportsNotice').hide();
                }
            } else {
                $flaggedReports.text('0');
            }
        },
        error: function() {
            $flaggedReports.text('0');
        }
    });
}

function loadPendingVerifications() {
    var $pendingVerifications = $('#pendingVerifications');
    
    $.ajax({
        url: baseUrl + 'php/endpoints/get-users.php?role=pending',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                var count = data.users ? data.users.length : 0;
                $pendingVerifications.text(count);
                if (count > 0) {
                    $('#pendingNotice').show();
                    $('#pendingMessage').html('<strong>' + count + '</strong> seller(s) waiting for document verification.');
                } else {
                    $('#pendingNotice').hide();
                }
            }
        },
        error: function() {
            $pendingVerifications.text('0');
        }
    });
}

function loadRecentUsers() {
    var $table = $('#recent-users-table');
    if (!$table.length) return;
    
    $.ajax({
        url: baseUrl + 'php/endpoints/get-users.php?recent=true',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success && data.users && data.users.length) {
                $table.empty();
                for (var i = 0; i < data.users.length; i++) {
                    var user = data.users[i];
                    var roleClass = getUserRoleClass(user.role);
                    $table.append(
                        '<tr>' +
                            '<td>' + escapeHtml(user.full_name) + '</td>' +
                            '<td>' + escapeHtml(user.email) + '</td>' +
                            '<td><span class="role-badge ' + roleClass + '">' + capitalizeFirst(user.role) + '</span></td>' +
                            '<td>' + escapeHtml(user.created_at) + '</td>' +
                        '</tr>'
                    );
                }
            } else {
                $table.html('<tr><td colspan="4" class="empty-cell">No users found</td></tr>');
            }
        },
        error: function() {
            $table.html('<tr><td colspan="4" class="error-cell">Error loading users</td></tr>');
        }
    });
}

function loadRecentOrders(limit) {
    limit = limit || 5;
    var $table = $('#recent-orders-table');
    if (!$table.length) return;
    
    $.ajax({
        url: baseUrl + 'php/endpoints/get-recent-orders.php?limit=' + limit,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success && data.orders && data.orders.length) {
                $table.empty();
                for (var i = 0; i < data.orders.length; i++) {
                    var order = data.orders[i];
                    var statusClass = getOrderStatusClass(order.status);
                    $table.append(
                        '<tr onclick="viewOrder(' + order.id + ')" style="cursor: pointer;">' +
                            '<td>#' + order.id + '</td>' +
                            '<td>' + escapeHtml(order.buyer_name) + '</td>' +
                            '<td>R ' + parseFloat(order.total).toFixed(2) + '</td>' +
                            '<td><span class="order-status-badge ' + statusClass + '">' + capitalizeFirst(order.status) + '</span></td>' +
                            '<td>' + escapeHtml(order.created_at) + '</td>' +
                        '</tr>'
                    );
                }
            } else {
                $table.html('<tr><td colspan="5" class="empty-cell">No orders found</td></tr>');
            }
        },
        error: function() {
            $table.html('<tr><td colspan="5" class="error-cell">Error loading orders</td></tr>');
        }
    });
}

function loadAdminDashboard() {
    loadAdminStats();
    loadRecentUsers();
    loadRecentOrders(5);
    loadPendingVerifications();
    loadFlaggedReportsCount();
}

// ========== SELLER DASHBOARD ==========

function loadSellerStats() {
    $.ajax({
        url: baseUrl + 'php/endpoints/get-user-stats.php?seller_id=' + currentUserId,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                $('#stat-earnings').text('R ' + parseFloat(data.total_revenue || 0).toFixed(2));
                $('#stat-products').text(data.total_products || 0);
                $('#stat-pending').text(data.pending_orders || 0);
            }
        }
    });
}

function loadSellerRecentOrders(limit) {
    limit = limit || 5;
    var $list = $('#recent-orders-list');
    if (!$list.length) return;
    
    $.ajax({
        url: baseUrl + 'php/endpoints/get-seller-recent-orders.php?limit=' + limit,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success && data.orders && data.orders.length > 0) {
                $list.empty();
                for (var i = 0; i < data.orders.length; i++) {
                    var order = data.orders[i];
                    var statusClass = getOrderStatusClass(order.status);
                    var productNames = order.product_names || '';
                    if (productNames.length > 40) {
                        productNames = productNames.substring(0, 37) + '...';
                    }
                    $list.append(
                        '<div class="order-item" onclick="viewOrder(' + order.id + ')">' +
                            '<div class="order-info">' +
                                '<span class="order-number">#' + order.id + '</span>' +
                                '<span class="order-status-badge ' + statusClass + '">' + capitalizeFirst(order.status) + '</span>' +
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
                }
            } else {
                $list.html(getEmptyStateHTML(
                    'shopping-cart-01-svgrepo-com.svg',
                    'No recent orders',
                    'You haven\'t received any orders yet.',
                    'Browse Products',
                    baseUrl + 'product-listings.php'
                ));
            }
        },
        error: function() {
            $list.html('<div class="error-message"><p>Error loading orders. Please refresh the page.</p></div>');
        }
    });
}

function loadSellerDashboard() {
    loadSellerStats();
    window.loadSellerProducts(4);
    loadSellerRecentOrders(5);
}

// ========== MOBILE SIDEBAR ==========

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

// ========== GALLERY FUNCTIONS (for add-product and edit-product pages) ==========

// Variables for image gallery
var selectedFiles = [];
var existingImages = [];
var newImageFiles = [];
var newImagePreviews = [];
var allDisplayImages = [];

/**
 * Initialize gallery from file input (for add product form)
 */
function initImageGalleryFromInput(input) {
    var files = Array.from(input.files);
    
    if (files.length > 4) {
        alert('You can only upload up to 4 images. Only the first 4 will be used.');
        files = files.slice(0, 4);
        input.files = files;
    }
    
    selectedFiles = files;
    allDisplayImages = [];
    newImageFiles = [];
    newImagePreviews = [];
    
    if (files.length === 0) {
        $('#image-gallery-container').hide();
        return;
    }
    
    // Build allDisplayImages from selected files
    for (var i = 0; i < files.length; i++) {
        var previewUrl = URL.createObjectURL(files[i]);
        newImagePreviews.push(previewUrl);
        newImageFiles.push(files[i]);
        
        allDisplayImages.push({
            file: files[i],
            previewUrl: previewUrl,
            is_primary: (i === 0),  // First image is primary by default
            is_existing: false,
            source: 'new'
        });
    }
    
    buildGalleryThumbnails();
    displayMainImage(0);
    $('#image-gallery-container').show();
}

/**
 * Initialize gallery from existing images (for edit product form)
 */
function initImageGalleryFromExisting(images) {
    existingImages = images;
    allDisplayImages = [];
    
    for (var i = 0; i < existingImages.length; i++) {
        allDisplayImages.push({
            url: existingImages[i].url,
            is_primary: existingImages[i].is_primary,
            image_id: existingImages[i].image_id,
            is_existing: true,
            source: 'existing'
        });
    }
    
    // Ensure at least one image is primary
    var hasPrimary = false;
    for (var i = 0; i < allDisplayImages.length; i++) {
        if (allDisplayImages[i].is_primary) {
            hasPrimary = true;
            break;
        }
    }
    if (!hasPrimary && allDisplayImages.length > 0) {
        allDisplayImages[0].is_primary = true;
    }
    
    buildGalleryThumbnails();
    
    // Find and display primary image
    var primaryIndex = 0;
    for (var i = 0; i < allDisplayImages.length; i++) {
        if (allDisplayImages[i].is_primary) {
            primaryIndex = i;
            break;
        }
    }
    displayMainImage(primaryIndex);
    $('#image-gallery-container').show();
}

/**
 * Build thumbnails for the gallery
 */
function buildGalleryThumbnails() {
    var $container = $('#gallery-thumbnails');
    if (!$container.length) return;
    
    $container.empty();
    
    for (var i = 0; i < allDisplayImages.length; i++) {
        (function(index) {
            var img = allDisplayImages[index];
            var isPrimary = img.is_primary;
            var borderColor = isPrimary ? 'var(--primary-color)' : 'var(--border-light)';
            var textColor = isPrimary ? 'var(--primary-color)' : 'var(--gray-medium)';
            var label = isPrimary ? 'Main' : 'Gallery';
            
            // Get image source (existing URL or preview URL)
            var imgSrc = img.source === 'existing' ? img.url : (img.previewUrl || '');
            
            var $thumbnail = $(
                '<div class="gallery-thumb" data-image-index="' + index + '" style="cursor: pointer; width: 80px; text-align: center; position: relative;">' +
                    '<img src="' + imgSrc + '" style="width: 100%; height: 80px; object-fit: cover; border-radius: var(--radius-md); border: 2px solid ' + borderColor + ';">' +
                    '<div style="font-size: 10px; margin-top: 4px; color: ' + textColor + ';">' + label + '</div>' +
                    '<div class="remove-image-btn" onclick="event.stopPropagation(); removeImageFromGallery(' + index + ')">×</div>' +
                '</div>'
            );
            $container.append($thumbnail);
        })(i);
    }
    
    attachThumbnailClickHandlers();
}

/**
 * Attach click handlers to thumbnails
 */
function attachThumbnailClickHandlers() {
    $('#gallery-thumbnails').off('click', '.gallery-thumb').on('click', '.gallery-thumb', function(e) {
        // Don't trigger if clicking remove button
        if ($(e.target).hasClass('remove-image-btn') || $(e.target).parent().hasClass('remove-image-btn')) {
            return;
        }
        
        var index = $(this).data('image-index');
        
        // Update primary status for all images
        for (var i = 0; i < allDisplayImages.length; i++) {
            allDisplayImages[i].is_primary = (i === index);
        }
        
        // Rebuild thumbnails to update labels
        buildGalleryThumbnails();
        
        // Update main preview
        displayMainImage(index);
    });
}

/**
 * Display main image in the large preview area
 */
function displayMainImage(index) {
    var img = allDisplayImages[index];
    if (!img) return;
    
    if (img.source === 'existing') {
        $('#gallery-main-preview').attr('src', img.url);
    } else if (img.previewUrl) {
        $('#gallery-main-preview').attr('src', img.previewUrl);
    }
}

/**
 * Remove image from gallery
 */
function removeImageFromGallery(index) {
    var img = allDisplayImages[index];
    
    if (img.source === 'existing') {
        // Mark for deletion (for edit product form)
        if (!window.imagesToDelete) window.imagesToDelete = [];
        if (img.image_id > 0 && window.imagesToDelete.indexOf(img.image_id) === -1) {
            window.imagesToDelete.push(img.image_id);
        }
    } else {
        // Remove from newImageFiles if it was a new image
        var fileIndex = newImageFiles.indexOf(img.file);
        if (fileIndex !== -1) {
            newImageFiles.splice(fileIndex, 1);
            newImagePreviews.splice(fileIndex, 1);
        }
    }
    
    // Remove from array
    allDisplayImages.splice(index, 1);
    
    // If no images left
    if (allDisplayImages.length === 0) {
        $('#gallery-thumbnails').empty();
        $('#gallery-main-preview').attr('src', '');
        $('#image-gallery-container').hide();
        return;
    }
    
    // Ensure at least one primary exists
    var hasPrimary = false;
    for (var i = 0; i < allDisplayImages.length; i++) {
        if (allDisplayImages[i].is_primary) {
            hasPrimary = true;
            break;
        }
    }
    if (!hasPrimary) {
        allDisplayImages[0].is_primary = true;
    }
    
    // Rebuild gallery
    buildGalleryThumbnails();
    
    // Find and display primary image
    var primaryIndex = 0;
    for (var i = 0; i < allDisplayImages.length; i++) {
        if (allDisplayImages[i].is_primary) {
            primaryIndex = i;
            break;
        }
    }
    displayMainImage(primaryIndex);
}

/**
 * Add new images to gallery (for edit product form)
 */
function addNewImagesToGallery(input) {
    var files = Array.from(input.files);
    var currentCount = allDisplayImages.length;
    var availableSlots = 4 - currentCount;
    
    if (files.length > availableSlots) {
        alert('You can only add up to ' + availableSlots + ' more images (max 4 total).');
        files = files.slice(0, availableSlots);
    }
    
    for (var i = 0; i < files.length; i++) {
        var previewUrl = URL.createObjectURL(files[i]);
        
        newImageFiles.push(files[i]);
        newImagePreviews.push(previewUrl);
        
        allDisplayImages.push({
            file: files[i],
            previewUrl: previewUrl,
            is_primary: false,
            is_existing: false,
            source: 'new'
        });
    }
    
    // If no primary exists, set first as primary
    var hasPrimary = false;
    for (var i = 0; i < allDisplayImages.length; i++) {
        if (allDisplayImages[i].is_primary) {
            hasPrimary = true;
            break;
        }
    }
    if (!hasPrimary && allDisplayImages.length > 0) {
        allDisplayImages[0].is_primary = true;
    }
    
    buildGalleryThumbnails();
    
    // Find and display primary image
    var primaryIndex = 0;
    for (var i = 0; i < allDisplayImages.length; i++) {
        if (allDisplayImages[i].is_primary) {
            primaryIndex = i;
            break;
        }
    }
    displayMainImage(primaryIndex);
    
    // Clear the file input
    input.value = '';
}

/**
 * Prepare form data before submit (for edit product form)
 */
function prepareEditFormData() {
    var imageOrderData = [];
    
    for (var i = 0; i < allDisplayImages.length; i++) {
        var img = allDisplayImages[i];
        var item = {
            is_primary: img.is_primary
        };
        
        if (img.source === 'existing') {
            item.image_id = img.image_id;
            item.is_existing = true;
        } else {
            item.is_new = true;
            // Find the index of this file in newImageFiles
            var fileIndex = newImageFiles.indexOf(img.file);
            item.file_index = fileIndex;
        }
        
        imageOrderData.push(item);
    }
    
    // Add image_order as JSON
    var orderInput = $('<input type="hidden" name="image_order" value="' + JSON.stringify(imageOrderData).replace(/"/g, '&quot;') + '">');
    $('#edit-product-form').append(orderInput);
    
    // Add delete_images as JSON
    if (window.imagesToDelete && window.imagesToDelete.length) {
        var deleteInput = $('<input type="hidden" name="delete_images" value="' + JSON.stringify(window.imagesToDelete).replace(/"/g, '&quot;') + '">');
        $('#edit-product-form').append(deleteInput);
    }
    
    return true;
}

// ========== DOCUMENT READY ==========

$(function() {
    // Initialize mobile sidebars
    initMobileSidebar('admin');
    initMobileSidebar('seller');
    
    // Load admin dashboard if on admin page
    if ($('#totalUsers').length || $('#recent-users-table').length) {
        loadAdminDashboard();
    }
    
    // Load seller dashboard if on seller page
    if ($('#stat-products').length || $('#listings-grid').length) {
        loadSellerDashboard();
    }
    
    // Handle edit product form submission
    if ($('#edit-product-form').length) {
        $('#edit-product-form').on('submit', function() {
            return prepareEditFormData();
        });
    }
});