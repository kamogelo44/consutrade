<?php

/**
 * ConsuTrade - CartRepository
 *
 * Handles all cart, checkout, and PayFast payment preparation operations.
 *
 * @author     Kamogelo Phale
 * @module     ITECA3-12 Web Development and e-Commerce
 * @institution Eduvos
 * @version    2.0.0
 * @since      2026
 *
 * References:
 * - Pressman, R.S. and Maxim, B.R., 2015. Software Engineering:
 *   A Practitioner's Approach. 8th ed. McGraw-Hill.
 * - Dennis, A., Wixom, B.H. and Tegarden, D., 2015. Systems Analysis
 *   and Design: An Object-Oriented Approach with UML. 6th ed.
 *   John Wiley and Sons.
 * - PHP Group, 2025. Classes and Objects. Available at:
 *   https://www.php.net/manual/en/language.oop5.php
 * - PHP-FIG, 2023. PSR-12: Extended Coding Style. Available at:
 *   https://www.php.fig.org/psr/psr-12/
 */

class CartRepository
{
    /** @var mysqli Database connection */
    private $db;

    /**
     * Constructor.
     *
     * @param mysqli $db Database connection
     */
    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    // ============================================================
    //  CART ITEMS
    // ============================================================

    /**
     * Add item to cart.
     *
     * @param int $userId    User ID
     * @param int $productId Product ID
     * @param int $quantity  Quantity to add
     * @return bool
     */
    public function addItem(int $userId, int $productId, int $quantity): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)"
        );
        $stmt->bind_param('iii', $userId, $productId, $quantity);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Get a specific cart item by user and product.
     *
     * @param int $userId    User ID
     * @param int $productId Product ID
     * @return array|null
     */
    public function getCartItemByProduct(int $userId, int $productId): ?array
    {
        $sql = "SELECT cart_id, quantity FROM cart WHERE user_id = ? AND product_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $userId, $productId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return $row;
        }

        $stmt->close();
        return null;
    }

    /**
     * Get cart items for a user with product and seller details.
     *
     * @param int $userId User ID
     * @return array       Cart items with product details, seller name, and subtotal
     */
    public function getCartItems(int $userId): array
    {
        $sql = "SELECT c.cart_id, c.quantity, c.added_at,
            p.product_id, p.title, p.price, p.image_url, p.seller_id, p.stock_quantity,
            u.full_name as seller_name, u.id_verified as is_verified
            FROM cart c
            JOIN products p ON c.product_id = p.product_id
            JOIN users u ON p.seller_id = u.user_id
            WHERE c.user_id = ?
            ORDER BY c.added_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        // create ProductRepository once outside the loop
        $productRepo = new ProductRepository($this->db);
        $items = [];

        while ($row = $result->fetch_assoc()) {
            $row['subtotal']  = $row['price'] * $row['quantity'];
            // use the existing repo instance
            $row['image_url'] = $productRepo->getImageUrl($row['image_url']);
            $row['is_verified'] = (bool)($row['is_verified'] ?? false);
            $items[] = $row;
        }
        $stmt->close();

        return $items;
    }

    /**
     * Calculate cart totals including delivery fee.
     * Free delivery over R500, R50 otherwise.
     *
     * @param array $items Array of cart items
     * @return array       ['subtotal' => float, 'delivery_fee' => float, 'total' => float]
     */
    public function calculateCartTotals(array $items): array
    {
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        $deliveryFee = ($subtotal > 0 && $subtotal < 500) ? 50 : 0;
        $total       = $subtotal + $deliveryFee;

        return [
            'subtotal'     => $subtotal,
            'delivery_fee' => $deliveryFee,
            'total'        => $total
        ];
    }

    /**
     * Update cart item quantity.
     *
     * @param int $cartId Cart item ID
     * @param int $userId User ID (for verification)
     * @param int $qty    New quantity
     * @return bool
     */
    public function updateCartQuantity(int $cartId, int $userId, int $qty): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE cart SET quantity = ? WHERE cart_id = ? AND user_id = ?"
        );
        $stmt->bind_param('iii', $qty, $cartId, $userId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Remove item from cart by product ID.
     *
     * @param int $productId Product ID
     * @param int $userId    User ID (for verification)
     * @return bool
     */
    public function removeCartItemByProductId(int $productId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM cart WHERE product_id = ? AND user_id = ?"
        );
        $stmt->bind_param('ii', $productId, $userId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Get total number of items in cart (sum of quantities).
     *
     * @param int $userId User ID
     * @return int
     */
    public function getCartCount(int $userId): int
    {
        $stmt = $this->db->prepare(
            "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?"
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $count = (int) ($row['total'] ?? 0);
        $stmt->close();
        return $count;
    }

    /**
     * Clear all items from a user's cart.
     *
     * @param int $userId User ID
     * @return bool
     */
    public function clearUserCart(int $userId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->bind_param('i', $userId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // ============================================================
    //  CHECKOUT
    // ============================================================

    /**
     * Verify all cart items have sufficient stock before checkout.
     *
     * @param array $items Array of cart items (must include product_id, title, quantity)
     * @return array       Array of error messages (empty if all stock is sufficient)
     */
    public function verifyCartStock(array $items): array
    {
        $errors = [];
        $productRepo = new ProductRepository($this->db);

        foreach ($items as $item) {
            $currentStock = $productRepo->getProductStock($item['product_id']);

            if ($currentStock < $item['quantity']) {
                $errors[] = $item['title'] . ': Only ' . $currentStock
                    . ' available (you have ' . $item['quantity'] . ' in cart)';
            }
        }

        return $errors;
    }

    /**
     * Process the full checkout flow within a transaction.
     * Verifies stock, creates orders, clears cart.
     *
     * @param int   $userId    The buyer's user ID
     * @param array $cartItems Array of cart items
     * @return array           ['success' => bool, 'errors' => array, 'order_ids' => array,
     *                          'payment_id' => string, 'primary_order_id' => int]
     */
    public function processCheckout(int $userId, array $cartItems): array
    {
        // Verify stock before starting
        $stockErrors = $this->verifyCartStock($cartItems);
        if (!empty($stockErrors)) {
            return ['success' => false, 'errors' => $stockErrors];
        }

        // Get unique seller IDs
        $sellerIds = array_unique(array_column($cartItems, 'seller_id'));

        $this->db->begin_transaction();

        try {
            $orderRepo    = new OrderRepository($this->db);
            $orderResult  = $orderRepo->createOrdersFromCart($userId, $cartItems, $sellerIds);

            if (!$orderResult) {
                throw new Exception('Failed to create orders');
            }

            //$this->clearUserCart($userId);

            $this->db->commit();

            return [
                'success'          => true,
                'order_ids'        => $orderResult['order_ids'],
                'payment_id'       => $orderResult['payment_id'],
                'primary_order_id' => $orderResult['order_ids'][0]
            ];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'errors' => ['Could not complete checkout.']];
        }
    }

    // ============================================================
    //  PAYFAST
    // ============================================================

    /**
     * Prepare PayFast payment form data.
     *
     * @param array  $orderInfo Contains order_ids, payment_id, total, buyer_name, buyer_email
     * @param string $baseUrl   Base URL of the site
     * @return array            PayFast form fields
     */
    public function preparePayFastData(array $orderInfo, string $baseUrl): array
    {
        return [
            'merchant_id'      => PAYFAST_MERCHANT_ID,
            'merchant_key'     => PAYFAST_MERCHANT_KEY,
            'return_url'       => $this->getAbsoluteUrl('order-confirmation.php'),
            'cancel_url'       => $this->getAbsoluteUrl('cart.php'),
            'notify_url'       => $this->getAbsoluteUrl('php/endpoints/payfast-notify.php'),
            'm_payment_id'     => $orderInfo['payment_id'],
            'amount'           => number_format($orderInfo['total'], 2, '.', ''),
            'item_name'        => 'ConsuTrade Order #' . $orderInfo['primary_order_id'],
            'item_description' => 'Order from ConsuTrade',
            'name_first'       => $orderInfo['buyer_name'],
            'email_address'    => $orderInfo['buyer_email']
        ];
    }

    // ============================================================
    //  USER INFO
    // ============================================================

    /**
     * Get user checkout info (name, email, phone).
     *
     * @param int $userId User ID
     * @return array|null
     */
    public function getUserCheckoutInfo(int $userId): ?array
    {
        $sql = "SELECT full_name, email, phone FROM users WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user;
    }

    // ============================================================
    //  PRIVATE HELPERS
    // ============================================================

    /**
     * Get full absolute URL for PayFast callbacks.
     *
     * @param string $path Relative path
     * @return string      Full absolute URL
     */
    private function getAbsoluteUrl(string $path): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host     = $_SERVER['HTTP_HOST'];
        return $protocol . '://' . $host . '/www/consutrade/' . ltrim($path, '/');
    }
}
