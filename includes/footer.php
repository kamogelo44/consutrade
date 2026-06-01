<?php
/*
 * ConsuTrade - Site Footer Component
 * Author: Kamogelo Phale
 */
?>
<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-section">
            <h3>Consu<span>Trade</span></h3>
            <p>Your trusted marketplace in South Africa</p>
            <div class="social-links">
                <a href="#"><img src="<?php echo $baseUrl; ?>images/icons/facebook-svgrepo-com.svg" alt="Facebook" width="18" height="18"></a>
                <a href="#"><img src="<?php echo $baseUrl; ?>images/icons/twitter-svgrepo-com.svg" alt="Twitter" width="18" height="18"></a>
                <a href="#"><img src="<?php echo $baseUrl; ?>images/icons/instagram-svgrepo-com.svg" alt="Instagram" width="18" height="18"></a>
                <a href="#"><img src="<?php echo $baseUrl; ?>images/icons/linkedin-svgrepo-com.svg" alt="LinkedIn" width="18" height="18"></a>
            </div>
        </div>
        <div class="footer-section">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="<?php echo $baseUrl; ?>about.php">About Us</a></li>
                <li><a href="<?php echo $baseUrl; ?>product-listings.php">Shop</a></li>
                <li><a href="<?php echo $baseUrl; ?>sell.php">Sell with Us</a></li>
            </ul>
        </div>
        <div class="footer-section">
            <h4>Support</h4>
            <ul>
                <li><a href="<?php echo $baseUrl; ?>faq.php">FAQ</a></li>
                <li><a href="<?php echo $baseUrl; ?>privacy.php">Privacy Policy</a></li>
                <li><a href="<?php echo $baseUrl; ?>terms.php">Terms & Conditions</a></li>
            </ul>
        </div>
        <div class="footer-section">
            <h4>Contact Info</h4>
            <p><a href="mailto:info@consutrade.co.za">info@consutrade.co.za</a></p>
            <p><a href="tel:+27123456789">+27 123 456 789</a></p>
            <p>Johannesburg, South Africa</p>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> ConsuTrade. All rights reserved.</p>
    </div>
</footer>

<!-- Scripts -->
<script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
<script src="<?php echo $baseUrl; ?>js/main.js"></script>

<script>
    // Pass PHP variables to JavaScript
    var isLoggedIn = <?php echo json_encode($isLoggedIn); ?>;
    var currentUserRole = <?php echo isset($currentUser) ? json_encode($currentUser->getRole()) : 'null'; ?>;
    var baseUrl = <?php echo json_encode($baseUrl); ?>;
    var currentUserId = <?php echo isset($currentUser) ? json_encode($currentUser->getUserId()) : '0'; ?>;
    var cartCount = <?php echo $_SESSION['cart_count'] ?? 0; ?>;
</script>

<script>
    $(function() {
        function updateCartCountDisplay() {
            $.ajax({
                url: baseUrl + 'php/endpoints/get-cart.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        var count = parseInt(response.item_count) || 0;
                        $('.cart-count').text(count);
                        if (window.sessionStorage) {
                            sessionStorage.setItem('cart_count', count);
                        }
                    }
                }
            });
        }

        if (isLoggedIn && currentUserRole === 'buyer') {
            updateCartCountDisplay();
        }

        // Set initial cart count from session storage or PHP
        if (window.sessionStorage && sessionStorage.getItem('cart_count')) {
            var cachedCount = parseInt(sessionStorage.getItem('cart_count'));
            if (!isNaN(cachedCount)) {
                $('.cart-count').text(cachedCount);
            }
        } else if (cartCount > 0) {
            $('.cart-count').text(cartCount);
        }
    });
</script>

<?php if (isset($load_products_js) && $load_products_js): ?>
    <script src="<?php echo $baseUrl; ?>js/products.js"></script>
<?php endif; ?>