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

    <script src="<?php echo $baseUrl; ?>js/jquery-3.7.1.min.js"></script>

    <style>
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

        .filter-btn {
            padding: 8px 16px;
            border-radius: var(--radius-md);
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
            align-items: center;
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
            padding: 8px 12px;
            background: var(--primary-color);
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all var(--transition-fast);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .search-bar button img {
            width: 16px;
            height: 16px;
            filter: brightness(0) invert(1);
        }

        .search-bar button:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .reset-search-btn {
            padding: 8px 16px;
            background: var(--gray-bg);
            color: var(--gray-dark);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: var(--font-medium);
            transition: all var(--transition-fast);
        }

        .reset-search-btn:hover {
            background: var(--gray-lighter);
            transform: translateY(-1px);
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
        }

        @media (max-width: 480px) {
            .page-header h1 {
                font-size: var(--font-xl);
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
                <div class="status-filters">
                    <button data-status="all" class="filter-btn active">All Orders</button>
                    <button data-status="pending" class="filter-btn">Pending</button>
                    <button data-status="processing" class="filter-btn">Processing</button>
                    <button data-status="shipped" class="filter-btn">Shipped</button>
                    <button data-status="completed" class="filter-btn">Completed</button>
                    <button data-status="cancelled" class="filter-btn">Cancelled</button>
                </div>
                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Search by order number or customer...">
                    <button id="searchBtn">
                        <img src="<?php echo $baseUrl; ?>images/icons/search-svgrepo-com.svg" width="16" height="16" alt="Search">
                    </button>
                    <button id="resetBtn" class="reset-search-btn" style="display: none;">Reset</button>
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
            <div class="modal-footer" id="orderModalFooter"></div>
        </div>
    </div>

    <script>
        var $ordersTable = null,
            $pagination = null,
            $filterBtns = null,
            $searchBtn = null,
            $resetBtn = null,
            $searchInput = null,
            currentPage = 1,
            currentStatus = 'all',
            currentSearch = '';

        function cacheElements() {
            $ordersTable = $('#ordersTable');
            $pagination = $('#pagination');
            $filterBtns = $('.status-filters .filter-btn');
            $searchBtn = $('#searchBtn');
            $resetBtn = $('#resetBtn');
            $searchInput = $('#searchInput');
        }

        function resetFilters() {
            $searchInput.val('');
            currentSearch = '';
            currentPage = 1;
            $filterBtns.removeClass('active');
            $filterBtns.filter('[data-status="all"]').addClass('active');
            currentStatus = 'all';
            loadSellerOrders();
            $resetBtn.hide();
        }

        function loadSellerOrders() {
            loadOrders(
                'php/endpoints/get-my-orders.php',
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

            $filterBtns.on('click', function() {
                $filterBtns.removeClass('active');
                $(this).addClass('active');
                currentStatus = $(this).data('status');
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
                resetFilters();
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