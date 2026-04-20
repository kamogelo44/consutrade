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
        <link rel="stylesheet" href="css/footer.css">
        <title>Cart Page - ConsuTrade</title>
    </head>
    <body>
        <!--Header-->
        <?php include 'includes/header.php'; ?>

        <main>
            <div class="cart-container">
                <!-- Heading at the top -->
                <div class="heading-container">
                    <h1 class="cart-heading">My Cart (<span class="item-num" id="cart-item-count">0</span>) items</h1>
                </div>
                
                <!-- Empty cart state - shown when no items -->
                <div class="empty-cart" id="empty-cart" style="display: none;">
                    <img src="images/icons/shopping-cart-01-svgrepo-com.svg" width="64px" height="64px" alt="Empty cart" class="icon-white" style="filter: invert(60%);">
                    <h2>Your cart is empty</h2>
                    <p>Looks like you have not added anything yet</p>
                    <a href="product-listings.php">
                        <button class="browse-products-btn">Browse Products</button>
                    </a>
                </div>
                
                <!-- Two columns wrapper -->
                <div class="cart-layout" id="cart-layout">
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
                                <tbody id="cart-table-body">
                                    <!-- Cart items will be inserted here by JavaScript -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Card Layout (hidden on desktop, visible on mobile) -->
                        <div class="mobile-cart-items" id="mobile-cart-items">
                            <!-- Mobile cart cards will be inserted here by JavaScript -->
                        </div>
                    </div>
                    
                    <div class="right-column">
                        <div class="order-sum-container">
                            <h2>Order Summary</h2>
                            <div class="calculations">
                                <p class="sub-total">Subtotal: 
                                    R<span class="sub-total-val" id="subtotal">0.00</span>
                                </p>
                                <p class="deliv-fee">Delivery Fee: 
                                    R<span class="deliv-fee-val" id="delivery-fee">0.00</span>
                                </p>
                                <div class="result">
                                    <p class="total">Total: R<span class="total-val" id="total">0.00</span></p>
                                </div>
                                <button class="checkout-btn" id="checkout-btn">
                                    Proceed to Checkout
                                </button>
                                <button class="cont-shopp-btn" onclick="window.location.href='product-listings.php'">
                                    <img src="images/icons/continue-svgrepo-com.svg" width="24px" height="24px" alt="">
                                    Continue Shopping
                                </button>
                                <div class="security-badge">
                                    <a href="https://www.payfast.co.za" class="payfast-badge" target="_blank">
                                        Secured with 
                                        <img src="images/icons/Payfast logo.svg" alt="PayFast Logo">
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!--Footer-->
        <?php include 'includes/footer.php'; ?>
        
        <script src="js/main.js"></script>
        <script>
            // Add checkout button functionality
            document.getElementById('checkout-btn').addEventListener('click', function() {
                window.location.href = 'checkout.php';
            });
        </script>
    </body>
</html>