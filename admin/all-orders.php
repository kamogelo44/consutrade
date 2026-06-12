<?php
/*
 * ConsuTrade - All Orders (Admin)
 * Author: Kamogelo Phale
 * 
 * Displays all orders on the marketplace for admin management.
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

    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/sidebar.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/dashboard-layout.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/orders.css">

    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <style>
        /* Only page-specific styles not in orders.css */
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

        .cancel-btn {
            background: var(--error-light);
            color: var(--error);
            border: 1px solid var(--error);
        }

        .cancel-btn:hover {
            background: var(--error);
            color: white;
        }

        /* Modal footer */
        .order-modal-footer {
            padding: var(--spacing-md) var(--spacing-lg);
            border-top: 1px solid var(--border-light);
            background: var(--gray-bg-light);
            display: flex;
            justify-content: flex-end;
            gap: var(--spacing-sm);
        }

        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }

            .action-btn {
                width: 100%;
                text-align: center;
            }

            .order-modal-footer {
                flex-direction: column;
            }

            .order-modal-footer button {
                width: 100%;
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
                <div class="filter-group">
                    <label>Filter by Status:</label>
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
                    <input type="text" id="searchInput" placeholder="Search by order number, customer, or seller...">
                    <button id="searchBtn">
                        <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" alt="Search">
                    </button>
                    <button id="resetBtn" class="reset-btn" style="display: none;">Reset</button>
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
                            <td colspan="8">
                                <div class="loading-spinner">Loading orders...</div>
                            </td>
                        </tr>
                    </tbody>
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
                <button class="order-modal-close" onclick="closeOrderModal()">&times;</button>
            </div>
            <div class="order-details-content" id="orderModalBody">
                <div class="loading-spinner">Loading order details...</div>
            </div>
            <div class="order-modal-footer" id="orderModalFooter"></div>
        </div>
    </div>

    <script>
        var $ordersTable = null,
            $pagination = null,
            $searchBtn = null,
            $resetBtn = null,
            $searchInput = null,
            currentPage = 1,
            currentStatus = 'all',
            currentSearch = '';

        function cacheElements() {
            $ordersTable = $('#ordersTable');
            $pagination = $('#pagination');
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
                        var totalPages = data.total_pages || 1;
                        if (typeof renderPagination === 'function') {
                            renderPagination($pagination, currentPage, totalPages, function(page) {
                                currentPage = page;
                                loadOrders();
                                $('html, body').animate({
                                    scrollTop: 0
                                }, 'smooth');
                            });
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

            if (currentStatus !== 'all') {
                emptyTitle = 'No ' + capitalizeFirst(currentStatus) + ' Orders';
                emptyMessage = 'There are no ' + currentStatus + ' orders' + (currentSearch ? ' matching "' + escapeHtml(currentSearch) + '"' : '') + '.';
            } else if (currentSearch !== '') {
                emptyTitle = 'No Orders Found';
                emptyMessage = 'No orders matching "' + escapeHtml(currentSearch) + '" were found.';
            } else {
                emptyTitle = 'No Orders Yet';
                emptyMessage = 'No orders have been placed on the platform yet.';
            }

            var resetHtml = '';
            if (currentSearch !== '' || currentStatus !== 'all') {
                resetHtml = '<button onclick="$resetBtn.click()" class="reset-btn" style="margin-top: 16px;">Clear Filters</button>';
            }

            $ordersTable.html(
                '<tr><td colspan="8" style="text-align: center; padding: 60px;">' +
                '<div class="empty-state">' +
                '<img src="' + baseUrl + 'images/icons/shopping-cart-01-svgrepo-com.svg" width="64" height="64" alt="No orders" style="opacity: 0.4;">' +
                '<h3>' + escapeHtml(emptyTitle) + '</h3>' +
                '<p>' + escapeHtml(emptyMessage) + '</p>' +
                resetHtml +
                '</div>' +
                '</td></tr>'
            );
        }

        $(document).ready(function() {
            cacheElements();
            loadOrders();

            $('#statusFilter').on('change', function() {
                currentStatus = $(this).val();
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
                $('#statusFilter').val('all');
                currentStatus = 'all';
                loadOrders();
                $(this).hide();
            });

            $searchInput.on('keypress', function(e) {
                if (e.which === 13) $searchBtn.click();
            });
        });
    </script>

</body>

</html>