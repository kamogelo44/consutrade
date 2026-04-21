<?php
session_start(); 
$baseUrl = "/www/consutrade/";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Products - ConsuTrade</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/products.css">
    <link rel="stylesheet" href="css/login-signup.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/animations.css">
    <link rel="stylesheet" href="css/footer.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
</head>
<body>

<?php include 'includes/header.php'; ?>

<main>
    <div class="breadcrumb">
        <a href="index.php">Home</a>
        <span class="breadcrumb-separator">></span>
        <span class="current-page">All Products</span>
    </div>

    <div class="listings-body">
        <!-- Filter button - only shows on mobile devices -->
        <button class="filter-btn" id="mobileFilterBtn">
            <img src="images/icons/filter-svgrepo-com.svg" alt="filter" width="18px" height="18px">
            Filter Products
        </button>
        
        <!-- Filter sidebar - sticky on desktop, hidden on mobile until button click -->
        <aside class="filter-sidebar" id="filterSidebar">
            <form id="filterForm">
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
                            <img src="images/icons/pin-location-svgrepo-com.svg" alt="location" class="location-icon" width="16px" height="16px">
                            <input type="search"
                                id="search-location"
                                name="location"
                                placeholder="Enter city or province...">
                        </div>
                    </fieldset>
                    
                    <!-- Filter Action Buttons -->
                    <div class="filter-actions">
                        <button type="submit" class="apply-filter-btn">
                            <img src="images/icons/verified-svgrepo-com.svg" alt="apply" width="14px" height="14px">
                            Apply Filters
                        </button>
                        <button type="reset" class="reset-filter-btn" id="resetFilters">
                            <img src="images/icons/form-close-svgrepo-com.svg" alt="reset" width="14px" height="14px">
                            Reset
                        </button>
                    </div>
                </fieldset>
            </form>
        </aside>

        <!-- Products Grid Section -->
        <section class="listings-products">
            <div class="listings-header">
                <h1>All Products</h1>
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
                <div class="loading-spinner">Loading products...</div>
            </div>
            
            <div class="pagination" id="pagination"></div>
        </section>
    </div>
</main>

<script src="js/products.js"></script>
<?php include 'includes/footer.php'; ?>
</body>
</html>