/**
 * ConsuTrade - Core JavaScript
 * Author: Kamogelo Phale
 * 
 * Core functionality shared across all pages.
 * Handles: Authentication modals, cart operations, toast notifications, DOM utilities.
 * Used on: All pages
 */

var baseUrl = baseUrl || '';

// Cache DOM references for better performance
var $toastContainer = null;
var $registerModal = null;
var $loginModal = null;
var $deleteModal = null;
var $bodyElement = null;
var $cartCountElements = null;

// ========== DOM UTILITIES ==========

/**
 * Escapes user input to prevent XSS attacks
 * 
 * @param {string} text - The text to escape
 * @returns {string} Escaped HTML safe for insertion
 */
function escapeHtml(text) {
    if (!text) return '';
    return $('<div>').text(text).html();
}

/**
 * Capitalizes the first letter of a string
 * 
 * @param {string} str - The string to capitalize
 * @returns {string} Capitalized string
 */
function capitalizeFirst(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

/**
 * Maps database status values to CSS classes
 * Aligns with components.css styles
 */
function getOrderStatusClass(status) {
    if (status == 'pending') return 'status-pending';
    if (status == 'processing') return 'status-processing';
    if (status == 'shipped') return 'status-shipped';
    if (status == 'completed') return 'status-completed';
    if (status == 'cancelled') return 'status-cancelled';
    return '';
}

/**
 * Human-readable status labels for UI display
 */
function getStatusLabel(status) {
    if (status == 'pending') return 'Pending';
    if (status == 'processing') return 'Processing';
    if (status == 'shipped') return 'Shipped';
    if (status == 'completed') return 'Completed';
    if (status == 'cancelled') return 'Cancelled';
    return capitalizeFirst(status);
}

/**
 * Fixes mixed absolute/relative image paths
 * Handles: full URLs, relative paths, uploads folder, and fallback
 */
function fixImageUrl(url, defaultPath) {
    defaultPath = defaultPath || 'images/default-product.png';
    if (!url || url == '') return baseUrl + defaultPath;
    
    if (url.startsWith('http://') || url.startsWith('https://')) return url;
    
    var cleanUrl = url.startsWith('/') ? url.substring(1) : url;
    
    if (cleanUrl.startsWith('uploads/') || cleanUrl.startsWith('images/')) {
        return baseUrl + cleanUrl;
    }
    
    var uploadsIndex = cleanUrl.indexOf('uploads/');
    if (uploadsIndex !== -1) {
        return baseUrl + cleanUrl.substring(uploadsIndex);
    }
    
    var imagesIndex = cleanUrl.indexOf('images/');
    if (imagesIndex !== -1) {
        return baseUrl + cleanUrl.substring(imagesIndex);
    }
    
    if (!cleanUrl.includes('/')) {
        return baseUrl + 'uploads/products/' + cleanUrl;
    }
    
    return baseUrl + defaultPath;
}

/**
 * Generates empty state HTML when no data exists
 */
function getEmptyStateHTML(icon, title, message, buttonText, buttonLink) {
    var html = '<div class="empty-state">' +
        '<img src="' + baseUrl + 'images/icons/' + icon + '" width="64" height="64" alt="' + escapeHtml(title) + '">' +
        '<h3>' + escapeHtml(title) + '</h3>' +
        '<p>' + escapeHtml(message) + '</p>';
    
    if (buttonText && buttonLink) {
        html += '<a href="' + buttonLink + '" class="view-all-btn">' + escapeHtml(buttonText) + '</a>';
    }
    
    html += '</div>';
    return html;
}

/**
 * Renders pagination controls with ellipsis for large page sets
 */
function renderPagination($container, currentPage, totalPages, onPageChange) {
    if (!$container.length || totalPages <= 1) {
        $container.empty();
        return;
    }

    var html = '';
    
    if (currentPage > 1) {
        html += '<button class="page-btn" data-page="' + (currentPage - 1) + '">← Previous</button>';
    }

    // Show first page, current page neighborhood, and last page
    for (var i = 1; i <= totalPages; i++) {
        if (i == currentPage) {
            html += '<button class="page-btn active" disabled>' + i + '</button>';
        } else if (Math.abs(i - currentPage) <= 2 || i == 1 || i == totalPages) {
            html += '<button class="page-btn" data-page="' + i + '">' + i + '</button>';
        } else if (Math.abs(i - currentPage) == 3) {
            html += '<span class="page-dots">...</span>';
        }
    }

    if (currentPage < totalPages) {
        html += '<button class="page-btn" data-page="' + (currentPage + 1) + '">Next →</button>';
    }

    $container.html(html);
    
    $container.find('.page-btn[data-page]').off('click').on('click', function() {
        var page = parseInt($(this).data('page'));
        if (!isNaN(page) && typeof onPageChange == 'function') {
            onPageChange(page);
        }
    });
}

// ========== TOAST NOTIFICATIONS ==========

/**
 * Shows user feedback without intrusive popups
 * Auto-dismisses after 4 seconds to prevent UI clutter
 * Supports: success, error, warning, info
 */
function showToast(message, type) {
    type = type || 'success';
    
    $('.toast-notification').remove();
    
    if (!$toastContainer) {
        $toastContainer = $('<div class="toast-container"></div>');
        $('body').append($toastContainer);
    }
    
    var toast = $(
        '<div class="toast-notification toast-' + type + '">' +
            '<div class="toast-message">' + escapeHtml(message) + '</div>' +
        '</div>'
    );
    
    $toastContainer.append(toast);
    
    setTimeout(function() {
        toast.addClass('hiding');
        setTimeout(function() { toast.remove(); }, 300);
    }, 4000);
}

// Cleaner wrappers for different toast types
function showSuccessToast(message) { showToast(message, 'success'); }
function showErrorToast(message) { showToast(message, 'error'); }
function showInfoToast(message) { showToast(message, 'info'); }
function showWarningToast(message) { showToast(message, 'warning'); }

// ========== PASSWORD TOGGLE ==========

/**
 * Toggles password visibility - helps users avoid typos
 */
function togglePassword(fieldId, button) {
    var $input = $('#' + fieldId);
    var $img = $(button).find('img');
    
    if ($input.attr('type') == 'password') {
        $input.attr('type', 'text');
        $img.attr('src', baseUrl + 'images/icons/eye-close-svgrepo-com.svg');
        $img.attr('alt', 'Hide password');
    } else {
        $input.attr('type', 'password');
        $img.attr('src', baseUrl + 'images/icons/eye-open-svgrepo-com.svg');
        $img.attr('alt', 'Show password');
    }
}

// ========== ORDER DETAILS MODAL ==========

/**
 * Opens order details modal and fetches data via AJAX
 * Single modal component used by buyers, sellers, and admins
 * Action buttons change based on user role and order status
 */
function openOrderModal(orderId) {
    var $modal = $('#orderModal');
    var $modalBody = $('#orderModalBody');
    var $modalFooter = $('#orderModalFooter');
    
    if (!$modal.length) {
        console.warn('Order modal not found in DOM');
        return;
    }
    
    $modal.addClass('active');
    $modalBody.html('<div class="loading-spinner">Loading order details...</div>');
    $modalFooter.empty();
    
    $.ajax({
        url: baseUrl + 'php/endpoints/get-order-details.php?order_id=' + orderId,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success && data.order) {
                var order = data.order;
                var isAdmin = window.location.pathname.includes('all-orders.php') || window.location.pathname.includes('admin-dashboard.php');
                
                var itemsHtml = '';
                if (order.items && order.items.length > 0) {
                    for (var i = 0; i < order.items.length; i++) {
                        var item = order.items[i];
                        var imagePath = fixImageUrl(item.image_url);
                        itemsHtml += 
                            '<div class="order-item">' +
                                '<div class="order-item-img">' +
                                    '<img src="' + imagePath + '" onerror="this.src=\'' + baseUrl + 'images/default-product.png\'">' +
                                '</div>' +
                                '<div class="order-item-details">' +
                                    '<h4>' + escapeHtml(item.product_name) + '</h4>' +
                                    '<p>Quantity: ' + item.quantity + '</p>' +
                                '</div>' +
                                '<div class="order-item-price">R ' + parseFloat(item.price).toFixed(2) + '</div>' +
                            '</div>';
                    }
                }
                
                $modalBody.html(
                    '<div class="order-info-section">' +
                        '<div class="info-row"><span class="info-label">Order Number:</span><span class="info-value">#' + order.order_id + '</span></div>' +
                        '<div class="info-row"><span class="info-label">Order Date:</span><span class="info-value">' + order.created_at + '</span></div>' +
                        '<div class="info-row"><span class="info-label">Order Status:</span><span class="info-value status-' + order.status + '">' + (order.status ? order.status.toUpperCase() : 'UNKNOWN') + '</span></div>' +
                        (isAdmin ? 
                            '<div class="info-row"><span class="info-label">Customer:</span><span class="info-value">' + escapeHtml(order.buyer_name || order.other_party_name || 'N/A') + '</span></div>' +
                            '<div class="info-row"><span class="info-label">Seller:</span><span class="info-value">' + escapeHtml(order.seller_name || order.other_party_name || 'N/A') + '</span></div>' : 
                            '<div class="info-row"><span class="info-label">' + (order.buyer_name ? 'Seller' : 'Customer') + ':</span><span class="info-value">' + escapeHtml(order.other_party_name) + '</span></div>') +
                        (order.shipping_address ? '<div class="info-row"><span class="info-label">Shipping Address:</span><span class="info-value">' + escapeHtml(order.shipping_address) + '</span></div>' : '') +
                    '</div>' +
                    '<h4>Order Items</h4>' +
                    '<div class="order-items-list">' + (itemsHtml || '<p>No items found.</p>') + '</div>' +
                    '<div class="order-total-section">' +
                        '<div class="total-row"><span>Subtotal:</span><span>R ' + parseFloat(order.subtotal || 0).toFixed(2) + '</span></div>' +
                        '<div class="total-row"><span>Delivery Fee:</span><span>R ' + parseFloat(order.delivery_fee || 0).toFixed(2) + '</span></div>' +
                        '<div class="total-row grand-total"><span>Total:</span><span>R ' + parseFloat(order.total || 0).toFixed(2) + '</span></div>' +
                    '</div>'
                );
                
                // Status-specific action buttons
                var actionButtons = '';
                if (order.status == 'pending') {
                    actionButtons = '<button class="process-btn" onclick="updateOrderStatus(' + order.order_id + ', \'processing\'); closeOrderModal();">Process Order</button>';
                } else if (order.status == 'processing') {
                    actionButtons = '<button class="ship-btn" onclick="updateOrderStatus(' + order.order_id + ', \'shipped\'); closeOrderModal();">Mark as Shipped</button>';
                } else if (order.status == 'shipped') {
                    actionButtons = '<button class="complete-btn" onclick="updateOrderStatus(' + order.order_id + ', \'completed\'); closeOrderModal();">Mark as Completed</button>';
                }
                if (order.status == 'pending' || order.status == 'processing') {
                    actionButtons += '<button class="cancel-btn" onclick="updateOrderStatus(' + order.order_id + ', \'cancelled\'); closeOrderModal();">Cancel Order</button>';
                }
                
                $modalFooter.html(actionButtons);
            } else {
                $modalBody.html('<p class="error">Unable to load order details.</p>');
            }
        },
        error: function() {
            $modalBody.html('<p class="error">Error loading order details.</p>');
        }
    });
}

function closeOrderModal() {
    $('#orderModal').removeClass('active');
}

/**
 * Updates order status with confirmation
 * Used by admin and seller dashboards
 * Business rule: status transitions follow pending → processing → shipped → completed
 */
function updateOrderStatus(orderId, newStatus) {
    var confirmMsg = 'Are you sure you want to ' + newStatus + ' this order?';
    if (newStatus == 'cancelled') {
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
                    showSuccessToast(data.message || 'Order status updated successfully!');
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    alert('Error: ' + (data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('Something went wrong. Please try again.');
            }
        });
    }
}

// ========== ORDER LISTING FUNCTIONS ==========

/**
 * Fetches orders and renders them in the table
 * Endpoint changes depending on who's logged in
 * Used by buyers, sellers, and admins
 */
function loadOrders(endpoint, $container, $pagination, page, status, search, userRole, onPageChange) {
    $container.html('<tr><td colspan="8"><div class="loading-spinner">Loading orders...</div></td></tr>');
    
    $.ajax({
        url: baseUrl + endpoint,
        type: 'GET',
        dataType: 'json',
        data: { page: page, status: status, search: search },
        success: function(data) {
            if (data.success && data.orders && data.orders.length) {
                renderOrdersTable(data.orders, $container, userRole);
                if (typeof renderPagination === 'function') {
                    renderPagination($pagination, page, data.total_pages, function(newPage) {
                        if (typeof onPageChange === 'function') onPageChange(newPage);
                    });
                }
            } else {
                showOrdersEmptyState($container, status, search, userRole);
                $pagination.empty();
            }
        },
        error: function() {
            $container.html('<tr><td colspan="8"><div class="error-cell">Failed to load orders. Try refreshing.</div></td></tr>');
        }
    });
}

/**
 * Renders orders with role-specific action buttons
 * Buyers: cancel only
 * Sellers: process, ship, complete, cancel
 * Admins: all actions
 */
function renderOrdersTable(orders, $container, userRole) {
    $container.empty();
    
    for (var i = 0; i < orders.length; i++) {
        var order = orders[i];
        var statusClass = getOrderStatusClass(order.status);
        var statusLabel = getStatusLabel(order.status);
        
        var buttons = '<div class="action-buttons">';
        buttons += '<button class="action-btn view-btn" onclick="openOrderModal(' + order.order_id + ')">View</button>';
        
        if (userRole === 'seller') {
            if (order.status === 'pending') {
                buttons += '<button class="action-btn process-btn" onclick="updateOrderStatus(' + order.order_id + ', \'processing\')">Process</button>';
            } else if (order.status === 'processing') {
                buttons += '<button class="action-btn ship-btn" onclick="updateOrderStatus(' + order.order_id + ', \'shipped\')">Ship</button>';
            } else if (order.status === 'shipped') {
                buttons += '<button class="action-btn complete-btn" onclick="updateOrderStatus(' + order.order_id + ', \'completed\')">Complete</button>';
            }
            if (order.status === 'pending' || order.status === 'processing') {
                buttons += '<button class="action-btn cancel-btn" onclick="updateOrderStatus(' + order.order_id + ', \'cancelled\')">Cancel</button>';
            }
        }
        
        if (userRole === 'buyer') {
            if (order.status === 'pending' || order.status === 'processing') {
                buttons += '<button class="action-btn cancel-btn" onclick="updateOrderStatus(' + order.order_id + ', \'cancelled\')">Cancel Order</button>';
            }
            // Review button for completed orders
            if (order.status === 'completed') {
                if (order.has_review) {
                    buttons += '<button class="action-btn edit-review-btn" onclick="openEditReviewModal(' + order.order_id + ', ' + order.seller_id + ', \'' + escapeHtml(order.seller_name) + '\', ' + order.review_rating + ', \'' + escapeHtml(order.review_comment).replace(/'/g, "\\'") + '\')">Edit Review</button>';
                } else {
                    buttons += '<button class="action-btn review-btn" onclick="openReviewModal(' + order.order_id + ', ' + order.seller_id + ', \'' + escapeHtml(order.seller_name) + '\')">Write a Review</button>';
                }
            }
        }
        
        if (userRole === 'admin') {
            if (order.status === 'pending') {
                buttons += '<button class="action-btn process-btn" onclick="updateOrderStatus(' + order.order_id + ', \'processing\')">Process</button>';
            } else if (order.status === 'processing') {
                buttons += '<button class="action-btn ship-btn" onclick="updateOrderStatus(' + order.order_id + ', \'shipped\')">Ship</button>';
            } else if (order.status === 'shipped') {
                buttons += '<button class="action-btn complete-btn" onclick="updateOrderStatus(' + order.order_id + ', \'completed\')">Complete</button>';
            }
            if (order.status === 'pending' || order.status === 'processing') {
                buttons += '<button class="action-btn cancel-btn" onclick="updateOrderStatus(' + order.order_id + ', \'cancelled\')">Cancel</button>';
            }
        }
        
        buttons += '</div>';
        
        var row = '<tr>';
        row += '<td data-label="Order #">#' + order.order_id + '</td>';
        row += '<td data-label="Customer">' + escapeHtml(order.buyer_name) + '</td>';
        
        if (userRole === 'admin') {
            row += '<td data-label="Seller">' + escapeHtml(order.seller_name) + '</td>';
        }
        
        row += '<td data-label="Items">' + (order.item_count || 0) + '</td>';
        row += '<td data-label="Amount">R ' + parseFloat(order.total_price).toFixed(2) + '</td>';
        row += '<td data-label="Status"><span class="order-status-badge ' + statusClass + '">' + statusLabel + '</span></td>';
        row += '<td data-label="Date">' + escapeHtml(order.created_at) + '</td>';
        row += '<td data-label="Actions">' + buttons + '</td>';
        row += '</tr>';
        
        $container.append(row);
    }
}

/**
 * Shows empty state for orders with role-specific messaging
 */
function showOrdersEmptyState($container, status, search, userRole) {
    var title = '';
    var message = '';
    
    if (status !== 'all') {
        title = 'No ' + capitalizeFirst(status) + ' Orders';
        if (userRole === 'buyer') {
            message = 'You don\'t have any ' + status + ' orders' + (search ? ' matching "' + escapeHtml(search) + '"' : '') + '.';
        } else if (userRole === 'seller') {
            message = 'No ' + status + ' orders from customers' + (search ? ' matching "' + escapeHtml(search) + '"' : '') + '.';
        } else {
            message = 'No ' + status + ' orders found' + (search ? ' matching "' + escapeHtml(search) + '"' : '') + '.';
        }
    } else if (search !== '') {
        title = 'No Orders Found';
        message = 'No orders matching "' + escapeHtml(search) + '" were found.';
    } else {
        title = 'No Orders Yet';
        if (userRole === 'buyer') {
            message = 'You haven\'t placed any orders yet.';
        } else if (userRole === 'seller') {
            message = 'You haven\'t received any orders yet.';
        } else {
            message = 'No orders have been placed on the platform yet.';
        }
    }
    
    var resetHtml = '';
    if (search !== '' || status !== 'all') {
        resetHtml = '<button class="reset-filters-btn view-all-btn" style="background: var(--primary-color); color: white; padding: 10px 24px; border-radius: 8px; border: none; cursor: pointer; margin-top: 16px;">Clear Filters</button>';
    }
    
    $container.html(
        '<tr><td colspan="8" style="text-align: center; padding: 60px;">' +
        '<div class="empty-state">' +
        '<img src="' + baseUrl + 'images/icons/shopping-cart-01-svgrepo-com.svg" width="64" height="64" alt="No orders" style="opacity: 0.4;">' +
        '<h3>' + escapeHtml(title) + '</h3>' +
        '<p>' + escapeHtml(message) + '</p>' +
        resetHtml +
        '</div>' +
        '</td></tr>'
    );
}

// ========== MODAL ERROR HANDLING ==========

/**
 * Clears validation errors from auth modals
 */
function clearModalErrors(modalId) {
    var $modal = $(modalId);
    $modal.find('.error-container').hide().empty();
    $modal.find('.input-group').removeClass('error');
    $modal.find('.error-text').remove();
}

/**
 * Displays validation errors from server response
 */
function displayModalErrors(modalId, errors, formData) {
    var $modal = $(modalId);
    if (!formData) formData = {};
    
    var $registerFullName = $('#register-full-name');
    var $registerEmail = $('#register-email');
    var $registerPhone = $('#register-phone');
    var $registerErrorContainer = $('#register-error-container');
    var $loginEmail = $('#login-email');
    var $loginErrorContainer = $('#login-error-container');
    
    if (modalId == '#register-modal') {
        if (formData.full_name) $registerFullName.val(formData.full_name);
        if (formData.email) $registerEmail.val(formData.email);
        if (formData.phone) $registerPhone.val(formData.phone);
        
        $registerErrorContainer.hide().empty();
        $('.input-group', $modal).removeClass('error');
        $('.error-text', $modal).remove();
        
        var errorMessages = [];
        if (errors.general && errors.general.trim()) {
            errorMessages.push(errors.general);
        }
        
        for (var field in errors) {
            var message = errors[field];
            if (field != 'general' && message && message.trim()) {
                errorMessages.push(message);
                
                var inputId = '';
                if (field == 'full_name') inputId = 'register-full-name';
                else if (field == 'email') inputId = 'register-email';
                else if (field == 'phone') inputId = 'register-phone';
                else if (field == 'password') inputId = 'register-password';
                else if (field == 'confirm_password') inputId = 'register-confirm-password';
                else inputId = 'register-' + field;
                
                var $input = $('#' + inputId);
                if ($input.length) {
                    $input.closest('.input-group').addClass('error');
                }
            }
        }
        
        if (errorMessages.length > 0) {
            $registerErrorContainer.show().html(errorMessages.join('<br>'));
        }
        
    } else if (modalId == '#login-modal') {
        if (formData.email) $loginEmail.val(formData.email);
        
        $loginErrorContainer.hide().empty();
        $('.input-group', $modal).removeClass('error');
        
        if (errors.general && errors.general.trim()) {
            $loginErrorContainer.show().text(errors.general);
        } else if (typeof errors == 'string' && errors.trim()) {
            $loginErrorContainer.show().text(errors);
        }
        
        for (var field2 in errors) {
            var msg = errors[field2];
            if (field2 != 'general' && msg && msg.trim()) {
                var $input2 = $('#login-' + field2);
                if ($input2.length) {
                    $input2.closest('.input-group').addClass('error');
                }
            }
        }
    }
}

function clearLoginErrors() {
    $('#login-error-container').hide().empty();
    $('#login-form .input-group').removeClass('error');
    $('#login-form .error-text').remove();
}

function clearRegisterErrors() {
    $('#register-error-container').hide().empty();
    $('#register-form .input-group').removeClass('error');
    $('#register-form .error-text').remove();
}

// ========== CART OPERATIONS ==========

/**
 * Cache cart elements to avoid repeated DOM queries
 */
function getCartCountElements() {
    if (!$cartCountElements) {
        $cartCountElements = $('.cart-count, .item-num, .cart-badge, .mobile-cart-count');
    }
    return $cartCountElements;
}

/**
 * Updates cart badge across all locations on the page
 * Caches in sessionStorage for faster subsequent loads
 */
function updateCartCountDisplay(count) {
    getCartCountElements().text(count);
    if (window.sessionStorage) sessionStorage.setItem('cart_count', count);
}

/**
 * Adds product to cart via AJAX
 * Used by product cards and detail pages
 */
function addToCart(productId, productName, productPrice) {
    $.ajax({
        url: baseUrl + 'php/endpoints/add-to-cart.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ product_id: productId, product_name: productName, product_price: productPrice }),
        success: function(data) {
            if (data.success) {
                updateCartCountDisplay(data.cart_count || 0);
                showSuccessToast(data.message || 'Item added to cart');
            } else {
                showErrorToast(data.message || 'Error adding item to cart');
            }
        },
        error: function() { showErrorToast('Something went wrong'); }
    });
}

/**
 * Removes item from cart with confirmation
 */
function removeFromCart(productId) {
    if (!confirm('Are you sure you want to remove this item from your cart?')) return;
    
    $.ajax({
        url: baseUrl + 'php/endpoints/remove-from-cart.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ product_id: productId }),
        success: function(data) {
            if (data.success) {
                updateCartCountDisplay(data.cart_count || 0);
                if (window.location.pathname.includes('cart.php')) {
                    refreshCart();
                } else {
                    showSuccessToast(data.message || 'Item removed from cart');
                }
            } else {
                showErrorToast(data.message || 'Error removing item from cart');
            }
        },
        error: function() { showErrorToast('Something went wrong'); }
    });
}

// ========== CART PAGE FUNCTIONS ==========

/**
 * Loads cart page using pre-loaded PHP data
 * Avoids extra AJAX call on initial load
 */
function loadCartPage() {
    if (!window.location.pathname.includes('cart.php')) return;
    
    if (typeof initialCartData === 'undefined') {
        console.warn('initialCartData not defined - cart may not load properly');
        $('#cart-layout').hide();
        $('#empty-cart').show();
        return;
    }
    
    if (!initialCartData.items) {
        console.warn('initialCartData.items is missing');
        $('#cart-layout').hide();
        $('#empty-cart').show();
        return;
    }
    
    if (initialCartData.items.length > 0) {
        displayCartItems(initialCartData);
        updateCartTotalsDisplay(initialCartData);
        updateCartCountDisplay(initialCartData.items.length);
    } else {
        $('#cart-layout').hide();
        $('#empty-cart').show();
    }
}

/**
 * Updates the order summary section with current totals
 */
function updateCartTotalsDisplay(cartData) {
    $('.sub-total-val').text('R ' + parseFloat(cartData.subtotal).toFixed(2));
    $('.deliv-fee-val').text('R ' + parseFloat(cartData.delivery_fee).toFixed(2));
    $('.total-val').text('R ' + parseFloat(cartData.total).toFixed(2));
}

/**
 * Refreshes cart via AJAX after quantity updates or removals
 */
function refreshCart() {
    if (!window.location.pathname.includes('cart.php')) return;
    
    $.ajax({
        url: baseUrl + 'php/endpoints/get-cart.php',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success && data.items) {
                displayCartItems(data);
                updateCartTotalsDisplay(data);
                updateCartCountDisplay(data.item_count);
                sessionStorage.setItem('cart_count', data.item_count);
            } else if (data.success && (!data.items || data.items.length === 0)) {
                $('#cart-layout').hide();
                $('#empty-cart').show();
                updateCartCountDisplay(0);
            } else {
                showErrorToast('Failed to load cart data');
            }
        },
        error: function() {
            showErrorToast('Failed to refresh cart');
        }
    });
}

/**
 * Updates cart badge from server for non-cart pages
 */
function updateCartCount() {
    if (!isLoggedIn || currentUserRole !== 'buyer') {
        updateCartCountDisplay(0);
        return;
    }
    
    var cachedCount = sessionStorage.getItem('cart_count');
    if (cachedCount && !isNaN(parseInt(cachedCount))) {
        updateCartCountDisplay(parseInt(cachedCount));
        // Background refresh to ensure accuracy
        $.get(baseUrl + 'php/endpoints/get-cart.php', function(data) {
            if (data.success && data.item_count !== parseInt(cachedCount)) {
                updateCartCountDisplay(data.item_count);
                sessionStorage.setItem('cart_count', data.item_count);
            }
        });
    } else {
        $.get(baseUrl + 'php/endpoints/get-cart.php', function(data) {
            if (data.success) {
                updateCartCountDisplay(data.item_count);
                sessionStorage.setItem('cart_count', data.item_count);
            }
        });
    }
}

/**
 * Renders cart items in both desktop table and mobile card views
 */
function displayCartItems(cartData) {
    var $desktopTableBody = $('#cart-table-body');
    var $mobileContainer = $('#mobile-cart-items');
    var $emptyCartDiv = $('#empty-cart');
    var $cartLayout = $('#cart-layout');
    var $cartItemCount = $('#cart-item-count');
    
    if (!cartData) {
        console.error('displayCartItems: cartData is null or undefined');
        if ($emptyCartDiv.length) $emptyCartDiv.css('display', 'flex');
        if ($cartLayout.length) $cartLayout.css('display', 'none');
        return;
    }
    
    var items = cartData.items || cartData;
    
    if (!items || !Array.isArray(items)) {
        console.error('displayCartItems: items is not an array', items);
        if ($emptyCartDiv.length) $emptyCartDiv.css('display', 'flex');
        if ($cartLayout.length) $cartLayout.css('display', 'none');
        return;
    }
    
    if (items.length == 0) {
        if ($emptyCartDiv.length) $emptyCartDiv.css('display', 'flex');
        if ($cartLayout.length) $cartLayout.css('display', 'none');
        if ($cartItemCount.length) $cartItemCount.text('0');
        return;
    }
    
    if ($emptyCartDiv.length) $emptyCartDiv.css('display', 'none');
    if ($cartLayout.length) $cartLayout.css('display', 'flex');
    if ($cartItemCount.length) $cartItemCount.text(items.length);
    
    if ($desktopTableBody.length) $desktopTableBody.empty();
    if ($mobileContainer.length) $mobileContainer.empty();
    
    for (var i = 0; i < items.length; i++) {
        var item = items[i];
        
        if (!item || !item.product_id) {
            console.warn('Skipping invalid cart item:', item);
            continue;
        }
        
        var verifiedBadge = item.is_verified ? 
            '<div class="verified-badge-cart"><img src="' + baseUrl + 'images/icons/verified-svgrepo-com.svg" width="14" height="14"><span>Verified Seller</span></div>' : 
            '<div class="unverified-badge-cart"><img src="' + baseUrl + 'images/icons/not-verified-svgrepo-com.svg" width="14" height="14"><span>Unverified</span></div>';
        
        var imagePath = fixImageUrl(item.image || item.image_url);
        var productName = item.product_name || item.title || 'Product';
        var sellerName = item.seller_name || 'Unknown Seller';
        var price = parseFloat(item.price) || 0;
        var quantity = parseInt(item.quantity) || 1;
        var cartId = item.cart_id;
        var productId = item.product_id;
        var stockQty = parseInt(item.stock_quantity) || 99;
        
        if ($desktopTableBody.length) {
            var row = $('<tr>').html(
                '<td class="product-cell" data-label="Product">' +
                    '<div class="cart-product-wrapper">' +
                        '<div class="cart-img-container"><img src="' + imagePath + '" alt="' + escapeHtml(productName) + '" onerror="this.src=\'' + baseUrl + 'images/default-product.png\'"></div>' +
                        '<div class="cart-prod-info"><p class="prod-name">' + escapeHtml(productName) + '</p></div>' +
                    '</div>' +
                '</td>' +
                '<td class="seller-cell" data-label="Seller">' +
                    '<div class="seller-cart-info">' +
                        '<p class="seller-name">' + escapeHtml(sellerName) + '</p>' +
                        '<div class="verification">' + verifiedBadge + '</div>' +
                    '</div>' +
                '</td>' +
                '<td class="price-cell" data-label="Price">R ' + price.toFixed(2) + '</td>' +
                '<td class="quantity-cell" data-label="Quantity">' +
                    '<div class="quantity-controls">' +
                        '<button class="qty-decrease" data-cart-id="' + cartId + '">-</button>' +
                        '<input type="number" class="qty-input" value="' + quantity + '" min="1" max="' + Math.min(99, stockQty) + '" data-cart-id="' + cartId + '" style="width: 60px; text-align: center;">' +
                        '<button class="qty-increase" data-cart-id="' + cartId + '">+</button>' +
                    '</div>' +
                    (quantity >= stockQty && stockQty > 0 ? '<small class="stock-warning">Max ' + stockQty + ' available</small>' : '') +
                '</td>' +
                '<td class="actions-cell" data-label="Actions">' +
                    '<button class="remove-btn" data-product-id="' + productId + '">' +
                        '<img src="' + baseUrl + 'images/icons/delete-svgrepo-com.svg" width="16" height="16" alt="Remove"> Remove' +
                    '</button>' +
                '</td>'
            );
            $desktopTableBody.append(row);
        }
        
        if ($mobileContainer.length) {
            var card = $('<div>').addClass('cart-card').html(
                '<div class="cart-card-header">' +
                    '<img src="' + imagePath + '" alt="' + escapeHtml(productName) + '" class="cart-card-img" onerror="this.src=\'' + baseUrl + 'images/default-product.png\'">' +
                    '<div>' +
                        '<h4>' + escapeHtml(productName) + '</h4>' +
                        '<p class="seller-name">' + escapeHtml(sellerName) + '</p>' +
                        verifiedBadge +
                    '</div>' +
                '</div>' +
                '<div class="cart-card-body">' +
                    '<div class="cart-card-price">R ' + price.toFixed(2) + '</div>' +
                    '<div class="quantity-controls">' +
                        '<button class="qty-decrease" data-cart-id="' + cartId + '">-</button>' +
                        '<input type="number" class="qty-input" value="' + quantity + '" min="1" max="' + Math.min(99, stockQty) + '" data-cart-id="' + cartId + '" style="width: 50px; text-align: center;">' +
                        '<button class="qty-increase" data-cart-id="' + cartId + '">+</button>' +
                    '</div>' +
                    '<button class="remove-btn" data-product-id="' + productId + '">' +
                        '<img src="' + baseUrl + 'images/icons/delete-svgrepo-com.svg" width="14" height="14" alt="Remove"> Remove' +
                    '</button>' +
                '</div>'
            );
            $mobileContainer.append(card);
        }
    }
    
    // Quantity control event handlers
    $('.qty-increase').off('click').on('click', function() {
        var $btn = $(this);
        var cartId = $btn.data('cart-id');
        var $input = $('.qty-input[data-cart-id="' + cartId + '"]');
        var currentVal = parseInt($input.val());
        var maxVal = parseInt($input.attr('max'));
        if (!isNaN(currentVal) && currentVal < maxVal) {
            $input.val(currentVal + 1);
            updateCartQuantity(cartId, currentVal + 1);
        }
    });
    
    $('.qty-decrease').off('click').on('click', function() {
        var $btn = $(this);
        var cartId = $btn.data('cart-id');
        var $input = $('.qty-input[data-cart-id="' + cartId + '"]');
        var currentVal = parseInt($input.val());
        if (!isNaN(currentVal) && currentVal > 1) {
            $input.val(currentVal - 1);
            updateCartQuantity(cartId, currentVal - 1);
        }
    });
    
    $('.qty-input').off('change').on('change', function() {
        var $input = $(this);
        var cartId = $input.data('cart-id');
        var quantity = parseInt($input.val());
        var maxVal = parseInt($input.attr('max'));
        if (isNaN(quantity) || quantity < 1) quantity = 1;
        if (quantity > maxVal) {
            quantity = maxVal;
            alert('Only ' + maxVal + ' available in stock.');
        }
        $input.val(quantity);
        updateCartQuantity(cartId, quantity);
    });
    
    $('.remove-btn').off('click').on('click', function() {
        var $btn = $(this);
        var productId = $btn.data('product-id');
        if (confirm('Remove this item from your cart?')) {
            removeFromCart(productId);
        }
    });
}

/**
 * Updates cart quantity via AJAX and refreshes the display
 */
function updateCartQuantity(cartId, quantity) {
    $.ajax({
        url: baseUrl + 'php/endpoints/update-cart.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ cart_id: cartId, quantity: quantity }),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                if (response.cart) {
                    displayCartItems(response.cart);
                    updateCartTotalsDisplay(response.cart);
                    updateCartCountDisplay(response.cart.item_count);
                } else {
                    refreshCart();
                }
                showSuccessToast(response.message);
            } else {
                showErrorToast(response.message);
                location.reload();
            }
        },
        error: function() {
            showErrorToast('Something went wrong.');
            location.reload();
        }
    });
}

// ========== MODAL CONTROLS ==========

/**
 * Opens modal with smooth CSS transition animation
 */
function openModal($modal) {
    if (!$modal.length) return;
    
    clearModalErrors($modal.attr('id'));
    
    var $content = $modal.find('.modal-content');
    $modal.find('.error-container').hide().empty();
    $modal.find('.input-group').removeClass('error');
    $modal.find('.error-text').remove();
    $content.removeClass('animate-in animate-out');
    
    $modal.css('visibility', 'visible');
    $modal.addClass('active');
    
    // Force reflow for smooth animation
    $modal[0].offsetHeight;
    $content.addClass('animate-in');
    $('body').css('overflow', 'hidden');
    
    setTimeout(function() { $content.removeClass('animate-in'); }, 350);
}

/**
 * Closes modal with smooth CSS transition animation
 */
function closeModal($modal) {
    if (!$modal.length) return;
    
    var $content = $modal.find('.modal-content');
    $modal.find('.error-container').hide().empty();
    $modal.find('.input-group').removeClass('error');
    $modal.find('.error-text').remove();
    $content.removeClass('animate-in');
    $modal[0].offsetHeight;
    $content.addClass('animate-out');
    
    setTimeout(function() {
        $modal.removeClass('active');
        $modal.css('visibility', 'hidden');
        $content.removeClass('animate-out');
        $('body').css('overflow', '');
    }, 280);
}

/**
 * Clears field errors as soon as user starts typing
 */
function initErrorClearingOnInput() {
    $('#login-email, #login-password').on('input', function() { 
        clearLoginErrors(); 
    });
    
    $('#register-full-name, #register-email, #register-phone, #register-password, #register-confirm-password').on('input', function() {
        $('#register-error-container').hide().empty();
        $(this).closest('.input-group').removeClass('error');
    });
    
    $('#switch-to-register').on('click', function() { 
        clearLoginErrors(); 
    });
    
    $('#switch-to-login').on('click', function() { 
        clearRegisterErrors(); 
    });
}

/**
 * AJAX login handler - prevents page reload and shows errors inline
 */
function initAjaxLogin() {
    var $loginForm = $('#login-form');
    if (!$loginForm.length) return;
    
    $loginForm.off('submit').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        var $submitBtn = $(this).find('button[type="submit"]');
        var originalText = $submitBtn.text();
        
        $submitBtn.prop('disabled', true).text('Logging in...');
        
        $.ajax({
            url: baseUrl + 'php/endpoints/login.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.redirect;
                } else {
                    displayModalErrors('#login-modal', { general: response.message }, { email: $('#login-email').val() });
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                displayModalErrors('#login-modal', { general: 'Something went wrong. Please try again.' }, {});
                $submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });
}

/**
 * AJAX registration handler - validates and creates account without page reload
 */
function initAjaxRegister() {
    var $registerForm = $('#register-form');
    if (!$registerForm.length) return;
    
    $registerForm.off('submit').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        var $submitBtn = $(this).find('button[type="submit"]');
        var originalText = $submitBtn.text();
        
        $submitBtn.prop('disabled', true).text('Creating account...');
        
        $.ajax({
            url: baseUrl + 'php/endpoints/register.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.redirect;
                } else {
                    displayModalErrors('#register-modal', response.errors, response.form_data);
                    if (response.errors && response.errors.general) {
                        $('#register-error-container').show().text(response.errors.general);
                    }
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                $('#register-error-container').show().text('Something went wrong. Please try again.');
                $submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });
}

// ========== UI CONTROLS ==========

/**
 * Mobile menu toggle with overlay
 */
function initMobileMenu() {
    var $menuToggle = $('#menuToggle');
    var $closeMenu = $('#closeMenu');
    var $mobileMenu = $('#mobileMenu');
    var $menuOverlay = $('#menuOverlay');
    
    function openMobileMenu() {
        $menuToggle.addClass('active');
        $mobileMenu.addClass('active');
        $menuOverlay.addClass('active');
        $('body').css('overflow', 'hidden');
    }
    
    function closeMobileMenu() {
        $menuToggle.removeClass('active');
        $mobileMenu.removeClass('active');
        $menuOverlay.removeClass('active');
        $('body').css('overflow', '');
    }
    
    if ($menuToggle.length) {
        $menuToggle.on('click', function() {
            if ($mobileMenu.hasClass('active')) closeMobileMenu();
            else openMobileMenu();
        });
    }
    
    if ($closeMenu.length) $closeMenu.on('click', closeMobileMenu);
    if ($menuOverlay.length) $menuOverlay.on('click', closeMobileMenu);
    
    $('.mobile-nav-links a, .mobile-nav-links button').on('click', function() {
        if ($(window).width() <= 768) closeMobileMenu();
    });
    
    $(window).on('resize', function() {
        if ($(window).width() > 768 && $mobileMenu.hasClass('active')) closeMobileMenu();
    });
}

/**
 * Mobile search bar toggle
 */
function initMobileSearch() {
    var $mobileSearchIcon = $('#mobileSearchIcon');
    var $mobileSearchContainer = $('#mobileSearch');
    
    if ($mobileSearchIcon.length && $mobileSearchContainer.length) {
        $mobileSearchIcon.on('click', function(e) {
            e.stopPropagation();
            $mobileSearchContainer.toggleClass('active');
        });
        
        $(document).on('click', function(event) {
            if ($mobileSearchContainer.length && $mobileSearchContainer.hasClass('active') &&
                !$mobileSearchContainer.is(event.target) && !$mobileSearchIcon.is(event.target) &&
                !$mobileSearchContainer.has(event.target).length) {
                $mobileSearchContainer.removeClass('active');
            }
        });
    }
}

/**
 * User dropdown menu for account settings
 */
function initUserDropdown() {
    var $accountBtn = $('#accountBtn');
    var $accountDropdown = $('#accountDropdown');
    
    if ($accountBtn.length && $accountDropdown.length) {
        $accountBtn.on('click', function(e) {
            e.stopPropagation();
            $accountDropdown.toggleClass('active');
        });
        
        $(document).on('click', function(e) {
            if (!$accountBtn.is(e.target) && !$accountDropdown.is(e.target) &&
                !$accountBtn.has(e.target).length && !$accountDropdown.has(e.target).length) {
                $accountDropdown.removeClass('active');
            }
        });
    }
}

/**
 * Modal open/close and switch between login/register
 */
function initModalControls() {
    var $registerModal = $('#register-modal');
    var $loginModal = $('#login-modal');
    var $deleteModal = $('#delete-modal');
    var $registerBtns = $('#registerBtn, #mobileRegisterBtn');
    var $loginBtns = $('#loginBtn, #mobileLoginBtn');
    var $modalCloseBtns = $('.modal-close, .btn-close');
    var $switchToRegister = $('#switch-to-register');
    var $switchToLogin = $('#switch-to-login');
    
    if ($registerBtns.length) {
        $registerBtns.on('click', function(e) {
            e.preventDefault();
            if ($loginModal.hasClass('active')) closeModal($loginModal);
            openModal($registerModal);
        });
    }
    
    if ($loginBtns.length) {
        $loginBtns.on('click', function(e) {
            e.preventDefault();
            if ($registerModal.hasClass('active')) closeModal($registerModal);
            openModal($loginModal);
        });
    }
    
    if ($modalCloseBtns.length) $modalCloseBtns.on('click', function() { 
        closeModal($registerModal);
        closeModal($loginModal);
        closeModal($deleteModal);
    });
    
    $registerModal.on('click', function(e) { 
        if ($(e.target).is($registerModal)) closeModal($registerModal); 
    });
    
    $loginModal.on('click', function(e) { 
        if ($(e.target).is($loginModal)) closeModal($loginModal); 
    });
    
    if ($deleteModal.length) {
        $deleteModal.on('click', function(e) { 
            if ($(e.target).is($deleteModal)) closeModal($deleteModal); 
        });
    }
    
    if ($switchToRegister.length) {
        $switchToRegister.on('click', function(e) {
            e.preventDefault();
            clearLoginErrors();
            closeModal($loginModal);
            setTimeout(function() { openModal($registerModal); }, 300);
        });
    }
    
    if ($switchToLogin.length) {
        $switchToLogin.on('click', function(e) {
            e.preventDefault();
            clearRegisterErrors();
            closeModal($registerModal);
            setTimeout(function() { openModal($loginModal); }, 300);
        });
    }
}

/**
 * Highlights current page in navigation menu
 */
function setActiveLink() {
    var path = window.location.pathname;
    var currentPage = path.substring(path.lastIndexOf('/') + 1) || 'index.php';
    
    $('.main-nav a, .mobile-nav-links a').each(function() {
        var $link = $(this);
        var href = $link.attr('href');
        if (!href) return;
        
        var hrefPage = href.substring(href.lastIndexOf('/') + 1);
        if (hrefPage.indexOf('?') !== -1) hrefPage = hrefPage.substring(0, hrefPage.indexOf('?'));
        
        $link.removeClass('active');
        if (hrefPage == currentPage) $link.addClass('active');
    });
}

/**
 * Auto-dismiss flash messages after a few seconds
 */
function initFlashMessages() {
    var $flashMsg = $('.flash-message');
    if ($flashMsg.length) {
        setTimeout(function() { $flashMsg.fadeOut(500); }, 4000);
    }
}

// ========== DOCUMENT READY ==========

$(function() {
    initMobileMenu();
    initMobileSearch();
    initModalControls();
    initUserDropdown();
    initFlashMessages();
    setActiveLink();
    initErrorClearingOnInput();
    initAjaxLogin();
    initAjaxRegister();
    
    // Cart initialization - only for buyers
    if (isLoggedIn && currentUserRole === 'buyer') {
        if (window.location.pathname.includes('cart.php')) {
            loadCartPage();
        } else {
            updateCartCount();
        }
    } else {
        updateCartCountDisplay(0);
    }
});