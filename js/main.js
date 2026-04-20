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
                alert(data.message || 'Item added to cart!');
            } else {
                alert(data.message || 'Error adding item to cart');
            }
        },
        error: function() {
            alert('Something went wrong');
        }
    });
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
            alert('Something went wrong');
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
                alert(data.message || 'Error removing item from cart');
            }
        },
        error: function() {
            alert('Something went wrong');
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
    
    // ========== SELL LINK HANDLING ==========
    var $sellLink = $('#sell-link');
    var $sellLinkMobile = $('#sell-link-mobile');

    function handleSellLink(e) {
        if (typeof isLoggedIn !== 'undefined' && isLoggedIn === true) {
            if (typeof currentUserRole !== 'undefined' && currentUserRole === 'buyer') {
                e.preventDefault();
                var userConfirmed = confirm('You are currently registered as a buyer.\n\nWould you like to upgrade to a seller account? This will allow you to list and sell products on ConsuTrade.');
                
                if (userConfirmed) {
                    window.location.href = baseUrl + 'php/upgrade-to-seller.php';
                }
            }
        }
    }

    if ($sellLink.length) {
        $sellLink.on('click', handleSellLink);
    }

    if ($sellLinkMobile.length) {
        $sellLinkMobile.on('click', handleSellLink);
    }
    
    // ========== UPGRADE BANNER BUTTON ==========
    $('#upgrade-to-seller-btn').on('click', function(e) {
        e.preventDefault();
        var userConfirmed = confirm('Are you sure you want to upgrade to a seller account?\n\nYou will be able to list and sell your products on ConsuTrade.');
        
        if (userConfirmed) {
            window.location.href = baseUrl + 'php/upgrade-to-seller.php';
        }
    });
    
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
    
    // Run these when page loads
    updateCartCount();
    loadCart();
});