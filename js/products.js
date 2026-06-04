// products.js
/*
 * ConsuTrade - Products JavaScript File
 * Author: Kamogelo Phale
 * 
 * Handles product listings, filtering, pagination, and product details
 * Relies on main.js for:
 *   - escapeHtml()
 *   - fixImageUrl()
 *   - addToCart()
 *   - renderPagination()
 *   - showSuccessToast()
 *   - showErrorToast()
 *   - openModal()
 *   - closeModal()
 */

// ========== PRODUCT LISTINGS PAGE ==========

/** @type {number} Current page number for pagination */
var currentPage = 1;

/** @type {Object} Current filter values (categories, price_range, location) */
var currentFilters = {};

/** @type {string} Current sort option ('newest', 'price_low', 'price_high') */
var currentSort = 'newest';

/** @type {number} Total number of pages available */
var totalPages = 1;

// Cached DOM elements
var $productsGrid = null;
var $paginationContainer = null;
var $filterSidebar = null;
var $filterForm = null;
var $sortBySelect = null;
var $resetFiltersBtn = null;
var $mobileFilterBtn = null;
var $window = null;
var $htmlBody = null;
var $categoryCheckboxes = null;
var $priceRangeRadios = null;
var $searchLocationInput = null;

/**
 * Caches all DOM elements used in the products page
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
 * Sets up event listeners for filtering and sorting
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
 * Shows empty state when no products are found
 */
function showEmptyState() {
    cacheProductsPageElements();
    
    $productsGrid.html(`
        <div class="empty-state" id="empty-products-state">
            <img src="${baseUrl}images/icons/product-catalog-svgrepo-com.svg" width="64" height="64" alt="No products">
            <h3>No products found</h3>
            <p>We couldn't find any products matching your criteria.</p>
            <button class="view-all-btn" id="resetFiltersEmptyBtn">Reset Filters</button>
        </div>
    `);
    $paginationContainer.empty();
    
    $('#resetFiltersEmptyBtn').off('click').on('click', function() {
        $filterForm[0].reset();
        currentFilters = {};
        currentPage = 1;
        loadProducts();
    });
}

/**
 * Shows error state when product loading fails
 */
function showErrorState() {
    cacheProductsPageElements();
    
    $productsGrid.html(`
        <div class="empty-state" id="error-products-state">
            <img src="${baseUrl}images/icons/error-svgrepo-com.svg" width="64" height="64" alt="Error">
            <h3>Something went wrong</h3>
            <p>Error loading products. Please try again.</p>
            <button class="view-all-btn" id="refreshPageBtn">Refresh Page</button>
        </div>
    `);
    $paginationContainer.empty();
    
    $('#refreshPageBtn').off('click').on('click', function() {
        location.reload();
    });
}

/**
 * Gets CSS class for condition badge
 */
function getConditionClass(condition) {
    var text = condition || 'Good';
    if (text === 'New') return 'new';
    if (text === 'Like New') return 'like-new';
    if (text === 'Good') return 'good';
    if (text === 'Fair') return 'fair';
    return 'good';
}

/**
 * Renders stock badge HTML
 */
function renderStockBadge(stockQuantity) {
    var qty = parseInt(stockQuantity) || 1;
    if (qty <= 0) {
        return '<div class="out-of-stock-badge-card">Out of Stock</div>';
    }
    if (qty <= 5) {
        return '<div class="low-stock-badge-card">Only ' + qty + ' left</div>';
    }
    return '';
}

/**
 * Renders seller verification badge
 */
function renderSellerBadge(isVerified) {
    if (isVerified) {
        return '<div class="verified-badge-card"><img src="' + baseUrl + 'images/icons/verified-svgrepo-com.svg" width="14" height="14"><span>Verified Seller</span></div>';
    }
    return '<div class="unverified-badge-card"><img src="' + baseUrl + 'images/icons/not-verified-svgrepo-com.svg" width="14" height="14"><span>Unverified</span></div>';
}

/**
 * Loads products from server with current filters and pagination
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
 */
function displayProducts(products) {
    if (!$productsGrid) {
        cacheProductsPageElements();
    }
    $productsGrid.empty();
    
    $.each(products, function(index, product) {
        var imagePath = fixImageUrl(product.display_image || product.image || product.image_url);
        var conditionClass = getConditionClass(product.condition);
        var conditionText = product.condition || 'Good';
        var stockBadge = renderStockBadge(product.stock_quantity);
        var sellerBadge = renderSellerBadge(product.is_verified);
        var isOutOfStock = (product.stock_quantity || 1) <= 0;
        var isOwnProduct = (typeof currentUserRole !== 'undefined' && currentUserRole === 'seller' && product.seller_id == currentUserId);
        
        var addToCartButton = '';
        if (!isOwnProduct && !isOutOfStock) {
            addToCartButton = '<button class="add-to-cart-btn" onclick="event.stopPropagation(); addToCart(' + product.id + ', \'' + escapeHtml(product.name).replace(/'/g, "\\'") + '\', ' + product.price + ')">Add to Cart</button>';
        } else if (isOutOfStock) {
            addToCartButton = '<button class="out-of-stock-btn" disabled>Out of Stock</button>';
        } else if (isOwnProduct) {
            addToCartButton = '<button class="own-product-btn" disabled>Your Product</button>';
        }
        
        var sellerAvatar = fixImageUrl(product.profile_image, 'images/icons/profile-svgrepo-com.svg');
        
        var $card = $('<div>').addClass('prod-card').css('cursor', 'pointer');
        $card.on('click', function() {
            window.location.href = baseUrl + 'product-details.php?id=' + product.id;
        });
        
        $card.html(`
            <div class="img-container">
                <img src="${imagePath}" alt="${escapeHtml(product.name)}" onerror="this.src='${baseUrl}images/default-product.png'">
                <div class="condition-badge ${conditionClass}">${conditionText}</div>
                ${stockBadge}
            </div>
            <div class="prod-info-container">
                <h3 class="prod-name">${escapeHtml(product.name)}</h3>
                <p class="prod-price">R ${parseFloat(product.price).toFixed(2)}</p>
                <div class="seller-info">
                    <div class="seller-avatar">
                        <img src="${sellerAvatar}" alt="${escapeHtml(product.seller_name)}" onerror="this.src='${baseUrl}images/icons/profile-svgrepo-com.svg'">
                    </div>
                    <div class="seller-details">
                        <p class="seller-name">${escapeHtml(product.seller_name)}</p>
                        <p class="location">
                            <img src="${baseUrl}images/icons/pin-location-svgrepo-com.svg" width="10" height="10" alt="location">
                            ${escapeHtml(product.location || 'South Africa')}
                        </p>
                    </div>
                    ${sellerBadge}
                </div>
                ${addToCartButton}
                <div class="payment-badge">
                    <span>Secure payment via</span>
                    <img src="${baseUrl}images/icons/Payfast logo.svg" alt="PayFast">
                </div>
            </div>
        `);
        $productsGrid.append($card);
    });
}

/**
 * Renders pagination controls using shared renderPagination function
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

// ========== PRODUCT DETAILS PAGE ==========

var $productDetailsContainer = null;
var $productDetailsContent = null;
var $mainProductImage = null;
var $smallImgElements = null;
var $galleryContainer = null;

// Report modal variables
var $reportModal = null;
var $reportForm = null;
var $reportReason = null;
var $reportDescription = null;
var $reportErrorContainer = null;
var $submitReportBtn = null;
var currentProductId = 0;

function cacheProductDetailsElements() {
    $productDetailsContainer = $('.product-details-container');
    $productDetailsContent = $('#product-details-content');
    $mainProductImage = $('#main-product-image');
    $galleryContainer = $('#gallery-container');
}

function cacheReportElements() {
    $reportModal = $('#reportModal');
    $reportForm = $('#reportForm');
    $reportReason = $('#reportReason');
    $reportDescription = $('#reportDescription');
    $reportErrorContainer = $('#reportErrorContainer');
    $submitReportBtn = $('#submitReportBtn');
}

function openReportModal() {
    cacheReportElements();
    if (!$reportModal.length) return;
    
    currentProductId = parseInt($('.product-details-container').data('product-id')) || 0;
    $reportReason.val('');
    $reportDescription.val('');
    $reportErrorContainer.hide().empty();
    
    if (typeof openModal === 'function') {
        openModal($reportModal);
    } else {
        $reportModal.addClass('active');
        $('body').css('overflow', 'hidden');
    }
}

function closeReportModal() {
    if (!$reportModal || !$reportModal.length) return;
    if (typeof closeModal === 'function') {
        closeModal($reportModal);
    } else {
        $reportModal.removeClass('active');
        $('body').css('overflow', '');
    }
}

function initReportModal() {
    cacheReportElements();
    if (!$reportModal.length) return;
    
    $('#closeReportModalBtn, #cancelReportBtn').off('click').on('click', function() {
        closeReportModal();
    });
    
    $reportModal.off('click').on('click', function(e) {
        if ($(e.target).is($reportModal)) {
            closeReportModal();
        }
    });
    
    $reportForm.off('submit').on('submit', function(e) {
        e.preventDefault();
        
        var reason = $reportReason.val();
        var description = $reportDescription.val();
        
        if (!reason) {
            $reportErrorContainer.show().addClass('error-message').html('Please select a reason for reporting.');
            return;
        }
        
        $submitReportBtn.prop('disabled', true).text('Submitting...');
        
        $.ajax({
            url: baseUrl + 'php/endpoints/report-product.php',
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
                    if (typeof showSuccessToast === 'function') {
                        showSuccessToast(data.message);
                    } else {
                        alert(data.message);
                    }
                    closeReportModal();
                    $('#reportProductBtn').prop('disabled', true).addClass('disabled');
                } else {
                    $reportErrorContainer.show().addClass('error-message').html(data.message);
                }
            },
            error: function() {
                $reportErrorContainer.show().addClass('error-message').html('Something went wrong. Please try again.');
            },
            complete: function() {
                $submitReportBtn.prop('disabled', false).text('Submit Report');
            }
        });
    });
}

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

function showProductError(message) {
    if (!$productDetailsContainer) {
        cacheProductDetailsElements();
    }
    if (!$productDetailsContainer.length) return;
    
    $productDetailsContainer.html(`
        <div class="product-error-container">
            <h2 class="product-error-title">Product Not Found</h2>
            <p class="product-error-message-text">${escapeHtml(message)}</p>
            <button class="product-error-action-btn" onclick="window.location.href='${baseUrl}product-listings.php'">Browse Products</button>
        </div>
    `);
}

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
        galleryHtml += `<div class="small-img ${isActive}" data-image-path="${thumbnails[i]}">
                            <img src="${thumbnails[i]}" alt="Thumbnail ${i+1}" onerror="this.src='${baseUrl}images/default-product.png'">
                        </div>`;
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
    
    var escapedName = escapeHtml(product.name).replace(/'/g, "\\'");
    var actionButtonsHtml = '';
    
    if (!isOutOfStock) {
        actionButtonsHtml = `<button class="cart-btn" onclick="addToCart(${product.id}, '${escapedName}', ${product.price})">Add to Cart</button>
                             <button class="buy-btn" onclick="buyNow(${product.id}, '${escapedName}', ${product.price})">Buy Now</button>`;
    } else {
        actionButtonsHtml = '<button class="cart-btn out-of-stock-btn" disabled>Out of Stock</button>';
    }
    
    var isLoggedInFlag = (typeof isLoggedIn !== 'undefined' && isLoggedIn === true);
    var isBuyerRole = (typeof currentUserRole !== 'undefined' && currentUserRole === 'buyer');
    var isNotOwner = (typeof currentUserId !== 'undefined' && currentUserId != product.seller_id);
    var showReportButton = isLoggedInFlag && isBuyerRole && isNotOwner;
    
    if (showReportButton) {
        actionButtonsHtml += `<button class="report-btn" id="reportProductBtn">
                                <img src="${baseUrl}images/icons/warning-svgrepo-com.svg" width="16" height="16" alt="Report"> Report This Product
                              </button>`;
    }
    
    var sellerImage = fixImageUrl(product.seller_profile_image);
    
    $productDetailsContent.html(`
        <div class="top-items">
            <div class="product-imgs">
                <div class="main-img">
                    <img src="${mainImage}" alt="${escapeHtml(product.name)}" id="main-product-image" onerror="this.src='${baseUrl}images/default-product.png'">
                </div>
                <div class="smaller-imgs" id="gallery-container">${galleryHtml}</div>
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
                    ${product.condition ? '<p><strong>Condition:</strong> ' + escapeHtml(product.condition) + '</p>' : ''}
                    ${product.location ? '<p><strong>Location:</strong> ' + escapeHtml(product.location) + '</p>' : ''}
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
                        '<div class="verified-badge"><img src="' + baseUrl + 'images/icons/verified-svgrepo-com.svg" width="20" height="20"><p>Verified Seller</p></div>' : 
                        '<div class="not-verified-badge"><img src="' + baseUrl + 'images/icons/not-verified-svgrepo-com.svg" width="20" height="20"><p>Not Verified</p></div>'}
                </div>
                <div class="star-reviews">
                    <h1>Seller Reviews</h1>
                    ${starsHtml}
                    <p>Rating: ${avgRating.toFixed(1)}/5 (${product.review_count || 0} reviews)</p>
                </div>
                <button class="view-profile" onclick="window.location.href='${baseUrl}seller-profile-public.php?seller_id=${product.seller_id}&product_id=${product.id}&product_name=${encodeURIComponent(product.name)}'">View Seller Profile</button>
            </div>
        </section>
        <div class="actions">
            <div class="actions-card">
                <div class="action-btns">${actionButtonsHtml}</div>
                <div class="payfast-badge">
                    <img src="${baseUrl}images/icons/Payfast logo.svg" alt="PayFast">
                    <span>Secure payments by PayFast</span>
                </div>
            </div>
        </div>
    `);
    
    $mainProductImage = $('#main-product-image');
    $smallImgElements = $('.small-img');
    
    $smallImgElements.on('click', function() {
        var $this = $(this);
        var newImagePath = $this.data('image-path');
        $mainProductImage.attr('src', newImagePath);
        $smallImgElements.removeClass('active');
        $this.addClass('active');
    });
    
    if (showReportButton) {
        $('#reportProductBtn').off('click').on('click', function(e) {
            e.stopPropagation();
            openReportModal();
        });
    }
}

function buyNow(productId, productName, productPrice) {
    addToCart(productId, productName, productPrice);
    window.location.href = baseUrl + 'checkout.php';
}

// ========== INITIALIZE ==========
$(function() {
    if ($('#products-grid').length) {
        cacheProductsPageElements();
        loadProducts();
        setupProductEventListeners();
    }
    
    if ($('.product-details-container').length) {
        cacheProductDetailsElements();
        var productId = $('.product-details-container').data('product-id');
        if (productId > 0) {
            loadProductDetails(productId);
        }
        initReportModal();
    }
});