<?php

/**
 * ConsuTrade - PayFastService
 *
 * Coordinates payment flow between our system and PayFast.
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
            return false;
        }

        return strpos($response, 'VERIFIED') !== false;
    }

    private function processPayment(array $data): array
    {
        $paymentStatus = $data['payment_status'] ?? '';
        $paymentId = $data['m_payment_id'] ?? '';
        $payfastRef = $data['pf_payment_id'] ?? '';
        $amountReceived = (float)($data['amount'] ?? 0);

        $parts = explode('_', $paymentId);
        $orderId = (int)($parts[0] ?? 0);
        $userId = (int)($parts[1] ?? 0);

        if ($paymentStatus !== 'COMPLETE') {
            return ['success' => false, 'message' => 'Payment not complete'];
        }

        $this->db->begin_transaction();

        try {
            $orders = $this->getOrdersByPaymentId($paymentId, $userId);
            if (empty($orders)) {
                throw new Exception('No pending orders found for this payment');
            }

            foreach ($orders as $order) {
                // 1. Update order status
                $this->orderRepo->updateStatusDirect($order['order_id'], 'processing');

                // 2. Update transaction from 'pending' to 'completed'
                $transaction = $this->transactionRepo->findByOrderId($order['order_id']);
                if ($transaction) {
                    $this->transactionRepo->updateStatus(
                        $transaction->getTransactionId(),
                        'completed'
                    );
                    // You'll need to add a method to update PayFast reference
                    // Or just create a new transaction record with the PayFast ref
                } else {
                    // Fallback: create transaction if it doesn't exist
                    $this->transactionRepo->createFromPayment(
                        $order['order_id'],
                        $payfastRef,
                        $order['total_price']
                    );
                }
            }

            // 3. Clear the cart (ONLY NOW - after payment is confirmed)
            $this->cartRepo->deleteAllByUser($userId);

            $this->db->commit();

            return ['success' => true, 'message' => 'Payment processed successfully'];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

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
     * Restore stock for an order (when payment fails or order is cancelled).
     *
     * @param int $orderId Order ID
     * @return bool
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

    public function preparePayFastData(array $orderData, string $baseUrl): array
    {
        $buyerName = $orderData['buyer_name'] ?? 'Customer';
        $nameParts = explode(' ', $buyerName, 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        return [
            'merchant_id' => PAYFAST_MERCHANT_ID,
            'merchant_key' => PAYFAST_MERCHANT_KEY,
            'return_url' => $baseUrl . 'order-confirmation.php',
            'cancel_url' => $baseUrl . 'cart.php',
            'notify_url' => $baseUrl . 'php/endpoints/payfast-notify.php',
            'm_payment_id' => $orderData['payment_id'],
            'amount' => number_format($orderData['total'], 2, '.', ''),
            'item_name' => 'ConsuTrade Order #' . ($orderData['primary_order_id'] ?? ''),
            'item_description' => 'Purchase from ConsuTrade',
            'name_first' => $firstName,
            'name_last' => $lastName,
            'email_address' => $orderData['buyer_email'] ?? ''
        ];
    }
}
