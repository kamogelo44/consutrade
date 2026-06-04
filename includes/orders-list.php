<?php

/**
 * ConsuTrade - Orders List Component
 * Author: Kamogelo Phale
 * 
 * Complete orders list with filtering and search
 * 
 * Usage:
 * $orders = $ordersArray;
 * $role = 'buyer'; // or 'seller' or 'admin'
 * $status_filter = $_GET['status'] ?? 'all';
 * $search_term = $_GET['search'] ?? '';
 * include __DIR__ . '/includes/orders-list.php';
 */

$orders = $orders ?? [];
$role = $role ?? 'buyer';
$status_filter = $status_filter ?? 'all';
$search_term = $search_term ?? '';
$hasSearchTerm = !empty($search_term);
?>

<div class="filters-bar">
    <?php
    $currentStatus = $status_filter;
    include __DIR__ . '/filter-bar.php';
    ?>

    <?php
    $searchTerm = $search_term;
    $placeholder = 'Search by order number or ' . ($role === 'seller' ? 'customer...' : 'seller...');
    include __DIR__ . '/search-bar.php';
    ?>
</div>

<div class="orders-list">
    <?php if (count($orders) > 0): ?>
        <?php foreach ($orders as $orderData): ?>
            <?php
            $order = $orderData;
            include __DIR__ . '/order-card.php';
            ?>
        <?php endforeach; ?>
    <?php else: ?>
        <?php
        $emptyIcon = 'shopping-cart-01-svgrepo-com.svg';
        $emptyTitle = 'No Orders Found';
        $emptyMessage = $hasSearchTerm
            ? 'No orders match your search criteria.'
            : ($role === 'buyer' ? 'You haven\'t placed any orders yet.' : 'You have not received any orders yet.');
        $emptyButtonText = $role === 'buyer' ? 'Start Shopping' : 'Back to Dashboard';
        $emptyButtonLink = $role === 'buyer' ? 'product-listings.php' : ($role === 'seller' ? 'seller-dashboard.php' : 'admin-dashboard.php');
        include __DIR__ . '/empty-state.php';
        ?>
    <?php endif; ?>
</div>