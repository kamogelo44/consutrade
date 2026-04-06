/*
 * ConsuTrade - Main JavaScript File
 * Author: Kamogelo Phale
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ========== HAMBURGER MENU TOGGLE ==========
    
    const hamburger = document.getElementById('hamburger');
    const sideMenuHamburger = document.getElementById('sideMenuHamburger');
    const sideMenu = document.getElementById('mobile-side-menu');
    const overlay = document.getElementById('menu-overlay');
    
    if (hamburger && sideMenu && overlay) {
        
        function toggleMenu() {
            hamburger.classList.toggle('active');
            
            if (sideMenuHamburger) {
                sideMenuHamburger.classList.toggle('active');
            }
            
            sideMenu.classList.toggle('active');
            overlay.classList.toggle('active');
            
            if (sideMenu.classList.contains('active')) {
                document.body.classList.add('menu-open');
                document.body.style.overflow = 'hidden';
            } else {
                document.body.classList.remove('menu-open');
                document.body.style.overflow = '';
            }
        }

        function setActiveLink() {
            const currentPage = window.location.pathname.split('/').pop();
            const pageName = currentPage === '' ? 'index.html' : currentPage;
            const allLinks = document.querySelectorAll('.nav-links a, .mobile-nav-links a');
            
            allLinks.forEach(function(link) {
                const linkHref = link.getAttribute('href');
                
                if (linkHref === pageName) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });
        }
        
        setActiveLink();
        hamburger.addEventListener('click', toggleMenu);
        
        if (sideMenuHamburger) {
            sideMenuHamburger.addEventListener('click', toggleMenu);
        }
        
        overlay.addEventListener('click', toggleMenu);
        
        const menuLinks = document.querySelectorAll('.mobile-nav-links a, .mobile-menu-cart');
        menuLinks.forEach(function(link) {
            link.addEventListener('click', toggleMenu);
        });
        
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                if (sideMenu.classList.contains('active')) {
                    toggleMenu();
                }
            }
        });
    }
    
    // ========== MOBILE SEARCH TOGGLE ==========
    
    const mobileSearchIcon = document.getElementById('mobileSearchIcon');
    const mobileSearchContainer = document.getElementById('mobileSearchContainer');
    
    if (mobileSearchIcon && mobileSearchContainer) {
        mobileSearchIcon.addEventListener('click', function() {
            mobileSearchContainer.classList.toggle('active');
            
            if (mobileSearchContainer.classList.contains('active')) {
                const searchInput = document.getElementById('mobile-search');
                if (searchInput) {
                    searchInput.focus();
                }
            }
        });
    }
    
    // ========== CLOSE SEARCH WHEN CLICKING OUTSIDE ==========
    
    document.addEventListener('click', function(event) {
        if (mobileSearchContainer && mobileSearchIcon) {
            if (!mobileSearchContainer.contains(event.target) && 
                !mobileSearchIcon.contains(event.target) && 
                mobileSearchContainer.classList.contains('active')) {
                mobileSearchContainer.classList.remove('active');
            }
        }
    });
    
    // ========== REGISTRATION MODAL TOGGLE ==========
    
    const modal = document.getElementById('register-modal');
    const registerBtns = document.querySelectorAll('#register');
    const loginLink = document.querySelector('#login');
    const closeBtn = document.querySelector('.btn-close');
    
    function removeModalActiveClasses() {
        registerBtns.forEach(function(btn) {
            btn.classList.remove('active');
        });
        if (loginLink) {
            loginLink.classList.remove('active');
        }
    }
    
    if (modal) {
        if (registerBtns.length > 0) {
            registerBtns.forEach(function(registerBtn) {
                registerBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    if (loginLink) {
                        loginLink.classList.remove('active');
                    }
                    
                    registerBtn.classList.add('active');
                    modal.classList.add('active');
                    document.body.style.overflow = 'hidden';
                });
            });
        }
        
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                modal.classList.remove('active');
                document.body.style.overflow = '';
                removeModalActiveClasses();
            });
        }
        
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
                removeModalActiveClasses();
            }
        });
        
        const form = document.querySelector('.register-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                alert('Form submitted! (This is just a test)');
            });
        }
    }
    
    // ========== LOGIN MODAL PLACEHOLDER ==========
    
    if (loginLink) {
        loginLink.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Login clicked - modal to be implemented');
            alert('Login modal coming soon!');
        });
    }
});

// ========== FEATURES TO BE IMPLEMENTED LATER ==========
// - Product image preview on upload
// - Shopping cart add/remove logic
// - Form validation (register, login, listing)
// - Lazy loading for product images