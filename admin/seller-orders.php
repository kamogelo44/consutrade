<?php
/*
 * ConsuTrade - All Orders (Admin)
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__) . '/init.php';
include dirname(__DIR__) . '/includes/session-vars.php';
include dirname(__DIR__) . '/includes/functions.php';

if (!$auth->isSeller()) {
    header('Location: login.php');
    exit;
}

$orders = $orderRepo->getAllOrders();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Orders - ConsuTrade</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/sidebar.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <style>
        .admin-main-content {
            margin-left: 280px;
            padding: var(--spacing-xl);
            min-height: 100vh;
            background: var(--gray-bg);
            transition: margin-left var(--transition-normal);
        }

        @media (max-width: 1024px) {
            .admin-main-content {
                margin-left: 0;
                padding: var(--spacing-md);
                padding-top: 70px;
            }
        }
    </style>
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <main class="admin-main-content">
        <div class="page-header">
            <h1>All Orders</h1>
            <p>View and manage all customer orders</p>
        </div>

        <?php
        $role = 'admin';
        include dirname(__DIR__) . '/includes/orders-list.php';
        ?>
    </main>

    <div id="orderModal" class="order-modal">
        <div class="order-modal-content">
            <div class="order-modal-header">
                <h2>Order Details</h2>
                <button class="order-modal-close" onclick="closeOrderModal()">&times;</button>
            </div>
            <div id="orderModalBody" class="order-details-content">
                <div class="loading-spinner">Loading order details...</div>
            </div>
            <div id="orderModalFooter" class="order-modal-footer"></div>
        </div>
    </div>

</body>

</html>