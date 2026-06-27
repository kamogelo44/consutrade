<?php

/**
 * ConsuTrade - PayFastService
 *
 * Coordinates the payment flow between our system and PayFast.
 * This service handles ITN verification, payment processing, and order updates.
 *
 * @author Kamogelo Phale
 * @version 2.2.0
 */

class PayFastService
{
    private mysqli $db;
    private OrderRepository $orderRepo;
    private CartRepository $cartRepo;
    private TransactionRepository $transactionRepo;

    public function __construct(
        mysqli $db,
        OrderRepository $orderRepo,
        CartRepository $cartRepo,
        TransactionRepository $transactionRepo
    ) {
        $this->db = $db;
        $this->orderRepo = $orderRepo;
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
        if (!$this->verifySignature($postData)) {
            error_log("PayFast ITN: Invalid signature for payment_id: " . ($postData['m_payment_id'] ?? 'unknown'));
            return ['success' => false, 'message' => 'Invalid signature'];
        }

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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("PayFast validation CURL error: " . $error);
            return false;
        }

        $response = trim($response);
        if ($response === 'VALID' || $response === 'VERIFIED') {
            return true;
        }

        error_log("PayFast validation failed. Response was: " . substr($response, 0, 200));
        return false;
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

        $parts = explode('_', $paymentId);
        $timestamp = (int)($parts[0] ?? 0);
        $userId = (int)($parts[1] ?? 0);

        if ($paymentStatus !== 'COMPLETE') {
            return ['success' => false, 'message' => 'Payment not complete'];
        }

        if (empty($paymentId) || empty($payfastRef) || $userId <= 0) {
            error_log("PayFast ITN: Invalid data - paymentId: $paymentId, payfastRef: $payfastRef, userId: $userId");
            return ['success' => false, 'message' => 'Invalid payment data'];
        }

        $this->db->begin_transaction();

        try {
            $orders = $this->getOrdersByPaymentId($paymentId, $userId);

            if (empty($orders) && $timestamp > 0) {
                $singleOrder = $this->getSingleOrder($timestamp, $userId);
                if ($singleOrder) {
                    $orders = [$singleOrder];
                }
            }

            if (empty($orders)) {
                $existingOrder = $this->getOrderByPaymentIdAnyStatus($paymentId, $userId);
                if ($existingOrder && in_array($existingOrder['status'], ['processing', 'completed', 'shipped'])) {
                    $this->cartRepo->deleteAllByUser($userId);
                    $this->db->commit();
                    return ['success' => true, 'message' => 'Payment already processed'];
                }
                throw new Exception('No pending orders found for this payment');
            }

            $processedCount = 0;

            foreach ($orders as $order) {
                if ($order['status'] !== 'pending') {
                    $processedCount++;
                    continue;
                }

                $updated = $this->orderRepo->updateStatusDirect($order['order_id'], 'processing');
                if (!$updated) {
                    throw new Exception("Failed to update order status for order_id: {$order['order_id']}");
                }

                $existingTransaction = $this->transactionRepo->findByOrderId($order['order_id']);
                if ($existingTransaction) {
                    $updated = $this->transactionRepo->updatePayFastRef(
                        $existingTransaction->getTransactionId(),
                        $payfastRef,
                        'completed'
                    );
                    if (!$updated) {
                        throw new Exception("Failed to update transaction for order_id: {$order['order_id']}");
                    }
                } else {
                    $result = $this->transactionRepo->createFromPayment(
                        $order['order_id'],
                        $payfastRef,
                        $order['total_price']
                    );
                    if ($result === false) {
                        throw new Exception("Failed to create transaction record for order_id: {$order['order_id']}");
                    }
                }

                $processedCount++;
            }

            $this->cartRepo->deleteAllByUser($userId);

            $this->db->commit();
            return ['success' => true, 'message' => 'Payment processed successfully'];
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("PayFast ITN Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Finds all pending orders associated with a payment ID.
     *
     * @param string $paymentId The payment ID
     * @param int $userId The buyer's user ID
     * @return array List of orders
     */
    private function getOrdersByPaymentId(string $paymentId, int $userId): array
    {
        $sql = "SELECT order_id, total_price, status FROM orders 
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
     * Fallback to find a single pending order when payment_id doesn't match multiple orders.
     *
     * @param int $orderId The order ID
     * @param int $userId The buyer's user ID
     * @return array|null Order data or null if not found
     */
    private function getSingleOrder(int $orderId, int $userId): ?array
    {
        $sql = "SELECT order_id, total_price, status FROM orders 
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
     * Get an order by payment ID regardless of status.
     * Used to check if an order was already processed.
     *
     * @param string $paymentId The payment ID
     * @param int $userId The buyer's user ID
     * @return array|null Order data or null if not found
     */
    private function getOrderByPaymentIdAnyStatus(string $paymentId, int $userId): ?array
    {
        $sql = "SELECT order_id, status FROM orders 
                WHERE payment_id = ? AND buyer_id = ? 
                ORDER BY order_id DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('si', $paymentId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();
        return $order ?: null;
    }
}
