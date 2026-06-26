<?php
/*
 * ConsuTrade - Seller Orders
 * Author: Kamogelo Phale
 * 
 * Displays orders for the logged-in seller with filtering and pagination.
 */

require_once dirname(__DIR__) . '/init.php';
include dirname(__DIR__) . '/includes/session-vars.php';
include dirname(__DIR__) . '/includes/functions.php';

if (!$auth->isSeller()) {
    header('Location: login.php');
    exit;
}

$sellerId = $currentUser->getUserId();
$sellerName = $currentUser->getFullName();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - ConsuTrade Seller</title>

    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/sidebar.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>admin/css/dashboard-layout.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/orders.css">

    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>
    <style>
        /* Only page-specific styles that aren't in orders.css */
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

        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }

            .action-btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <main class="seller-main-content">
        <div class="dashboard-content">
            <div class="page-header">
                <h1>My Orders</h1>
                <p>View and manage orders from your customers</p>
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
                    <input type="text" id="searchInput" placeholder="Search by order number or customer...">
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
                            <th>Items</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="ordersTable">
                        <tr>
                            <td colspan="7">
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

        function loadSellerOrders() {
            loadOrders(
                'php/endpoints/orders/get-my-orders.php',
                $ordersTable,
                $pagination,
                currentPage,
                currentStatus,
                currentSearch,
                'seller',
                function(newPage) {
                    currentPage = newPage;
                    loadSellerOrders();
                    $('html, body').animate({
                        scrollTop: 0
                    }, 'smooth');
                }
            );
        }

        $(document).ready(function() {
            cacheElements();
            loadSellerOrders();

            $('#statusFilter').on('change', function() {
                currentStatus = $(this).val();
                currentPage = 1;
                loadSellerOrders();
            });

            $searchBtn.on('click', function() {
                currentSearch = $searchInput.val().trim();
                currentPage = 1;
                loadSellerOrders();
                $resetBtn.toggle(!!currentSearch);
            });

            $resetBtn.on('click', function() {
                $searchInput.val('');
                currentSearch = '';
                currentPage = 1;
                $('#statusFilter').val('all');
                currentStatus = 'all';
                loadSellerOrders();
                $(this).hide();
            });

            $searchInput.on('keypress', function(e) {
                if (e.which === 13) $searchBtn.click();
            });

            $('.order-modal-close').on('click', function() {
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