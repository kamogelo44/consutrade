<?php

/**
 * ConsuTrade - TransactionRepository
 *
 * Handles all transaction database operations for payment records.
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
     * Creates a transaction record after a successful payment.
     * Uses idempotency key to prevent duplicate processing.
     *
     * @param int $orderId The order ID
     * @param string $payfastRef The PayFast payment reference
     * @param float $amount The amount paid
     * @param string|null $idempotencyKey Unique key to prevent duplicates
     * @return Transaction|false The transaction object or false on failure/duplicate
     */
    public function createFromPayment(int $orderId, string $payfastRef, float $amount, ?string $idempotencyKey = null): Transaction|false
    {
        // Check for duplicate if idempotency key provided
        if ($idempotencyKey) {
            $stmt = $this->db->prepare(
                "SELECT transaction_id FROM transactions WHERE idempotency_key = ?"
            );
            $stmt->bind_param('s', $idempotencyKey);
            $stmt->execute();
            if ($stmt->get_result()->fetch_assoc()) {
                $stmt->close();
                error_log("Duplicate transaction prevented for idempotency_key: $idempotencyKey");
                return false;
            }
            $stmt->close();
        }

        $stmt = $this->db->prepare(
            "INSERT INTO transactions (order_id, payfast_ref, amount, status, idempotency_key, paid_at) 
         VALUES (?, ?, ?, 'completed', ?, NOW())"
        );
        $stmt->bind_param('isds', $orderId, $payfastRef, $amount, $idempotencyKey);

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
     * Gets a transaction by its ID.
     *
     * @param int $transactionId The transaction ID
     * @return Transaction|null The transaction object or null if not found
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
     * Gets a transaction by its associated order ID.
     * Each order should have at most one transaction.
     *
     * @param int $orderId The order ID
     * @return Transaction|null The transaction object or null if not found
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
     * Gets all transactions for a specific order (in case of multiple attempts).
     *
     * @param int $orderId The order ID
     * @return array List of transaction objects
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
     * Gets all transactions for admin with pagination.
     *
     * @param int $limit Results per page
     * @param int $offset Pagination offset
     * @return array List of transaction objects
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
     * Gets recent transactions for the admin dashboard.
     *
     * @param int $limit Number of transactions to return
     * @return array List of transactions with buyer and seller names
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
     * Counts all transactions.
     *
     * @return int Total number of transactions
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
     * Gets the total revenue from all completed transactions.
     *
     * @return float Total revenue
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
     * Updates the status of a transaction.
     * Used when PayFast confirms or rejects a payment.
     *
     * @param int $transactionId The transaction ID
     * @param string $status New status ('pending', 'completed', 'failed', 'refunded')
     * @return bool True on success, false on failure
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

    /**
     * Updates the amount of a transaction.
     * Used when the final total is known after order creation.
     *
     * @param int $transactionId The transaction ID
     * @param float $amount The correct amount
     * @return bool True on success, false on failure
     */
    public function updateAmount(int $transactionId, float $amount): bool
    {
        $stmt = $this->db->prepare("UPDATE transactions SET amount = ? WHERE transaction_id = ?");
        $stmt->bind_param('di', $amount, $transactionId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Updates a transaction with the actual PayFast reference after payment.
     *
     * @param int $transactionId The transaction ID
     * @param string $payfastRef The actual PayFast payment reference
     * @param string $status New status ('completed')
     * @return bool True on success, false on failure
     */
    public function updatePayFastRef(int $transactionId, string $payfastRef, string $status = 'completed'): bool
    {
        $stmt = $this->db->prepare("UPDATE transactions SET payfast_ref = ?, status = ?, paid_at = NOW() WHERE transaction_id = ?");
        $stmt->bind_param('ssi', $payfastRef, $status, $transactionId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // ============================================================
    // DELETE
    // ============================================================

    /**
     * Deletes a transaction by ID.
     *
     * @param int $transactionId The transaction ID
     * @return bool True on success, false on failure
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
     * Deletes all transactions for a specific order.
     *
     * @param int $orderId The order ID
     * @return bool True on success, false on failure
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
