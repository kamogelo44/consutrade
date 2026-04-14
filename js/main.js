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
 * - User dropdown menus (desktop + mobile)
 * - Sell link behavior for logged-in buyers
 * - Active navigation link highlighting
 */

// Wait for the page to finish loading - learned this the hard way when my JS ran before elements existed
document.addEventListener('DOMContentLoaded', function() {
    
    // ========== ACTIVE NAVIGATION LINK ==========
    // Highlights the current page in the navigation menu

    function setActiveLink() {
        // Get current path
        var path = window.location.pathname;
        var currentPage = path.substring(path.lastIndexOf('/') + 1);
        
        // If currentPage is empty (URL ends with /), treat as index.php
        if (currentPage === '') {
            currentPage = 'index.php';
        }
        
        // Get all navigation links - only for main website
        var navLinks = document.querySelectorAll('.nav-links a, .mobile-nav-links a, .mobile-menu-cart');
        
        for (var i = 0; i < navLinks.length; i++) {
            var link = navLinks[i];
            var href = link.getAttribute('href');
            
            // Remove active class from all links first
            link.classList.remove('active');
            
            // For regular pages, check if the link href matches the current page
            if (href) {
                // Extract just the filename from href (handles absolute paths)
                var hrefPage = href.substring(href.lastIndexOf('/') + 1);
                
                // Remove query parameters if any
                if (hrefPage.indexOf('?') !== -1) {
                    hrefPage = hrefPage.substring(0, hrefPage.indexOf('?'));
                }
                
                // Handle homepage
                if ((currentPage === 'index.php' || currentPage === 'index.html' || currentPage === '') && 
                    (hrefPage === 'index.php' || hrefPage === 'index.html' || hrefPage === '')) {
                    link.classList.add('active');
                }
                // Handle exact page match
                else if (hrefPage === currentPage) {
                    link.classList.add('active');
                }
                // Handle shop/product-listings page
                else if (currentPage === 'product-listings.php' && hrefPage === 'product-listings.php') {
                    link.classList.add('active');
                }
                // Handle cart page
                else if (currentPage === 'cart.php' && hrefPage === 'cart.php') {
                    link.classList.add('active');
                }
                // Handle sell page
                else if (currentPage === 'sell.php' && hrefPage === 'sell.php') {
                    link.classList.add('active');
                }
                // Handle profile page
                else if (currentPage === 'profile.php' && hrefPage === 'profile.php') {
                    link.classList.add('active');
                }
                // Handle my-orders page for buyers
                else if (currentPage === 'my-orders.php' && hrefPage === 'my-orders.php') {
                    link.classList.add('active');
                }
            }
        }
    }

    // Run the active link function when page loads
    setActiveLink();
    
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
                
                // Change the eye icon - only do this ONCE
                var img = this.querySelector('img');
                if (img) {
                    var iconPath = newType === 'password' ? 
                        'images/icons/eye-open-svgrepo-com.svg' : 
                        'images/icons/eye-close-svgrepo-com.svg';
                    img.setAttribute('src', iconPath);
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
    
    // ========== SELL LINK HANDLING FOR LOGGED-IN BUYERS ==========
    // When a logged-in buyer clicks "Sell", show confirmation before proceeding
    
    var sellLink = document.getElementById('sell-link');
    var sellLinkMobile = document.querySelector('.sell-link-mobile');
    
    function handleSellLink(e) {
        e.preventDefault();
        // Show confirmation dialog for buyers
        var userConfirmed = confirm('You are currently registered as a buyer.\n\nWould you like to upgrade to a seller account? This will allow you to list and sell products on ConsuTrade.');
        
        if (userConfirmed) {
            window.location.href = 'php/upgrade-to-seller.php';
        }
    }
    
    if (sellLink) {
        sellLink.addEventListener('click', handleSellLink);
    }
    
    if (sellLinkMobile) {
        sellLinkMobile.addEventListener('click', handleSellLink);
    }
    
    // ========== UPGRADE BANNER BUTTON ==========
    // Handle the upgrade button on the sell.php page for logged-in buyers
    
    var upgradeBtn = document.getElementById('upgrade-to-seller-btn');
    if (upgradeBtn) {
        upgradeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            var userConfirmed = confirm('Are you sure you want to upgrade to a seller account?\n\nYou will be able to list and sell your products on ConsuTrade.');
            
            if (userConfirmed) {
                window.location.href = 'php/upgrade-to-seller.php';
            }
        });
    }
    
    // ========== AUTO-CLOSE MODAL ON SUCCESS ==========
    // If there's a flash message and no errors, close any open modals
    // This prevents the modal from staying open after successful registration/login
    
    var flashMessage = document.querySelector('.flash');
    if (flashMessage) {
        // Flash message exists = successful registration/login
        // Close any open modals
        if (registerModal && registerModal.classList.contains('active')) {
            closeModal(registerModal);
        }
        if (loginModal && loginModal.classList.contains('active')) {
            closeModal(loginModal);
        }
        
        // Auto-hide flash message after 5 seconds
        setTimeout(function() {
            flashMessage.style.opacity = '0';
            flashMessage.style.transition = 'opacity 0.5s ease';
            setTimeout(function() {
                if (flashMessage) flashMessage.remove();
            }, 500);
        }, 5000);
    }

    // ========== USER DROPDOWN TOGGLE (DESKTOP) ==========
    // Took me a while to figure out how to close dropdown when clicking outside
    // Had to use event listeners properly

    var userInfo = document.querySelector('.user-info');
    var desktopDropdownMenu = document.getElementById('desktopDropdownMenu');

    if (userInfo && desktopDropdownMenu) {
        userInfo.addEventListener('click', function(e) {
            e.stopPropagation();
            desktopDropdownMenu.classList.toggle('show');
        });        
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (userInfo && desktopDropdownMenu) {
                if (!userInfo.contains(e.target) && !desktopDropdownMenu.contains(e.target)) {
                    desktopDropdownMenu.classList.remove('show');
                }
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

    // ========== MY CART FUNCTIONS ==========
    // took me forever to figure out fetch() but i got it working
    // Using absolute paths to avoid 404 errors from different folders

    function addToCart(productId, productName, productPrice, quantity = 1) {
        fetch('php/add-to-cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                product_id: productId,
                product_name: productName,
                product_price: productPrice,
                quantity: quantity
            })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                updateCartCount();
                alert('Item added to cart!');
            } else {
                alert('Error adding item to cart');
            }
        })
        .catch(function(error) {
            console.log('Error:', error);
            alert('Something went wrong');
        });
    }

    function updateCartItem(productId, quantity) {
        fetch('php/update-cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: quantity
            })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                updateCartCount();
                location.reload();
            }
        })
        .catch(function(error) {
            console.log('Error:', error);
        });
    }

    function removeFromCart(productId) {
        fetch('php/remove-from-cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                product_id: productId
            })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                updateCartCount();
                location.reload();
            }
        })
        .catch(function(error) {
            console.log('Error:', error);
        });
    }

    function updateCartCount() {
        fetch('php/get-cart.php')
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                var cartCount = data.item_count;
                var cartBadges = document.querySelectorAll('.cart-count');
                for (var i = 0; i < cartBadges.length; i++) {
                    cartBadges[i].textContent = cartCount;
                }
                
                var itemNum = document.querySelector('.item-num');
                if (itemNum) {
                    itemNum.textContent = cartCount;
                }
            }
        })
        .catch(function(error) {
            console.log('Error fetching cart count:', error);
        });
    }

    // Load all cart items for the cart page - only runs on cart.php
    function loadCart() {
        // Only run this on the cart page
        if (window.location.pathname.includes('cart.php')) {
            fetch('php/get-cart.php')
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    displayCartItems(data);
                    updateOrderSummary(data);
                }
            })
            .catch(function(error) {
                console.log('Error loading cart:', error);
            });
        }
    }
    
    // Display cart items on the page
    function displayCartItems(cartData) {
        var desktopTableBody = document.querySelector('.product-table tbody');
        var mobileContainer = document.querySelector('.mobile-cart-items');
        var emptyCartDiv = document.getElementById('empty-cart');
        var cartLayout = document.querySelector('.cart-layout');
        
        if (!cartData.items || cartData.items.length === 0) {
            // Show empty cart state
            if (emptyCartDiv) emptyCartDiv.style.display = 'flex';
            if (cartLayout) cartLayout.style.display = 'none';
            return;
        }
        
        // Hide empty cart, show layout
        if (emptyCartDiv) emptyCartDiv.style.display = 'none';
        if (cartLayout) cartLayout.style.display = 'flex';
        
        // Clear existing rows
        if (desktopTableBody) desktopTableBody.innerHTML = '';
        if (mobileContainer) mobileContainer.innerHTML = '';
        
        // Loop through cart items and create rows/cards
        for (var i = 0; i < cartData.items.length; i++) {
            var item = cartData.items[i];
            var verifiedBadge = '<div class="verified-badge-cart"><img src="images/icons/verified-svgrepo-com.svg" width="20px" height="20px" alt="verification"><p>Verified Seller</p></div>';
            
            // Desktop table row
            if (desktopTableBody) {
                var row = document.createElement('tr');
                row.innerHTML = `
                    <td class="product-cell" data-label="Product">
                        <div class="cart-product-wrapper">
                            <div class="cart-img-container">
                                <img src="${item.image}" alt="${item.product_name}">
                            </div>
                            <div class="cart-prod-info">
                                <p class="prod-name">${item.product_name}</p>
                                <p><span class="num-avail">${item.stock}</span> Available</p>
                            </div>
                        </div>
                    </td>
                    <td class="seller-cell" data-label="Seller">
                        <div class="seller-cart-info">
                            <p class="seller-name">${item.seller_name}</p>
                            <div class="verification">
                                ${verifiedBadge}
                            </div>
                        </div>
                    </td>
                    <td class="price-cell" data-label="Price">R ${parseFloat(item.price).toFixed(2)}</td>
                    <td class="actions-cell" data-label="Actions">
                        <button class="remove-btn" onclick="removeFromCart(${item.product_id})">Remove</button>
                    </td>
                `;
                desktopTableBody.appendChild(row);
            }
            
            // Mobile card
            if (mobileContainer) {
                var card = document.createElement('div');
                card.className = 'mobile-cart-card';
                card.innerHTML = `
                    <div class="mobile-cart-img">
                        <img src="${item.image}" alt="${item.product_name}">
                    </div>
                    <div class="mobile-cart-details">
                        <h3 class="mobile-prod-name">${item.product_name}</h3>
                        <p class="mobile-availability"><span class="num-avail">${item.stock}</span> Available</p>
                    </div>
                    <div class="mobile-cart-seller">
                        <p class="seller-name">${item.seller_name}</p>
                        ${verifiedBadge}
                    </div>
                    <div class="mobile-cart-price">
                        <p class="price">R ${parseFloat(item.price).toFixed(2)}</p>
                    </div>
                    <div class="mobile-cart-actions">
                        <button class="remove-btn" onclick="removeFromCart(${item.product_id})">Remove</button>
                    </div>
                `;
                mobileContainer.appendChild(card);
            }
        }
    }
    
    // Update the order summary totals
    function updateOrderSummary(cartData) {
        var subtotalSpan = document.querySelector('.sub-total-val');
        var deliverySpan = document.querySelector('.deliv-fee-val');
        var totalSpan = document.querySelector('.total-val');
        
        if (subtotalSpan) subtotalSpan.textContent = cartData.subtotal;
        if (deliverySpan) deliverySpan.textContent = cartData.delivery_fee;
        if (totalSpan) totalSpan.textContent = cartData.total;
    }
    
    // Run these when page loads
    updateCartCount();
    loadCart();
    
    // TODO: Features I want to add later when I have time:
    // 1. Product image preview when uploading (like Facebook does)
    // 2. Better cart add/remove animations
    // 3. Form validation before submitting (right now it relies on PHP only)
    // 4. Lazy loading for product images to make page faster
    
    // I keep updating the code for the javascript along with the comments. 
    // This is cause i want to make this site to the best of my abilities without overcomplicated.
});