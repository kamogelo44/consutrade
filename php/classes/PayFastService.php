<?php

/**
 * ConsuTrade - PayFastService
 *
 * Handles PayFast payment verification and processing.
 *
 * @author Kamogelo Phale
 * @version 2.0.0
 */
class PayFastService
{
    private $db;
    private $orderRepo;
    private $productRepo;
    private $cartRepo;
    private $transactionRepo;

    public function __construct($db, $orderRepo, $productRepo, $cartRepo, $transactionRepo)
    {
        $this->db = $db;
        $this->orderRepo = $orderRepo;
        $this->productRepo = $productRepo;
        $this->cartRepo = $cartRepo;
        $this->transactionRepo = $transactionRepo;
    }

    /**
     * Handle PayFast ITN request
     */
    public function handleItn($postData)
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
     * Verify PayFast signature
     */
    private function verifySignature($data)
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
        return $expectedSignature === $data['signature'];
    }

    /**
     * Validate with PayFast server
     */
    private function validateWithPayFast($data)
    {
        $payfast_url = PAYFAST_SANDBOX
            ? 'https://sandbox.payfast.co.za/eng/query/validate'
            : 'https://www.payfast.co.za/eng/query/validate';

        $pfRequest = '';
        foreach ($data as $key => $val) {
            $pfRequest .= $key . '=' . urlencode(trim($val)) . '&';
        }
        $pfRequest = rtrim($pfRequest, '&');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $payfast_url);
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

    /**
     * Process successful payment
     */
    private function processPayment($data)
    {
        $paymentStatus = $data['payment_status'] ?? '';
        $paymentId = $data['m_payment_id'] ?? '';
        $payfastRef = $data['pf_payment_id'] ?? '';
        $amountReceived = (float)($data['amount'] ?? 0);

        $parts = explode('_', $paymentId);
        $orderId = (int)($parts[0] ?? 0);
        $userId = (int)($parts[1] ?? 0);

        if ($paymentStatus !== 'COMPLETE' || $orderId <= 0 || $userId <= 0) {
            return ['success' => false, 'message' => 'Invalid payment data'];
        }

        $this->db->begin_transaction();

        try {
            $orders = $this->getOrdersByPaymentId($paymentId, $userId);

            if (empty($orders)) {
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

                $this->orderRepo->updateOrderStatusDirect($order['order_id'], 'processing');

                // Create transaction record
                $transaction = $this->transactionRepo->createFromPayment(
                    $order['order_id'],
                    $payfastRef,
                    $order['total_price']
                );
                $transactions[] = $transaction;

                $this->decreaseOrderStock($order['order_id']);
            }

            // Log amount mismatch but don't fail
            if (abs($totalAmount - $amountReceived) >= 0.01) {
                error_log("PayFast amount mismatch for payment_id: $paymentId");
            }

            $this->cartRepo->clearUserCart($userId);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Payment processed',
                'transactions' => $transactions
            ];
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("PayFast processing error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function getOrdersByPaymentId($paymentId, $userId)
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

    private function getSingleOrder($orderId, $userId)
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

    private function decreaseOrderStock($orderId)
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
}
