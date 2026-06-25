<?php

/**
 * ConsuTrade - PayFastService
 *
 * Coordinates the payment flow between our system and PayFast.
 * This service handles ITN verification, payment processing, and order updates.
 *
 * @author Kamogelo Phale
 * @version 2.1.0
 */

class PayFastService
{
    private mysqli $db;
    private OrderRepository $orderRepo;
    private ProductRepository $productRepo;
    private CartRepository $cartRepo;
    private TransactionRepository $transactionRepo;

    public function __construct(
        mysqli $db,
        OrderRepository $orderRepo,
        ProductRepository $productRepo,
        CartRepository $cartRepo,
        TransactionRepository $transactionRepo
    ) {
        $this->db = $db;
        $this->orderRepo = $orderRepo;
        $this->productRepo = $productRepo;
        $this->cartRepo = $cartRepo;
        $this->transactionRepo = $transactionRepo;
    }

    /**
     * Main entry point for PayFast's server-to-server notification.
     * PayFast calls this after a user completes payment.
     *
     * @param array $postData The POST data from PayFast
     * @return array Response with success flag and message
     */
    public function handleItn(array $postData): array
    {
        // Verify the request is actually from PayFast
        if (!$this->verifySignature($postData)) {
            error_log("PayFast ITN: Invalid signature for payment_id: " . ($postData['m_payment_id'] ?? 'unknown'));
            return ['success' => false, 'message' => 'Invalid signature'];
        }

        // Double-check with PayFast's server that the payment is legitimate
        if (!$this->validateWithPayFast($postData)) {
            error_log("PayFast ITN: Validation failed for payment_id: " . ($postData['m_payment_id'] ?? 'unknown'));
            return ['success' => false, 'message' => 'PayFast validation failed'];
        }

        return $this->processPayment($postData);
    }

    /**
     * Verifies the signature sent by PayFast to ensure the request is authentic.
     * PayFast generates this using our merchant key and the posted data.
     *
     * @param array $data The POST data from PayFast
     * @return bool True if the signature is valid
     */
    private function verifySignature(array $data): bool
    {
        if (!isset($data['signature'])) {
            return false;
        }

        $pfParamString = '';
        foreach ($data as $key => $val) {
            if ($key !== 'signature') {
                $pfParamString .= $key . '=' . urlencode(trim($val)) . '&';
            }
        }
        $pfParamString = rtrim($pfParamString, '&');

        $expectedSignature = md5($pfParamString);
        return hash_equals($expectedSignature, $data['signature']);
    }

    /**
     * Confirms with PayFast that the payment actually happened.
     * This prevents spoofed ITN requests.
     *
     * @param array $data The POST data from PayFast
     * @return bool True if PayFast confirms the payment
     */
    private function validateWithPayFast(array $data): bool
    {
        $payfastUrl = PAYFAST_SANDBOX
            ? 'https://sandbox.payfast.co.za/eng/query/validate'
            : 'https://www.payfast.co.za/eng/query/validate';

        $pfRequest = '';
        foreach ($data as $key => $val) {
            $pfRequest .= $key . '=' . urlencode(trim($val)) . '&';
        }
        $pfRequest = rtrim($pfRequest, '&');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $payfastUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $pfRequest);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("PayFast validation CURL error: " . $error);
            return false;
        }

        $verified = strpos($response, 'VERIFIED') !== false;
        if (!$verified) {
            error_log("PayFast validation failed. Response: " . substr($response, 0, 200));
        }
        return $verified;
    }

    /**
     * Processes a confirmed payment by updating order status,
     * creating a transaction record, and clearing the user's cart.
     * All operations run in a database transaction for consistency.
     *
     * @param array $data The validated POST data from PayFast
     * @return array Result with success flag and message
     */
    protected function processPayment(array $data): array
    {
        $paymentStatus = $data['payment_status'] ?? '';
        $paymentId = $data['m_payment_id'] ?? '';
        $payfastRef = $data['pf_payment_id'] ?? '';
        $amountReceived = (float)($data['amount'] ?? 0);

        // Parse payment_id format: orderId_userId
        $parts = explode('_', $paymentId);
        $orderId = (int)($parts[0] ?? 0);
        $userId = (int)($parts[1] ?? 0);

        // Validate payment status
        if ($paymentStatus !== 'COMPLETE') {
            error_log("PayFast ITN: Payment not complete for payment_id: $paymentId, status: $paymentStatus");
            return ['success' => false, 'message' => 'Payment not complete'];
        }

        // Validate required data
        if (empty($paymentId) || empty($payfastRef) || $userId <= 0) {
            error_log("PayFast ITN: Invalid data received - payment_id: $paymentId, pf_payment_id: $payfastRef, user_id: $userId");
            return ['success' => false, 'message' => 'Invalid payment data'];
        }

        $this->db->begin_transaction();

        try {
            // Find pending orders
            $orders = $this->getOrdersByPaymentId($paymentId, $userId);

            // Try legacy single-order format as fallback
            if (empty($orders) && $orderId > 0) {
                $singleOrder = $this->getSingleOrder($orderId, $userId);
                if ($singleOrder) {
                    $orders = [$singleOrder];
                    error_log("PayFast ITN: Using legacy single-order fallback for order_id: $orderId");
                }
            }

            if (empty($orders)) {
                throw new Exception('No pending orders found for this payment');
            }

            $processedCount = 0;

            foreach ($orders as $order) {
                // Check if already processed to prevent duplicate handling
                $existingTransaction = $this->transactionRepo->findByOrderId($order['order_id']);
                if ($existingTransaction && $existingTransaction->getStatus() === 'completed') {
                    error_log("PayFast ITN: Order {$order['order_id']} already processed, skipping");
                    continue;
                }

                // Mark the order as processing so the seller can fulfill it
                $updated = $this->orderRepo->updateStatusDirect($order['order_id'], 'processing');
                if (!$updated) {
                    throw new Exception("Failed to update order status for order_id: {$order['order_id']}");
                }
                error_log("PayFast ITN: Order {$order['order_id']} status updated to processing");

                // Update the transaction status to completed
                if ($existingTransaction) {
                    $updated = $this->transactionRepo->updateStatus(
                        $existingTransaction->getTransactionId(),
                        'completed'
                    );
                    if (!$updated) {
                        throw new Exception("Failed to update transaction status for order_id: {$order['order_id']}");
                    }
                    error_log("PayFast ITN: Transaction {$existingTransaction->getTransactionId()} updated to completed");
                } else {
                    // Fallback in case the transaction wasn't created earlier
                    $result = $this->transactionRepo->createFromPayment(
                        $order['order_id'],
                        $payfastRef,
                        $order['total_price']
                    );
                    if ($result === false) {
                        throw new Exception("Failed to create transaction record for order_id: {$order['order_id']}");
                    }
                    error_log("PayFast ITN: Transaction created for order_id: {$order['order_id']}");
                }

                $processedCount++;
            }

            if ($processedCount === 0) {
                throw new Exception('All orders were already processed');
            }

            // CRITICAL: Clear the user's cart ONLY after successful payment confirmation
            $cleared = $this->cartRepo->deleteAllByUser($userId);
            if (!$cleared) {
                error_log("PayFast ITN: Warning - Failed to clear cart for user_id: $userId");
            } else {
                error_log("PayFast ITN: Cart cleared for user_id: $userId");
            }

            $this->db->commit();
            error_log("PayFast ITN: Payment processed successfully for payment_id: $paymentId, orders: " . implode(',', array_column($orders, 'order_id')));

            return ['success' => true, 'message' => 'Payment processed successfully'];
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("PayFast ITN Error: " . $e->getMessage() . " for payment_id: $paymentId");
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Finds all pending orders associated with a payment ID.
     * A single payment can cover multiple orders from different sellers.
     *
     * @param string $paymentId The payment ID
     * @param int $userId The buyer's user ID
     * @return array List of orders
     */
    private function getOrdersByPaymentId(string $paymentId, int $userId): array
    {
        $sql = "SELECT order_id, total_price FROM orders 
                WHERE payment_id = ? AND buyer_id = ? AND status = 'pending'";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('si', $paymentId, $userId);
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
     * Fallback to find a single order when payment_id doesn't match multiple orders.
     * This handles the case where older code used single-order payment IDs.
     *
     * @param int $orderId The order ID
     * @param int $userId The buyer's user ID
     * @return array|null Order data or null if not found
     */
    private function getSingleOrder(int $orderId, int $userId): ?array
    {
        $sql = "SELECT order_id, total_price FROM orders 
                WHERE order_id = ? AND buyer_id = ? AND status = 'pending'";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $orderId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $order = $result->fetch_assoc();
        $stmt->close();

        return $order ?: null;
    }

    /**
     * Restores stock for all items in an order.
     * Called when payment fails or an order is cancelled.
     *
     * @param int $orderId The order ID
     * @return bool True if all stock was restored successfully
     */
    private function restoreOrderStock(int $orderId): bool
    {
        $sql = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $result = $stmt->get_result();

        $allSuccess = true;
        while ($item = $result->fetch_assoc()) {
            $success = $this->productRepo->increaseStock($item['product_id'], $item['quantity']);
            if (!$success) {
                error_log("Failed to restore stock for product_id: {$item['product_id']}, quantity: {$item['quantity']}");
                $allSuccess = false;
            }
        }
        $stmt->close();

        return $allSuccess;
    }
}
