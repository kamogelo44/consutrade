/*
 * ConsuTrade - Main JavaScript File (jQuery Version)
 * Author: Kamogelo Phale
 * 
 * Handles mobile menu, search, password toggle, modals, cart, dropdowns, toast notifications
 */

// Base URL for all fetch requests
var baseUrl = '/www/consutrade/';

// ========== GLOBAL ESCAPE HTML FUNCTION ==========
function escapeHtml(text) {
    if (!text) return '';
    return $('<div>').text(text).html();
}

// ========== TOAST NOTIFICATIONS (No emojis) ==========
function showToast(message, type = 'success') {
    $('.toast-notification').remove();
    
    var toast = $(`
        <div class="toast-notification toast-${type}">
            <div class="toast-message">${escapeHtml(message)}</div>
        </div>
    `);
    
    $('body').append(toast);
    
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

// ========== ERROR CLEARING ==========
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

function clearModalErrors($modal) {
    $modal.find('.error-container').hide().empty();
    $modal.find('.input-group').removeClass('error');
    $modal.find('.error-text').remove();
}

// ========== CART FUNCTIONS ==========
function addToCart(productId, productName, productPrice) {
    $.ajax({
        url: baseUrl + 'php/endpoints/add-to-cart.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ product_id: productId, product_name: productName, product_price: productPrice }),
        success: function(data) {
            if (data.success) {
                updateCartCount();
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
                updateCartCount();
                if (window.location.pathname.includes('cart.php')) {
                    location.reload();
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

function updateCartCount() {
    $.get(baseUrl + 'php/endpoints/get-cart.php', function(data) {
        if (data.success) {
            $('.cart-count').text(data.item_count);
            $('.item-num').text(data.item_count);
        }
    }).fail(function() {});
}

function loadCart() {
    if (!window.location.pathname.includes('cart.php')) return;
    
    $.get(baseUrl + 'php/get-cart.php', function(data) {
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
        
        var imagePath = item.image;
        if (imagePath && !imagePath.startsWith('http') && !imagePath.startsWith('/')) {
            imagePath = baseUrl + imagePath;
        }
        
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
    $('.qty-increase').off('click').on('click', function() {
        var cartId = $(this).data('cart-id');
        var $input = $('.qty-input[data-cart-id="' + cartId + '"]');
        var currentVal = parseInt($input.val());
        var maxVal = parseInt($input.attr('max'));
        if (!isNaN(currentVal) && currentVal < maxVal) {
            $input.val(currentVal + 1);
            updateCartQuantity(cartId, currentVal + 1);
        }
    });
    
    $('.qty-decrease').off('click').on('click', function() {
        var cartId = $(this).data('cart-id');
        var $input = $('.qty-input[data-cart-id="' + cartId + '"]');
        var currentVal = parseInt($input.val());
        if (!isNaN(currentVal) && currentVal > 1) {
            $input.val(currentVal - 1);
            updateCartQuantity(cartId, currentVal - 1);
        }
    });
    
    $('.qty-input').off('change').on('change', function() {
        var cartId = $(this).data('cart-id');
        var quantity = parseInt($(this).val());
        var maxVal = parseInt($(this).attr('max'));
        if (isNaN(quantity) || quantity < 1) quantity = 1;
        if (quantity > maxVal) {
            quantity = maxVal;
            alert('Only ' + maxVal + ' available in stock.');
        }
        $(this).val(quantity);
        updateCartQuantity(cartId, quantity);
    });
    
    $('.remove-btn').off('click').on('click', function() {
        var productId = $(this).data('product-id');
        if (confirm('Remove this item from your cart?')) {
            removeFromCart(productId);
        }
    });
}

function updateOrderSummary(cartData) {
    if ($('.sub-total-val').length) $('.sub-total-val').text(cartData.subtotal);
    if ($('.deliv-fee-val').length) $('.deliv-fee-val').text(cartData.delivery_fee);
    if ($('.total-val').length) $('.total-val').text(cartData.total);
}

// ========== MODAL FUNCTIONS ==========
function openModal($modal) {
    if (!$modal.length) return;
    
    var $content = $modal.find('.modal-content');
    clearModalErrors($modal);
    $content.removeClass('animate-in animate-out');
    
    $modal.css('visibility', 'visible');
    $modal.addClass('active');
    
    $modal[0].offsetHeight;
    $content.addClass('animate-in');
    $('body').css('overflow', 'hidden');
    
    setTimeout(function() { $content.removeClass('animate-in'); }, 350);
}

function closeModal($modal) {
    if (!$modal.length) return;
    
    var $content = $modal.find('.modal-content');
    clearModalErrors($modal);
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

// ========== ERROR CLEARING ON INPUT ==========
function initErrorClearingOnInput() {
    $('#login-email, #login-password').on('input', function() { clearLoginErrors(); });
    $('#register-full-name, #register-email, #register-phone, #register-password, #register-confirm-password').on('input', function() {
        clearRegisterErrors();
        $(this).closest('.input-group').removeClass('error');
        $(this).closest('.input-group').find('.error-text').remove();
    });
    $('#switch-to-register').on('click', function() { clearLoginErrors(); });
    $('#switch-to-login').on('click', function() { clearRegisterErrors(); });
}

// ========== DOCUMENT READY ==========
$(function() {
    // Mobile Menu Toggle
    var $mainToggle = $('#mobileMenuToggle');
    var $sideClose = $('#sideMenuClose');
    var $mobileNav = $('#mobileNav');
    var $overlay = $('#mobileMenuOverlay');
    
    function openMenu() {
        $mainToggle.addClass('active');
        $mobileNav.addClass('active');
        $overlay.addClass('active');
        $('body').addClass('menu-open').css('overflow', 'hidden');
    }
    
    function closeMenu() {
        $mainToggle.removeClass('active');
        $mobileNav.removeClass('active');
        $overlay.removeClass('active');
        $('body').removeClass('menu-open').css('overflow', '');
    }
    
    if ($mainToggle.length) {
        $mainToggle.on('click', function() {
            if ($mobileNav.hasClass('active')) closeMenu();
            else openMenu();
        });
    }
    
    if ($sideClose.length) $sideClose.on('click', closeMenu);
    if ($overlay.length) $overlay.on('click', closeMenu);
    
    $('.mobile-nav-links a, .mobile-nav-btn').on('click', function() {
        if ($(window).width() <= 768) closeMenu();
    });
    
    $(window).on('resize', function() {
        if ($(window).width() > 768 && $mobileNav.hasClass('active')) closeMenu();
    });
    
    // Mobile Search
    var $mobileSearchIcon = $('#mobileSearchIcon');
    var $mobileSearchContainer = $('#mobileSearchContainer');
    
    if ($mobileSearchIcon.length && $mobileSearchContainer.length) {
        $mobileSearchIcon.on('click', function(e) {
            e.stopPropagation();
            $mobileSearchContainer.toggleClass('active');
            if ($mobileSearchContainer.hasClass('active')) $('#mobile-search').focus();
        });
        
        $(document).on('click', function(event) {
            if ($mobileSearchContainer.length && $mobileSearchContainer.hasClass('active') &&
                !$mobileSearchContainer.is(event.target) && !$mobileSearchIcon.is(event.target) &&
                !$mobileSearchContainer.has(event.target).length) {
                $mobileSearchContainer.removeClass('active');
            }
        });
    }
    
    // Modal Controls
    var $registerModal = $('#register-modal');
    var $loginModal = $('#login-modal');
    var $deleteModal = $('#delete-modal');
    var $registerBtns = $('#registerBtn, #mobile-register-btn');
    var $loginBtns = $('#loginBtn, #mobile-login-btn');
    var $registerClose = $('#register-modal .btn-close, #register-modal .modal-close');
    var $loginClose = $('#login-modal .btn-close, #login-modal .modal-close');
    var $deleteClose = $('#delete-modal .btn-close, #delete-modal .modal-close, #delete-modal .delete-cancel-btn');
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
    
    if ($registerClose.length) $registerClose.on('click', function() { closeModal($registerModal); });
    if ($loginClose.length) $loginClose.on('click', function() { closeModal($loginModal); });
    if ($deleteClose.length) $deleteClose.on('click', function() { closeModal($deleteModal); });
    
    $registerModal.on('click', function(e) { if ($(e.target).is($registerModal)) closeModal($registerModal); });
    $loginModal.on('click', function(e) { if ($(e.target).is($loginModal)) closeModal($loginModal); });
    if ($deleteModal.length) $deleteModal.on('click', function(e) { if ($(e.target).is($deleteModal)) closeModal($deleteModal); });
    
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
    
    // User Dropdown
    var $userMenuBtn = $('#userMenuBtn');
    var $userDropdown = $('#userDropdown');
    
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
    
    // Auto-close modal on success
    var $flashMessage = $('.flash');
    if ($flashMessage.length) {
        if ($('#register-modal').hasClass('active')) $('#register-modal').removeClass('active');
        if ($('#login-modal').hasClass('active')) $('#login-modal').removeClass('active');
        
        setTimeout(function() {
            $flashMessage.css('opacity', '0').css('transition', 'opacity 0.5s ease');
            setTimeout(function() { $flashMessage.remove(); }, 500);
        }, 5000);
    }
    
    // Active Navigation Link
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
            if (hrefPage === currentPage) $link.addClass('active');
        });
    }
    
    setActiveLink();
    initErrorClearingOnInput();
    updateCartCount();
    loadCart();
});