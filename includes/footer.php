<?php
/*
 * ConsuTrade - Site Footer Component
 * Author: Kamogelo Phale
 * 
 * Reusable footer for main website pages
 */

// Base URL for the site (if not already defined)
if (!isset($baseUrl)) {
    $baseUrl = "/www/consutrade/";
}

// Get current year for copyright
$currentYear = date('Y');
?>
    
    <footer class="site-footer">
        <div class="footer-container">
            <!-- Brand Section -->
            <div class="footer-section">
                <h3>Consu<span>Trade</span></h3>
                <p>Your trusted marketplace in South Africa</p>
                <div class="social-links">
                    <a href="#" aria-label="Facebook">
                        <img src="<?php echo $baseUrl; ?>images/icons/facebook-svgrepo-com.svg" alt="Facebook" width="18" height="18">
                    </a>
                    <a href="#" aria-label="Twitter">
                        <img src="<?php echo $baseUrl; ?>images/icons/twitter-svgrepo-com.svg" alt="Twitter" width="18" height="18">
                    </a>
                    <a href="#" aria-label="Instagram">
                        <img src="<?php echo $baseUrl; ?>images/icons/instagram-svgrepo-com.svg" alt="Instagram" width="18" height="18">
                    </a>
                    <a href="#" aria-label="LinkedIn">
                        <img src="<?php echo $baseUrl; ?>images/icons/linkedin-svgrepo-com.svg" alt="LinkedIn" width="18" height="18">
                    </a>
                </div>
            </div>
            
            <!-- Quick Links Section -->
            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="<?php echo $baseUrl; ?>about.php">About Us</a></li>
                    <li><a href="<?php echo $baseUrl; ?>contact.php">Contact</a></li>
                    <li><a href="<?php echo $baseUrl; ?>product-listings.php">Shop</a></li>
                    <li><a href="<?php echo $baseUrl; ?>sell.php">Sell with Us</a></li>
                </ul>
            </div>
            
            <!-- Support Section -->
            <div class="footer-section">
                <h4>Support</h4>
                <ul>
                    <li><a href="<?php echo $baseUrl; ?>faq.php">FAQ</a></li>
                    <li><a href="<?php echo $baseUrl; ?>privacy.php">Privacy Policy</a></li>
                    <li><a href="<?php echo $baseUrl; ?>terms.php">Terms & Conditions</a></li>
                    <li><a href="<?php echo $baseUrl; ?>returns.php">Returns Policy</a></li>
                </ul>
            </div>
            
            <!-- Contact Section -->
            <div class="footer-section">
                <h4>Contact Info</h4>
                <p class="contact-item">
                    <img src="<?php echo $baseUrl; ?>images/icons/email-svgrepo-com.svg" alt="Email" width="18" height="18">
                    <a href="mailto:info@consutrade.co.za">info@consutrade.co.za</a>
                </p>
                <p class="contact-item">
                    <img src="<?php echo $baseUrl; ?>images/icons/phone-call-svgrepo-com.svg" alt="Phone" width="18" height="18">
                    <a href="tel:+27123456789">+27 123 456 789</a>
                </p>
                <p class="contact-item">
                    <img src="<?php echo $baseUrl; ?>images/icons/location-svgrepo-com.svg" alt="Location" width="18" height="18">
                    <span>Johannesburg, South Africa</span>
                </p>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p class="copyright">&copy; <?php echo $currentYear; ?> ConsuTrade. All rights reserved.</p>
        </div>
    </footer>
    
    <!-- jQuery (local version) - Must load before main.js -->
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    
    <!-- Main JavaScript (jQuery version) -->
    <script src="<?php echo $baseUrl; ?>js/main.js"></script>
    
    <!-- Pass PHP variables to JavaScript -->
    <script>
    // Global variables for JavaScript
    var isLoggedIn = <?php echo isset($is_logged_in) ? json_encode($is_logged_in) : 'false'; ?>;
    var currentUserRole = <?php echo isset($user_role) ? json_encode($user_role) : 'null'; ?>;
    var baseUrl = <?php echo json_encode($baseUrl); ?>;
    </script>
    
    <!-- Additional inline scripts (for functionality that's not yet in main.js) -->
    <script>
    // This ensures jQuery is loaded before running any jQuery-dependent code
    $(document).ready(function() {
        // ========== MOBILE MENU TOGGLE ==========
        var $mainToggle = $('#mobileMenuToggle');
        var $sideClose = $('#sideMenuClose');
        var $mobileNav = $('#mobileNav');
        var $overlay = $('#mobileMenuOverlay');
        
        function openMenu() {
            $mainToggle.addClass('active');
            $mobileNav.addClass('active');
            $overlay.addClass('active');
            $('body').addClass('menu-open').css('overflow', 'hidden');
        }
        
        function closeMenu() {
            $mainToggle.removeClass('active');
            $mobileNav.removeClass('active');
            $overlay.removeClass('active');
            $('body').removeClass('menu-open').css('overflow', '');
        }
        
        if ($mainToggle.length) {
            $mainToggle.on('click', function() {
                if ($mobileNav.hasClass('active')) {
                    closeMenu();
                } else {
                    openMenu();
                }
            });
        }
        
        if ($sideClose.length) {
            $sideClose.on('click', closeMenu);
        }
        
        if ($overlay.length) {
            $overlay.on('click', closeMenu);
        }
        
        // Close menu when clicking on mobile nav links
        $('.mobile-nav-links a, .mobile-nav-btn').on('click', function() {
            if ($(window).width() <= 768) {
                closeMenu();
            }
        });
        
        // Close menu on window resize if screen becomes desktop
        $(window).on('resize', function() {
            if ($(window).width() > 768 && $mobileNav.hasClass('active')) {
                closeMenu();
            }
        });
        
        // ========== MOBILE SEARCH ==========
        var $mobileSearchIcon = $('#mobileSearchIcon');
        var $mobileSearchContainer = $('#mobileSearchContainer');
        
        if ($mobileSearchIcon.length && $mobileSearchContainer.length) {
            $mobileSearchIcon.on('click', function(e) {
                e.stopPropagation();
                $mobileSearchContainer.toggleClass('active');
                
                if ($mobileSearchContainer.hasClass('active')) {
                    $('#mobile-search').focus();
                }
            });
        }
        
        $(document).on('click', function(event) {
            if ($mobileSearchContainer.length && $mobileSearchIcon.length) {
                if (!$mobileSearchContainer.is(event.target) && 
                    !$mobileSearchIcon.is(event.target) && 
                    !$mobileSearchContainer.has(event.target).length &&
                    $mobileSearchContainer.hasClass('active')) {
                    $mobileSearchContainer.removeClass('active');
                }
            }
        });
        
        // ========== USER DROPDOWN TOGGLE ==========
        var $userMenuBtn = $('#userMenuBtn');
        var $userDropdown = $('#userDropdown');
        
        if ($userMenuBtn.length && $userDropdown.length) {
            $userMenuBtn.on('click', function(e) {
                e.stopPropagation();
                $userDropdown.toggleClass('active');
                $userMenuBtn.toggleClass('active');
            });
            
            $(document).on('click', function(e) {
                if (!$userMenuBtn.is(e.target) && 
                    !$userDropdown.is(e.target) && 
                    !$userMenuBtn.has(e.target).length && 
                    !$userDropdown.has(e.target).length) {
                    $userDropdown.removeClass('active');
                    $userMenuBtn.removeClass('active');
                }
            });
        }
        
        // ========== SELL LINK HANDLING FOR BUYERS ==========
        var $sellLink = $('#sell-link');
        var $sellLinkMobile = $('#sell-link-mobile');
        
        function handleSellLink(e) {
            if (isLoggedIn && currentUserRole === 'buyer') {
                e.preventDefault();
                var userConfirmed = confirm('You are currently registered as a buyer.\n\nWould you like to upgrade to a seller account? This will allow you to list and sell products on ConsuTrade.');
                
                if (userConfirmed) {
                    window.location.href = baseUrl + 'php/upgrade-to-seller.php';
                }
            }
        }
        
        if ($sellLink.length) {
            $sellLink.on('click', handleSellLink);
        }
        
        if ($sellLinkMobile.length) {
            $sellLinkMobile.on('click', handleSellLink);
        }
        
        // ========== ACTIVE NAVIGATION LINK ==========
        function setActiveLink() {
            var path = window.location.pathname;
            var currentPage = path.substring(path.lastIndexOf('/') + 1);
            
            if (currentPage === '') {
                currentPage = 'index.php';
            }
            
            $('.main-nav a, .mobile-nav-links a').each(function() {
                var $link = $(this);
                var href = $link.attr('href');
                
                $link.removeClass('active');
                
                if (href) {
                    var hrefPage = href.substring(href.lastIndexOf('/') + 1);
                    
                    if (hrefPage.indexOf('?') !== -1) {
                        hrefPage = hrefPage.substring(0, hrefPage.indexOf('?'));
                    }
                    
                    if ((currentPage === 'index.php' || currentPage === 'index.html' || currentPage === '') && 
                        (hrefPage === 'index.php' || hrefPage === 'index.html' || hrefPage === '')) {
                        $link.addClass('active');
                    }
                    else if (hrefPage === currentPage) {
                        $link.addClass('active');
                    }
                    else if (currentPage === 'cart.php' && hrefPage === 'cart.php') {
                        $link.addClass('active');
                    }
                    else if (currentPage === 'sell.php' && hrefPage === 'sell.php') {
                        $link.addClass('active');
                    }
                    else if (currentPage === 'profile.php' && hrefPage === 'profile.php') {
                        $link.addClass('active');
                    }
                    else if (currentPage === 'my-orders.php' && hrefPage === 'my-orders.php') {
                        $link.addClass('active');
                    }
                }
            });
        }
        
        setActiveLink();
    });
    </script>