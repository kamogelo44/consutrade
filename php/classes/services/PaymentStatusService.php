<?php

/**
 * ConsuTrade - PaymentStatusService
 * 
 * Handles payment status determination using domain classes.
 * 
 * @author Kamogelo Phale
 * @version 1.1.0
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
     * Get payment status result from PayFast redirect.
     */
    public function getStatus(string $paymentStatus, string $paymentId, int $orderId, int $userId): array
    {
        if ($orderId === 0 && !empty($paymentId)) {
            $order = $this->orderRepo->findByPaymentId($paymentId);
            if ($order) $orderId = (int)$order['order_id'];
        }

        if (empty($paymentStatus) && $orderId === 0) {
            return ['redirect' => true, 'url' => 'cart.php'];
        }

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
            return ['success' => false, 'message' => 'Unable to identify your order.'];
        }

        $order = $this->orderRepo->findById($orderId, $userId, 'buyer');
        if (!$order) {
            return ['success' => false, 'message' => 'Order not found.'];
        }

        $transaction = $this->transactionRepo->findByOrderId($orderId);

        if ($order['status'] === 'pending') {
            return [
                'success' => false,
                'message' => 'Payment received. Your order is being processed. Please check your orders page.'
            ];
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
            'message' => 'Payment was cancelled. You can try again from your cart.'
        ];
    }

    private function handleFailed(): array
    {
        return [
            'success' => false,
            'message' => 'Payment failed. Please check your payment method and try again.'
        ];
    }

    private function handleUnknown(string $paymentStatus, int $orderId, int $userId): array
    {
        if (!empty($paymentStatus)) {
            return [
                'success' => false,
                'message' => 'Payment status: ' . htmlspecialchars($paymentStatus)
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
            return ['success' => false, 'message' => 'Order not found.'];
        }

        $transaction = $this->transactionRepo->findByOrderId($orderId);

        if ($transaction && $transaction->isPaid()) {
            return ['success' => true, 'order' => $order, 'transaction' => $transaction];
        }

        if (in_array($order['status'], ['processing', 'completed', 'shipped'])) {
            return ['success' => true, 'order' => $order, 'transaction' => $transaction];
        }

        return [
            'success' => false,
            'message' => 'Your order is pending payment confirmation.'
        ];
    }
}
