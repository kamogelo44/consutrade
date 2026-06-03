// products.js
/*
 * ConsuTrade - Products JavaScript File
 * Author: Kamogelo Phale
 * 
 * Handles product listings, filtering, pagination, and product details
 * Relies on main.js for escapeHtml, toast notifications, addToCart, and renderPagination
 */

// ========== HELPER FUNCTIONS ==========

/**
 * Gets the seller avatar URL with fallback to default profile image
 * 
 * @param {string} profileImage - The seller's profile image path
 * @returns {string} Fixed URL for the seller avatar
 * 
 * @example
 * getSellerAvatar('uploads/profiles/user1.jpg') // Returns full URL
 */
function getSellerAvatar(profileImage) {
    return fixImageUrl(profileImage, 'images/icons/profile-svgrepo-com.svg');
}

// ========== PRODUCT LISTINGS PAGE ==========

/** @type {number} Current page number for pagination */
var currentPage = 1;

/** @type {Object} Current filter values (categories, price_range, location) */
var currentFilters = {};

/** @type {string} Current sort option ('newest', 'price_low', 'price_high') */
var currentSort = 'newest';

/** @type {number} Total number of pages available */
var totalPages = 1;

// Cached DOM elements for products page
/** @type {jQuery|null} Products grid container */
var $productsGrid = null;

/** @type {jQuery|null} Pagination container */
var $paginationContainer = null;

/** @type {jQuery|null} Filter sidebar element */
var $filterSidebar = null;

/** @type {jQuery|null} Filter form element */
var $filterForm = null;

/** @type {jQuery|null} Sort by select dropdown */
var $sortBySelect = null;

/** @type {jQuery|null} Reset filters button */
var $resetFiltersBtn = null;

/** @type {jQuery|null} Mobile filter button */
var $mobileFilterBtn = null;

/** @type {jQuery|null} Window object for resize events */
var $window = null;

/** @type {jQuery|null} HTML and body for smooth scrolling */
var $htmlBody = null;

/** @type {jQuery|null} Category checkbox inputs */
var $categoryCheckboxes = null;

/** @type {jQuery|null} Price range radio inputs */
var $priceRangeRadios = null;

/** @type {jQuery|null} Search location input */
var $searchLocationInput = null;

/** @type {jQuery|null} Empty state reset button (dynamic) */
var $emptyResetBtn = null;

/**
 * Caches all DOM elements used in the products page
 * 
 * @returns {void}
 */
function cacheProductsPageElements() {
    $productsGrid = $('#products-grid');
    $paginationContainer = $('#pagination');
    $filterSidebar = $('#filterSidebar');
    $filterForm = $('#filterForm');
    $sortBySelect = $('#sortBy');
    $resetFiltersBtn = $('#resetFilters');
    $mobileFilterBtn = $('#mobileFilterBtn');
    $window = $(window);
    $htmlBody = $('html, body');
    $categoryCheckboxes = $('input[name="category[]"]');
    $priceRangeRadios = $('input[name="price_range"]');
    $searchLocationInput = $('#search-location');
}

/**
 * Sets up all event listeners for product filtering and sorting
 * 
 * @returns {void}
 * 
 * @sideeffect Binds click, submit, and change events to filter controls
 */
function setupProductEventListeners() {
    cacheProductsPageElements();
    
    $mobileFilterBtn.on('click', function() {
        $filterSidebar.toggleClass('active');
    });

    $filterForm.on('submit', function(e) {
        e.preventDefault();
        collectFilters();
        currentPage = 1;
        loadProducts();
        if ($window.width() <= 768) {
            $filterSidebar.removeClass('active');
        }
    });

    $resetFiltersBtn.on('click', function() {
        $filterForm[0].reset();
        currentFilters = {};
        currentPage = 1;
        loadProducts();
    });

    $sortBySelect.on('change', function() {
        currentSort = $sortBySelect.val();
        currentPage = 1;
        loadProducts();
    });
}

/**
 * Collects current filter values from the filter form
 * 
 * @returns {void}
 * 
 * @sideeffect Updates the global currentFilters object
 */
function collectFilters() {
    var categories = [];
    $categoryCheckboxes.each(function() {
        var $checkbox = $(this);
        if ($checkbox.is(':checked')) {
            categories.push($checkbox.val());
        }
    });
    
    var $selectedPriceRange = $priceRangeRadios.filter(':checked');
    var priceRange = $selectedPriceRange.length ? $selectedPriceRange.val() : '';
    
    currentFilters = {
        categories: categories,
        price_range: priceRange,
        location: $searchLocationInput.val() || ''
    };
}

/**
 * Shows empty state when no products are found
 * 
 * @returns {void}
 * 
 * @sideeffect Updates products grid with empty state UI
 */
function showEmptyState() {
    cacheProductsPageElements();
    
    $productsGrid.html(
        '<div class="empty-state" id="empty-products-state">' +
            '<img src="' + baseUrl + 'images/icons/product-catalog-svgrepo-com.svg" width="64" height="64" alt="No products">' +
            '<h3>No products found</h3>' +
            '<p>We couldn\'t find any products matching your criteria.</p>' +
            '<button class="view-all-btn" id="resetFiltersEmptyBtn">Reset Filters</button>' +
        '</div>'
    );
    $paginationContainer.empty();
    
    // Bind reset filters button from empty state
    $('#resetFiltersEmptyBtn').off('click').on('click', function() {
        $filterForm[0].reset();
        currentFilters = {};
        currentPage = 1;
        loadProducts();
    });
}

/**
 * Shows error state when product loading fails
 * 
 * @returns {void}
 * 
 * @sideeffect Updates products grid with error state UI
 */
function showErrorState() {
    cacheProductsPageElements();
    
    $productsGrid.html(
        '<div class="empty-state" id="error-products-state">' +
            '<img src="' + baseUrl + 'images/icons/error-svgrepo-com.svg" width="64" height="64" alt="Error">' +
            '<h3>Something went wrong</h3>' +
            '<p>Error loading products. Please try again.</p>' +
            '<button class="view-all-btn" id="refreshPageBtn">Refresh Page</button>' +
        '</div>'
    );
    $paginationContainer.empty();
    
    // Bind refresh button
    $('#refreshPageBtn').off('click').on('click', function() {
        location.reload();
    });
}

/**
 * Loads products from the server with current filters and pagination
 * 
 * @returns {void}
 * 
 * @fires AJAX GET request to get-products.php
 * @sideeffect Updates the products grid with new products
 */
function loadProducts() {
    if (!$productsGrid) {
        cacheProductsPageElements();
    }
    
    var params = new URLSearchParams();
    params.append('page', currentPage);
    params.append('sort', currentSort);
    params.append('limit', 12);
    
    if (currentFilters.categories && currentFilters.categories.length > 0) {
        params.append('categories', currentFilters.categories.join(','));
    }
    if (currentFilters.price_range) params.append('price_range', currentFilters.price_range);
    if (currentFilters.location) params.append('location', currentFilters.location);
    
    $productsGrid.html('<div class="loading-spinner">Loading products...</div>');
    
    $.get(baseUrl + 'php/endpoints/get-products.php?' + params.toString(), function(data) {
        if (data.success && data.products && data.products.length > 0) {
            displayProducts(data.products);
            totalPages = data.total_pages || 1;
            displayPagination();
        } else {
            showEmptyState();
        }
    }).fail(function() {
        showErrorState();
    });
}

/**
 * Renders product cards in the products grid
 * 
 * @param {Array} products - Array of product objects to display
 * @returns {void}
 * 
 * @sideeffect Populates the products grid DOM with product cards
 */
function displayProducts(products) {
    if (!$productsGrid) {
        cacheProductsPageElements();
    }
    $productsGrid.empty();
    
    $.each(products, function(index, product) {
        var imagePath = fixImageUrl(product.display_image || product.image || product.image_url);
        
        var verifiedBadge = product.is_verified ? 
            '<div class="verified-badge-card"><img src="' + baseUrl + 'images/icons/verified-svgrepo-com.svg" width="14" height="14"><span>Verified Seller</span></div>' : 
            '<div class="unverified-badge-card"><img src="' + baseUrl + 'images/icons/not-verified-svgrepo-com.svg" width="14" height="14"><span>Unverified</span></div>';
        
        var conditionClass = '';
        var conditionText = product.condition || 'Good';
        if (conditionText === 'New') conditionClass = 'new';
        else if (conditionText === 'Like New') conditionClass = 'like-new';
        else if (conditionText === 'Good') conditionClass = 'good';
        else if (conditionText === 'Fair') conditionClass = 'fair';
        
        var stockQuantity = product.stock_quantity || 1;
        var isOutOfStock = stockQuantity <= 0;
        var stockBadge = isOutOfStock ? 
            '<div class="out-of-stock-badge-card">Out of Stock</div>' : 
            (stockQuantity <= 5 ? '<div class="low-stock-badge-card">Only ' + stockQuantity + ' left</div>' : '');
        
        var isOwnProduct = (typeof currentUserRole !== 'undefined' && currentUserRole === 'seller' && product.seller_id == currentUserId);
        var addToCartButton = '';
        
        if (!isOwnProduct && !isOutOfStock) {
            addToCartButton = '<button class="add-to-cart-btn" onclick="event.stopPropagation(); addToCart(' + product.id + ', \'' + escapeHtml(product.name).replace(/'/g, "\\'") + '\', ' + product.price + ')">Add to Cart</button>';
        } else if (isOutOfStock) {
            addToCartButton = '<button class="out-of-stock-btn" disabled>Out of Stock</button>';
        } else if (isOwnProduct) {
            addToCartButton = '<button class="own-product-btn" disabled>Your Product</button>';
        }
        
        var $card = $('<div>').addClass('prod-card').css('cursor', 'pointer');
        $card.on('click', function() {
            window.location.href = baseUrl + 'product-details.php?id=' + product.id;
        });
        
        $card.html(
            '<div class="img-container">' +
                '<img src="' + imagePath + '" alt="' + escapeHtml(product.name) + '" onerror="this.src=\'' + baseUrl + 'images/default-product.png\'">' +
                '<div class="condition-badge ' + conditionClass + '">' + conditionText + '</div>' +
                stockBadge +
            '</div>' +
            '<div class="prod-info-container">' +
                '<h3 class="prod-name">' + escapeHtml(product.name) + '</h3>' +
                '<p class="prod-price">R ' + parseFloat(product.price).toFixed(2) + '</p>' +
                '<div class="seller-info">' +
                    '<div class="seller-avatar">' +
                        '<img src="' + getSellerAvatar(product.profile_image) + '" alt="' + escapeHtml(product.seller_name) + '" onerror="this.src=\'' + baseUrl + 'images/icons/profile-svgrepo-com.svg\'">' +
                    '</div>' +
                    '<div class="seller-details">' +
                        '<p class="seller-name">' + escapeHtml(product.seller_name) + '</p>' +
                        '<p class="location">' +
                            '<img src="' + baseUrl + 'images/icons/pin-location-svgrepo-com.svg" width="10" height="10" alt="location">' +
                            escapeHtml(product.location || 'South Africa') +
                        '</p>' +
                    '</div>' +
                    verifiedBadge +
                '</div>' +
                addToCartButton +
                '<div class="payment-badge">' +
                    '<span>Secure payment via</span>' +
                    '<img src="' + baseUrl + 'images/icons/Payfast logo.svg" alt="PayFast">' +
                '</div>' +
            '</div>'
        );
        $productsGrid.append($card);
    });
}

/**
 * Renders pagination controls using the shared renderPagination function
 * 
 * @returns {void}
 * 
 * @sideeffect Creates pagination buttons and scrolls to top
 */
function displayPagination() {
    if (!$paginationContainer) {
        cacheProductsPageElements();
    }
    renderPagination($paginationContainer, currentPage, totalPages, function(page) {
        currentPage = page;
        loadProducts();
        $htmlBody.animate({ scrollTop: 0 }, 'smooth');
    });
}

// ========== PRODUCT DETAILS PAGE FUNCTIONS ==========

/** @type {jQuery|null} Product details container element */
var $productDetailsContainer = null;

/** @type {jQuery|null} Product details content element */
var $productDetailsContent = null;

/** @type {jQuery|null} Main product image element */
var $mainProductImage = null;

/** @type {jQuery|null} Small thumbnail image elements */
var $smallImgElements = null;

/** @type {jQuery|null} Gallery container element */
var $galleryContainer = null;

/**
 * Caches DOM elements used on the product details page
 * 
 * @returns {void}
 */
function cacheProductDetailsElements() {
    $productDetailsContainer = $('.product-details-container');
    $productDetailsContent = $('#product-details-content');
    $mainProductImage = $('#main-product-image');
    $galleryContainer = $('#gallery-container');
    $smallImgElements = null;
}

/**
 * Loads and displays product details for a given product ID
 * 
 * @param {number} id - The product ID to load
 * @returns {void}
 * 
 * @fires AJAX GET request to get-product.php
 */
function loadProductDetails(id) {
    if (!$productDetailsContainer) {
        cacheProductDetailsElements();
    }
    
    if (!$productDetailsContainer.length) return;
    
    $productDetailsContent.html('<div class="loading-spinner">Loading product details...</div>');
    
    $.get(baseUrl + 'php/endpoints/get-product.php?id=' + id, function(data) {
        if (data.success && data.product) {
            displayProductDetails(data.product);
        } else {
            showProductError(data.error || 'Product not found.');
        }
    }).fail(function() {
        showProductError('Unable to load product.');
    });
}

/**
 * Displays an error message when a product is not found
 * 
 * @param {string} message - Error message to display
 * @returns {void}
 * 
 * @sideeffect Replaces product details container with error UI
 */
function showProductError(message) {
    if (!$productDetailsContainer) {
        cacheProductDetailsElements();
    }
    
    if (!$productDetailsContainer.length) return;
    
    $productDetailsContainer.html(
        '<div class="product-error-container">' +
            '<h2 class="product-error-title">Product Not Found</h2>' +
            '<p class="product-error-message-text">' + escapeHtml(message) + '</p>' +
            '<button class="product-error-action-btn" onclick="window.location.href=\'' + baseUrl + 'product-listings.php\'">Browse Products</button>' +
        '</div>'
    );
}

/**
 * Renders full product details including images, info, reviews, and actions
 * 
 * @param {Object} product - Product object containing all details
 * @returns {void}
 * 
 * @sideeffect Populates the product details DOM with complete product information
 */
function displayProductDetails(product) {
    if (!$productDetailsContent) {
        cacheProductDetailsElements();
    }
    if (!$productDetailsContent.length) return;
    
    var mainImage = fixImageUrl(product.image_url);
    var galleryImages = product.gallery_images || [];
    
    var thumbnails = [mainImage];
    
    for (var i = 0; i < galleryImages.length && thumbnails.length < 4; i++) {
        var galleryUrl = fixImageUrl(galleryImages[i]);
        if (galleryUrl !== mainImage) {
            thumbnails.push(galleryUrl);
        }
    }
    
    while (thumbnails.length < 4) {
        thumbnails.push(baseUrl + 'images/default-product.png');
    }
    
    var galleryHtml = '';
    for (var i = 0; i < thumbnails.length; i++) {
        var isActive = (i === 0) ? 'active' : '';
        var thumbUrl = thumbnails[i];
        
        galleryHtml +=
            '<div class="small-img ' + isActive + '" data-image-path="' + thumbUrl + '">' +
                '<img src="' + thumbUrl + '" alt="Thumbnail ' + (i+1) + '" onerror="this.src=\'' + baseUrl + 'images/default-product.png\'">' +
            '</div>';
    }
    
    var isOutOfStock = product.stock_quantity <= 0;
    var isLowStock = product.stock_quantity > 0 && product.stock_quantity <= 5;
    var stockHtml = '';
    
    if (isOutOfStock) {
        stockHtml = '<div class="stock-status out-of-stock"><span class="stock-icon">✕</span> Out of Stock</div>';
    } else if (isLowStock) {
        stockHtml = '<div class="stock-status low-stock"><span class="stock-icon">⚠</span> Only ' + product.stock_quantity + ' left in stock!</div>';
    } else {
        stockHtml = '<div class="stock-status in-stock"><span class="stock-icon">✓</span> In Stock (' + product.stock_quantity + ' available)</div>';
    }
    
    var starsHtml = '';
    var avgRating = parseFloat(product.avg_rating) || 0;
    for (var i = 1; i <= 5; i++) {
        starsHtml += (i <= avgRating) ? '<span class="star">★</span>' : '<span class="star empty">★</span>';
    }
    
    var actionButtonsHtml = '';
    if (!isOutOfStock) {
        var escapedName = escapeHtml(product.name).replace(/'/g, "\\'");
        actionButtonsHtml =
            '<button class="cart-btn" onclick="addToCart(' + product.id + ', \'' + escapedName + '\', ' + product.price + ')">Add to Cart</button>' +
            '<button class="buy-btn" onclick="buyNow(' + product.id + ', \'' + escapedName + '\', ' + product.price + ')">Buy Now</button>';
    } else {
        actionButtonsHtml = '<button class="cart-btn out-of-stock-btn" disabled>Out of Stock</button>';
    }
    
    var sellerImage = fixImageUrl(product.seller_profile_image);
    
    $productDetailsContent.html(
        '<div class="top-items">' +
            '<div class="product-imgs">' +
                '<div class="main-img">' +
                    '<img src="' + mainImage + '" alt="' + escapeHtml(product.name) + '" id="main-product-image" onerror="this.src=\'' + baseUrl + 'images/default-product.png\'">' +
                '</div>' +
                '<div class="smaller-imgs" id="gallery-container">' + galleryHtml + '</div>' +
            '</div>' +
            '<div class="product-info">' +
                '<h1 class="details-prod-name">' + escapeHtml(product.name) + '</h1>' +
                '<p class="details-price">R ' + parseFloat(product.price).toFixed(2) + '</p>' +
                '<div class="cat-badge">' +
                    '<span class="cat-name">' + escapeHtml(product.category_name || 'General') + '</span>' +
                '</div>' +
                stockHtml +
                '<div class="description">' +
                    '<p class="sub-head">Description</p>' +
                    '<p class="des">' + escapeHtml(product.description || 'No description available.') + '</p>' +
                '</div>' +
                '<div class="con-loc">' +
                    (product.condition ? '<p><strong>Condition:</strong> ' + escapeHtml(product.condition) + '</p>' : '') +
                    (product.location ? '<p><strong>Location:</strong> ' + escapeHtml(product.location) + '</p>' : '') +
                '</div>' +
            '</div>' +
        '</div>' +
        '<section class="review">' +
            '<div class="rev-container">' +
                '<div class="seller-profile">' +
                    '<div class="profile-pic">' +
                        '<img src="' + sellerImage + '" width="40" height="40" alt="' + escapeHtml(product.seller_name) + '" onerror="this.src=\'' + baseUrl + 'images/icons/profile-svgrepo-com.svg\'">' +
                    '</div>' +
                    '<p class="seller-name">' + escapeHtml(product.seller_name) + '</p>' +
                '</div>' +
                '<div class="verification">' +
                    (product.is_verified ? 
                        '<div class="verified-badge"><img src="' + baseUrl + 'images/icons/verified-svgrepo-com.svg" width="20" height="20"><p>Verified Seller</p></div>' : 
                        '<div class="not-verified-badge"><img src="' + baseUrl + 'images/icons/not-verified-svgrepo-com.svg" width="20" height="20"><p>Not Verified</p></div>') +
                '</div>' +
                '<div class="star-reviews">' +
                    '<h1>Seller Reviews</h1>' +
                    starsHtml +
                    '<p>Rating: ' + avgRating.toFixed(1) + '/5 (' + (product.review_count || 0) + ' reviews)</p>' +
                '</div>' +
                '<button class="view-profile" onclick="window.location.href=\'' + baseUrl + 'seller-profile-public.php?seller_id=' + product.seller_id + '&product_id=' + product.id + '&product_name=' + encodeURIComponent(product.name) + '\'">View Seller Profile</button>' +
            '</div>' +
        '</section>' +
        '<div class="actions">' +
            '<div class="actions-card">' +
                '<div class="action-btns">' + actionButtonsHtml + '</div>' +
                '<div class="payfast-badge">' +
                    '<img src="' + baseUrl + 'images/icons/Payfast logo.svg" alt="PayFast">' +
                    '<span>Secure payments by PayFast</span>' +
                '</div>' +
            '</div>' +
        '</div>'
    );
    
    $mainProductImage = $('#main-product-image');
    $smallImgElements = $('.small-img');
    
    $smallImgElements.on('click', function() {
        var $this = $(this);
        var newImagePath = $this.data('image-path');
        $mainProductImage.attr('src', newImagePath);
        $smallImgElements.removeClass('active');
        $this.addClass('active');
    });
}

/**
 * Adds product to cart and redirects to checkout page
 * 
 * @param {number} productId - Product ID
 * @param {string} productName - Product name
 * @param {number} productPrice - Product price
 * @returns {void}
 * 
 * @fires addToCart from main.js
 * @sideeffect Redirects to checkout page
 */
function buyNow(productId, productName, productPrice) {
    addToCart(productId, productName, productPrice);
    window.location.href = baseUrl + 'checkout.php';
}

// ========== INITIALIZE ==========

/**
 * Initializes the products page or product details page based on DOM elements
 * 
 * @returns {void}
 * 
 * @fires cacheProductsPageElements, loadProducts, setupProductEventListeners for listings page
 * @fires cacheProductDetailsElements, loadProductDetails for details page
 */
$(function() {
    var $productsGridElement = $('#products-grid');
    var $productDetailsContainerElement = $('.product-details-container');
    
    if ($productsGridElement.length) {
        cacheProductsPageElements();
        loadProducts();
        setupProductEventListeners();
    }
    
    if ($productDetailsContainerElement.length) {
        cacheProductDetailsElements();
        var productId = $productDetailsContainerElement.data('product-id');
        if (productId > 0) {
            loadProductDetails(productId);
        }
    }
});