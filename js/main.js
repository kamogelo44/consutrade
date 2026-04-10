/*
 * ConsuTrade - Main JavaScript File
 * Author: Kamogelo Phale
 * 
 * This file handles:
 * - Mobile menu toggle (hamburger)
 * - Mobile search bar
 * - Password show/hide (took me a while to figure this out)
 * - Login/register modal popups
 * - Cart count updates
 * 
 */

// Wait for the page to finish loading - learned this the hard way when my JS ran before elements existed
document.addEventListener('DOMContentLoaded', function() {
    
    // ========== HAMBURGER MENU ==========
    // Got this from a YouTube tutorial but modified it to work with my layout
    // The side menu slides in from the left when you click the three lines
    
    var hamburger = document.getElementById('hamburger');
    var sideMenuHamburger = document.getElementById('sideMenuHamburger');
    var sideMenu = document.getElementById('mobile-side-menu');
    var overlay = document.getElementById('menu-overlay');
    
    function toggleMenu() {
        // Toggle means switch between open and closed states
        hamburger.classList.toggle('active');
        if (sideMenuHamburger) sideMenuHamburger.classList.toggle('active');
        sideMenu.classList.toggle('active');
        overlay.classList.toggle('active');
        
        // Stop scrolling when menu is open - otherwise the background scrolls behind the menu
        if (sideMenu.classList.contains('active')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
    
    // Only run this if the menu elements actually exist on this page
    if (hamburger && sideMenu && overlay) {
        hamburger.addEventListener('click', toggleMenu);
        if (sideMenuHamburger) sideMenuHamburger.addEventListener('click', toggleMenu);
        overlay.addEventListener('click', toggleMenu);
        
        // Close menu when you click any link inside it - makes sense for user experience
        document.querySelectorAll('.mobile-nav-links a, .mobile-menu-cart').forEach(function(link) {
            link.addEventListener('click', toggleMenu);
        });
        
        // If user resizes window to desktop size, close the mobile menu automatically
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768 && sideMenu.classList.contains('active')) {
                toggleMenu();
            }
        });
    }
    
    // ========== MOBILE SEARCH ==========
    // Clicking the search icon makes the search bar expand
    // Had to use stopPropagation() because clicks were closing it immediately
    
    var mobileSearchIcon = document.getElementById('mobileSearchIcon');
    var mobileSearchContainer = document.getElementById('mobileSearchContainer');
    
    if (mobileSearchIcon && mobileSearchContainer) {
        mobileSearchIcon.addEventListener('click', function(e) {
            e.stopPropagation(); // Without this, the document click listener would close it right away
            mobileSearchContainer.classList.toggle('active');
            
            // Automatically focus the search input when it opens - small detail but users like it
            if (mobileSearchContainer.classList.contains('active')) {
                var searchInput = document.getElementById('mobile-search');
                if (searchInput) {
                    setTimeout(function() { searchInput.focus(); }, 50);
                }
            }
        });
    }
    
    // Close search bar when clicking anywhere outside of it
    document.addEventListener('click', function(event) {
        if (mobileSearchContainer && mobileSearchIcon) {
            if (!mobileSearchContainer.contains(event.target) && 
                !mobileSearchIcon.contains(event.target) && 
                mobileSearchContainer.classList.contains('active')) {
                mobileSearchContainer.classList.remove('active');
            }
        }
    });
    
    // ========== PASSWORD TOGGLE ==========
    // This was tricky - I had to figure out how to toggle input type between 'password' and 'text'
    // My first version didn't work because I used getElementById wrong
    
    document.querySelectorAll('.toggle-password').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var targetId = this.getAttribute('data-target');
            var input = document.getElementById(targetId);
            
            if (input) {
                // Check current type and switch it
                var currentType = input.getAttribute('type');
                var newType = currentType === 'password' ? 'text' : 'password';
                input.setAttribute('type', newType);
                
                // Change the eye icon to show whether password is visible or not
                // I need both eye-open and eye-closed icons in my images folder
                var img = this.querySelector('img');
                if (img) {
                    var iconPath = newType === 'password' ? 
                        'images/icons/eye-open-svgrepo-com.svg' : 
                        'images/icons/eye-close-svgrepo-com.svg';
                    img.setAttribute('src', iconPath);
                }
                
                if (newType === 'password') {
                    // Password is hidden, show OPEN eye
                    img.setAttribute('src', 'images/icons/eye-open-svgrepo-com.svg');
                } else {
                    // Password is visible, show CLOSED eye  
                    img.setAttribute('src', 'images/icons/eye-close-svgrepo-com.svg');
                }
            }
        });
    });
    
    // ========== MODAL CONTROLS ==========
    // Popup windows for login and registration
    // Had to make sure only one modal opens at a time
    
    var registerModal = document.getElementById('register-modal');
    var loginModal = document.getElementById('login-modal');
    var registerBtns = document.querySelectorAll('#register, .register-link-mobile');
    var loginBtns = document.querySelectorAll('#login, .login-link-mobile');
    var registerClose = document.querySelector('#register-modal .btn-close');
    var loginClose = document.querySelector('#login-modal .btn-close');
    
    function openModal(modal) {
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        }
    }
    
    function closeModal(modal) {
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
    
    // REGISTER MODAL
    if (registerModal && registerBtns.length) {
        registerBtns.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                // Close login modal first if it's open - can't have both open at once
                if (loginModal && loginModal.classList.contains('active')) {
                    closeModal(loginModal);
                }
                openModal(registerModal);
            });
        });
        
        // Close button (X)
        if (registerClose) {
            registerClose.addEventListener('click', function() { 
                closeModal(registerModal); 
            });
        }
        
        // Click outside the white box closes the modal
        registerModal.addEventListener('click', function(e) {
            if (e.target === registerModal) {
                closeModal(registerModal);
            }
        });
        
        // Switch to login modal when user clicks "Already have an account?"
        var loginLinkInRegister = document.querySelector('#register-modal .login-link a');
        if (loginLinkInRegister) {
            loginLinkInRegister.addEventListener('click', function(e) {
                e.preventDefault();
                closeModal(registerModal);
                // Small delay makes the transition smoother
                setTimeout(function() { 
                    openModal(loginModal); 
                }, 400);
            });
        }
    }
    
    // LOGIN MODAL - same pattern as register modal
    if (loginModal && loginBtns.length) {
        loginBtns.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                if (registerModal && registerModal.classList.contains('active')) {
                    closeModal(registerModal);
                }
                openModal(loginModal);
            });
        });
        
        if (loginClose) {
            loginClose.addEventListener('click', function() { 
                closeModal(loginModal); 
            });
        }
        
        loginModal.addEventListener('click', function(e) {
            if (e.target === loginModal) {
                closeModal(loginModal);
            }
        });
        
        // Switch to register modal
        var registerLinkInLogin = document.querySelector('#login-modal .register-link a');
        if (registerLinkInLogin) {
            registerLinkInLogin.addEventListener('click', function(e) {
                e.preventDefault();
                closeModal(loginModal);
                setTimeout(function() { 
                    openModal(registerModal); 
                }, 400);
            });
        }
    }
    
    // ========== SELLER PAGE BUTTONS ==========
    // These buttons are on the seller dashboard page
    // They trigger the same modals from the seller area
    
    var sellerRegisterBtn = document.getElementById('seller-register-btn');
    var sellerLoginBtn = document.getElementById('seller-login-btn');
    var createSellerBtn = document.getElementById('create-seller-btn');
    
    if (sellerRegisterBtn) {
        sellerRegisterBtn.addEventListener('click', function(e) {
            e.preventDefault();
            openModal(registerModal);
        });
    }
    
    if (sellerLoginBtn) {
        sellerLoginBtn.addEventListener('click', function(e) {
            e.preventDefault();
            openModal(loginModal);
        });
    }
    
    if (createSellerBtn) {
        createSellerBtn.addEventListener('click', function(e) {
            e.preventDefault();
            openModal(registerModal);
        });
    }
    
    // ========== CART COUNT ==========
    // Reads from localStorage and updates all cart badges on the page
    // I need to call this whenever items are added/removed from cart
    
    function updateCartCount() {
        var cartCount = 0;
        var savedCart = localStorage.getItem('consutrade_cart');
        
        if (savedCart) {
            try {
                var cart = JSON.parse(savedCart);
                // Loop through each item and add up quantities
                for (var i = 0; i < cart.length; i++) {
                    cartCount += cart[i].quantity || 1;
                }
            } catch(e) {
                // If JSON is corrupted, just ignore it
                console.log('Error reading cart:', e);
            }
        }
        
        // Update all cart count badges (there are multiple on desktop and mobile)
        var cartBadges = document.querySelectorAll('.cart-count');
        for (var i = 0; i < cartBadges.length; i++) {
            cartBadges[i].textContent = cartCount;
        }
        
        // Update the cart page item count if we're on that page
        var itemNum = document.querySelector('.item-num');
        if (itemNum) {
            itemNum.textContent = cartCount;
        }
    }

    // ========== USER DROPDOWN TOGGLE (DESKTOP) ==========
    // Took me a while to figure out how to close dropdown when clicking outside
    // Had to use event listeners properly

    var desktopDropdownToggle = document.getElementById('desktopDropdownToggle');
    var desktopDropdownMenu = document.getElementById('desktopDropdownMenu');

    if (desktopDropdownToggle && desktopDropdownMenu) {
        desktopDropdownToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            desktopDropdownMenu.classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!desktopDropdownToggle.contains(e.target) && !desktopDropdownMenu.contains(e.target)) {
                desktopDropdownMenu.classList.remove('show');
            }
        });
    }

    // ========== MOBILE DROPDOWN TOGGLE ==========
    var mobileDropdownToggle = document.getElementById('mobileDropdownToggle');
    var mobileDropdownMenu = document.getElementById('mobileDropdownMenu');

    if (mobileDropdownToggle && mobileDropdownMenu) {
        mobileDropdownToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            mobileDropdownMenu.classList.toggle('show');
        });
        
        // Close mobile dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (mobileDropdownToggle && mobileDropdownMenu) {
                if (!mobileDropdownToggle.contains(e.target) && !mobileDropdownMenu.contains(e.target)) {
                    mobileDropdownMenu.classList.remove('show');
                }
            }
        });
    }
    

    // Run this when page loads
    updateCartCount();
    
    // TODO: Features I want to add later when I have time:
    // 1. Product image preview when uploading (like Facebook does)
    // 2. Better cart add/remove animations
    // 3. Form validation before submitting (right now it relies on PHP only)
    // 4. Lazy loading for product images to make page faster
    
    //I keep updating the code for the javascript along with the comments. 
    // This is cause i want to make this site to the best of my abilities without overcomplicated.
});