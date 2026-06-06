<?php

/**
 * ConsuTrade - PayFastService
 *
 * Handles PayFast payment verification and processing.
 * This is a SERVICE class that coordinates multiple repositories.
 * 
 * NOTE: This class does NOT generate HTML forms - that belongs in frontend views.
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

    /**
     * Constructor with dependency injection.
     *
     * @param mysqli $db Database connection
     * @param OrderRepository $orderRepo Order repository
     * @param ProductRepository $productRepo Product repository
     * @param CartRepository $cartRepo Cart repository
     * @param TransactionRepository $transactionRepo Transaction repository
     */
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
     * Handle PayFast ITN (Instant Transaction Notification) request.
     *
     * @param array $postData $_POST data from PayFast
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
     * Verify PayFast signature.
     * 
     * @param array $data POST data from PayFast
     * @return bool True if signature is valid
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
     * Validate payment with PayFast server (server-to-server confirmation).
     *
     * @param array $data POST data from PayFast
     * @return bool True if PayFast confirms payment
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

        return strpos($response, 'VERIFIED') !== false;
    }

    /**
     * Process successful payment.
     *
     * @param array $data Validated POST data from PayFast
     * @return array Result with success flag and details
     */
    private function processPayment(array $data): array
    {
        $paymentStatus = $data['payment_status'] ?? '';
        $paymentId = $data['m_payment_id'] ?? '';
        $payfastRef = $data['pf_payment_id'] ?? '';
        $amountReceived = (float)($data['amount'] ?? 0);

        // Parse payment ID format: "orderId_userId_timestamp"
        $parts = explode('_', $paymentId);
        $orderId = (int)($parts[0] ?? 0);
        $userId = (int)($parts[1] ?? 0);

        if ($paymentStatus !== 'COMPLETE' || $orderId <= 0 || $userId <= 0) {
            return ['success' => false, 'message' => 'Invalid payment data'];
        }

        $this->db->begin_transaction();

        try {
            // Try to find orders by payment_id first (supports multiple orders)
            $orders = $this->getOrdersByPaymentId($paymentId, $userId);

            if (empty($orders)) {
                // Fallback to single order lookup
                $order = $this->getSingleOrder($orderId, $userId);
                if ($order) {
                    $orders = [$order];
                }
            }

            if (empty($orders)) {
                throw new Exception('No orders found for payment');
            }

            $totalAmount = 0;
            $transactions = [];

            foreach ($orders as $order) {
                $totalAmount += $order['total_price'];

                // Update order status
                $this->orderRepo->updateOrderStatusDirect($order['order_id'], 'processing');

                // Create transaction record
                $transaction = $this->transactionRepo->createFromPayment(
                    $order['order_id'],
                    $payfastRef,
                    $order['total_price']
                );
                $transactions[] = $transaction;

                // Decrease stock for items in this order
                $this->decreaseOrderStock($order['order_id']);
            }

            // Log amount mismatch but don't fail the transaction
            if (abs($totalAmount - $amountReceived) >= 0.01) {
                error_log("PayFast amount mismatch for payment_id: $paymentId. Expected: $totalAmount, Received: $amountReceived");
            }

            // Clear user's cart after successful payment
            $this->cartRepo->clearUserCart($userId);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Payment processed successfully',
                'transactions' => $transactions
            ];
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("PayFast processing error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get orders by payment ID (supports multiple orders in one payment).
     *
     * @param string $paymentId Payment ID from frontend
     * @param int $userId Buyer user ID
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
     * Get a single order by order ID (fallback method).
     *
     * @param int $orderId Order ID
     * @param int $userId Buyer user ID
     * @return array|null Order data or null
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
     * Decrease stock for all items in an order.
     *
     * @param int $orderId Order ID
     * @return void
     */
    private function decreaseOrderStock(int $orderId): void
    {
        $sql = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($item = $result->fetch_assoc()) {
            $this->productRepo->decreaseProductStock($item['product_id'], $item['quantity']);
        }
        $stmt->close();
    }

    /**
     * Prepare PayFast data array (NOT HTML form).
     * Returns data that the frontend view will use to build the form.
     *
     * @param array $orderData Order information
     * @param string $baseUrl Site base URL
     * @return array PayFast parameters
     */
    public function preparePayFastData(array $orderData, string $baseUrl): array
    {
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
            'name_first' => explode(' ', $orderData['buyer_name'] ?? 'Customer')[0],
            'name_last' => count(explode(' ', $orderData['buyer_name'] ?? 'Customer')) > 1
                ? substr($orderData['buyer_name'], strpos($orderData['buyer_name'], ' ') + 1)
                : 'Customer',
            'email_address' => $orderData['buyer_email'] ?? ''
        ];
    }
}
