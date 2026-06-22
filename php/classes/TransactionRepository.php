<?php

/**
 * ConsuTrade - TransactionRepository
 *
 * Handles all transaction database operations.
 *
 * @author Kamogelo Phale
 * @version 2.0.0
 */

class TransactionRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // ============================================================
    // CREATE
    // ============================================================

    /**
     * Create a transaction record from successful payment.
     *
     * @param int $orderId Order ID
     * @param string $payfastRef PayFast reference
     * @param float $amount Payment amount
     * @return Transaction|false
     */
    public function createFromPayment(int $orderId, string $payfastRef, float $amount): Transaction|false
    {
        $stmt = $this->db->prepare(
            "INSERT INTO transactions (order_id, payfast_ref, amount, status, paid_at) 
             VALUES (?, ?, ?, 'completed', NOW())"
        );
        $stmt->bind_param('isd', $orderId, $payfastRef, $amount);

        if (!$stmt->execute()) {
            error_log("Transaction creation failed for order_id: $orderId - " . $stmt->error);
            $stmt->close();
            return false;
        }

        $transactionId = $stmt->insert_id;
        $stmt->close();

        if ($transactionId > 0) {
            return new Transaction([
                'transaction_id' => $transactionId,
                'order_id' => $orderId,
                'payfast_ref' => $payfastRef,
                'amount' => $amount,
                'status' => 'completed',
                'paid_at' => date('Y-m-d H:i:s')
            ]);
        }

        return false;
    }

    // ============================================================
    // READ
    // ============================================================

    /**
     * Get transaction by ID.
     *
     * @param int $transactionId Transaction ID
     * @return Transaction|null
     */
    public function findById(int $transactionId): ?Transaction
    {
        $stmt = $this->db->prepare("SELECT * FROM transactions WHERE transaction_id = ?");
        $stmt->bind_param('i', $transactionId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return new Transaction($row);
        }
        $stmt->close();
        return null;
    }

    /**
     * Get transaction by order ID.
     *
     * @param int $orderId Order ID
     * @return Transaction|null
     */
    public function findByOrderId(int $orderId): ?Transaction
    {
        $stmt = $this->db->prepare("SELECT * FROM transactions WHERE order_id = ? LIMIT 1");
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return new Transaction($row);
        }
        $stmt->close();
        return null;
    }

    /**
     * Get all transactions for a specific order.
     *
     * @param int $orderId Order ID
     * @return array
     */
    public function findAllByOrderId(int $orderId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM transactions WHERE order_id = ? ORDER BY transaction_id DESC");
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $result = $stmt->get_result();

        $transactions = [];
        while ($row = $result->fetch_assoc()) {
            $transactions[] = new Transaction($row);
        }
        $stmt->close();

        return $transactions;
    }

    /**
     * Get all transactions (for admin).
     *
     * @param int $limit Results per page
     * @param int $offset Pagination offset
     * @return array
     */
    public function findAll(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->db->prepare("
            SELECT t.*, o.buyer_id, o.seller_id 
            FROM transactions t
            JOIN orders o ON t.order_id = o.order_id
            ORDER BY t.transaction_id DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        $transactions = [];
        while ($row = $result->fetch_assoc()) {
            $transactions[] = new Transaction($row);
        }
        $stmt->close();

        return $transactions;
    }

    /**
     * Get recent transactions for dashboard.
     *
     * @param int $limit Number of transactions
     * @return array
     */
    public function findRecent(int $limit = 5): array
    {
        $stmt = $this->db->prepare("
            SELECT t.*, o.buyer_id, o.seller_id,
                   buyer.full_name as buyer_name,
                   seller.full_name as seller_name
            FROM transactions t
            JOIN orders o ON t.order_id = o.order_id
            JOIN users buyer ON o.buyer_id = buyer.user_id
            JOIN users seller ON o.seller_id = seller.user_id
            ORDER BY t.transaction_id DESC
            LIMIT ?
        ");
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        $transactions = [];
        while ($row = $result->fetch_assoc()) {
            $transaction = new Transaction($row);
            $transactions[] = [
                'transaction' => $transaction,
                'buyer_name' => $row['buyer_name'],
                'seller_name' => $row['seller_name']
            ];
        }
        $stmt->close();

        return $transactions;
    }

    /**
     * Get total transaction count.
     *
     * @return int
     */
    public function countAll(): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM transactions");
        $stmt->execute();
        $result = $stmt->get_result();
        $total = (int) ($result->fetch_assoc()['total'] ?? 0);
        $stmt->close();
        return $total;
    }

    /**
     * Get total revenue from completed transactions.
     *
     * @return float
     */
    public function getTotalRevenue(): float
    {
        $stmt = $this->db->prepare("SELECT SUM(amount) as total FROM transactions WHERE status = 'completed'");
        $stmt->execute();
        $result = $stmt->get_result();
        $total = (float) ($result->fetch_assoc()['total'] ?? 0);
        $stmt->close();
        return $total;
    }

    // ============================================================
    // UPDATE
    // ============================================================

    /**
     * Update transaction status.
     *
     * @param int $transactionId Transaction ID
     * @param string $status New status
     * @return bool
     */
    public function updateStatus(int $transactionId, string $status): bool
    {
        $validStatuses = ['pending', 'completed', 'failed', 'refunded'];
        if (!in_array($status, $validStatuses)) {
            return false;
        }

        $stmt = $this->db->prepare("UPDATE transactions SET status = ? WHERE transaction_id = ?");
        $stmt->bind_param('si', $status, $transactionId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // ============================================================
    // DELETE
    // ============================================================

    /**
     * Delete a transaction by ID.
     *
     * @param int $transactionId Transaction ID
     * @return bool
     */
    public function delete(int $transactionId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM transactions WHERE transaction_id = ?");
        $stmt->bind_param('i', $transactionId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Delete all transactions for an order.
     *
     * @param int $orderId Order ID
     * @return bool
     */
    public function deleteByOrderId(int $orderId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM transactions WHERE order_id = ?");
        $stmt->bind_param('i', $orderId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
