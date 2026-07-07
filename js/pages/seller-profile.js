$(function() {
    var sellerId = window.sellerProfileId;

    function loadSellerProducts() {
        var $grid = $('#seller-products-grid');

        $.ajax({
            url: baseUrl + 'php/endpoints/products/get-products.php?limit=12&seller_id=' + sellerId,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success && data.products && data.products.length > 0) {
                    $('#sellerProductCount').text(data.total || data.products.length);
                    displayProducts(data.products, '#seller-products-grid');
                } else {
                    $('#sellerProductCount').text(0);
                    $grid.html(
                        '<div class="empty-state">' +
                        '<img src="' + baseUrl + 'images/icons/product-catalog-svgrepo-com.svg" width="64" height="64" alt="No products">' +
                        '<h3>No Products Yet</h3>' +
                        '<p>This seller hasn\'t listed any products yet.</p>' +
                        '</div>'
                    );
                }
            },
            error: function() {
                $('#sellerProductCount').text(0);
                $grid.html(
                    '<div class="empty-state">' +
                    '<img src="' + baseUrl + 'images/icons/error-svgrepo-com.svg" width="64" height="64" alt="Error">' +
                    '<h3>Something went wrong</h3>' +
                    '<p>Error loading products. Please refresh the page.</p>' +
                    '</div>'
                );
            }
        });
    }

    // Load More Reviews
    $('#loadMoreReviews').on('click', function() {
        var $btn = $(this);
        var offset = parseInt($btn.data('offset'));
        var $list = $('#reviews-list');

        $btn.prop('disabled', true).text('Loading...');

        $.ajax({
            url: baseUrl + 'php/endpoints/reviews/get-reviews.php?seller_id=' + sellerId + '&offset=' + offset + '&limit=5',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success && data.reviews && data.reviews.length > 0) {
                    for (var i = 0; i < data.reviews.length; i++) {
                        var review = data.reviews[i];
                        var starsHtml = '';
                        for (var s = 1; s <= 5; s++) {
                            starsHtml += (s <= review.rating) ? '<span class="star">★</span>' : '<span class="star empty">★</span>';
                        }

                        var card = '<div class="review-card">' +
                            '<div class="review-header">' +
                                '<div class="reviewer-info">' +
                                    '<div class="reviewer-avatar">' +
                                        '<img src="' + baseUrl + 'images/icons/profile-svgrepo-com.svg" alt="' + escapeHtml(review.buyer_name) + '" loading="lazy">' +
                                    '</div>' +
                                    '<div>' +
                                        '<div class="reviewer-name">' + escapeHtml(review.buyer_name) + '</div>' +
                                        starsHtml +
                                    '</div>' +
                                '</div>' +
                                '<div class="review-date">' + review.created_at + '</div>' +
                            '</div>' +
                            (review.comment ? '<div class="review-comment">' + escapeHtml(review.comment).replace(/\n/g, '<br>') + '</div>' : '') +
                        '</div>';

                        $list.append(card);
                    }

                    var newOffset = offset + data.reviews.length;
                    $btn.data('offset', newOffset);

                    if (!data.has_more) {
                        $btn.remove();
                    } else {
                        $btn.prop('disabled', false).text('Load More Reviews');
                    }
                } else {
                    $btn.remove();
                }
            },
            error: function() {
                $btn.prop('disabled', false).text('Load More Reviews');
            }
        });
    });

    loadSellerProducts();
});