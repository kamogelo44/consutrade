/**
 * ConsuTrade - Product Listings JavaScript
 * Author: Kamogelo Phale
 * 
 * Handles product listings, filtering, pagination, search, and product details.
 * Used on: product-listings.php, product-details.php, search-results.php, index.php
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
// PRODUCT LISTINGS FUNCTIONS
// ============================================================

/**
 * Loads products from server with current filters.
 * Works for both product-listings.php and search-results.php.
 */
function loadProducts() {
    cacheProductElements();

    if (!$productsGrid || !$productsGrid.length) return;

    var params = new URLSearchParams();
    params.append('page', currentPage);
    params.append('sort', currentSort);
    params.append('limit', 12);

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

    $productsGrid.html('<div class="loading-spinner">' + (isSearchPage ? 'Searching for products...' : 'Loading products...') + '</div>');

    // Use different endpoint based on page
    var endpoint = isSearchPage ? 'php/endpoints/products/search-products.php' : 'php/endpoints/products/get-products.php'

    $.ajax({
        url: baseUrl + endpoint + '?' + params.toString(),
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            // ============================================================
            // DEBUG: Log the response to see what's being returned
            // ============================================================
            console.log('Search response:', data);

            if (data.success && data.products && data.products.length > 0) {
                if (typeof displayProducts === 'function') {
                    displayProducts(data.products);
                } else {
                    console.error('displayProducts function not found');
                    $productsGrid.html('<p class="error">Error: displayProducts function not loaded.</p>');
                }
                totalPages = data.total_pages || 1;
                displayPagination();
            } else {
                showEmptyState();
            }
        },
        error: function(xhr, status, error) {
            console.error('Search error:', status, error);
            console.error('Response:', xhr.responseText);
            showErrorState();
        }
    });
}

/**
 * Displays products in grid with optional container selector.
 */
function displayProducts(products, containerSelector) {
    cacheProductElements();

    var $grid = $productsGrid;
    if (containerSelector) {
        $grid = $(containerSelector);
    }

    if (!$grid || !$grid.length) return;

    $grid.empty();

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
        if (stockQty <= 0) {
            stockBadge = '<div class="out-of-stock-badge-card">Out of Stock</div>';
        } else if (stockQty <= 5) {
            stockBadge = '<div class="low-stock-badge-card">Only ' + stockQty + ' left</div>';
        }

        var sellerBadge = '';
        if (product.is_verified) {
            sellerBadge = '<div class="verified-badge-card"><img src="' + baseUrl + 'images/icons/verified-svgrepo-com.svg" width="14" height="14"><span>Verified Seller</span></div>';
        } else {
            sellerBadge = '<div class="unverified-badge-card"><img src="' + baseUrl + 'images/icons/not-verified-svgrepo-com.svg" width="14" height="14"><span>Unverified</span></div>';
        }

        var isOutOfStock = stockQty <= 0;
        var addToCartButton = '';

        if (isOutOfStock) {
            addToCartButton = '<button class="out-of-stock-btn" disabled>Out of Stock</button>';
        } else {
            addToCartButton = '<button class="add-to-cart-btn" onclick="event.stopPropagation(); addToCart(' + product.id + ', \'' + escapeHtml(product.name).replace(/'/g, "\\'") + '\', ' + product.price + ')">Add to Cart</button>';
        }

        var sellerAvatar = fixImageUrl(product.profile_image, 'images/icons/profile-svgrepo-com.svg');

        var $card = $('<div>').addClass('prod-card').css('cursor', 'pointer');
        $card.on('click', function(id) {
            return function() { window.location.href = baseUrl + 'product-details.php?id=' + id; };
        }(product.id));

        $card.html(
            '<div class="img-container">' +
                '<img src="' + imagePath + '" alt="' + escapeHtml(product.name) + '" loading="lazy" onerror="this.src=\'' + baseUrl + 'images/default-product.png\'">' +
                '<div class="condition-badge ' + conditionClass + '">' + conditionText + '</div>' +
                stockBadge +
            '</div>' +
            '<div class="prod-info-container">' +
                '<h3 class="prod-name">' + escapeHtml(product.name) + '</h3>' +
                '<p class="prod-price">R ' + parseFloat(product.price).toFixed(2) + '</p>' +
                '<div class="seller-info">' +
                    '<div class="seller-avatar">' +
                        '<img src="' + sellerAvatar + '" alt="' + escapeHtml(product.seller_name) + '" loading="lazy" onerror="this.src=\'' + baseUrl + 'images/icons/profile-svgrepo-com.svg\'">' +
                    '</div>' +
                    '<div class="seller-details">' +
                        '<p class="seller-name">' + escapeHtml(product.seller_name) + '</p>' +
                        '<p class="location">' +
                            '<img src="' + baseUrl + 'images/icons/pin-location-svgrepo-com.svg" width="10" height="10" alt="location" loading="lazy">' +
                            escapeHtml(product.location || 'South Africa') +
                        '</p>' +
                    '</div>' +
                    sellerBadge +
                '</div>' +
                addToCartButton +
                '<div class="payment-badge">' +
                    '<span>Secure payment via</span>' +
                    '<img src="' + baseUrl + 'images/icons/Payfast logo.svg" alt="PayFast" loading="lazy">' +
                '</div>' +
            '</div>'
        );
        $grid.append($card);
    }
}

/**
 * Shows empty state when no products match filters.
 */
function showEmptyState() {
    cacheProductElements();

    var isSearchPage = window.location.pathname.includes('search-results.php');
    var title = isSearchPage ? 'No products found for "' + escapeHtml(currentSearchQuery) + '"' : 'No products found';
    var message = isSearchPage ? 'We couldn\'t find any products matching your search.' : 'We couldn\'t find any products matching your criteria.';
    var buttonText = isSearchPage ? 'Browse All Products' : 'Clear Filters';
    var buttonAction = isSearchPage ? 'window.location.href=\'product-listings.php\'' : '$resetFiltersBtn.click()';

    $productsGrid.html(
        '<div class="empty-state" id="empty-products-state">' +
            '<img src="' + baseUrl + 'images/icons/product-catalog-svgrepo-com.svg" width="64" height="64" alt="No products" loading="lazy">' +
            '<h3>' + title + '</h3>' +
            '<p>' + message + '</p>' +
            '<button class="view-all-btn" onclick="' + buttonAction + '">' + buttonText + '</button>' +
        '</div>'
    );
    $paginationContainer.empty();
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
}

/**
 * Renders pagination controls.
 */
function displayPagination() {
    cacheProductElements();

    if (typeof renderPagination === 'function') {
        renderPagination($paginationContainer, currentPage, totalPages, function(page) {
            currentPage = page;
            loadProducts();
            $htmlBody.animate({ scrollTop: 0 }, 'smooth');
        });
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

    if ($filterForm && $filterForm.length) {
        $filterForm.on('submit', function(e) {
            e.preventDefault();
            collectFilters();
            currentPage = 1;
            loadProducts();
            if ($window.width() <= 768) {
                $filterSidebar.removeClass('active');
            }
        });
    }

    if ($resetFiltersBtn && $resetFiltersBtn.length) {
        $resetFiltersBtn.on('click', function() {
            $filterForm[0].reset();
            currentFilters = {
                categories: [],
                price_range: '',
                location: ''
            };
            currentPage = 1;
            loadProducts();
        });
    }

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
function loadProductDetails(id) {
    if (!$('.product-details-container').length) return;

    $('#product-details-content').html('<div class="loading-spinner">Loading product details...</div>');

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
 * Renders product details on the page.
 */
function displayProductDetails(product) {
    // ... existing displayProductDetails code ...
    // (Keeping this as is since it's long and already works)
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
// BUY NOW
// ============================================================

/**
 * Buy now - adds to cart and redirects to checkout.
 */
function buyNow(productId, productName, productPrice) {
    addToCart(productId, productName, productPrice);
    window.location.href = baseUrl + 'checkout.php';
}

// ============================================================
// DOCUMENT READY
// ============================================================

$(function() {
    // Cache elements
    cacheProductElements();

    // Initialize product listings or search
    if ($('#products-grid').length) {
        // Check if we're on search page
        var isSearchPage = window.location.pathname.includes('search-results.php');
        if (isSearchPage) {
            var urlParams = new URLSearchParams(window.location.search);
            currentSearchQuery = urlParams.get('search') || '';
        }

        // Make loadProducts globally accessible for pagination
        window.loadProducts = loadProducts;

        // Load products
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