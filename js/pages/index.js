/**
 * ConsuTrade - Homepage
 * Only dynamic content (stats, products, sellers)
 */

function loadHeroStats() {
    var stats = window.heroStats;
    if (!stats) return;

    var $items = $('#hero-stats-container .stat-item');
    if ($items.length < 3) return;

    // Active traders
    $items.eq(0).find('.stat-number')
        .removeClass('skeleton skeleton-stat-number')
        .text(stats.activeSellers.toLocaleString());
    $items.eq(0).find('.stat-label')
        .removeClass('skeleton skeleton-stat-label')
        .text(t('active_traders'));

    // Items listed
    $items.eq(1).find('.stat-number')
        .removeClass('skeleton skeleton-stat-number')
        .text(stats.totalListings.toLocaleString());
    $items.eq(1).find('.stat-label')
        .removeClass('skeleton skeleton-stat-label')
        .text(t('items_listed'));

    // Trades completed
    $items.eq(2).find('.stat-number')
        .removeClass('skeleton skeleton-stat-number')
        .text(stats.tradesCompleted + '%');
    $items.eq(2).find('.stat-label')
        .removeClass('skeleton skeleton-stat-label')
        .text(t('trades_completed'));
}

function loadHeroProducts() {
    var $container = $('#hero-products .hero-card-body');

    $.ajax({
        url: baseUrl + 'php/endpoints/products/get-products.php?limit=3&sort=newest',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success && data.products && data.products.length > 0) {
                // Remove skeleton
                $('#hero-products-skeleton').remove();
                $container.empty();

                data.products.forEach(function(product, index) {
                    var imageUrl = product.display_image || product.image || product.image_url || 'images/default-product.png';
                    var productName = product.name || product.title || 'Product';
                    var productPrice = parseFloat(product.price || 0);

                    var borderStyle = (index < data.products.length - 1) ? 'border-bottom:1px solid var(--border-light);' : '';

                    var row = $(
                        '<a href="product-details.php?id=' + product.id + '" style="display:flex;align-items:center;gap:var(--spacing-md);padding:var(--spacing-sm) 0;' + borderStyle + 'text-decoration:none;">' +
                            '<div style="width:52px;height:52px;border-radius:var(--radius-md);overflow:hidden;flex-shrink:0;background:var(--gray-bg);">' +
                                '<img src="' + imageUrl + '" alt="' + escapeHtml(productName) + '" style="width:100%;height:100%;object-fit:cover;" loading="lazy" onerror="this.src=\'images/default-product.png\'">' +
                            '</div>' +
                            '<div style="flex:1;">' +
                                '<strong style="display:block;font-size:var(--font-sm);color:var(--dark-bg);">' + escapeHtml(productName) + '</strong>' +
                                '<span style="font-size:var(--font-sm);color:var(--primary-color);font-weight:var(--font-bold);">R ' + productPrice.toFixed(0) + '</span>' +
                            '</div>' +
                        '</a>'
                    );
                    $container.append(row);
                });
            }
        },
        error: function() {
            $('#hero-products-skeleton').replaceWith(
                '<p style="color:var(--gray-medium);font-size:var(--font-sm);">' + t('error_loading_products') + '</p>'
            );
        }
    });
}

function loadFeaturedProducts() {
    var $grid = $('#featured-products-grid');

    $.ajax({
        url: baseUrl + 'php/endpoints/products/get-products.php?limit=6&page=1',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success && data.products && data.products.length > 0) {
                displayProducts(data.products, '#featured-products-grid');
            } else {
                showFeaturedEmptyState();
            }
        },
        error: function() {
            $grid.html(
                '<div class="empty-state">' +
                '<img src="' + baseUrl + 'images/icons/error-svgrepo-com.svg" width="64" height="64" alt="Error" loading="lazy">' +
                '<h3>' + t('error_loading_products') + '</h3>' +
                '<p>' + t('error_loading_products') + '</p>' +
                '</div>'
            );
        }
    });
}

function loadTopSellers() {
    var $grid = $('#sellers-grid');

    $.ajax({
        url: baseUrl + 'php/endpoints/users/get-top-sellers.php?limit=4',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success && data.sellers && data.sellers.length > 0) {
                $grid.empty();

                data.sellers.forEach(function(seller) {
                    var initials = (seller.full_name || 'U').substring(0, 1);
                    var verifiedBadge = seller.id_verified ?
                        '<span class="verified-badge-card">&#10003;</span>' :
                        '<span class="unverified-badge-card">!</span>';

                    var productCount = seller.product_count || 0;
                    var rating = seller.rating || 0;
                    var trades = seller.trades || 0;

                    // FIXED: Use translation for location fallback
                    var location = seller.location ? escapeHtml(seller.location) : t('south_africa');

                    var card = $(
                        '<div class="seller-card">' +
                            '<div class="seller-card-top">' +
                                '<div class="seller-avatar">' + initials + '</div>' +
                                '<div>' +
                                    '<h4>' + escapeHtml(seller.full_name) + '</h4>' +
                                    '<span>' + location + '</span>' +
                                '</div>' +
                                verifiedBadge +
                            '</div>' +
                            '<div class="seller-card-stats">' +
                                '<div>' +
                                    '<span class="stat-number">' + productCount + '</span>' +
                                    '<span class="stat-label">' + t('listings') + '</span>' +
                                '</div>' +
                                '<div>' +
                                    '<span class="stat-number">' + rating.toFixed(1) + '</span>' +
                                    '<span class="stat-label">' + t('rating') + '</span>' +
                                '</div>' +
                                '<div>' +
                                    '<span class="stat-number">' + trades + '</span>' +
                                    '<span class="stat-label">' + t('trades') + '</span>' +
                                '</div>' +
                            '</div>' +
                            '<a href="seller-profile-public.php?seller_id=' + seller.user_id + '" class="seller-link">' + t('view_shop') + ' →</a>' +
                        '</div>'
                    );
                    $grid.append(card);
                });
            } else {
                $grid.html(
                    '<div class="empty-state" style="grid-column:1/-1;">' +
                    '<h3>' + t('no_products_found') + '</h3>' +
                    '<p>' + t('no_products_found') + '</p>' +
                    '</div>'
                );
            }
        },
        error: function() {
            $grid.html(
                '<div class="empty-state" style="grid-column:1/-1;">' +
                '<h3>' + t('error_loading_products') + '</h3>' +
                '<p>' + t('error_loading_products') + '</p>' +
                '</div>'
            );
        }
    });
}

function showFeaturedEmptyState() {
    $('#featured-products-grid').html(
        '<div class="empty-state">' +
        '<img src="' + baseUrl + 'images/icons/product-catalog-svgrepo-com.svg" width="64" height="64" alt="No products" loading="lazy">' +
        '<h3>' + t('no_products_found') + '</h3>' +
        '<p>' + t('no_products_found') + '</p>' +
        '<a href="' + baseUrl + 'sell.php" class="view-all-btn" style="display:inline-block;">' + t('start_selling') + '</a>' +
        '</div>'
    );
}

function initLocationSearch() {
    var $input = $('#location-input');
    var $btn = $('#location-btn');
    var $nearbyCount = $('#nearby-count');
    var $sellerCount = $('#seller-count');
    var $distanceCount = $('#distance-count');

    function updateLocation(location) {
        if (!location || location.length < 2) {
            $nearbyCount.text('-');
            $sellerCount.text('-');
            $distanceCount.text('-');
            return;
        }

        $.ajax({
            url: baseUrl + 'php/endpoints/location/nearby.php',
            type: 'GET',
            data: { q: location },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    $nearbyCount.text(data.total_products || '0');
                    $sellerCount.text(data.total_sellers || '0');
                    $distanceCount.text(data.avg_distance || t('distance_estimate')); // FIXED
                }
            },
            error: function() {
                var productCount = Math.floor(Math.random() * 30) + 5;
                var sellerCount = Math.floor(Math.random() * 15) + 3;
                $nearbyCount.text(productCount);
                $sellerCount.text(sellerCount);
                $distanceCount.text(t('distance_estimate')); // FIXED
            }
        });
    }

    $btn.on('click', function() {
        updateLocation($input.val());
    });

    $input.on('keypress', function(e) {
        if (e.key === 'Enter') {
            updateLocation($input.val());
        }
    });

    $('.pill').on('click', function() {
        var loc = $(this).data('location');
        $input.val(loc);
        updateLocation(loc);
    });
}

$(function() {
    loadHeroStats();
    loadHeroProducts();
    loadFeaturedProducts();
    loadTopSellers();
    initLocationSearch();

    $('#primary-btn').on('click', function() {
        if (isLoggedIn && currentUserRole === 'seller') {
            window.location.href = baseUrl + 'profile.php';
        } else if (isLoggedIn) {
            window.location.href = baseUrl + 'sell.php';
        } else {
            openModal($('#register-modal'));
            $('#seller').prop('checked', true);
            $('#register-modal .modal-header p').text(t('create_account'));
        }
    });

    // CTA button at bottom - opens register modal or redirects
    $('#cta-register-btn').on('click', function(e) {
        e.preventDefault();
        if (isLoggedIn) {
            window.location.href = baseUrl + 'product-listings.php';
        } else {
            openModal($('#register-modal'));
        }
    });
});