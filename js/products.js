/**
 * ConsuTrade - Product Listings JavaScript
 * Author: Kamogelo Phale
 * 
 * Handles product listings, filtering, pagination, search, and product details.
 * Used on: product-listings.php, product-details.php, search-results.php, index.php
 * 
 * Depends on: utils.js (escapeHtml, fixImageUrl, showToast, showSuccessToast, showErrorToast)
 */

// ============================================================
// DOM CACHE
// ============================================================

/**
 * DOM element references for product pages.
 * All elements are cached once and reused throughout the page.
 */
var $productsGrid = null;
var $paginationContainer = null;
var $filterSidebar = null;
var $filterForm = null;
var $mobileFilterBtn = null;
var $resetFiltersBtn = null;
var $sortBySelect = null;
var $categoryCheckboxes = null;
var $priceRangeRadios = null;
var $searchLocationInput = null;
var $window = null;
var $htmlBody = null;

/**
 * Caches all DOM elements used on product pages.
 * Called once on page load to store jQuery references.
 */
function cacheProductElements() {
    $productsGrid = $('#products-grid');
    $paginationContainer = $('#pagination');
    $filterSidebar = $('#filterSidebar');
    $filterForm = $('#filterForm');
    $mobileFilterBtn = $('#mobileFilterBtn');
    $resetFiltersBtn = $('#resetFilters');
    $sortBySelect = $('#sortBy');
    $categoryCheckboxes = $('input[name="category[]"]');
    $priceRangeRadios = $('input[name="price_range"]');
    $searchLocationInput = $('#search-location');
    $window = $(window);
    $htmlBody = $('html, body');
}

// ============================================================
// PRODUCT LISTINGS VARIABLES
// ============================================================

var currentPage = 1;
var currentFilters = {};
var currentSort = 'newest';
var totalPages = 1;
var currentSearchQuery = '';

// ============================================================
// INFINITE SCROLL VARIABLES
// ============================================================

var isLoading = false;
var hasMoreProducts = true;
var observer = null;
var isInfiniteScrollEnabled = true;

// ============================================================
// INITIAL CATEGORY FROM URL
// ============================================================

// Check if a category was passed from the URL (homepage category links)
var initialCategory = window.initialCategory || '';

// ============================================================
// PRODUCT LISTINGS FUNCTIONS
// ============================================================

/**
 * Loads products from server with current filters.
 * Works for both product-listings.php and search-results.php.
 */
function loadProducts() {
    cacheProductElements();

    if (!$productsGrid || !$productsGrid.length) return;

    // Reset infinite scroll state
    resetInfiniteScroll();

    var params = new URLSearchParams();
    params.append('page', currentPage);
    params.append('sort', currentSort);
    params.append('limit', 12);

    // Check for initial category from URL (homepage links)
    if (initialCategory && (!currentFilters.categories || currentFilters.categories.length === 0)) {
        currentFilters.categories = [initialCategory];
        // Check the checkbox in the UI
        $('input[name="category[]"][value="' + initialCategory + '"]').prop('checked', true);
    }

    // Check if we're on search page
    var isSearchPage = window.location.pathname.includes('search-results.php');
    if (isSearchPage) {
        var urlParams = new URLSearchParams(window.location.search);
        var searchQuery = urlParams.get('search') || '';
        currentSearchQuery = searchQuery;
        if (searchQuery) {
            params.append('search', searchQuery);
        }
    }

    if (currentFilters.categories && currentFilters.categories.length > 0) {
        params.append('categories', currentFilters.categories.join(','));
    }
    if (currentFilters.price_range) params.append('price_range', currentFilters.price_range);
    if (currentFilters.location) params.append('location', currentFilters.location);

    // Don't replace skeleton with spinner - keep skeleton
    // var loadingText = isSearchPage ? 'Searching for products...' : 'Loading products...';
    // $productsGrid.html('<div class="loading-spinner">' + loadingText + '</div>');

    // Use different endpoint based on page
    var endpoint = isSearchPage ? 'php/endpoints/products/search-products.php' : 'php/endpoints/products/get-products.php';

    $.ajax({
        url: baseUrl + endpoint + '?' + params.toString(),
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success && data.products && data.products.length > 0) {
                if (typeof displayProducts === 'function') {
                    // Initial load - replace content
                    displayProducts(data.products, '#products-grid', false);
                } else {
                    console.error('displayProducts function not found');
                    $productsGrid.html('<p class="error">Error: displayProducts function not loaded.</p>');
                }
                totalPages = data.total_pages || 1;
                hasMoreProducts = data.products.length >= 12;
                
                // Setup infinite scroll after products load
                if (hasMoreProducts && isInfiniteScrollEnabled) {
                    setupInfiniteScroll();
                }
            } else {
                showEmptyState();
                hasMoreProducts = false;
            }
        },
        error: function(xhr, status, error) {
            console.error('Search error:', status, error);
            console.error('Response:', xhr.responseText);
            showErrorState();
            hasMoreProducts = false;
        }
    });
}

/**
 * Displays products in grid with optional container selector.
 * Uses fixImageUrl from utils.js
 * Uses escapeHtml from utils.js
 * 
 * @param {Array} products - Array of product objects
 * @param {string} containerSelector - Optional container selector
 * @param {boolean} append - If true, appends to existing content instead of replacing
 */
function displayProducts(products, containerSelector, append) {
    cacheProductElements();

    var $grid = $productsGrid;
    if (containerSelector) {
        $grid = $(containerSelector);
    }

    if (!$grid || !$grid.length) return;

    if (!append) {
        $grid.empty();
    }

    for (var i = 0; i < products.length; i++) {
        var product = products[i];
        var imagePath = fixImageUrl(product.display_image || product.image || product.image_url);

        var conditionText = product.condition || 'Good';
        var conditionClass = 'good';
        if (conditionText == 'New') conditionClass = 'new';
        else if (conditionText == 'Like New') conditionClass = 'like-new';
        else if (conditionText == 'Good') conditionClass = 'good';
        else if (conditionText == 'Fair') conditionClass = 'fair';

        var stockQty = parseInt(product.stock_quantity) || 0;
        var stockBadge = '';
        var cartBtn = '';

        if (stockQty <= 0) {
            stockBadge = '<span class="out-of-stock-badge-card">' + t('out_of_stock') + '</span>';
            cartBtn = '<button class="prod-cart-btn disabled" disabled aria-label="' + t('out_of_stock') + '">' +
                '<img src="' + baseUrl + 'images/icons/x-svgrepo-com.svg" alt="" width="16" height="16">' +
            '</button>';
        } else if (stockQty <= 5) {
            stockBadge = '<span class="low-stock-badge-card">' + t('only_n_left').replace('{n}', stockQty) + '</span>';
            cartBtn = '<button class="prod-cart-btn" data-product-id="' + product.id + '" aria-label="' + t('add_to_cart') + '">' +
                '<img src="' + baseUrl + 'images/icons/shopping-cart-01-svgrepo-com.svg" alt="" width="16" height="16">' + +
            '</button>';
        } else {
            cartBtn = '<button class="prod-cart-btn" data-product-id="' + product.id + '" aria-label="' + t('add_to_cart') + '">' +
                '<img src="' + baseUrl + 'images/icons/shopping-cart-01-svgrepo-com.svg" alt="" width="16" height="16">' +
            '</button>';
        }

        var verifiedBadge = product.is_verified
            ? '<span class="verified-badge-card">&#10003;</span>'
            : '<span class="unverified-badge-card">!</span>';

        var sellerAvatar = fixImageUrl(product.profile_image, 'images/icons/profile-svgrepo-com.svg');
        var onlineClass = product.is_online ? ' online' : '';

        var $card = $('<div>').addClass('prod-card').css('cursor', 'pointer');
        $card.on('click', function(id) {
            return function() { window.location.href = baseUrl + 'product-details.php?id=' + id; };
        }(product.id));

        $card.html(
            '<div class="img-container">' +
                '<span class="condition-badge ' + conditionClass + '">' + conditionText + '</span>' +
                stockBadge +
                '<img src="' + imagePath + '" alt="' + escapeHtml(product.name) + '" loading="lazy" onerror="this.src=\'' + baseUrl + 'images/default-product.png\'">' +
            '</div>' +
            '<div class="prod-info-container">' +
                '<h3 class="prod-name">' + escapeHtml(product.name) + '</h3>' +
                '<div class="prod-price-row">' +
                    '<p class="prod-price"><span class="currency">R</span>' + parseFloat(product.price).toFixed(2) + '</p>' +
                    cartBtn +
                '</div>' +
                '<div class="seller-info">' +
                    '<div class="seller-avatar' + onlineClass + '">' +
                        '<img src="' + sellerAvatar + '" alt="' + escapeHtml(product.seller_name) + '" loading="lazy" onerror="this.src=\'' + baseUrl + 'images/icons/profile-svgrepo-com.svg\'">' +
                    '</div>' +
                    '<div class="seller-details">' +
                        '<span class="seller-name">' + escapeHtml(product.seller_name) + '</span>' +
                        verifiedBadge +
                        '<span class="location">' +
                            '<img src="' + baseUrl + 'images/icons/pin-location-svgrepo-com.svg" width="10" height="10" alt=""> ' +
                            escapeHtml(product.location || 'South Africa') +
                        '</span>' +
                    '</div>' +
                '</div>' +
            '</div>' +
            '<div class="payment-badge">' +
                '<span>' + t('secured_by') + '</span>' +
                '<img src="' + baseUrl + 'images/icons/Payfast logo.svg" alt="PayFast" loading="lazy">' +
            '</div>'
        );
        $grid.append($card);
    }

    // Bind cart buttons
    $grid.find('.prod-cart-btn').not('.disabled').off('click').on('click', function(e) {
        e.stopPropagation();
        var productId = $(this).data('product-id');
        if (productId && typeof addToCart === 'function') {
            addToCart(productId);
        }
    });
}

/**
 * Shows empty state when no products match filters.
 */
function showEmptyState() {
    cacheProductElements();

    var isSearchPage = window.location.pathname.includes('search-results.php');
    var title = isSearchPage ? 'No products found for "' + escapeHtml(currentSearchQuery) + '"' : 'No products found';
    var message = isSearchPage ? 'We couldn\'t find any products matching your search.' : 'We couldn\'t find any products matching your criteria.';

    $productsGrid.html(
        '<div class="empty-state" id="empty-products-state">' +
            '<img src="' + baseUrl + 'images/icons/product-catalog-svgrepo-com.svg" width="64" height="64" alt="No products" loading="lazy">' +
            '<h3>' + title + '</h3>' +
            '<p>' + message + '</p>' +
            (isSearchPage 
                ? '<a href="' + baseUrl + 'product-listings.php" class="view-all-btn">Browse All Products</a>'
                : '<button class="view-all-btn" id="empty-clear-filters-btn">Clear Filters</button>') +
        '</div>'
    );
    $paginationContainer.empty();
    removeLoadingIndicator();

    // Bind the clear filters button after it's added to DOM
    if (!isSearchPage) {
        $('#empty-clear-filters-btn').on('click', function() {
            $filterForm[0].reset();
            currentFilters = {
                categories: [],
                price_range: '',
                location: ''
            };
            currentPage = 1;
            initialCategory = '';
            loadProducts();
        });
    }
}

/**
 * Shows error state when product loading fails.
 */
function showErrorState() {
    $productsGrid.html(
        '<div class="empty-state" id="error-products-state">' +
            '<img src="' + baseUrl + 'images/icons/error-svgrepo-com.svg" width="64" height="64" alt="Error" loading="lazy">' +
            '<h3>Something went wrong</h3>' +
            '<p>Error loading products. Please try again.</p>' +
            '<button class="view-all-btn" onclick="location.reload()">Refresh Page</button>' +
        '</div>'
    );
    $paginationContainer.empty();
    removeLoadingIndicator();
}

// ============================================================
// INFINITE SCROLL FUNCTIONS
// ============================================================

/**
 * Sets up infinite scroll using Intersection Observer.
 */
function setupInfiniteScroll() {
    // Don't setup if already exists
    if (observer) {
        observer.disconnect();
        observer = null;
    }

    // Remove old sentinel
    $('#scroll-sentinel').remove();

    // Create sentinel element at the bottom
    var $sentinel = $('<div id="scroll-sentinel" style="height: 1px;"></div>');
    $('#products-grid').after($sentinel);

    // Use Intersection Observer if available
    if (typeof IntersectionObserver !== 'undefined') {
        observer = new IntersectionObserver(function(entries) {
            if (entries[0].isIntersecting) {
                loadMoreProducts();
            }
        }, {
            rootMargin: '0px 0px 300px 0px'
        });

        observer.observe($sentinel[0]);
    } else {
        // Fallback to scroll event for older browsers
        setupScrollFallback();
    }
}

/**
 * Fallback for browsers without Intersection Observer.
 */
function setupScrollFallback() {
    var $window = $(window);
    var $document = $(document);

    $window.off('scroll.infinite').on('scroll.infinite', function() {
        var scrollPosition = $window.scrollTop() + $window.height();
        var documentHeight = $document.height();

        if (scrollPosition >= documentHeight - 400) {
            loadMoreProducts();
        }
    });
}

/**
 * Loads the next page of products (infinite scroll).
 */
function loadMoreProducts() {
    // Don't load if already loading, no more products, or not enabled
    if (isLoading || !hasMoreProducts || !isInfiniteScrollEnabled) return;

    isLoading = true;
    currentPage++;

    showLoadingIndicator();

    var params = new URLSearchParams();
    params.append('page', currentPage);
    params.append('sort', currentSort);
    params.append('limit', 12);

    if (currentFilters.categories && currentFilters.categories.length > 0) {
        params.append('categories', currentFilters.categories.join(','));
    }
    if (currentFilters.price_range) params.append('price_range', currentFilters.price_range);
    if (currentFilters.location) params.append('location', currentFilters.location);

    var isSearchPage = window.location.pathname.includes('search-results.php');
    if (isSearchPage && currentSearchQuery) {
        params.append('search', currentSearchQuery);
    }

    var endpoint = isSearchPage ? 'php/endpoints/products/search-products.php' : 'php/endpoints/products/get-products.php';

    $.ajax({
        url: baseUrl + endpoint + '?' + params.toString(),
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            removeLoadingIndicator();

            if (data.success && data.products && data.products.length > 0) {
                if (typeof displayProducts === 'function') {
                    // Append new products
                    displayProducts(data.products, '#products-grid', true);
                }
                // Check if there are more products (batch size is 12)
                hasMoreProducts = data.products.length >= 12;
                
                // Re-setup sentinel for next batch
                if (hasMoreProducts) {
                    resetSentinel();
                } else {
                    // Show "no more products" message
                    showEndMessage();
                }
            } else {
                hasMoreProducts = false;
                showEndMessage();
            }

            isLoading = false;
        },
        error: function() {
            removeLoadingIndicator();
            isLoading = false;
            hasMoreProducts = false;
            showErrorToast('Failed to load more products.');
        }
    });
}

/**
 * Resets the sentinel observer after loading new products.
 */
function resetSentinel() {
    // Remove old sentinel
    $('#scroll-sentinel').remove();

    if (!hasMoreProducts) return;

    // Create new sentinel
    var $sentinel = $('<div id="scroll-sentinel" style="height: 1px;"></div>');
    $('#products-grid').after($sentinel);

    if (observer) {
        observer.disconnect();
        observer = null;
    }

    if (typeof IntersectionObserver !== 'undefined') {
        observer = new IntersectionObserver(function(entries) {
            if (entries[0].isIntersecting) {
                loadMoreProducts();
            }
        }, {
            rootMargin: '0px 0px 300px 0px'
        });
        observer.observe($sentinel[0]);
    }
}

/**
 * Shows a loading indicator at the bottom of the product grid.
 */
function showLoadingIndicator() {
    removeLoadingIndicator();
    var $loading = $(
        '<div id="infinite-loading" class="loading-spinner">' +
            'Loading more products...' +
        '</div>'
    );
    $('#products-grid').after($loading);
}

/**
 * Removes the loading indicator.
 */
function removeLoadingIndicator() {
    $('#infinite-loading').remove();
}

/**
 * Shows "no more products" message at the bottom.
 */
function showEndMessage() {
    var $endMessage = $(
        '<div id="end-message" style="text-align:center;padding:var(--spacing-xl);color:var(--gray-medium);font-size:var(--font-sm);">' +
            'You\'ve reached the end of the list' +
        '</div>'
    );
    
    // Remove existing end message
    $('#end-message').remove();
    
    // Only show if products exist (not empty state)
    if ($productsGrid.children().length > 0) {
        $('#products-grid').after($endMessage);
    }
}

/**
 * Resets infinite scroll state.
 * Call this when filters change or search is performed.
 */
function resetInfiniteScroll() {
    currentPage = 1;
    hasMoreProducts = true;
    isLoading = false;
    isInfiniteScrollEnabled = true;
    removeLoadingIndicator();
    $('#end-message').remove();
    $('#scroll-sentinel').remove();

    // Remove scroll event fallback
    $window.off('scroll.infinite');

    if (observer) {
        observer.disconnect();
        observer = null;
    }
}

// ============================================================
// FILTER FUNCTIONS
// ============================================================

/**
 * Collects filter values from the filter form.
 */
function collectFilters() {
    var categories = [];
    $categoryCheckboxes.each(function() {
        if ($(this).is(':checked')) {
            categories.push($(this).val());
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
 * Sets up event listeners for product filtering.
 */
function setupProductEventListeners() {
    cacheProductElements();

    if ($mobileFilterBtn && $mobileFilterBtn.length) {
        $mobileFilterBtn.on('click', function() {
            $filterSidebar.toggleClass('active');
        });
    }

    // Apply Filters button (in header bar)
    $('#applyFiltersBtn').on('click', function() {
        collectFilters();
        currentPage = 1;
        loadProducts();
        if ($window.width() <= 768) {
            $filterSidebar.removeClass('active');
        }
    });

    // Clear Filters button (in header bar)
    $('#clearFiltersBtn').on('click', function() {
        $filterForm[0].reset();
        currentFilters = {
            categories: [],
            price_range: '',
            location: ''
        };
        currentPage = 1;
        // Clear the initial category so it doesn't reappear
        initialCategory = '';
        loadProducts();
    });

    if ($sortBySelect && $sortBySelect.length) {
        $sortBySelect.on('change', function() {
            currentSort = $sortBySelect.val();
            currentPage = 1;
            loadProducts();
        });
    }
}

// ============================================================
// PRODUCT DETAILS FUNCTIONS
// ============================================================

var currentProductId = 0;

/**
 * Loads product details for single product page.
 */
/**
 * Loads product details for single product page.
 */
function loadProductDetails(id) {
    if (!$('.product-details-container').length) return;

    $.get(baseUrl + 'php/endpoints/products/get-product.php?id=' + id, function(data) {
        if (data.success && data.product) {
            displayProductDetails(data.product);
        } else {
            $('.product-details-container').html(
                '<div class="empty-state">' +
                    '<img src="' + baseUrl + 'images/icons/product-catalog-svgrepo-com.svg" width="64" height="64" alt="No product" loading="lazy">' +
                    '<h3>Product Not Found</h3>' +
                    '<p>The product you\'re looking for does not exist or has been removed.</p>' +
                    '<a href="' + baseUrl + 'product-listings.php" class="view-all-btn">Browse Products</a>' +
                '</div>'
            );
        }
    }).fail(function() {
        $('.product-details-container').html(
            '<div class="empty-state">' +
                '<img src="' + baseUrl + 'images/icons/error-svgrepo-com.svg" width="64" height="64" alt="Error" loading="lazy">' +
                '<h3>Something went wrong</h3>' +
                '<p>Unable to load product details. Please try again.</p>' +
                '<a href="' + baseUrl + 'product-listings.php" class="view-all-btn">Browse Products</a>' +
            '</div>'
        );
    });
}

/**
 * Renders product details on the page
 * Uses fixImageUrl from utils.js
 * Uses escapeHtml from utils.js
 */
function displayProductDetails(product) {
    var mainImage = fixImageUrl(product.image_url);
    var galleryImages = product.gallery_images || [];
    
    var thumbnails = [mainImage];
    for (var i = 0; i < galleryImages.length && thumbnails.length < 4; i++) {
        var galleryUrl = fixImageUrl(galleryImages[i]);
        if (galleryUrl != mainImage) {
            thumbnails.push(galleryUrl);
        }
    }
    
    while (thumbnails.length < 4) {
        thumbnails.push(baseUrl + 'images/default-product.png');
    }
    
    var galleryHtml = '';
    for (var i = 0; i < thumbnails.length; i++) {
        var isActive = (i === 0) ? 'active' : '';
        galleryHtml += '<div class="small-img ' + isActive + '" data-image-path="' + thumbnails[i] + '">' +
                            '<img src="' + thumbnails[i] + '" alt="Thumbnail ' + (i+1) + '" loading="lazy" onerror="this.src=\'' + baseUrl + 'images/default-product.png\'">' +
                        '</div>';
    }
    
    var stockQty = parseInt(product.stock_quantity) || 0;
    var isOutOfStock = stockQty <= 0;
    var isLowStock = stockQty > 0 && stockQty <= 5;
    var stockHtml = '';
    
    if (isOutOfStock) {
        stockHtml = '<div class="stock-status out-of-stock">Out of Stock</div>';
    } else if (isLowStock) {
        stockHtml = '<div class="stock-status low-stock">Only ' + stockQty + ' left in stock!</div>';
    } else {
        stockHtml = '<div class="stock-status in-stock">In Stock (' + stockQty + ' available)</div>';
    }
    
    var starsHtml = '';
    var avgRating = parseFloat(product.avg_rating) || 0;
    for (var i = 1; i <= 5; i++) {
        starsHtml += (i <= avgRating) ? '<span class="star">★</span>' : '<span class="star empty">★</span>';
    }
    
    var escapedName = escapeHtml(product.name).replace(/'/g, "\\'");
    
    var isLoggedInFlag = (typeof isLoggedIn !== 'undefined' && isLoggedIn === true);
    var isOwnProduct = (typeof currentUserId !== 'undefined' && currentUserId == product.seller_id);
    var isBuyer = (typeof currentUserRole !== 'undefined' && currentUserRole == 'buyer');
    var showReportButton = isLoggedInFlag && isBuyer;
    
    var actionButtonsHtml = '';
    
    if (isOwnProduct) {
        actionButtonsHtml = '<button class="cart-btn own-product-btn" disabled>This is your product</button>';
    } else if (isOutOfStock) {
        actionButtonsHtml = '<button class="cart-btn out-of-stock-btn" disabled>Out of Stock</button>';
    } else if (!isLoggedInFlag) {
        actionButtonsHtml = '<button class="cart-btn" onclick="openModal($(\'#login-modal\'))">Add to Cart</button>' +
                            '<button class="buy-btn" onclick="openModal($(\'#login-modal\'))">Buy Now</button>';
    } else {
        actionButtonsHtml = '<button class="cart-btn" onclick="addToCart(' + product.id + ', \'' + escapedName + '\', ' + product.price + ')">Add to Cart</button>' +
                            '<button class="buy-btn" onclick="buyNow(' + product.id + ', \'' + escapedName + '\', ' + product.price + ')">Buy Now</button>';
    }
    
    if (showReportButton) {
        actionButtonsHtml += '<button class="report-btn" id="reportProductBtn">' +
                                '<img src="' + baseUrl + 'images/icons/warning-svgrepo-com.svg" width="16" height="16" alt="Report" loading="lazy"> Report This Product</button>';
    }
    
    var contactHtml = '';
    var sellerPhone = product.seller_phone || '';
    var sellerEmail = product.seller_email || '';
    
    var whatsappNumber = '';
    if (sellerPhone) {
        var digits = sellerPhone.replace(/\D/g, '');
        if (digits.startsWith('0')) {
            digits = digits.substring(1);
        }
        if (!digits.startsWith('27')) {
            digits = '27' + digits;
        }
        whatsappNumber = digits;
    }
    
    if (sellerPhone) {
        contactHtml += '<a href="https://wa.me/' + whatsappNumber + '" target="_blank" class="contact-btn whatsapp-btn">' +
                            '<img src="' + baseUrl + 'images/icons/whatsapp-svgrepo-com.svg" width="18" height="18" alt="WhatsApp" loading="lazy"> WhatsApp</a>';
    }
    if (sellerEmail) {
        contactHtml += '<a href="mailto:' + sellerEmail + '" class="contact-btn email-btn">' +
                            '<img src="' + baseUrl + 'images/icons/email-svgrepo-com.svg" width="16" height="16" alt="Email" loading="lazy"> Email Seller</a>';
    }
    
    var sellerImage = fixImageUrl(product.seller_profile_image);
    
    $('#product-details-content').html(
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
                '<div class="cat-badge"><span class="cat-name">' + escapeHtml(product.category_name || 'General') + '</span></div>' +
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
                    '<div class="profile-pic' + (product.is_online ? ' online' : '') + '">' +
                        '<img src="' + sellerImage + '" width="40" height="40" alt="' + escapeHtml(product.seller_name) + '" loading="lazy" onerror="this.src=\'' + baseUrl + 'images/icons/profile-svgrepo-com.svg\'">' +
                    '</div>' +
                    '<p class="seller-name">' + escapeHtml(product.seller_name) + '</p>' +
                '</div>' +
                '<div class="verification">' +
                    (product.is_verified ? 
                        '<span class="sp-badge verified">&#10003;</span>' : 
                        '<span class="sp-badge unverified">!</span>') +
                '</div>' +
                '<div class="contact-buttons">' + contactHtml + '</div>' +
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
                    '<img src="' + baseUrl + 'images/icons/Payfast logo.svg" alt="PayFast" loading="lazy">' +
                    '<span>Secure payments by PayFast</span>' +
                '</div>' +
            '</div>' +
        '</div>'
    );
    
    $('.small-img').on('click', function() {
        var newImagePath = $(this).data('image-path');
        $('#main-product-image').attr('src', newImagePath);
        $('.small-img').removeClass('active');
        $(this).addClass('active');
    });
    
    if (showReportButton) {
        $('#reportProductBtn').off('click').on('click', function(e) {
            e.stopPropagation();
            openReportModal(product.id);
        });
    }
}

// ============================================================
// REPORT MODAL FUNCTIONS
// ============================================================

function openReportModal(productId) {
    currentProductId = productId;

    $('#reportReason').val('');
    $('#reportDescription').val('');
    $('#reportErrorContainer').hide().empty();

    openModal($('#reportModal'));
}

function closeReportModal() {
    closeModal($('#reportModal'));
}

function initReportModal() {
    $('#closeReportModalBtn, #cancelReportBtn').off('click').on('click', function() {
        closeReportModal();
    });

    $('#reportModal').off('click').on('click', function(e) {
        if ($(e.target).is($('#reportModal'))) {
            closeReportModal();
        }
    });

    $('#reportForm').off('submit').on('submit', function(e) {
        e.preventDefault();

        var reason = $('#reportReason').val();
        var description = $('#reportDescription').val();

        if (!reason) {
            $('#reportErrorContainer').show().addClass('error-message').html('Please select a reason for reporting.');
            return;
        }

        $('#submitReportBtn').prop('disabled', true).text('Submitting...');

        $.ajax({
            url: baseUrl + 'php/endpoints/reports/report-product.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                product_id: currentProductId,
                reason: reason,
                description: description
            }),
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    showSuccessToast(data.message);
                    closeReportModal();
                    $('#reportProductBtn').prop('disabled', true).addClass('disabled');
                } else {
                    $('#reportErrorContainer').show().addClass('error-message').html(data.message);
                }
            },
            error: function() {
                $('#reportErrorContainer').show().addClass('error-message').html('Something went wrong. Please try again.');
            },
            complete: function() {
                $('#submitReportBtn').prop('disabled', false).text('Submit Report');
            }
        });
    });
}

// ============================================================
// CART FUNCTIONS
// ============================================================

/**
 * Add product to cart via AJAX.
 * Uses showSuccessToast and showErrorToast from utils.js
 */
function addToCart(productId, productName, price) {
    if (!isLoggedIn) {
        openModal($('#login-modal'));
        return;
    }

    $.ajax({
        url: baseUrl + 'php/endpoints/cart/add.php',
        type: 'POST',
        data: {
            product_id: productId,
            quantity: 1
        },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                showSuccessToast(productName + ' added to cart');
                // Update cart count
                if (data.cart_count !== undefined) {
                    $('.cart-badge').text(data.cart_count);
                } else {
                    // Reload page to update cart count
                    location.reload();
                }
            } else {
                showErrorToast(data.message || 'Failed to add to cart');
            }
        },
        error: function() {
            showErrorToast('Something went wrong. Please try again.');
        }
    });
}

/**
 * Buy now - redirect to checkout.
 */
function buyNow(productId, productName, price) {
    if (!isLoggedIn) {
        openModal($('#login-modal'));
        return;
    }

    // Add to cart first, then redirect to cart page
    $.ajax({
        url: baseUrl + 'php/endpoints/cart/add.php',
        type: 'POST',
        data: {
            product_id: productId,
            quantity: 1
        },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                window.location.href = baseUrl + 'cart.php';
            } else {
                showErrorToast(data.message || 'Failed to add to cart');
            }
        },
        error: function() {
            showErrorToast('Something went wrong. Please try again.');
        }
    });
}

// ============================================================
// DOCUMENT READY - WITH JQUERY LOAD CHECK
// ============================================================

(function waitForJQuery() {
    if (typeof jQuery !== 'undefined') {
        jQuery(function() {
            // Cache elements
            cacheProductElements();

            // Initialize product listings or search
            if ($('#products-grid').length) {
                var isSearchPage = window.location.pathname.includes('search-results.php');
                if (isSearchPage) {
                    var urlParams = new URLSearchParams(window.location.search);
                    currentSearchQuery = urlParams.get('search') || '';
                }

                window.loadProducts = loadProducts;
                loadProducts();
                setupProductEventListeners();
            }

            // Initialize product details
            if ($('.product-details-container').length) {
                var productId = $('.product-details-container').data('product-id');
                if (productId > 0) {
                    loadProductDetails(productId);
                }
                initReportModal();
            }
        });
    } else {
        setTimeout(waitForJQuery, 50);
    }
})();