<?php
/*
 * ConsuTrade - All Orders (Admin)
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__) . '/php/helpers.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

$baseUrl = getBaseUrl();

// Get database connection
require_once dirname(__DIR__) . '/php/config.php';

$current_page = 'all-orders';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Orders - ConsuTrade Admin</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/dashboard.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/my-orders.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <script>
        var baseUrl = '<?php echo $baseUrl; ?>';
    </script>
</head>
<body class="admin-dashboard-page">

<?php include 'includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="dashboard-header">
    <h1>All Orders</h1>
    <p>View and manage all orders on the marketplace</p>
</div>

<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Seller</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="orders-table">
            <tr><td colspan="7" style="text-align: center;">Loading orders...</td><td style="display: none;"></td><td style="display: none;"></td><td style="display: none;"></td><td style="display: none;"></td><td style="display: none;"></td><td style="display: none;"></td></tr>
        </tbody>
    </table>
</div>

</main>
</div>

<script src="<?php echo $baseUrl; ?>js/main.js"></script>
<script src="<?php echo $baseUrl; ?>admin/js/dashboard.js"></script>
<script>
$(document).ready(function() {
    loadAllOrders();
    
    function loadAllOrders() {
        var $tbody = $('#orders-table');
        $tbody.html('<tr><td colspan="7" style="text-align: center;">Loading orders...</td></tr>');
        
        $.ajax({
            url: baseUrl + 'admin/php/get-all-orders.php',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success && data.orders && data.orders.length) {
                    $tbody.empty();
                    $.each(data.orders, function(i, order) {
                        $tbody.append(`
                            <tr>
                                <td>#${order.order_id}</td>
                                <td>${escapeHtml(order.buyer_name)}</td>
                                <td>${escapeHtml(order.seller_name)}</td>
                                <td>R ${parseFloat(order.total_price).toFixed(2)}</td>
                                <td><span class="status-badge status-${order.status}">${order.status}</span></td>
                                <td>${order.created_at}</td>
                                <td>
                                    <button class="action-btn view-btn" onclick="viewOrder(${order.order_id})">View</button>
                                </td>
                            </tr>
                        `);
                    });
                } else {
                    $tbody.html('<tr><td colspan="7" style="text-align: center;">No orders found</td></tr>');
                }
            },
            error: function() {
                $tbody.html('<tr><td colspan="7" style="text-align: center;">Error loading orders</td></tr>');
            }
        });
    }
    
    window.viewOrder = function(orderId) {
        // Create modal or redirect to order details
        alert('Order details for #' + orderId + ' - Feature coming soon');
    };
});
</script>
</body>
</html>