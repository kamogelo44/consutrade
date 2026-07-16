/**
 * ConsuTrade - Cart Module
 * Cart operations, count display, cart page logic
 * Depends on: jQuery, core/ui.js, core/utils.js
 */

// ============================================================
// CART COUNT
// ============================================================

function getCartCountElements() {
    if (!$cartCountElements) {
        $cartCountElements = $('.cart-count, .item-num, .cart-badge, .mobile-cart-count');
    }
    return $cartCountElements;
}

function updateCartCountDisplay(count) {
    getCartCountElements().text(count);
    if (window.sessionStorage) sessionStorage.setItem('cart_count', count);
}

function updateCartCount() {
    if (!isLoggedIn) {
        updateCartCountDisplay(0);
        return;
    }

    $.get(baseUrl + 'php/endpoints/cart/get-cart.php', function(data) {
        if (data.success) {
            updateCartCountDisplay(data.item_count);
            sessionStorage.setItem('cart_count', data.item_count);
        }
    });
}

// ============================================================
// ADD / REMOVE
// ============================================================

function addToCart(productId, productName, productPrice) {
    $.ajax({
        url: baseUrl + 'php/endpoints/cart/add-to-cart.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ product_id: productId, product_name: productName, product_price: productPrice }),
        success: function(data) {
            if (data.success) {
                updateCartCountDisplay(data.cart_count || 0);
                showSuccessToast(data.message || t('item_added_to_cart'));
            } else {
                showErrorToast(data.message || t('error_adding_to_cart'));
            }
        },
        error: function() { showErrorToast(t('error_occurred')); }
    });
}

function removeFromCart(productId) {
    if (!confirm(t('remove_item_confirm'))) return;

    $.ajax({
        url: baseUrl + 'php/endpoints/cart/remove-from-cart.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ product_id: productId }),
        success: function(data) {
            if (data.success) {
                updateCartCountDisplay(data.cart_count || 0);
                if (window.location.pathname.includes('cart.php')) {
                    refreshCart();
                } else {
                    showSuccessToast(data.message || t('item_removed_from_cart'));
                }
            } else {
                showErrorToast(data.message || t('error_removing_from_cart'));
            }
        },
        error: function() { showErrorToast(t('error_occurred')); }
    });
}

// ============================================================
// BUY NOW
// ============================================================

function buyNow(productId, productName, productPrice) {
    // Add to cart
    $.ajax({
        url: baseUrl + 'php/endpoints/cart/add-to-cart.php',
        type: 'POST',
        contentType: 'application/json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        data: JSON.stringify({ product_id: productId, product_name: productName, product_price: productPrice }),
        success: function(cartResponse) {
            console.log('Cart response:', cartResponse);
            
            if (!cartResponse.success) {
                showErrorToast(cartResponse.message || t('could_not_add_to_cart'));
                return;
            }

            updateCartCountDisplay(cartResponse.cart_count || 0);
            showSuccessToast(t('item_added_checkout'));

            // Create checkout session
            $.ajax({
                url: baseUrl + 'php/endpoints/checkout/place-order.php',
                type: 'POST',
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function(checkoutResponse) {
                    console.log('Checkout response:', checkoutResponse);
                    
                    if (checkoutResponse.success) {
                        showSuccessToast(t('checkout_ready'));
                        setTimeout(function() {
                            window.location.href = baseUrl + 'checkout.php';
                        }, 1000);
                    } else {
                        showErrorToast(checkoutResponse.message || t('checkout_failed'));
                        console.error('Checkout failed:', checkoutResponse);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Checkout AJAX error:', status, error, xhr.responseText);
                    showErrorToast(t('network_error'));
                }
            });
        },
        error: function(xhr, status, error) {
            console.error('Cart AJAX error:', status, error, xhr.responseText);
            showErrorToast(t('network_error'));
        }
    });
}

// ============================================================
// CART PAGE
// ============================================================

function loadCartPage() {
    if (!window.location.pathname.includes('cart.php')) return;

    if (!isLoggedIn) {
        $('#cart-layout').hide();
        $('#empty-cart').show();
        $('#empty-cart h3').text(t('login_required'));
        $('#empty-cart p').text(t('please_login_to_view_cart'));
        return;
    }

    if (typeof initialCartData === 'undefined') {
        console.warn('initialCartData not defined');
        $('#cart-layout').hide();
        $('#empty-cart').show();
        return;
    }

    if (!initialCartData.items || initialCartData.items.length === 0) {
        $('#cart-layout').hide();
        $('#empty-cart').show();
        return;
    }

    displayCartItems(initialCartData);
    updateCartTotalsDisplay(initialCartData);

    var totalQty = 0;
    for (var i = 0; i < initialCartData.items.length; i++) {
        totalQty += initialCartData.items[i].quantity;
    }
    updateCartCountDisplay(totalQty);
}

function updateCartTotalsDisplay(cartData) {
    var subtotal = parseFloat(cartData.subtotal) || 0;
    var deliveryFee = parseFloat(cartData.delivery_fee) || 0;
    var total = parseFloat(cartData.total) || 0;

    $('.sub-total-val').text(t('currency_symbol') + ' ' + subtotal.toFixed(2));
    $('.deliv-fee-val').text(t('currency_symbol') + ' ' + deliveryFee.toFixed(2));
    $('.total-val').text(t('currency_symbol') + ' ' + total.toFixed(2));
}

function refreshCart() {
    if (!window.location.pathname.includes('cart.php')) return;

    $.ajax({
        url: baseUrl + 'php/endpoints/cart/get-cart.php',
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
                showErrorToast(t('failed_load_cart'));
            }
        },
        error: function() {
            showErrorToast(t('failed_refresh_cart'));
        }
    });
}

function displayCartItems(cartData) {
    var $desktopTableBody = $('#cart-table-body');
    var $mobileContainer = $('#mobile-cart-items');
    var $emptyCartDiv = $('#empty-cart');
    var $cartLayout = $('#cart-layout');
    var $cartItemCount = $('#cart-item-count');

    if (!cartData) {
        if ($emptyCartDiv.length) $emptyCartDiv.css('display', 'flex');
        if ($cartLayout.length) $cartLayout.css('display', 'none');
        return;
    }

    var items = cartData.items || cartData;

    if (!items || !Array.isArray(items)) {
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

    var totalQty = 0;
    for (var i = 0; i < items.length; i++) {
        totalQty += items[i].quantity;
    }
    if ($cartItemCount.length) $cartItemCount.text(totalQty);

    if ($desktopTableBody.length) $desktopTableBody.empty();
    if ($mobileContainer.length) $mobileContainer.empty();

    for (var i = 0; i < items.length; i++) {
        var item = items[i];

        if (!item || !item.product_id) continue;

        var price = parseFloat(item.price) || 0;
        var quantity = parseInt(item.quantity) || 1;
        var itemTotal = price * quantity;

        var verifiedBadge = item.is_verified ?
            '<div class="verified-badge-cart"><img src="' + baseUrl + 'images/icons/verified-svgrepo-com.svg" width="14" height="14"><span>' + t('verified_seller') + '</span></div>' :
            '<div class="unverified-badge-cart"><img src="' + baseUrl + 'images/icons/not-verified-svgrepo-com.svg" width="14" height="14"><span>' + t('unverified') + '</span></div>';

        var imagePath = fixImageUrl(item.image || item.image_url);
        var productName = item.product_name || item.title || 'Product';
        var sellerName = item.seller_name || t('unknown_seller');
        var cartId = item.cart_id;
        var productId = item.product_id;
        var stockQty = parseInt(item.stock_quantity) || 99;

        if ($desktopTableBody.length) {
            var row = $('<tr>').html(
                '<td class="product-cell" data-label="' + t('product') + '">' +
                    '<div class="cart-product-wrapper">' +
                        '<div class="cart-img-container"><img src="' + imagePath + '" alt="' + escapeHtml(productName) + '" onerror="this.src=\'' + baseUrl + 'images/default-product.png\'"></div>' +
                        '<div class="cart-prod-info"><p class="prod-name">' + escapeHtml(productName) + '</p></div>' +
                    '</div>' +
                '</td>' +
                '<td class="seller-cell" data-label="' + t('seller') + '">' +
                    '<div class="seller-cart-info">' +
                        '<p class="seller-name">' + escapeHtml(sellerName) + '</p>' +
                        '<div class="verification">' + verifiedBadge + '</div>' +
                    '</div>' +
                '</td>' +
                '<td class="price-cell" data-label="' + t('price') + '">' + t('currency_symbol') + ' ' + price.toFixed(2) + '</td>' +
                '<td class="quantity-cell" data-label="' + t('quantity') + '">' +
                    '<div class="quantity-controls">' +
                        '<button class="qty-decrease" data-cart-id="' + cartId + '">-</button>' +
                        '<input type="number" class="qty-input" value="' + quantity + '" min="1" max="' + Math.min(99, stockQty) + '" data-cart-id="' + cartId + '" style="width: 60px; text-align: center;">' +
                        '<button class="qty-increase" data-cart-id="' + cartId + '">+</button>' +
                    '</div>' +
                    (quantity >= stockQty && stockQty > 0 ? '<small class="stock-warning">' + t('max_available').replace('{stock}', stockQty) + '</small>' : '') +
                '</td>' +
                '<td class="actions-cell" data-label="' + t('action') + '">' +
                    '<button class="remove-btn" data-product-id="' + productId + '">' +
                        '<img src="' + baseUrl + 'images/icons/delete-svgrepo-com.svg" width="16" height="16" alt="' + t('remove') + '">' +
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
                    '<div>' +
                        '<div class="cart-card-price">' + t('currency_symbol') + ' ' + price.toFixed(2) + ' ' + t('each') + '</div>' +
                        '<div style="font-weight: bold; color: var(--primary-color);">' + t('total') + ': ' + t('currency_symbol') + ' ' + itemTotal.toFixed(2) + '</div>' +
                    '</div>' +
                    '<div class="quantity-controls">' +
                        '<button class="qty-decrease" data-cart-id="' + cartId + '">-</button>' +
                        '<input type="number" class="qty-input" value="' + quantity + '" min="1" max="' + Math.min(99, stockQty) + '" data-cart-id="' + cartId + '" style="width: 50px; text-align: center;">' +
                        '<button class="qty-increase" data-cart-id="' + cartId + '">+</button>' +
                    '</div>' +
                    '<button class="remove-btn" data-product-id="' + productId + '">' +
                        '<img src="' + baseUrl + 'images/icons/delete-svgrepo-com.svg" width="14" height="14" alt="' + t('remove') + '">' +
                    '</button>' +
                '</div>'
            );
            $mobileContainer.append(card);
        }
    }

    // Quantity buttons
    $(document).off('click', '.qty-increase').on('click', '.qty-increase', function() {
        var cartId = $(this).data('cart-id');
        var $input = $('.qty-input[data-cart-id="' + cartId + '"]');
        var currentVal = parseInt($input.val());
        var maxVal = parseInt($input.attr('max'));
        if (!isNaN(currentVal) && currentVal < maxVal) {
            $input.val(currentVal + 1);
            updateCartQuantity(cartId, currentVal + 1);
        }
    });

    $(document).off('click', '.qty-decrease').on('click', '.qty-decrease', function() {
        var cartId = $(this).data('cart-id');
        var $input = $('.qty-input[data-cart-id="' + cartId + '"]');
        var currentVal = parseInt($input.val());
        if (!isNaN(currentVal) && currentVal > 1) {
            $input.val(currentVal - 1);
            updateCartQuantity(cartId, currentVal - 1);
        }
    });

    $(document).off('change', '.qty-input').on('change', '.qty-input', function() {
        var $input = $(this);
        var cartId = $input.data('cart-id');
        var quantity = parseInt($input.val());
        var maxVal = parseInt($input.attr('max'));
        if (isNaN(quantity) || quantity < 1) quantity = 1;
        if (quantity > maxVal) {
            quantity = maxVal;
            alert(t('only_available_in_stock').replace('{max}', maxVal));
        }
        $input.val(quantity);
        updateCartQuantity(cartId, quantity);
    });

    $(document).off('click', '.remove-btn').on('click', '.remove-btn', function() {
        var productId = $(this).data('product-id');
        if (confirm(t('remove_item_confirm'))) {
            removeFromCart(productId);
        }
    });

    if (typeof updateCartTotalsDisplay === 'function' && cartData.subtotal !== undefined) {
        updateCartTotalsDisplay(cartData);
    }
}

function updateCartQuantity(cartId, quantity) {
    $.ajax({
        url: baseUrl + 'php/endpoints/cart/update-cart.php',
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
                    if (typeof initialCartData !== 'undefined') {
                        initialCartData = response.cart;
                    }
                    showSuccessToast(response.message || t('cart_updated'));
                } else {
                    refreshCart();
                }
            } else {
                showErrorToast(response.message || t('failed_update_cart'));
                setTimeout(function() { location.reload(); }, 1500);
            }
        },
        error: function() {
            showErrorToast(t('error_refresh_page'));
            setTimeout(function() { location.reload(); }, 1500);
        }
    });
}