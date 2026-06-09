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
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/dashboard-layout.css">
    <style>
        /* Orders page specific styles only */

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

        /* Order action buttons - page specific */
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
            transform: translateY(-1px);
        }

        .process-btn {
            background: var(--warning-light);
            color: var(--warning);
            border: 1px solid var(--warning);
        }

        .process-btn:hover {
            background: var(--warning);
            color: white;
            transform: translateY(-1px);
        }

        .ship-btn {
            background: var(--primary-fade);
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }

        .ship-btn:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-1px);
        }

        .complete-btn {
            background: var(--success-light);
            color: var(--success);
            border: 1px solid var(--success);
        }

        .complete-btn:hover {
            background: var(--success);
            color: white;
            transform: translateY(-1px);
        }

        .cancel-btn {
            background: var(--error-light);
            color: var(--error);
            border: 1px solid var(--error);
        }

        .cancel-btn:hover {
            background: var(--error);
            color: white;
            transform: translateY(-1px);
        }

        /* Order info rows - modal content styling */
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

        /* Modal footer buttons */
        .modal-footer {
            padding: var(--spacing-md) var(--spacing-lg);
            border-top: 1px solid var(--border-light);
            background: var(--gray-bg-light);
            display: flex;
            justify-content: flex-end;
            gap: var(--spacing-sm);
        }

        .modal-footer .process-btn,
        .modal-footer .ship-btn,
        .modal-footer .complete-btn,
        .modal-footer .cancel-btn {
            padding: 8px 20px;
            font-size: var(--font-sm);
        }

        /* Responsive overrides for orders page */
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

            .info-row {
                flex-direction: column;
                gap: var(--spacing-xs);
            }

            .info-label {
                width: 100%;
            }

            .modal-footer {
                flex-direction: column;
            }

            .modal-footer button {
                width: 100%;
            }
        }
    </style>
    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
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
                    <button id="searchBtn">
                        <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" width="16" height="16" alt="Search">
                    </button>
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
                            <td colspan="8" class="loading-spinner">Loading orders...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="pagination" id="pagination"></div>
        </div>
    </main>
    <!-- Order Details Modal - matching orders.css -->
    <div id="orderModal" class="order-modal">
        <div class="order-modal-content">
            <div class="order-modal-header">
                <h2>Order Details</h2>
                <button class="order-modal-close" onclick="closeOrderModal()">&times;</button>
            </div>
            <div class="order-details-content" id="orderModalBody">
                <div class="loading-spinner">Loading order details...</div>
            </div>
            <div class="modal-footer" id="orderModalFooter"></div>
        </div>
    </div>

    <script>
        // Page state
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

        function cacheElements() {
            $ordersTable = $('#ordersTable');
            $pagination = $('#pagination');
            $filterBtns = $('.status-filters .filter-btn');
            $searchBtn = $('#searchBtn');
            $resetBtn = $('#resetBtn');
            $searchInput = $('#searchInput');
        }

        function loadOrders() {
            $ordersTable.html('<tr><td colspan="8"><div class="loading-spinner">Loading orders...</div></td></tr>');

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
                        displayOrders(data.orders);
                        totalPages = data.total_pages || 1;
                        if (typeof renderPagination === 'function') {
                            renderPagination($pagination, currentPage, totalPages, function(page) {
                                currentPage = page;
                                loadOrders();
                                $('html, body').animate({
                                    scrollTop: 0
                                }, 'smooth');
                            });
                        } else {
                            $pagination.empty();
                        }
                    } else {
                        showEmptyState();
                        $pagination.empty();
                    }
                },
                error: function() {
                    $ordersTable.html('<tr><td colspan="8" class="error-cell">Error loading orders. Please refresh the page.</td></tr>');
                }
            });
        }

        function displayOrders(orders) {
            $ordersTable.empty();

            for (var i = 0; i < orders.length; i++) {
                var order = orders[i];
                var statusClass = getOrderStatusClass(order.status);
                var statusLabel = getStatusLabel(order.status);

                var actionButtons = '<div class="action-buttons">';
                actionButtons += '<button class="action-btn view-btn" onclick="openOrderModal(' + order.order_id + ')">View</button>';

                if (order.status === 'pending') {
                    actionButtons += '<button class="action-btn process-btn" onclick="updateOrderStatus(' + order.order_id + ', \'processing\')">Process</button>';
                } else if (order.status === 'processing') {
                    actionButtons += '<button class="action-btn ship-btn" onclick="updateOrderStatus(' + order.order_id + ', \'shipped\')">Ship</button>';
                } else if (order.status === 'shipped') {
                    actionButtons += '<button class="action-btn complete-btn" onclick="updateOrderStatus(' + order.order_id + ', \'completed\')">Complete</button>';
                }

                if (order.status === 'pending' || order.status === 'processing') {
                    actionButtons += '<button class="action-btn cancel-btn" onclick="updateOrderStatus(' + order.order_id + ', \'cancelled\')">Cancel</button>';
                }

                actionButtons += '</div>';

                $ordersTable.append(
                    '<tr>' +
                    '<td data-label="Order Number">#' + order.order_id + '</td>' +
                    '<td data-label="Customer">' + escapeHtml(order.buyer_name) + '</td>' +
                    '<td data-label="Seller">' + escapeHtml(order.seller_name) + '</td>' +
                    '<td data-label="Items">' + (order.item_count || 0) + '</td>' +
                    '<td data-label="Amount">R ' + parseFloat(order.total_price).toFixed(2) + '</td>' +
                    '<td data-label="Status"><span class="order-status-badge ' + statusClass + '">' + statusLabel + '</span></td>' +
                    '<td data-label="Date">' + escapeHtml(order.created_at) + '</td>' +
                    '<td data-label="Actions">' + actionButtons + '</td>' +
                    '</tr>'
                );
            }
        }

        function showEmptyState() {
            var emptyTitle = '';
            var emptyMessage = '';
            var emptyIcon = 'shopping-cart-01-svgrepo-com.svg';

            if (currentStatus !== 'all') {
                var statusName = capitalizeFirst(currentStatus);
                emptyTitle = 'No ' + statusName + ' Orders';
                emptyMessage = 'There are no ' + currentStatus + ' orders' + (currentSearch ? ' matching "' + escapeHtml(currentSearch) + '"' : '') + '.';
            } else if (currentSearch !== '') {
                emptyTitle = 'No Orders Found';
                emptyMessage = 'No orders matching "' + escapeHtml(currentSearch) + '" were found.';
            } else {
                emptyTitle = 'No Orders Yet';
                emptyMessage = 'No orders have been placed on the platform yet.';
            }

            var resetButtonHtml = '';
            if (currentSearch !== '' || currentStatus !== 'all') {
                resetButtonHtml = '<button onclick="document.getElementById(\'resetBtn\').click()" class="view-all-btn" style="background: var(--primary-color); color: white; padding: 10px 24px; border-radius: 8px; border: none; cursor: pointer; margin-top: 16px;">Clear Filters</button>';
            }

            $ordersTable.html(
                '<tr><td colspan="8" style="text-align: center; padding: 60px;">' +
                '<div class="empty-state">' +
                '<img src="' + baseUrl + 'images/icons/' + emptyIcon + '" width="64" height="64" alt="No orders" style="opacity: 0.4; margin-bottom: 16px;">' +
                '<h3 style="font-size: 20px; font-weight: var(--font-bold); margin-bottom: 8px; color: var(--dark-bg);">' + escapeHtml(emptyTitle) + '</h3>' +
                '<p style="color: var(--gray-medium); margin-bottom: 0;">' + escapeHtml(emptyMessage) + '</p>' +
                resetButtonHtml +
                '</div>' +
                '</td></tr>'
            );
        }

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
                $filterBtns.removeClass('active');
                $filterBtns.filter('[data-status="all"]').addClass('active');
                currentStatus = 'all';
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