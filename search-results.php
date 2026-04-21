<?php
/*
 * ConsuTrade - Search Results Page
 * Author: Kamogelo Phale
 * 
 * This page displays search results from the header search bar
 */

session_start();

$baseUrl = "/www/consutrade/";

// Get search query from URL
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - ConsuTrade</title>
    <meta name="description" content="Search results for products on ConsuTrade">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/animations.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/products.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/login-signup.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/header.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/footer.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
</head>
<body>

<?php include 'includes/header.php'; ?>

<main>
    <!-- Breadcrumb Navigation -->
    <div class="breadcrumb">
        <a href="index.php">Home</a>
        <span class="breadcrumb-separator">></span>
        <a href="product-listings.php">Products</a>
        <span class="breadcrumb-separator">></span>
        <span class="current-page">Search: <?php echo htmlspecialchars($search_query); ?></span>
    </div>

    <div class="listings-body">
        <!-- Filter button - only shows on mobile devices -->
        <button class="filter-btn" id="mobileFilterBtn">
            <img src="<?php echo $baseUrl; ?>images/icons/filter-svgrepo-com.svg" alt="filter" width="18" height="18">
            Filter Results
        </button>
        
        <!-- Filter sidebar -->
        <aside class="filter-sidebar" id="filterSidebar">
            <form id="filterForm" method="GET" action="search-results.php">
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                
                <fieldset class="filter-fields">
                    <legend class="filter-title">Filter Results</legend>
                    
                    <!-- Category Filter Section -->
                    <fieldset class="filter-category">
                        <legend class="filter-heading">Category</legend>
                        <label class="checkbox-label">
                            <input type="checkbox" name="category[]" value="clothing">
                            <span>Clothing & Accessories</span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="category[]" value="electronics">
                            <span>Electronics</span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="category[]" value="food">
                            <span>Food and Drinks</span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="category[]" value="furniture">
                            <span>Furniture</span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="category[]" value="beauty">
                            <span>Beauty & Health</span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="category[]" value="other">
                            <span>Other</span>
                        </label>
                    </fieldset>
                    
                    <!-- Price Range Filter Section -->
                    <fieldset class="filter-price">
                        <legend class="filter-heading">Price Range</legend>
                        <label class="radio-label">
                            <input type="radio" name="price_range" value="under100">
                            <span>Under R100</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="price_range" value="100-500">
                            <span>R100 - R500</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="price_range" value="500-1000">
                            <span>R500 - R1000</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="price_range" value="over1000">
                            <span>Over R1000</span>
                        </label>
                    </fieldset>
                    
                    <!-- Location Filter Section -->
                    <fieldset class="filter-location">
                        <legend class="filter-heading">Location</legend>
                        <div class="search-loc-wrapper">
                            <img src="<?php echo $baseUrl; ?>images/icons/pin-location-svgrepo-com.svg" alt="location" class="location-icon" width="16" height="16">
                            <input type="search" id="search-location" name="location" placeholder="Enter city or province...">
                        </div>
                    </fieldset>
                    
                    <!-- Filter Action Buttons -->
                    <div class="filter-actions">
                        <button type="submit" class="apply-filter-btn">
                            <img src="<?php echo $baseUrl; ?>images/icons/verified-svgrepo-com.svg" alt="apply" width="14" height="14">
                            Apply Filters
                        </button>
                        <button type="reset" class="reset-filter-btn" id="resetFilters">
                            <img src="<?php echo $baseUrl; ?>images/icons/form-close-svgrepo-com.svg" alt="reset" width="14" height="14">
                            Reset
                        </button>
                    </div>
                </fieldset>
            </form>
        </aside>

        <!-- Products Grid Section -->
        <section class="listings-products">
            <div class="listings-header">
                <h1>Search Results for "<?php echo htmlspecialchars($search_query); ?>"</h1>
                <div class="sort-options">
                    <label for="sortBy">Sort by:</label>
                    <select id="sortBy">
                        <option value="newest">Newest First</option>
                        <option value="price_low">Price: Low to High</option>
                        <option value="price_high">Price: High to Low</option>
                    </select>
                </div>
            </div>
            
            <!-- Products will be loaded here by JavaScript -->
            <div class="listings-grid" id="products-grid">
                <div class="loading-spinner">Searching for products...</div>
            </div>
            
            <!-- Pagination section -->
            <div class="pagination" id="pagination"></div>
        </section>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<script>
var searchQuery = '<?php echo addslashes($search_query); ?>';
var currentPage = 1;
var currentSort = 'newest';
var currentFilters = {
    categories: [],
    price_range: '',
    location: ''
};
var totalPages = 1;

$(document).ready(function() {
    loadSearchResults();
    setupEventListeners();
});

function setupEventListeners() {
    // Mobile filter button toggle
    $('#mobileFilterBtn').on('click', function() {
        $('#filterSidebar').toggleClass('active');
    });

    // Filter form submission
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        collectFilters();
        currentPage = 1;
        loadSearchResults();
        if ($(window).width() <= 768) {
            $('#filterSidebar').removeClass('active');
        }
    });

    // Reset filters button
    $('#resetFilters').on('click', function() {
        $('#filterForm')[0].reset();
        currentFilters = { categories: [], price_range: '', location: '' };
        currentPage = 1;
        loadSearchResults();
    });

    // Sort options change
    $('#sortBy').on('change', function() {
        currentSort = $(this).val();
        currentPage = 1;
        loadSearchResults();
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

function loadSearchResults() {
    var params = new URLSearchParams();
    params.append('search', searchQuery);
    params.append('page', currentPage);
    params.append('sort', currentSort);
    params.append('limit', 12);
    
    if (currentFilters.categories && currentFilters.categories.length > 0) {
        params.append('categories', currentFilters.categories.join(','));
    }
    if (currentFilters.price_range) {
        params.append('price_range', currentFilters.price_range);
    }
    if (currentFilters.location) {
        params.append('location', currentFilters.location);
    }
    
    $('#products-grid').html('<div class="loading-spinner">Searching for products...</div>');
    
    $.get(baseUrl + 'php/search-products.php?' + params.toString(), function(data) {
        if (data.success && data.products && data.products.length > 0) {
            displayProducts(data.products);
            totalPages = data.total_pages || 1;
            displayPagination();
        } else {
            $('#products-grid').html(`
                <div class="no-products">
                    <img src="${baseUrl}images/icons/search-svgrepo-com.svg" width="64" height="64" alt="No results" style="opacity: 0.5; margin-bottom: 20px;">
                    <h3>No products found</h3>
                    <p>We couldn't find any products matching "${escapeHtml(searchQuery)}"</p>
                    <button onclick="window.location.href='product-listings.php'" class="reset-btn">Browse All Products</button>
                </div>
            `);
            $('#pagination').empty();
        }
    }).fail(function() {
        $('#products-grid').html('<p class="error">Error loading search results. Please try again.</p>');
    });
}

function displayProducts(products) {
    var $grid = $('#products-grid');
    $grid.empty();
    
    $.each(products, function(index, product) {
        var verifiedBadge = product.is_verified ? 
            '<div class="verified-badge-card"><img src="' + baseUrl + 'images/icons/verified-svgrepo-com.svg" width="14" height="14" alt="Verified"><span>Verified Seller</span></div>' : 
            '<div class="unverified-badge-card"><img src="' + baseUrl + 'images/icons/not-verified-svgrepo-com.svg" width="14" height="14" alt="Not Verified"><span>Unverified</span></div>';
        
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
                        <img src="${baseUrl}images/icons/profile-svgrepo-com.svg" alt="Seller">
                    </div>
                    <div class="seller-details">
                        <p class="seller-name">${escapeHtml(product.seller_name)}</p>
                        <p class="location">
                            <img src="${baseUrl}images/icons/pin-location-svgrepo-com.svg" width="10" height="10" alt="location">
                            ${escapeHtml(product.location)}
                        </p>
                    </div>
                    ${verifiedBadge}
                </div>
                <button class="add-to-cart-btn" onclick="event.stopPropagation(); addToCart(${product.id}, '${escapeHtml(product.name).replace(/'/g, "\\'")}', ${product.price})">
                    <img src="${baseUrl}images/icons/shopping-cart-01-svgrepo-com.svg" alt="Cart" width="16" height="16">
                    Add to Cart
                </button>
                <div class="payment-badge">
                    <span>Secure payment via</span>
                    <img src="${baseUrl}images/icons/Payfast logo.svg" alt="PayFast" width="40" height="16">
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
    loadSearchResults();
    $('html, body').animate({ scrollTop: 0 }, 'smooth');
}

function escapeHtml(text) {
    if (!text) return '';
    return $('<div>').text(text).html();
}
</script>

</body>
</html>