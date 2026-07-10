<?php
/*
 * ConsuTrade - Site Footer Component
 * Author: Kamogelo Phale
 * 
 * Includes global JavaScript variables and scripts
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
            <div class="contact-item">
                <img src="<?php echo $baseUrl; ?>images/icons/email-svgrepo-com.svg" alt="Email" width="16" height="16">
                <a href="mailto:info@consutrade.co.za" class="contact-link">info@consutrade.co.za</a>
            </div>
            <div class="contact-item">
                <img src="<?php echo $baseUrl; ?>images/icons/phone-call-svgrepo-com.svg" alt="Phone" width="16" height="16">
                <a href="tel:+27123456789" class="contact-link">+27 12 345 6789</a>
            </div>
            <div class="contact-item">
                <img src="<?php echo $baseUrl; ?>images/icons/pin-location-svgrepo-com.svg" alt="Location" width="16" height="16">
                <span>Limpopo, South Africa</span>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> ConsuTrade. All rights reserved.</p>
    </div>
</footer>

<!-- GLOBAL JAVASCRIPT VARIABLES -->
<script>
    var baseUrl = '<?php echo $baseUrl; ?>';
    var currentUserId = <?php echo $currentUser ? $currentUser->getUserId() : 0; ?>;
    var currentUserRole = '<?php echo $currentUserRole ?? ''; ?>';
    var isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
</script>

<!-- Library -->
<script src="<?php echo $baseUrl; ?>js/lib/jquery-3.7.1.min.js"></script>

<!-- Core (must load before modules) -->
<script src="<?php echo $baseUrl; ?>js/core/utils.js"></script>
<script src="<?php echo $baseUrl; ?>js/core/ui.js"></script>

<!-- Modules (loaded on all pages) -->
<script src="<?php echo $baseUrl; ?>js/modules/auth.js"></script>
<script src="<?php echo $baseUrl; ?>js/modules/cart.js"></script>
<script src="<?php echo $baseUrl; ?>js/modules/mobile.js"></script>

<!-- Verification module -->
<?php if (isset($load_verification_js) && $load_verification_js): ?>
    <script src="<?php echo $baseUrl; ?>js/modules/verification.js"></script>
<?php endif; ?>

<!-- Order module (only on order pages) -->
<?php if (isset($load_orders_js) && $load_orders_js): ?>
    <script src="<?php echo $baseUrl; ?>js/modules/orders.js"></script>
<?php endif; ?>

<!-- Products module (listings, details, search) -->
<?php if (isset($load_products_js) && $load_products_js): ?>
    <script src="<?php echo $baseUrl; ?>js/products.js"></script>
<?php endif; ?>

<!-- Dashboard module (admin/seller dashboards) -->
<?php if (isset($load_dashboard_js) && $load_dashboard_js): ?>
    <script src="<?php echo $baseUrl; ?>js/image-compressor.js"></script>
    <script src="<?php echo $baseUrl; ?>js/dashboard.js"></script>
<?php endif; ?>

<!-- Page-specific init scripts -->
<?php if (isset($page_js)): ?>
    <script src="<?php echo $baseUrl; ?>js/pages/<?php echo $page_js; ?>"></script>
<?php endif; ?>

<!-- Global init -->
<script>
    $(function() {
        initMobileMenu();
        initMobileSearch();
        initModalControls();
        initUserDropdown();
        initFlashMessages();
        setActiveLink();
        initErrorClearingOnInput();
        initAjaxLogin();
        initAjaxRegister();

        if (isLoggedIn) {
            if (window.location.pathname.includes('cart.php')) {
                loadCartPage();
            } else {
                updateCartCount();
            }
        } else {
            updateCartCountDisplay(0);
        }
    });
</script>