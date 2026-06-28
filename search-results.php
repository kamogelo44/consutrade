<?php
/*
 * ConsuTrade - Search Results Page
 * Author: Kamogelo Phale
 */

require_once __DIR__ . '/init.php';
include __DIR__ . '/includes/session-vars.php';

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$breadcrumbItems = [
    ['url' => 'product-listings.php', 'label' => 'Products'],
    ['label' => 'Search: ' . htmlspecialchars($search_query)]
];
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
        <?php include 'includes/breadcrumb.php'; ?>

        <div class="listings-body">
            <button class="filter-btn" id="mobileFilterBtn">
                <img src="<?php echo $baseUrl; ?>images/icons/filter-svgrepo-com.svg" alt="filter" width="18" height="18">
                Filter Results
            </button>

            <aside class="filter-sidebar" id="filterSidebar">
                <form id="filterForm">
                    <!-- Hidden search query for form submission -->
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
    <?php include 'includes/modal-errors.php'; ?>

    <!-- Single products.js handles both product listings and search -->
    <script src="<?php echo $baseUrl; ?>js/products.js"></script>

</body>

</html>