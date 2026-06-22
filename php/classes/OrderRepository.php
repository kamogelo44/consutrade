<?php

/**
 * ConsuTrade - OrderRepository
 *
 * Handles all order and order item database operations.
 *
 * @author Kamogelo Phale
 * @version 2.0.0
 */
class OrderRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Get buyer orders with optional filters and pagination
     *
     * @param int $id Buyer user ID
     * @param string $filter Status filter
     * @param string $search Search term
     * @param int $limit Limit (0 for no limit)
     * @param int $offset Offset
     * @return array
     */
    public function getBuyerOrders($id, $filter = 'all', $search = '', $limit = 0, $offset = 0)
    {
        $sql = "SELECT o.order_id, o.total_price, o.status, o.created_at,
                       u.full_name as seller_name, u.user_id as seller_id,
                       (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
                FROM orders o
                JOIN users u ON o.seller_id = u.user_id
                WHERE o.buyer_id = ?";

        $params = [$id];
        $types = "i";

        if ($filter !== 'all') {
            $sql .= " AND o.status = ?";
            $params[] = $filter;
            $types .= "s";
        }

        if (!empty($search)) {
            $sql .= " AND (o.order_id LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $types .= "s";
        }

        $sql .= " ORDER BY o.created_at DESC";

        if ($limit > 0) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types .= "ii";
        }

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
     * Get seller orders with optional filters and pagination
     *
     * @param int $id Seller user ID
     * @param string $filter Status filter
     * @param string $search Search term
     * @param int $limit Limit (0 for no limit)
     * @param int $offset Offset
     * @return array
     */
    public function getSellerOrders($id, $filter = 'all', $search = '', $limit = 0, $offset = 0)
    {
        $sql = "SELECT o.order_id, o.total_price, o.status, o.created_at,
                       u.full_name as buyer_name, u.user_id as buyer_id,
                       (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
                FROM orders o
                JOIN users u ON o.buyer_id = u.user_id
                WHERE o.seller_id = ?";

        $params = [$id];
        $types = "i";

        if ($filter !== 'all') {
            $sql .= " AND o.status = ?";
            $params[] = $filter;
            $types .= "s";
        }

        if (!empty($search)) {
            $sql .= " AND (o.order_id LIKE ? OR u.full_name LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types .= "ss";
        }

        $sql .= " ORDER BY o.created_at DESC";

        if ($limit > 0) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types .= "ii";
        }

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
     * Get total number of completed orders for a seller
     *
     * @param int $sellerId
     * @return int
     */
    public function getSellerTotalOrders($sellerId)
    {
        $sql = "SELECT COUNT(*) as total 
                FROM orders 
                WHERE seller_id = ? AND status = 'completed'";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $sellerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return (int)($row['total'] ?? 0);
    }

    /**
     * Get total revenue for a seller from completed orders
     *
     * @param int $sellerId
     * @return float
     */
    public function getSellerTotalRevenue($sellerId)
    {
        $sql = "SELECT SUM(total_price) as total FROM orders WHERE seller_id = ? AND status = 'completed'";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $sellerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return (float)($row['total'] ?? 0);
    }

    /**
     * Get all orders for admin
     *
     * @return array
     */
    public function getAllOrders()
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
     * Get recent orders for admin dashboard
     *
     * @param int $limit Number of orders to return
     * @return array
     */
    public function getRecentOrders($limit = 5)
    {
        $sql = "SELECT o.order_id as id, o.total_price as total, o.status, 
            DATE_FORMAT(o.created_at, '%d %b %Y') as created_at,
            DATE_FORMAT(o.created_at, '%d %b %Y, %h:%i %p') as full_created_at,
            buyer.full_name as buyer_name,
            seller.full_name as seller_name,
            (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
            FROM orders o
            JOIN users buyer ON o.buyer_id = buyer.user_id
            JOIN users seller ON o.seller_id = seller.user_id
            ORDER BY o.created_at DESC 
            LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = [
                'id' => (int)$row['id'],
                'order_id' => (int)$row['id'],
                'total' => (float)$row['total'],
                'status' => $row['status'],
                'created_at' => $row['created_at'],
                'full_created_at' => $row['full_created_at'],
                'buyer_name' => $row['buyer_name'],
                'seller_name' => $row['seller_name'],
                'item_count' => (int)($row['item_count'] ?? 0)
            ];
        }
        $stmt->close();

        return $orders;
    }

    /**
     * Get single order details with items
     *
     * @param int $orderId Order ID
     * @param int $userId User ID for verification
     * @param string $role User role
     * @return array|null
     */
    public function getOrderDetails($orderId, $userId, $role)
    {
        // Admin can view any order
        if ($role === 'admin') {
            $sql = "SELECT o.order_id, o.total_price, o.status, o.created_at, o.payment_id,
                       buyer.full_name as buyer_name, seller.full_name as seller_name
                FROM orders o
                JOIN users buyer ON o.buyer_id = buyer.user_id
                JOIN users seller ON o.seller_id = seller.user_id
                WHERE o.order_id = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $orderId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
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
                $row['other_party_name'] = $row['buyer_name'];
                $stmt->close();
                return $row;
            }
            $stmt->close();
            return null;
        }

        // For buyer and seller
        $idColumn = ($role === 'buyer') ? 'buyer_id' : 'seller_id';

        $sql = "SELECT o.order_id, o.total_price, o.status, o.created_at, o.payment_id,
                   u.full_name as other_party_name
            FROM orders o
            JOIN users u ON " . ($role === 'buyer' ? "o.seller_id = u.user_id" : "o.buyer_id = u.user_id") . "
            WHERE o.order_id = ? AND o.$idColumn = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $orderId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
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

    /**
     * Update order status
     *
     * @param int $orderId Order ID
     * @param int $sellerId Seller ID for verification
     * @param string $status New status
     * @return array
     */
    public function updateSellerOrderStatus($orderId, $sellerId, $status)
    {
        $sql = "UPDATE orders SET status = ? WHERE order_id = ? AND seller_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sii', $status, $orderId, $sellerId);

        if ($stmt->execute()) {
            $stmt->close();

            if ($status === 'cancelled') {
                $productRepo = new ProductRepository($this->db);
                $productRepo->restoreOrderStock($orderId);
            }

            return ['success' => true, 'message' => 'Order status updated to ' . ucfirst($status) . '.'];
        }

        $stmt->close();
        return ['success' => false, 'message' => 'Failed to update order status.'];
    }

    /**
     * Cancel buyer order
     *
     * @param int $orderId Order ID
     * @param int $buyerId Buyer ID for verification
     * @return bool
     */
    public function cancelBuyerOrder($orderId, $buyerId)
    {
        $sql = "UPDATE orders SET status = 'cancelled' WHERE order_id = ? AND buyer_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $orderId, $buyerId);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            $productRepo = new ProductRepository($this->db);
            $productRepo->restoreOrderStock($orderId);
        }

        return $result;
    }

    /**
     * Create orders from cart items
     *
     * @param int $buyerId Buyer user ID
     * @param array $items Cart items
     * @param array $sellers Unique seller IDs
     * @return array|null
     */
    public function createOrdersFromCart($buyerId, $items, $sellers)
    {
        $orderIds = [];
        $paymentId = time() . '_' . $buyerId;

        foreach ($sellers as $sellerId) {
            $sellerSubtotal = 0;
            foreach ($items as $item) {
                if ($item['seller_id'] == $sellerId) {
                    $sellerSubtotal += $item['price'] * $item['quantity'];
                }
            }

            $sellerDelivery = ($sellerSubtotal > 0 && $sellerSubtotal < 500) ? 50 : 0;
            $sellerTotal = $sellerSubtotal + $sellerDelivery;

            $orderSql = "INSERT INTO orders (buyer_id, seller_id, total_price, status, payment_id, created_at)
                         VALUES (?, ?, ?, 'pending', ?, NOW())";
            $orderStmt = $this->db->prepare($orderSql);
            $orderStmt->bind_param('iids', $buyerId, $sellerId, $sellerTotal, $paymentId);

            if (!$orderStmt->execute()) {
                return null;
            }

            $orderId = $orderStmt->insert_id;
            $orderIds[] = $orderId;
            $orderStmt->close();

            foreach ($items as $item) {
                if ($item['seller_id'] == $sellerId) {
                    $itemSql = "INSERT INTO order_items (order_id, product_id, quantity, price)
                                VALUES (?, ?, ?, ?)";
                    $itemStmt = $this->db->prepare($itemSql);
                    $itemStmt->bind_param('iiid', $orderId, $item['product_id'], $item['quantity'], $item['price']);
                    $itemStmt->execute();
                    $itemStmt->close();
                }
            }
        }

        return ['order_ids' => $orderIds, 'payment_id' => $paymentId];
    }

    /**
     * Count completed orders for a seller
     *
     * @param int $sellerId Seller ID
     * @return int
     */
    public function countSellerCompletedOrders($sellerId)
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
     * Get buyer statistics
     *
     * @param int $buyerId Buyer ID
     * @return array
     */
    public function getBuyerStats($buyerId)
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
     * Get seller recent orders for dashboard
     *
     * @param int $sellerId Seller ID
     * @param int $limit Number of orders to return
     * @return array
     */
    public function getSellerRecentOrders($sellerId, $limit = 5)
    {
        $sql = "SELECT o.order_id as id, o.total_price as total, o.status,
            DATE_FORMAT(o.created_at, '%d %b %Y') as created_at,
            u.full_name as buyer_name,
            GROUP_CONCAT(DISTINCT p.title SEPARATOR ', ') as product_names,
            COUNT(DISTINCT oi.item_id) as item_count
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

    /**
     * Update order status directly (for PayFast)
     *
     * @param int $orderId Order ID
     * @param string $status New status
     * @return bool
     */
    public function updateOrderStatusDirect($orderId, $status)
    {
        $validStatuses = ['pending', 'processing', 'shipped', 'completed', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            return false;
        }

        $stmt = $this->db->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $stmt->bind_param('si', $status, $orderId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    /**
     * Count buyer orders with filters (for pagination)
     *
     * @param int $id Buyer user ID
     * @param string $filter Status filter
     * @param string $search Search term
     * @return int
     */
    public function countBuyerOrders($id, $filter = 'all', $search = '')
    {
        $sql = "SELECT COUNT(*) as total FROM orders o WHERE o.buyer_id = ?";
        $params = [$id];
        $types = "i";

        if ($filter !== 'all') {
            $sql .= " AND o.status = ?";
            $params[] = $filter;
            $types .= "s";
        }

        if (!empty($search)) {
            $sql .= " AND (o.order_id LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $types .= "s";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return (int)($row['total'] ?? 0);
    }

    /**
     * Count seller orders with filters (for pagination)
     *
     * @param int $id Seller user ID
     * @param string $filter Status filter
     * @param string $search Search term
     * @return int
     */
    public function countSellerOrders($id, $filter = 'all', $search = '')
    {
        $sql = "SELECT COUNT(*) as total FROM orders o WHERE o.seller_id = ?";
        $params = [$id];
        $types = "i";

        if ($filter !== 'all') {
            $sql .= " AND o.status = ?";
            $params[] = $filter;
            $types .= "s";
        }

        if (!empty($search)) {
            $sql .= " AND (o.order_id LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $types .= "s";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return (int)($row['total'] ?? 0);
    }

    /**
     * Count seller orders by status
     */
    public function countSellerOrdersByStatus(int $sellerId, string $status): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM orders WHERE seller_id = ? AND status = ?");
        $stmt->bind_param('is', $sellerId, $status);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return (int)($row['count'] ?? 0);
    }

    /**
     * Count all orders
     */
    public function countAll(): int
    {
        $result = $this->db->query("SELECT COUNT(*) as count FROM orders");
        $row = $result->fetch_assoc();
        return (int)($row['count'] ?? 0);
    }

    /**
     * Count orders by status
     */
    public function countByStatus(string $status): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM orders WHERE status = ?");
        $stmt->bind_param('s', $status);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return (int)($row['count'] ?? 0);
    }

    /**
     * Get total revenue from completed transactions
     * Uses transactions table (not orders) for accurate revenue
     */
    public function getTotalRevenue(): float
    {
        $result = $this->db->query(
            "SELECT COALESCE(SUM(amount), 0) as total_revenue 
         FROM transactions 
         WHERE status = 'completed'"
        );
        $row = $result->fetch_assoc();
        return (float)($row['total_revenue'] ?? 0);
    }

    /**
     * Get recent orders with buyer names (for admin dashboard)
     * This is an alias/improvement of getRecentOrders() with consistent formatting
     */
    public function getRecentWithBuyerNames(int $limit = 5): array
    {
        $sql = "SELECT o.order_id as id, o.total_price as total, o.status, 
            DATE_FORMAT(o.created_at, '%d %b %Y, %h:%i %p') as created_at,
            DATE_FORMAT(o.created_at, '%d %b %Y') as short_created_at,
            buyer.full_name as buyer_name,
            seller.full_name as seller_name,
            (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
            FROM orders o
            JOIN users buyer ON o.buyer_id = buyer.user_id
            JOIN users seller ON o.seller_id = seller.user_id
            ORDER BY o.created_at DESC 
            LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = [
                'id' => (int)$row['id'],
                'total' => (float)$row['total'],
                'status' => $row['status'],
                'created_at' => $row['short_created_at'] ?? $row['created_at'],
                'full_created_at' => $row['created_at'],
                'buyer_name' => $row['buyer_name'],
                'seller_name' => $row['seller_name'],
                'item_count' => (int)($row['item_count'] ?? 0)
            ];
        }
        $stmt->close();

        return $orders;
    }
}
