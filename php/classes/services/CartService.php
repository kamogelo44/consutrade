<?php

/**
 * ConsuTrade - CartService
 *
 * Handles cart business logic including calculations, validation,
 * and checkout orchestration.
 *
 * @author Kamogelo Phale
 * @version 2.0.0
 */

class CartService
{
    private mysqli $db;
    private ProductRepository $productRepo;
    private OrderRepository $orderRepo;
    private TransactionRepository $transactionRepo;

    public function __construct(
        mysqli $db,
        ProductRepository $productRepo,
        OrderRepository $orderRepo,
        TransactionRepository $transactionRepo
    ) {
        $this->db = $db;
        $this->productRepo = $productRepo;
        $this->orderRepo = $orderRepo;
        $this->transactionRepo = $transactionRepo;
    }

    /**
     * Calculate cart totals including delivery fee.
     * Free delivery over R500, R75 otherwise.
     */
    public function calculateTotals(array $items): array
    {
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        $deliveryFee = ($subtotal > 0 && $subtotal < 500) ? 75 : 0;

        return [
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'total' => $subtotal + $deliveryFee
        ];
    }

    /**
     * Verify all cart items have sufficient stock.
     */
    public function verifyStock(array $items): array
    {
        $errors = [];
        foreach ($items as $item) {
            $currentStock = $this->productRepo->getStock($item['product_id']);
            if ($currentStock < $item['quantity']) {
                $errors[] = $item['title'] . ': Only ' . $currentStock
                    . ' available (you have ' . $item['quantity'] . ' in cart)';
            }
        }
        return $errors;
    }

    /**
     * Process the full checkout flow within a transaction.
     */
    public function processCheckout(int $userId, array $cartItems): array
    {
        $stockErrors = $this->verifyStock($cartItems);
        if (!empty($stockErrors)) {
            return ['success' => false, 'errors' => $stockErrors];
        }

        $sellerIds = array_unique(array_column($cartItems, 'seller_id'));

        $this->db->begin_transaction();

        try {
            $orderResult = $this->orderRepo->createFromCart($userId, $cartItems, $sellerIds);
            if (!$orderResult) {
                throw new Exception('Failed to create orders');
            }

            foreach ($cartItems as $item) {
                if (!$this->productRepo->decreaseStock($item['product_id'], $item['quantity'])) {
                    throw new Exception("Failed to reserve stock for product: {$item['product_id']}");
                }
            }

            $primaryOrderId = $orderResult['order_ids'][0];
            $order = $this->orderRepo->findById($primaryOrderId, $userId, 'buyer');
            $total = $order['total_price'] ?? 0;

            // No idempotency key here — PayFast will provide it on ITN callback
            $this->transactionRepo->createFromPayment(
                $primaryOrderId,
                $orderResult['payment_id'],
                $total,
                null
            );

            $this->db->commit();

            return [
                'success' => true,
                'order_ids' => $orderResult['order_ids'],
                'payment_id' => $orderResult['payment_id'],
                'primary_order_id' => $primaryOrderId
            ];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'errors' => ['Could not complete checkout.']];
        }
    }

    /**
     * Prepare PayFast payment form data.
     */
    public function preparePayFastData(array $orderInfo, string $baseUrl): array
    {
        return [
            'merchant_id' => PAYFAST_MERCHANT_ID,
            'merchant_key' => PAYFAST_MERCHANT_KEY,
            'return_url' => rtrim($baseUrl, '/') . '/order-confirmation.php',
            'cancel_url' => rtrim($baseUrl, '/') . '/cart.php',
            'notify_url' => rtrim($baseUrl, '/') . '/php/endpoints/checkout/payfast-notify.php',
            'm_payment_id' => $orderInfo['payment_id'],
            'amount' => number_format($orderInfo['total'], 2, '.', ''),
            'item_name' => 'ConsuTrade Order #' . ($orderInfo['primary_order_id'] ?? ''),
            'item_description' => 'Order from ConsuTrade',
            'name_first' => $orderInfo['buyer_name'],
            'email_address' => $orderInfo['buyer_email']
        ];
    }
}
