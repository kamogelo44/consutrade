/**
 * Seller Profile - All data loaded via AJAX
 * Depends on: utils.js, products.js (for displayProducts)
 */

$(function() {
    // Get data passed from PHP
    var sellerId = window.sellerProfileId;
    var sellerNameFromPage = window.sellerProfileName || 'Seller';
    var sellerLocationFromPage = window.sellerProfileLocation || '';
    var sellerProfileImageFromPage = window.sellerProfileImage || baseUrl + 'images/icons/profile-svgrepo-com.svg';

    if (!sellerId || sellerId <= 0) {
        window.location.href = baseUrl + 'index.php';
        return;
    }

    // Helper: get initials from name
    function getInitials(name) {
        if (!name) return '?';
        var parts = name.split(' ');
        if (parts.length >= 2) {
            return (parts[0][0] + parts[1][0]).toUpperCase();
        }
        return name.substring(0, 2).toUpperCase();
    }

    // ============================================================
    // SELLER PROFILE (Storefront)
    // ============================================================
    function loadSellerProfile() {
        var $container = $('#sp-storefront-container');

        $.ajax({
            url: baseUrl + 'php/endpoints/users/get-user-stats.php?seller_id=' + sellerId + '&role=seller',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    displaySellerProfile(data);
                } else {
                    $container.html(
                        '<div class="sp-empty">' +
                        '<div class="sp-empty-icon">' +
                            '<img src="' + baseUrl + 'images/icons/error-svgrepo-com.svg" alt="Error" width="26" height="26">' +
                        '</div>' +
                        '<h3>' + t('seller_not_found') + '</h3>' +
                        '<p>' + t('seller_not_found_message') + '</p>' +
                        '</div>'
                    );
                }
            },
            error: function() {
                $container.html(
                    '<div class="sp-empty">' +
                    '<div class="sp-empty-icon">' +
                        '<img src="' + baseUrl + 'images/icons/error-svgrepo-com.svg" alt="Error" width="26" height="26">' +
                    '</div>' +
                    '<h3>' + t('error_loading_seller') + '</h3>' +
                    '<p>' + t('error_loading_seller_message') + '</p>' +
                    '</div>'
                );
            }
        });
    }

    function displaySellerProfile(data) {
        var $container = $('#sp-storefront-container');

        // Use data from API, fallback to page data
        var sellerName = data.seller_name || sellerNameFromPage;
        var profileImage = data.profile_image || sellerProfileImageFromPage;
        var location = data.location || sellerLocationFromPage;
        var memberSince = data.member_since || '';
        var productCount = data.total_products || 0;
        var isVerified = data.is_verified || false;

        var verifiedBadge = isVerified ?
            '<span class="sp-badge verified"><img src="' + baseUrl + 'images/icons/verified-svgrepo-com.svg" width="13" height="13" alt="Verified"> ' + t('verified') + '</span>' :
            '<span class="sp-badge unverified"><img src="' + baseUrl + 'images/icons/not-verified-svgrepo-com.svg" width="13" height="13" alt="Unverified"> ' + t('unverified') + '</span>';

        var html =
            '<div class="sp-storefront">' +
                '<div class="sp-cover"></div>' +
                '<div class="sp-storefront-body">' +
                    '<div class="sp-avatar-wrap">' +
                        '<img src="' + profileImage + '" alt="' + escapeHtml(sellerName) + '" loading="lazy" onerror="this.src=\'' + baseUrl + 'images/icons/profile-svgrepo-com.svg\'">' +
                    '</div>' +
                    '<div class="sp-storefront-info">' +
                        '<h1 class="sp-storefront-name">' + escapeHtml(sellerName) + '\'s <span>' + t('shop') + '</span></h1>' +
                        '<div class="sp-storefront-meta">' +
                            verifiedBadge +
                            (location ? '<span class="sp-location"><img src="' + baseUrl + 'images/icons/pin-location-svgrepo-com.svg" width="14" height="14" alt="Location"> ' + escapeHtml(location) + '</span>' : '') +
                            (memberSince ? '<span class="sp-since">' + t('member_since') + ' ' + escapeHtml(memberSince) + '</span>' : '') +
                        '</div>' +
                    '</div>' +
                    '<div class="sp-storefront-stat">' +
                        '<span class="number">' + productCount + '</span>' +
                        '<span class="label">' + t('products') + '</span>' +
                    '</div>' +
                '</div>' +
            '</div>';

        $container.html(html);
    }

    // ============================================================
    // SELLER STATS
    // ============================================================
    function loadSellerStats() {
        var $container = $('#sp-stats-container');

        $.ajax({
            url: baseUrl + 'php/endpoints/users/get-user-stats.php?seller_id=' + sellerId + '&role=seller',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    displaySellerStats(data);
                } else {
                    $container.html('');
                }
            },
            error: function() {
                $container.html('');
            }
        });
    }

    function displaySellerStats(data) {
        var $container = $('#sp-stats-container');

        var productCount = data.total_products || 0;
        var avgRating = data.avg_rating || 0;
        var reviewCount = data.total_reviews || 0;
        var memberSince = data.member_since || '';

        var starsHtml = '';
        if (avgRating > 0) {
            var fullStars = Math.floor(avgRating);
            for (var s = 1; s <= 5; s++) {
                starsHtml += (s <= fullStars) ? '<span class="star">★</span>' : '<span class="star empty">★</span>';
            }
            starsHtml = '<div class="stars">' + starsHtml + '</div>';
        }

        var html =
            '<div class="sp-quick-stats">' +
                '<div class="sp-quick-stat">' +
                    '<div class="sp-quick-stat-icon">' +
                        '<img src="' + baseUrl + 'images/icons/product-catalog-svgrepo-com.svg" alt="Products" width="22" height="22">' +
                    '</div>' +
                    '<div class="sp-quick-stat-content">' +
                        '<span class="number">' + productCount + '</span>' +
                        '<span class="label">' + t('products_listed') + '</span>' +
                    '</div>' +
                '</div>' +
                '<div class="sp-quick-stat">' +
                    '<div class="sp-quick-stat-icon">' +
                        '<img src="' + baseUrl + 'images/icons/star-svgrepo-com.svg" alt="Rating" width="22" height="22">' +
                    '</div>' +
                    '<div class="sp-quick-stat-content">' +
                        '<span class="number">' + (avgRating ? avgRating.toFixed(1) : '—') + '</span>' +
                        '<span class="label">' + t('avg_rating') + '</span>' +
                        starsHtml +
                    '</div>' +
                '</div>' +
                '<div class="sp-quick-stat">' +
                    '<div class="sp-quick-stat-icon">' +
                        '<img src="' + baseUrl + 'images/icons/comment-svgrepo-com.svg" alt="Reviews" width="22" height="22">' +
                    '</div>' +
                    '<div class="sp-quick-stat-content">' +
                        '<span class="number">' + reviewCount + '</span>' +
                        '<span class="label">' + t('reviews_received') + '</span>' +
                    '</div>' +
                '</div>' +
                '<div class="sp-quick-stat">' +
                    '<div class="sp-quick-stat-icon">' +
                        '<img src="' + baseUrl + 'images/icons/calendar-svgrepo-com.svg" alt="Member since" width="22" height="22">' +
                    '</div>' +
                    '<div class="sp-quick-stat-content">' +
                        '<span class="number">' + (memberSince || '—') + '</span>' +
                        '<span class="label">' + t('member_since') + '</span>' +
                    '</div>' +
                '</div>' +
            '</div>';

        $container.html(html);
    }

    // ============================================================
    // PRODUCTS - Uses displayProducts() from products.js
    // ============================================================
    function loadSellerProducts() {
        var $grid = $('#seller-products-grid');

        $.ajax({
            url: baseUrl + 'php/endpoints/products/get-products.php?limit=8&seller_id=' + sellerId,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success && data.products && data.products.length > 0) {
                    displayProducts(data.products, '#seller-products-grid');
                    var total = data.total || data.products.length;
                    $('#sp-product-count').text(total + ' ' + t('items'));
                } else {
                    $('#sp-product-count').text('');
                    $grid.html(
                        '<div class="sp-empty">' +
                        '<div class="sp-empty-icon">' +
                            '<img src="' + baseUrl + 'images/icons/product-catalog-svgrepo-com.svg" alt="No products" width="26" height="26">' +
                        '</div>' +
                        '<h3>' + t('no_products_yet') + '</h3>' +
                        '<p>' + t('seller_no_products_message') + '</p>' +
                        '</div>'
                    );
                }
            },
            error: function() {
                $('#sp-product-count').text('');
                $grid.html(
                    '<div class="sp-empty">' +
                    '<div class="sp-empty-icon">' +
                        '<img src="' + baseUrl + 'images/icons/error-svgrepo-com.svg" alt="Error" width="26" height="26">' +
                    '</div>' +
                    '<h3>' + t('error_loading_products') + '</h3>' +
                    '<p>' + t('error_loading_products') + '</p>' +
                    '</div>'
                );
            }
        });
    }

    // ============================================================
    // REVIEWS
    // ============================================================
    function loadSellerReviews() {
        var $container = $('#sp-reviews-container');

        $.ajax({
            url: baseUrl + 'php/endpoints/reviews/get-reviews.php?seller_id=' + sellerId + '&limit=5&offset=0',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success && data.reviews && data.reviews.length > 0) {
                    displayReviews(data);
                    $('#sp-review-count').text(data.reviews.length + ' ' + t('items'));
                } else {
                    $('#sp-review-count').text('');
                    $container.html(
                        '<div class="sp-empty">' +
                        '<div class="sp-empty-icon">' +
                            '<img src="' + baseUrl + 'images/icons/comment-svgrepo-com.svg" alt="No reviews" width="26" height="26">' +
                        '</div>' +
                        '<h3>' + t('no_reviews_yet') + '</h3>' +
                        '<p>' + t('no_reviews_message') + '</p>' +
                        '</div>'
                    );
                }
            },
            error: function() {
                $('#sp-review-count').text('');
                $container.html(
                    '<div class="sp-empty">' +
                    '<div class="sp-empty-icon">' +
                        '<img src="' + baseUrl + 'images/icons/error-svgrepo-com.svg" alt="Error" width="26" height="26">' +
                    '</div>' +
                    '<h3>' + t('error_loading_reviews') + '</h3>' +
                    '<p>' + t('error_loading_reviews_message') + '</p>' +
                    '</div>'
                );
            }
        });
    }

    function displayReviews(data) {
        var $container = $('#sp-reviews-container');
        var html = '<div class="sp-reviews-list" id="reviews-list">';

        for (var i = 0; i < data.reviews.length; i++) {
            var review = data.reviews[i];
            var buyerName = review.buyer_name || 'Anonymous';
            var initials = getInitials(buyerName);
            var rating = review.rating || 0;
            var comment = review.comment || '';
            var date = review.created_at || '';
            var isVerified = review.is_verified || false;

            var starsHtml = '';
            for (var s = 1; s <= 5; s++) {
                starsHtml += (s <= rating) ? '<span class="star">★</span>' : '<span class="star empty">★</span>';
            }

            var verifiedBadge = isVerified ?
                '<span class="sp-review-verified-buyer"><img src="' + baseUrl + 'images/icons/verified-svgrepo-com.svg" width="10" height="10" alt="Verified"> ' + t('verified_buyer') + '</span>' :
                '';

            html +=
                '<div class="sp-review">' +
                    '<div class="sp-review-top">' +
                        '<div class="sp-review-user">' +
                            '<div class="sp-review-user-avatar">' + initials + '</div>' +
                            '<div class="sp-review-user-info">' +
                                '<div class="sp-review-user-name">' + escapeHtml(buyerName) + ' ' + verifiedBadge + '</div>' +
                                '<div class="sp-review-stars">' + starsHtml + '</div>' +
                            '</div>' +
                        '</div>' +
                        '<div class="sp-review-date">' + escapeHtml(date) + '</div>' +
                    '</div>' +
                    (comment ? '<div class="sp-review-text">' + escapeHtml(comment) + '</div>' : '') +
                '</div>';
        }

        html += '</div>';

        if (data.has_more) {
            html += '<button class="sp-load-more" id="loadMoreReviews" data-seller="' + sellerId + '" data-offset="5">' + t('load_more_reviews') + '</button>';
        }

        $container.html(html);

        // Bind load more
        $('#loadMoreReviews').on('click', function() {
            var $btn = $(this);
            var offset = parseInt($btn.data('offset'));
            var $list = $('#reviews-list');

            $btn.prop('disabled', true).text(t('loading_more_reviews'));

            $.ajax({
                url: baseUrl + 'php/endpoints/reviews/get-reviews.php?seller_id=' + sellerId + '&offset=' + offset + '&limit=5',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.reviews && response.reviews.length > 0) {
                        for (var j = 0; j < response.reviews.length; j++) {
                            var rev = response.reviews[j];
                            var name = rev.buyer_name || 'Anonymous';
                            var init = getInitials(name);
                            var stars = '';
                            for (var s2 = 1; s2 <= 5; s2++) {
                                stars += (s2 <= rev.rating) ? '<span class="star">★</span>' : '<span class="star empty">★</span>';
                            }

                            var verified = rev.is_verified ?
                                '<span class="sp-review-verified-buyer"><img src="' + baseUrl + 'images/icons/verified-svgrepo-com.svg" width="10" height="10" alt="Verified"> ' + t('verified_buyer') + '</span>' :
                                '';

                            var card =
                                '<div class="sp-review">' +
                                    '<div class="sp-review-top">' +
                                        '<div class="sp-review-user">' +
                                            '<div class="sp-review-user-avatar">' + init + '</div>' +
                                            '<div class="sp-review-user-info">' +
                                                '<div class="sp-review-user-name">' + escapeHtml(name) + ' ' + verified + '</div>' +
                                                '<div class="sp-review-stars">' + stars + '</div>' +
                                            '</div>' +
                                        '</div>' +
                                        '<div class="sp-review-date">' + escapeHtml(rev.created_at || '') + '</div>' +
                                    '</div>' +
                                    (rev.comment ? '<div class="sp-review-text">' + escapeHtml(rev.comment) + '</div>' : '') +
                                '</div>';

                            $list.append(card);
                        }

                        var newOffset = offset + response.reviews.length;
                        $btn.data('offset', newOffset);

                        if (!response.has_more) {
                            $btn.remove();
                        } else {
                            $btn.prop('disabled', false).text(t('load_more_reviews'));
                        }
                    } else {
                        $btn.remove();
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text(t('load_more_reviews'));
                }
            });
        });
    }

    // ============================================================
    // LOAD EVERYTHING
    // ============================================================
    loadSellerProfile();
    loadSellerStats();
    loadSellerProducts();
    loadSellerReviews();
});