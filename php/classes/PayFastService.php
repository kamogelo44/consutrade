<?php

/**
 * ConsuTrade - PayFastService
 *
 * Coordinates payment flow between our system and PayFast.
 * I chose to make this a service class rather than putting payment logic
 * in the repository because it needs to coordinate multiple repositories
 * (orders, transactions, products, cart) which repositories shouldn't do.
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
     * Constructor - injecting dependencies instead of creating them inside.
     * This makes testing easier and follows dependency inversion.
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
     * Main entry point for PayFast's server-to-server notification.
     * PayFast calls this after user pays, regardless of whether they close the browser.
     *
     * @param array $postData $_POST data from PayFast
     * @return array Response with success flag and message
     */
    public function handleItn(array $postData): array
    {
        // Without signature verification, anyone could fake a payment notification
        if (!$this->verifySignature($postData)) {
            error_log("PayFast ITN: Invalid signature");
            return ['success' => false, 'message' => 'Invalid signature'];
        }

        // Even with valid signature, we double-check with PayFast's server
        // This prevents a attacker who somehow got our merchant key
        if (!$this->validateWithPayFast($postData)) {
            error_log("PayFast ITN: Server validation failed");
            return ['success' => false, 'message' => 'PayFast validation failed'];
        }

        return $this->processPayment($postData);
    }

    /**
     * Verifies the signature PayFast sent.
     * PayFast generates this using our merchant key and the posted data.
     * If it doesn't match, the request is fake.
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
     * Confirms with PayFast that the payment actually happened.
     * The initial ITN could be spoofed, so we ask PayFast directly.
     * This is server-to-server so the attacker can't intercept it.
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
     * Actually updates our database after PayFast confirms payment.
     * Everything runs in a transaction so if one part fails, nothing changes.
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

        $parts = explode('_', $paymentId);
        $orderId = (int)($parts[0] ?? 0);
        $userId = (int)($parts[1] ?? 0);

        // PayFast sends 'COMPLETE' only for successful payments
        if ($paymentStatus !== 'COMPLETE') {
            error_log("PayFast: Payment status not COMPLETE - {$paymentStatus}");
            return ['success' => false, 'message' => 'Payment not complete'];
        }

        if ($orderId <= 0 || $userId <= 0) {
            error_log("PayFast: Invalid orderId or userId - orderId: {$orderId}, userId: {$userId}");
            return ['success' => false, 'message' => 'Invalid payment data'];
        }

        $this->db->begin_transaction();

        try {
            // PayFast might send the same ITN twice (network issues).
            // If we already recorded this transaction, just acknowledge it.
            $existingTransaction = $this->transactionRepo->getByOrderId($orderId);
            if ($existingTransaction !== null) {
                error_log("PayFast: Order {$orderId} already has a transaction. Skipping.");
                $this->db->commit();
                return ['success' => true, 'message' => 'Payment already processed'];
            }

            $orders = $this->getOrdersByPaymentId($paymentId, $userId);

            if (empty($orders)) {
                $order = $this->getSingleOrder($orderId, $userId);
                if ($order) {
                    $orders = [$order];
                }
            }

            if (empty($orders)) {
                throw new Exception('No pending orders found for this payment');
            }

            $totalAmount = 0;
            $transactions = [];

            foreach ($orders as $order) {
                $totalAmount += $order['total_price'];

                $orderUpdated = $this->orderRepo->updateOrderStatusDirect($order['order_id'], 'processing');

                if (!$orderUpdated) {
                    throw new Exception("Failed to update order status for order_id: {$order['order_id']}");
                }

                $transaction = $this->transactionRepo->createFromPayment(
                    $order['order_id'],
                    $payfastRef,
                    $order['total_price']
                );

                // If we can't record the transaction, we can't prove payment happened
                if ($transaction === false) {
                    throw new Exception("Failed to create transaction record for order_id: {$order['order_id']}");
                }

                $transactions[] = $transaction;

                $stockDecreased = $this->decreaseOrderStock($order['order_id']);

                if (!$stockDecreased) {
                    throw new Exception("Failed to decrease stock for order_id: {$order['order_id']}");
                }
            }

            // Small rounding differences are normal due to PayFast's fees or formatting
            if (abs($totalAmount - $amountReceived) >= 0.01) {
                error_log("PayFast amount mismatch for payment_id: {$paymentId}. Expected: {$totalAmount}, Received: {$amountReceived}");
            }

            // Only clear cart after we're sure everything succeeded
            $this->cartRepo->clearUserCart($userId);

            $this->db->commit();
            error_log("PayFast: Payment processed successfully for payment_id: {$paymentId}");

            return [
                'success' => true,
                'message' => 'Payment processed successfully',
                'transactions' => $transactions
            ];
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("PayFast processing error: " . $e->getMessage());

            // Set orders to 'payment_failed' so admin knows something went wrong
            // User can still retry since cart wasn't cleared
            if (!empty($orders)) {
                foreach ($orders as $order) {
                    $this->orderRepo->updateOrderStatusDirect($order['order_id'], 'payment_failed');
                }
            }

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Fetches all orders tied to one payment ID.
     * One cart with multiple sellers creates multiple orders but one payment.
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
     * Fallback when payment_id doesn't match multiple orders.
     * Older version of the code used single-order payment IDs.
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
     * Removes sold items from inventory.
     * If any product lacks sufficient stock (shouldn't happen since we checked earlier),
     * we catch it here and roll back the entire transaction.
     *
     * @param int $orderId Order ID
     * @return bool True if all stock updates succeeded
     */
    private function decreaseOrderStock(int $orderId): bool
    {
        $sql = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $result = $stmt->get_result();

        $allSuccess = true;
        while ($item = $result->fetch_assoc()) {
            $success = $this->productRepo->decreaseProductStock($item['product_id'], $item['quantity']);
            if (!$success) {
                error_log("Failed to decrease stock for product_id: {$item['product_id']}, quantity: {$item['quantity']}");
                $allSuccess = false;
            }
        }
        $stmt->close();

        return $allSuccess;
    }

    /**
     * Builds the data array for the PayFast form.
     * The actual HTML form is in checkout.php - this just provides the values.
     * Keeps payment gateway details out of the view layer.
     *
     * @param array $orderData Order information
     * @param string $baseUrl Site base URL
     * @return array PayFast parameters
     */
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
