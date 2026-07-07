/**
 * ConsuTrade - Cart Page Init
 * Depends on: jQuery, modules/cart.js
 */

$(function() {
    var $cartLayout = $('#cart-layout');
    var $emptyCart = $('#empty-cart');
    var $cartItemCount = $('#cart-item-count');
    var $checkoutBtn = $('#checkoutBtn');

    // Load cart with initial data
    if (initialCartData.items && initialCartData.items.length > 0) {
        if (typeof displayCartItems === 'function') displayCartItems(initialCartData);
        if (typeof updateCartTotalsDisplay === 'function') updateCartTotalsDisplay(initialCartData);
        $cartLayout.css('display', 'flex');
        $emptyCart.css('display', 'none');
    } else {
        $cartLayout.css('display', 'none');
        $emptyCart.css('display', 'flex');
        $cartItemCount.text('0');
    }

    // Checkout
    if ($checkoutBtn.length) {
        $checkoutBtn.on('click', function() {
            $checkoutBtn.prop('disabled', true).text('Processing...');

            $.ajax({
                url: baseUrl + 'php/endpoints/checkout/place-order.php',
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        window.location.href = baseUrl + 'checkout.php';
                    } else {
                        showErrorToast(response.message || 'Unable to proceed to checkout');
                        $checkoutBtn.prop('disabled', false).text('Proceed to Checkout');
                    }
                },
                error: function() {
                    showErrorToast('An error occurred. Please try again.');
                    $checkoutBtn.prop('disabled', false).text('Proceed to Checkout');
                }
            });
        });
    }

    // Continue shopping / browse
    $('#continueBtn, #browseBtn').on('click', function() {
        window.location.href = baseUrl + 'product-listings.php';
    });
});