<?php
/*
 * ConsuTrade - Shopping Cart Page
 * Author: Kamogelo Phale
 * 
 * Displays user's cart items with quantity and stock awareness
 */

require_once __DIR__ . '/init.php';

$baseUrl = getBaseUrl();

// Load cart items using helper if logged in
$cart_items = [];
$cart_totals = ['subtotal' => 0, 'delivery_fee' => 0, 'total' => 0];
$total_quantity = 0;

if ($is_logged_in && $current_user_id) {
    $cart_items = getCartItems($conn, $current_user_id);
    $cart_totals = calculateCartTotals($cart_items);
    $total_quantity = array_sum(array_column($cart_items, 'quantity'));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart - ConsuTrade</title>
    <meta name="author" content="Kamogelo Phale">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/animations.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/cart.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/login-signup.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/header.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/footer.css">
</head>
<body>

<?php include 'includes/header.php'; ?>

<main>
    <div class="cart-container">
        <div class="heading-container">
            <h1 class="cart-heading">My Cart (<span class="item-num" id="cart-item-count"><?php echo $total_quantity; ?></span> items)</h1>
        </div>
        
        <?php if (empty($cart_items)): ?>
        <div class="empty-cart" id="empty-cart">
            <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" width="64" height="64" alt="Empty cart" class="icon-white" style="filter: invert(60%);">
            <h2>Your cart is empty</h2>
            <p>Looks like you have not added anything yet</p>
            <a href="product-listings.php">
                <button class="browse-products-btn">Browse Products</button>
            </a>
        </div>
        <?php else: ?>
        <div class="cart-layout" id="cart-layout">
            <div class="left-column">
                <div class="product-table-wrapper desktop-table">
                    <table class="product-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Seller</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="cart-table-body">
                            <?php foreach ($cart_items as $item): ?>
                            <tr data-cart-id="<?php echo $item['cart_id']; ?>" data-product-id="<?php echo $item['product_id']; ?>">
                                <td class="product-cell">
                                    <div class="cart-product-wrapper">
                                        <div class="cart-img-container">
                                            <img src="<?php echo getProductImageUrl($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" onerror="this.src='<?php echo $baseUrl; ?>images/default-product.png'">
                                        </div>
                                        <div class="cart-prod-info">
                                            <p class="prod-name"><?php echo htmlspecialchars($item['title']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="seller-cell"><?php echo htmlspecialchars($item['seller_name']); ?></td>
                                <td class="price-cell">R <?php echo number_format($item['price'], 2); ?></td>
                                <td class="quantity-cell">
                                    <div class="quantity-controls">
                                        <button class="qty-decrease" data-cart-id="<?php echo $item['cart_id']; ?>">-</button>
                                        <input type="number" class="qty-input" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo min(99, $item['stock_quantity']); ?>" data-cart-id="<?php echo $item['cart_id']; ?>">
                                        <button class="qty-increase" data-cart-id="<?php echo $item['cart_id']; ?>">+</button>
                                    </div>
                                    <?php if ($item['quantity'] >= $item['stock_quantity'] && $item['stock_quantity'] > 0): ?>
                                    <small class="stock-warning">Max <?php echo $item['stock_quantity']; ?> available</small>
                                    <?php endif; ?>
                                </td>
                                <td class="actions-cell">
                                    <button class="remove-btn" data-product-id="<?php echo $item['product_id']; ?>" onclick="removeFromCart(<?php echo $item['product_id']; ?>)">
                                        <img src="<?php echo $baseUrl; ?>images/icons/delete-svgrepo-com.svg" width="16" height="16" alt="Remove">
                                        Remove
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mobile-cart-items" id="mobile-cart-items">
                    <?php foreach ($cart_items as $item): ?>
                    <div class="cart-card" data-cart-id="<?php echo $item['cart_id']; ?>">
                        <div class="cart-card-header">
                            <img src="<?php echo getProductImageUrl($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="cart-card-img" onerror="this.src='<?php echo $baseUrl; ?>images/default-product.png'">
                            <div>
                                <h4><?php echo htmlspecialchars($item['title']); ?></h4>
                                <p class="seller-name"><?php echo htmlspecialchars($item['seller_name']); ?></p>
                            </div>
                        </div>
                        <div class="cart-card-body">
                            <div class="cart-card-price">R <?php echo number_format($item['price'], 2); ?></div>
                            <div class="quantity-controls">
                                <button class="qty-decrease" data-cart-id="<?php echo $item['cart_id']; ?>">-</button>
                                <input type="number" class="qty-input" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo min(99, $item['stock_quantity']); ?>" data-cart-id="<?php echo $item['cart_id']; ?>">
                                <button class="qty-increase" data-cart-id="<?php echo $item['cart_id']; ?>">+</button>
                            </div>
                            <button class="remove-btn" data-product-id="<?php echo $item['product_id']; ?>" onclick="removeFromCart(<?php echo $item['product_id']; ?>)">
                                <img src="<?php echo $baseUrl; ?>images/icons/delete-svgrepo-com.svg" width="14" height="14" alt="Remove">
                                Remove
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="right-column">
                <div class="order-sum-container">
                    <h2>Order Summary</h2>
                    <div class="calculations">
                        <p class="sub-total">Subtotal: R<span class="sub-total-val"><?php echo number_format($cart_totals['subtotal'], 2); ?></span></p>
                        <p class="deliv-fee">Delivery Fee: R<span class="deliv-fee-val"><?php echo number_format($cart_totals['delivery_fee'], 2); ?></span></p>
                        <div class="result">
                            <p class="total">Total: R<span class="total-val"><?php echo number_format($cart_totals['total'], 2); ?></span></p>
                        </div>
                        <button class="checkout-btn" id="checkout-btn">Proceed to Checkout</button>
                        <button class="cont-shopp-btn" onclick="window.location.href='product-listings.php'">
                            <img src="<?php echo $baseUrl; ?>images/icons/continue-svgrepo-com.svg" width="24" height="24" alt="">
                            Continue Shopping
                        </button>
                        <div class="security-badge">
                            <a href="https://www.payfast.co.za" class="payfast-badge" target="_blank">
                                Secured with <img src="<?php echo $baseUrl; ?>images/icons/Payfast logo.svg" alt="PayFast Logo">
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<script>
/*
 * ConsuTrade - Cart Functionality with Quantity
 * Author: Kamogelo Phale
 */
var baseUrl = '<?php echo $baseUrl; ?>';

$(document).ready(function() {
    $('.qty-increase').on('click', function() {
        var cartId = $(this).data('cart-id');
        var $input = $('.qty-input[data-cart-id="' + cartId + '"]');
        var currentVal = parseInt($input.val());
        var maxVal = parseInt($input.attr('max'));
        if (!isNaN(currentVal) && currentVal < maxVal) {
            $input.val(currentVal + 1);
            updateCartQuantity(cartId, currentVal + 1);
        }
    });
    
    $('.qty-decrease').on('click', function() {
        var cartId = $(this).data('cart-id');
        var $input = $('.qty-input[data-cart-id="' + cartId + '"]');
        var currentVal = parseInt($input.val());
        if (!isNaN(currentVal) && currentVal > 1) {
            $input.val(currentVal - 1);
            updateCartQuantity(cartId, currentVal - 1);
        }
    });
    
    $('.qty-input').on('change', function() {
        var cartId = $(this).data('cart-id');
        var quantity = parseInt($(this).val());
        var maxVal = parseInt($(this).attr('max'));
        if (isNaN(quantity) || quantity < 1) {
            quantity = 1;
            $(this).val(1);
        }
        if (quantity > maxVal) {
            quantity = maxVal;
            $(this).val(maxVal);
            alert('Only ' + maxVal + ' available in stock.');
        }
        updateCartQuantity(cartId, quantity);
    });
    
    $('#checkout-btn').on('click', function() {
        window.location.href = baseUrl + 'checkout.php';
    });
});

function updateCartQuantity(cartId, quantity) {
    $.ajax({
        url: baseUrl + 'php/update-cart.php',
        method: 'POST',
        data: { cart_id: cartId, quantity: quantity },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + response.message);
                location.reload();
            }
        },
        error: function() {
            alert('Something went wrong. Please try again.');
            location.reload();
        }
    });
}
</script>

</body>
</html>