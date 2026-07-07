/**
 * ConsuTrade - Homepage Init
 * Depends on: jQuery, products.js (for displayProducts)
 */

function loadFeaturedProducts() {
    var $grid = $('#featured-products-grid');
    $grid.html('<div class="loading-spinner">Loading products...</div>');

    $.ajax({
        url: baseUrl + 'php/endpoints/products/get-products.php?limit=4&page=1',
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
                '<h3>Could not load products</h3>' +
                '<p>Please refresh the page to try again.</p>' +
                '</div>'
            );
        }
    });
}

function showFeaturedEmptyState() {
    $('#featured-products-grid').html(
        '<div class="empty-state">' +
        '<img src="' + baseUrl + 'images/icons/product-catalog-svgrepo-com.svg" width="64" height="64" alt="No products">' +
        '<h3>No products yet</h3>' +
        '<p>Be the first to list a product on ConsuTrade!</p>' +
        '<a href="' + baseUrl + 'sell.php" class="view-all-btn" style="display: inline-block;">Start Selling</a>' +
        '</div>'
    );
}

$(function() {
    loadFeaturedProducts();

    $('#primary-btn').on('click', function() {
        if (isLoggedIn && currentUserRole === 'seller') {
            window.location.href = baseUrl + 'admin/seller-dashboard.php';
        } else if (isLoggedIn) {
            window.location.href = baseUrl + 'sell.php';
        } else {
            openModal($('#register-modal'));
            $('#seller').prop('checked', true);
            $('#register-modal .modal-header p').text('Create your account to start selling');
        }
    });
});