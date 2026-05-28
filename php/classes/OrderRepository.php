<?php
/**
 * ConsuTrade - OrderRepository
 *
 * Handles all order and order item database operations for buyers, sellers, and admins.
 *
 * @author     Kamogelo Phale
 * @module     ITECA3-12 Web Development and e-Commerce
 * @institution Eduvos
 * @version    2.0.0
 * @since      2026
 *
 * References:
 * - Pressman, R.S. and Maxim, B.R., 2015. Software Engineering:
 *   A Practitioner's Approach. 8th ed. McGraw-Hill.
 * - Dennis, A., Wixom, B.H. and Tegarden, D., 2015. Systems Analysis
 *   and Design: An Object-Oriented Approach with UML. 6th ed.
 *   John Wiley and Sons.
 * - PHP Group, 2025. Classes and Objects. Available at:
 *   https://www.php.net/manual/en/language.oop5.php
 * - PHP-FIG, 2023. PSR-12: Extended Coding Style. Available at:
 *   https://www.php.fig.org/psr/psr-12/
 */

class OrderRepository
{
    /** @var mysqli Database connection */
    private $db;

    /**
     * Constructor.
     *
     * @param mysqli $db Database connection
     */
    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    // ============================================================
    //  ORDER LISTINGS
    // ============================================================

    /**
     * Get buyer orders with optional filters.
     *
     * @param int    $id      The buyer's user ID
     * @param string $filter  Status filter: 'pending', 'processing', 'shipped',
     *                        'completed', 'cancelled', 'all'
     * @param string $search  Search by order ID or seller name
     * @return array
     */
    public function getBuyerOrders(
        int $id,
        string $filter = 'all',
        string $search = ''
    ): array {
        $sql = "SELECT o.order_id, o.total_price, o.status, o.created_at,
                       u.full_name as seller_name, u.user_id as seller_id,
                       (SELECT GROUP_CONCAT(DISTINCT p.title SEPARATOR ', ')
                        FROM order_items oi
                        JOIN products p ON oi.product_id = p.product_id
                        WHERE oi.order_id = o.order_id) as product_names,
                       (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
                FROM orders o
                JOIN users u ON o.seller_id = u.user_id
                WHERE o.buyer_id = ?";

        $params = [$id];
        $types  = "i";

        if ($filter !== 'all') {
            $sql    .= " AND o.status = ?";
            $params[] = $filter;
            $types  .= "s";
        }

        if (!empty($search)) {
            $sql       .= " AND (u.full_name LIKE ? OR o.order_id LIKE ?)";
            $searchParam = "%$search%";
            $params[]  = $searchParam;
            $params[]  = $searchParam;
            $types    .= "ss";
        }

        $sql .= " ORDER BY
                    CASE o.status
                        WHEN 'pending' THEN 1
                        WHEN 'processing' THEN 2
                        WHEN 'shipped' THEN 3
                        WHEN 'completed' THEN 4
                        WHEN 'cancelled' THEN 5
                        ELSE 6
                    END,
                    o.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        $stmt->close();

        return $orders;
    }

    /**
     * Get seller orders with optional filters (for seller dashboard).
     *
     * @param int    $id      The seller's user ID
     * @param string $filter  Status filter
     * @param string $search  Search by order ID or buyer name
     * @return array
     */
    public function getSellerOrders(
        int $id,
        string $filter = 'all',
        string $search = ''
    ): array {
        $sql = "SELECT o.order_id, o.total_price, o.status, o.created_at,
                       u.full_name as buyer_name, u.email as buyer_email, u.user_id as buyer_id,
                       (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
                FROM orders o
                JOIN users u ON o.buyer_id = u.user_id
                WHERE o.seller_id = ?";

        $params = [$id];
        $types  = "i";

        if ($filter !== 'all') {
            $sql    .= " AND o.status = ?";
            $params[] = $filter;
            $types  .= "s";
        }

        if (!empty($search)) {
            $sql       .= " AND (u.full_name LIKE ? OR o.order_id LIKE ?)";
            $searchParam = "%$search%";
            $params[]  = $searchParam;
            $params[]  = $searchParam;
            $types    .= "ss";
        }

        $sql .= " ORDER BY
                    CASE o.status
                        WHEN 'pending' THEN 1
                        WHEN 'processing' THEN 2
                        WHEN 'shipped' THEN 3
                        WHEN 'completed' THEN 4
                        WHEN 'cancelled' THEN 5
                        ELSE 6
                    END,
                    o.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        $stmt->close();

        return $orders;
    }

    /**
     * Get all orders in the system (admin only).
     *
     * @return array
     */
    public function getAllOrders(): array
    {
        $sql = "SELECT o.order_id, o.total_price, o.status, o.created_at,
                       buyer.full_name as buyer_name, buyer.user_id as buyer_id,
                       seller.full_name as seller_name, seller.user_id as seller_id,
                       (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
                FROM orders o
                JOIN users buyer ON o.buyer_id = buyer.user_id
                JOIN users seller ON o.seller_id = seller.user_id
                ORDER BY o.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();

        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        $stmt->close();

        return $orders;
    }

    /**
     * Get single order details with its items.
     *
     * @param int    $orderId Order ID
     * @param int    $userId  User ID (for verification)
     * @param string $role    User role: 'buyer' or 'seller'
     * @return array|null
     */
    public function getOrderDetails(int $orderId, int $userId, string $role): ?array
    {
        $idColumn = ($role === 'buyer') ? 'buyer_id' : 'seller_id';

        $sql = "SELECT o.order_id, o.total_price, o.status, o.created_at, o.payment_id,
                       u.full_name as other_party_name
                FROM orders o
                JOIN users u ON " .
                ($role === 'buyer' ? "o.seller_id = u.user_id" : "o.buyer_id = u.user_id") . "
                WHERE o.order_id = ? AND o.$idColumn = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $orderId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            // Get order items
            $itemsSql = "SELECT oi.quantity, oi.price,
                                p.title as product_name, p.image_url
                         FROM order_items oi
                         JOIN products p ON oi.product_id = p.product_id
                         WHERE oi.order_id = ?";
            $itemsStmt = $this->db->prepare($itemsSql);
            $itemsStmt->bind_param('i', $orderId);
            $itemsStmt->execute();
            $itemsResult = $itemsStmt->get_result();

            $items = [];
            while ($item = $itemsResult->fetch_assoc()) {
                $items[] = $item;
            }
            $itemsStmt->close();

            $row['items'] = $items;
            $stmt->close();
            return $row;
        }

        $stmt->close();
        return null;
    }

    // ============================================================
    //  ORDER STATUS UPDATES
    // ============================================================

    /**
     * Update order status (for sellers).
     * Validates that the status transition is allowed.
     *
     * @param int    $orderId  Order ID
     * @param int    $sellerId Seller ID (for verification)
     * @param string $status   New status: 'processing', 'shipped', 'completed', 'cancelled'
     * @return array           ['success' => bool, 'message' => string]
     */
    public function updateSellerOrderStatus(int $orderId, int $sellerId, string $status): array
    {
        $allowedStatuses = ['processing', 'shipped', 'completed', 'cancelled'];
        if (!in_array($status, $allowedStatuses)) {
            return ['success' => false, 'message' => 'Invalid status.'];
        }

        // Verify ownership and get current status
        $checkSql = "SELECT status FROM orders WHERE order_id = ? AND seller_id = ?";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->bind_param('ii', $orderId, $sellerId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows === 0) {
            $checkStmt->close();
            return ['success' => false, 'message' => 'Order not found.'];
        }

        $order = $checkResult->fetch_assoc();
        $currentStatus = $order['status'];
        $checkStmt->close();

        // Valid status transitions
        $validTransitions = [
            'pending'    => ['processing', 'cancelled'],
            'processing' => ['shipped', 'cancelled'],
            'shipped'    => ['completed', 'cancelled'],
            'completed'  => [],
            'cancelled'  => []
        ];

        if (!in_array($status, $validTransitions[$currentStatus])) {
            return [
                'success' => false,
                'message' => "Cannot change status from '$currentStatus' to '$status'."
            ];
        }

        $updateSql = "UPDATE orders SET status = ? WHERE order_id = ? AND seller_id = ?";
        $updateStmt = $this->db->prepare($updateSql);
        $updateStmt->bind_param('sii', $status, $orderId, $sellerId);

        if ($updateStmt->execute()) {
            $updateStmt->close();

            // Restore stock if cancelled
            if ($status === 'cancelled') {
                $productRepo = new ProductRepository($this->db);
                $productRepo->restoreOrderStock($orderId);
            }

            return ['success' => true, 'message' => 'Order status updated to ' . ucfirst($status) . '.'];
        }

        $updateStmt->close();
        return ['success' => false, 'message' => 'Failed to update order status.'];
    }

    /**
     * Cancel an order (for buyers — only pending orders can be cancelled).
     *
     * @param int $orderId Order ID
     * @param int $buyerId Buyer ID (for verification)
     * @return bool
     */
    public function cancelBuyerOrder(int $orderId, int $buyerId): bool
    {
        // Only pending orders can be cancelled by buyer
        $checkSql = "SELECT status FROM orders
                     WHERE order_id = ? AND buyer_id = ? AND status = 'pending'";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->bind_param('ii', $orderId, $buyerId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows === 0) {
            $checkStmt->close();
            return false;
        }
        $checkStmt->close();

        $updateSql = "UPDATE orders SET status = 'cancelled' WHERE order_id = ? AND buyer_id = ?";
        $updateStmt = $this->db->prepare($updateSql);
        $updateStmt->bind_param('ii', $orderId, $buyerId);
        $result = $updateStmt->execute();
        $updateStmt->close();

        return $result;
    }

    // ============================================================
    //  ORDER CREATION
    // ============================================================

    /**
     * Create orders from cart items, grouping by seller.
     * Each seller gets their own order.
     *
     * @param int   $buyerId   The buyer's user ID
     * @param array $items     Array of cart items (must include seller_id, product_id,
     *                         price, quantity)
     * @param array $sellers   Array of unique seller IDs
     * @return array|null      ['order_ids' => array, 'payment_id' => string] or null on failure
     */
    public function createOrdersFromCart(int $buyerId, array $items, array $sellers): ?array
    {
        $orderIds  = [];
        $paymentId = time() . '_' . $buyerId;

        foreach ($sellers as $sellerId) {
            // Calculate seller-specific subtotal
            $sellerSubtotal = 0;
            foreach ($items as $item) {
                if ($item['seller_id'] == $sellerId) {
                    $sellerSubtotal += $item['price'] * $item['quantity'];
                }
            }

            // Delivery fee: R50 if under R500, free otherwise
            $sellerDelivery = ($sellerSubtotal > 0 && $sellerSubtotal < 500) ? 50 : 0;
            $sellerTotal    = $sellerSubtotal + $sellerDelivery;

            // Create order
            $orderSql = "INSERT INTO orders (buyer_id, seller_id, total_price, status, payment_id, created_at)
                         VALUES (?, ?, ?, 'pending', ?, NOW())";
            $orderStmt = $this->db->prepare($orderSql);
            $orderStmt->bind_param('iids', $buyerId, $sellerId, $sellerTotal, $paymentId);

            if (!$orderStmt->execute()) {
                return null;
            }

            $orderId = $orderStmt->insert_id;
            $orderIds[] = $orderId;

            // Insert order items for this seller
            foreach ($items as $item) {
                if ($item['seller_id'] == $sellerId) {
                    $itemSql = "INSERT INTO order_items (order_id, product_id, quantity, price)
                                VALUES (?, ?, ?, ?)";
                    $itemStmt = $this->db->prepare($itemSql);
                    $itemStmt->bind_param('iiid', $orderId, $item['product_id'], $item['quantity'], $item['price']);
                    $itemStmt->execute();
                    $itemStmt->close();

                    // Decrease stock
                    $productRepo = new ProductRepository($this->db);
                    $productRepo->decreaseProductStock($item['product_id'], $item['quantity']);
                }
            }
            $orderStmt->close();
        }

        return ['order_ids' => $orderIds, 'payment_id' => $paymentId];
    }

    /**
     * Count completed orders for a seller.
     *
     * @param int $sellerId Seller ID
     * @return int
     */
    public function countSellerCompletedOrders(int $sellerId): int
    {
        $sql = "SELECT COUNT(DISTINCT o.order_id) as total 
                FROM orders o
                JOIN order_items oi ON o.order_id = oi.order_id
                JOIN products p ON oi.product_id = p.product_id
                WHERE p.seller_id = ? AND o.status = 'completed'";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $sellerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $total = (int)($result->fetch_assoc()['total'] ?? 0);
        $stmt->close();
        return $total;
    }

    /**
     * Get buyer statistics (orders count, total spent, pending, completed).
     *
     * @param int $buyerId Buyer ID
     * @return array
     */
    public function getBuyerStats(int $buyerId): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status IN ('completed', 'processing', 'shipped') THEN total_price ELSE 0 END) as total_spent,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders
                FROM orders 
                WHERE buyer_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $buyerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        
        return [
            'total_orders' => (int)($data['total_orders'] ?? 0),
            'total_spent' => (float)($data['total_spent'] ?? 0),
            'pending_orders' => (int)($data['pending_orders'] ?? 0),
            'completed_orders' => (int)($data['completed_orders'] ?? 0)
        ];
    }

    /**
     * Get seller recent orders for dashboard.
     *
     * @param int $sellerId Seller ID
     * @param int $limit Limit of orders to return
     * @return array
     */
    public function getSellerRecentOrders(int $sellerId, int $limit = 5): array
    {
        $sql = "SELECT o.order_id as id, o.total_price as total, o.status,
                DATE_FORMAT(o.created_at, '%d %b %Y') as created_at,
                u.full_name as buyer_name,
                GROUP_CONCAT(DISTINCT p.title SEPARATOR ', ') as product_names,
                COUNT(DISTINCT oi.order_item_id) as item_count
                FROM orders o
                JOIN users u ON o.buyer_id = u.user_id
                JOIN order_items oi ON o.order_id = oi.order_id
                JOIN products p ON oi.product_id = p.product_id
                WHERE o.seller_id = ?
                GROUP BY o.order_id
                ORDER BY o.created_at DESC 
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $sellerId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        $stmt->close();
        
        return $orders;
    }
}