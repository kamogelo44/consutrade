<?php

/**
 * ConsuTrade - PayFastService
 *
 * Coordinates the payment flow between our system and PayFast.
 * Uses idempotency keys to prevent duplicate payment processing.
 *
 * @author Kamogelo Phale
 * @version 3.0.0
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
     *
     * @param array $postData The POST data from PayFast
     * @return array Response with success flag and message
     */
    public function handleItn(array $postData): array
    {
        if (!$this->verifySignature($postData)) {
            return ['success' => false, 'message' => 'Invalid signature'];
        }

        if (!$this->validateWithPayFast($postData)) {
            return ['success' => false, 'message' => 'PayFast validation failed'];
        }

        return $this->processPayment($postData);
    }

    /**
     * Verifies the signature sent by PayFast.
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

        return hash_equals(md5($pfParamString), $data['signature']);
    }

    /**
     * Confirms with PayFast that the payment actually happened.
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
            return false;
        }

        return in_array(trim($response), ['VALID', 'VERIFIED']);
    }

    /**
     * Processes a confirmed payment using idempotency to prevent duplicates.
     * Uses m_payment_id from PayFast as the idempotency key.
     *
     * @param array $data The validated POST data from PayFast
     * @return array Result with success flag and message
     */
    protected function processPayment(array $data): array
    {
        $paymentStatus = $data['payment_status'] ?? '';
        $paymentId = $data['m_payment_id'] ?? '';
        $payfastRef = $data['pf_payment_id'] ?? '';

        if ($paymentStatus !== 'COMPLETE') {
            return ['success' => false, 'message' => 'Payment not complete'];
        }

        if (empty($paymentId) || empty($payfastRef)) {
            return ['success' => false, 'message' => 'Invalid payment data'];
        }

        $parts = explode('_', $paymentId);
        $userId = (int)($parts[1] ?? 0);

        if ($userId <= 0) {
            return ['success' => false, 'message' => 'Invalid user in payment data'];
        }

        $this->db->begin_transaction();

        try {
            $orders = $this->getOrdersByPaymentId($paymentId, $userId);

            if (empty($orders)) {
                $existingOrder = $this->getOrderByPaymentIdAnyStatus($paymentId, $userId);
                if ($existingOrder && $existingOrder['status'] !== 'pending') {
                    $this->cartRepo->deleteAllByUser($userId);
                    $this->db->commit();
                    return ['success' => true, 'message' => 'Payment already processed'];
                }
                throw new Exception('No orders found for this payment');
            }

            foreach ($orders as $order) {
                if ($order['status'] !== 'pending') {
                    continue;
                }

                $this->orderRepo->updateStatusDirect($order['order_id'], 'processing');

                // Use m_payment_id as idempotency key to prevent duplicates
                $this->transactionRepo->createFromPayment(
                    $order['order_id'],
                    $payfastRef,
                    $order['total_price'],
                    $paymentId  // idempotency key
                );
            }

            $this->cartRepo->deleteAllByUser($userId);
            $this->db->commit();

            return ['success' => true, 'message' => 'Payment processed'];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Finds all pending orders associated with a payment ID.
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
     * Get an order by payment ID regardless of status.
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
