/*
 * ConsuTrade - Products JavaScript File
 * Author: Kamogelo Phale
 * 
 * Handles product listings, filtering, pagination, and product details
 * Relies on main.js for escapeHtml, toast notifications, and addToCart
 */

// ========== HELPER FUNCTIONS ==========
function getSellerAvatar(profileImage) {
    if (profileImage && profileImage !== '') {
        if (profileImage.startsWith('http')) return profileImage;
        return baseUrl + profileImage;
    }
    return baseUrl + 'images/icons/profile-svgrepo-com.svg';
}

// ========== PRODUCT LISTINGS PAGE ==========
let currentPage = 1;
let currentFilters = {};
let currentSort = 'newest';
let totalPages = 1;

function setupProductEventListeners() {
    $('#mobileFilterBtn').on('click', function() {
        $('#filterSidebar').toggleClass('active');
    });

    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        collectFilters();
        currentPage = 1;
        loadProducts();
        if ($(window).width() <= 768) {
            $('#filterSidebar').removeClass('active');
        }
    });

    $('#resetFilters').on('click', function() {
        $('#filterForm')[0].reset();
        currentFilters = {};
        currentPage = 1;
        loadProducts();
    });

    $('#sortBy').on('change', function() {
        currentSort = $(this).val();
        currentPage = 1;
        loadProducts();
    });
}

function collectFilters() {
    let categories = [];
    $('input[name="category[]"]:checked').each(function() {
        categories.push($(this).val());
    });
    
    currentFilters = {
        categories: categories,
        price_range: $('input[name="price_range"]:checked').val() || '',
        location: $('#search-location').val() || ''
    };
}

function loadProducts() {
    let params = new URLSearchParams();
    params.append('page', currentPage);
    params.append('sort', currentSort);
    
    if (currentFilters.categories && currentFilters.categories.length > 0) {
        params.append('categories', currentFilters.categories.join(','));
    }
    if (currentFilters.price_range) params.append('price_range', currentFilters.price_range);
    if (currentFilters.location) params.append('location', currentFilters.location);
    
    $('#products-grid').html('<div class="loading-spinner">Loading products...</div>');
    
    $.get(`${baseUrl}php/endpoints/get-products.php?${params.toString()}`, function(data) {
        if (data.success && data.products && data.products.length > 0) {
            displayProducts(data.products);
            totalPages = data.total_pages || 1;
            displayPagination();
        } else {
            $('#products-grid').html('<div class="no-products"><p>No products found.</p></div>');
        }
    }).fail(function() {
        $('#products-grid').html('<p class="error">Error loading products. Please try again.</p>');
    });
}

function displayProducts(products) {
    let $grid = $('#products-grid');
    $grid.empty();
    
    $.each(products, function(index, product) {
        let imagePath = product.display_image || product.image || product.image_url;
        if (imagePath && !imagePath.startsWith('http') && !imagePath.startsWith('/')) {
            imagePath = baseUrl + imagePath;
        }
        
        let verifiedBadge = product.is_verified ? 
            `<div class="verified-badge-card"><img src="${baseUrl}images/icons/verified-svgrepo-com.svg" width="14" height="14"><span>Verified Seller</span></div>` : 
            `<div class="unverified-badge-card"><img src="${baseUrl}images/icons/not-verified-svgrepo-com.svg" width="14" height="14"><span>Unverified</span></div>`;
        
        let conditionClass = '';
        let conditionText = product.condition || 'Good';
        if (conditionText === 'New') conditionClass = 'new';
        else if (conditionText === 'Like New') conditionClass = 'like-new';
        else if (conditionText === 'Good') conditionClass = 'good';
        else if (conditionText === 'Fair') conditionClass = 'fair';
        
        let stockQuantity = product.stock_quantity || 1;
        let isOutOfStock = stockQuantity <= 0;
        let stockBadge = isOutOfStock ? 
            '<div class="out-of-stock-badge-card">Out of Stock</div>' : 
            (stockQuantity <= 5 ? `<div class="low-stock-badge-card">Only ${stockQuantity} left</div>` : '');
        
        let isOwnProduct = (typeof currentUserRole !== 'undefined' && currentUserRole === 'seller' && product.seller_id == currentUserId);
        let addToCartButton = '';
        
        if (!isOwnProduct && !isOutOfStock) {
            addToCartButton = `<button class="add-to-cart-btn" onclick="event.stopPropagation(); addToCart(${product.id}, '${escapeHtml(product.name).replace(/'/g, "\\'")}', ${product.price})">Add to Cart</button>`;
        } else if (isOutOfStock) {
            addToCartButton = '<button class="out-of-stock-btn" disabled>Out of Stock</button>';
        } else if (isOwnProduct) {
            addToCartButton = '<button class="own-product-btn" disabled>Your Product</button>';
        }
        
        let $card = $('<div>').addClass('prod-card').css('cursor', 'pointer');
        $card.on('click', function() {
            window.location.href = `${baseUrl}product-details.php?id=${product.id}`;
        });
        
        $card.html(`
            <div class="img-container">
                <img src="${imagePath || baseUrl + 'images/default-product.png'}" alt="${escapeHtml(product.name)}" onerror="this.src='${baseUrl}images/default-product.png'">
                <div class="condition-badge ${conditionClass}">${conditionText}</div>
                ${stockBadge}
            </div>
            <div class="prod-info-container">
                <h3 class="prod-name">${escapeHtml(product.name)}</h3>
                <p class="prod-price">R ${parseFloat(product.price).toFixed(2)}</p>
                <div class="seller-info">
                    <div class="seller-avatar">
                        <img src="${getSellerAvatar(product.profile_image)}" alt="${escapeHtml(product.seller_name)}" onerror="this.src='${baseUrl}images/icons/profile-svgrepo-com.svg'">
                    </div>
                    <div class="seller-details">
                        <p class="seller-name">${escapeHtml(product.seller_name)}</p>
                        <p class="location">
                            <img src="${baseUrl}images/icons/pin-location-svgrepo-com.svg" width="10" height="10" alt="location">
                            ${escapeHtml(product.location || 'South Africa')}
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
        `);
        $grid.append($card);
    });
}

function displayPagination() {
    let $pagination = $('#pagination');
    if (!$pagination.length || totalPages <= 1) {
        $pagination.empty();
        return;
    }
    
    let html = '';
    if (currentPage > 1) html += `<button class="page-btn" onclick="goToPage(${currentPage - 1})">← Previous</button>`;
    
    for (let i = 1; i <= totalPages; i++) {
        if (i === currentPage) {
            html += `<button class="page-btn active" disabled>${i}</button>`;
        } else if (Math.abs(i - currentPage) <= 2 || i === 1 || i === totalPages) {
            html += `<button class="page-btn" onclick="goToPage(${i})">${i}</button>`;
        } else if (Math.abs(i - currentPage) === 3) {
            html += '<span class="page-dots">...</span>';
        }
    }
    
    if (currentPage < totalPages) html += `<button class="page-btn" onclick="goToPage(${currentPage + 1})">Next →</button>`;
    $pagination.html(html);
}

function goToPage(page) {
    currentPage = page;
    loadProducts();
    $('html, body').animate({ scrollTop: 0 }, 'smooth');
}

function resetFilters() {
    $('#filterForm')[0].reset();
    currentFilters = {};
    currentPage = 1;
    loadProducts();
}

// ========== PRODUCT DETAILS PAGE FUNCTIONS ==========
function loadProductDetails(id) {
    let $container = $('.product-details-container');
    if (!$container.length) return;
    
    $('#product-details-content').html('<div class="loading-spinner">Loading product details...</div>');
    
    $.get(`${baseUrl}php/endpoints/get-product.php?id=${id}`, function(data) {
        if (data.success && data.product) {
            displayProductDetails(data.product);
        } else {
            showError(data.error || 'Product not found.');
        }
    }).fail(function() {
        showError('Unable to load product.');
    });
}

function showError(message) {
    let $container = $('.product-details-container');
    if (!$container.length) return;
    
    $container.html(`
        <div class="product-error-container">
            <h2 class="product-error-title">Product Not Found</h2>
            <p class="product-error-message-text">${escapeHtml(message)}</p>
            <button class="product-error-action-btn" onclick="window.location.href='${baseUrl}product-listings.php'">Browse Products</button>
        </div>
    `);
}

function displayProductDetails(product) {
    let $container = $('#product-details-content');
    if (!$container.length) return;
    
    // Helper function to fix image URLs
    function fixImageUrl(url) {
        if (!url || url === '') return `${baseUrl}images/default-product.png`;
        if (url.startsWith('http://') || url.startsWith('https://')) {
            return url;
        }
        
        let parts = url.split('/');
        let filename = parts[parts.length - 1];
        
        if (url.includes('/products/')) {
            return `${baseUrl}uploads/products/${filename}`;
        } else if (url.includes('/profiles/')) {
            return `${baseUrl}uploads/profiles/${filename}`;
        }
        
        let uploadsIndex = url.indexOf('uploads/');
        if (uploadsIndex !== -1) {
            return baseUrl + url.substring(uploadsIndex);
        }
        
        return `${baseUrl}images/default-product.png`;
    }
    
    // Get main image
    let mainImage = fixImageUrl(product.image_url);
    let galleryImages = product.gallery_images || [];
    
    // Build thumbnails: start with main image
    let thumbnails = [mainImage];
    
    // Add up to 3 gallery images (remaining spots)
    for (let i = 0; i < galleryImages.length && thumbnails.length < 4; i++) {
        let galleryUrl = fixImageUrl(galleryImages[i]);
        if (galleryUrl !== mainImage) {
            thumbnails.push(galleryUrl);
        }
    }
    
    // Fill remaining spots (up to 3) with placeholders
    while (thumbnails.length < 4) {
        thumbnails.push(`${baseUrl}images/default-product.png`);
    }
    
    // Build thumbnails HTML
    let galleryHtml = '';
    for (let i = 0; i < thumbnails.length; i++) {
        let isActive = (i === 0) ? 'active' : '';
        let thumbUrl = thumbnails[i];
        
        galleryHtml += `
            <div class="small-img ${isActive}" data-image-path="${thumbUrl}">
                <img src="${thumbUrl}" alt="Thumbnail ${i+1}" onerror="this.src='${baseUrl}images/default-product.png'">
            </div>
        `;
    }
    
    // Stock status with proper styling
    let isOutOfStock = product.stock_quantity <= 0;
    let isLowStock = product.stock_quantity > 0 && product.stock_quantity <= 5;
    let stockHtml = '';
    
    if (isOutOfStock) {
        stockHtml = '<div class="stock-status out-of-stock"><span class="stock-icon">✕</span> Out of Stock</div>';
    } else if (isLowStock) {
        stockHtml = `<div class="stock-status low-stock"><span class="stock-icon">⚠</span> Only ${product.stock_quantity} left in stock!</div>`;
    } else {
        stockHtml = `<div class="stock-status in-stock"><span class="stock-icon">✓</span> In Stock (${product.stock_quantity} available)</div>`;
    }
    
    let starsHtml = '';
    let avgRating = parseFloat(product.avg_rating) || 0;
    for (let i = 1; i <= 5; i++) {
        starsHtml += (i <= avgRating) ? '<span class="star">★</span>' : '<span class="star empty">★</span>';
    }
    
    let actionButtonsHtml = '';
    if (!isOutOfStock) {
        let escapedName = escapeHtml(product.name).replace(/'/g, "\\'");
        actionButtonsHtml = `
            <button class="cart-btn" onclick="addToCart(${product.id}, '${escapedName}', ${product.price})">
                Add to Cart
            </button>
            <button class="buy-btn" onclick="buyNow(${product.id}, '${escapedName}', ${product.price})">Buy Now</button>
        `;
    } else {
        actionButtonsHtml = '<button class="cart-btn out-of-stock-btn" disabled>Out of Stock</button>';
    }
    
    let sellerImage = fixImageUrl(product.seller_profile_image);
    
    $container.html(`
        <div class="breadcrumb">
            <a href="${baseUrl}index.php">Home</a> > 
            <a href="${baseUrl}product-listings.php">Products</a> > 
            <span>${escapeHtml(product.name)}</span>
        </div>
        
        <div class="top-items">
            <div class="product-imgs">
                <div class="main-img">
                    <img src="${mainImage}" alt="${escapeHtml(product.name)}" id="main-product-image" onerror="this.src='${baseUrl}images/default-product.png'">
                </div>
                <div class="smaller-imgs" id="gallery-container">
                    ${galleryHtml}
                </div>
            </div>
            
            <div class="product-info">
                <h1 class="details-prod-name">${escapeHtml(product.name)}</h1>
                <p class="details-price">R ${parseFloat(product.price).toFixed(2)}</p>
                <div class="cat-badge">
                    <span class="cat-name">${escapeHtml(product.category_name || 'General')}</span>
                </div>
                ${stockHtml}
                <div class="description">
                    <p class="sub-head">Description</p>
                    <p class="des">${escapeHtml(product.description || 'No description available.')}</p>
                </div>
                <div class="con-loc">
                    ${product.condition ? `<p><strong>Condition:</strong> ${escapeHtml(product.condition)}</p>` : ''}
                    ${product.location ? `<p><strong>Location:</strong> ${escapeHtml(product.location)}</p>` : ''}
                </div>
            </div>
        </div>
        
        <section class="review">
            <div class="rev-container">
                <div class="seller-profile">
                    <div class="profile-pic">
                        <img src="${sellerImage}" width="40" height="40" alt="${escapeHtml(product.seller_name)}" onerror="this.src='${baseUrl}images/icons/profile-svgrepo-com.svg'">
                    </div>
                    <p class="seller-name">${escapeHtml(product.seller_name)}</p>
                </div>
                <div class="verification">
                    ${product.is_verified ? 
                        `<div class="verified-badge"><img src="${baseUrl}images/icons/verified-svgrepo-com.svg" width="20" height="20"><p>Verified Seller</p></div>` : 
                        `<div class="not-verified-badge"><img src="${baseUrl}images/icons/not-verified-svgrepo-com.svg" width="20" height="20"><p>Not Verified</p></div>`}
                </div>
                <div class="star-reviews">
                    <h1>Seller Reviews</h1>
                    ${starsHtml}
                    <p>Rating: ${avgRating.toFixed(1)}/5 (${product.review_count || 0} reviews)</p>
                </div>
                <button class="view-profile" onclick="window.location.href='${baseUrl}seller-profile-public.php?seller_id=${product.seller_id}'">
                    View Seller Profile
                </button>
            </div>
        </section>

        <div class="actions">
            <div class="actions-card">
                <div class="action-btns">
                    ${actionButtonsHtml}
                </div>
                <div class="payfast-badge">
                    <img src="${baseUrl}images/icons/Payfast logo.svg" alt="PayFast">
                    <span>Secure payments by PayFast</span>
                </div>
            </div>
        </div>
    `);
    
    // Thumbnail click handler
    $('.small-img').on('click', function() {
        let newImagePath = $(this).data('image-path');
        $('#main-product-image').attr('src', newImagePath);
        $('.small-img').removeClass('active');
        $(this).addClass('active');
    });
}

function buyNow(productId, productName, productPrice) {
    addToCart(productId, productName, productPrice);
    window.location.href = `${baseUrl}checkout.php`;
}

// ========== INITIALIZE ==========
$(function() {
    if ($('#products-grid').length) {
        loadProducts();
        setupProductEventListeners();
    }
    if ($('.product-details-container').length) {
        let productId = $('.product-details-container').data('product-id');
        if (productId > 0) {
            loadProductDetails(productId);
        }
    }
});