<?php

/**
 * ConsuTrade - PaymentStatusService
 * 
 * Handles payment status determination using domain classes.
 * 
 * @author Kamogelo Phale
 * @version 1.0.0
 */

class PaymentStatusService
{
    private OrderRepository $orderRepo;
    private TransactionRepository $transactionRepo;

    public function __construct(
        OrderRepository $orderRepo,
        TransactionRepository $transactionRepo
    ) {
        $this->orderRepo = $orderRepo;
        $this->transactionRepo = $transactionRepo;
    }

    /**
     * Get payment status result from PayFast redirect
     */
    public function getStatus(string $paymentStatus, string $paymentId, int $orderId, int $userId): array
    {
        // Extract order_id from m_payment_id if needed
        if ($orderId === 0 && !empty($paymentId)) {
            $parts = explode('_', $paymentId);
            $orderId = (int)($parts[0] ?? 0);
        }

        // No payment data - redirect to cart
        if (empty($paymentStatus) && $orderId === 0) {
            return ['redirect' => true, 'url' => 'cart.php'];
        }

        // Handle based on payment status
        switch ($paymentStatus) {
            case 'COMPLETE':
                return $this->handleComplete($orderId, $userId);
            case 'CANCELLED':
                return $this->handleCancelled();
            case 'FAILED':
                return $this->handleFailed();
            default:
                return $this->handleUnknown($paymentStatus, $orderId, $userId);
        }
    }

    private function handleComplete(int $orderId, int $userId): array
    {
        if ($orderId <= 0) {
            return [
                'success' => false,
                'message' => 'Unable to identify your order.'
            ];
        }

        $order = $this->orderRepo->findById($orderId, $userId, 'buyer');
        if (!$order) {
            return [
                'success' => false,
                'message' => 'Order not found. Please contact support.'
            ];
        }

        $transaction = $this->transactionRepo->findByOrderId($orderId);

        // If order is still pending, ITN might be delayed
        if ($order['status'] === 'pending') {
            sleep(1);
            $transaction = $this->transactionRepo->findByOrderId($orderId);
            $order = $this->orderRepo->findById($orderId, $userId, 'buyer');

            if ($order['status'] === 'pending') {
                return [
                    'success' => false,
                    'message' => 'Payment received but confirmation is pending. Please check your email.'
                ];
            }
        }

        return [
            'success' => true,
            'order' => $order,
            'transaction' => $transaction
        ];
    }

    private function handleCancelled(): array
    {
        return [
            'success' => false,
            'message' => 'Your payment was cancelled. You can try again from your cart.'
        ];
    }

    private function handleFailed(): array
    {
        return [
            'success' => false,
            'message' => 'Your payment failed. Please check your payment method and try again.'
        ];
    }

    private function handleUnknown(string $paymentStatus, int $orderId, int $userId): array
    {
        if (!empty($paymentStatus)) {
            return [
                'success' => false,
                'message' => 'Payment status: ' . htmlspecialchars($paymentStatus) . '. Please contact support.'
            ];
        }

        if ($orderId > 0) {
            return $this->checkExistingOrder($orderId, $userId);
        }

        return ['redirect' => true, 'url' => 'cart.php'];
    }

    private function checkExistingOrder(int $orderId, int $userId): array
    {
        $order = $this->orderRepo->findById($orderId, $userId, 'buyer');
        if (!$order) {
            return [
                'success' => false,
                'message' => 'Order not found. Please check your order history.'
            ];
        }

        $transaction = $this->transactionRepo->findByOrderId($orderId);

        // Use domain class methods for status checking
        if ($transaction && $transaction->isPaid()) {
            return [
                'success' => true,
                'order' => $order,
                'transaction' => $transaction
            ];
        }

        if (in_array($order['status'], ['processing', 'completed', 'shipped'])) {
            return [
                'success' => true,
                'order' => $order,
                'transaction' => $transaction
            ];
        }

        return [
            'success' => false,
            'message' => 'Your order is pending payment confirmation. Please wait a moment...'
        ];
    }
}
