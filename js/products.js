// products.js - handles product listings, filtering, pagination, product details
// Author: Kamogelo Phale

// product listings page variables
var currentPage = 1;
var currentFilters = {};
var currentSort = 'newest';
var totalPages = 1;

// load products from server with filters
function loadProducts() {
    var params = new URLSearchParams();
    params.append('page', currentPage);
    params.append('sort', currentSort);
    params.append('limit', 12);
    
    if (currentFilters.categories && currentFilters.categories.length > 0) {
        params.append('categories', currentFilters.categories.join(','));
    }
    if (currentFilters.price_range) params.append('price_range', currentFilters.price_range);
    if (currentFilters.location) params.append('location', currentFilters.location);
    
    $('#products-grid').html('<div class="loading-spinner">Loading products...</div>');
    
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

// display products in grid
function displayProducts(products) {
    var $grid = $('#products-grid');
    $grid.empty();
    
    for (var i = 0; i < products.length; i++) {
        var product = products[i];
        var imagePath = fixImageUrl(product.display_image || product.image || product.image_url);
        
        // condition class for badge
        var conditionText = product.condition || 'Good';
        var conditionClass = 'good';
        if (conditionText == 'New') conditionClass = 'new';
        else if (conditionText == 'Like New') conditionClass = 'like-new';
        else if (conditionText == 'Good') conditionClass = 'good';
        else if (conditionText == 'Fair') conditionClass = 'fair';
        
        // stock badge - default to 0, not 1
        var stockQty = parseInt(product.stock_quantity) || 0;
        var stockBadge = '';
        if (stockQty <= 0) {
            stockBadge = '<div class="out-of-stock-badge-card">Out of Stock</div>';
        } else if (stockQty <= 5) {
            stockBadge = '<div class="low-stock-badge-card">Only ' + stockQty + ' left</div>';
        }
        
        // seller verification badge
        var sellerBadge = '';
        if (product.is_verified) {
            sellerBadge = '<div class="verified-badge-card"><img src="' + baseUrl + 'images/icons/verified-svgrepo-com.svg" width="14" height="14"><span>Verified Seller</span></div>';
        } else {
            sellerBadge = '<div class="unverified-badge-card"><img src="' + baseUrl + 'images/icons/not-verified-svgrepo-com.svg" width="14" height="14"><span>Unverified</span></div>';
        }
        
        // button logic - out of stock OR add to cart
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

// show empty state when no products
function showEmptyState() {
    $('#products-grid').html(
        '<div class="empty-state" id="empty-products-state">' +
            '<img src="' + baseUrl + 'images/icons/product-catalog-svgrepo-com.svg" width="64" height="64" alt="No products" loading="lazy">' +
            '<h3>No products found</h3>' +
            '<p>We couldn\'t find any products matching your criteria.</p>' +
            '<button class="view-all-btn" id="resetFiltersEmptyBtn">Reset Filters</button>' +
        '</div>'
    );
    $('#pagination').empty();
    
    $('#resetFiltersEmptyBtn').off('click').on('click', function() {
        $('#filterForm')[0].reset();
        currentFilters = {};
        currentPage = 1;
        loadProducts();
    });
}

// show error state
function showErrorState() {
    $('#products-grid').html(
        '<div class="empty-state" id="error-products-state">' +
            '<img src="' + baseUrl + 'images/icons/error-svgrepo-com.svg" width="64" height="64" alt="Error" loading="lazy">' +
            '<h3>Something went wrong</h3>' +
            '<p>Error loading products. Please try again.</p>' +
            '<button class="view-all-btn" id="refreshPageBtn">Refresh Page</button>' +
        '</div>'
    );
    $('#pagination').empty();
    
    $('#refreshPageBtn').off('click').on('click', function() {
        location.reload();
    });
}

// display pagination controls
function displayPagination() {
    renderPagination($('#pagination'), currentPage, totalPages, function(page) {
        currentPage = page;
        loadProducts();
        $('html, body').animate({ scrollTop: 0 }, 'smooth');
    });
}

// collect filter values from form
function collectFilters() {
    var categories = [];
    $('input[name="category[]"]').each(function() {
        if ($(this).is(':checked')) {
            categories.push($(this).val());
        }
    });
    
    var $selectedPriceRange = $('input[name="price_range"]:checked');
    var priceRange = $selectedPriceRange.length ? $selectedPriceRange.val() : '';
    
    currentFilters = {
        categories: categories,
        price_range: priceRange,
        location: $('#search-location').val() || ''
    };
}

// setup event listeners for filtering
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
        currentSort = $('#sortBy').val();
        currentPage = 1;
        loadProducts();
    });
}

// ========== PRODUCT DETAILS PAGE ==========

var currentProductId = 0;

// load product details for single product page
function loadProductDetails(id) {
    if (!$('.product-details-container').length) return;
    
    $('#product-details-content').html('<div class="loading-spinner">Loading product details...</div>');
    
    $.get(baseUrl + 'php/endpoints/get-product.php?id=' + id, function(data) {
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

// display product details on the page
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
    
    // Stock status
    var stockQty = parseInt(product.stock_quantity) || 0;
    var isOutOfStock = stockQty <= 0;
    var isLowStock = stockQty > 0 && stockQty <= 5;
    var stockHtml = '';
    
    if (isOutOfStock) {
        stockHtml = '<div class="stock-status out-of-stock"><span class="stock-icon">✕</span> Out of Stock</div>';
    } else if (isLowStock) {
        stockHtml = '<div class="stock-status low-stock"><span class="stock-icon">⚠</span> Only ' + stockQty + ' left in stock!</div>';
    } else {
        stockHtml = '<div class="stock-status in-stock"><span class="stock-icon">✓</span> In Stock (' + stockQty + ' available)</div>';
    }
    
    var starsHtml = '';
    var avgRating = parseFloat(product.avg_rating) || 0;
    for (var i = 1; i <= 5; i++) {
        starsHtml += (i <= avgRating) ? '<span class="star">★</span>' : '<span class="star empty">★</span>';
    }
    
    var escapedName = escapeHtml(product.name).replace(/'/g, "\\'");
    
    // Action buttons
    var actionButtonsHtml = '';
    
    if (isOutOfStock) {
        actionButtonsHtml = '<button class="cart-btn out-of-stock-btn" disabled>Out of Stock</button>';
    } else {
        actionButtonsHtml = '<button class="cart-btn" onclick="addToCart(' + product.id + ', \'' + escapedName + '\', ' + product.price + ')">Add to Cart</button>' +
                            '<button class="buy-btn" onclick="buyNow(' + product.id + ', \'' + escapedName + '\', ' + product.price + ')">Buy Now</button>';
    }
    
    // report button - only for logged in buyers
    var isLoggedInFlag = (typeof isLoggedIn !== 'undefined' && isLoggedIn === true);
    var isBuyer = (typeof currentUserRole !== 'undefined' && currentUserRole == 'buyer');
    var showReportButton = isLoggedInFlag && isBuyer;
    
    if (showReportButton) {
        actionButtonsHtml += '<button class="report-btn" id="reportProductBtn">' +
                                '<img src="' + baseUrl + 'images/icons/warning-svgrepo-com.svg" width="16" height="16" alt="Report" loading="lazy"> Report This Product' +
                              '</button>';
    }
    
    // ========== CONTACT BUTTONS ==========
    var contactHtml = '';
    var sellerPhone = product.seller_phone || '';
    var sellerEmail = product.seller_email || '';
    
    // Format phone for WhatsApp (remove non-digits, add 27 prefix if needed)
    var whatsappNumber = '';
    if (sellerPhone) {
        var digits = sellerPhone.replace(/\D/g, '');
        // Remove leading 0 if present
        if (digits.startsWith('0')) {
            digits = digits.substring(1);
        }
        // Add 27 country code if not already there
        if (!digits.startsWith('27')) {
            digits = '27' + digits;
        }
        whatsappNumber = digits;
    }
    
    if (sellerPhone) {
        contactHtml += '<a href="https://wa.me/' + whatsappNumber + '" target="_blank" class="contact-btn whatsapp-btn">' +
                            '<img src="' + baseUrl + 'images/icons/whatsapp-svgrepo-com.svg" width="18" height="18" alt="WhatsApp" loading="lazy"> WhatsApp' +
                        '</a>';
    }
    if (sellerEmail) {
        contactHtml += '<a href="mailto:' + sellerEmail + '" class="contact-btn email-btn">' +
                            '<img src="' + baseUrl + 'images/icons/email-svgrepo-com.svg" width="16" height="16" alt="Email" loading="lazy"> Email Seller' +
                        '</a>';
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
                    '<div class="profile-pic">' +
                        '<img src="' + sellerImage + '" width="40" height="40" alt="' + escapeHtml(product.seller_name) + '" loading="lazy" onerror="this.src=\'' + baseUrl + 'images/icons/profile-svgrepo-com.svg\'">' +
                    '</div>' +
                    '<p class="seller-name">' + escapeHtml(product.seller_name) + '</p>' +
                '</div>' +
                '<div class="verification">' +
                    (product.is_verified ? 
                        '<div class="verified-badge"><img src="' + baseUrl + 'images/icons/verified-svgrepo-com.svg" width="20" height="20" loading="lazy"><p>Verified Seller</p></div>' : 
                        '<div class="not-verified-badge"><img src="' + baseUrl + 'images/icons/not-verified-svgrepo-com.svg" width="20" height="20" loading="lazy"><p>Not Verified</p></div>') +
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
    
    // setup gallery click handlers
    $('.small-img').on('click', function() {
        var newImagePath = $(this).data('image-path');
        $('#main-product-image').attr('src', newImagePath);
        $('.small-img').removeClass('active');
        $(this).addClass('active');
    });
    
    // report button handler
    if (showReportButton) {
        $('#reportProductBtn').off('click').on('click', function(e) {
            e.stopPropagation();
            openReportModal(product.id);
        });
    }
}

// report modal functions
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

// buy now - add to cart and go to checkout
function buyNow(productId, productName, productPrice) {
    addToCart(productId, productName, productPrice);
    window.location.href = baseUrl + 'checkout.php';
}

// initialize everything when document is ready
$(function() {
    if ($('#products-grid').length) {
        loadProducts();
        setupProductEventListeners();
    }
    
    if ($('.product-details-container').length) {
        var productId = $('.product-details-container').data('product-id');
        if (productId > 0) {
            loadProductDetails(productId);
        }
        initReportModal();
    }
});