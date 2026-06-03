<?php
/*
 * ConsuTrade - Search Results Page
 * Author: Kamogelo Phale
 */

require_once __DIR__ . '/init.php';

$registerErrors = $_SESSION['register_errors'] ?? [];
$registerFormData = $_SESSION['register_form_data'] ?? [];
$loginErrors = $_SESSION['login_errors'] ?? [];
$loginEmail = $_SESSION['login_email'] ?? '';
unset($_SESSION['register_errors'], $_SESSION['register_form_data'], $_SESSION['login_errors'], $_SESSION['login_email']);

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;

$search_query_js = htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <main>
        <div class="breadcrumb">
            <a href="index.php">Home</a>
            <span class="breadcrumb-separator">></span>
            <a href="product-listings.php">Products</a>
            <span class="breadcrumb-separator">></span>
            <span class="current-page">Search: <?php echo htmlspecialchars($search_query); ?></span>
        </div>

        <div class="listings-body">
            <button class="filter-btn" id="mobileFilterBtn">
                <img src="<?php echo $baseUrl; ?>images/icons/filter-svgrepo-com.svg" alt="filter" width="18" height="18">
                Filter Results
            </button>

            <aside class="filter-sidebar" id="filterSidebar">
                <form id="filterForm" method="GET" action="search-results.php">
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                    <fieldset class="filter-fields">
                        <legend class="filter-title">Filter Results</legend>

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

                        <fieldset class="filter-location">
                            <legend class="filter-heading">Location</legend>
                            <div class="search-loc-wrapper">
                                <img src="<?php echo $baseUrl; ?>images/icons/pin-location-svgrepo-com.svg" alt="location" class="location-icon" width="16" height="16">
                                <input type="search" id="search-location" name="location" placeholder="Enter city or province...">
                            </div>
                        </fieldset>

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

                <div class="listings-grid" id="products-grid">
                    <div class="loading-spinner">Searching for products...</div>
                </div>

                <div class="pagination" id="pagination"></div>
            </section>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="<?php echo $baseUrl; ?>js/products.js"></script>

    <script>
        // ========== CACHED DOM ELEMENTS ==========
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

        // ========== GLOBAL VARIABLES ==========
        var searchQuery = <?php echo json_encode($search_query_js); ?>;
        var currentPage = 1;
        var currentSort = 'newest';
        var currentFilters = {
            categories: [],
            price_range: '',
            location: ''
        };
        var totalPages = 1;

        // ========== CACHE FUNCTION ==========
        function cacheSearchResultsElements() {
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

        // ========== SETUP EVENT LISTENERS ==========
        function setupEventListeners() {
            cacheSearchResultsElements();

            $mobileFilterBtn.on('click', function() {
                $filterSidebar.toggleClass('active');
            });

            $filterForm.on('submit', function(e) {
                e.preventDefault();
                collectFilters();
                currentPage = 1;
                loadSearchResults();
                if ($window.width() <= 768) {
                    $filterSidebar.removeClass('active');
                }
            });

            $resetFiltersBtn.on('click', function() {
                $filterForm[0].reset();
                currentFilters = {
                    categories: [],
                    price_range: '',
                    location: ''
                };
                currentPage = 1;
                loadSearchResults();
            });

            $sortBySelect.on('change', function() {
                currentSort = $sortBySelect.val();
                currentPage = 1;
                loadSearchResults();
            });
        }

        // ========== COLLECT FILTERS ==========
        function collectFilters() {
            var categories = [];
            $categoryCheckboxes.each(function() {
                var $checkbox = $(this);
                if ($checkbox.is(':checked')) {
                    categories.push($checkbox.val());
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

        // ========== LOAD SEARCH RESULTS ==========
        function loadSearchResults() {
            cacheSearchResultsElements();

            var params = {
                search: searchQuery,
                page: currentPage,
                sort: currentSort,
                limit: 12
            };

            if (currentFilters.categories && currentFilters.categories.length > 0) {
                params.categories = currentFilters.categories.join(',');
            }
            if (currentFilters.price_range) {
                params.price_range = currentFilters.price_range;
            }
            if (currentFilters.location) {
                params.location = currentFilters.location;
            }

            $productsGrid.html('<div class="loading-spinner">Searching for products...</div>');

            $.ajax({
                url: baseUrl + 'php/endpoints/search-products.php',
                type: 'GET',
                data: params,
                dataType: 'json',
                success: function(data) {
                    if (data.success && data.products && data.products.length > 0) {
                        if (typeof displayProducts === 'function') {
                            displayProducts(data.products);
                        } else {
                            $productsGrid.html('<p class="error">Error displaying products.</p>');
                        }
                        totalPages = data.total_pages || 1;
                        displayPagination();
                    } else {
                        $productsGrid.html(
                            '<div class="empty-state">' +
                            '<img src="' + baseUrl + 'images/icons/search-svgrepo-com.svg" width="64" height="64" alt="No results">' +
                            '<h3>No products found</h3>' +
                            '<p>We couldn\'t find any products matching "' + escapeHtml(searchQuery) + '"</p>' +
                            '<button onclick="window.location.href=\'product-listings.php\'" class="view-all-btn">Browse All Products</button>' +
                            '</div>'
                        );
                        $paginationContainer.empty();
                    }
                },
                error: function() {
                    $productsGrid.html('<p class="error">Error loading search results. Please try again.</p>');
                }
            });
        }

        // ========== DISPLAY PAGINATION ==========
        function displayPagination() {
            cacheSearchResultsElements();

            if (typeof renderPagination === 'function') {
                renderPagination($paginationContainer, currentPage, totalPages, function(page) {
                    currentPage = page;
                    loadSearchResults();
                    $htmlBody.animate({
                        scrollTop: 0
                    }, 'smooth');
                });
            }
        }

        // ========== INITIALIZE ==========
        $(function() {
            cacheSearchResultsElements();
            loadSearchResults();
            setupEventListeners();
        });
    </script>

</body>

</html>