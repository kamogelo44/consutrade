<?php
$breadcrumbItems = [
    ['url' => 'profile.php', 'label' => 'My Profile'],
    ['label' => 'My Orders']
];
$page_js = 'orders-seller.js';
$load_orders_js = true;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/sidebar.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/dashboard-layout.css">
</head>

<body>
    <?php include 'admin/includes/sidebar.php'; ?>

    <main class="seller-main-content">
        <div class="dashboard-content">
            <div class="page-header">
                <h1>Orders</h1>
                <p>Manage orders from your customers</p>
            </div>

            <?php if ($hasBuyerRole): ?>
                <div class="orders-role-switch">
                    <a href="?tab=buyer" class="orders-switch-link">← Switch to Buying</a>
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
                    <input type="text" id="searchInput" placeholder="Search orders or customers...">
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
                            <th>Customer</th>
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
        </div>
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