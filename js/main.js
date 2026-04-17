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

// Base URL for all fetch requests - works from any folder (root or admin)
var baseUrl = '/www/consutrade/';

// ========== GLOBAL CART FUNCTIONS ==========
// These need to be global so they can be called from onclick attributes in dynamically created cards

function addToCart(productId, productName, productPrice) {
    // No quantity parameter - each product can only be added once
    fetch(baseUrl + 'php/add-to-cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            product_id: productId,
            product_name: productName,
            product_price: productPrice
        })
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success) {
            updateCartCount();
            alert(data.message || 'Item added to cart!');
        } else {
            alert(data.message || 'Error adding item to cart');
        }
    })
    .catch(function() {
        alert('Something went wrong');
    });
}
function updateCartItem(productId, quantity) {
    fetch(baseUrl + 'php/update-cart.php', {
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
    .catch(function() {
        alert('Something went wrong');
    });
}

function removeFromCart(productId) {
    if (!confirm('Are you sure you want to remove this item from your cart?')) {
        return;
    }
    
    fetch(baseUrl + 'php/remove-from-cart.php', {
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
            // Reload the page to refresh cart display
            location.reload();
        } else {
            alert(data.message || 'Error removing item from cart');
        }
    })
    .catch(function() {
        alert('Something went wrong');
    });
}

function updateCartCount() {
    fetch(baseUrl + 'php/get-cart.php')
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
    .catch(function() {
        // Silent fail - don't show error for cart count
    });
}

function loadCart() {
    // Only run this on the cart page
    if (window.location.pathname.includes('cart.php')) {
        fetch(baseUrl + 'php/get-cart.php')
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                displayCartItems(data);
                updateOrderSummary(data);
            }
        })
        .catch(function() {
            console.log('Error loading cart');
        });
    }
}

// Display cart items on the page
function displayCartItems(cartData) {
    var desktopTableBody = document.getElementById('cart-table-body');
    var mobileContainer = document.getElementById('mobile-cart-items');
    var emptyCartDiv = document.getElementById('empty-cart');
    var cartLayout = document.getElementById('cart-layout');
    var cartItemCount = document.getElementById('cart-item-count');
    
    if (!cartData.items || cartData.items.length === 0) {
        if (emptyCartDiv) emptyCartDiv.style.display = 'flex';
        if (cartLayout) cartLayout.style.display = 'none';
        if (cartItemCount) cartItemCount.textContent = '0';
        return;
    }
    
    if (emptyCartDiv) emptyCartDiv.style.display = 'none';
    if (cartLayout) cartLayout.style.display = 'flex';
    if (cartItemCount) cartItemCount.textContent = cartData.items.length;
    
    if (desktopTableBody) desktopTableBody.innerHTML = '';
    if (mobileContainer) mobileContainer.innerHTML = '';
    
    for (var i = 0; i < cartData.items.length; i++) {
        var item = cartData.items[i];
        
        // Use the correct badge based on seller verification status
        var verifiedBadge = item.is_verified ? 
            '<div class="verified-badge-cart"><img src="' + baseUrl + 'images/icons/verified-svgrepo-com.svg" width="14px" height="14px" alt="verification"><span>Verified Seller</span></div>' : 
            '<div class="unverified-badge-cart"><img src="' + baseUrl + 'images/icons/not-verified-svgrepo-com.svg" width="14px" height="14px" alt="not-verified"><span>Unverified</span></div>';
        
        // Fix image path
        var imagePath = item.image;
        if (imagePath && !imagePath.startsWith('http') && !imagePath.startsWith('/')) {
            imagePath = baseUrl + imagePath;
        }
        
        // Desktop table row
        if (desktopTableBody) {
            var row = document.createElement('tr');
            row.innerHTML = `
                <td class="product-cell" data-label="Product">
                    <div class="cart-product-wrapper">
                        <div class="cart-img-container">
                            <img src="${imagePath}" alt="${escapeHtml(item.product_name)}" onerror="this.src='${baseUrl}images/default-product.png'">
                        </div>
                        <div class="cart-prod-info">
                            <p class="prod-name">${escapeHtml(item.product_name)}</p>
                        </div>
                    </div>
                </td>
                <td class="seller-cell" data-label="Seller">
                    <div class="seller-cart-info">
                        <p class="seller-name">${escapeHtml(item.seller_name)}</p>
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
                    <img src="${imagePath}" alt="${escapeHtml(item.product_name)}" onerror="this.src='${baseUrl}images/default-product.png'">
                </div>
                <div class="mobile-cart-details">
                    <h3 class="mobile-prod-name">${escapeHtml(item.product_name)}</h3>
                </div>
                <div class="mobile-cart-seller">
                    <p class="seller-name">${escapeHtml(item.seller_name)}</p>
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

function updateOrderSummary(cartData) {
    var subtotalSpan = document.querySelector('.sub-total-val');
    var deliverySpan = document.querySelector('.deliv-fee-val');
    var totalSpan = document.querySelector('.total-val');
    
    if (subtotalSpan) subtotalSpan.textContent = cartData.subtotal;
    if (deliverySpan) deliverySpan.textContent = cartData.delivery_fee;
    if (totalSpan) totalSpan.textContent = cartData.total;
}

function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Wait for the page to finish loading - learned this the hard way when my JS ran before elements existed
document.addEventListener('DOMContentLoaded', function() {
    
    // ========== ACTIVE NAVIGATION LINK ==========
    function setActiveLink() {
        var path = window.location.pathname;
        var currentPage = path.substring(path.lastIndexOf('/') + 1);
        
        if (currentPage === '') {
            currentPage = 'index.php';
        }
        
        var navLinks = document.querySelectorAll('.nav-links a, .mobile-nav-links a, .mobile-menu-cart');
        
        for (var i = 0; i < navLinks.length; i++) {
            var link = navLinks[i];
            var href = link.getAttribute('href');
            
            link.classList.remove('active');
            
            if (href) {
                var hrefPage = href.substring(href.lastIndexOf('/') + 1);
                
                if (hrefPage.indexOf('?') !== -1) {
                    hrefPage = hrefPage.substring(0, hrefPage.indexOf('?'));
                }
                
                if ((currentPage === 'index.php' || currentPage === 'index.html' || currentPage === '') && 
                    (hrefPage === 'index.php' || hrefPage === 'index.html' || hrefPage === '')) {
                    link.classList.add('active');
                }
                else if (hrefPage === currentPage) {
                    link.classList.add('active');
                }
                else if (currentPage === 'product-listings.php' && hrefPage === 'product-listings.php') {
                    link.classList.add('active');
                }
                else if (currentPage === 'cart.php' && hrefPage === 'cart.php') {
                    link.classList.add('active');
                }
                else if (currentPage === 'sell.php' && hrefPage === 'sell.php') {
                    link.classList.add('active');
                }
                else if (currentPage === 'profile.php' && hrefPage === 'profile.php') {
                    link.classList.add('active');
                }
                else if (currentPage === 'my-orders.php' && hrefPage === 'my-orders.php') {
                    link.classList.add('active');
                }
            }
        }
    }

    setActiveLink();
    
    // ========== HAMBURGER MENU ==========
    var hamburger = document.getElementById('hamburger');
    var sideMenuHamburger = document.getElementById('sideMenuHamburger');
    var sideMenu = document.getElementById('mobile-side-menu');
    var overlay = document.getElementById('menu-overlay');
    
    function toggleMenu() {
        hamburger.classList.toggle('active');
        if (sideMenuHamburger) sideMenuHamburger.classList.toggle('active');
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
    
    if (hamburger && sideMenu && overlay) {
        hamburger.addEventListener('click', toggleMenu);
        if (sideMenuHamburger) sideMenuHamburger.addEventListener('click', toggleMenu);
        overlay.addEventListener('click', toggleMenu);
        
        document.querySelectorAll('.mobile-nav-links a, .mobile-menu-cart').forEach(function(link) {
            link.addEventListener('click', toggleMenu);
        });
        
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768 && sideMenu.classList.contains('active')) {
                toggleMenu();
            }
        });
    }
    
    // ========== MOBILE SEARCH ==========
    var mobileSearchIcon = document.getElementById('mobileSearchIcon');
    var mobileSearchContainer = document.getElementById('mobileSearchContainer');
    
    if (mobileSearchIcon && mobileSearchContainer) {
        mobileSearchIcon.addEventListener('click', function(e) {
            e.stopPropagation();
            mobileSearchContainer.classList.toggle('active');
            
            if (mobileSearchContainer.classList.contains('active')) {
                var searchInput = document.getElementById('mobile-search');
                if (searchInput) {
                    setTimeout(function() { searchInput.focus(); }, 50);
                }
            }
        });
    }
    
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
    document.querySelectorAll('.toggle-password').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var targetId = this.getAttribute('data-target');
            var input = document.getElementById(targetId);
            
            if (input) {
                var currentType = input.getAttribute('type');
                var newType = currentType === 'password' ? 'text' : 'password';
                input.setAttribute('type', newType);
                
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
    var registerModal = document.getElementById('register-modal');
    var loginModal = document.getElementById('login-modal');
    var registerBtns = document.querySelectorAll('#register, .register-link-mobile');
    var loginBtns = document.querySelectorAll('#login, .login-link-mobile');
    var registerClose = document.querySelector('#register-modal .btn-close');
    var loginClose = document.querySelector('#login-modal .btn-close');
    
    function openModal(modal) {
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }
    
    function closeModal(modal) {
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
    
    if (registerModal && registerBtns.length) {
        registerBtns.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                if (loginModal && loginModal.classList.contains('active')) {
                    closeModal(loginModal);
                }
                openModal(registerModal);
            });
        });
        
        if (registerClose) {
            registerClose.addEventListener('click', function() { 
                closeModal(registerModal); 
            });
        }
        
        registerModal.addEventListener('click', function(e) {
            if (e.target === registerModal) {
                closeModal(registerModal);
            }
        });
        
        var loginLinkInRegister = document.querySelector('#register-modal .login-link a');
        if (loginLinkInRegister) {
            loginLinkInRegister.addEventListener('click', function(e) {
                e.preventDefault();
                closeModal(registerModal);
                setTimeout(function() { 
                    openModal(loginModal); 
                }, 400);
            });
        }
    }
    
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
    
    // ========== SELL LINK HANDLING ==========
    var sellLink = document.getElementById('sell-link');
    var sellLinkMobile = document.querySelector('.sell-link-mobile');

    function handleSellLink(e) {
        // Check if user is logged in via a data attribute or global variable
        // You'll need to pass this from PHP to JavaScript
        
        if (typeof isLoggedIn !== 'undefined' && isLoggedIn === true) {
            // Logged in - check role
            if (typeof currentUserRole !== 'undefined' && currentUserRole === 'buyer') {
                e.preventDefault();
                var userConfirmed = confirm('You are currently registered as a buyer.\n\nWould you like to upgrade to a seller account? This will allow you to list and sell products on ConsuTrade.');
                
                if (userConfirmed) {
                    window.location.href = baseUrl + 'php/upgrade-to-seller.php';
                }
            }
            // If already seller, let the link work normally (go to sell.php)
        }
        // If not logged in, let the link work normally (go to sell.php)
    }

    if (sellLink) {
        sellLink.addEventListener('click', handleSellLink);
    }

    if (sellLinkMobile) {
        sellLinkMobile.addEventListener('click', handleSellLink);
    }
    
    // ========== UPGRADE BANNER BUTTON ==========
    var upgradeBtn = document.getElementById('upgrade-to-seller-btn');
    if (upgradeBtn) {
        upgradeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            var userConfirmed = confirm('Are you sure you want to upgrade to a seller account?\n\nYou will be able to list and sell your products on ConsuTrade.');
            
            if (userConfirmed) {
                window.location.href = baseUrl + 'php/upgrade-to-seller.php';
            }
        });
    }
    
    // ========== AUTO-CLOSE MODAL ON SUCCESS ==========
    var flashMessage = document.querySelector('.flash');
    if (flashMessage) {
        if (registerModal && registerModal.classList.contains('active')) {
            closeModal(registerModal);
        }
        if (loginModal && loginModal.classList.contains('active')) {
            closeModal(loginModal);
        }
        
        setTimeout(function() {
            flashMessage.style.opacity = '0';
            flashMessage.style.transition = 'opacity 0.5s ease';
            setTimeout(function() {
                if (flashMessage) flashMessage.remove();
            }, 500);
        }, 5000);
    }

    // ========== USER DROPDOWN TOGGLE ==========
    var userInfo = document.querySelector('.user-info');
    var desktopDropdownMenu = document.getElementById('desktopDropdownMenu');

    if (userInfo && desktopDropdownMenu) {
        userInfo.addEventListener('click', function(e) {
            e.stopPropagation();
            desktopDropdownMenu.classList.toggle('show');
        });        
        
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
        
        document.addEventListener('click', function(e) {
            if (mobileDropdownToggle && mobileDropdownMenu) {
                if (!mobileDropdownToggle.contains(e.target) && !mobileDropdownMenu.contains(e.target)) {
                    mobileDropdownMenu.classList.remove('show');
                }
            }
        }); 
    }
    
    // Run these when page loads
    updateCartCount();
    loadCart();
});