/*
 * ConsuTrade - Main JavaScript File
 * Author: Kamogelo Phale
 * Student Project - All functionality for the ConsuTrade website
 */

// Wait for the HTML document to fully load before running any JavaScript
// This ensures all elements exist before we try to interact with them
document.addEventListener('DOMContentLoaded', function() {
    
    // ========== HAMBURGER MENU TOGGLE ==========
    // This makes the mobile side menu slide in and out when clicking the hamburger icon
    
    // Get all the elements needed for the mobile menu
    const hamburger = document.getElementById('hamburger');           // The three lines button (☰)
    const sideMenuHamburger = document.getElementById('sideMenuHamburger'); // Copy of hamburger inside the menu
    const sideMenu = document.getElementById('mobile-side-menu');     // The panel that slides in
    const overlay = document.getElementById('menu-overlay');         // Dark background behind menu
    
    // Only run this code if all the elements exist on the page
    if (hamburger && sideMenu && overlay) {
        
        // This function opens or closes the side menu
        function toggleMenu() {
            // Toggle means switch between two states (open ↔ closed)
            
            // Change the hamburger icon to an X when menu opens
            hamburger.classList.toggle('active');
            
            // Keep both hamburgers in sync (the one in header and the one in menu)
            if (sideMenuHamburger) {
                sideMenuHamburger.classList.toggle('active');
            }
            
            // Slide the menu in or out
            sideMenu.classList.toggle('active');
            
            // Show or hide the dark overlay
            overlay.classList.toggle('active');
            
            // If menu is now open, prevent scrolling and dim the background
            if (sideMenu.classList.contains('active')) {
                document.body.classList.add('menu-open');      // Adds class to dim content
                document.body.style.overflow = 'hidden';       // Stops page from scrolling
            } else {
                // Menu is closed, restore normal scrolling
                document.body.classList.remove('menu-open');
                document.body.style.overflow = '';
            }
        }

        // This function highlights the current page in the navigation menu
        function setActiveLink() {
            // Get the current page filename (like "index.html" or "shop.html")
            const currentPage = window.location.pathname.split('/').pop();
            
            // If the URL ends with just "/", treat it as index.html
            const pageName = currentPage === '' ? 'index.html' : currentPage;
            
            // Get all navigation links (both desktop and mobile)
            const allLinks = document.querySelectorAll('.nav-links a, .mobile-nav-links a');
            
            // Loop through each link and check if it matches the current page
            allLinks.forEach(function(link) {
                const linkHref = link.getAttribute('href');
                
                if (linkHref === pageName) {
                    link.classList.add('active');   // Add orange color to current page link
                } else {
                    link.classList.remove('active'); // Remove orange from other links
                }
            });
        }
        
        // Run the function to highlight the active link when page loads
        setActiveLink();
        
        // When user clicks the hamburger icon, open or close the menu
        hamburger.addEventListener('click', toggleMenu);
        
        // When user clicks the hamburger inside the side menu, close the menu
        if (sideMenuHamburger) {
            sideMenuHamburger.addEventListener('click', toggleMenu);
        }
        
        // When user clicks the dark overlay, close the menu
        overlay.addEventListener('click', toggleMenu);
        
        // Close menu when user clicks any link inside the side menu
        const menuLinks = document.querySelectorAll('.mobile-nav-links a, .mobile-menu-cart');
        menuLinks.forEach(function(link) {
            link.addEventListener('click', toggleMenu);
        });
        
        // If the user resizes the window to desktop size, close the mobile menu
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                if (sideMenu.classList.contains('active')) {
                    toggleMenu();  // Close the menu
                }
            }
        });
    }
    
    // ========== MOBILE SEARCH TOGGLE ==========
    // This makes the search bar expand when clicking the search icon on mobile
    
    const mobileSearchIcon = document.getElementById('mobileSearchIcon');
    const mobileSearchContainer = document.getElementById('mobileSearchContainer');
    
    if (mobileSearchIcon && mobileSearchContainer) {
        mobileSearchIcon.addEventListener('click', function() {
            // Show or hide the search input
            mobileSearchContainer.classList.toggle('active');
            
            // If search bar opened, automatically focus on the input field
            if (mobileSearchContainer.classList.contains('active')) {
                const searchInput = document.getElementById('mobile-search');
                if (searchInput) {
                    searchInput.focus();  // Brings up keyboard on phones
                }
            }
        });
    }
    
    // ========== CLOSE SEARCH WHEN CLICKING OUTSIDE ==========
    // This closes the search bar if user clicks anywhere else on the page
    
    document.addEventListener('click', function(event) {
        if (mobileSearchContainer && mobileSearchIcon) {
            // Check if click was outside search container AND outside search icon
            if (!mobileSearchContainer.contains(event.target) && 
                !mobileSearchIcon.contains(event.target) && 
                mobileSearchContainer.classList.contains('active')) {
                
                mobileSearchContainer.classList.remove('active'); // Close search bar
            }
        }
    });
    
    // ========== REGISTRATION MODAL TOGGLE ==========
    // This controls the popup registration form
    
    const registerModal = document.getElementById('register-modal');
    const registerBtns = document.querySelectorAll('#register');  // All Register buttons on page
    const loginLink = document.querySelector('#login');
    const registerCloseBtn = document.querySelector('#register-modal .btn-close');
    
    // Removes the orange highlight from Register buttons when modal closes
    function removeRegisterModalActiveClasses() {
        registerBtns.forEach(function(btn) {
            btn.classList.remove('active');
        });
        if (loginLink) {
            loginLink.classList.remove('active');
        }
    }
    
    // Switches from Register modal to Login modal with smooth transition
    function switchToLoginModal() {
        if (registerModal && registerModal.classList.contains('active')) {
            // Start closing register modal (it will slide out to the right)
            registerModal.classList.remove('active');
            
            // Wait for register modal to finish closing animation (400ms)
            setTimeout(function() {
                // Now open login modal (it will slide in from left)
                loginModal.classList.add('active');
                document.body.style.overflow = 'hidden';
                
                // Update active link states
                if (registerLink) registerLink.classList.remove('active');
                if (loginBtns.length > 0) {
                    loginBtns.forEach(function(btn) {
                        btn.classList.add('active');
                    });
                }
            }, 400);
        } else {
            loginModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }
    
    // Only run if the registration modal exists on this page
    if (registerModal) {
        // Open modal when any Register button is clicked
        if (registerBtns.length > 0) {
            registerBtns.forEach(function(registerBtn) {
                registerBtn.addEventListener('click', function(e) {
                    e.preventDefault();  // Prevent link from jumping to #
                    
                    // Close login modal if it's open with smooth transition
                    if (loginModal && loginModal.classList.contains('active')) {
                        loginModal.classList.remove('active');
                        
                        // Wait for login modal to close before opening register
                        setTimeout(function() {
                            registerBtn.classList.add('active');
                            registerModal.classList.add('active');
                            document.body.style.overflow = 'hidden';
                            removeLoginModalActiveClasses();
                        }, 400);
                    } else {
                        registerBtn.classList.add('active');
                        registerModal.classList.add('active');
                        document.body.style.overflow = 'hidden';
                    }
                    
                    if (loginLink) {
                        loginLink.classList.remove('active');
                    }
                });
            });
        }
        
        // Close modal when clicking the X button
        if (registerCloseBtn) {
            registerCloseBtn.addEventListener('click', function() {
                registerModal.classList.remove('active');
                document.body.style.overflow = '';
                removeRegisterModalActiveClasses();
            });
        }
        
        // Close modal when clicking outside the white modal content
        registerModal.addEventListener('click', function(e) {
            if (e.target === registerModal) {
                registerModal.classList.remove('active');
                document.body.style.overflow = '';
                removeRegisterModalActiveClasses();
            }
        });
        
        // Handle form submission (testing only - actual backend not implemented yet)
        const registerForm = document.querySelector('.register-form');
        if (registerForm) {
            registerForm.addEventListener('submit', function(e) {
                e.preventDefault();  // Prevent page refresh
                alert('Registration form submitted! (This is just a test)');
            });
        }
        
        // Switch to Login modal when user clicks "Already have an account? Login"
        const loginLinkInRegister = document.querySelector('#register-modal .login-link a');
        if (loginLinkInRegister) {
            loginLinkInRegister.addEventListener('click', function(e) {
                e.preventDefault();
                switchToLoginModal();
            });
        }
    }
    
    // ========== LOGIN MODAL TOGGLE ==========
    // This controls the popup login form
    
    const loginModal = document.getElementById('login-modal');
    const loginBtns = document.querySelectorAll('#login');  // All Login buttons on page
    const registerLink = document.querySelector('#register');
    const loginCloseBtn = document.querySelector('#login-modal .btn-close');
    
    // Removes the orange highlight from Login buttons when modal closes
    function removeLoginModalActiveClasses() {
        loginBtns.forEach(function(btn) {
            btn.classList.remove('active');
        });
        if (registerLink) {
            registerLink.classList.remove('active');
        }
    }
    
    // Switches from Login modal to Register modal with smooth transition
    function switchToRegisterModal() {
        if (loginModal && loginModal.classList.contains('active')) {
            // Start closing login modal (it will slide out to the left)
            loginModal.classList.remove('active');
            
            // Wait for login modal to finish closing animation (400ms)
            setTimeout(function() {
                // Now open register modal (it will slide in from right)
                registerModal.classList.add('active');
                document.body.style.overflow = 'hidden';
                
                // Update active link states
                if (loginLink) loginLink.classList.remove('active');
                if (registerBtns.length > 0) {
                    registerBtns.forEach(function(btn) {
                        btn.classList.add('active');
                    });
                }
            }, 400);
        } else {
            registerModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }
    
    // Only run if the login modal exists on this page
    if (loginModal) {
        // Open modal when any Login button is clicked
        if (loginBtns.length > 0) {
            loginBtns.forEach(function(loginBtn) {
                loginBtn.addEventListener('click', function(e) {
                    e.preventDefault();  // Prevent link from jumping to #
                    
                    // Close register modal if it's open with smooth transition
                    if (registerModal && registerModal.classList.contains('active')) {
                        registerModal.classList.remove('active');
                        
                        // Wait for register modal to close before opening login
                        setTimeout(function() {
                            loginBtn.classList.add('active');
                            loginModal.classList.add('active');
                            document.body.style.overflow = 'hidden';
                            removeRegisterModalActiveClasses();
                        }, 400);
                    } else {
                        loginBtn.classList.add('active');
                        loginModal.classList.add('active');
                        document.body.style.overflow = 'hidden';
                    }
                    
                    if (registerLink) {
                        registerLink.classList.remove('active');
                    }
                });
            });
        }
        
        // Close modal when clicking the X button
        if (loginCloseBtn) {
            loginCloseBtn.addEventListener('click', function() {
                loginModal.classList.remove('active');
                document.body.style.overflow = '';
                removeLoginModalActiveClasses();
            });
        }
        
        // Close modal when clicking outside the white modal content
        loginModal.addEventListener('click', function(e) {
            if (e.target === loginModal) {
                loginModal.classList.remove('active');
                document.body.style.overflow = '';
                removeLoginModalActiveClasses();
            }
        });
        
        // Handle form submission (testing only - actual backend not implemented yet)
        const loginForm = document.querySelector('.login-form');
        if (loginForm) {
            loginForm.addEventListener('submit', function(e) {
                e.preventDefault();  // Prevent page refresh
                alert('Login form submitted! (This is just a test)');
            });
        }
        
        // Switch to Register modal when user clicks "Don't have an account? Register"
        const registerLinkInLogin = document.querySelector('#login-modal .register-link a');
        if (registerLinkInLogin) {
            registerLinkInLogin.addEventListener('click', function(e) {
                e.preventDefault();
                switchToRegisterModal();
            });
        }
    }
});

// ========== FEATURES TO BE IMPLEMENTED LATER ==========
// These features will be added as the project progresses:
// 1. Product image preview when sellers upload products
// 2. Shopping cart add/remove functionality with price calculations
// 3. Form validation for registration, login, and product listing forms
// 4. Lazy loading for product images (improves performance on slow connections)