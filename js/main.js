// main.js
/*
 * ConsuTrade - Main JavaScript File (jQuery Version)
 * Author: Kamogelo Phale
 * 
 * Handles mobile menu, search, password toggle, modals, cart, dropdowns, toast notifications
 */

// Base URL will be set by footer.php
var baseUrl = baseUrl || '';

// ========== GLOBAL ESCAPE HTML FUNCTION ==========
function escapeHtml(text) {
    if (!text) return '';
    return $('<div>').text(text).html();
}

// ========== HELPER: CAPITALIZE FIRST LETTER ==========
function capitalizeFirst(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// ========== HELPER: GET STATUS CLASS ==========
function getStatusClass(status) {
    var classes = {
        'pending': 'status-pending',
        'processing': 'status-processing',
        'shipped': 'status-shipped',
        'completed': 'status-completed',
        'cancelled': 'status-cancelled'
    };
    return classes[status] || '';
}

// ========== HELPER: FIX IMAGE URL ==========
function fixImageUrl(url, defaultPath) {
    defaultPath = defaultPath || 'images/default-product.png';
    if (!url || url === '') return baseUrl + defaultPath;
    if (url.startsWith('http://') || url.startsWith('https://')) return url;
    
    var parts = url.split('/');
    var filename = parts[parts.length - 1];
    
    if (url.includes('/products/')) return baseUrl + 'uploads/products/' + filename;
    if (url.includes('/profiles/')) return baseUrl + 'uploads/profiles/' + filename;
    
    var uploadsIndex = url.indexOf('uploads/');
    if (uploadsIndex !== -1) return baseUrl + url.substring(uploadsIndex);
    
    return baseUrl + defaultPath;
}

// ========== PAGINATION FUNCTIONS ==========
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
        if (i === currentPage) {
            html += '<button class="page-btn active" disabled>' + i + '</button>';
        } else if (Math.abs(i - currentPage) <= 2 || i === 1 || i === totalPages) {
            html += '<button class="page-btn" data-page="' + i + '">' + i + '</button>';
        } else if (Math.abs(i - currentPage) === 3) {
            html += '<span class="page-dots">...</span>';
        }
    }

    if (currentPage < totalPages) {
        html += '<button class="page-btn" data-page="' + (currentPage + 1) + '">Next →</button>';
    }

    $container.html(html);
    
    $container.find('.page-btn[data-page]').off('click').on('click', function() {
        var page = parseInt($(this).data('page'));
        if (!isNaN(page) && typeof onPageChange === 'function') {
            onPageChange(page);
        }
    });
}

// ========== TOAST NOTIFICATIONS (Globally accessible) ==========
var $toastContainer = null;
var $existingToasts = null;

function showToast(message, type) {
    type = type || 'success';
    
    $existingToasts = $('.toast-notification');
    $existingToasts.remove();
    
    if (!$toastContainer) {
        $toastContainer = $('<div class="toast-container"></div>');
        $('body').append($toastContainer);
    }
    
    var toast = $(`
        <div class="toast-notification toast-${type}">
            <div class="toast-message">${escapeHtml(message)}</div>
        </div>
    `);
    
    $toastContainer.append(toast);
    
    toast.on('click', function() {
        toast.addClass('hiding');
        setTimeout(function() { toast.remove(); }, 300);
    });
    
    setTimeout(function() {
        if (toast && toast.length) {
            toast.addClass('hiding');
            setTimeout(function() { toast.remove(); }, 300);
        }
    }, 4000);
}

function showSuccessToast(message) { showToast(message, 'success'); }
function showErrorToast(message) { showToast(message, 'error'); }
function showInfoToast(message) { showToast(message, 'info'); }
function showWarningToast(message) { showToast(message, 'warning'); }

// ========== PASSWORD TOGGLE ==========
function togglePassword(fieldId, button) {
    var $input = $('#' + fieldId);
    var $img = $(button).find('img');
    
    if ($input.attr('type') === 'password') {
        $input.attr('type', 'text');
        $img.attr('src', baseUrl + 'images/icons/eye-close-svgrepo-com.svg');
        $img.attr('alt', 'Hide password');
    } else {
        $input.attr('type', 'password');
        $img.attr('src', baseUrl + 'images/icons/eye-open-svgrepo-com.svg');
        $img.attr('alt', 'Show password');
    }
}

// ========== ORDER MODAL FUNCTIONS (Globally accessible) ==========
var $orderModal = null;
var $orderModalBody = null;
var $orderModalFooter = null;

function cacheOrderModalElements() {
    if (!$orderModal) {
        $orderModal = $('#orderModal');
        $orderModalBody = $('#orderModalBody');
        $orderModalFooter = $('#orderModalFooter');
    }
}

function openOrderModal(orderId) {
    cacheOrderModalElements();
    
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
                displayOrderDetailsInModal(data.order);
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

function displayOrderDetailsInModal(order) {
    cacheOrderModalElements();
    
    var isAdmin = window.location.pathname.includes('all-orders.php');
    
    var itemsHtml = '';
    if (order.items && order.items.length > 0) {
        for (var i = 0; i < order.items.length; i++) {
            var item = order.items[i];
            var imagePath = fixImageUrl(item.image_url);
            itemsHtml += `
                <div class="order-item">
                    <div class="order-item-img">
                        <img src="${imagePath}" onerror="this.src='${baseUrl}images/default-product.png'">
                    </div>
                    <div class="order-item-details">
                        <h4>${escapeHtml(item.product_name)}</h4>
                        <p>Quantity: ${item.quantity}</p>
                    </div>
                    <div class="order-item-price">R ${parseFloat(item.price).toFixed(2)}</div>
                </div>
            `;
        }
    }
    
    $orderModalBody.html(`
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
                    <span class="info-label">${order.buyer_name ? 'Seller' : 'Customer'}:</span>
                    <span class="info-value">${escapeHtml(order.other_party_name)}</span>
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
            ${itemsHtml || '<p>No items found.</p>'}
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
    
    $orderModalFooter.html(actionButtons);
}

function updateOrderStatus(orderId, newStatus) {
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
                    showSuccessToast(data.message || 'Order status updated successfully!');
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    showErrorToast('Error: ' + (data.message || 'Unknown error'));
                }
            },
            error: function() {
                showErrorToast('Something went wrong. Please try again.');
            }
        });
    }
}

// ========== MODAL ERROR HANDLING ==========
function clearModalErrors(modalId) {
    var $modal = $(modalId);
    var $modalErrorContainer = $modal.find('.error-container');
    var $modalInputGroups = $modal.find('.input-group');
    var $modalErrorTexts = $modal.find('.error-text');
    
    $modalErrorContainer.hide().empty();
    $modalInputGroups.removeClass('error');
    $modalErrorTexts.remove();
}    

function displayModalErrors(modalId, errors, formData) {
    var $modal = $(modalId);
    
    if (!formData) formData = {};
    
    var $registerFullName = $('#register-full-name');
    var $registerEmail = $('#register-email');
    var $registerPhone = $('#register-phone');
    var $registerErrorContainer = $('#register-error-container');
    var $loginEmail = $('#login-email');
    var $loginErrorContainer = $('#login-error-container');
    
    if (modalId === '#register-modal') {
        if (formData.full_name) $registerFullName.val(formData.full_name);
        if (formData.email) $registerEmail.val(formData.email);
        if (formData.phone) $registerPhone.val(formData.phone);
        
        if (errors.general && errors.general.trim()) {
            $registerErrorContainer.show().text(errors.general);
        }
        
        $.each(errors, function(field, message) {
            if (field !== 'general' && message && message.trim()) {
                var $input = $('#register-' + field);
                if ($input.length) {
                    var $closestInputGroup = $input.closest('.input-group');
                    $closestInputGroup.addClass('error');
                    $closestInputGroup.append('<small class="error-text">' + message + '</small>');
                }
            }
        });
    } else if (modalId === '#login-modal') {
        if (formData.email) $loginEmail.val(formData.email);
        
        if (errors.general && errors.general.trim()) {
            $loginErrorContainer.show().text(errors.general);
        }
        
        $.each(errors, function(field, message) {
            if (field !== 'general' && message && message.trim()) {
                var $input = $('#login-' + field);
                if ($input.length) {
                    var $closestInputGroup = $input.closest('.input-group');
                    $closestInputGroup.addClass('error');
                    $closestInputGroup.append('<small class="error-text">' + message + '</small>');
                }
            }
        });
    }
}

// ========== ERROR CLEARING ==========
var $loginErrorContainer = null;
var $loginFormInputGroups = null;
var $loginFormErrorTexts = null;
var $registerErrorContainer = null;
var $registerFormInputGroups = null;
var $registerFormErrorTexts = null;

function cacheLoginErrorElements() {
    $loginErrorContainer = $('#login-error-container');
    $loginFormInputGroups = $('#login-form .input-group');
    $loginFormErrorTexts = $('#login-form .error-text');
}

function cacheRegisterErrorElements() {
    $registerErrorContainer = $('#register-error-container');
    $registerFormInputGroups = $('#register-form .input-group');
    $registerFormErrorTexts = $('#register-form .error-text');
}

function clearLoginErrors() {
    cacheLoginErrorElements();
    if ($loginErrorContainer) $loginErrorContainer.hide().empty();
    if ($loginFormInputGroups) $loginFormInputGroups.removeClass('error');
    if ($loginFormErrorTexts) $loginFormErrorTexts.remove();
}

function clearRegisterErrors() {
    cacheRegisterErrorElements();
    if ($registerErrorContainer) $registerErrorContainer.hide().empty();
    if ($registerFormInputGroups) $registerFormInputGroups.removeClass('error');
    if ($registerFormErrorTexts) $registerFormErrorTexts.remove();
}

function clearModalErrorsOld($modal) {
    var $modalErrorContainer = $modal.find('.error-container');
    var $modalInputGroups = $modal.find('.input-group');
    var $modalErrorTexts = $modal.find('.error-text');
    
    $modalErrorContainer.hide().empty();
    $modalInputGroups.removeClass('error');
    $modalErrorTexts.remove();
}

// ========== CART FUNCTIONS ==========
var $cartCountElements = null;
var $subTotalVal = null;
var $delivFeeVal = null;
var $totalVal = null;

function getCartCountElements() {
    if (!$cartCountElements) {
        $cartCountElements = $('.cart-count, .item-num');
    }
    return $cartCountElements;
}

function updateCartCountDisplay(count) {
    var $elements = getCartCountElements();
    $elements.text(count);
    if (window.sessionStorage) sessionStorage.setItem('cart_count', count);
}

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

function updateCartCount() {
    var cachedCount = sessionStorage.getItem('cart_count');
    if (cachedCount && !isNaN(parseInt(cachedCount))) {
        updateCartCountDisplay(parseInt(cachedCount));
    } else {
        $.get(baseUrl + 'php/endpoints/get-cart.php', function(data) {
            if (data.success) {
                updateCartCountDisplay(data.item_count);
            }
        }).fail(function() {});
    }
}

function loadCart() {
    if (!window.location.pathname.includes('cart.php')) return;
    
    $.get(baseUrl + 'php/endpoints/get-cart.php', function(data) {
        if (data.success) {
            displayCartItems(data);
            updateOrderSummary(data);
        }
    }).fail(function() { console.log('Error loading cart'); });
}

function displayCartItems(cartData) {
    var $desktopTableBody = $('#cart-table-body');
    var $mobileContainer = $('#mobile-cart-items');
    var $emptyCartDiv = $('#empty-cart');
    var $cartLayout = $('#cart-layout');
    var $cartItemCount = $('#cart-item-count');
    
    if (!cartData.items || cartData.items.length === 0) {
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
    
    $.each(cartData.items, function(index, item) {
        var verifiedBadge = item.is_verified ? 
            '<div class="verified-badge-cart"><img src="' + baseUrl + 'images/icons/verified-svgrepo-com.svg" width="14" height="14"><span>Verified Seller</span></div>' : 
            '<div class="unverified-badge-cart"><img src="' + baseUrl + 'images/icons/not-verified-svgrepo-com.svg" width="14" height="14"><span>Unverified</span></div>';
        
        var imagePath = fixImageUrl(item.image);
        
        if ($desktopTableBody.length) {
            var row = $('<tr>').html(`
                <td class="product-cell" data-label="Product">
                    <div class="cart-product-wrapper">
                        <div class="cart-img-container"><img src="${imagePath}" alt="${escapeHtml(item.product_name)}" onerror="this.src='${baseUrl}images/default-product.png'"></div>
                        <div class="cart-prod-info"><p class="prod-name">${escapeHtml(item.product_name)}</p></div>
                    </div>
                </td>
                <td class="seller-cell" data-label="Seller">
                    <div class="seller-cart-info">
                        <p class="seller-name">${escapeHtml(item.seller_name)}</p>
                        <div class="verification">${verifiedBadge}</div>
                    </div>
                </td>
                <td class="price-cell" data-label="Price">R ${parseFloat(item.price).toFixed(2)}</td>
                <td class="quantity-cell" data-label="Quantity">
                    <div class="quantity-controls">
                        <button class="qty-decrease" data-cart-id="${item.cart_id}">-</button>
                        <input type="number" class="qty-input" value="${item.quantity}" min="1" max="${Math.min(99, item.stock_quantity || 99)}" data-cart-id="${item.cart_id}" style="width: 60px; text-align: center;">
                        <button class="qty-increase" data-cart-id="${item.cart_id}">+</button>
                    </div>
                    ${item.quantity >= (item.stock_quantity || 99) && (item.stock_quantity || 0) > 0 ? `<small class="stock-warning">Max ${item.stock_quantity} available</small>` : ''}
                </td>
                <td class="actions-cell" data-label="Actions">
                    <button class="remove-btn" data-product-id="${item.product_id}">
                        <img src="${baseUrl}images/icons/delete-svgrepo-com.svg" width="16" height="16" alt="Remove"> Remove
                    </button>
                </td>
            `);
            $desktopTableBody.append(row);
        }
        
        if ($mobileContainer.length) {
            var card = $('<div>').addClass('cart-card').html(`
                <div class="cart-card-header">
                    <img src="${imagePath}" alt="${escapeHtml(item.product_name)}" class="cart-card-img" onerror="this.src='${baseUrl}images/default-product.png'">
                    <div>
                        <h4>${escapeHtml(item.product_name)}</h4>
                        <p class="seller-name">${escapeHtml(item.seller_name)}</p>
                        ${verifiedBadge}
                    </div>
                </div>
                <div class="cart-card-body">
                    <div class="cart-card-price">R ${parseFloat(item.price).toFixed(2)}</div>
                    <div class="quantity-controls">
                        <button class="qty-decrease" data-cart-id="${item.cart_id}">-</button>
                        <input type="number" class="qty-input" value="${item.quantity}" min="1" max="${Math.min(99, item.stock_quantity || 99)}" data-cart-id="${item.cart_id}" style="width: 50px; text-align: center;">
                        <button class="qty-increase" data-cart-id="${item.cart_id}">+</button>
                    </div>
                    <button class="remove-btn" data-product-id="${item.product_id}">
                        <img src="${baseUrl}images/icons/delete-svgrepo-com.svg" width="14" height="14" alt="Remove"> Remove
                    </button>
                </div>
            `);
            $mobileContainer.append(card);
        }
    });
    
    attachCartEventHandlers();
}

function attachCartEventHandlers() {
    var $qtyIncrease = $('.qty-increase');
    var $qtyDecrease = $('.qty-decrease');
    var $qtyInput = $('.qty-input');
    var $removeBtns = $('.remove-btn');
    
    $qtyIncrease.off('click').on('click', function() {
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
    
    $qtyDecrease.off('click').on('click', function() {
        var $btn = $(this);
        var cartId = $btn.data('cart-id');
        var $input = $('.qty-input[data-cart-id="' + cartId + '"]');
        var currentVal = parseInt($input.val());
        if (!isNaN(currentVal) && currentVal > 1) {
            $input.val(currentVal - 1);
            updateCartQuantity(cartId, currentVal - 1);
        }
    });
    
    $qtyInput.off('change').on('change', function() {
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
    
    $removeBtns.off('click').on('click', function() {
        var $btn = $(this);
        var productId = $btn.data('product-id');
        if (confirm('Remove this item from your cart?')) {
            removeFromCart(productId);
        }
    });
}

function updateOrderSummary(cartData) {
    $subTotalVal = $('.sub-total-val');
    $delivFeeVal = $('.deliv-fee-val');
    $totalVal = $('.total-val');
    
    if ($subTotalVal.length) $subTotalVal.text(cartData.subtotal);
    if ($delivFeeVal.length) $delivFeeVal.text(cartData.delivery_fee);
    if ($totalVal.length) $totalVal.text(cartData.total);
}

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

// ========== MODAL FUNCTIONS ==========
var $registerModal = null;
var $loginModal = null;
var $deleteModal = null;
var $bodyElement = null;

function cacheModalElements() {
    if (!$registerModal) $registerModal = $('#register-modal');
    if (!$loginModal) $loginModal = $('#login-modal');
    if (!$deleteModal) $deleteModal = $('#delete-modal');
    if (!$bodyElement) $bodyElement = $('body');
}

function openModal($modal) {
    if (!$modal.length) return;
    
    clearModalErrors($modal.attr('id'));
    
    var $content = $modal.find('.modal-content');
    clearModalErrorsOld($modal);
    $content.removeClass('animate-in animate-out');
    
    $modal.css('visibility', 'visible');
    $modal.addClass('active');
    
    $modal[0].offsetHeight;
    $content.addClass('animate-in');
    $bodyElement.css('overflow', 'hidden');
    
    setTimeout(function() { $content.removeClass('animate-in'); }, 350);
}

function closeModal($modal) {
    if (!$modal.length) return;
    
    var $content = $modal.find('.modal-content');
    clearModalErrorsOld($modal);
    $content.removeClass('animate-in');
    $modal[0].offsetHeight;
    $content.addClass('animate-out');
    
    setTimeout(function() {
        $modal.removeClass('active');
        $modal.css('visibility', 'hidden');
        $content.removeClass('animate-out');
        $bodyElement.css('overflow', '');
    }, 280);
}

// ========== ERROR CLEARING ON INPUT ==========
var $loginEmailInput = null;
var $loginPasswordInput = null;
var $registerFullNameInput = null;
var $registerEmailInput = null;
var $registerPhoneInput = null;
var $registerPasswordInput = null;
var $registerConfirmPasswordInput = null;
var $switchToRegisterBtn = null;
var $switchToLoginBtn = null;

function cacheErrorClearingElements() {
    $loginEmailInput = $('#login-email');
    $loginPasswordInput = $('#login-password');
    $registerFullNameInput = $('#register-full-name');
    $registerEmailInput = $('#register-email');
    $registerPhoneInput = $('#register-phone');
    $registerPasswordInput = $('#register-password');
    $registerConfirmPasswordInput = $('#register-confirm-password');
    $switchToRegisterBtn = $('#switch-to-register');
    $switchToLoginBtn = $('#switch-to-login');
}

function initErrorClearingOnInput() {
    cacheErrorClearingElements();
    
    $loginEmailInput.add($loginPasswordInput).on('input', function() { 
        clearLoginErrors(); 
    });
    
    $registerFullNameInput.add($registerEmailInput).add($registerPhoneInput).add($registerPasswordInput).add($registerConfirmPasswordInput).on('input', function() {
        clearRegisterErrors();
        var $this = $(this);
        var $closestInputGroup = $this.closest('.input-group');
        $closestInputGroup.removeClass('error');
        $closestInputGroup.find('.error-text').remove();
    });
    
    $switchToRegisterBtn.on('click', function() { 
        clearLoginErrors(); 
    });
    
    $switchToLoginBtn.on('click', function() { 
        clearRegisterErrors(); 
    });
}

// ========== AJAX LOGIN HANDLER ==========
var $loginForm = null;
var $loginFormSubmitBtn = null;

function cacheLoginFormElements() {
    $loginForm = $('#login-form');
}

function initAjaxLogin() {
    cacheLoginFormElements();
    
    $loginForm.off('submit').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var formData = $form.serialize();
        var $submitBtn = $form.find('button[type="submit"]');
        var originalText = $submitBtn.text();
        
        $submitBtn.prop('disabled', true).text('Logging in...');
        
        $.ajax({
            url: baseUrl + 'php/endpoints/login.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.redirect;
                } else {
                    var $loginErrorContainer = $('#login-error-container');
                    $loginErrorContainer.show().text(response.message);
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                var $loginErrorContainer = $('#login-error-container');
                $loginErrorContainer.show().text('Something went wrong. Please try again.');
                $submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });
}

// ========== AJAX REGISTER HANDLER ==========
var $registerForm = null;

function cacheRegisterFormElements() {
    $registerForm = $('#register-form');
}

function initAjaxRegister() {
    cacheRegisterFormElements();
    
    $registerForm.off('submit').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var formData = $form.serialize();
        var $submitBtn = $form.find('button[type="submit"]');
        var originalText = $submitBtn.text();
        
        $submitBtn.prop('disabled', true).text('Creating account...');
        
        $.ajax({
            url: baseUrl + 'php/endpoints/register.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.redirect;
                } else {
                    displayModalErrors('#register-modal', response.errors, response.form_data);
                    if (response.errors && response.errors.general) {
                        var $registerErrorContainer = $('#register-error-container');
                        $registerErrorContainer.show().text(response.errors.general);
                    }
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                var $registerErrorContainer = $('#register-error-container');
                $registerErrorContainer.show().text('Something went wrong. Please try again.');
                $submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });
}

// ========== MOBILE MENU FUNCTIONS ==========
var $mainToggle = null;
var $sideClose = null;
var $mobileNav = null;
var $overlay = null;
var $mobileNavLinks = null;
var $mobileNavBtns = null;

function cacheMobileMenuElements() {
    $mainToggle = $('#mobileMenuToggle');
    $sideClose = $('#sideMenuClose');
    $mobileNav = $('#mobileNav');
    $overlay = $('#mobileMenuOverlay');
    $mobileNavLinks = $('.mobile-nav-links a');
    $mobileNavBtns = $('.mobile-nav-btn');
}

function openMobileMenu() {
    cacheMobileMenuElements();
    $mainToggle.addClass('active');
    $mobileNav.addClass('active');
    $overlay.addClass('active');
    $('body').addClass('menu-open').css('overflow', 'hidden');
}

function closeMobileMenu() {
    cacheMobileMenuElements();
    $mainToggle.removeClass('active');
    $mobileNav.removeClass('active');
    $overlay.removeClass('active');
    $('body').removeClass('menu-open').css('overflow', '');
}

function initMobileMenu() {
    cacheMobileMenuElements();
    
    if ($mainToggle.length) {
        $mainToggle.on('click', function() {
            if ($mobileNav.hasClass('active')) closeMobileMenu();
            else openMobileMenu();
        });
    }
    
    if ($sideClose.length) $sideClose.on('click', closeMobileMenu);
    if ($overlay.length) $overlay.on('click', closeMobileMenu);
    
    $mobileNavLinks.add($mobileNavBtns).on('click', function() {
        var $windowWidth = $(window).width();
        if ($windowWidth <= 768) closeMobileMenu();
    });
    
    $(window).on('resize', function() {
        var $windowWidth = $(window).width();
        if ($windowWidth > 768 && $mobileNav.hasClass('active')) closeMobileMenu();
    });
}

// ========== MOBILE SEARCH FUNCTIONS ==========
var $mobileSearchIcon = null;
var $mobileSearchContainer = null;
var $mobileSearchInput = null;
var $document = null;

function cacheMobileSearchElements() {
    $mobileSearchIcon = $('#mobileSearchIcon');
    $mobileSearchContainer = $('#mobileSearchContainer');
    $mobileSearchInput = $('#mobile-search');
    $document = $(document);
}

function initMobileSearch() {
    cacheMobileSearchElements();
    
    if ($mobileSearchIcon.length && $mobileSearchContainer.length) {
        $mobileSearchIcon.on('click', function(e) {
            e.stopPropagation();
            $mobileSearchContainer.toggleClass('active');
            if ($mobileSearchContainer.hasClass('active')) $mobileSearchInput.focus();
        });
        
        $document.on('click', function(event) {
            if ($mobileSearchContainer.length && $mobileSearchContainer.hasClass('active') &&
                !$mobileSearchContainer.is(event.target) && !$mobileSearchIcon.is(event.target) &&
                !$mobileSearchContainer.has(event.target).length) {
                $mobileSearchContainer.removeClass('active');
            }
        });
    }
}

// ========== USER DROPDOWN FUNCTIONS ==========
var $userMenuBtn = null;
var $userDropdown = null;

function cacheUserDropdownElements() {
    $userMenuBtn = $('#userMenuBtn');
    $userDropdown = $('#userDropdown');
}

function initUserDropdown() {
    cacheUserDropdownElements();
    
    if ($userMenuBtn.length && $userDropdown.length) {
        $userMenuBtn.on('click', function(e) {
            e.stopPropagation();
            $userDropdown.toggleClass('active');
            $userMenuBtn.toggleClass('active');
        });
        
        $(document).on('click', function(e) {
            if (!$userMenuBtn.is(e.target) && !$userDropdown.is(e.target) &&
                !$userMenuBtn.has(e.target).length && !$userDropdown.has(e.target).length) {
                $userDropdown.removeClass('active');
                $userMenuBtn.removeClass('active');
            }
        });
    }
}

// ========== MODAL CONTROLS INIT ==========
var $registerBtns = null;
var $loginBtns = null;
var $registerClose = null;
var $loginClose = null;
var $deleteClose = null;
var $switchToRegister = null;
var $switchToLogin = null;

function cacheModalControlElements() {
    cacheModalElements();
    
    $registerBtns = $('#registerBtn, #mobile-register-btn');
    $loginBtns = $('#loginBtn, #mobile-login-btn');
    $registerClose = $('#register-modal .btn-close, #register-modal .modal-close');
    $loginClose = $('#login-modal .btn-close, #login-modal .modal-close');
    $deleteClose = $('#delete-modal .btn-close, #delete-modal .modal-close, #delete-modal .delete-cancel-btn');
    $switchToRegister = $('#switch-to-register');
    $switchToLogin = $('#switch-to-login');
}

function initModalControls() {
    cacheModalControlElements();
    
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
    
    if ($registerClose.length) $registerClose.on('click', function() { closeModal($registerModal); });
    if ($loginClose.length) $loginClose.on('click', function() { closeModal($loginModal); });
    if ($deleteClose.length) $deleteClose.on('click', function() { closeModal($deleteModal); });
    
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

// ========== SET ACTIVE NAVIGATION LINK ==========
var $mainNavLinks = null;
var $mobileNavLinksForActive = null;

function cacheActiveLinkElements() {
    $mainNavLinks = $('.main-nav a');
    $mobileNavLinksForActive = $('.mobile-nav-links a');
}

function setActiveLink() {
    cacheActiveLinkElements();
    
    var path = window.location.pathname;
    var currentPage = path.substring(path.lastIndexOf('/') + 1) || 'index.php';
    
    var $allNavLinks = $mainNavLinks.add($mobileNavLinksForActive);
    
    $allNavLinks.each(function() {
        var $link = $(this);
        var href = $link.attr('href');
        if (!href) return;
        
        var hrefPage = href.substring(href.lastIndexOf('/') + 1);
        if (hrefPage.indexOf('?') !== -1) hrefPage = hrefPage.substring(0, hrefPage.indexOf('?'));
        
        $link.removeClass('active');
        if (hrefPage === currentPage) $link.addClass('active');
    });
}

// ========== INIT FLASH MESSAGES ==========
var $flashMsg = null;

function initFlashMessages() {
    $flashMsg = $('.flash-message');
    if ($flashMsg.length) {
        setTimeout(function() { $flashMsg.fadeOut(500); }, 4000);
    }
}

// ========== DOCUMENT READY ==========
$(function() {
    // Mobile Menu Toggle
    initMobileMenu();
    
    // Mobile Search
    initMobileSearch();
    
    // Modal Controls
    initModalControls();
    
    // User Dropdown
    initUserDropdown();
    
    // Auto-hide flash messages
    initFlashMessages();
    
    // Active Navigation Link
    setActiveLink();
    
    // Error clearing on input
    initErrorClearingOnInput();
    
    // AJAX handlers
    initAjaxLogin();
    initAjaxRegister();
    
    // Cart functions
    updateCartCount();
    loadCart();
});