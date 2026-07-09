<?php
$page_js = 'orders-seller.js';
$load_orders_js = true;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/sidebar.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/dashboard-layout.css">
</head>

<body>
    <?php include 'admin/includes/sidebar.php'; ?>

    <main class="seller-main-content">
        <div class="dashboard-content">
            <div class="orders-page-header">
                <div>
                    <h1>Orders</h1>
                    <p>Manage orders from your customers</p>
                </div>
                <?php if (isset($hasBuyerRole) && $hasBuyerRole): ?>
                    <a href="orders.php?tab=buyer" class="orders-switch-link">← Buying orders</a>
                <?php endif; ?>
            </div>

            <div class="orders-filters">
                <select id="statusFilter" class="orders-filter-select">
                    <option value="all">All Orders</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="shipped">Shipped</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <div class="orders-search">
                    <input type="text" id="searchInput" placeholder="Search orders or customers...">
                    <button id="searchBtn"><img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" width="16" height="16" alt="Search"></button>
                </div>
            </div>

            <div class="orders-table-wrap">
                <table class="orders-table">
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

    <!-- Order Details Modal -->
    <div id="orderModal" class="order-modal">
        <div class="order-modal-content">
            <div class="order-modal-header">
                <h2>Order Details</h2>
                <button class="btn-close" onclick="closeOrderModal()">&times;</button>
            </div>
            <div class="order-modal-body" id="orderModalBody"></div>
            <div class="order-modal-footer" id="orderModalFooter"></div>
        </div>
    </div>
    <script>
        var baseUrl = '<?php echo $baseUrl; ?>';
        var currentUserId = <?php echo $currentUser->getUserId(); ?>;
        var currentUserRole = '<?php echo $currentUserRole ?? ''; ?>';
        var isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
    </script>
    <script src="<?php echo $baseUrl; ?>js/lib/jquery-3.7.1.min.js"></script>
    <script src="<?php echo $baseUrl; ?>js/core/utils.js"></script>
    <script src="<?php echo $baseUrl; ?>js/core/ui.js"></script>
    <script src="<?php echo $baseUrl; ?>js/modules/orders.js"></script>
    <script src="<?php echo $baseUrl; ?>js/pages/orders-seller.js"></script>

</body>

</html>