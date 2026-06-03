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
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    /**
     * Create a transaction record from successful payment
     */
    public function createFromPayment(int $orderId, string $payfastRef, float $amount): Transaction
    {
        $stmt = $this->db->prepare(
            "INSERT INTO transactions (order_id, payfast_ref, amount, status, paid_at) 
             VALUES (?, ?, ?, 'completed', NOW())"
        );
        $stmt->bind_param('isd', $orderId, $payfastRef, $amount);
        $stmt->execute();
        $transactionId = $stmt->insert_id;
        $stmt->close();

        return new Transaction([
            'transaction_id' => $transactionId,
            'order_id' => $orderId,
            'payfast_ref' => $payfastRef,
            'amount' => $amount,
            'status' => 'completed',
            'paid_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get transaction by order ID
     */
    public function getByOrderId(int $orderId): ?Transaction
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
     * Get all transactions (for admin)
     */
    public function getAll(): array
    {
        $stmt = $this->db->prepare("
            SELECT t.*, o.buyer_id, o.seller_id 
            FROM transactions t
            JOIN orders o ON t.order_id = o.order_id
            ORDER BY t.transaction_id DESC
        ");
        $stmt->execute();
        $result = $stmt->get_result();

        $transactions = [];
        while ($row = $result->fetch_assoc()) {
            $transactions[] = new Transaction($row);
        }
        $stmt->close();

        return $transactions;
    }
}
