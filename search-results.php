<?php
/*
 * ConsuTrade - Search Results Page
 * Author: Kamogelo Phale
 * 
 * This page displays search results from the header search bar
 */

require_once __DIR__ . '/init.php';

// Read register errors
$registerErrors = $_SESSION['register_errors'] ?? [];
$registerFormData = $_SESSION['register_form_data'] ?? [];
unset($_SESSION['register_errors'], $_SESSION['register_form_data']);

// Read login errors
$loginErrors = $_SESSION['login_errors'] ?? [];
$loginEmail = $_SESSION['login_email'] ?? '';
unset($_SESSION['login_errors'], $_SESSION['login_email']);

// Get search query from URL and sanitize properly
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_query = htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8'); // Sanitize for HTML output
// For JavaScript, we need a separate safe version
$search_query_js = htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8');
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
    <meta name="author" content="Kamogelo Phale">
    <meta name="description" content="Search results for products on ConsuTrade">
    
    <!-- Master Stylesheet (includes all CSS) -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    
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
        <span class="current-page">Search: <?php echo $search_query; ?></span>
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
                <input type="hidden" name="search" value="<?php echo $search_query; ?>">
                
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
                <h1>Search Results for "<?php echo $search_query; ?>"</h1>
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

<?php if (!empty($registerErrors)): ?>
<script>
$(function() {
    openModal($('#register-modal'));
    displayModalErrors('#register-modal', <?php echo json_encode($registerErrors); ?>, <?php echo json_encode($registerFormData); ?>);
});
</script>
<?php endif; ?>

<?php if (!empty($loginErrors)): ?>
<script>
$(function() {
    openModal($('#login-modal'));
    displayModalErrors('#login-modal', <?php echo json_encode($loginErrors); ?>, {email: <?php echo json_encode($loginEmail); ?>});
});
</script>
<?php endif; ?>

<script>
/*
 * Search Results Functionality
 * 
 * Note: displayProducts(), escapeHtml(), getSellerAvatar(), and addToCart() 
 * are already defined in products.js and main.js
 */

// Use JSON.stringify for safe JavaScript string
var searchQuery = <?php echo json_encode($search_query_js, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
currentPage = 1;
currentSort = 'newest';
currentFilters = {
    categories: [],
    price_range: '',
    location: ''
};
totalPages = 1;
var baseUrl = <?php echo json_encode($baseUrl); ?>;

// Make sure auth variables are available for the product display
var isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
var currentUserId = <?php echo $current_user_id ?: 0; ?>;
var currentUserRole = <?php echo json_encode($current_user ? $current_user['role'] : ''); ?>;

$(function() {
    loadSearchResults();
    setupEventListeners();
});

function setupEventListeners() {
    $('#mobileFilterBtn').on('click', function() {
        $('#filterSidebar').toggleClass('active');
    });

    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        collectFilters();
        currentPage = 1;
        loadSearchResults();
        if ($(window).width() <= 768) {
            $('#filterSidebar').removeClass('active');
        }
    });

    $('#resetFilters').on('click', function() {
        $('#filterForm')[0].reset();
        currentFilters = { categories: [], price_range: '', location: '' };
        currentPage = 1;
        loadSearchResults();
    });

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
            // REUSE the displayProducts function from products.js
            if (typeof displayProducts === 'function') {
                displayProducts(data.products);
            } else {
                console.error('displayProducts function not found');
                $('#products-grid').html('<p class="error">Error displaying products.</p>');
            }
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
</script>

</body>
</html>