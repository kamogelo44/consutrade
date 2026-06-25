<?php

/**
 * ConsuTrade - CartService
 *
 * Handles cart business logic including calculations, validation,
 * and checkout orchestration.
 *
 * @author Kamogelo Phale
 * @version 1.0.0
 */

class CartService
{
    private CartRepository $cartRepo;
    private ProductRepository $productRepo;
    private OrderRepository $orderRepo;
    private TransactionRepository $transactionRepo;
    private mysqli $db;

    public function __construct(
        mysqli $db,
        CartRepository $cartRepo,
        ProductRepository $productRepo,
        OrderRepository $orderRepo,
        TransactionRepository $transactionRepo
    ) {
        $this->db = $db;
        $this->cartRepo = $cartRepo;
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
        $total = $subtotal + $deliveryFee;

        return [
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'total' => $total
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
            // Create orders
            $orderResult = $this->orderRepo->createFromCart($userId, $cartItems, $sellerIds);
            if (!$orderResult) {
                throw new Exception('Failed to create orders');
            }

            // Decrease stock (reserve inventory)
            foreach ($cartItems as $item) {
                $decreased = $this->productRepo->decreaseStock($item['product_id'], $item['quantity']);
                if (!$decreased) {
                    throw new Exception("Failed to reserve stock for product: {$item['product_id']}");
                }
            }

            // Create pending transaction
            $primaryOrderId = $orderResult['order_ids'][0];
            $order = $this->orderRepo->findById($primaryOrderId, $userId, 'buyer');
            $total = $order['total_price'] ?? 0;
            $paymentId = $orderResult['payment_id'];

            $this->transactionRepo->createFromPayment(
                $primaryOrderId,
                'PF-PENDING-' . $paymentId,
                $total
            );

            $this->db->commit();

            return [
                'success' => true,
                'order_ids' => $orderResult['order_ids'],
                'payment_id' => $orderResult['payment_id'],
                'primary_order_id' => $orderResult['order_ids'][0]
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
            'notify_url' => rtrim($baseUrl, '/') . '/php/endpoints/payfast-notify.php',
            'm_payment_id' => $orderInfo['payment_id'],
            'amount' => number_format($orderInfo['total'], 2, '.', ''),
            'item_name' => 'ConsuTrade Order #' . ($orderInfo['primary_order_id'] ?? ''),
            'item_description' => 'Order from ConsuTrade',
            'name_first' => $orderInfo['buyer_name'],
            'email_address' => $orderInfo['buyer_email']
        ];
    }
}
