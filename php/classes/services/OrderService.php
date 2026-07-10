<?php

/**
 * ConsuTrade - OrderService
 *
 * Handles order business logic including creation, status updates,
 * cancellations, and revenue calculations.
 * Uses state machine for valid status transitions.
 *
 * @author Kamogelo Phale
 * @version 3.0.0
 */

class OrderService
{
    private mysqli $db;
    private OrderRepository $orderRepo;
    private ProductRepository $productRepo;
    private TransactionRepository $transactionRepo;

    /**
     * Valid status transitions.
     * Each status maps to the statuses it can transition to.
     */
    private const VALID_TRANSITIONS = [
        'pending'    => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped'    => ['completed', 'cancelled'],
        'completed'  => [],
        'cancelled'  => [],
    ];

    public function __construct(
        mysqli $db,
        OrderRepository $orderRepo,
        ProductRepository $productRepo,
        TransactionRepository $transactionRepo
    ) {
        $this->db = $db;
        $this->orderRepo = $orderRepo;
        $this->productRepo = $productRepo;
        $this->transactionRepo = $transactionRepo;
    }

    // ============================================================
    // CREATE
    // ============================================================

    /**
     * Create orders from cart items.
     *
     * @param int $buyerId Buyer user ID
     * @param array $items Cart items
     * @param array $sellers Unique seller IDs
     * @return array|null ['order_ids' => [...], 'payment_id' => '...']
     */
    public function createFromCart(int $buyerId, array $items, array $sellers): ?array
    {
        return $this->orderRepo->createFromCart($buyerId, $items, $sellers);
    }

    // ============================================================
    // STATUS MANAGEMENT
    // ============================================================

    /**
     * Update order status with state machine validation.
     * Sellers can only update their own orders.
     *
     * @param int $orderId Order ID
     * @param int $sellerId Seller user ID
     * @param string $newStatus New status
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateStatus(int $orderId, int $sellerId, string $newStatus): array
    {
        // Get current order state
        $order = $this->orderRepo->findByIdForSeller($orderId, $sellerId);

        if (!$order) {
            return ['success' => false, 'message' => 'Order not found or access denied.'];
        }

        $currentStatus = $order['status'];

        // Validate status exists in state machine
        if (!isset(self::VALID_TRANSITIONS[$currentStatus])) {
            return ['success' => false, 'message' => 'Invalid current order status.'];
        }

        // Validate transition is allowed
        if (!in_array($newStatus, self::VALID_TRANSITIONS[$currentStatus])) {
            return [
                'success' => false,
                'message' => "Cannot change order from '{$currentStatus}' to '{$newStatus}'."
            ];
        }

        // Perform the update
        $result = $this->orderRepo->updateStatus($orderId, $sellerId, $newStatus);

        // Restore stock on cancellation
        if ($result['success'] && $newStatus === 'cancelled') {
            $this->productRepo->restoreStockFromOrder($orderId);

            // If there's a completed transaction, mark it as refunded
            $transaction = $this->transactionRepo->findByOrderId($orderId);
            if ($transaction && $transaction->isPaid()) {
                $this->transactionRepo->updateStatus($transaction->getTransactionId(), 'refunded');
            }
        }

        // If marking as completed, ensure transaction exists
        if ($result['success'] && $newStatus === 'completed') {
            $transaction = $this->transactionRepo->findByOrderId($orderId);
            if (!$transaction) {
                // Create a placeholder transaction for completed orders without payment
                $this->transactionRepo->createFromPayment(
                    $orderId,
                    'MANUAL_' . time(),
                    $order['total_price'] ?? 0
                );
            }
        }

        return $result;
    }

    /**
     * Cancel order by buyer with state machine validation.
     *
     * @param int $orderId Order ID
     * @param int $buyerId Buyer user ID
     * @return array ['success' => bool, 'message' => string]
     */
    public function cancelByBuyer(int $orderId, int $buyerId): array
    {
        $order = $this->orderRepo->findByIdForBuyer($orderId, $buyerId);

        if (!$order) {
            return ['success' => false, 'message' => 'Order not found or access denied.'];
        }

        $currentStatus = $order['status'];

        // Buyers can only cancel pending or processing orders
        $cancellableStatuses = ['pending', 'processing'];

        if (!in_array($currentStatus, $cancellableStatuses)) {
            return [
                'success' => false,
                'message' => "Orders with status '{$currentStatus}' cannot be cancelled."
            ];
        }

        $result = $this->orderRepo->cancelByBuyer($orderId, $buyerId);

        if ($result) {
            $this->productRepo->restoreStockFromOrder($orderId);

            // Refund if payment was made
            $transaction = $this->transactionRepo->findByOrderId($orderId);
            if ($transaction && $transaction->isPaid()) {
                $this->transactionRepo->updateStatus($transaction->getTransactionId(), 'refunded');
            }

            return ['success' => true, 'message' => 'Order cancelled successfully.'];
        }

        return ['success' => false, 'message' => 'Failed to cancel order.'];
    }

    /**
     * Get valid next statuses for an order.
     * Useful for UI to show available actions.
     *
     * @param string $currentStatus Current order status
     * @return array List of valid next statuses
     */
    public function getValidTransitions(string $currentStatus): array
    {
        return self::VALID_TRANSITIONS[$currentStatus] ?? [];
    }

    /**
     * Check if a status transition is valid.
     *
     * @param string $from Current status
     * @param string $to Desired status
     * @return bool
     */
    public function isValidTransition(string $from, string $to): bool
    {
        return isset(self::VALID_TRANSITIONS[$from]) && in_array($to, self::VALID_TRANSITIONS[$from]);
    }

    // ============================================================
    // REVENUE
    // ============================================================

    /**
     * Get total revenue from all completed transactions.
     *
     * @return float
     */
    public function getTotalRevenue(): float
    {
        return $this->transactionRepo->getTotalRevenue();
    }

    /**
     * Get total revenue for a seller from completed orders.
     *
     * @param int $sellerId Seller user ID
     * @return float
     */
    public function getSellerTotalRevenue(int $sellerId): float
    {
        return $this->orderRepo->getSellerTotalRevenue($sellerId);
    }

    /**
     * Get total number of completed orders for a seller.
     *
     * @param int $sellerId Seller user ID
     * @return int
     */
    public function getSellerTotalOrders(int $sellerId): int
    {
        return $this->orderRepo->getSellerTotalOrders($sellerId);
    }

    // ============================================================
    // READ
    // ============================================================

    /**
     * Get order by ID with transaction data included.
     *
     * @param int $orderId Order ID
     * @param int $userId User ID
     * @param string $role User role
     * @return array|null
     */
    public function findById(int $orderId, int $userId, string $role): ?array
    {
        $order = $this->orderRepo->findById($orderId, $userId, $role);

        if ($order) {
            $transaction = $this->transactionRepo->findByOrderId($orderId);
            if ($transaction) {
                $order['transaction'] = [
                    'reference' => $transaction->getPayfastRef(),
                    'status' => $transaction->getStatus(),
                    'amount' => $transaction->getAmount(),
                    'paid_at' => $transaction->getFormattedPaidAt()
                ];
            }
        }

        return $order;
    }

    /**
     * Get order by ID for buyer with transaction data.
     *
     * @param int $orderId Order ID
     * @param int $userId Buyer user ID
     * @return array|null
     */
    public function findByIdForBuyer(int $orderId, int $userId): ?array
    {
        $order = $this->orderRepo->findByIdForBuyer($orderId, $userId);

        if ($order) {
            $transaction = $this->transactionRepo->findByOrderId($orderId);
            if ($transaction) {
                $order['transaction'] = [
                    'reference' => $transaction->getPayfastRef(),
                    'status' => $transaction->getStatus(),
                    'amount' => $transaction->getAmount(),
                    'paid_at' => $transaction->getFormattedPaidAt()
                ];
            }
        }

        return $order;
    }

    /**
     * Get order by ID for seller with transaction data.
     *
     * @param int $orderId Order ID
     * @param int $userId Seller user ID
     * @return array|null
     */
    public function findByIdForSeller(int $orderId, int $userId): ?array
    {
        $order = $this->orderRepo->findByIdForSeller($orderId, $userId);

        if ($order) {
            $transaction = $this->transactionRepo->findByOrderId($orderId);
            if ($transaction) {
                $order['transaction'] = [
                    'reference' => $transaction->getPayfastRef(),
                    'status' => $transaction->getStatus(),
                    'amount' => $transaction->getAmount(),
                    'paid_at' => $transaction->getFormattedPaidAt()
                ];
            }
        }

        return $order;
    }

    /**
     * Get order by ID for admin with transaction data.
     *
     * @param int $orderId Order ID
     * @return array|null
     */
    public function findByIdForAdmin(int $orderId): ?array
    {
        $order = $this->orderRepo->findByIdForAdmin($orderId);

        if ($order) {
            $transaction = $this->transactionRepo->findByOrderId($orderId);
            if ($transaction) {
                $order['transaction'] = [
                    'reference' => $transaction->getPayfastRef(),
                    'status' => $transaction->getStatus(),
                    'amount' => $transaction->getAmount(),
                    'paid_at' => $transaction->getFormattedPaidAt()
                ];
            }
        }

        return $order;
    }

    /**
     * Get buyer orders with filters.
     *
     * @param int $id Buyer user ID
     * @param string $filter Status filter
     * @param string $search Search term
     * @param int $limit Results per page
     * @param int $offset Pagination offset
     * @return array
     */
    public function findByBuyer(int $id, string $filter = 'all', string $search = '', int $limit = 0, int $offset = 0): array
    {
        return $this->orderRepo->findByBuyer($id, $filter, $search, $limit, $offset);
    }

    /**
     * Get seller orders with filters.
     *
     * @param int $id Seller user ID
     * @param string $filter Status filter
     * @param string $search Search term
     * @param int $limit Results per page
     * @param int $offset Pagination offset
     * @return array
     */
    public function findBySeller(int $id, string $filter = 'all', string $search = '', int $limit = 0, int $offset = 0): array
    {
        return $this->orderRepo->findBySeller($id, $filter, $search, $limit, $offset);
    }

    /**
     * Get all orders for admin with filters and pagination.
     *
     * @return array
     */
    public function findAll(string $status = 'all', string $search = '', int $limit = 10, int $offset = 0): array
    {
        return $this->orderRepo->findAll($status, $search, $limit, $offset);
    }


    /**
     * Get recent orders for admin dashboard.
     *
     * @param int $limit Number of orders
     * @return array
     */
    public function findRecent(int $limit = 5): array
    {
        return $this->orderRepo->findRecent($limit);
    }

    /**
     * Get buyer statistics.
     *
     * @param int $buyerId Buyer user ID
     * @return array
     */
    public function findBuyerStats(int $buyerId): array
    {
        return $this->orderRepo->findBuyerStats($buyerId);
    }

    /**
     * Get order items by order ID.
     *
     * @param int $orderId Order ID
     * @return array
     */
    public function getOrderItems(int $orderId): array
    {
        return $this->orderRepo->getOrderItems($orderId);
    }

    // ============================================================
    // COUNTS
    // ============================================================

    /**
     * Count all orders for admin with filters.
     *
     * @return int
     */
    public function countAll(string $status = 'all', string $search = ''): int
    {
        return $this->orderRepo->countAll($status, $search);
    }

    /**
     * Count orders by status.
     *
     * @param string $status Order status
     * @return int
     */
    public function countByStatus(string $status): int
    {
        return $this->orderRepo->countByStatus($status);
    }

    /**
     * Count buyer orders with filters.
     *
     * @param int $id Buyer user ID
     * @param string $filter Status filter
     * @param string $search Search term
     * @return int
     */
    public function countByBuyer(int $id, string $filter = 'all', string $search = ''): int
    {
        return $this->orderRepo->countByBuyer($id, $filter, $search);
    }

    /**
     * Count seller orders with filters.
     *
     * @param int $id Seller user ID
     * @param string $filter Status filter
     * @param string $search Search term
     * @return int
     */
    public function countBySeller(int $id, string $filter = 'all', string $search = ''): int
    {
        return $this->orderRepo->countBySeller($id, $filter, $search);
    }
}
