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

$load_products_js = true;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Products - ConsuTrade</title>
    <meta name="description" content="Browse all products on ConsuTrade - South Africa's trusted marketplace">
    <meta name="author" content="Kamogelo Phale">

    <!-- CSS Files -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <main>
        <!-- Listings Banner -->
        <div class="listings-banner">
            <div class="listings-banner-content">
                <h1>Browse the Market</h1>
                <p>Products from verified traders across South Africa</p>
            </div>
        </div>

        <div class="listings-body">
            <!-- Mobile Filter Button -->
            <button class="mobile-filter-btn" id="mobileFilterBtn">
                <img src="<?php echo $baseUrl; ?>images/icons/filter-svgrepo-com.svg" alt="filter" width="18" height="18">
                Filters
            </button>

            <!-- Filter Sidebar -->
            <aside class="filter-sidebar" id="filterSidebar">
                <form id="filterForm">
                    <fieldset class="filter-fields">
                        <!-- Category Filter -->
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
                                <span>Food & Drinks</span>
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

                        <!-- Price Range Filter -->
                        <fieldset class="filter-price">
                            <legend class="filter-heading">Price Range</legend>
                            <label class="radio-label">
                                <input type="radio" name="price_range" value="under100">
                                <span>Under R100</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="price_range" value="100-500">
                                <span>R100 – R500</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="price_range" value="500-1000">
                                <span>R500 – R1,000</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="price_range" value="over1000">
                                <span>Over R1,000</span>
                            </label>
                        </fieldset>

                        <!-- Location Filter -->
                        <fieldset class="filter-location">
                            <legend class="filter-heading">Location</legend>
                            <div class="search-loc-wrapper">
                                <img src="<?php echo $baseUrl; ?>images/icons/pin-location-svgrepo-com.svg" alt="location" class="location-icon" width="16" height="16">
                                <input type="search" id="search-location" name="location" placeholder="City or province...">
                            </div>
                        </fieldset>
                    </fieldset>
                </form>
            </aside>

            <!-- Products Grid Section -->
            <section class="listings-products">
                <div class="listings-header">
                    <div>
                        <h2>All Products</h2>
                        <p class="listings-count" id="listingsCount"></p>
                    </div>
                    <div class="listings-actions">
                        <button type="button" class="apply-filters-btn" id="applyFiltersBtn">Apply Filters</button>
                        <button type="button" class="clear-filters-btn" id="clearFiltersBtn">Clear</button>
                        <div class="sort-options">
                            <label for="sortBy">Sort by:</label>
                            <select id="sortBy">
                                <option value="newest">Newest First</option>
                                <option value="price_low">Price: Low to High</option>
                                <option value="price_high">Price: High to Low</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="listings-grid" id="products-grid">
                    <div class="loading-spinner">Loading products...</div>
                </div>

                <div class="pagination" id="pagination"></div>
            </section>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/modal-errors.php'; ?>

</body>

</html>