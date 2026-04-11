<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="css/style.css">
        <link rel="stylesheet" href="css/products.css">
        <link rel="stylesheet" href="css/login-signup.css">
        <link rel="stylesheet" href="css/header.css">
        <title>Product Details</title>
    </head>
    <body>
        <!--Header-->
        <?php include 'header.php'?>
        
        <main>
            <section class="products-details">
                <div class="breadcrumb">
                    <a href="index.php">Home</a>
                    <span> > </span>
                    <a href="product-listings.php">Product Listings</a>
                    <span> > </span>
                    <span>Handmade Leather Bag</span>
                </div>
                <div class="top-items">
                    <div class="product-imgs">
                        <div class="main-img">
                            <img src="" alt="">
                        </div>
                        <div class="smaller-imgs">
                            <div class="small-img">
                                <img src="" alt="">
                            </div>
                            <div class="small-img">
                                <img src="" alt="">
                            </div>
                            <div class="small-img">
                                <img src="" alt="">
                            </div>
                            <div class="small-img">
                                <img src="" alt="">
                            </div>
                            
                        </div>
                    </div>
                    <div class="product-info">
                        <div class="price-desc">
                            <h1 class="details-prod-name">Handmade Leather Bag</h1>
                            <p class="details-price">R0.00</p>
                            <div class="cat-badge">
                                <p class="cat-name">Clothing & Accessories</p>
                            </div>
                        </div>
                        
                        
                        <div class="description">
                            <p class="sub-head">Description</p>
                            <p class="des">Handcrafted genuine leather bag made by a local artisan in Soweto. Available in brown and black. Suitable for everyday use. Contact seller for more details.</p>
                        </div>
                        
                        <div class="con-loc">
                            <p class="sub-head">Condition: <span class="condition">Used</span></p>
                            <p class="sub-head">Location: <span class="city">Polokwane, </span> <span class="province">Limpopo</span></p>
                        </div>
                    </div>
                </div>
                <section class="review">
                    <div class="rev-container">
                        <div class="seller-profile">
                            <div class="profile-pic">
                                <img src="images/icons/profile-round-1342-svgrepo-com.svg" class=profile-color width="24px" height="24px" alt="Seller Profile Picture">
                            </div>
                            <p class="seller-name">Jacobeth Thobakgale</p>
                        </div>
                        <div class="verification">
                                <div class="verified-badge">
                                    <img src="images/icons/verified-svgrepo-com.svg" width="20px" height="20px" alt="verification">
                                    <p>
                                        Verified Seller
                                    </p> 
                                </div>
                                <div class="not-verified-badge">
                                    <img src="images/icons/not-verified-svgrepo-com.svg" width="20px" height="20px" alt="not-verified">
                                    <p>
                                        Not Verified Seller
                                    </p>
                                </div>                    
                        </div>
                        <div class="star-reviews">
                            <h1 class="">Seller Reviews</h1>
                            <span class="star">★</span>
                            <span class="star">★</span>
                            <span class="star">★</span>
                            <span class="star">★</span>
                            <span class="star">★</span>
                            <p id="output">Rating is: 0/5</p>
                        </div>
                        <button class="view-profile">View Seller Profile</button>
                    </div>
                </section>

                <div class="actions">
                    <div class="actions-card">
                        <div class="avail">
                            <p>
                                <span class="num-avail"> 0 </span>
                                Available
                            </p>
                        </div>

                        <div class="action-btns">
                            <button class="cart-btn">
                                <img src="images/icons/shopping-cart-01-svgrepo-com.svg" width="24px" height="24px" alt="Cart">
                                Add to Cart
                            </button>
                            <button class="buy-btn"><img src="" alt="">Buy</button>
                            <button class="wish-btn"><img src="images/icons/wishlist-svgrepo-com.svg"width="24px" height="24px" alt="">
                                Add to Wishlist
                            </button>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!--Footer-->
        <?php include 'footer.php'?>
        <script src="js/main.js"></script>
    </body>
</html>