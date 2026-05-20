<?php
/*
 * ConsuTrade - All Orders (Admin)
 * Author: Kamogelo Phale
 * 
 * Displays all orders on the marketplace for admin management
 */

require_once dirname(__DIR__) . '/init.php';

if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

$baseUrl = getBaseUrl();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Orders - ConsuTrade Admin</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/dashboard-clean.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/sidebar-clean.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/modal.css">
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <script src="<?php echo $baseUrl; ?>js/main.js"></script>
    <script src="<?php echo $baseUrl; ?>admin/js/dashboard.js"></script>
    <script>var baseUrl = '<?php echo $baseUrl; ?>';</script>
    <style>
        .page-header { margin-bottom: var(--spacing-xl); }
        .page-header h1 { font-size: var(--font-2xl); font-weight: var(--font-bold); margin-bottom: var(--spacing-xs); }
        .page-header p { color: var(--gray-medium); }
        
        .filters-bar { display: flex; justify-content: space-between; margin-bottom: var(--spacing-lg); flex-wrap: wrap; gap: var(--spacing-md); align-items: center; }
        .status-filters { display: flex; gap: var(--spacing-sm); flex-wrap: wrap; }
        .filter-btn { padding: 8px 16px; border-radius: var(--radius-md); text-decoration: none; background: var(--white); border: 1px solid var(--border-light); color: var(--gray-dark); cursor: pointer; transition: all var(--transition-fast); font-size: var(--font-sm); }
        .filter-btn:hover { background: var(--primary-fade); border-color: var(--primary-color); color: var(--primary-color); }
        .filter-btn.active { background: var(--primary-color); color: var(--white); border-color: var(--primary-color); }
        
        .search-bar { display: flex; gap: var(--spacing-sm); }
        .search-bar input { padding: 8px 12px; border: 1px solid var(--border-light); border-radius: var(--radius-md); width: 250px; font-size: var(--font-md); }
        .search-bar input:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 2px rgba(255, 107, 0, 0.1); }
        .search-bar button { padding: 8px 16px; background: var(--primary-color); color: white; border: none; border-radius: var(--radius-md); cursor: pointer; font-weight: var(--font-medium); transition: all var(--transition-fast); }
        .search-bar button:hover { background: var(--primary-dark); transform: translateY(-1px); }
        
        .table-wrapper { overflow-x: auto; background: var(--white); border-radius: var(--radius-lg); border: 1px solid var(--border-light); }
        .data-table { width: 100%; border-collapse: collapse; font-size: var(--font-sm); }
        .data-table th, .data-table td { padding: var(--spacing-md); text-align: left; border-bottom: 1px solid var(--border-light); }
        .data-table th { background: var(--gray-bg-light); font-weight: var(--font-semibold); color: var(--gray-dark); }
        .data-table tr:hover td { background: var(--gray-bg-light); }
        
        .status-badge { display: inline-block; padding: 4px 10px; border-radius: var(--radius-round); font-size: var(--font-xs); font-weight: var(--font-medium); }
        .status-badge.pending { background: var(--warning-light); color: var(--warning); }
        .status-badge.processing { background: var(--info-light); color: var(--info); }
        .status-badge.shipped { background: var(--primary-fade); color: var(--primary-color); }
        .status-badge.completed { background: var(--success-light); color: var(--success); }
        .status-badge.cancelled { background: var(--error-light); color: var(--error); }
        
        .action-buttons { display: flex; gap: var(--spacing-sm); flex-wrap: wrap; }
        .action-btn { padding: 6px 12px; border-radius: var(--radius-sm); font-size: var(--font-xs); cursor: pointer; border: none; font-weight: var(--font-medium); transition: all var(--transition-fast); }
        .view-btn { background: var(--info-light); color: var(--info); border: 1px solid var(--info); }
        .view-btn:hover { background: var(--info); color: white; transform: translateY(-1px); }
        .process-btn { background: var(--warning-light); color: var(--warning); border: 1px solid var(--warning); }
        .process-btn:hover { background: var(--warning); color: white; transform: translateY(-1px); }
        .ship-btn { background: var(--primary-fade); color: var(--primary-color); border: 1px solid var(--primary-color); }
        .ship-btn:hover { background: var(--primary-color); color: white; transform: translateY(-1px); }
        .complete-btn { background: var(--success-light); color: var(--success); border: 1px solid var(--success); }
        .complete-btn:hover { background: var(--success); color: white; transform: translateY(-1px); }
        
        .pagination { display: flex; justify-content: center; gap: var(--spacing-sm); margin-top: var(--spacing-xl); flex-wrap: wrap; }
        .page-btn { padding: 8px 14px; border: 1px solid var(--border-light); background: var(--white); border-radius: var(--radius-md); cursor: pointer; transition: all var(--transition-fast); font-size: var(--font-sm); }
        .page-btn:hover { background: var(--primary-fade); border-color: var(--primary-color); color: var(--primary-color); }
        .page-btn.active { background: var(--primary-color); color: white; border-color: var(--primary-color); cursor: default; }
        .page-dots { padding: 8px 4px; color: var(--gray-light); }
        
        .loading-cell { text-align: center; padding: var(--spacing-xl); color: var(--gray-medium); }
        .error-cell { text-align: center; padding: var(--spacing-xl); color: var(--error); background: var(--error-light); border-left: 4px solid var(--error); }
        .empty-cell { text-align: center; padding: var(--spacing-xl); color: var(--gray-medium); }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .data-table th, .data-table td { padding: var(--spacing-sm); }
        }
        @media (max-width: 1024px) {
            .data-table { min-width: 800px; }
            .action-buttons { flex-direction: row; flex-wrap: wrap; }
        }
        @media (max-width: 768px) {
            .filters-bar { flex-direction: column; align-items: stretch; }
            .status-filters { justify-content: center; }
            .search-bar { justify-content: center; }
            .action-buttons { flex-direction: column; }
            .action-btn { width: 100%; text-align: center; }
            .data-table, .data-table tbody, .data-table tr, .data-table td { display: block; }
            .data-table thead { display: none; }
            .data-table tr { border: 1px solid var(--border-light); margin-bottom: var(--spacing-md); border-radius: var(--radius-md); }
            .data-table td { display: flex; justify-content: space-between; align-items: center; padding: var(--spacing-sm); border-bottom: 1px solid var(--border-light); }
            .data-table td:last-child { border-bottom: none; }
            .data-table td:before { content: attr(data-label); font-weight: var(--font-bold); color: var(--gray-dark); min-width: 120px; }
            .error-cell { display: block; text-align: center; }
        }
        @media (max-width: 480px) {
            .page-header h1 { font-size: var(--font-xl); }
            .search-bar input { width: 100%; }
            .pagination { gap: var(--spacing-xs); }
            .page-btn { padding: 6px 10px; font-size: var(--font-xs); }
        }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<main class="admin-main-content">
    <div class="dashboard-content">
        <div class="page-header">
            <h1>All Orders</h1>
            <p>View and manage all orders on the marketplace</p>
        </div>

        <div class="filters-bar">
            <div class="status-filters">
                <button data-status="all" class="filter-btn active">All Orders</button>
                <button data-status="pending" class="filter-btn">Pending</button>
                <button data-status="processing" class="filter-btn">Processing</button>
                <button data-status="shipped" class="filter-btn">Shipped</button>
                <button data-status="completed" class="filter-btn">Completed</button>
                <button data-status="cancelled" class="filter-btn">Cancelled</button>
            </div>
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Search by order number, customer, or seller...">
                <button id="searchBtn">Search</button>
                <button id="resetBtn" style="display: none;">Reset</button>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order Number</th>
                        <th>Customer</th>
                        <th>Seller</th>
                        <th>Items</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="ordersTable">
                    <td><td colspan="8" class="loading-cell">Loading orders...</td></tr>
                </tbody>
            </table>
        </div>
        
        <div class="pagination" id="pagination"></div>
    </div>
</main>

<div id="orderModal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h3>Order Details</h3>
            <button class="modal-close" onclick="closeOrderModal()">&times;</button>
        </div>
        <div class="modal-body" id="orderModalBody">
            <div class="loading-spinner">Loading order details...</div>
        </div>
        <div class="modal-footer" id="orderModalFooter"></div>
    </div>
</div>

<script src="<?php echo $baseUrl; ?>admin/js/dashboard.js"></script>
<script>
var currentPage = 1, currentStatus = 'all', currentSearch = '', totalPages = 1;

$(function() {
    loadOrders();
    
    $('.status-filters .filter-btn').on('click', function() {
        $('.status-filters .filter-btn').removeClass('active');
        $(this).addClass('active');
        currentStatus = $(this).data('status');
        currentPage = 1;
        loadOrders();
    });
    
    $('#searchBtn').on('click', function() {
        currentSearch = $('#searchInput').val().trim();
        currentPage = 1;
        loadOrders();
        $('#resetBtn').toggle(!!currentSearch);
    });
    
    $('#resetBtn').on('click', function() {
        $('#searchInput').val('');
        currentSearch = '';
        currentPage = 1;
        loadOrders();
        $(this).hide();
    });
    
    $('#searchInput').on('keypress', function(e) {
        if (e.which === 13) $('#searchBtn').click();
    });
});

function loadOrders() {
    $('#ordersTable').html('<tr><td colspan="8" class="loading-cell">Loading orders...</td></tr>');
    
    $.ajax({
        url: baseUrl + 'admin/php/get-all-orders.php',
        type: 'GET',
        dataType: 'json',
        data: { page: currentPage, status: currentStatus, search: currentSearch },
        success: function(data) {
            if (data.success && data.orders && data.orders.length) {
                displayOrders(data.orders);
                totalPages = data.total_pages;
                displayPagination();
            } else {
                $('#ordersTable').html('<tr><td colspan="8" class="empty-cell">No orders found</td></tr>');
                $('#pagination').empty();
            }
        },
        error: function() {
            $('#ordersTable').html('<td><td colspan="8" class="error-cell">Error loading orders. Please refresh and try again.</td></tr>');
        }
    });
}

function displayOrders(orders) {
    var $tbody = $('#ordersTable');
    $tbody.empty();
    
    $.each(orders, function(i, order) {
        var statusClass = order.status;
        var actionButtons = '<button class="action-btn view-btn" onclick="openOrderModal(' + order.order_id + ')">View</button>';
        
        if (order.status === 'pending') {
            actionButtons += '<button class="action-btn process-btn" onclick="updateOrderStatus(' + order.order_id + ', \'processing\')">Process</button>';
        } else if (order.status === 'processing') {
            actionButtons += '<button class="action-btn ship-btn" onclick="updateOrderStatus(' + order.order_id + ', \'shipped\')">Ship</button>';
        } else if (order.status === 'shipped') {
            actionButtons += '<button class="action-btn complete-btn" onclick="updateOrderStatus(' + order.order_id + ', \'completed\')">Complete</button>';
        }
        
        $tbody.append(`
            <tr>
                <td data-label="Order Number">#${order.order_id}</td>
                <td data-label="Customer">${escapeHtml(order.buyer_name)}</td>
                <td data-label="Seller">${escapeHtml(order.seller_name)}</td>
                <td data-label="Items">${order.item_count || 0}</td>
                <td data-label="Amount">R ${parseFloat(order.total_price).toFixed(2)}</td>
                <td data-label="Status"><span class="status-badge ${statusClass}">${order.status}</span></td>
                <td data-label="Date">${order.created_at}</td>
                <td data-label="Actions" class="action-buttons">${actionButtons}</td>
            </tr>
        `);
    });
}

function displayPagination() {
    if (totalPages <= 1) { $('#pagination').empty(); return; }
    
    var html = '';
    if (currentPage > 1) html += `<button class="page-btn" onclick="goToPage(${currentPage - 1})">← Previous</button>`;
    
    for (var i = 1; i <= totalPages; i++) {
        if (i === currentPage) {
            html += `<button class="page-btn active" disabled>${i}</button>`;
        } else if (Math.abs(i - currentPage) <= 2 || i === 1 || i === totalPages) {
            html += `<button class="page-btn" onclick="goToPage(${i})">${i}</button>`;
        } else if (Math.abs(i - currentPage) === 3) {
            html += `<span class="page-dots">...</span>`;
        }
    }
    
    if (currentPage < totalPages) html += `<button class="page-btn" onclick="goToPage(${currentPage + 1})">Next →</button>`;
    $('#pagination').html(html);
}

function goToPage(page) { currentPage = page; loadOrders(); $('html, body').animate({ scrollTop: 0 }, 'smooth'); }
</script>

</body>
</html>