// main.js
/*
 * ConsuTrade - Main JavaScript File (jQuery Version)
 * Author: Kamogelo Phale
 * 
 * Handles mobile menu, search, password toggle, modals, cart, dropdowns, toast notifications
 */

// Base URL will be set by footer.php
var baseUrl = baseUrl || '';

// ========== GLOBAL VARIABLE DECLARATIONS ==========
var $toastContainer = null;
var $existingToasts = null;
var $orderModal = null;
var $orderModalBody = null;
var $orderModalFooter = null;
var $cartCountElements = null;
var $subTotalVal = null;
var $delivFeeVal = null;
var $totalVal = null;
var $registerModal = null;
var $loginModal = null;
var $deleteModal = null;
var $bodyElement = null;
var $loginErrorContainer = null;
var $loginFormInputGroups = null;
var $loginFormErrorTexts = null;
var $registerErrorContainer = null;
var $registerFormInputGroups = null;
var $registerFormErrorTexts = null;
var $loginEmailInput = null;
var $loginPasswordInput = null;
var $registerFullNameInput = null;
var $registerEmailInput = null;
var $registerPhoneInput = null;
var $registerPasswordInput = null;
var $registerConfirmPasswordInput = null;
var $switchToRegisterBtn = null;
var $switchToLoginBtn = null;
var $loginForm = null;
var $loginFormSubmitBtn = null;
var $registerForm = null;
var $menuToggle = null;
var $closeMenu = null;
var $mobileMenu = null;
var $menuOverlay = null;
var $mobileNavLinks = null;
var $mobileNavBtns = null;
var $mobileSearchIcon = null;
var $mobileSearchContainer = null;
var $document = null;
var $accountBtn = null;
var $accountDropdown = null;
var $registerBtns = null;
var $loginBtns = null;
var $modalCloseBtns = null;
var $switchToRegister = null;
var $switchToLogin = null;
var $mainNavLinks = null;
var $mobileNavLinksForActive = null;
var $flashMsg = null;

// ========== GLOBAL ESCAPE HTML FUNCTION ==========

/**
 * Escapes HTML special characters to prevent XSS attacks
 * 
 * @param {string} text - The text to escape
 * @returns {string} HTML-escaped text
 * 
 * @example
 * escapeHtml('<script>alert("xss")</script>') 
 * // Returns '&lt;script&gt;alert("xss")&lt;/script&gt;'
 */
function escapeHtml(text) {
    if (!text) return '';
    return $('<div>').text(text).html();
}

// ========== HELPER FUNCTIONS ==========

/**
 * Capitalizes the first letter of a string
 * 
 * @param {string} str - The string to capitalize
 * @returns {string} String with first letter capitalized
 * 
 * @example
 * capitalizeFirst('hello') // Returns 'Hello'
 */
function capitalizeFirst(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

/**
 * Returns CSS class name for order status
 * 
 * @param {string} status - Order status (pending, processing, shipped, completed, cancelled)
 * @returns {string} CSS class name for the status badge
 * 
 * @example
 * getStatusClass('pending') // Returns 'status-pending'
 */
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

/**
 * Fixes image URL to ensure correct path
 * 
 * @param {string} url - The image URL to fix
 * @param {string} [defaultPath='images/default-product.png'] - Fallback image path
 * @returns {string} Corrected absolute URL
 * 
 * @example
 * fixImageUrl('uploads/products/image.jpg') // Returns full URL
 */
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

/**
 * Renders pagination controls for product listings
 * 
 * @param {Object} $container - jQuery object of the pagination container
 * @param {number} currentPage - Current active page number
 * @param {number} totalPages - Total number of pages
 * @param {Function} onPageChange - Callback function when page changes, receives new page number
 * @returns {void}
 * 
 * @example
 * renderPagination($('#pagination'), 2, 10, function(page) {
 *     loadProducts(page);
 * });
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

// ========== TOAST NOTIFICATIONS ==========

/**
 * Displays a temporary toast notification message
 * 
 * @param {string} message - The message to display
 * @param {string} [type='success'] - Toast type: 'success', 'error', 'info', 'warning'
 * @returns {void}
 * 
 * @sideeffect Creates and removes DOM elements
 * @sideeffect Automatically hides after 4 seconds
 * 
 * @example
 * showToast('Item added to cart', 'success');
 * showToast('Connection failed', 'error');
 */
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

/**
 * Displays a success toast notification
 * 
 * @param {string} message - Success message to display
 * @returns {void}
 */
function showSuccessToast(message) { showToast(message, 'success'); }

/**
 * Displays an error toast notification
 * 
 * @param {string} message - Error message to display
 * @returns {void}
 */
function showErrorToast(message) { showToast(message, 'error'); }

/**
 * Displays an info toast notification
 * 
 * @param {string} message - Info message to display
 * @returns {void}
 */
function showInfoToast(message) { showToast(message, 'info'); }

/**
 * Displays a warning toast notification
 * 
 * @param {string} message - Warning message to display
 * @returns {void}
 */
function showWarningToast(message) { showToast(message, 'warning'); }

// ========== PASSWORD TOGGLE ==========

/**
 * Toggles password field visibility between text and password
 * 
 * @param {string} fieldId - ID of the password input field
 * @param {HTMLElement} button - The toggle button element
 * @returns {void}
 * 
 * @sideeffect Changes input type and button icon
 * 
 * @example
 * <button onclick="togglePassword('login-password', this)">Show/Hide</button>
 */
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

// ========== ORDER MODAL FUNCTIONS ==========

/**
 * Caches jQuery objects for order modal elements
 * 
 * @returns {void}
 * 
 * @sideeffect Sets global variables $orderModal, $orderModalBody, $orderModalFooter
 */
function cacheOrderModalElements() {
    if (!$orderModal) {
        $orderModal = $('#orderModal');
        $orderModalBody = $('#orderModalBody');
        $orderModalFooter = $('#orderModalFooter');
    }
}

/**
 * Opens the order details modal and loads order data
 * 
 * @param {number} orderId - The order ID to load
 * @returns {void}
 * 
 * @fires AJAX GET request to get-order-details.php
 */
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

/**
 * Closes the order details modal
 * 
 * @returns {void}
 */
function closeOrderModal() {
    if ($orderModal) $orderModal.removeClass('active');
}

/**
 * Displays order details inside the modal
 * 
 * @param {Object} order - Order object containing details and items
 * @returns {void}
 * 
 * @sideeffect Updates DOM with order information
 */
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

/**
 * Updates order status via AJAX
 * 
 * @param {number} orderId - The order ID to update
 * @param {string} newStatus - New status (pending, processing, shipped, completed, cancelled)
 * @returns {void}
 * 
 * @fires AJAX POST request to update-order-status.php
 * @fires showSuccessToast or showErrorToast on completion
 * @sideeffect Reloads page on success
 */
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

/**
 * Clears all error messages and error states from a modal
 * 
 * @param {string} modalId - CSS selector of the modal (e.g., '#register-modal')
 * @returns {void}
 * 
 * @sideeffect Removes error classes and clears error containers
 */
function clearModalErrors(modalId) {
    var $modal = $(modalId);
    var $modalErrorContainer = $modal.find('.error-container');
    var $modalInputGroups = $modal.find('.input-group');
    var $modalErrorTexts = $modal.find('.error-text');
    
    $modalErrorContainer.hide().empty();
    $modalInputGroups.removeClass('error');
    $modalErrorTexts.remove();
}    

/**
 * Displays validation errors inside a modal
 * 
 * @param {string} modalId - CSS selector of the modal ('#register-modal' or '#login-modal')
 * @param {Object} errors - Error object with field names as keys and error messages as values
 * @param {Object} formData - Submitted form data to restore
 * @returns {void}
 * 
 * @sideeffect Shows error messages and highlights invalid fields
 * 
 * @example
 * displayModalErrors('#register-modal', 
 *     { email: 'Email already exists', password: 'Too short' },
 *     { full_name: 'John Doe', email: 'john@example.com' }
 * );
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
    
    if (modalId === '#register-modal') {
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
        
        $.each(errors, function(field, message) {
            if (field !== 'general' && message && message.trim()) {
                errorMessages.push(message);
                
                var inputId = '';
                switch(field) {
                    case 'full_name': inputId = 'register-full-name'; break;
                    case 'email': inputId = 'register-email'; break;
                    case 'phone': inputId = 'register-phone'; break;
                    case 'password': inputId = 'register-password'; break;
                    case 'confirm_password': inputId = 'register-confirm-password'; break;
                    default: inputId = 'register-' + field;
                }
                var $input = $('#' + inputId);
                if ($input.length) {
                    $input.closest('.input-group').addClass('error');
                }
            }
        });
        
        if (errorMessages.length > 0) {
            $registerErrorContainer.show().html(errorMessages.join('<br>'));
        }
        
    } else if (modalId === '#login-modal') {
        if (formData.email) $loginEmail.val(formData.email);
        
        $loginErrorContainer.hide().empty();
        $('.input-group', $modal).removeClass('error');
        
        if (errors.general && errors.general.trim()) {
            $loginErrorContainer.show().text(errors.general);
        } else if (typeof errors === 'string' && errors.trim()) {
            $loginErrorContainer.show().text(errors);
        }
        
        $.each(errors, function(field, message) {
            if (field !== 'general' && message && message.trim()) {
                var $input = $('#login-' + field);
                if ($input.length) {
                    $input.closest('.input-group').addClass('error');
                }
            }
        });
    }
}

// ========== ERROR CLEARING ==========

/**
 * Caches jQuery objects for login error elements
 * 
 * @returns {void}
 */
function cacheLoginErrorElements() {
    $loginErrorContainer = $('#login-error-container');
    $loginFormInputGroups = $('#login-form .input-group');
    $loginFormErrorTexts = $('#login-form .error-text');
}

/**
 * Caches jQuery objects for register error elements
 * 
 * @returns {void}
 */
function cacheRegisterErrorElements() {
    $registerErrorContainer = $('#register-error-container');
    $registerFormInputGroups = $('#register-form .input-group');
    $registerFormErrorTexts = $('#register-form .error-text');
}

/**
 * Clears all login form errors
 * 
 * @returns {void}
 * 
 * @sideeffect Hides error container, removes error classes and error text
 */
function clearLoginErrors() {
    cacheLoginErrorElements();
    if ($loginErrorContainer) $loginErrorContainer.hide().empty();
    if ($loginFormInputGroups) $loginFormInputGroups.removeClass('error');
    if ($loginFormErrorTexts) $loginFormErrorTexts.remove();
}

/**
 * Clears all register form errors
 * 
 * @returns {void}
 * 
 * @sideeffect Hides error container, removes error classes and error text
 */
function clearRegisterErrors() {
    cacheRegisterErrorElements();
    if ($registerErrorContainer) $registerErrorContainer.hide().empty();
    if ($registerFormInputGroups) $registerFormInputGroups.removeClass('error');
    if ($registerFormErrorTexts) $registerFormErrorTexts.remove();
}

/**
 * Legacy function to clear modal errors
 * 
 * @param {Object} $modal - jQuery object of the modal
 * @returns {void}
 * 
 * @deprecated Use clearModalErrors() instead
 */
function clearModalErrorsOld($modal) {
    var $modalErrorContainer = $modal.find('.error-container');
    var $modalInputGroups = $modal.find('.input-group');
    var $modalErrorTexts = $modal.find('.error-text');
    
    $modalErrorContainer.hide().empty();
    $modalInputGroups.removeClass('error');
    $modalErrorTexts.remove();
}

// ========== CART FUNCTIONS ==========

/**
 * Gets or creates cached jQuery object for cart count elements
 * 
 * @returns {Object} jQuery object containing cart count elements
 */
function getCartCountElements() {
    if (!$cartCountElements) {
        $cartCountElements = $('.cart-count, .item-num');
    }
    return $cartCountElements;
}

/**
 * Updates the cart count display across all cart badges
 * 
 * @param {number} count - New cart item count
 * @returns {void}
 * 
 * @sideeffect Updates DOM and sessionStorage
 */
function updateCartCountDisplay(count) {
    var $elements = getCartCountElements();
    $elements.text(count);
    if (window.sessionStorage) sessionStorage.setItem('cart_count', count);
}

/**
 * Adds a product to the shopping cart
 * 
 * @param {number} productId - Product ID
 * @param {string} productName - Product name
 * @param {number} productPrice - Product price
 * @returns {void}
 * 
 * @fires AJAX POST request to add-to-cart.php
 * @fires showSuccessToast or showErrorToast on completion
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
 * Removes a product from the shopping cart
 * 
 * @param {number} productId - Product ID to remove
 * @returns {void}
 * 
 * @fires AJAX POST request to remove-from-cart.php
 * @fires showSuccessToast or showErrorToast on completion
 * @sideeffect Reloads page if on cart.php
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
                if (window.location.pathname.includes('cart.php')) location.reload();
                else showSuccessToast(data.message || 'Item removed from cart');
            } else {
                showErrorToast(data.message || 'Error removing item from cart');
            }
        },
        error: function() { showErrorToast('Something went wrong'); }
    });
}

/**
 * Updates the cart count from cache or server
 * 
 * @returns {void}
 * 
 * @sideeffect Updates cart count badge
 */
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

/**
 * Loads and displays cart items on cart.php page
 * 
 * @returns {void}
 * 
 * @sideeffect Populates cart table and mobile cart items
 */
function loadCart() {
    if (!window.location.pathname.includes('cart.php')) return;
    
    $.get(baseUrl + 'php/endpoints/get-cart.php', function(data) {
        if (data.success) {
            displayCartItems(data);
            updateOrderSummary(data);
        }
    }).fail(function() { console.log('Error loading cart'); });
}

/**
 * Renders cart items in both desktop table and mobile card views
 * 
 * @param {Object} cartData - Cart data containing items array
 * @returns {void}
 * 
 * @sideeffect Updates DOM with cart items
 */
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

/**
 * Attaches event handlers for cart quantity controls and remove buttons
 * 
 * @returns {void}
 * 
 * @sideeffect Binds click and change events to cart controls
 */
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

/**
 * Updates the order summary totals in the cart sidebar
 * 
 * @param {Object} cartData - Cart data containing subtotal, delivery_fee, and total
 * @returns {void}
 * 
 * @sideeffect Updates DOM with new totals
 */
function updateOrderSummary(cartData) {
    $subTotalVal = $('.sub-total-val');
    $delivFeeVal = $('.deliv-fee-val');
    $totalVal = $('.total-val');
    
    if ($subTotalVal.length) $subTotalVal.text(cartData.subtotal);
    if ($delivFeeVal.length) $delivFeeVal.text(cartData.delivery_fee);
    if ($totalVal.length) $totalVal.text(cartData.total);
}

/**
 * Updates cart item quantity via AJAX
 * 
 * @param {number} cartId - Cart item ID
 * @param {number} quantity - New quantity
 * @returns {void}
 * 
 * @fires AJAX POST request to update-cart.php
 * @sideeffect Reloads page on success
 */
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

/**
 * Caches jQuery objects for modal elements
 * 
 * @returns {void}
 */
function cacheModalElements() {
    if (!$registerModal) $registerModal = $('#register-modal');
    if (!$loginModal) $loginModal = $('#login-modal');
    if (!$deleteModal) $deleteModal = $('#delete-modal');
    if (!$bodyElement) $bodyElement = $('body');
}

/**
 * Opens a modal with animation
 * 
 * @param {Object} $modal - jQuery object of the modal to open
 * @returns {void}
 * 
 * @sideeffect Adds active class, locks body scroll
 */
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

/**
 * Closes a modal with animation
 * 
 * @param {Object} $modal - jQuery object of the modal to close
 * @returns {void}
 * 
 * @sideeffect Removes active class, restores body scroll
 */
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

/**
 * Caches DOM elements used for error clearing
 * 
 * @returns {void}
 */
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

/**
 * Initializes input event handlers to clear errors when user types
 * 
 * @returns {void}
 * 
 * @sideeffect Binds input event handlers
 */
function initErrorClearingOnInput() {
    cacheErrorClearingElements();
    
    $loginEmailInput.add($loginPasswordInput).on('input', function() { 
        clearLoginErrors(); 
    });
    
    $registerFullNameInput.add($registerEmailInput).add($registerPhoneInput).add($registerPasswordInput).add($registerConfirmPasswordInput).on('input', function() {
        $('#register-error-container').hide().empty();
        var $this = $(this);
        var $closestInputGroup = $this.closest('.input-group');
        $closestInputGroup.removeClass('error');
    });
    
    $switchToRegisterBtn.on('click', function() { 
        clearLoginErrors(); 
    });
    
    $switchToLoginBtn.on('click', function() { 
        clearRegisterErrors(); 
    });
}

// ========== AJAX LOGIN HANDLER ==========

/**
 * Caches login form jQuery object
 * 
 * @returns {void}
 */
function cacheLoginFormElements() {
    $loginForm = $('#login-form');
}

/**
 * Initializes AJAX login form submission handler
 * 
 * @returns {void}
 * 
 * @sideeffect Prevents default form submission, sends AJAX request
 */
function initAjaxLogin() {
    cacheLoginFormElements();
    
    if (!$loginForm.length) return;
    
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
                    var errors = { general: response.message };
                    displayModalErrors('#login-modal', errors, { email: $('#login-email').val() });
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

// ========== AJAX REGISTER HANDLER ==========

/**
 * Caches register form jQuery object
 * 
 * @returns {void}
 */
function cacheRegisterFormElements() {
    $registerForm = $('#register-form');
}

/**
 * Initializes AJAX register form submission handler
 * 
 * @returns {void}
 * 
 * @sideeffect Prevents default form submission, sends AJAX request
 */
function initAjaxRegister() {
    cacheRegisterFormElements();
    
    if (!$registerForm.length) return;
    
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

/**
 * Caches jQuery objects for mobile menu elements
 * 
 * @returns {void}
 */
function cacheMobileMenuElements() {
    $menuToggle = $('#menuToggle');
    $closeMenu = $('#closeMenu');
    $mobileMenu = $('#mobileMenu');
    $menuOverlay = $('#menuOverlay');
    $mobileNavLinks = $('.mobile-nav-links a');
    $mobileNavBtns = $('.mobile-nav-links button');
}

/**
 * Opens the mobile navigation menu
 * 
 * @returns {void}
 * 
 * @sideeffect Adds active classes, locks body scroll
 */
function openMobileMenu() {
    cacheMobileMenuElements();
    $menuToggle.addClass('active');
    $mobileMenu.addClass('active');
    $menuOverlay.addClass('active');
    $('body').css('overflow', 'hidden');
}

/**
 * Closes the mobile navigation menu
 * 
 * @returns {void}
 * 
 * @sideeffect Removes active classes, restores body scroll
 */
function closeMobileMenu() {
    cacheMobileMenuElements();
    $menuToggle.removeClass('active');
    $mobileMenu.removeClass('active');
    $menuOverlay.removeClass('active');
    $('body').css('overflow', '');
}

/**
 * Initializes mobile menu toggle functionality
 * 
 * @returns {void}
 * 
 * @sideeffect Binds click events for menu toggle, close button, and overlay
 */
function initMobileMenu() {
    cacheMobileMenuElements();
    
    if ($menuToggle.length) {
        $menuToggle.on('click', function() {
            if ($mobileMenu.hasClass('active')) closeMobileMenu();
            else openMobileMenu();
        });
    }
    
    if ($closeMenu.length) $closeMenu.on('click', closeMobileMenu);
    if ($menuOverlay.length) $menuOverlay.on('click', closeMobileMenu);
    
    $mobileNavLinks.add($mobileNavBtns).on('click', function() {
        var $windowWidth = $(window).width();
        if ($windowWidth <= 768) closeMobileMenu();
    });
    
    $(window).on('resize', function() {
        var $windowWidth = $(window).width();
        if ($windowWidth > 768 && $mobileMenu.hasClass('active')) closeMobileMenu();
    });
}

// ========== MOBILE SEARCH FUNCTIONS ==========

/**
 * Caches jQuery objects for mobile search elements
 * 
 * @returns {void}
 */
function cacheMobileSearchElements() {
    $mobileSearchIcon = $('#mobileSearchIcon');
    $mobileSearchContainer = $('#mobileSearch');
    $document = $(document);
}

/**
 * Initializes mobile search toggle functionality
 * 
 * @returns {void}
 * 
 * @sideeffect Binds click events for search icon and document clicks
 */
function initMobileSearch() {
    cacheMobileSearchElements();
    
    if ($mobileSearchIcon.length && $mobileSearchContainer.length) {
        $mobileSearchIcon.on('click', function(e) {
            e.stopPropagation();
            $mobileSearchContainer.toggleClass('active');
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

/**
 * Caches jQuery objects for user dropdown elements
 * 
 * @returns {void}
 */
function cacheUserDropdownElements() {
    $accountBtn = $('#accountBtn');
    $accountDropdown = $('#accountDropdown');
}

/**
 * Initializes user account dropdown functionality
 * 
 * @returns {void}
 * 
 * @sideeffect Binds click events for dropdown toggle and document clicks
 */
function initUserDropdown() {
    cacheUserDropdownElements();
    
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

// ========== MODAL CONTROLS INIT ==========

/**
 * Caches jQuery objects for modal control buttons
 * 
 * @returns {void}
 */
function cacheModalControlElements() {
    cacheModalElements();
    
    $registerBtns = $('#registerBtn, #mobileRegisterBtn');
    $loginBtns = $('#loginBtn, #mobileLoginBtn');
    $modalCloseBtns = $('.modal-close, .btn-close');
    $switchToRegister = $('#switch-to-register');
    $switchToLogin = $('#switch-to-login');
}

/**
 * Initializes all modal control buttons (open, close, switch)
 * 
 * @returns {void}
 * 
 * @sideeffect Binds click events for all modal controls
 */
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

// ========== SET ACTIVE NAVIGATION LINK ==========

/**
 * Caches jQuery objects for navigation links
 * 
 * @returns {void}
 */
function cacheActiveLinkElements() {
    $mainNavLinks = $('.main-nav a');
    $mobileNavLinksForActive = $('.mobile-nav-links a');
}

/**
 * Sets the active class on navigation links based on current page
 * 
 * @returns {void}
 * 
 * @sideeffect Adds/removes 'active' class from navigation links
 */
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

/**
 * Initializes auto-hide for flash messages
 * 
 * @returns {void}
 * 
 * @sideeffect Sets timeout to fade out flash messages after 4 seconds
 */
function initFlashMessages() {
    $flashMsg = $('.flash-message');
    if ($flashMsg.length) {
        setTimeout(function() { $flashMsg.fadeOut(500); }, 4000);
    }
}

// ========== DOCUMENT READY ==========

/**
 * Initializes all functionality when DOM is ready
 * 
 * @returns {void}
 * 
 * @fires All initialization functions
 */
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