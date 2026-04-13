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
                    <div class="listings-grid">
                        <!-- Card 1 -->
                        <div class="prod-card">
                            <div class="img-container">
                                <img src="" alt="Product Image">
                            </div>
                            <p class="prod-name">Product Name</p>
                            <p class="prod-price">R 0.00</p>
                            <div class="seller-info">
                                <img src="" alt="Seller Profile Picture">
                                <p class="seller-name">Seller: Gethro Molungsi</p>
                                <p class="location">Polokwane</p>
                                <img src="images/icons/verified-svgrepo-com.svg" width="24px" height="24px" alt="Verified">
                                <img src="images/icons/not-verified-svgrepo-com.svg" class="not-verified-icon" width="24px" height="24px" alt="Not Verified">
                            </div>
                            <button class="add-to-cart-btn" data-id="1" data-name="Product Name" data-price="99.99">
                                Add to Cart
                            </button>
                        </div>

                        <!-- Card 2 -->
                        <div class="prod-card">
                            <div class="img-container">
                                <img src="" alt="Product Image">
                            </div>
                            <p class="prod-name">Product Name</p>
                            <p class="prod-price">R 0.00</p>
                            <div class="seller-info">
                                <img src="" alt="Seller Profile Picture">
                                <p class="seller-name">Seller: Gethro Molungsi</p>
                                <p class="location">Polokwane</p>
                                <img src="images/icons/verified-svgrepo-com.svg" width="24px" height="24px" alt="Verified">
                                <img src="images/icons/not-verified-svgrepo-com.svg" class="not-verified-icon" width="24px" height="24px" alt="Not Verified">
                            </div>
                            <button class="add-to-cart-btn" data-id="1" data-name="Product Name" data-price="99.99">
                                Add to Cart
                            </button>                            
                        </div>

                        <!-- Card 3 -->
                        <div class="prod-card">
                            <div class="img-container">
                                <img src="" alt="Product Image">
                            </div>
                            <p class="prod-name">Product Name</p>
                            <p class="prod-price">R 0.00</p>
                            <div class="seller-info">
                                <img src="" alt="Seller Profile Picture">
                                <p class="seller-name">Seller: Gethro Molungsi</p>
                                <p class="location">Polokwane</p>
                                <img src="images/icons/verified-svgrepo-com.svg" width="24px" height="24px" alt="Verified">
                                <img src="images/icons/not-verified-svgrepo-com.svg" class="not-verified-icon" width="24px" height="24px" alt="Not Verified">
                            </div>
                            <button class="add-to-cart-btn" data-id="1" data-name="Product Name" data-price="99.99">
                                Add to Cart
                            </button>                            
                        </div>

                        <!-- Card 4 -->
                        <div class="prod-card">
                            <div class="img-container">
                                <img src="" alt="Product Image">
                            </div>
                            <p class="prod-name">Product Name</p>
                            <p class="prod-price">R 0.00</p>
                            <div class="seller-info">
                                <img src="" alt="Seller Profile Picture">
                                <p class="seller-name">Seller: Gethro Molungsi</p>
                                <p class="location">Polokwane</p>
                                <img src="images/icons/verified-svgrepo-com.svg" width="24px" height="24px" alt="Verified">
                                <img src="images/icons/not-verified-svgrepo-com.svg" class="not-verified-icon" width="24px" height="24px" alt="Not Verified">
                            </div>
                            <button class="add-to-cart-btn" data-id="1" data-name="Product Name" data-price="99.99">
                                Add to Cart
                            </button>                            
                        </div>
                    </div>
                </section>
            </div>
        </main>

        <!--Footer-->
        <?php include 'footer.php'?>
        
        <script src="js/products.js"></script>
        <script src="js/main.js"></script>
    </body>
</html>