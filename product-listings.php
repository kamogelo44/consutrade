<?php
/*
 * ConsuTrade - Product Listings Page
 * Author: Kamogelo Phale
 * 
 * Displays all products with filtering and sorting options
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

$breadcrumbItems = [
    ['label' => 'All Products']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Products - ConsuTrade</title>
    <meta name="author" content="Kamogelo Phale">
    
    <!-- Master Stylesheet -->
    <link rel="stylesheet" href="css/main.css">
    
</head>
<body>

<?php include 'includes/header.php'; ?>

<main>
    <!-- Breadcrumb -->
    <?php include 'includes/breadcrumb.php'; ?>

    <!-- Listings Body -->
    <div class="listings-body">
        <!-- Mobile Filter Button -->
        <button class="filter-btn" id="mobileFilterBtn">
            <img src="images/icons/filter-svgrepo-com.svg" alt="filter" width="18" height="18">
            Filter Products
        </button>
        
        <!-- Filter Sidebar -->
        <aside class="filter-sidebar" id="filterSidebar">
            <form id="filterForm">
                <fieldset class="filter-fields">
                    <legend class="filter-title">Filter Results</legend>
                    
                    <!-- Category Filter -->
                    <fieldset class="filter-category">
                        <legend class="filter-heading">Category</legend>
                        <label class="checkbox-label">
                            <input type="checkbox" name="category[]" value="clothing">
                            <span>Clothing and Accessories</span>
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
                            <span>Beauty and Health</span>
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
                    
                    <!-- Location Filter -->
                    <fieldset class="filter-location">
                        <legend class="filter-heading">Location</legend>
                        <div class="search-loc-wrapper">
                            <img src="images/icons/pin-location-svgrepo-com.svg" alt="location" class="location-icon" width="16" height="16">
                            <input type="search" id="search-location" name="location" placeholder="Enter city or province...">
                        </div>
                    </fieldset>
                    
                    <!-- Filter Actions -->
                    <div class="filter-actions">
                        <button type="submit" class="apply-filter-btn">
                            <img src="images/icons/verified-svgrepo-com.svg" alt="apply" width="14" height="14">
                            Apply Filters
                        </button>
                        <button type="reset" class="reset-filter-btn" id="resetFilters">
                            <img src="images/icons/form-close-svgrepo-com.svg" alt="reset" width="14" height="14">
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

<script src="js/products.js"></script>

<script>
// Pass session data to JavaScript
var isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
var currentUserId = <?php echo $current_user_id ?: 0; ?>;
var currentUserRole = '<?php echo $current_user ? $current_user['role'] : ''; ?>';
var baseUrl = '<?php echo $baseUrl; ?>';
</script>

</body>
</html>