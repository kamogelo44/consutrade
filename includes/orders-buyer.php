<?php
$breadcrumbItems = [
    ['url' => 'profile.php', 'label' => 'My Profile'],
    ['label' => 'My Orders']
];
$page_js = 'orders-buyer.js';
$load_orders_js = true;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/breadcrumb.php'; ?>

    <main class="orders-container">
        <div class="page-header">
            <h1>My Orders</h1>
            <p>Track and manage your purchases</p>
        </div>

        <?php if ($hasSellerRole): ?>
            <div class="orders-role-switch">
                <a href="?tab=seller" class="orders-switch-link">Switch to Selling →</a>
            </div>
        <?php endif; ?>

        <div class="filters-bar">
            <div class="filter-group">
                <select id="statusFilter">
                    <option value="all">All Orders</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="shipped">Shipped</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="search-group">
                <input type="text" id="searchInput" placeholder="Search orders...">
                <button id="searchBtn" class="search-btn">
                    <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" alt="Search" width="16" height="16">
                </button>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Seller</th>
                        <th>Items</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="ordersTable"></tbody>
            </table>
        </div>
        <div class="pagination" id="pagination"></div>
    </main>

    <div id="orderModal" class="order-modal">
        <div class="order-modal-content">
            <div class="order-modal-header">
                <h2>Order Details</h2>
                <button class="btn-close" onclick="closeOrderModal()">&times;</button>
            </div>
            <div id="orderModalBody"></div>
            <div id="orderModalFooter"></div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>

</html>