/*
 * ConsuTrade - Seller Dashboard JavaScript
 * Author: Kamogelo Phale
 * 
 * This file handles seller dashboard specific functionality:
 * - Load seller statistics
 * - Load seller products
 * - Load recent orders
 * - Delete products
 * - Edit products
 * - Mobile sidebar toggle with span animation
 */

// ========== GLOBAL FUNCTIONS for onclick handlers ==========
// These need to be global so they can be called from dynamically created buttons

function editProduct(productId) {
    window.location.href = 'edit-product.php?id=' + productId;
}

function deleteProduct(productId) {
    if (confirm('Are you sure you want to delete this product?')) {
        fetch('/www/consutrade/php/delete-product.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ product_id: productId })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                loadSellerProducts();
                alert('Product deleted successfully!');
            } else {
                alert('Error deleting product: ' + data.message);
            }
        })
        .catch(function(error) {
            console.log('Error:', error);
            alert('Something went wrong');
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    
    // ========== MOBILE SIDEBAR TOGGLE with span animation ==========
    var hamburger = document.getElementById('sellerHamburger');
    var sideMenu = document.getElementById('sellerSideMenu');
    var overlay = document.getElementById('sellerMenuOverlay');
    var closeBtn = document.getElementById('sellerSidebarClose');
    
    function toggleSidebar() {
        // Toggle hamburger animation (turns into X)
        if (hamburger) hamburger.classList.toggle('active');
        
        // Toggle sidebar visibility
        if (sideMenu) sideMenu.classList.toggle('active');
        
        // Toggle overlay
        if (overlay) overlay.classList.toggle('active');
        
        // Prevent body scroll when menu is open
        if (sideMenu && sideMenu.classList.contains('active')) {
            document.body.classList.add('seller-menu-open');
            document.body.style.overflow = 'hidden';
        } else {
            document.body.classList.remove('seller-menu-open');
            document.body.style.overflow = '';
        }
    }
    
    // Open/close with hamburger button
    if (hamburger) {
        hamburger.addEventListener('click', toggleSidebar);
    }
    
    // Close with close button (X) in sidebar
    if (closeBtn) {
        closeBtn.addEventListener('click', toggleSidebar);
    }
    
    // Close with overlay click
    if (overlay) {
        overlay.addEventListener('click', toggleSidebar);
    }
    
    // Close sidebar when clicking on a link (mobile only)
    var sidebarLinks = document.querySelectorAll('.seller-sidebar-nav a, .seller-sidebar-link');
    sidebarLinks.forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                if (hamburger) hamburger.classList.remove('active');
                if (sideMenu) sideMenu.classList.remove('active');
                if (overlay) overlay.classList.remove('active');
                document.body.classList.remove('seller-menu-open');
                document.body.style.overflow = '';
            }
        });
    });
    
    // ========== LOAD DASHBOARD STATS ==========
    loadDashboardStats();
    loadSellerProducts();
    loadRecentOrders();
    
    function loadDashboardStats() {
        fetch('/www/consutrade/admin/get-stats.php')
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    var earningsElement = document.getElementById('stat-earnings');
                    var productsElement = document.getElementById('stat-products');
                    var pendingElement = document.getElementById('stat-pending');
                    
                    if (earningsElement) earningsElement.textContent = 'R' + parseFloat(data.total_earnings || 0).toFixed(2);
                    if (productsElement) productsElement.textContent = data.total_products || 0;
                    if (pendingElement) pendingElement.textContent = data.pending_orders || 0;
                }
            })
            .catch(function(error) {
                console.log('Error loading dashboard stats:', error);
            });
    }
    
    function loadSellerProducts() {
        var grid = document.getElementById('listings-grid');
        if (!grid) return;
        
        grid.innerHTML = '<div class="loading-spinner">Loading your products...</div>';
        
        fetch('/www/consutrade/php/get-seller-products.php')
            .then(function(response) { 
                if (!response.ok) {
                    throw new Error('HTTP error ' + response.status);
                }
                return response.json(); 
            })
            .then(function(data) {
                if (data.success && data.products && data.products.length > 0) {
                    displaySellerProducts(data.products);
                } else {
                    grid.innerHTML = `
                        <div class="empty-listings">
                            <p>You haven't listed any products yet.</p>
                            <button class="add-listing-btn" onclick="window.location.href='add-product.php'">+ Add Your First Product</button>
                        </div>
                    `;
                }
            })
            .catch(function(error) {
                console.log('Error loading products:', error);
                grid.innerHTML = '<p class="error">Error loading products. Please refresh the page.</p>';
            });
    }
    
    function displaySellerProducts(products) {
        var grid = document.getElementById('listings-grid');
        if (!grid) return;
        
        grid.innerHTML = '';
        
        for (var i = 0; i < products.length; i++) {
            var product = products[i];
            
            // Fix image path - prepend base URL if needed
            var imagePath = product.image;
            if (imagePath && !imagePath.startsWith('http') && !imagePath.startsWith('/')) {
                imagePath = '/www/consutrade/' + imagePath;
            }
            
            var card = document.createElement('div');
            card.className = 'listing-card';
            card.innerHTML = `
                <div class="listing-img">
                    <img src="${imagePath}" alt="${escapeHtml(product.name)}" onerror="this.src='/www/consutrade/images/default-product.png'">
                </div>
                <div class="listing-info">
                    <h3 class="listing-title">${escapeHtml(product.name)}</h3>
                    <p class="listing-price">R ${parseFloat(product.price).toFixed(2)}</p>
                    <p class="listing-status ${product.status}">${product.status}</p>
                    <div class="listing-actions">
                        <button class="edit-btn" onclick="editProduct(${product.id})">Edit</button>
                        <button class="delete-btn" onclick="deleteProduct(${product.id})">Delete</button>
                    </div>
                </div>
            `;
            grid.appendChild(card);
        }
    }
    
    function loadRecentOrders() {
        var ordersList = document.getElementById('recent-orders-list');
        if (!ordersList) return;
        
        fetch('/www/consutrade/php/get-recent-orders.php')
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success && data.orders && data.orders.length > 0) {
                    ordersList.innerHTML = '';
                    for (var i = 0; i < data.orders.length; i++) {
                        var order = data.orders[i];
                        var orderItem = document.createElement('div');
                        orderItem.className = 'order-item';
                        orderItem.innerHTML = `
                            <p class="order-id">Order #${order.id}</p>
                            <p class="order-amount">R ${parseFloat(order.total).toFixed(2)}</p>
                            <p class="order-status ${order.status}">${order.status}</p>
                        `;
                        ordersList.appendChild(orderItem);
                    }
                } else {
                    ordersList.innerHTML = '<p class="placeholder-text">No recent orders to display.</p>';
                }
            })
            .catch(function(error) {
                console.log('Error loading orders:', error);
                ordersList.innerHTML = '<p class="placeholder-text">Error loading orders.</p>';
            });
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});