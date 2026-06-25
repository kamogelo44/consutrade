<?php

/**
 * ConsuTrade - OrderService
 *
 * Handles order business logic including creation, status updates,
 * cancellations, and revenue calculations.
 *
 * @author Kamogelo Phale
 * @version 1.0.0
 */

class OrderService
{
    private mysqli $db;
    private OrderRepository $orderRepo;
    private ProductRepository $productRepo;
    private TransactionRepository $transactionRepo;

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

    /**
     * Create orders from cart items.
     */
    public function createFromCart(int $buyerId, array $items, array $sellers): ?array
    {
        return $this->orderRepo->createFromCart($buyerId, $items, $sellers);
    }

    /**
     * Update order status with business logic (stock restoration on cancel).
     */
    public function updateStatus(int $orderId, int $sellerId, string $status): array
    {
        $result = $this->orderRepo->updateStatus($orderId, $sellerId, $status);

        if ($result['success'] && $status === 'cancelled') {
            $this->productRepo->restoreStockFromOrder($orderId);
        }

        return $result;
    }

    /**
     * Cancel order by buyer with stock restoration.
     */
    public function cancelByBuyer(int $orderId, int $buyerId): bool
    {
        $result = $this->orderRepo->cancelByBuyer($orderId, $buyerId);

        if ($result) {
            $this->productRepo->restoreStockFromOrder($orderId);
        }

        return $result;
    }

    /**
     * Get total revenue from all completed transactions.
     */
    public function getTotalRevenue(): float
    {
        return $this->orderRepo->getTotalRevenue();
    }

    /**
     * Get total revenue for a seller from completed orders.
     */
    public function getSellerTotalRevenue(int $sellerId): float
    {
        return $this->orderRepo->getSellerTotalRevenue($sellerId);
    }

    /**
     * Get total number of completed orders for a seller.
     */
    public function getSellerTotalOrders(int $sellerId): int
    {
        return $this->orderRepo->getSellerTotalOrders($sellerId);
    }

    /**
     * Get order by ID with role-based access.
     */
    public function findById(int $orderId, int $userId, string $role): ?array
    {
        return $this->orderRepo->findById($orderId, $userId, $role);
    }

    /**
     * Get buyer orders with filters.
     */
    public function findByBuyer(int $id, string $filter = 'all', string $search = '', int $limit = 0, int $offset = 0): array
    {
        return $this->orderRepo->findByBuyer($id, $filter, $search, $limit, $offset);
    }

    /**
     * Get seller orders with filters.
     */
    public function findBySeller(int $id, string $filter = 'all', string $search = '', int $limit = 0, int $offset = 0): array
    {
        return $this->orderRepo->findBySeller($id, $filter, $search, $limit, $offset);
    }

    /**
     * Get all orders for admin.
     */
    public function findAll(): array
    {
        return $this->orderRepo->findAll();
    }

    /**
     * Get recent orders for admin dashboard.
     */
    public function findRecent(int $limit = 5): array
    {
        return $this->orderRepo->findRecent($limit);
    }

    /**
     * Get buyer statistics.
     */
    public function findBuyerStats(int $buyerId): array
    {
        return $this->orderRepo->findBuyerStats($buyerId);
    }

    /**
     * Get order items by order ID.
     */
    public function getOrderItems(int $orderId): array
    {
        return $this->orderRepo->getOrderItems($orderId);
    }

    /**
     * Count all orders.
     */
    public function countAll(): int
    {
        return $this->orderRepo->countAll();
    }

    /**
     * Count orders by status.
     */
    public function countByStatus(string $status): int
    {
        return $this->orderRepo->countByStatus($status);
    }

    /**
     * Count buyer orders with filters.
     */
    public function countByBuyer(int $id, string $filter = 'all', string $search = ''): int
    {
        return $this->orderRepo->countByBuyer($id, $filter, $search);
    }

    /**
     * Count seller orders with filters.
     */
    public function countBySeller(int $id, string $filter = 'all', string $search = ''): int
    {
        return $this->orderRepo->countBySeller($id, $filter, $search);
    }
}
