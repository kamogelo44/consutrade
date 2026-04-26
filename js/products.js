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

// Helper function to get seller avatar URL
function getSellerAvatar(profileImage) {
    if (profileImage && profileImage !== '') {
        if (profileImage.startsWith('http')) {
            return profileImage;
        }
        return baseUrl + profileImage;
    }
    return baseUrl + 'images/icons/profile-svgrepo-com.svg';
}

// ========== PRODUCT LISTINGS PAGE ==========
let currentPage = 1;
let currentFilters = {};
let currentSort = 'newest';
let totalPages = 1;

// Initialize product listings when page loads
$(document).ready(function() {
    // Only run if we're on a page with products grid
    if ($('#products-grid').length) {
        loadProducts();
        setupProductEventListeners();
    }
    
    // Only run if we're on product details page
    if ($('#product-details-container').length) {
        var productId = $('#product-details-container').data('product-id');
        if (productId > 0) {
            loadProductDetails(productId);
        }
    }
});

// ========== PRODUCT LISTINGS FUNCTIONS ==========

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
    var categories = [];
    $('input[name="category[]"]:checked').each(function() {
        categories.push($(this).val());
    });
    
    var priceRange = $('input[name="price_range"]:checked').val();
    var location = $('#search-location').val() || '';
    
    currentFilters = {
        categories: categories,
        price_range: priceRange || '',
        location: location
    };
}

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
    
    $('#products-grid').html('<div class="loading-spinner">Loading products...</div>');
    
    $.get(baseUrl + 'php/get-products.php?' + params.toString(), function(data) {
        if (data.success && data.products && data.products.length > 0) {
            displayProducts(data.products);
            totalPages = data.total_pages || 1;
            displayPagination();
        } else {
            $('#products-grid').html('<div class="no-products"><p>No products found matching your criteria.</p><button onclick="resetFilters()" class="reset-btn">Clear Filters</button></div>');
        }
    }).fail(function() {
        $('#products-grid').html('<p class="error">Error loading products. Please try again.</p>');
    });
}

function displayProducts(products) {
    var $grid = $('#products-grid');
    $grid.empty();
    
    $.each(products, function(index, product) {
        var verifiedBadge = product.is_verified ? 
            '<div class="verified-badge-card"><img src="' + baseUrl + 'images/icons/verified-svgrepo-com.svg" width="14px" height="14px" alt="Verified"><span>Verified Seller</span></div>' : 
            '<div class="unverified-badge-card"><img src="' + baseUrl + 'images/icons/not-verified-svgrepo-com.svg" width="14px" height="14px" alt="Not Verified"><span>Unverified</span></div>';
        
        var conditionClass = '';
        var conditionText = product.condition || 'Good';
        if (conditionText === 'New') conditionClass = 'new';
        else if (conditionText === 'Like New') conditionClass = 'like-new';
        else if (conditionText === 'Good') conditionClass = 'good';
        else if (conditionText === 'Fair') conditionClass = 'fair';
        
        var imagePath = product.image;
        if (imagePath && !imagePath.startsWith('http') && !imagePath.startsWith('/')) {
            imagePath = baseUrl + imagePath;
        }
        
        var isOwnProduct = (typeof currentUserRole !== 'undefined' && currentUserRole === 'seller' && product.seller_id == currentUserId);
        var addToCartButton = '';

        if (!isOwnProduct) {
            addToCartButton = '<button class="add-to-cart-btn" onclick="event.stopPropagation(); addToCart(' + product.id + ', \'' + escapeHtml(product.name).replace(/'/g, "\\'") + '\', ' + product.price + ')">' +
                '<img src="' + baseUrl + 'images/icons/shopping-cart-01-svgrepo-com.svg" alt="Cart">' +
                'Add to Cart' +
                '</button>';
        } else {
            addToCartButton = '<button class="own-product-btn" disabled>Your Product</button>';
        }
        
        var $card = $('<div>').addClass('prod-card').css('cursor', 'pointer');
        $card.on('click', function() {
            window.location.href = baseUrl + 'product-details.php?id=' + product.id;
        });
        
        $card.html(`
            <div class="img-container">
                <img src="${imagePath}" alt="${escapeHtml(product.name)}" 
                    width="280" height="280" 
                    onerror="this.src='${baseUrl}images/default-product.png'"
                    loading="lazy">
                <div class="condition-badge ${conditionClass}">${conditionText}</div>
            </div>
            <div class="prod-info-container">
                <h3 class="prod-name">${escapeHtml(product.name)}</h3>
                <p class="prod-price">R ${parseFloat(product.price).toFixed(2)}</p>
                <div class="seller-info">
                    <div class="seller-avatar">
                        <img src="${getSellerAvatar(product.profile_image)}" alt="${escapeHtml(product.seller_name)}" 
                            onerror="this.src='${baseUrl}images/icons/profile-svgrepo-com.svg'">
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
        `);
        $grid.append($card);
    });
}

function displayPagination() {
    var $pagination = $('#pagination');
    if (!$pagination.length || totalPages <= 1) {
        $pagination.empty();
        return;
    }
    
    var html = '';
    
    if (currentPage > 1) {
        html += '<button class="page-btn" onclick="goToPage(' + (currentPage - 1) + ')">← Previous</button>';
    }
    
    for (var i = 1; i <= totalPages; i++) {
        if (i === currentPage) {
            html += '<button class="page-btn active" disabled>' + i + '</button>';
        } else if (Math.abs(i - currentPage) <= 2 || i === 1 || i === totalPages) {
            html += '<button class="page-btn" onclick="goToPage(' + i + ')">' + i + '</button>';
        } else if (Math.abs(i - currentPage) === 3) {
            html += '<span class="page-dots">...</span>';
        }
    }
    
    if (currentPage < totalPages) {
        html += '<button class="page-btn" onclick="goToPage(' + (currentPage + 1) + ')">Next →</button>';
    }
    
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
    var $container = $('#product-details-container');
    if (!$container.length) return;
    
    $container.html('<div class="loading-spinner">Loading product details...</div>');
    
    $.get(baseUrl + 'php/get-product.php?id=' + id, function(data) {
        if (data.success && data.product) {
            displayProductDetails(data.product);
        } else {
            showError(data.error || 'Product not found.');
        }
    }).fail(function() {
        showError('Unable to load product. Please try again later.');
    });
}

function showError(message) {
    var $container = $('#product-details-container');
    if (!$container.length) return;
    
    $container.html(`
        <div class="product-error-container">
            <img src="${baseUrl}images/icons/shopping-cart-01-svgrepo-com.svg" width="64" height="64" alt="Error" class="error-icon">
            <h2 class="error-title">Oops!</h2>
            <p class="error-message-text">${escapeHtml(message)}</p>
            <button class="error-action-btn" onclick="window.location.href='${baseUrl}product-listings.php'">Browse Products</button>
        </div>
    `);
}

function displayProductDetails(product) {
    var $container = $('#product-details-container');
    if (!$container.length) return;
    
    var mainImage = product.image;
    if (mainImage && !mainImage.startsWith('http') && !mainImage.startsWith('/')) {
        mainImage = baseUrl + mainImage;
    }
    
    var galleryImages = [];
    if (product.gallery_images) {
        try {
            galleryImages = JSON.parse(product.gallery_images);
        } catch(e) {
            galleryImages = [];
        }
    }
    
    var allImages = [mainImage];
    for (var i = 0; i < galleryImages.length; i++) {
        var thumbPath = galleryImages[i];
        if (thumbPath && !thumbPath.startsWith('http') && !thumbPath.startsWith('/')) {
            thumbPath = baseUrl + thumbPath;
        }
        allImages.push(thumbPath);
    }
    
    var galleryHtml = '';
    for (var i = 0; i < 4; i++) {
        var thumbPath = allImages[i] || baseUrl + 'images/default-product.png';
        var activeClass = (i === 0) ? 'active' : '';
        galleryHtml += `
            <div class="small-img ${activeClass}" data-image-index="${i}" data-image-path="${thumbPath}">
                <img src="${thumbPath}" alt="Product thumbnail" onerror="this.src='${baseUrl}images/default-product.png'">
            </div>
        `;
    }
    
    var verificationBadge = product.is_verified ? 
        '<div class="verified-badge"><img src="' + baseUrl + 'images/icons/verified-svgrepo-com.svg" width="20" height="20" alt="verification"><p>Verified Seller</p></div>' : 
        '<div class="not-verified-badge"><img src="' + baseUrl + 'images/icons/not-verified-svgrepo-com.svg" width="20" height="20" alt="not-verified"><p>Not Verified Seller</p></div>';
    
    var rating = product.avg_rating || 0;
    var starsHtml = '';
    for (var i = 1; i <= 5; i++) {
        starsHtml += (i <= rating) ? '<span class="star">★</span>' : '<span class="star empty">★</span>';
    }
    
    var conditionHtml = (product.condition && product.condition !== '') ? `<p class="sub-head">Condition: <span class="condition">${escapeHtml(product.condition)}</span></p>` : '';
    var locationHtml = (product.location) ? `<p class="sub-head">Location: <span class="city">${escapeHtml(product.location)}</span></p>` : '';
    
    var isOwnProduct = (typeof currentUserRole !== 'undefined' && currentUserRole === 'seller' && product.seller_id == currentUserId);
    var actionButtonsHtml = '';
    
    if (!isOwnProduct) {
        actionButtonsHtml = `
            <button class="cart-btn" onclick="addToCart(${product.id}, '${escapeHtml(product.name).replace(/'/g, "\\'")}', ${product.price})">
                <img src="${baseUrl}images/icons/shopping-cart-01-svgrepo-com.svg" width="24" height="24" alt="Cart">
                Add to Cart
            </button>
            <button class="buy-btn" onclick="buyNow(${product.id}, '${escapeHtml(product.name).replace(/'/g, "\\'")}', ${product.price})">Buy Now</button>
        `;
    } else {
        actionButtonsHtml = `<div class="own-product-message"><p>You cannot purchase your own product.</p></div>`;
    }
    
    $container.html(`
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
                        <img src="${getSellerAvatar(product.profile_image)}" width="40" height="40" alt="${escapeHtml(product.seller_name)}" 
                            onerror="this.src='${baseUrl}images/icons/profile-svgrepo-com.svg'">
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
                <button class="view-profile" onclick="window.location.href='${baseUrl}seller-profile-public.php?seller_id=${product.seller_id}'">
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
                <div class="payfast-badge">
                    <img src="${baseUrl}images/icons/Payfast logo.svg" alt="PayFast" width="80">
                    <span>Secure payments by PayFast</span>
                </div>
            </div>
        </div>
    `);
    
    // Attach click handler for gallery thumbnails
    $('.small-img').on('click', function() {
        var $this = $(this);
        var imagePath = $this.data('image-path');
        var $mainImage = $('#main-product-image');
        
        if ($mainImage.length && imagePath) {
            $mainImage.css('opacity', '0.5');
            $mainImage.attr('src', imagePath);
            $mainImage.off('load error').on('load', function() {
                $(this).css('opacity', '1');
            }).on('error', function() {
                $(this).attr('src', baseUrl + 'images/default-product.png').css('opacity', '1');
            });
        }
        
        $('.small-img').removeClass('active');
        $this.addClass('active');
    });
    
    $('.star').on('click', function() {
        if (typeof isLoggedIn !== 'undefined' && isLoggedIn) {
            window.location.href = baseUrl + 'my-orders.php';
        } else {
            alert('Please login to leave a review');
            window.location.href = baseUrl + 'index.php';
        }
    });
}

function buyNow(productId, productName, productPrice) {
    addToCart(productId, productName, productPrice);
    window.location.href = baseUrl + 'checkout.php';
}

function escapeHtml(text) {
    if (!text) return '';
    return $('<div>').text(text).html();
}