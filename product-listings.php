<?php
/*
 * ConsuTrade - Product Listings Page
 * Author: Kamogelo Phale
 * 
 * This page displays all products with filtering and sorting options
 * Users can browse, filter by category/price/location, and add items to cart
 */

session_start(); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Products - ConsuTrade South Africa</title>
    <meta name="description" content="Browse products from local South African traders. Buy and sell with confidence.">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/products.css">
    <link rel="stylesheet" href="css/login-signup.css">
    <link rel="stylesheet" href="css/header.css">
</head>
<body>
    <!-- Header -->
    <?php include 'header.php'; ?>

    <main>
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
                <!-- Header with title and sort options -->
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
                
                <!-- Products will be loaded here by JavaScript -->
                <div class="listings-grid" id="products-grid">
                    <div class="loading-spinner">Loading products...</div>
                </div>
                
                <!-- Pagination section -->
                <div class="pagination" id="pagination"></div>
            </section>
        </div>
    </main>

    <!-- Footer -->
    <?php include 'footer.php'; ?>
    
    <script src="js/main.js"></script>
    <script>
        // ========== PRODUCT LISTINGS JAVASCRIPT ==========
        // Author: Kamogelo Phale
        // Handles filtering, sorting, pagination, and product display
        
        let currentPage = 1;
        let currentFilters = {};
        let currentSort = 'newest';
        let totalPages = 1;

        // Initialize page when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            loadProducts();
            setupEventListeners();
        });

        // Set up all event listeners for filters, sorting, and mobile toggle
        function setupEventListeners() {
            // Mobile filter button toggle
            var filterBtn = document.getElementById('mobileFilterBtn');
            var filterSidebar = document.getElementById('filterSidebar');
            if (filterBtn && filterSidebar) {
                filterBtn.addEventListener('click', function() {
                    filterSidebar.classList.toggle('active');
                });
            }

            // Filter form submission
            var filterForm = document.getElementById('filterForm');
            if (filterForm) {
                filterForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    collectFilters();
                    currentPage = 1;
                    loadProducts();
                    // Close filter sidebar on mobile after applying
                    if (window.innerWidth <= 768 && filterSidebar) {
                        filterSidebar.classList.remove('active');
                    }
                });
            }

            // Reset filters button
            var resetBtn = document.getElementById('resetFilters');
            if (resetBtn) {
                resetBtn.addEventListener('click', function() {
                    filterForm.reset();
                    currentFilters = {};
                    currentPage = 1;
                    loadProducts();
                });
            }

            // Sort options change
            var sortSelect = document.getElementById('sortBy');
            if (sortSelect) {
                sortSelect.addEventListener('change', function() {
                    currentSort = this.value;
                    currentPage = 1;
                    loadProducts();
                });
            }
        }

        // Collect all filter values from the form
        function collectFilters() {
            var categories = [];
            var categoryChecks = document.querySelectorAll('input[name="category[]"]:checked');
            categoryChecks.forEach(function(checkbox) {
                categories.push(checkbox.value);
            });
            
            var priceRange = document.querySelector('input[name="price_range"]:checked');
            var location = document.getElementById('search-location').value;
            
            currentFilters = {
                categories: categories,
                price_range: priceRange ? priceRange.value : '',
                location: location
            };
        }

        // Load products from server with current filters, sort, and page
        function loadProducts() {
            var params = new URLSearchParams();
            params.append('page', currentPage);
            params.append('sort', currentSort);
            
            if (currentFilters.categories && currentFilters.categories.length > 0) {
                params.append('categories', currentFilters.categories.join(','));
            }
            if (currentFilters.price_range) {
                params.append('price_range', currentFilters.price_range);
            }
            if (currentFilters.location) {
                params.append('location', currentFilters.location);
            }
            
            fetch('php/get-products.php?' + params.toString())
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    var grid = document.getElementById('products-grid');
                    
                    if (data.success && data.products && data.products.length > 0) {
                        displayProducts(data.products);
                        totalPages = data.total_pages || 1;
                        displayPagination();
                    } else {
                        grid.innerHTML = '<div class="no-products"><p>No products found matching your criteria.</p><button onclick="resetFilters()" class="reset-btn">Clear Filters</button></div>';
                    }
                })
                .catch(function(error) {
                    console.log('Error:', error);
                    document.getElementById('products-grid').innerHTML = '<p class="error">Error loading products. Please try again.</p>';
                });
        }

        // Display products in the grid
        function displayProducts(products) {
            var grid = document.getElementById('products-grid');
            grid.innerHTML = '';
            
            for (var i = 0; i < products.length; i++) {
                var product = products[i];
                
                // Determine which verification icon to show
                var verifiedIcon = product.is_verified ? 
                    '<div class="verified-icon"><img src="images/icons/verified-svgrepo-com.svg" width="16px" height="16px" alt="Verified Seller"></div>' : 
                    '<img src="images/icons/not-verified-svgrepo-com.svg" class="not-verified-icon" width="16px" height="16px" alt="Not Verified">';
                
                // Determine condition badge class and text
                var conditionClass = '';
                var conditionText = product.condition || 'Good';
                if (conditionText === 'New') conditionClass = 'new';
                else if (conditionText === 'Like New') conditionClass = 'like-new';
                else if (conditionText === 'Good') conditionClass = 'good';
                else if (conditionText === 'Fair') conditionClass = 'fair';
                
                // Create the product card
                var card = document.createElement('div');
                card.className = 'prod-card';
                card.innerHTML = `
                    <div class="img-container">
                        <a href="product-details.php?id=${product.id}">
                            <img src="${product.image}" alt="${escapeHtml(product.name)}" onerror="this.src='images/default-product.jpg'">
                        </a>
                        <div class="condition-badge ${conditionClass}">${conditionText}</div>
                    </div>
                    <div class="prod-info-container">
                        <h3 class="prod-name">
                            <a href="product-details.php?id=${product.id}">${escapeHtml(product.name)}</a>
                        </h3>
                        <p class="prod-price">R ${parseFloat(product.price).toFixed(2)}</p>
                        <div class="seller-info">
                            <div class="seller-avatar">
                                <img src="images/icons/profile-svgrepo-com.svg" alt="Seller">
                            </div>
                            <div class="seller-details">
                                <p class="seller-name">${escapeHtml(product.seller_name)}</p>
                                <p class="location">
                                    <img src="images/icons/pin-location-svgrepo-com.svg" width="10px" height="10px" alt="location">
                                    ${escapeHtml(product.location)}
                                </p>
                            </div>
                            ${verifiedIcon}
                        </div>
                        <button class="add-to-cart-btn" onclick="addToCart(${product.id}, '${escapeHtml(product.name).replace(/'/g, "\\'")}', ${product.price})">
                            <img src="images/icons/shopping-cart-01-svgrepo-com.svg" width="16px" height="16px" alt="Cart">
                            Add to Cart
                        </button>
                    </div>
                `;
                grid.appendChild(card);
            }
        }

        // Display pagination controls
        function displayPagination() {
            var paginationDiv = document.getElementById('pagination');
            if (!paginationDiv || totalPages <= 1) {
                if (paginationDiv) paginationDiv.innerHTML = '';
                return;
            }
            
            var html = '';
            
            // Previous button
            if (currentPage > 1) {
                html += '<button class="page-btn" onclick="goToPage(' + (currentPage - 1) + ')">← Previous</button>';
            }
            
            // Page numbers - show limited pages for better UX
            for (var i = 1; i <= totalPages; i++) {
                if (i === currentPage) {
                    html += '<button class="page-btn active" disabled>' + i + '</button>';
                } else if (Math.abs(i - currentPage) <= 2 || i === 1 || i === totalPages) {
                    html += '<button class="page-btn" onclick="goToPage(' + i + ')">' + i + '</button>';
                } else if (Math.abs(i - currentPage) === 3) {
                    html += '<span class="page-dots">...</span>';
                }
            }
            
            // Next button
            if (currentPage < totalPages) {
                html += '<button class="page-btn" onclick="goToPage(' + (currentPage + 1) + ')">Next →</button>';
            }
            
            paginationDiv.innerHTML = html;
        }

        // Navigate to specific page
        function goToPage(page) {
            currentPage = page;
            loadProducts();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Reset all filters and reload products
        function resetFilters() {
            var filterForm = document.getElementById('filterForm');
            if (filterForm) filterForm.reset();
            currentFilters = {};
            currentPage = 1;
            loadProducts();
        }

        // Helper function to escape HTML and prevent XSS attacks
        function escapeHtml(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>