<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="/www/consutrade/css/sell.css">
        <link rel="stylesheet" href="/www/consutrade/css/style.css">
        <link rel="stylesheet" href="/www/consutrade/css/header.css">

        <title>Seller Dashboard</title>
    </head>
    <body>
        <!--header-->
        <?php
        include ('../header.php');
        ?>
        <main>
            <!--Stats section-->
            <section class="stats-sect">
                <div class="stats">
                    <div class="stat-card">
                        <h2 class="stat-num" id="total-sales-num">0</h2>
                        <p class="stat-name">Total Sales</p>
                    </div>

                    <div class="stat-card">
                        <h2 class="stat-num" id="active-listings">0</h2>
                        <p class="stat-name">Active Listings</p>
                    </div>

                    <div class="stat-card">
                        <h2 class="stat-num" id="pending-sales">0</h2>
                        <p class="stat-name">Pending Sales</p>
                    </div>
                </div>
            </section>

            <section class="listings-sect">
                <div class="left-col">
                    <h1 class="sect-head">My Listings</h1>
                    <div class="listing-card">
                        <div class="prod-img">
                            <img src="" alt="">
                        </div>
                        <div class="prod-descr">
                            <p class="prod-name"></p>
                            <p class="prod-price"></p>
                        </div>

                        <div class="btn-cont">
                            <button class="edit-btn">
                                Edit
                            </button>
                            <button class="delete-btn">
                                Delete
                            </button>
                        </div>

                    </div>
                </div>

                <div class="right-col">
                    <h1>Quick Actions</h1>
                    <div class="q-actions">
                        <button class="add-listing-btn">+ Add listing</button>
                        <button class="view-all-order">View All Orders</button>
                        <button class="Edit-my-profile">Edit My Profile</button>
                     </div>
                    <div class="recent-orders">
                        <h2>Recent Orders</h2>
                        <div class="rec-order-card">
                            <p>Order <span class=order-id>#0000</span></p>
                            <p class="cust-name">Lukas Malkop</p>
                            <p class="prod-price">R0</p>
                            <div class="order-state-container">
                                <p class="order-state">Completed</p>
                                <p classl="order-state">Pending</p>
                                <p class="order-state"> Cancelled</p>
                            </div>
                        </div>
                    </div>
                </div>

            </section>
        </main>
    </body>
</html>