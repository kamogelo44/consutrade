/*
 * ConsuTrade - Products JavaScript File
 * Author: Kamogelo Phale
 * 
 * This file handles:
 * - Product listings display
 * - Filtering and sorting products
 * - Pagination
 * - Product card interactions
 * - Product details page
 */

// Base URL for all fetch requests
var baseUrl = '/www/consutrade/';

// ========== PRODUCT LISTINGS PAGE ==========
// Handles loading, filtering, sorting, and displaying products

let currentPage = 1;
let currentFilters = {};
let currentSort = 'newest';
let totalPages = 1;

// Initialize product listings when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Only run if we're on a page with products grid
    if (document.getElementById('products-grid')) {
        loadProducts();
        setupProductEventListeners();
    }
    
    // Only run if we're on product details page
    if (document.getElementById('product-details-container')) {
        var productIdElement = document.getElementById('product-details-container');
        if (productIdElement && productIdElement.getAttribute('data-product-id')) {
            var productId = parseInt(productIdElement.getAttribute('data-product-id'));
            if (productId > 0) {
                loadProductDetails(productId);
            }
        }
    }
});

// ========== PRODUCT LISTINGS FUNCTIONS ==========

// Set up all event listeners for filters, sorting, and mobile toggle
function setupProductEventListeners() {
    // Mobile filter button toggle
    var filterBtn = document.getElementById('mobileFilterBtn');
    var filterSidebar = document.getElementById('filterSidebar');
    if (filterBtn && filterSidebar) {
        filterBtn.addEventListener('click', function() {
            filterSidebar.classList.toggle('active');
        });
    }

    // Filter form submission
    var filterForm = document.getElementById('filterForm');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            collectFilters();
            currentPage = 1;
            loadProducts();
            // Close filter sidebar on mobile after applying
            if (window.innerWidth <= 768 && filterSidebar) {
                filterSidebar.classList.remove('active');
            }
        });
    }

    // Reset filters button
    var resetBtn = document.getElementById('resetFilters');
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            filterForm.reset();
            currentFilters = {};
            currentPage = 1;
            loadProducts();
        });
    }

    // Sort options change
    var sortSelect = document.getElementById('sortBy');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            currentSort = this.value;
            currentPage = 1;
            loadProducts();
        });
    }
}

// Collect all filter values from the form
function collectFilters() {
    var categories = [];
    var categoryChecks = document.querySelectorAll('input[name="category[]"]:checked');
    categoryChecks.forEach(function(checkbox) {
        categories.push(checkbox.value);
    });
    
    var priceRange = document.querySelector('input[name="price_range"]:checked');
    var location = document.getElementById('search-location') ? document.getElementById('search-location').value : '';
    
    currentFilters = {
        categories: categories,
        price_range: priceRange ? priceRange.value : '',
        location: location
    };
}

// Load products from server with current filters, sort, and page
function loadProducts() {
    var params = new URLSearchParams();
    params.append('page', currentPage);
    params.append('sort', currentSort);
    
    if (currentFilters.categories && currentFilters.categories.length > 0) {
        params.append('categories', currentFilters.categories.join(','));
    }
    if (currentFilters.price_range) {
        params.append('price_range', currentFilters.price_range);
    }
    if (currentFilters.location) {
        params.append('location', currentFilters.location);
    }
    
    fetch(baseUrl + 'php/get-products.php?' + params.toString())
        .then(function(response) { return response.json(); })
        .then(function(data) {
            var grid = document.getElementById('products-grid');
            
            if (data.success && data.products && data.products.length > 0) {
                displayProducts(data.products);
                totalPages = data.total_pages || 1;
                displayPagination();
            } else {
                grid.innerHTML = '<div class="no-products"><p>No products found matching your criteria.</p><button onclick="resetFilters()" class="reset-btn">Clear Filters</button></div>';
            }
        })
        .catch(function() {
            document.getElementById('products-grid').innerHTML = '<p class="error">Error loading products. Please try again.</p>';
        });
}

// Display products in the grid
function displayProducts(products) {
    var grid = document.getElementById('products-grid');
    grid.innerHTML = '';
    
    for (var i = 0; i < products.length; i++) {
        var product = products[i];

        // Determine verification badge
        var verifiedBadge = product.is_verified ? 
            '<div class="verified-badge-card"><img src="' + baseUrl + 'images/icons/verified-svgrepo-com.svg" width="14px" height="14px" alt="Verified"><span>Verified Seller</span></div>' : 
            '<div class="unverified-badge-card"><img src="' + baseUrl + 'images/icons/not-verified-svgrepo-com.svg" width="14px" height="14px" alt="Not Verified"><span>Unverified</span></div>';
        
        // Determine condition badge class and text
        var conditionClass = '';
        var conditionText = product.condition || 'Good';
        if (conditionText === 'New') conditionClass = 'new';
        else if (conditionText === 'Like New') conditionClass = 'like-new';
        else if (conditionText === 'Good') conditionClass = 'good';
        else if (conditionText === 'Fair') conditionClass = 'fair';
        
        // Fix image path
        var imagePath = product.image;
        if (imagePath && !imagePath.startsWith('http') && !imagePath.startsWith('/')) {
            imagePath = baseUrl + imagePath;
        }
        
        // Check if this is the seller's own product
        var isOwnProduct = (typeof currentUserRole !== 'undefined' && currentUserRole === 'seller' && product.seller_id == currentUserId);
        var addToCartButton = '';

        if (!isOwnProduct) {
            addToCartButton = '<button class="add-to-cart-btn" onclick="event.stopPropagation(); addToCart(' + product.id + ', \'' + escapeHtml(product.name).replace(/'/g, "\\'") + '\', ' + product.price + ')">' +
                '<img src="' + baseUrl + 'images/icons/shopping-cart-01-svgrepo-com.svg" alt="Cart">' +
                'Add to Cart' +
                '</button>';
        } else {
            addToCartButton = '<button class="own-product-btn" disabled style="background-color: #ccc; cursor: not-allowed; width: 100%; padding: 10px; border-radius: 8px; border: none;">Your Product</button>';
        }
        
        // Create the product card
        var card = document.createElement('div');
        card.className = 'prod-card';
        card.style.cursor = 'pointer';
        card.addEventListener('click', (function(id) {
            return function() {
                window.location.href = baseUrl + 'product-details.php?id=' + id;
            };
        })(product.id));
        
        card.innerHTML = `
            <div class="img-container">
                <img src="${imagePath}" alt="${escapeHtml(product.name)}" onerror="this.src='${baseUrl}images/default-product.png'">
                <div class="condition-badge ${conditionClass}">${conditionText}</div>
            </div>
            <div class="prod-info-container">
                <h3 class="prod-name">${escapeHtml(product.name)}</h3>
                <p class="prod-price">R ${parseFloat(product.price).toFixed(2)}</p>
                <div class="seller-info">
                    <div class="seller-avatar">
                        <img src="${baseUrl}images/icons/profile-svgrepo-com.svg" alt="Seller">
                    </div>
                    <div class="seller-details">
                        <p class="seller-name">${escapeHtml(product.seller_name)}</p>
                        <p class="location">
                            <img src="${baseUrl}images/icons/pin-location-svgrepo-com.svg" width="10px" height="10px" alt="location">
                            ${escapeHtml(product.location)}
                        </p>
                    </div>
                    ${verifiedBadge}
                </div>
                ${addToCartButton}
                <div class="payment-badge">
                    <span>Secure payment via</span>
                    <img src="${baseUrl}images/icons/Payfast logo.svg" alt="PayFast">
                </div>
            </div>
        `;
        grid.appendChild(card);
    }
}

// Display pagination controls
function displayPagination() {
    var paginationDiv = document.getElementById('pagination');
    if (!paginationDiv || totalPages <= 1) {
        if (paginationDiv) paginationDiv.innerHTML = '';
        return;
    }
    
    var html = '';
    
    // Previous button
    if (currentPage > 1) {
        html += '<button class="page-btn" onclick="goToPage(' + (currentPage - 1) + ')">← Previous</button>';
    }
    
    // Page numbers
    for (var i = 1; i <= totalPages; i++) {
        if (i === currentPage) {
            html += '<button class="page-btn active" disabled>' + i + '</button>';
        } else if (Math.abs(i - currentPage) <= 2 || i === 1 || i === totalPages) {
            html += '<button class="page-btn" onclick="goToPage(' + i + ')">' + i + '</button>';
        } else if (Math.abs(i - currentPage) === 3) {
            html += '<span class="page-dots">...</span>';
        }
    }
    
    // Next button
    if (currentPage < totalPages) {
        html += '<button class="page-btn" onclick="goToPage(' + (currentPage + 1) + ')">Next →</button>';
    }
    
    paginationDiv.innerHTML = html;
}

// Navigate to specific page
function goToPage(page) {
    currentPage = page;
    loadProducts();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Reset all filters and reload products
function resetFilters() {
    var filterForm = document.getElementById('filterForm');
    if (filterForm) filterForm.reset();
    currentFilters = {};
    currentPage = 1;
    loadProducts();
}

// ========== PRODUCT DETAILS PAGE FUNCTIONS ==========

function loadProductDetails(id) {
    var container = document.getElementById('product-details-container');
    if (!container) return;
    
    container.innerHTML = '<div class="loading-spinner" style="text-align: center; padding: 60px;">Loading product details...</div>';
    
    fetch(baseUrl + 'php/get-product.php?id=' + id)
        .then(function(response) { 
            if (!response.ok) {
                throw new Error('Server error: ' + response.status);
            }
            return response.json(); 
        })
        .then(function(data) {
            if (data.success && data.product) {
                displayProductDetails(data.product);
            } else {
                showError(data.error || 'Product not found.');
            }
        })
        .catch(function() {
            showError('Unable to load product. Please try again later.');
        });
}

function showError(message) {
    var container = document.getElementById('product-details-container');
    if (!container) return;
    
    container.innerHTML = `
        <div class="error-container" style="text-align: center; padding: 80px 20px; max-width: 500px; margin: 0 auto;">
            <img src="${baseUrl}images/icons/shopping-cart-01-svgrepo-com.svg" width="64px" height="64px" alt="Error" style="opacity: 0.5; margin-bottom: 20px;">
            <h2 style="color: #f44336; margin-bottom: 10px; font-size: 24px;">Oops!</h2>
            <p style="color: #666;">${escapeHtml(message)}</p>
            <button onclick="window.location.href='${baseUrl}product-listings.php'" style="margin-top: 20px; padding: 10px 24px; background-color: #FF6B00; color: white; border: none; border-radius: 8px; cursor: pointer;">Browse Products</button>
        </div>
    `;
}

function displayProductDetails(product) {
    var container = document.getElementById('product-details-container');
    if (!container) return;
    
    // Fix image paths
    var mainImage = product.image;
    if (mainImage && !mainImage.startsWith('http') && !mainImage.startsWith('/')) {
        mainImage = baseUrl + mainImage;
    }
    
    // Parse gallery images from JSON
    var galleryImages = [];
    if (product.gallery_images) {
        try {
            galleryImages = JSON.parse(product.gallery_images);
        } catch(e) {
            galleryImages = [];
        }
    }
    
    // Build gallery thumbnails HTML with active class
    var galleryHtml = '';
    for (var i = 0; i < 4; i++) {
        var thumbPath = galleryImages[i] || null;
        var activeClass = (i === 0 && !thumbPath) ? 'active' : '';
        
        if (thumbPath) {
            if (thumbPath && !thumbPath.startsWith('http') && !thumbPath.startsWith('/')) {
                thumbPath = baseUrl + thumbPath;
            }
            activeClass = (i === 0) ? 'active' : '';
            galleryHtml += `
                <div class="small-img ${activeClass}" data-image-index="${i}" onclick="changeMainImage('${thumbPath}', ${i})">
                    <img src="${thumbPath}" alt="Product thumbnail" onerror="this.src='${baseUrl}images/default-product.png'">
                </div>
            `;
        } else {
            galleryHtml += `
                <div class="small-img empty-thumb">
                    <img src="${baseUrl}images/default-product.png" alt="No image">
                </div>
            `;
        }
    }
    
    // Determine verification badge
    var verificationBadge = product.is_verified ? 
        '<div class="verified-badge"><img src="' + baseUrl + 'images/icons/verified-svgrepo-com.svg" width="20px" height="20px" alt="verification"><p>Verified Seller</p></div>' : 
        '<div class="not-verified-badge"><img src="' + baseUrl + 'images/icons/not-verified-svgrepo-com.svg" width="20px" height="20px" alt="not-verified"><p>Not Verified Seller</p></div>';
    
    // Build stars
    var rating = product.avg_rating || 0;
    var starsHtml = '';
    for (var i = 1; i <= 5; i++) {
        if (i <= rating) {
            starsHtml += '<span class="star">★</span>';
        } else {
            starsHtml += '<span class="star empty">★</span>';
        }
    }
    
    // Build condition HTML
    var conditionHtml = '';
    if (product.condition && product.condition !== '') {
        conditionHtml = `<p class="sub-head">Condition: <span class="condition">${escapeHtml(product.condition)}</span></p>`;
    }
    
    // Build location HTML
    var locationHtml = '';
    if (product.location) {
        locationHtml = `<p class="sub-head">Location: <span class="city">${escapeHtml(product.location)}</span></p>`;
    }
    
    // Check if this is the seller's own product
    var isOwnProduct = (typeof currentUserRole !== 'undefined' && currentUserRole === 'seller' && product.seller_id == currentUserId);
    var actionButtonsHtml = '';
    
    if (!isOwnProduct) {
        actionButtonsHtml = `
            <button class="cart-btn" onclick="addToCart(${product.id}, '${escapeHtml(product.name).replace(/'/g, "\\'")}', ${product.price})">
                <img src="${baseUrl}images/icons/shopping-cart-01-svgrepo-com.svg" width="24px" height="24px" alt="Cart">
                Add to Cart
            </button>
            <button class="buy-btn" onclick="buyNow(${product.id})">Buy Now</button>
        `;
    } else {
        actionButtonsHtml = `
            <div class="own-product-message">
                <p>You cannot purchase your own product.</p>
            </div>
        `;
    }
    
    container.innerHTML = `
        <div class="breadcrumb">
            <a href="${baseUrl}index.php">Home</a>
            <span> > </span>
            <a href="${baseUrl}product-listings.php">Product Listings</a>
            <span> > </span>
            <span>${escapeHtml(product.name)}</span>
        </div>
        
        <div class="top-items">
            <div class="product-imgs">
                <div class="main-img" id="main-image-container">
                    <img src="${mainImage}" alt="${escapeHtml(product.name)}" onerror="this.src='${baseUrl}images/default-product.png'" id="main-product-image">
                </div>
                <div class="smaller-imgs" id="gallery-container">
                    ${galleryHtml}
                </div>
            </div>
            
            <div class="product-info">
                <div class="price-desc">
                    <h1 class="details-prod-name">${escapeHtml(product.name)}</h1>
                    <p class="details-price">R ${parseFloat(product.price).toFixed(2)}</p>
                    <div class="cat-badge">
                        <p class="cat-name">${escapeHtml(product.category_name || 'General')}</p>
                    </div>
                </div>
                
                <div class="description">
                    <p class="sub-head">Description</p>
                    <p class="des">${escapeHtml(product.description || 'No description available.')}</p>
                </div>
                
                <div class="con-loc">
                    ${conditionHtml}
                    ${locationHtml}
                </div>
            </div>
        </div>
        
        <section class="review">
            <div class="rev-container">
                <div class="seller-profile">
                    <div class="profile-pic">
                        <img src="${baseUrl}images/icons/profile-svgrepo-com.svg" width="24px" height="24px" alt="Seller Profile Picture">
                    </div>
                    <p class="seller-name">${escapeHtml(product.seller_name)}</p>
                </div>
                <div class="verification">
                    ${verificationBadge}
                </div>
                <div class="star-reviews">
                    <h1>Seller Reviews</h1>
                    ${starsHtml}
                    <p id="output">Rating: ${rating}/5 (${product.review_count || 0} reviews)</p>
                </div>
                <button class="view-profile" onclick="window.location.href='${baseUrl}profile.php?seller_id=${product.seller_id}'">
                    View Seller Profile
                </button>
            </div>
        </section>

        <div class="actions">
            <div class="actions-card">
                <div class="avail">
                    <p><span class="num-avail">In Stock</span></p>
                </div>
                <div class="action-btns">
                    ${actionButtonsHtml}
                </div>
            </div>
        </div>
    `;
    
    // Add star click handler
    var stars = document.querySelectorAll('.star');
    stars.forEach(function(star) {
        star.addEventListener('click', function() {
            if (typeof isLoggedIn !== 'undefined' && isLoggedIn) {
                window.location.href = baseUrl + 'my-orders.php';
            } else {
                alert('Please login to leave a review');
                window.location.href = baseUrl + 'index.php';
            }
        });
    });
}

function changeMainImage(imagePath, selectedIndex) {
    var mainImage = document.getElementById('main-product-image');
    if (mainImage) {
        mainImage.style.opacity = '0.5';
        mainImage.src = imagePath;
        mainImage.onload = function() {
            mainImage.style.opacity = '1';
        };
        mainImage.onerror = function() {
            mainImage.src = baseUrl + 'images/default-product.png';
            mainImage.style.opacity = '1';
        };
    }
    
    // Update active class on thumbnails
    var thumbnails = document.querySelectorAll('.small-img');
    for (var i = 0; i < thumbnails.length; i++) {
        thumbnails[i].classList.remove('active');
        if (i == selectedIndex) {
            thumbnails[i].classList.add('active');
        }
    }
}

function buyNow(productId) {
    window.location.href = baseUrl + 'cart.php';
}

// Helper function to escape HTML
function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}