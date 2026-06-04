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

<!-- GLOBAL JAVASCRIPT VARIABLES - MUST be defined before jQuery and main.js -->
<script>
    // Global variables accessible from any script
    var baseUrl = '<?php echo $baseUrl; ?>';
    var currentUserId = <?php echo isset($currentUser) ? $currentUser->getUserId() : 0; ?>;
    var currentUserRole = '<?php echo isset($currentUser) ? $currentUser->getRole() : ''; ?>';
    var isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
    var cartCountInitial = <?php echo ($isLoggedIn && isset($currentUser) && $currentUser instanceof Buyer) ? ($_SESSION['cart_count'] ?? 0) : 0; ?>;
</script>

<script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
<script src="<?php echo $baseUrl; ?>js/main.js"></script>

<script>
    // ========== CACHED DOM ELEMENTS ==========
    var $cartCountElements = null;
    var $mobileCartCount = null;

    // ========== CACHE FUNCTION ==========
    function cacheFooterElements() {
        $cartCountElements = $('.cart-badge, .cart-count');
        $mobileCartCount = $('.mobile-cart-count');
    }

    // ========== UPDATE CART COUNT DISPLAY ==========
    function updateCartCountDisplay() {
        cacheFooterElements();

        $.ajax({
            url: baseUrl + 'php/endpoints/get-cart.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var count = parseInt(response.item_count) || 0;
                    if ($cartCountElements.length) {
                        $cartCountElements.text(count);
                    }
                    if ($mobileCartCount.length) {
                        $mobileCartCount.text(count);
                    }
                    if (window.sessionStorage) {
                        sessionStorage.setItem('cart_count', count);
                    }
                }
            },
            error: function() {
                if ($cartCountElements.length) {
                    $cartCountElements.text('0');
                }
                if ($mobileCartCount.length) {
                    $mobileCartCount.text('0');
                }
            }
        });
    }

    // ========== LOAD CACHED CART COUNT ==========
    function loadCachedCartCount() {
        cacheFooterElements();

        if (window.sessionStorage) {
            var cachedCount = parseInt(sessionStorage.getItem('cart_count'));
            if (!isNaN(cachedCount)) {
                if ($cartCountElements.length) {
                    $cartCountElements.text(cachedCount);
                }
                if ($mobileCartCount.length) {
                    $mobileCartCount.text(cachedCount);
                }
            }
        }
    }

    // ========== INITIALIZE ==========
    $(function() {
        cacheFooterElements();

        // Use the global variables
        if (typeof cartCountInitial !== 'undefined' && cartCountInitial > 0) {
            if ($cartCountElements.length) {
                $cartCountElements.text(cartCountInitial);
            }
            if ($mobileCartCount.length) {
                $mobileCartCount.text(cartCountInitial);
            }
        }

        if (isLoggedIn && currentUserRole === 'buyer') {
            updateCartCountDisplay();
            loadCachedCartCount();
        } else {
            if ($cartCountElements.length) {
                $cartCountElements.text('0');
            }
            if ($mobileCartCount.length) {
                $mobileCartCount.text('0');
            }
        }
    });
</script>

<?php if (isset($load_products_js) && $load_products_js): ?>
    <script src="<?php echo $baseUrl; ?>js/products.js"></script>
<?php endif; ?>