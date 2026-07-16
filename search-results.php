<?php
/*
 * ConsuTrade - Search Results Page
 * Author: Kamogelo Phale
 */

require_once __DIR__ . '/init.php';
include __DIR__ . '/includes/session-vars.php';
include __DIR__ . '/includes/functions.php';

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$pageTitle = 'Search: ' . htmlspecialchars($search_query);
$pageSubtitle = 'Showing results for "' . htmlspecialchars($search_query) . '"';
$headingTitle = 'Search Results';

$load_products_js = true;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results for "<?php echo htmlspecialchars($search_query); ?>" - ConsuTrade</title>
    <meta name="description" content="Search results for <?php echo htmlspecialchars($search_query); ?> on ConsuTrade">
    <meta name="author" content="Kamogelo Phale">

    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <main>
        <!-- ============================================ -->
        <!-- BANNER - Same as product listings            -->
        <!-- ============================================ -->
        <div class="listings-banner" id="listingsBanner">
            <div class="listings-banner-content">
                <div class="banner-text">
                    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
                    <p id="searchSubtitle"><?php echo htmlspecialchars($pageSubtitle); ?></p>
                </div>
                <button class="banner-close-btn" id="bannerCloseBtn" aria-label="Hide banner">
                    <span aria-hidden="true">↑</span> Hide
                </button>
            </div>
        </div>

        <!-- RESTORE BUTTON - OUTSIDE the banner -->
        <div id="bannerRestoreWrapper">
            <button class="banner-restore-btn" id="bannerRestoreBtn">
                <span aria-hidden="true">↓</span> Show banner
            </button>
        </div>

        <!-- ============================================ -->
        <!-- MAIN CONTENT - Filters + Products            -->
        <!-- ============================================ -->
        <div class="listings-body" id="listingsBody">

            <!-- Mobile Filter Button -->
            <button class="mobile-filter-btn" id="mobileFilterBtn">
                <img src="<?php echo $baseUrl; ?>images/icons/filter-svgrepo-com.svg" alt="" width="18" height="18">
                Filters
            </button>

            <!-- ========================================== -->
            <!-- FILTER SIDEBAR                             -->
            <!-- ========================================== -->
            <aside class="filter-sidebar" id="filterSidebar">
                <div class="filter-sidebar-header">
                    <span class="filter-sidebar-title">Filters</span>
                    <button type="button" class="filter-toggle-btn" id="filterToggleBtn">
                        <span class="filter-toggle-label">Hide</span>
                        <span aria-hidden="true" class="filter-arrow">←</span>
                    </button>
                </div>
                <div class="filter-sidebar-body" id="filterSidebarBody">
                    <form id="filterForm">
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                        <fieldset class="filter-fields">
                            <!-- Category -->
                            <fieldset class="filter-category">
                                <legend class="filter-heading">Category</legend>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="category[]" value="clothing">
                                    <span>Clothing &amp; Accessories</span>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="category[]" value="electronics">
                                    <span>Electronics</span>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="category[]" value="food">
                                    <span>Food &amp; Drinks</span>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="category[]" value="furniture">
                                    <span>Furniture</span>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="category[]" value="beauty">
                                    <span>Beauty &amp; Health</span>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="category[]" value="other">
                                    <span>Other</span>
                                </label>
                            </fieldset>

                            <!-- Price -->
                            <fieldset class="filter-price">
                                <legend class="filter-heading">Price Range</legend>
                                <label class="radio-label">
                                    <input type="radio" name="price_range" value="under100">
                                    <span>Under R100</span>
                                </label>
                                <label class="radio-label">
                                    <input type="radio" name="price_range" value="100-500">
                                    <span>R100 &ndash; R500</span>
                                </label>
                                <label class="radio-label">
                                    <input type="radio" name="price_range" value="500-1000">
                                    <span>R500 &ndash; R1,000</span>
                                </label>
                                <label class="radio-label">
                                    <input type="radio" name="price_range" value="over1000">
                                    <span>Over R1,000</span>
                                </label>
                            </fieldset>

                            <!-- Location -->
                            <fieldset class="filter-location">
                                <legend class="filter-heading">Location</legend>
                                <div class="search-loc-wrapper">
                                    <img src="<?php echo $baseUrl; ?>images/icons/pin-location-svgrepo-com.svg" alt="" class="location-icon" width="16" height="16">
                                    <input type="search" id="search-location" name="location" placeholder="City or province...">
                                </div>
                            </fieldset>
                        </fieldset>
                    </form>
                </div>
            </aside>

            <!-- ========================================== -->
            <!-- PRODUCTS GRID                             -->
            <!-- ========================================== -->
            <section class="listings-products">
                <div class="listings-header">
                    <div class="listings-header-left">
                        <div class="listings-heading-text">
                            <h2><?php echo htmlspecialchars($headingTitle); ?></h2>
                            <p class="listings-count" id="listingsCount">Loading products&hellip;</p>
                        </div>
                    </div>
                    <div class="listings-actions">
                        <button type="button" class="clear-filters-btn" id="clearFiltersBtn">Clear all</button>
                        <div class="sort-options">
                            <label for="sortBy">Sort by</label>
                            <select id="sortBy">
                                <option value="relevance">Relevance</option>
                                <option value="newest">Newest First</option>
                                <option value="price_low">Price: Low to High</option>
                                <option value="price_high">Price: High to Low</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="listings-grid" id="products-grid">
                    <?php for ($i = 0; $i < 12; $i++): ?>
                        <div class="prod-card skeleton-card">
                            <div class="img-container skeleton" style="height:180px;"></div>
                            <div class="prod-info-container">
                                <div class="skeleton skeleton-text" style="width:80%;height:16px;"></div>
                                <div class="skeleton skeleton-text" style="width:40%;height:14px;margin-top:8px;"></div>
                                <div class="skeleton skeleton-text" style="width:60%;height:12px;margin-top:8px;"></div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>

                <div class="pagination" id="pagination"></div>
            </section>
        </div>
    </main>

    <script>
        window.initialCategory = '';
        window.isSearchPage = true;
        window.searchQuery = '<?php echo htmlspecialchars($search_query); ?>';
    </script>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/modal-errors.php'; ?>

</body>

</html>