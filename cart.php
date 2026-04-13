<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="css/style.css">
        <link rel="stylesheet" href="css/cart.css">
        <link rel="stylesheet" href="css/login-signup.css">
        <link rel="stylesheet" href="css/header.css">
        <title>Cart Page</title>
    </head>
    <body>
        <!--Header-->
        <?php include 'header.php'?>

        <main>
            <div class="cart-container">
                <!-- Heading at the top -->
                <div class="heading-container">
                    <h1 class="cart-heading">My Cart (<span class="item-num">0</span>) items</h1>
                </div>
                <!-- Empty cart state - shown when no items -->
                <div class="empty-cart" id="empty-cart" style="display:none;">
                    <img src="images/icons/shopping-cart-01-svgrepo-com.svg" width="64px" height="64px" alt="Empty cart" class="icon-white" style="filter: invert(60%);">
                    <h2>Your cart is empty</h2>
                    <p>Looks like you have not added anything yet</p>
                    <a href="product-listings.php">
                        <button class="browse-products-btn">Browse Products</button>
                    </a>
                </div>
                <!-- Two columns wrapper -->
                <div class="cart-layout">
                    <div class="left-column">
                        <!-- Desktop Table (visible on desktop, hidden on mobile) -->
                        <div class="product-table-wrapper desktop-table">
                            <table class="product-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Seller</th>
                                        <th>Price</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="product-cell" data-label="Product">
                                            <div class="cart-product-wrapper">
                                                <div class="cart-img-container">
                                                    <img src="" alt="Product Image">
                                                </div>
                                                <div class="cart-prod-info">
                                                    <p class="prod-name">Purse</p>
                                                    <p class="prod-info">Color: blue</p>
                                                    <p><span class="num-avail">0</span> Available</p>
                                                </div>
                                            </div>
                                        </td>
                                        
                                        <td class="seller-cell" data-label="Seller">
                                            <div class="seller-cart-info">
                                                <p class="seller-name">Seller name</p>
                                                <div class="verification">
                                                    <div class="verified-badge-cart">
                                                        <img src="images/icons/verified-svgrepo-com.svg" width="20px" height="20px" alt="verification">
                                                        <p>Verified Seller</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        
                                        <td class="price-cell" data-label="Price">R 0.00</td>
                                        
                                        <td class="actions-cell" data-label="Actions">
                                            <button class="remove-btn">Remove</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Card Layout (hidden on desktop, visible on mobile) -->
                        <div class="mobile-cart-items">
                            <div class="mobile-cart-card">
                                <div class="mobile-cart-img">
                                    <img src="" alt="Product Image">
                                </div>
                                <div class="mobile-cart-details">
                                    <h3 class="mobile-prod-name">Purse</h3>
                                    <p class="mobile-prod-info">Color: blue</p>
                                    <p class="mobile-availability"><span class="num-avail">0</span> Available</p>
                                </div>
                                <div class="mobile-cart-seller">
                                    <p class="seller-name">Seller name</p>
                                    <div class="verified-badge-cart">
                                        <img src="images/icons/verified-svgrepo-com.svg" width="16px" height="16px" alt="verified">
                                        <p>Verified Seller</p>
                                    </div>
                                </div>
                                <div class="mobile-cart-price">
                                    <p class="price">R 0.00</p>
                                </div>
                                <div class="mobile-cart-actions">
                                    <button class="remove-btn">Remove</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="right-column">
                        <div class="order-sum-container">
                            <h2>Order Summary</h2>
                            <div class="calculations">
                                <p class="sub-total">Subtotal: 
                                    R<span class="sub-total-val">0</span>
                                </p>
                                <p class="deliv-fee">Delivery Fee: 
                                    R<span class="deliv-fee-val">0</span>
                                </p>
                                <div class="result">
                                    <p class="total">Total: R<span class="total-val">0</span></p>
                                </div>
                                <button class="checkout-btn">
                                    Proceed to Checkout
                                </button>
                                <button class="cont-shopp-btn" onclick="window.location.href='product-listings.php'">
                                    <img src="images/icons/continue-svgrepo-com.svg" width="24px" height="24px" alt="">
                                    Continue Shopping
                                </button>
                                <div class="security-badge">
                                    Secured with <a href="">PayFast</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!--Footer-->
        <?php include 'footer.php'?>
        
        <script src="js/main.js"></script>
    </body>
</html>