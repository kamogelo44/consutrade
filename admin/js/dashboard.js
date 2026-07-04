// dashboard.js - admin and seller dashboard specific functions
// Author: Kamogelo Phale

var baseUrl = baseUrl || '';

// ============================================================
// DOM CACHE - Dashboard specific elements
// ============================================================

var $totalRevenue = null,
    $totalUsers = null,
    $totalProducts = null,
    $pendingOrders = null,
    $pendingVerifications = null,
    $flaggedReports = null,
    $pendingNotice = null,
    $pendingMessage = null,
    $flaggedReportsNotice = null,
    $flaggedReportsMessage = null,
    $recentUsersTable = null,
    $recentOrdersTable = null,
    $statEarnings = null,
    $statProducts = null,
    $statPending = null,
    $listingsGrid = null,
    $recentOrdersList = null;

function cacheDashboardElements() {
    $totalRevenue = $('#totalRevenue');
    $totalUsers = $('#totalUsers');
    $totalProducts = $('#totalProducts');
    $pendingOrders = $('#pendingOrders');
    $pendingVerifications = $('#pendingVerifications');
    $flaggedReports = $('#flaggedReports');
    $pendingNotice = $('#pendingNotice');
    $pendingMessage = $('#pendingMessage');
    $flaggedReportsNotice = $('#flaggedReportsNotice');
    $flaggedReportsMessage = $('#flaggedReportsMessage');
    $recentUsersTable = $('#recent-users-table');
    $recentOrdersTable = $('#recent-orders-table');
    $statEarnings = $('#stat-earnings');
    $statProducts = $('#stat-products');
    $statPending = $('#stat-pending');
    $listingsGrid = $('#listings-grid');
    $recentOrdersList = $('#recent-orders-list');
}

function getUserRoleClass(role) {
    switch(role) {
        case 'admin': return 'role-admin';
        case 'seller': return 'role-seller';
        case 'buyer': return 'role-buyer';
        default: return 'role-buyer';
    }
}

// ========== PRODUCT MANAGEMENT ==========

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
        url: baseUrl + 'php/endpoints/products/update-product-status.php',
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

window.deleteProduct = function(productId, productName, callback) {
    var confirmMsg = productName 
        ? 'Delete "' + productName + '"? This action cannot be undone.'
        : 'Delete this product? This action cannot be undone.';
    
    if (confirm(confirmMsg)) {
        $.ajax({
            url: baseUrl + 'php/endpoints/products/delete-product.php',
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

window.loadSellerProducts = function(limit) {
    if (!$listingsGrid || !$listingsGrid.length) return;
    
    $listingsGrid.html('<div class="loading-spinner">Loading your products...</div>');
    var url = baseUrl + 'php/endpoints/products/get-seller-products.php?seller_id=' + currentUserId;
    if (limit) url += '&limit=' + limit;
    
    $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success && data.products && data.products.length) {
                $listingsGrid.empty();
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
                    $listingsGrid.append($card);
                }
            } else {
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

// ========== ADMIN DASHBOARD ==========

function loadAdminDashboard() {
    if ($totalRevenue && $totalRevenue.length) $totalRevenue.text('Loading...');
    if ($totalUsers && $totalUsers.length) $totalUsers.text('Loading...');
    if ($totalProducts && $totalProducts.length) $totalProducts.text('Loading...');
    if ($pendingOrders && $pendingOrders.length) $pendingOrders.text('Loading...');
    if ($pendingVerifications && $pendingVerifications.length) $pendingVerifications.text('Loading...');
    if ($flaggedReports && $flaggedReports.length) $flaggedReports.text('Loading...');
    
    $.ajax({
        url: baseUrl + 'php/endpoints/users/get-user-stats.php',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                updateStats(data);
                updateNotices(data);
            } else {
                showErrorToast('Failed to load dashboard data');
            }
        },
        error: function() {
            showErrorToast('Error loading dashboard. Please refresh.');
        }
    });
    
    loadRecentUsers();
    loadRecentOrders(5);
}

function updateStats(data) {
    if ($totalRevenue && $totalRevenue.length) $totalRevenue.text('R ' + (data.total_revenue || 0).toFixed(2));
    if ($totalUsers && $totalUsers.length) $totalUsers.text(data.total_users || 0);
    if ($totalProducts && $totalProducts.length) $totalProducts.text(data.total_products || 0);
    if ($pendingOrders && $pendingOrders.length) $pendingOrders.text(data.pending_orders || 0);
    if ($pendingVerifications && $pendingVerifications.length) $pendingVerifications.text(data.pending_verifications || 0);
    if ($flaggedReports && $flaggedReports.length) $flaggedReports.text(data.pending_reports || 0);
}

function updateNotices(data) {
    if (data.pending_verifications > 0) {
        if ($pendingNotice && $pendingNotice.length) {
            $pendingNotice.show();
            if ($pendingMessage && $pendingMessage.length) {
                $pendingMessage.html(
                    '<strong>' + data.pending_verifications + '</strong> seller(s) waiting for document verification.'
                );
            }
        }
    } else {
        if ($pendingNotice && $pendingNotice.length) $pendingNotice.hide();
    }
    
    if (data.pending_reports > 0) {
        if ($flaggedReportsNotice && $flaggedReportsNotice.length) {
            $flaggedReportsNotice.show();
            if ($flaggedReportsMessage && $flaggedReportsMessage.length) {
                $flaggedReportsMessage.text(
                    data.pending_reports + ' product ' + 
                    (data.pending_reports == 1 ? 'report requires' : 'reports require') + 
                    ' your attention.'
                );
            }
        }
    } else {
        if ($flaggedReportsNotice && $flaggedReportsNotice.length) $flaggedReportsNotice.hide();
    }
}

function loadRecentUsers() {
    if (!$recentUsersTable || !$recentUsersTable.length) return;
    
    $recentUsersTable.html('<tr><td colspan="4" class="loading-cell">Loading...</td></tr>');
    
    $.ajax({
        url: baseUrl + 'php/endpoints/users/get-users.php?recent=true&limit=5',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success && data.users && data.users.length) {
                $recentUsersTable.empty();
                for (var i = 0; i < data.users.length; i++) {
                    var user = data.users[i];
                    var roleClass = getUserRoleClass(user.role);
                    $recentUsersTable.append(
                        '<tr>' +
                            '<td>' + escapeHtml(user.full_name) + '</td>' +
                            '<td>' + escapeHtml(user.email) + '</td>' +
                            '<td><span class="role-badge ' + roleClass + '">' + 
                                capitalizeFirst(user.role) + 
                            '</span></td>' +
                            '<td>' + escapeHtml(user.created_at) + '</td>' +
                        '</tr>'
                    );
                }
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
    if (!$recentOrdersTable || !$recentOrdersTable.length) return;
    
    limit = limit || 5;
    $recentOrdersTable.html('<tr><td colspan="5" class="loading-cell">Loading...</td></tr>');
    
    $.ajax({
        url: baseUrl + 'php/endpoints/orders/get-recent-orders.php?limit=' + limit,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success && data.orders && data.orders.length) {
                $recentOrdersTable.empty();
                for (var i = 0; i < data.orders.length; i++) {
                    var order = data.orders[i];
                    var statusClass = getOrderStatusClass(order.status);
                    $recentOrdersTable.append(
                        '<tr onclick="viewOrder(' + order.id + ')" style="cursor: pointer;">' +
                            '<td>#' + order.id + '</td>' +
                            '<td>' + escapeHtml(order.buyer_name || 'Guest') + '</td>' +
                            '<td>R ' + parseFloat(order.total).toFixed(2) + '</td>' +
                            '<td><span class="order-status-badge ' + statusClass + '">' + 
                                capitalizeFirst(order.status) + 
                            '</span></td>' +
                            '<td>' + escapeHtml(order.created_at) + '</td>' +
                        '</tr>'
                    );
                }
            } else {
                $recentOrdersTable.html('<tr><td colspan="5" class="empty-cell">No orders found</td></tr>');
            }
        },
        error: function() {
            $recentOrdersTable.html('<tr><td colspan="5" class="error-cell">Error loading orders</td></tr>');
        }
    });
}

// ========== SELLER DASHBOARD ==========

function loadSellerStats() {
    $.ajax({
        url: baseUrl + 'php/endpoints/users/get-user-stats.php?seller_id=' + currentUserId,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                if ($statEarnings && $statEarnings.length) $statEarnings.text('R ' + parseFloat(data.total_revenue || 0).toFixed(2));
                if ($statProducts && $statProducts.length) $statProducts.text(data.total_products || 0);
                if ($statPending && $statPending.length) $statPending.text(data.pending_orders || 0);
            } else {
                showErrorToast('Failed to load seller stats');
            }
        },
        error: function() {
            showErrorToast('Error loading seller stats');
        }
    });
}

function loadSellerRecentOrders(limit) {
    if (!$recentOrdersList || !$recentOrdersList.length) return;
    
    limit = limit || 5;
    $recentOrdersList.html('<div class="loading-spinner">Loading recent orders...</div>');
    
    $.ajax({
        url: baseUrl + 'php/endpoints/orders/get-seller-recent-orders.php?limit=' + limit,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success && data.orders && data.orders.length > 0) {
                $recentOrdersList.empty();
                for (var i = 0; i < data.orders.length; i++) {
                    var order = data.orders[i];
                    var statusClass = getOrderStatusClass(order.status);
                    var productNames = order.product_names || '';
                    if (productNames.length > 40) {
                        productNames = productNames.substring(0, 37) + '...';
                    }
                    $recentOrdersList.append(
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

// ============================================================
// GALLERY FUNCTIONS
// ============================================================

var selectedFiles = [];
var existingImages = [];
var newImageFiles = [];
var newImagePreviews = [];
var allDisplayImages = [];

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
    
    var compressor = new ImageCompressor({
        maxWidth: 1200,
        maxHeight: 1200,
        quality: 0.8,
        format: 'image/webp'
    });
    
    // Show compression progress
    $('#compression-progress').show();
    $('#compression-progress-text').text('Compressing ' + files.length + ' images...');
    $('#compression-progress-bar').css('width', '30%');
    
    compressor.compressMultiple(files).then(function(compressedFiles) {
        $('#compression-progress').hide();
        
        // Check if all files are WebP
        var allWebP = true;
        for (var i = 0; i < compressedFiles.length; i++) {
            if (compressedFiles[i].type !== 'image/webp') {
                allWebP = false;
                break;
            }
        }
        
        if (!allWebP) {
            showWarningToast('Some images could not be converted to WebP. Using original format.');
        }
        
        for (var i = 0; i < compressedFiles.length; i++) {
            var file = compressedFiles[i];
            var previewUrl = URL.createObjectURL(file);
            
            newImageFiles.push(file);
            newImagePreviews.push(previewUrl);
            
            allDisplayImages.push({
                file: file,
                previewUrl: previewUrl,
                is_primary: (i === 0),
                is_existing: false,
                source: 'new',
                url: previewUrl
            });
        }
        
        buildGalleryThumbnails();
        displayMainImage(0);
        $('#image-gallery-container').show();
        updateImageCounter();
        
        showSuccessToast(compressedFiles.length + ' image(s) ready!');
    }).catch(function(error) {
        console.error('Compression error:', error);
        $('#compression-progress').hide();
        showErrorToast('Failed to compress images. Please try again.');
    });
}

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
    
    var primaryIndex = 0;
    for (var i = 0; i < allDisplayImages.length; i++) {
        if (allDisplayImages[i].is_primary) {
            primaryIndex = i;
            break;
        }
    }
    displayMainImage(primaryIndex);
    $('#image-gallery-container').show();
    updateImageCounter();
}

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
            
            var imgSrc = baseUrl + 'images/default-product.png';
            if (img.source === 'existing') {
                imgSrc = img.url || baseUrl + 'images/default-product.png';
            } else if (img.previewUrl) {
                imgSrc = img.previewUrl;
            } else if (img.url) {
                imgSrc = img.url;
            }
            
            var $thumbnail = $(
                '<div class="gallery-thumb" data-image-index="' + index + '" style="cursor: pointer; width: 80px; text-align: center; position: relative;">' +
                    '<img src="' + imgSrc + '" style="width: 100%; height: 80px; object-fit: cover; border-radius: var(--radius-md); border: 2px solid ' + borderColor + ';" ' +
                    'onerror="this.onerror=null; this.src=\'' + baseUrl + 'images/default-product.png\'">' +
                    '<div style="font-size: 10px; margin-top: 4px; color: ' + textColor + ';">' + label + '</div>' +
                    '<div class="remove-image-btn" onclick="event.stopPropagation(); removeImageFromGallery(' + index + ')">×</div>' +
                '</div>'
            );
            $container.append($thumbnail);
        })(i);
    }
    
    attachThumbnailClickHandlers();
}

function attachThumbnailClickHandlers() {
    var $container = $('#gallery-thumbnails');
    if (!$container.length) return;
    
    $container.off('click', '.gallery-thumb').on('click', '.gallery-thumb', function(e) {
        if ($(e.target).hasClass('remove-image-btn') || $(e.target).parent().hasClass('remove-image-btn')) {
            return;
        }
        
        var index = $(this).data('image-index');
        
        for (var i = 0; i < allDisplayImages.length; i++) {
            allDisplayImages[i].is_primary = (i === index);
        }
        
        buildGalleryThumbnails();
        displayMainImage(index);
        updateImageCounter();
    });
}

function displayMainImage(index) {
    var $mainPreview = $('#gallery-main-preview');
    if (!$mainPreview.length) return;
    
    if (allDisplayImages.length === 0) {
        $mainPreview.attr('src', baseUrl + 'images/default-product.png');
        $mainPreview.attr('alt', 'No images uploaded');
        return;
    }
    
    var img = allDisplayImages[index];
    if (!img) {
        $mainPreview.attr('src', baseUrl + 'images/default-product.png');
        $mainPreview.attr('alt', 'No images uploaded');
        return;
    }
    
    var imgSrc = baseUrl + 'images/default-product.png';
    if (img.source === 'existing') {
        imgSrc = img.url || baseUrl + 'images/default-product.png';
    } else if (img.previewUrl) {
        imgSrc = img.previewUrl;
    } else if (img.url) {
        imgSrc = img.url;
    }
    
    $mainPreview.attr('src', imgSrc);
    $mainPreview.attr('alt', 'Product image');
}

function removeImageFromGallery(index) {
    var img = allDisplayImages[index];
    
    if (img.source === 'existing') {
        if (!window.imagesToDelete) window.imagesToDelete = [];
        if (img.image_id > 0 && window.imagesToDelete.indexOf(img.image_id) === -1) {
            window.imagesToDelete.push(img.image_id);
        }
    } else {
        var fileIndex = -1;
        for (var j = 0; j < newImageFiles.length; j++) {
            if (newImageFiles[j] === img.file) {
                fileIndex = j;
                break;
            }
        }
        if (fileIndex !== -1) {
            newImageFiles.splice(fileIndex, 1);
            newImagePreviews.splice(fileIndex, 1);
        }
    }
    
    allDisplayImages.splice(index, 1);
    
    if (allDisplayImages.length === 0) {
        $('#gallery-thumbnails').empty();
        $('#gallery-main-preview').attr('src', baseUrl + 'images/default-product.png');
        $('#image-gallery-container').hide();
        updateImageCounter();
        return;
    }
    
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
    
    buildGalleryThumbnails();
    
    var primaryIndex = 0;
    for (var i = 0; i < allDisplayImages.length; i++) {
        if (allDisplayImages[i].is_primary) {
            primaryIndex = i;
            break;
        }
    }
    displayMainImage(primaryIndex);
    updateImageCounter();
}

function addNewImagesToGallery(input) {
    var files = Array.from(input.files);
    var currentCount = allDisplayImages.length;
    var maxAllowed = 4;
    var availableSlots = maxAllowed - currentCount;
    
    if (availableSlots <= 0) {
        var replaceConfirm = confirm('You already have 4 images. Do you want to replace existing images with new ones?');
        if (!replaceConfirm) {
            return;
        }
        
        if (files.length > maxAllowed) {
            alert('You can only upload up to ' + maxAllowed + ' images.');
            files = files.slice(0, maxAllowed);
        }
        
        for (var i = 0; i < allDisplayImages.length; i++) {
            if (allDisplayImages[i].source === 'existing' && allDisplayImages[i].image_id > 0) {
                if (!window.imagesToDelete) window.imagesToDelete = [];
                if (window.imagesToDelete.indexOf(allDisplayImages[i].image_id) === -1) {
                    window.imagesToDelete.push(allDisplayImages[i].image_id);
                }
            }
        }
        
        allDisplayImages = [];
        compressAndAddImages(files);
        return;
    }
    
    if (files.length > availableSlots) {
        alert('You can only add up to ' + availableSlots + ' more images (max 4 total).');
        files = files.slice(0, availableSlots);
    }
    
    compressAndAddImages(files);
}

function compressAndAddImages(files) {
    if (!files || files.length === 0) {
        return;
    }
    
    var compressor = new ImageCompressor({
        maxWidth: 1200,
        maxHeight: 1200,
        quality: 0.8,
        format: 'image/webp'
    });
    
    compressor.compressMultiple(files).then(function(compressedFiles) {
        for (var i = 0; i < compressedFiles.length; i++) {
            var file = compressedFiles[i];
            var previewUrl = URL.createObjectURL(file);
            
            newImageFiles.push(file);
            newImagePreviews.push(previewUrl);
            
            allDisplayImages.push({
                file: file,
                previewUrl: previewUrl,
                is_primary: (allDisplayImages.length === 0),
                is_existing: false,
                source: 'new',
                url: previewUrl
            });
        }
        
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
        
        var primaryIndex = 0;
        for (var i = 0; i < allDisplayImages.length; i++) {
            if (allDisplayImages[i].is_primary) {
                primaryIndex = i;
                break;
            }
        }
        displayMainImage(primaryIndex);
        updateImageCounter();
    });
}

function updateImageCounter() {
    var $counter = $('#image-counter');
    if (!$counter.length) return;
    
    var currentCount = allDisplayImages.length;
    var maxAllowed = 4;
    var availableSlots = maxAllowed - currentCount;
    
    if (availableSlots > 0) {
        $counter.text('You can add up to ' + availableSlots + ' new images.');
    } else {
        $counter.text('Maximum 4 images reached. You can replace existing images.');
    }
}

function prepareEditFormData() {
    var imageOrderData = [];
    
    $('#edit-product-form input[name="image_order"]').remove();
    $('#edit-product-form input[name="delete_images"]').remove();
    
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
            var fileIndex = -1;
            for (var j = 0; j < newImageFiles.length; j++) {
                if (newImageFiles[j] === img.file) {
                    fileIndex = j;
                    break;
                }
            }
            if (fileIndex === -1) {
                fileIndex = i;
            }
            item.file_index = fileIndex;
        }
        
        imageOrderData.push(item);
    }
    
    var orderInput = $('<input type="hidden" name="image_order" value="' + JSON.stringify(imageOrderData).replace(/"/g, '&quot;') + '">');
    $('#edit-product-form').append(orderInput);
    
    if (window.imagesToDelete && window.imagesToDelete.length) {
        var deleteInput = $('<input type="hidden" name="delete_images" value="' + JSON.stringify(window.imagesToDelete).replace(/"/g, '&quot;') + '">');
        $('#edit-product-form').append(deleteInput);
    }
    
    return true;
}

// ============================================================
// INITIALIZATION
// ============================================================

// ============================================================
// DOCUMENT READY & FORM INTERCEPTION
// ============================================================

$(function() {
    // Cache dashboard elements
    cacheDashboardElements();
    
    // Initialize mobile sidebars
    initMobileSidebar('admin');
    initMobileSidebar('seller');
    
    // Load dashboards based on present fields
    if ($totalUsers && $totalUsers.length || ($recentUsersTable && $recentUsersTable.length)) {
        loadAdminDashboard();
    }
    if ($statProducts && $statProducts.length || ($listingsGrid && $listingsGrid.length)) {
        loadSellerDashboard();
    }
    
    // ============================================================
    // INTERCEPT EDIT PRODUCT FORM SUBMISSION FOR COMPRESSION
    // ============================================================
    if ($('#edit-product-form').length) {
        $('#edit-product-form').on('submit', async function(e) {
            const fileInput = $('input[name="new_product_images[]"]')[0];
            
            // 1. If no files are chosen, append order/deletion arrays and submit normally
            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                return prepareEditFormData();
            }

            // 2. Prevent infinite loops once compression has successfully completed
            if ($(this).data('compressed') === true) {
                return prepareEditFormData(); 
            }

            // 3. Stop the immediate synchronous form upload
            e.preventDefault();

            // 4. Provide visual feedback during processing
            const $submitBtn = $(this).find('button[type="submit"]');
            const originalBtnText = $submitBtn.text();
            $submitBtn.prop('disabled', true).text('Compressing Images (WebP)...');

            try {
                // 5. Instantiate your fixed compression utility
                const compressor = new ImageCompressor({
                    maxWidth: 1200,
                    maxHeight: 1200,
                    quality: 0.8
                });

                // Convert FileList to a manageable array
                const originalFiles = Array.from(fileInput.files);
                
                // 6. Await the compression pipeline loop
                const compressedFiles = await compressor.compressMultiple(originalFiles);

                // 7. Use DataTransfer to swap the raw files with compressed WebP binaries
                const dataTransfer = new DataTransfer();
                compressedFiles.forEach(file => {
                    dataTransfer.items.add(file);
                });

                // Seamlessly overwrite the input's native file list
                fileInput.files = dataTransfer.files;

                // Mark form state tracking as ready
                $(this).data('compressed', true);

                // 8. Run your existing sorting index allocations
                prepareEditFormData();

                // 9. Call the native DOM submit method to bypass jQuery event listeners
                this.submit();

            } catch (error) {
                console.error('Client-side compression pipeline encountered a failure:', error);
                $submitBtn.prop('disabled', false).text(originalBtnText);
                alert('An error occurred while converting your images. Please try uploading again.');
            }
        });
    }

    // ============================================================
    // INTERCEPT ADD PRODUCT FORM SUBMISSION (IF APPLICABLE)
    // ============================================================
    if ($('#add-product-form').length) {
        $('#add-product-form').on('submit', async function(e) {
            const fileInput = $('input[name="product_images[]"]')[0];
            
            if (!fileInput || !fileInput.files || fileInput.files.length === 0) return true;
            if ($(this).data('compressed') === true) return true;

            e.preventDefault();
            const $submitBtn = $(this).find('button[type="submit"]');
            const originalBtnText = $submitBtn.text();
            $submitBtn.prop('disabled', true).text('Processing Images...');

            try {
                const compressor = new ImageCompressor({ maxWidth: 1200, maxHeight: 1200, quality: 0.8 });
                const compressedFiles = await compressor.compressMultiple(Array.from(fileInput.files));

                const dataTransfer = new DataTransfer();
                compressedFiles.forEach(file => dataTransfer.items.add(file));
                fileInput.files = dataTransfer.files;

                $(this).data('compressed', true);
                this.submit();
            } catch (error) {
                console.error('Compression pipeline failed:', error);
                $submitBtn.prop('disabled', false).text(originalBtnText);
            }
        });
    }
});