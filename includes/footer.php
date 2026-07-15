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
            <p><?php t('trusted_marketplace'); ?></p>
            <div class="social-links">
                <a href="#" aria-label="Facebook"><img src="<?php echo $baseUrl; ?>images/icons/facebook-svgrepo-com.svg" alt="Facebook" width="18" height="18" loading="lazy"></a>
                <a href="#" aria-label="Twitter"><img src="<?php echo $baseUrl; ?>images/icons/twitter-svgrepo-com.svg" alt="Twitter" width="18" height="18" loading="lazy"></a>
                <a href="#" aria-label="Instagram"><img src="<?php echo $baseUrl; ?>images/icons/instagram-svgrepo-com.svg" alt="Instagram" width="18" height="18" loading="lazy"></a>
                <a href="#" aria-label="LinkedIn"><img src="<?php echo $baseUrl; ?>images/icons/linkedin-svgrepo-com.svg" alt="LinkedIn" width="18" height="18" loading="lazy"></a>
            </div>
        </div>
        <div class="footer-section">
            <h4><?php t('quick_links'); ?></h4>
            <ul>
                <li><a href="<?php echo $baseUrl; ?>about.php"><?php t('about'); ?></a></li>
                <li><a href="<?php echo $baseUrl; ?>product-listings.php"><?php t('shop'); ?></a></li>
                <li><a href="<?php echo $baseUrl; ?>sell.php"><?php t('sell_with_us'); ?></a></li>
            </ul>
        </div>
        <div class="footer-section">
            <h4><?php t('support'); ?></h4>
            <ul>
                <li><a href="<?php echo $baseUrl; ?>faq.php"><?php t('faq'); ?></a></li>
                <li><a href="<?php echo $baseUrl; ?>privacy.php"><?php t('privacy_policy'); ?></a></li>
                <li><a href="<?php echo $baseUrl; ?>terms.php"><?php t('terms_conditions'); ?></a></li>
            </ul>
        </div>
        <div class="footer-section">
            <h4><?php t('contact_info'); ?></h4>
            <div class="contact-item">
                <img src="<?php echo $baseUrl; ?>images/icons/email-svgrepo-com.svg" alt="Email" width="16" height="16" loading="lazy">
                <a href="mailto:info@consutrade.co.za" class="contact-link">info@consutrade.co.za</a>
            </div>
            <div class="contact-item">
                <img src="<?php echo $baseUrl; ?>images/icons/phone-call-svgrepo-com.svg" alt="Phone" width="16" height="16" loading="lazy">
                <a href="tel:+27123456789" class="contact-link">+27 12 345 6789</a>
            </div>
            <div class="contact-item">
                <img src="<?php echo $baseUrl; ?>images/icons/pin-location-svgrepo-com.svg" alt="Location" width="16" height="16" loading="lazy">
                <span><?php t('location_sa'); ?></span>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> ConsuTrade. <?php t('all_rights_reserved'); ?></p>
    </div>
</footer>

<!-- GLOBAL JAVASCRIPT VARIABLES ARE IN HEADER.PHP - DO NOT DUPLICATE HERE -->

<!-- ============================================================
     JAVASCRIPT LOADING ORDER
     ============================================================ -->

<!-- Library - jQuery MUST load FIRST before any other JS -->
<script src="<?php echo $baseUrl; ?>js/lib/jquery-3.7.1.min.js"></script>

<!-- ============================================================
     CORE (must load before modules)
     ============================================================ -->
<script src="<?php echo $baseUrl; ?>js/core/utils.js"></script>
<script src="<?php echo $baseUrl; ?>js/core/ui.js"></script>

<!-- ============================================================
     MODULES (loaded on all pages)
     ============================================================ -->
<script src="<?php echo $baseUrl; ?>js/modules/auth.js"></script>
<script src="<?php echo $baseUrl; ?>js/modules/cart.js"></script>
<script src="<?php echo $baseUrl; ?>js/modules/mobile.js"></script>
<script src="<?php echo $baseUrl; ?>js/modules/modals.js"></script>

<!-- ============================================================
     HEADER BEHAVIOR (always loaded, after modules)
     ============================================================ -->
<script src="<?php echo $baseUrl; ?>js/pages/header.js"></script>

<!-- ============================================================
     CONDITIONAL MODULES
     ============================================================ -->

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

<!-- ============================================================
     PAGE-SPECIFIC SCRIPTS
     ============================================================ -->
<?php if (isset($page_js)): ?>
    <script src="<?php echo $baseUrl; ?>js/pages/<?php echo $page_js; ?>"></script>
<?php endif; ?>

<!-- ============================================================
     GLOBAL INIT
     ============================================================ -->
<script>
    $(function() {
        // Mobile UI
        initMobileMenu();
        initMobileSearch();
        initModalControls();
        initUserDropdown();
        initFlashMessages();
        setActiveLink();

        // Forms
        initErrorClearingOnInput();

        // Auth
        initAjaxLogin();
        initAjaxRegister();

        // ============================================================
        // MOBILE LANGUAGE TOGGLE
        // ============================================================
        initMobileLanguageToggle();

        // ============================================================
        // CART
        // ============================================================
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