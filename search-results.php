<?php
/*
 * ConsuTrade - Search Results Page
 * Author: Kamogelo Phale
 * 
 * Displays search results from header search bar
 */

require_once __DIR__ . '/init.php';

// Get register/login errors from session (handled by header modals)
$registerErrors = $registerErrors ?? [];
$registerFormData = $registerFormData ?? [];
$loginErrors = $loginErrors ?? [];
$loginEmail = $loginEmail ?? '';

// Get search query from URL
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;

// For JavaScript output - sanitize separately
$search_query_js = htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - ConsuTrade</title>
    <meta name="author" content="Kamogelo Phale">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <main>
        <!-- Breadcrumb navigation -->
        <div class="breadcrumb">
            <a href="index.php">Home</a>
            <span class="breadcrumb-separator">></span>
            <a href="product-listings.php">Products</a>
            <span class="breadcrumb-separator">></span>
            <span class="current-page">Search: <?php echo htmlspecialchars($search_query); ?></span>
        </div>

        <div class="listings-body">
            <!-- Mobile filter toggle button -->
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

                        <!-- Category filter -->
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

                        <!-- Price range filter -->
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

                        <!-- Location filter -->
                        <fieldset class="filter-location">
                            <legend class="filter-heading">Location</legend>
                            <div class="search-loc-wrapper">
                                <img src="<?php echo $baseUrl; ?>images/icons/pin-location-svgrepo-com.svg" alt="location" class="location-icon" width="16" height="16">
                                <input type="search" id="search-location" name="location" placeholder="Enter city or province...">
                            </div>
                        </fieldset>

                        <!-- Filter action buttons -->
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

            <!-- Products section -->
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

                <!-- Products loaded here by products.js -->
                <div class="listings-grid" id="products-grid">
                    <div class="loading-spinner">Searching for products...</div>
                </div>

                <!-- Pagination -->
                <div class="pagination" id="pagination"></div>
            </section>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="<?php echo $baseUrl; ?>js/products.js"></script>

    <script>
        // Search results functionality
        // These variables are already declared in products.js, just so I just assign values
        searchQuery = <?php echo json_encode($search_query_js); ?>;
        currentPage = 1;
        currentSort = 'newest';
        currentFilters = {
            categories: [],
            price_range: '',
            location: ''
        };
        totalPages = 1;

        $(function() {
            loadSearchResults();
            setupEventListeners();
        });

        // Set up all event listeners
        function setupEventListeners() {
            // Mobile filter sidebar toggle
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
                currentFilters = {
                    categories: [],
                    price_range: '',
                    location: ''
                };
                currentPage = 1;
                loadSearchResults();
            });

            // Sort order change
            $('#sortBy').on('change', function() {
                currentSort = $(this).val();
                currentPage = 1;
                loadSearchResults();
            });
        }

        // Collect current filter values
        function collectFilters() {
            var categories = [];
            $('input[name="category[]"]:checked').each(function() {
                categories.push($(this).val());
            });

            currentFilters = {
                categories: categories,
                price_range: $('input[name="price_range"]:checked').val() || '',
                location: $('#search-location').val() || ''
            };
        }

        // Load search results from API
        function loadSearchResults() {
            var params = $.param({
                search: searchQuery,
                page: currentPage,
                sort: currentSort,
                limit: 12
            });

            if (currentFilters.categories && currentFilters.categories.length > 0) {
                params += '&categories=' + encodeURIComponent(currentFilters.categories.join(','));
            }
            if (currentFilters.price_range) {
                params += '&price_range=' + encodeURIComponent(currentFilters.price_range);
            }
            if (currentFilters.location) {
                params += '&location=' + encodeURIComponent(currentFilters.location);
            }

            $('#products-grid').html('<div class="loading-spinner">Searching for products...</div>');

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
                            $('#products-grid').html('<p class="error">Error displaying products.</p>');
                        }
                        totalPages = data.total_pages || 1;
                        displayPagination();
                    } else {
                        $('#products-grid').html(
                            '<div class="no-products">' +
                            '<img src="' + baseUrl + 'images/icons/search-svgrepo-com.svg" width="64" height="64" alt="No results" style="opacity: 0.5; margin-bottom: 20px;">' +
                            '<h3>No products found</h3>' +
                            '<p>We couldn\'t find any products matching "' + escapeHtml(searchQuery) + '"</p>' +
                            '<button onclick="window.location.href=\'product-listings.php\'" class="reset-btn">Browse All Products</button>' +
                            '</div>'
                        );
                        $('#pagination').empty();
                    }
                },
                error: function() {
                    $('#products-grid').html('<p class="error">Error loading search results. Please try again.</p>');
                }
            });
        }

        // Display pagination controls
        function displayPagination() {
            renderPagination($('#pagination'), currentPage, totalPages, function(page) {
                goToPage(page);
            });
        }

        // Navigate to specific page
        function goToPage(page) {
            currentPage = page;
            loadSearchResults();
            $('html, body').animate({
                scrollTop: 0
            }, 'smooth');
        }
    </script>

</body>

</html>