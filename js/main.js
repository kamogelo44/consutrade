/*
 * ConsuTrade - Main JavaScript File (jQuery Version)
 * Author: Kamogelo Phale
 * 
 * This file handles:
 * - Mobile menu toggle (hamburger)
 * - Mobile search bar
 * - Password show/hide
 * - Login/register modal popups
 * - Cart count updates
 * - User dropdown menus (desktop + mobile)
 * - Sell link behavior for logged-in buyers
 * - Active navigation link highlighting
 * - Error message clearing on input
 */

// Base URL for all fetch requests
var baseUrl = '/www/consutrade/';

// ========== GLOBAL PASSWORD TOGGLE FUNCTION ==========
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

// ========== ERROR CLEARING FUNCTIONS ==========
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

// ========== GLOBAL CART FUNCTIONS ==========
function addToCart(productId, productName, productPrice) {
    $.ajax({
        url: baseUrl + 'php/add-to-cart.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            product_id: productId,
            product_name: productName,
            product_price: productPrice
        }),
        success: function(data) {
            if (data.success) {
                updateCartCount();
                // Optional: Show success toast instead of alert
                showSuccessToast(data.message || 'Item added to cart!');
            } else {
                showErrorToast(data.message || 'Error adding item to cart');
            }
        },
        error: function() {
            showErrorToast('Something went wrong');
        }
    });
}

// Optional: Toast notifications (more user-friendly than alerts)
function showSuccessToast(message) {
    // You can implement a nice toast notification here
    alert(message); // Fallback to alert for now
}

function showErrorToast(message) {
    alert(message);
}

function updateCartItem(productId, quantity) {
    $.ajax({
        url: baseUrl + 'php/update-cart.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            product_id: productId,
            quantity: quantity
        }),
        success: function(data) {
            if (data.success) {
                updateCartCount();
                location.reload();
            }
        },
        error: function() {
            showErrorToast('Something went wrong');
        }
    });
}

function removeFromCart(productId) {
    if (!confirm('Are you sure you want to remove this item from your cart?')) {
        return;
    }
    
    $.ajax({
        url: baseUrl + 'php/remove-from-cart.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ product_id: productId }),
        success: function(data) {
            if (data.success) {
                updateCartCount();
                location.reload();
            } else {
                showErrorToast(data.message || 'Error removing item from cart');
            }
        },
        error: function() {
            showErrorToast('Something went wrong');
        }
    });
}

function updateCartCount() {
    $.get(baseUrl + 'php/get-cart.php', function(data) {
        if (data.success) {
            var cartCount = data.item_count;
            $('.cart-count').text(cartCount);
            
            var itemNum = $('.item-num');
            if (itemNum.length) {
                itemNum.text(cartCount);
            }
        }
    }).fail(function() {
        // Silent fail - don't show error for cart count
    });
}

function loadCart() {
    if (window.location.pathname.includes('cart.php')) {
        $.get(baseUrl + 'php/get-cart.php', function(data) {
            if (data.success) {
                displayCartItems(data);
                updateOrderSummary(data);
            }
        }).fail(function() {
            console.log('Error loading cart');
        });
    }
}

function displayCartItems(cartData) {
    var $desktopTableBody = $('#cart-table-body');
    var $mobileContainer = $('#mobile-cart-items');
    var $emptyCartDiv = $('#empty-cart');
    var $cartLayout = $('#cart-layout');
    var $cartItemCount = $('#cart-item-count');
    
    if (!cartData.items || cartData.items.length === 0) {
        $emptyCartDiv.css('display', 'flex');
        $cartLayout.css('display', 'none');
        $cartItemCount.text('0');
        return;
    }
    
    $emptyCartDiv.css('display', 'none');
    $cartLayout.css('display', 'flex');
    $cartItemCount.text(cartData.items.length);
    
    $desktopTableBody.empty();
    $mobileContainer.empty();
    
    $.each(cartData.items, function(index, item) {
        var verifiedBadge = item.is_verified ? 
            '<div class="verified-badge-cart"><img src="' + baseUrl + 'images/icons/verified-svgrepo-com.svg" width="14px" height="14px" alt="verification"><span>Verified Seller</span></div>' : 
            '<div class="unverified-badge-cart"><img src="' + baseUrl + 'images/icons/not-verified-svgrepo-com.svg" width="14px" height="14px" alt="not-verified"><span>Unverified</span></div>';
        
        var imagePath = item.image;
        if (imagePath && !imagePath.startsWith('http') && !imagePath.startsWith('/')) {
            imagePath = baseUrl + imagePath;
        }
        
        // Desktop table row
        if ($desktopTableBody.length) {
            var row = $('<tr>').html(`
                <td class="product-cell" data-label="Product">
                    <div class="cart-product-wrapper">
                        <div class="cart-img-container">
                            <img src="${imagePath}" alt="${escapeHtml(item.product_name)}" onerror="this.src='${baseUrl}images/default-product.png'">
                        </div>
                        <div class="cart-prod-info">
                            <p class="prod-name">${escapeHtml(item.product_name)}</p>
                        </div>
                    </div>
                </td>
                <td class="seller-cell" data-label="Seller">
                    <div class="seller-cart-info">
                        <p class="seller-name">${escapeHtml(item.seller_name)}</p>
                        <div class="verification">
                            ${verifiedBadge}
                        </div>
                    </div>
                </td>
                <td class="price-cell" data-label="Price">R ${parseFloat(item.price).toFixed(2)}</td>
                <td class="actions-cell" data-label="Actions">
                    <button class="remove-btn" onclick="removeFromCart(${item.product_id})">Remove</button>
                </td>
            `);
            $desktopTableBody.append(row);
        }
        
        // Mobile card
        if ($mobileContainer.length) {
            var card = $('<div>').addClass('mobile-cart-card').html(`
                <div class="mobile-cart-img">
                    <img src="${imagePath}" alt="${escapeHtml(item.product_name)}" onerror="this.src='${baseUrl}images/default-product.png'">
                </div>
                <div class="mobile-cart-details">
                    <h3 class="mobile-prod-name">${escapeHtml(item.product_name)}</h3>
                </div>
                <div class="mobile-cart-seller">
                    <p class="seller-name">${escapeHtml(item.seller_name)}</p>
                    ${verifiedBadge}
                </div>
                <div class="mobile-cart-price">
                    <p class="price">R ${parseFloat(item.price).toFixed(2)}</p>
                </div>
                <div class="mobile-cart-actions">
                    <button class="remove-btn" onclick="removeFromCart(${item.product_id})">Remove</button>
                </div>
            `);
            $mobileContainer.append(card);
        }
    });
}

function updateOrderSummary(cartData) {
    $('.sub-total-val').text(cartData.subtotal);
    $('.deliv-fee-val').text(cartData.delivery_fee);
    $('.total-val').text(cartData.total);
}

function escapeHtml(text) {
    if (!text) return '';
    return $('<div>').text(text).html();
}

// ========== GLOBAL MODAL FUNCTIONS ==========

function openModal($modal) {
    if (!$modal.length) return;
    
    var $content = $modal.find('.modal-content');
    
    // Clear any existing errors when opening modal
    clearModalErrors($modal);
    
    // Remove any lingering animation classes
    $content.removeClass('animate-in animate-out');
    
    // Show the modal
    $modal.css('visibility', 'visible');
    $modal.addClass('active');
    
    $modal[0].offsetHeight;
    
    // Now add the animation
    $content.addClass('animate-in');
    $('body').css('overflow', 'hidden');
    
    // Clean up animation class after it completes
    setTimeout(function() {
        $content.removeClass('animate-in');
    }, 350);
}

function closeModal($modal) {
    if (!$modal.length) return;
    
    var $content = $modal.find('.modal-content');
    
    // Clear errors when closing modal
    clearModalErrors($modal);
    
    // Remove animate-in if present
    $content.removeClass('animate-in');
    
    // Force reflow
    $modal[0].offsetHeight;
    
    // Add animate-out
    $content.addClass('animate-out');
    
    // Hide modal after animation completes
    setTimeout(function() {
        $modal.removeClass('active');
        $modal.css('visibility', 'hidden');
        $content.removeClass('animate-out');
        $('body').css('overflow', '');
    }, 280);
}

// ========== CLEAR ERRORS ON INPUT ==========
function initErrorClearingOnInput() {
    // Login form - clear errors when user starts typing
    $('#login-email, #login-password').on('input', function() {
        clearLoginErrors();
    });
    
    // Register form - clear errors when user starts typing in any field
    $('#register-full-name, #register-email, #register-phone, #register-password, #register-confirm-password').on('input', function() {
        clearRegisterErrors();
        // Also clear the specific field's error highlighting
        $(this).closest('.input-group').removeClass('error');
        $(this).closest('.input-group').find('.error-text').remove();
    });
    
    // Clear errors when switching between login/register via the switch links
    $('#switch-to-register').on('click', function() {
        clearLoginErrors();
    });
    
    $('#switch-to-login').on('click', function() {
        clearRegisterErrors();
    });
}

// Document Ready
$(document).ready(function() { 
    // ========== MOBILE MENU TOGGLE ==========
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
            if ($mobileNav.hasClass('active')) {
                closeMenu();
            } else {
                openMenu();
            }
        });
    }
    
    if ($sideClose.length) {
        $sideClose.on('click', closeMenu);
    }
    
    if ($overlay.length) {
        $overlay.on('click', closeMenu);
    }
    
    $('.mobile-nav-links a, .mobile-nav-btn').on('click', function() {
        if ($(window).width() <= 768) {
            closeMenu();
        }
    });
    
    $(window).on('resize', function() {
        if ($(window).width() > 768 && $mobileNav.hasClass('active')) {
            closeMenu();
        }
    });
    
    // ========== MOBILE SEARCH ==========
    var $mobileSearchIcon = $('#mobileSearchIcon');
    var $mobileSearchContainer = $('#mobileSearchContainer');
    
    if ($mobileSearchIcon.length && $mobileSearchContainer.length) {
        $mobileSearchIcon.on('click', function(e) {
            e.stopPropagation();
            $mobileSearchContainer.toggleClass('active');
            
            if ($mobileSearchContainer.hasClass('active')) {
                $('#mobile-search').focus();
            }
        });
    }
    
    $(document).on('click', function(event) {
        if ($mobileSearchContainer.length && $mobileSearchIcon.length) {
            if (!$mobileSearchContainer.is(event.target) && 
                !$mobileSearchIcon.is(event.target) && 
                !$mobileSearchContainer.has(event.target).length &&
                $mobileSearchContainer.hasClass('active')) {
                $mobileSearchContainer.removeClass('active');
            }
        }
    });
    
    // ========== MODAL CONTROLS ==========
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


    // Register buttons
    if ($registerBtns.length) {
        $registerBtns.on('click', function(e) {
            e.preventDefault();
            if ($loginModal.hasClass('active')) {
                closeModal($loginModal);
            }
            openModal($registerModal);
        });
    }

    // Login buttons
    if ($loginBtns.length) {
        $loginBtns.on('click', function(e) {
            e.preventDefault();
            if ($registerModal.hasClass('active')) {
                closeModal($registerModal);
            }
            openModal($loginModal);
        });
    }

    // Close buttons
    if ($registerClose.length) {
        $registerClose.on('click', function() { 
            closeModal($registerModal); 
        });
    }

    if ($loginClose.length) {
        $loginClose.on('click', function() { 
            closeModal($loginModal); 
        });
    }

    if ($deleteClose.length) {
        $deleteClose.on('click', function() { 
            closeModal($deleteModal); 
        });
    }

    // Close modal when clicking outside
    $registerModal.on('click', function(e) {
        if ($(e.target).is($registerModal)) {
            closeModal($registerModal);
        }
    });

    $loginModal.on('click', function(e) {
        if ($(e.target).is($loginModal)) {
            closeModal($loginModal);
        }
    });

    if ($deleteModal.length) {
        $deleteModal.on('click', function(e) {
            if ($(e.target).is($deleteModal)) {
                closeModal($deleteModal);
            }
        });
    }

    // Switch between modals - clear errors when switching
    if ($switchToRegister.length) {
        $switchToRegister.on('click', function(e) {
            e.preventDefault();
            clearLoginErrors(); // Clear login errors before switching
            closeModal($loginModal);
            setTimeout(function() { 
                openModal($registerModal); 
            }, 300);
        });
    }

    if ($switchToLogin.length) {
        $switchToLogin.on('click', function(e) {
            e.preventDefault();
            clearRegisterErrors(); // Clear register errors before switching
            closeModal($registerModal);
            setTimeout(function() { 
                openModal($loginModal); 
            }, 300);
        });
    }
    
    // ========== USER DROPDOWN TOGGLE ==========
    var $userMenuBtn = $('#userMenuBtn');
    var $userDropdown = $('#userDropdown');
    
    if ($userMenuBtn.length && $userDropdown.length) {
        $userMenuBtn.on('click', function(e) {
            e.stopPropagation();
            $userDropdown.toggleClass('active');
            $userMenuBtn.toggleClass('active');
        });
        
        $(document).on('click', function(e) {
            if (!$userMenuBtn.is(e.target) && 
                !$userDropdown.is(e.target) && 
                !$userMenuBtn.has(e.target).length && 
                !$userDropdown.has(e.target).length) {
                $userDropdown.removeClass('active');
                $userMenuBtn.removeClass('active');
            }
        });
    }
    
    // ========== AUTO-CLOSE MODAL ON SUCCESS ==========
    var $flashMessage = $('.flash');
    if ($flashMessage.length) {
        if ($('#register-modal').hasClass('active')) {
            $('#register-modal').removeClass('active');
        }
        if ($('#login-modal').hasClass('active')) {
            $('#login-modal').removeClass('active');
        }
        
        setTimeout(function() {
            $flashMessage.css('opacity', '0').css('transition', 'opacity 0.5s ease');
            setTimeout(function() {
                $flashMessage.remove();
            }, 500);
        }, 5000);
    }
    
    // ========== ACTIVE NAVIGATION LINK ==========
    function setActiveLink() {
        var path = window.location.pathname;
        var currentPage = path.substring(path.lastIndexOf('/') + 1);
        
        if (currentPage === '') {
            currentPage = 'index.php';
        }
        
        $('.main-nav a, .mobile-nav-links a').each(function() {
            var $link = $(this);
            var href = $link.attr('href');
            
            $link.removeClass('active');
            
            if (href) {
                var hrefPage = href.substring(href.lastIndexOf('/') + 1);
                
                if (hrefPage.indexOf('?') !== -1) {
                    hrefPage = hrefPage.substring(0, hrefPage.indexOf('?'));
                }
                
                if ((currentPage === 'index.php' || currentPage === 'index.html' || currentPage === '') && 
                    (hrefPage === 'index.php' || hrefPage === 'index.html' || hrefPage === '')) {
                    $link.addClass('active');
                }
                else if (hrefPage === currentPage) {
                    $link.addClass('active');
                }
                else if (currentPage === 'cart.php' && hrefPage === 'cart.php') {
                    $link.addClass('active');
                }
                else if (currentPage === 'sell.php' && hrefPage === 'sell.php') {
                    $link.addClass('active');
                }
                else if (currentPage === 'profile.php' && hrefPage === 'profile.php') {
                    $link.addClass('active');
                }
                else if (currentPage === 'my-orders.php' && hrefPage === 'my-orders.php') {
                    $link.addClass('active');
                }
            }
        });
    }

    setActiveLink();
    
    // ========== INITIALIZE ERROR CLEARING ON INPUT ==========
    initErrorClearingOnInput();
    
    // Run these when page loads
    updateCartCount();
    loadCart();
});