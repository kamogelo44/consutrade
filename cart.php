<?php
/*
 * ConsuTrade - Shopping Cart Page
 * Author: Kamogelo Phale
 * 
 * Displays user's cart items with quantity and stock awareness
 */

require_once __DIR__ . '/init.php';

// Read register errors
$registerErrors = $_SESSION['register_errors'] ?? [];
$registerFormData = $_SESSION['register_form_data'] ?? [];
unset($_SESSION['register_errors'], $_SESSION['register_form_data']);

// Read login errors
$loginErrors = $_SESSION['login_errors'] ?? [];
$loginEmail = $_SESSION['login_email'] ?? '';
unset($_SESSION['login_errors'], $_SESSION['login_email']);

$breadcrumbItems = [
    ['label' => 'Shopping Cart']
];

// Initialize cart variables
$cart_items = [];
$cart_totals = ['subtotal' => 0, 'delivery_fee' => 0, 'total' => 0];
$total_quantity = 0;

// If user is logged in, load cart using CartRepository
if ($is_logged_in && $current_user_id) {
    $cart_items = $cartRepo->getCartItems($current_user_id);
    $cart_totals = $cartRepo->calculateCartTotals($cart_items);
    $total_quantity = $cartRepo->getCartCount($current_user_id);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <style>
        #empty-cart img { filter: brightness(0.5) invert(1); }
        #empty-cart button { background-color: var(--primary-color); color: var(--white); border: none; padding: 12px 32px; border-radius: var(--radius-md); font-weight: var(--font-bold); cursor: pointer; margin-top: var(--spacing-sm); }
        #empty-cart button:hover { background-color: var(--primary-dark); }
        
        .browse-products-btn { background-color: var(--primary-color); color: var(--white); border: none; padding: 12px 32px; border-radius: var(--radius-md); font-weight: var(--font-bold); cursor: pointer; margin-top: var(--spacing-sm); }
        .cart-container { margin: 0 0 30px 0; padding: 0 var(--spacing-xl); }
        .heading-container { margin-top: 30px; text-align: center; margin-bottom: 30px; width: 100%; }
        .cart-heading { font-size: var(--font-3xl); font-weight: var(--font-bold); color: var(--dark-bg); margin: 0; padding-bottom: var(--spacing-sm); border-bottom: 2px solid var(--primary-color); display: inline-block; }
        .item-num { color: var(--primary-color); }
        .cart-layout { display: flex; gap: 30px; align-items: flex-start; }
        .left-column { flex: 2; min-width: 0; }
        .right-column { flex: 1; min-width: 0; display: flex; flex-direction: column; }
        .desktop-table { display: block; }
        .mobile-cart-items { display: none; }
        .product-table-wrapper { width: 100%; overflow-x: auto; border-radius: var(--radius-lg); }
        .product-table { width: 100%; min-width: 700px; border-collapse: collapse; background-color: var(--white); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-sm); }
        .product-table th, .product-table td { padding: var(--spacing-md); border-bottom: 1px solid var(--border-light); text-align: center; vertical-align: middle; }
        .product-table th { background-color: var(--gray-bg); font-weight: var(--font-bold); font-size: var(--font-base); color: var(--gray-dark); }
        .product-cell { min-width: 280px; }
        .cart-product-wrapper { display: flex; align-items: center; gap: var(--spacing-md); justify-content: center; }
        .cart-img-container { width: 80px; height: 80px; flex-shrink: 0; background-color: var(--gray-bg); border-radius: var(--radius-md); overflow: hidden; }
        .cart-img-container img { width: 100%; height: 100%; object-fit: cover; }
        .cart-prod-info { flex: 1; text-align: left; }
        .cart-prod-info .prod-name { font-size: var(--font-base); font-weight: var(--font-bold); color: var(--dark-bg); }
        .seller-cell { min-width: 160px; }
        .price-cell { font-size: var(--font-lg); font-weight: var(--font-bold); color: var(--primary-color); white-space: nowrap; min-width: 90px; }
        .quantity-cell { min-width: 140px; }
        .quantity-controls { display: flex; align-items: center; justify-content: center; gap: 8px; }
        .qty-input { width: 55px; height: 32px; text-align: center; border: 1px solid var(--border-light); border-radius: var(--radius-sm); font-size: var(--font-md); }
        .stock-warning { display: block; font-size: var(--font-xs); color: var(--warning); margin-top: 4px; }
        .actions-cell { white-space: nowrap; min-width: 100px; }
        .remove-btn { background: transparent; border: 1px solid var(--error); color: var(--error); padding: 6px 14px; border-radius: var(--radius-sm); cursor: pointer; font-size: var(--font-sm); font-weight: var(--font-medium); transition: all var(--transition-fast); display: inline-flex; align-items: center; gap: 6px; }
        .remove-btn:hover { background-color: var(--error); color: var(--white); }
        .remove-btn:hover img { filter: brightness(0) invert(1); }
        .verified-badge-cart, .unverified-badge-cart { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: var(--radius-round); font-size: var(--font-xs); }
        .verified-badge-cart { background-color: var(--success-light); border: 1px solid var(--success); color: var(--success); }
        .unverified-badge-cart { background-color: var(--warning-light); border: 1px solid var(--warning); color: var(--warning); }
        .order-sum-container { background-color: var(--gray-bg); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: var(--spacing-lg); position: sticky; top: var(--spacing-xl); box-shadow: var(--shadow-sm); }
        .order-sum-container h2 { font-size: var(--font-xl); font-weight: var(--font-bold); margin-bottom: var(--spacing-xl); padding-bottom: var(--spacing-sm); border-bottom: 1px solid var(--border-light); text-align: center; color: var(--dark-bg); }
        .calculations { display: flex; flex-direction: column; gap: var(--spacing-md); }
        .sub-total, .deliv-fee { display: flex; justify-content: space-between; font-size: var(--font-base); color: var(--gray-medium); padding: 0 var(--spacing-sm); }
        .result { border-top: 1px solid var(--border-light); padding-top: var(--spacing-md); margin-top: var(--spacing-sm); }
        .total { display: flex; justify-content: space-between; font-size: var(--font-xl); font-weight: var(--font-bold); color: var(--primary-color); padding: 0 var(--spacing-sm); }
        .checkout-btn, .cont-shopp-btn { width: 100%; padding: 12px; border-radius: var(--radius-md); font-weight: var(--font-bold); cursor: pointer; margin-top: var(--spacing-md); }
        .checkout-btn { background-color: var(--primary-color); color: var(--white); border: none; }
        .checkout-btn:hover { background-color: var(--primary-dark); transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .cont-shopp-btn { background-color: transparent; color: var(--primary-color); border: 2px solid var(--primary-color); display: flex; align-items: center; justify-content: center; gap: var(--spacing-sm); }
        .cont-shopp-btn:hover { background-color: var(--primary-color); color: var(--white); }
        .cont-shopp-btn img { width: 18px; height: 18px; filter: brightness(0) saturate(100%) invert(48%) sepia(96%) saturate(1577%) hue-rotate(350deg); transition: filter var(--transition-fast); }
        .cont-shopp-btn:hover img { filter: brightness(0) invert(1); }
        .security-badge .payfast-badge { display: flex; align-items: center; justify-content: center; gap: var(--spacing-sm); margin-top: var(--spacing-md); padding-top: var(--spacing-md); border-top: 1px solid var(--border-light); font-size: var(--font-sm); color: var(--gray-medium); text-decoration: none; }
        .security-badge .payfast-badge img { height: 20px; width: auto; }
        .cart-card { background-color: var(--white); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: var(--spacing-lg); margin-bottom: var(--spacing-lg); box-shadow: var(--shadow-sm); }
        @media (max-width: 992px) { .cart-layout { flex-direction: column; } .right-column { order: -1; } .order-sum-container { max-width: 500px; margin: 0 auto; position: static; } }
        @media (max-width: 768px) { .cart-container { padding: var(--spacing-md); } .cart-heading { font-size: var(--font-2xl); } .desktop-table { display: none; } .mobile-cart-items { display: block; } }
        @media (max-width: 480px) { .cart-heading { font-size: var(--font-lg); } .order-sum-container { padding: var(--spacing-md); } .cart-card { padding: var(--spacing-md); } }
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<main>
    <?php include 'includes/breadcrumb.php'; ?>
    
    <div class="cart-container">
        <div class="heading-container">
            <h1 class="cart-heading">My Cart (<span class="item-num" id="cart-item-count"><?php echo $total_quantity; ?></span> items)</h1>
        </div>
        
        <?php if (empty($cart_items)): ?>
        <div class="empty-state" id="empty-cart">
            <img src="<?php echo $baseUrl; ?>images/icons/shopping-cart-01-svgrepo-com.svg" width="64" height="64" alt="Empty cart">
            <h2>Your cart is empty</h2>
            <p>Looks like you have not added anything yet</p>
            <a href="product-listings.php"><button class="browse-products-btn">Browse Products</button></a>
        </div>
        <?php else: ?>
        <div class="cart-layout">
            <div class="left-column">
                <div class="product-table-wrapper desktop-table">
                    <table class="product-table">
                        <thead>
                            <tr><th>Product</th><th>Seller</th><th>Price</th><th>Quantity</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $item): ?>
                            <tr>
                                <td class="product-cell">
                                    <div class="cart-product-wrapper">
                                        <div class="cart-img-container">
                                            <img src="<?php echo $item['image_url']; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" onerror="this.src='<?php echo $baseUrl; ?>images/default-product.png'">
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
                                    <button class="remove-btn" data-product-id="<?php echo $item['product_id']; ?>">
                                        <img src="<?php echo $baseUrl; ?>images/icons/delete-svgrepo-com.svg" width="16" height="16" alt="Remove"> Remove
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mobile-cart-items">
                    <?php foreach ($cart_items as $item): ?>
                    <div class="cart-card">
                        <div class="cart-card-header">
                            <img src="<?php echo $item['image_url']; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="cart-card-img" onerror="this.src='<?php echo $baseUrl; ?>images/default-product.png'">
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
                            <button class="remove-btn" data-product-id="<?php echo $item['product_id']; ?>">
                                <img src="<?php echo $baseUrl; ?>images/icons/delete-svgrepo-com.svg" width="14" height="14" alt="Remove"> Remove
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
                            <img src="<?php echo $baseUrl; ?>images/icons/continue-svgrepo-com.svg" width="24" height="24" alt=""> Continue Shopping
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
        if (isNaN(quantity) || quantity < 1) quantity = 1;
        if (quantity > maxVal) {
            quantity = maxVal;
            $(this).val(maxVal);
            alert('Only ' + maxVal + ' available in stock.');
        }
        updateCartQuantity(cartId, quantity);
    });
    
    $('.remove-btn').on('click', function() {
        var productId = $(this).data('product-id');
        if (confirm('Remove this item from your cart?')) {
            removeFromCart(productId);
        }
    });
    
    $('#checkout-btn').on('click', function() {
        window.location.href = baseUrl + 'checkout.php';
    });
});

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
</script>

</body>
</html>