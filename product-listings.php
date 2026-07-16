<?php
/*
 * ConsuTrade - Product Listings Page
 * Author: Kamogelo Phale
 * 
 * Displays all products with filtering and sorting options
 * Uses AJAX loading via products.js (loaded in footer via $load_products_js flag)
 */

require_once __DIR__ . '/init.php';
include __DIR__ . '/includes/session-vars.php';
include __DIR__ . '/includes/functions.php';

// Get category from URL
$selectedCategory = isset($_GET['category']) ? trim($_GET['category']) : '';

$categoryNames = [
    'clothing' => 'Clothing & Accessories',
    'electronics' => 'Electronics',
    'food' => 'Food & Drinks',
    'furniture' => 'Furniture',
    'beauty' => 'Beauty & Health',
    'other' => 'Other'
];

$hasCategory = $selectedCategory !== '' && isset($categoryNames[$selectedCategory]);
$categoryLabel = $hasCategory ? $categoryNames[$selectedCategory] : ucfirst($selectedCategory);
$pageTitle = $hasCategory ? $categoryLabel : 'Browse the Market';
$pageSubtitle = $hasCategory
    ? 'Products in this category from verified traders across South Africa'
    : 'Buy with confidence from verified traders across South Africa';
$headingTitle = $hasCategory ? $categoryLabel : 'All Products';

$load_products_js = true;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $hasCategory ? htmlspecialchars($categoryLabel) . ' - ' : ''; ?>Shop Products - ConsuTrade</title>
    <meta name="description" content="Browse all products on ConsuTrade - South Africa's trusted marketplace">
    <meta name="author" content="Kamogelo Phale">

    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <main>
        <!-- ============================================ -->
        <!-- BANNER - Clean, collapsible                  -->
        <!-- ============================================ -->
        <div class="listings-banner" id="listingsBanner">
            <div class="listings-banner-content">
                <div class="banner-text">
                    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
                    <p><?php echo htmlspecialchars($pageSubtitle); ?></p>
                </div>
                <button class="banner-close-btn" id="bannerCloseBtn" aria-label="Hide banner">
                    <span aria-hidden="true">↑</span> Hide
                </button>
            </div>
        </div>

        <!-- RESTORE BUTTON - OUTSIDE the banner, in its own container -->
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
                        <fieldset class="filter-fields">
                            <!-- Category -->
                            <fieldset class="filter-category">
                                <legend class="filter-heading">Category</legend>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="category[]" value="clothing" <?php echo $selectedCategory === 'clothing' ? 'checked' : ''; ?>>
                                    <span>Clothing &amp; Accessories</span>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="category[]" value="electronics" <?php echo $selectedCategory === 'electronics' ? 'checked' : ''; ?>>
                                    <span>Electronics</span>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="category[]" value="food" <?php echo $selectedCategory === 'food' ? 'checked' : ''; ?>>
                                    <span>Food &amp; Drinks</span>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="category[]" value="furniture" <?php echo $selectedCategory === 'furniture' ? 'checked' : ''; ?>>
                                    <span>Furniture</span>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="category[]" value="beauty" <?php echo $selectedCategory === 'beauty' ? 'checked' : ''; ?>>
                                    <span>Beauty &amp; Health</span>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="category[]" value="other" <?php echo $selectedCategory === 'other' ? 'checked' : ''; ?>>
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
        window.initialCategory = '<?php echo $selectedCategory; ?>';
    </script>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/modal-errors.php'; ?>

</body>

</html>