// main.js - main scripts for the site
// handles mobile menu, search, modals, cart stuff, etc.
// Author: Kamogelo Phale

// setting base url - this gets set by footer.php hopefully
var baseUrl = baseUrl || '';

// global vars - yeah i know global is bad but it works
var $toastContainer = null;
var $orderModal = null;
var $orderModalBody = null;
var $orderModalFooter = null;
var $registerModal = null;
var $loginModal = null;
var $deleteModal = null;
var $bodyElement = null;
var $cartCountElements = null;

// helper to escape html - copied this from stack overflow tbh
function escapeHtml(text) {
    if (!text) return '';
    return $('<div>').text(text).html();
}

// capitalizes first letter - simple enough
function capitalizeFirst(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// returns css class for order status badges
function getStatusClass(status) {
    if (status == 'pending') return 'status-pending';
    if (status == 'processing') return 'status-processing';
    if (status == 'shipped') return 'status-shipped';
    if (status == 'completed') return 'status-completed';
    if (status == 'cancelled') return 'status-cancelled';
    return '';
}

// returns readable status label
function getStatusLabel(status) {
    if (status == 'pending') return 'Pending';
    if (status == 'processing') return 'Processing';
    if (status == 'shipped') return 'Shipped';
    if (status == 'completed') return 'Completed';
    if (status == 'cancelled') return 'Cancelled';
    return capitalizeFirst(status);
}

// fixes image urls - had issues with paths so made this function
function fixImageUrl(url, defaultPath) {
    defaultPath = defaultPath || 'images/default-product.png';
    if (!url || url == '') return baseUrl + defaultPath;
    if (url.startsWith('http://') || url.startsWith('https://')) return url;
    
    var parts = url.split('/');
    var filename = parts[parts.length - 1];
    
    if (url.includes('/products/')) return baseUrl + 'uploads/products/' + filename;
    if (url.includes('/profiles/')) return baseUrl + 'uploads/profiles/' + filename;
    
    var uploadsIndex = url.indexOf('uploads/');
    if (uploadsIndex !== -1) return baseUrl + url.substring(uploadsIndex);
    
    return baseUrl + defaultPath;
}

// shows empty state message when no data
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

// pagination renderer - took me a while to get this right
function renderPagination($container, currentPage, totalPages, onPageChange) {
    if (!$container.length || totalPages <= 1) {
        $container.empty();
        return;
    }

    var html = '';
    
    if (currentPage > 1) {
        html += '<button class="page-btn" data-page="' + (currentPage - 1) + '">← Previous</button>';
    }

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

// renders admin orders table - shows all orders with action buttons
function renderAdminOrdersTable(orders, $container) {
    if (!$container || !$container.length) return;
    
    $container.empty();
    
    if (!orders || orders.length == 0) {
        $container.html('<tr><td colspan="8" class="empty-cell">No orders found</td></tr>');
        return;
    }
    
    for (var i = 0; i < orders.length; i++) {
        var order = orders[i];
        var statusClass = order.status;
        var actionButtons = '<button class="action-btn view-btn" onclick="openOrderModal(' + order.order_id + ')">View</button>';
        
        if (order.status == 'pending') {
            actionButtons += '<button class="action-btn process-btn" onclick="updateOrderStatus(' + order.order_id + ', \'processing\')">Process</button>';
        } else if (order.status == 'processing') {
            actionButtons += '<button class="action-btn ship-btn" onclick="updateOrderStatus(' + order.order_id + ', \'shipped\')">Ship</button>';
        } else if (order.status == 'shipped') {
            actionButtons += '<button class="action-btn complete-btn" onclick="updateOrderStatus(' + order.order_id + ', \'completed\')">Complete</button>';
        }
        
        $container.append(
            '<tr>' +
                '<td data-label="Order Number">#' + order.order_id + '</td>' +
                '<td data-label="Customer">' + escapeHtml(order.buyer_name) + '</td>' +
                '<td data-label="Seller">' + escapeHtml(order.seller_name) + '</td>' +
                '<td data-label="Items">' + (order.item_count || 0) + '</td>' +
                '<td data-label="Amount">R ' + parseFloat(order.total_price).toFixed(2) + '</td>' +
                '<td data-label="Status"><span class="status-badge ' + statusClass + '">' + order.status + '</span></td>' +
                '<td data-label="Date">' + order.created_at + '</td>' +
                '<td data-label="Actions" class="action-buttons">' + actionButtons + '</td>' +
            '</tr>'
        );
    }
}

// toast notifications - shows temporary messages
function showToast(message, type) {
    type = type || 'success';
    
    // remove any existing toasts first
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
    
    // auto remove after 4 seconds
    setTimeout(function() {
        toast.addClass('hiding');
        setTimeout(function() { toast.remove(); }, 300);
    }, 4000);
}

function showSuccessToast(message) { showToast(message, 'success'); }
function showErrorToast(message) { showToast(message, 'error'); }
function showInfoToast(message) { showToast(message, 'info'); }
function showWarningToast(message) { showToast(message, 'warning'); }

// toggle password visibility - shows/hides password field
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

// open order details modal and load data
function openOrderModal(orderId) {
    if (!$orderModal) {
        $orderModal = $('#orderModal');
        $orderModalBody = $('#orderModalBody');
        $orderModalFooter = $('#orderModalFooter');
    }
    
    if (!$orderModal.length) return;
    
    $orderModal.addClass('active');
    $orderModalBody.html('<div class="loading-spinner">Loading order details...</div>');
    $orderModalFooter.empty();
    
    $.ajax({
        url: baseUrl + 'php/endpoints/get-order-details.php?order_id=' + orderId,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success && data.order) {
                // display order details in modal
                var order = data.order;
                var isAdmin = window.location.pathname.includes('all-orders.php');
                
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
                
                $orderModalBody.html(
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
                
                $orderModalFooter.html(actionButtons);
            } else {
                $orderModalBody.html('<p class="error">Unable to load order details.</p>');
            }
        },
        error: function() {
            $orderModalBody.html('<p class="error">Error loading order details.</p>');
        }
    });
}

function closeOrderModal() {
    if ($orderModal) $orderModal.removeClass('active');
}

// update order status - sends ajax request
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

// clear errors in a modal - just removes the error messages
function clearModalErrors(modalId) {
    var $modal = $(modalId);
    $modal.find('.error-container').hide().empty();
    $modal.find('.input-group').removeClass('error');
    $modal.find('.error-text').remove();
}

// shows validation errors in modals
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

// clear login form errors
function clearLoginErrors() {
    $('#login-error-container').hide().empty();
    $('#login-form .input-group').removeClass('error');
    $('#login-form .error-text').remove();
}

// clear register form errors
function clearRegisterErrors() {
    $('#register-error-container').hide().empty();
    $('#register-form .input-group').removeClass('error');
    $('#register-form .error-text').remove();
}

// get cart count elements - cached but whatever
function getCartCountElements() {
    if (!$cartCountElements) {
        $cartCountElements = $('.cart-count, .item-num, .cart-badge');
    }
    return $cartCountElements;
}

// update the cart count badge on the page
function updateCartCountDisplay(count) {
    getCartCountElements().text(count);
    if (window.sessionStorage) sessionStorage.setItem('cart_count', count);
}

// add product to shopping cart
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

// remove product from cart
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
                if (window.location.pathname.includes('cart.php')) location.reload();
                else showSuccessToast(data.message || 'Item removed from cart');
            } else {
                showErrorToast(data.message || 'Error removing item from cart');
            }
        },
        error: function() { showErrorToast('Something went wrong'); }
    });
}

// update cart count from cache or server
function updateCartCount() {
    var cachedCount = sessionStorage.getItem('cart_count');
    if (cachedCount && !isNaN(parseInt(cachedCount))) {
        updateCartCountDisplay(parseInt(cachedCount));
    } else {
        $.get(baseUrl + 'php/endpoints/get-cart.php', function(data) {
            if (data.success) {
                updateCartCountDisplay(data.item_count);
            }
        });
    }
}

// load cart items on cart page
function loadCart() {
    if (!window.location.pathname.includes('cart.php')) return;
    
    $.get(baseUrl + 'php/endpoints/get-cart.php', function(data) {
        if (data.success) {
            displayCartItems(data);
            $('.sub-total-val').text(data.subtotal);
            $('.deliv-fee-val').text(data.delivery_fee);
            $('.total-val').text(data.total);
        }
    });
}

// display cart items in table and mobile view
function displayCartItems(cartData) {
    var $desktopTableBody = $('#cart-table-body');
    var $mobileContainer = $('#mobile-cart-items');
    var $emptyCartDiv = $('#empty-cart');
    var $cartLayout = $('#cart-layout');
    var $cartItemCount = $('#cart-item-count');
    
    if (!cartData.items || cartData.items.length == 0) {
        if ($emptyCartDiv.length) $emptyCartDiv.css('display', 'flex');
        if ($cartLayout.length) $cartLayout.css('display', 'none');
        if ($cartItemCount.length) $cartItemCount.text('0');
        return;
    }
    
    if ($emptyCartDiv.length) $emptyCartDiv.css('display', 'none');
    if ($cartLayout.length) $cartLayout.css('display', 'flex');
    if ($cartItemCount.length) $cartItemCount.text(cartData.items.length);
    
    if ($desktopTableBody.length) $desktopTableBody.empty();
    if ($mobileContainer.length) $mobileContainer.empty();
    
    for (var i = 0; i < cartData.items.length; i++) {
        var item = cartData.items[i];
        var verifiedBadge = item.is_verified ? 
            '<div class="verified-badge-cart"><img src="' + baseUrl + 'images/icons/verified-svgrepo-com.svg" width="14" height="14"><span>Verified Seller</span></div>' : 
            '<div class="unverified-badge-cart"><img src="' + baseUrl + 'images/icons/not-verified-svgrepo-com.svg" width="14" height="14"><span>Unverified</span></div>';
        
        var imagePath = fixImageUrl(item.image);
        
        if ($desktopTableBody.length) {
            var row = $('<tr>').html(
                '<td class="product-cell" data-label="Product">' +
                    '<div class="cart-product-wrapper">' +
                        '<div class="cart-img-container"><img src="' + imagePath + '" alt="' + escapeHtml(item.product_name) + '" onerror="this.src=\'' + baseUrl + 'images/default-product.png\'"></div>' +
                        '<div class="cart-prod-info"><p class="prod-name">' + escapeHtml(item.product_name) + '</p></div>' +
                    '</div>' +
                '</td>' +
                '<td class="seller-cell" data-label="Seller">' +
                    '<div class="seller-cart-info">' +
                        '<p class="seller-name">' + escapeHtml(item.seller_name) + '</p>' +
                        '<div class="verification">' + verifiedBadge + '</div>' +
                    '</div>' +
                '</td>' +
                '<td class="price-cell" data-label="Price">R ' + parseFloat(item.price).toFixed(2) + '</td>' +
                '<td class="quantity-cell" data-label="Quantity">' +
                    '<div class="quantity-controls">' +
                        '<button class="qty-decrease" data-cart-id="' + item.cart_id + '">-</button>' +
                        '<input type="number" class="qty-input" value="' + item.quantity + '" min="1" max="' + Math.min(99, item.stock_quantity || 99) + '" data-cart-id="' + item.cart_id + '" style="width: 60px; text-align: center;">' +
                        '<button class="qty-increase" data-cart-id="' + item.cart_id + '">+</button>' +
                    '</div>' +
                    (item.quantity >= (item.stock_quantity || 99) && (item.stock_quantity || 0) > 0 ? '<small class="stock-warning">Max ' + item.stock_quantity + ' available</small>' : '') +
                '</td>' +
                '<td class="actions-cell" data-label="Actions">' +
                    '<button class="remove-btn" data-product-id="' + item.product_id + '">' +
                        '<img src="' + baseUrl + 'images/icons/delete-svgrepo-com.svg" width="16" height="16" alt="Remove"> Remove' +
                    '</button>' +
                '</td>'
            );
            $desktopTableBody.append(row);
        }
        
        if ($mobileContainer.length) {
            var card = $('<div>').addClass('cart-card').html(
                '<div class="cart-card-header">' +
                    '<img src="' + imagePath + '" alt="' + escapeHtml(item.product_name) + '" class="cart-card-img" onerror="this.src=\'' + baseUrl + 'images/default-product.png\'">' +
                    '<div>' +
                        '<h4>' + escapeHtml(item.product_name) + '</h4>' +
                        '<p class="seller-name">' + escapeHtml(item.seller_name) + '</p>' +
                        verifiedBadge +
                    '</div>' +
                '</div>' +
                '<div class="cart-card-body">' +
                    '<div class="cart-card-price">R ' + parseFloat(item.price).toFixed(2) + '</div>' +
                    '<div class="quantity-controls">' +
                        '<button class="qty-decrease" data-cart-id="' + item.cart_id + '">-</button>' +
                        '<input type="number" class="qty-input" value="' + item.quantity + '" min="1" max="' + Math.min(99, item.stock_quantity || 99) + '" data-cart-id="' + item.cart_id + '" style="width: 50px; text-align: center;">' +
                        '<button class="qty-increase" data-cart-id="' + item.cart_id + '">+</button>' +
                    '</div>' +
                    '<button class="remove-btn" data-product-id="' + item.product_id + '">' +
                        '<img src="' + baseUrl + 'images/icons/delete-svgrepo-com.svg" width="14" height="14" alt="Remove"> Remove' +
                    '</button>' +
                '</div>'
            );
            $mobileContainer.append(card);
        }
    }
    
    // attach event handlers for cart controls
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

// update cart quantity via ajax
function updateCartQuantity(cartId, quantity) {
    $.ajax({
        url: baseUrl + 'php/endpoints/update-cart.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ cart_id: cartId, quantity: quantity }),
        dataType: 'json',
        success: function(response) {
            if (response.success) location.reload();
            else alert('Error: ' + response.message);
        },
        error: function() { alert('Something went wrong.'); }
    });
}

// open modal with animation
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
    
    $modal[0].offsetHeight;
    $content.addClass('animate-in');
    $('body').css('overflow', 'hidden');
    
    setTimeout(function() { $content.removeClass('animate-in'); }, 350);
}

// close modal with animation
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

// setup input handlers to clear errors when typing
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

// ajax login form handler
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

// ajax register form handler
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

// mobile menu functions
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

// mobile search toggle
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

// user dropdown for account menu
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

// modal controls - open close switch etc
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

// set active nav link based on current page
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

// flash messages auto hide
function initFlashMessages() {
    var $flashMsg = $('.flash-message');
    if ($flashMsg.length) {
        setTimeout(function() { $flashMsg.fadeOut(500); }, 4000);
    }
}

// document ready - initialize everything
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
    
    if (!window.location.pathname.includes('cart.php')) {
        updateCartCount();
        loadCart();
    }
});