<?php
/*
 * ConsuTrade - All Orders (Admin)
 * Author: Kamogelo Phale
 * 
 * Displays all orders on the marketplace for admin management.
 * Uses main.js for modal, toast, pagination, and admin orders table rendering.
 */

require_once dirname(__DIR__) . '/init.php';
include dirname(__DIR__) . '/includes/session-vars.php';
include dirname(__DIR__) . '/includes/functions.php';

if (!$auth->isAdmin()) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Orders - ConsuTrade Admin</title>

    <!-- CSS Imports - Using component-based architecture -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/sidebar.css">
    <style>
        /* Page-specific styles that don't belong in components */
        .admin-main-content {
            margin-left: 280px;
            padding: var(--spacing-xl);
            min-height: 100vh;
            background: var(--gray-bg);
            transition: margin-left var(--transition-normal);
        }

        .dashboard-content {
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            margin-bottom: var(--spacing-xl);
        }

        .page-header h1 {
            font-size: var(--font-2xl);
            font-weight: var(--font-bold);
            margin-bottom: var(--spacing-xs);
        }

        .page-header p {
            color: var(--gray-medium);
        }

        .filters-bar {
            display: flex;
            justify-content: space-between;
            margin-bottom: var(--spacing-lg);
            flex-wrap: wrap;
            gap: var(--spacing-md);
            align-items: center;
        }

        .status-filters {
            display: flex;
            gap: var(--spacing-sm);
            flex-wrap: wrap;
        }

        /* Filter button styles - using component pattern */
        .filter-btn {
            padding: 8px 16px;
            border-radius: var(--radius-md);
            text-decoration: none;
            background: var(--white);
            border: 1px solid var(--border-light);
            color: var(--gray-dark);
            cursor: pointer;
            transition: all var(--transition-fast);
            font-size: var(--font-sm);
        }

        .filter-btn:hover {
            background: var(--primary-fade);
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .filter-btn.active {
            background: var(--primary-color);
            color: var(--white);
            border-color: var(--primary-color);
        }

        .search-bar {
            display: flex;
            gap: var(--spacing-sm);
        }

        .search-bar input {
            padding: 8px 12px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            width: 250px;
            font-size: var(--font-md);
        }

        .search-bar input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 107, 0, 0.1);
        }

        .search-bar button {
            padding: 8px 16px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: var(--font-medium);
            transition: all var(--transition-fast);
        }

        .search-bar button:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .table-wrapper {
            overflow-x: auto;
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-light);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: var(--font-sm);
        }

        .data-table th,
        .data-table td {
            padding: var(--spacing-md);
            text-align: left;
            border-bottom: 1px solid var(--border-light);
        }

        .data-table th {
            background: var(--gray-bg-light);
            font-weight: var(--font-semibold);
            color: var(--gray-dark);
        }

        .data-table tr:hover td {
            background: var(--gray-bg-light);
        }

        /* Status badges - using component styles from main.css */
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: var(--radius-round);
            font-size: var(--font-xs);
            font-weight: var(--font-medium);
        }

        .status-badge.pending {
            background: var(--warning-light);
            color: var(--warning);
        }

        .status-badge.processing {
            background: var(--info-light);
            color: var(--info);
        }

        .status-badge.shipped {
            background: var(--primary-fade);
            color: var(--primary-color);
        }

        .status-badge.completed {
            background: var(--success-light);
            color: var(--success);
        }

        .status-badge.cancelled {
            background: var(--error-light);
            color: var(--error);
        }

        .action-buttons {
            display: flex;
            gap: var(--spacing-sm);
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            font-size: var(--font-xs);
            cursor: pointer;
            border: none;
            font-weight: var(--font-medium);
            transition: all var(--transition-fast);
        }

        .view-btn {
            background: var(--info-light);
            color: var(--info);
            border: 1px solid var(--info);
        }

        .view-btn:hover {
            background: var(--info);
            color: white;
        }

        .process-btn {
            background: var(--warning-light);
            color: var(--warning);
            border: 1px solid var(--warning);
        }

        .process-btn:hover {
            background: var(--warning);
            color: white;
        }

        .ship-btn {
            background: var(--primary-fade);
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }

        .ship-btn:hover {
            background: var(--primary-color);
            color: white;
        }

        .complete-btn {
            background: var(--success-light);
            color: var(--success);
            border: 1px solid var(--success);
        }

        .complete-btn:hover {
            background: var(--success);
            color: white;
        }

        /* Modal styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            visibility: hidden;
            opacity: 0;
            transition: visibility 0.3s ease, opacity 0.3s ease;
        }

        .modal-overlay.active {
            visibility: visible;
            opacity: 1;
        }

        .modal-container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-md) var(--spacing-lg);
            border-bottom: 1px solid var(--border-light);
            background: var(--white);
        }

        .modal-header h3 {
            font-size: var(--font-lg);
            font-weight: var(--font-semibold);
            margin: 0;
            color: var(--dark-bg);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--gray-medium);
            transition: all var(--transition-fast);
            line-height: 1;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-round);
        }

        .modal-close:hover {
            background: var(--error-light);
            color: var(--error);
        }

        .modal-body {
            flex: 1;
            overflow-y: auto;
            padding: var(--spacing-lg);
            background: var(--white);
        }

        .modal-footer {
            padding: var(--spacing-md) var(--spacing-lg);
            border-top: 1px solid var(--border-light);
            background: var(--gray-bg-light);
            display: flex;
            justify-content: flex-end;
            gap: var(--spacing-sm);
        }

        /* Pagination - using component styles */
        .pagination {
            display: flex;
            justify-content: center;
            gap: var(--spacing-sm);
            margin-top: var(--spacing-xl);
            flex-wrap: wrap;
        }

        .page-btn {
            padding: 8px 14px;
            border: 1px solid var(--border-light);
            background: var(--white);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all var(--transition-fast);
            font-size: var(--font-sm);
        }

        .page-btn:hover {
            background: var(--primary-fade);
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .page-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            cursor: default;
        }

        .page-dots {
            padding: 8px 4px;
            color: var(--gray-light);
        }

        /* Loading and empty states */
        .loading-cell {
            text-align: center;
            padding: var(--spacing-xl);
            color: var(--gray-medium);
        }

        .error-cell {
            text-align: center;
            padding: var(--spacing-xl);
            color: var(--error);
            background: var(--error-light);
        }

        .empty-cell {
            text-align: center;
            padding: var(--spacing-xl);
            color: var(--gray-medium);
        }

        .loading-spinner {
            text-align: center;
            padding: var(--spacing-xl);
            color: var(--gray-medium);
        }

        /* Order details styles */
        .order-info-section {
            margin-bottom: var(--spacing-lg);
        }

        .info-row {
            display: flex;
            padding: var(--spacing-sm) 0;
            border-bottom: 1px solid var(--border-light);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: var(--font-semibold);
            width: 140px;
            color: var(--gray-dark);
        }

        .info-value {
            flex: 1;
            color: var(--dark-bg);
        }

        .order-items-list {
            margin-top: var(--spacing-md);
        }

        .order-item {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            padding: var(--spacing-md);
            border-bottom: 1px solid var(--border-light);
            background: var(--gray-bg-light);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-sm);
        }

        .order-item-img {
            width: 60px;
            height: 60px;
            flex-shrink: 0;
            background: var(--gray-bg);
            border-radius: var(--radius-md);
            overflow: hidden;
        }

        .order-item-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .order-item-details {
            flex: 1;
        }

        .order-item-details h4 {
            font-size: var(--font-md);
            font-weight: var(--font-semibold);
            margin: 0 0 var(--spacing-xs) 0;
            color: var(--dark-bg);
        }

        .order-item-details p {
            font-size: var(--font-sm);
            color: var(--gray-medium);
            margin: 0;
        }

        .order-item-price {
            font-weight: var(--font-bold);
            color: var(--primary-color);
            font-size: var(--font-md);
        }

        .order-total-section {
            margin-top: var(--spacing-lg);
            padding-top: var(--spacing-md);
            border-top: 2px solid var(--border-light);
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: var(--spacing-xs) 0;
            font-size: var(--font-md);
            color: var(--gray-dark);
        }

        .total-row.grand-total {
            font-size: var(--font-lg);
            font-weight: var(--font-bold);
            color: var(--primary-color);
            margin-top: var(--spacing-sm);
            padding-top: var(--spacing-sm);
            border-top: 1px solid var(--border-light);
        }

        @media (max-width: 1024px) {
            .admin-main-content {
                margin-left: 0;
                padding: var(--spacing-md);
                padding-top: 70px;
            }
        }

        @media (max-width: 768px) {
            .filters-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .status-filters {
                justify-content: center;
            }

            .search-bar {
                justify-content: center;
            }

            .search-bar input {
                width: 100%;
            }

            .action-buttons {
                flex-direction: column;
            }

            .action-btn {
                width: 100%;
                text-align: center;
            }

            .data-table,
            .data-table tbody,
            .data-table tr,
            .data-table td {
                display: block;
            }

            .data-table thead {
                display: none;
            }

            .data-table tr {
                border: 1px solid var(--border-light);
                margin-bottom: var(--spacing-md);
                border-radius: var(--radius-md);
            }

            .data-table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: var(--spacing-sm);
                border-bottom: 1px solid var(--border-light);
            }

            .data-table td:last-child {
                border-bottom: none;
            }

            .data-table td:before {
                content: attr(data-label);
                font-weight: var(--font-bold);
                color: var(--gray-dark);
                min-width: 120px;
            }

            .modal-container {
                width: 95%;
                max-height: 95vh;
            }

            .info-row {
                flex-direction: column;
                gap: var(--spacing-xs);
            }

            .info-label {
                width: 100%;
            }

            .order-item {
                flex-direction: column;
                text-align: center;
            }

            .order-item-img {
                width: 80px;
                height: 80px;
                margin: 0 auto;
            }

            .modal-footer {
                flex-direction: column;
            }

            .modal-footer button {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .admin-main-content {
                padding: var(--spacing-sm);
                padding-top: 60px;
            }

            .page-header h1 {
                font-size: var(--font-xl);
            }

            .pagination {
                gap: var(--spacing-xs);
            }

            .page-btn {
                padding: 6px 10px;
                font-size: var(--font-xs);
            }

            .modal-body {
                padding: var(--spacing-md);
            }

            .modal-header {
                padding: var(--spacing-sm) var(--spacing-md);
            }

            .modal-header h3 {
                font-size: var(--font-base);
            }
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
                        <tr>
                            <td colspan="8" class="loading-cell">Loading orders...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="pagination" id="pagination"></div>
        </div>
    </main>

    <!-- Modal -->
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

    <?php include 'includes/footer.php'; ?>

    <script>
        // ========== GLOBAL VARIABLES ==========
        var $ordersTable = null,
            $pagination = null,
            $filterBtns = null,
            $searchBtn = null,
            $resetBtn = null,
            $searchInput = null,
            currentPage = 1,
            currentStatus = 'all',
            currentSearch = '',
            totalPages = 1;

        // ========== CACHE ELEMENTS ==========
        function cacheElements() {
            $ordersTable = $('#ordersTable');
            $pagination = $('#pagination');
            $filterBtns = $('.status-filters .filter-btn');
            $searchBtn = $('#searchBtn');
            $resetBtn = $('#resetBtn');
            $searchInput = $('#searchInput');
        }

        // ========== LOAD ORDERS ==========
        function loadOrders() {
            $ordersTable.html('<tr><td colspan="8" class="loading-cell">Loading orders...</td></tr>');
            $.ajax({
                url: baseUrl + 'php/endpoints/get-all-orders.php',
                type: 'GET',
                dataType: 'json',
                data: {
                    page: currentPage,
                    status: currentStatus,
                    search: currentSearch
                },
                success: function(data) {
                    if (data.success && data.orders && data.orders.length) {
                        renderAdminOrdersTable(data.orders, $ordersTable);
                        totalPages = data.total_pages || 1;
                        renderPagination($pagination, currentPage, totalPages, function(page) {
                            currentPage = page;
                            loadOrders();
                            $('html, body').animate({
                                scrollTop: 0
                            }, 'smooth');
                        });
                    } else {
                        $ordersTable.html('<tr><td colspan="8" class="empty-cell">No orders found</td></tr>');
                        $pagination.empty();
                    }
                },
                error: function() {
                    $ordersTable.html('</td><td colspan="8" class="error-cell">Error loading orders</td></tr>');
                }
            });
        }

        // ========== EVENT HANDLERS ==========
        $(document).ready(function() {
            cacheElements();
            loadOrders();

            $filterBtns.on('click', function() {
                $filterBtns.removeClass('active');
                $(this).addClass('active');
                currentStatus = $(this).data('status');
                currentPage = 1;
                loadOrders();
            });

            $searchBtn.on('click', function() {
                currentSearch = $searchInput.val().trim();
                currentPage = 1;
                loadOrders();
                $resetBtn.toggle(!!currentSearch);
            });

            $resetBtn.on('click', function() {
                $searchInput.val('');
                currentSearch = '';
                currentPage = 1;
                loadOrders();
                $(this).hide();
            });

            $searchInput.on('keypress', function(e) {
                if (e.which === 13) $searchBtn.click();
            });

            $('.modal-close').on('click', function() {
                closeOrderModal();
            });

            $('#orderModal').on('click', function(e) {
                if ($(e.target).is('#orderModal')) {
                    closeOrderModal();
                }
            });
        });
    </script>

</body>

</html>