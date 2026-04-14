<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ConsuTrade - Buy and Sell Across South Africa</title>
        <link rel="stylesheet" href="css/style.css">
        <link rel="stylesheet" href="css/products.css">
        <link rel="stylesheet" href="css/login-signup.css">
        <link rel="stylesheet" href="css/header.css">
    </head>
    <body>
        <!--Header-->
        <?php include 'header.php'?>

        <main>
            <div class="listings-body">
                <!--Filter button for mobile-->
                <button class="filter-btn"><img src="images/icons/filter-svgrepo-com.svg" alt="filter">Filter</button>
                
                <!--Filter sidebar-->
                <div class="filter-sidebar">
                    <form action="">
                        <fieldset class="filter-fields">
                            <legend class="filter-title">Filter Results</legend>
                            
                            <fieldset class="filter-category">
                                <legend class="filter-heading">Category</legend>
                                <input type="checkbox" id="chk" name="check">
                                <label for="chk">Clothing</label>
                                <br>
                                <input type="checkbox" id="elect" name="electro">
                                <label for="elect">Electronics</label>
                                <br>
                                <input type="checkbox" id="fd" name="food-drink">
                                <label for="fd">Food and Drinks</label>
                                <br>
                                <input type="checkbox" id="furn" name="furniture">
                                <label for="furn">Furniture</label>
                                <br>
                                <input type="checkbox" id="oth" name="other">
                                <label for="oth">Other</label>
                            </fieldset>
                            
                            <fieldset class="filter-price">
                                <legend class="filter-heading">Price Range</legend>
                                <input type="checkbox" id="un" name="under">
                                <label for="un">Under 100</label>
                                <br>
                                <input type="checkbox" id="bet1" name="between1">
                                <label for="bet1">R100 - R500</label>
                                <br>
                                <input type="checkbox" id="bet2" name="between2">
                                <label for="bet2">R500 - R1000</label>
                                <br>
                                <input type="checkbox" id="ov" name="over">
                                <label for="ov">Over R1000</label>
                            </fieldset>
                            
                            <fieldset class="filter-location">
                                <legend class="filter-heading">Location</legend>
                                <div class="search-loc-wrapper">
                                    <input type="search"
                                        id="search-location"
                                        name="lq"
                                        placeholder="Enter city or province...">
                                </div>
                            </fieldset>
                            
                            <button class="apply-filter-btn" type="submit">
                                Apply Filters
                            </button>
                        </fieldset> 
                    </form>
                </div>
                
                <!--Products grid for listings page-->
                <section class="listings-products">
                    <div class="listings-grid" id="products-grid">
                        <!-- Products will be loaded here by JavaScript -->
                        <div class="loading-spinner">Loading products...</div>
                    </div>
                </section>
            </div>
        </main>
    <script>
    // Load products when page loads
    document.addEventListener('DOMContentLoaded', function() {
        fetchProducts();
    });

    function fetchProducts() {
        fetch('php/get-products.php')
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success && data.products.length > 0) {
                    displayProducts(data.products);
                } else {
                    document.getElementById('products-grid').innerHTML = '<p class="no-products">No products found.</p>';
                }
            })
            .catch(function(error) {
                console.log('Error:', error);
                document.getElementById('products-grid').innerHTML = '<p class="error">Error loading products. Please try again.</p>';
            });
    }

    function displayProducts(products) {
        var grid = document.getElementById('products-grid');
        grid.innerHTML = '';
        
        for (var i = 0; i < products.length; i++) {
            var product = products[i];
            var verifiedIcon = product.is_verified ? 
                '<img src="images/icons/verified-svgrepo-com.svg" width="24px" height="24px" alt="Verified">' : 
                '<img src="images/icons/not-verified-svgrepo-com.svg" class="not-verified-icon" width="24px" height="24px" alt="Not Verified">';
            
            var card = document.createElement('div');
            card.className = 'prod-card';
            card.innerHTML = `
                <a href="product-details.php?id=${product.id}" class="product-link">
                    <div class="img-container">
                        <img src="${product.image}" alt="${product.name}">
                    </div>
                    <p class="prod-name">${product.name}</p>
                    <p class="prod-price">R ${parseFloat(product.price).toFixed(2)}</p>
                    <div class="seller-info">
                        <img src="images/icons/profile-svgrepo-com.svg" alt="Seller Profile Picture">
                        <p class="seller-name">Seller: ${product.seller_name}</p>
                        <p class="location">${product.location}</p>
                        ${verifiedIcon}
                    </div>
                </a>
                <button class="add-to-cart-btn" onclick="addToCart(${product.id}, '${product.name.replace(/'/g, "\\'")}', ${product.price})">
                    Add to Cart
                </button>
            `;
            grid.appendChild(card);
        }
    }
    </script>
        <!--Footer-->
        <?php include 'footer.php'?>
        
        <script src="js/products.js"></script>
        <script src="js/main.js"></script>
    </body>
</html>