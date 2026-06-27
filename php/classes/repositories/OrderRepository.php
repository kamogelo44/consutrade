<?php

/**
 * ConsuTrade - OrderRepository
 *
 * Handles all order and order item database operations ONLY.
 * Business logic moved to OrderService.
 * 
 * All methods are documented with parameter types, return types,
 * and descriptions of what they do.
 *
 * @author Kamogelo Phale
 * @version 2.2.0
 */

class OrderRepository
{
    /** @var mysqli Database connection */
    private $db;

    /**
     * Constructor
     *
     * @param mysqli $db Database connection
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    // ============================================================
    // CREATE (C)
    // ============================================================

    /**
     * Create orders from cart items.
     * Creates one order per seller in the cart.
     * All orders share the same payment_id for tracking.
     *
     * @param int $buyerId Buyer user ID
     * @param array $items Cart items (each with product_id, quantity, price, seller_id)
     * @param array $sellers Unique seller IDs
     * @return array|null Returns ['order_ids' => [...], 'payment_id' => '...'] or null on failure
     */
    public function createFromCart(int $buyerId, array $items, array $sellers): ?array
    {
        $orderIds = [];
        $paymentId = time() . '_' . $buyerId;

        foreach ($sellers as $sellerId) {
            // Calculate subtotal for this seller
            $sellerSubtotal = 0;
            foreach ($items as $item) {
                if ($item['seller_id'] == $sellerId) {
                    $sellerSubtotal += $item['price'] * $item['quantity'];
                }
            }

            // Delivery fee: R50 if subtotal is between R1 and R499, free if R500+
            $sellerDelivery = ($sellerSubtotal > 0 && $sellerSubtotal < 500) ? 50 : 0;
            $sellerTotal = $sellerSubtotal + $sellerDelivery;

            // Insert order
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

            // Insert order items for this seller
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

    // ============================================================
    // READ (R)
    // ============================================================

    /**
     * Check if an order exists by ID and return basic data.
     * Lightweight check for existence and ownership verification.
     *
     * @param int $orderId Order ID
     * @return array|null Returns order data or null if not found
     */
    public function existsById(int $orderId): ?array
    {
        $sql = "SELECT order_id, buyer_id, seller_id, status FROM orders WHERE order_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();
        return $order;
    }

    /**
     * Find order by payment ID.
     * Used to look up orders when PayFast redirects with m_payment_id.
     *
     * @param string $paymentId Payment ID
     * @return array|null Returns order data or null if not found
     */
    public function findByPaymentId(string $paymentId): ?array
    {
        $sql = "SELECT * FROM orders WHERE payment_id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $paymentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();
        return $order ?: null;
    }

    /**
     * Get order items by order ID.
     * Returns only product_id and quantity for stock operations.
     *
     * @param int $orderId Order ID
     * @return array List of items ['product_id' => int, 'quantity' => int]
     */
    public function getOrderItems(int $orderId): array
    {
        $sql = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        $stmt->close();
        return $items;
    }

    /**
     * Get single order details with items.
     * Role-based access control - returns different data based on user role.
     *
     * @param int $orderId Order ID
     * @param int $userId User ID (buyer, seller, or admin)
     * @param string $role User role ('buyer', 'seller', or 'admin')
     * @return array|null Returns order with items and other party name, or null if not found
     */
    public function findById(int $orderId, int $userId, string $role): ?array
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

        // Buyers and sellers can only view their own orders
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
     * Get buyer orders with optional filters and pagination.
     * Used for the "My Orders" page for buyers.
     *
     * @param int $id Buyer user ID
     * @param string $filter Status filter ('all', 'pending', 'processing', etc.)
     * @param string $search Search term for order ID
     * @param int $limit Results per page (0 = no limit)
     * @param int $offset Pagination offset
     * @return array List of orders with seller info and item count
     */
    public function findByBuyer(int $id, string $filter = 'all', string $search = '', int $limit = 0, int $offset = 0): array
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
     * Get seller orders with optional filters and pagination.
     * Used for the "My Sales" page for sellers.
     *
     * @param int $id Seller user ID
     * @param string $filter Status filter ('all', 'pending', 'processing', etc.)
     * @param string $search Search term for order ID or buyer name
     * @param int $limit Results per page (0 = no limit)
     * @param int $offset Pagination offset
     * @return array List of orders with buyer info and item count
     */
    public function findBySeller(int $id, string $filter = 'all', string $search = '', int $limit = 0, int $offset = 0): array
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
     * Get all orders for admin.
     * Shows both buyer and seller names for each order.
     *
     * @return array List of all orders with buyer and seller info
     */
    public function findAll(): array
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
     * Get recent orders for admin dashboard.
     *
     * @param int $limit Number of orders to return (default 5)
     * @return array List of recent orders with formatted dates
     */
    public function findRecent(int $limit = 5): array
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
     * Get seller recent orders for dashboard.
     *
     * @param int $sellerId Seller user ID
     * @param int $limit Number of orders to return (default 5)
     * @return array List of recent orders with product names
     */
    public function findRecentBySeller(int $sellerId, int $limit = 5): array
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
     * Get buyer statistics.
     * Used for buyer dashboard to show order counts and spending.
     *
     * @param int $buyerId Buyer user ID
     * @return array ['total_orders', 'total_spent', 'pending_orders', 'completed_orders']
     */
    public function findBuyerStats(int $buyerId): array
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

    // ============================================================
    // UPDATE (U)
    // ============================================================

    /**
     * Update order status with seller verification.
     * Sellers can only update orders they own.
     *
     * @param int $orderId Order ID
     * @param int $sellerId Seller user ID (must match order.seller_id)
     * @param string $status New status
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateStatus(int $orderId, int $sellerId, string $status): array
    {
        $sql = "UPDATE orders SET status = ? WHERE order_id = ? AND seller_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sii', $status, $orderId, $sellerId);

        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Order status updated to ' . ucfirst($status) . '.'];
        }

        $stmt->close();
        return ['success' => false, 'message' => 'Failed to update order status.'];
    }

    /**
     * Update order status directly (for PayFast ITN).
     * 
     * This method is used by PayFastService to update order status
     * after successful payment confirmation.
     * 
     * Features:
     * - Validates the status is allowed
     * - Only updates if the status is different (prevents unnecessary updates)
     * - Returns true only if a row was actually affected
     * - Logs all operations for debugging
     *
     * @param int $orderId The order ID
     * @param string $status The new status ('pending', 'processing', 'shipped', 'completed', 'cancelled')
     * @return bool True if the status was updated, false otherwise
     */
    public function updateStatusDirect(int $orderId, string $status): bool
    {
        $validStatuses = ['pending', 'processing', 'shipped', 'completed', 'cancelled'];

        // Validate status
        if (!in_array($status, $validStatuses)) {
            error_log("updateStatusDirect: Invalid status '$status' for order_id: $orderId");
            return false;
        }

        // Prepare statement - only update if status is different
        $stmt = $this->db->prepare("UPDATE orders SET status = ? WHERE order_id = ? AND status != ?");
        if (!$stmt) {
            error_log("updateStatusDirect: Prepare failed - " . $this->db->error);
            return false;
        }

        $stmt->bind_param('sis', $status, $orderId, $status);
        $result = $stmt->execute();

        if ($result) {
            $affectedRows = $stmt->affected_rows;
            error_log("updateStatusDirect: order_id=$orderId, status=$status, affected_rows=$affectedRows");
            // Return true only if a row was actually updated
            $success = $affectedRows > 0;
            $stmt->close();
            return $success;
        }

        error_log("updateStatusDirect: Execute failed - " . $stmt->error);
        $stmt->close();
        return false;
    }

    /**
     * Cancel buyer order (database only).
     * Stock restoration is handled in OrderService.
     *
     * @param int $orderId Order ID
     * @param int $buyerId Buyer user ID (must match order.buyer_id)
     * @return bool True if cancelled successfully
     */
    public function cancelByBuyer(int $orderId, int $buyerId): bool
    {
        $sql = "UPDATE orders SET status = 'cancelled' WHERE order_id = ? AND buyer_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $orderId, $buyerId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // ============================================================
    // DELETE (D)
    // ============================================================

    /**
     * Delete all orders for a buyer (soft delete).
     *
     * @param int $buyerId Buyer user ID
     * @return bool True on success
     */
    public function deleteByBuyer(int $buyerId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM orders WHERE buyer_id = ?");
        $stmt->bind_param('i', $buyerId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Delete all orders for a seller (soft delete).
     *
     * @param int $sellerId Seller user ID
     * @return bool True on success
     */
    public function deleteBySeller(int $sellerId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM orders WHERE seller_id = ?");
        $stmt->bind_param('i', $sellerId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // ============================================================
    // COUNTS / STATISTICS
    // ============================================================

    /**
     * Count all orders in the system.
     *
     * @return int Total number of orders
     */
    public function countAll(): int
    {
        $result = $this->db->query("SELECT COUNT(*) as count FROM orders");
        $row = $result->fetch_assoc();
        return (int)($row['count'] ?? 0);
    }

    /**
     * Count orders by status.
     *
     * @param string $status Order status
     * @return int Number of orders with that status
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
     * Count buyer orders with filters.
     *
     * @param int $id Buyer user ID
     * @param string $filter Status filter ('all', 'pending', etc.)
     * @param string $search Search term for order ID
     * @return int Number of orders matching the filters
     */
    public function countByBuyer(int $id, string $filter = 'all', string $search = ''): int
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
     * Count seller orders with filters.
     *
     * @param int $id Seller user ID
     * @param string $filter Status filter ('all', 'pending', etc.)
     * @param string $search Search term for order ID
     * @return int Number of orders matching the filters
     */
    public function countBySeller(int $id, string $filter = 'all', string $search = ''): int
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
     * Count completed orders for a seller.
     * Used for seller statistics.
     *
     * @param int $sellerId Seller user ID
     * @return int Number of completed orders
     */
    public function countCompletedBySeller(int $sellerId): int
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
     * Count orders by seller and status.
     *
     * @param int $sellerId Seller user ID
     * @param string $status Order status
     * @return int Number of orders matching the criteria
     */
    public function countBySellerAndStatus(int $sellerId, string $status): int
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
     * Get total revenue from all completed transactions.
     *
     * @return float Total revenue
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
     * Get total revenue for a seller from completed orders.
     *
     * @param int $sellerId Seller user ID
     * @return float Total revenue for the seller
     */
    public function getSellerTotalRevenue(int $sellerId): float
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
     * Get total number of completed orders for a seller.
     *
     * @param int $sellerId Seller user ID
     * @return int Number of completed orders
     */
    public function getSellerTotalOrders(int $sellerId): int
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
     * Count active orders for a seller (not cancelled).
     *
     * @param int $sellerId Seller user ID
     * @return int Number of active orders
     */
    public function countActiveBySeller(int $sellerId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM orders WHERE seller_id = ? AND status != 'cancelled'");
        $stmt->bind_param('i', $sellerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return (int)($row['count'] ?? 0);
    }
}
