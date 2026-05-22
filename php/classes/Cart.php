<?php
/**
 * ConsuTrade - Cart
 *
 * Domain class representing a single item in a user's shopping cart.
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

class Cart
{
    /** @var int */
    private $cartId;

    /** @var int */
    private $userId;

    /** @var int */
    private $productId;

    /** @var int */
    private $quantity;

    /** @var string */
    private $addedAt;

    /** @var float Price from the joined products table */
    private $price;

    /**
     * Constructor.
     *
     * @param array $data Associative array of cart data from the database
     */
    public function __construct(array $data)
    {
        $this->cartId    = (int) ($data['cart_id']    ?? 0);
        $this->userId    = (int) ($data['user_id']    ?? 0);
        $this->productId = (int) ($data['product_id'] ?? 0);
        $this->quantity  = (int) ($data['quantity']   ?? 1);
        $this->addedAt   = (string) ($data['added_at'] ?? '');
        $this->price     = (float) ($data['price']    ?? 0.00);
    }

    /**
     * Returns the cart item ID.
     *
     * @return int
     */
    public function getCartId(): int
    {
        return $this->cartId;
    }

    /**
     * Returns the line subtotal (price × quantity).
     *
     * @return float
     */
    public function getSubtotal(): float
    {
        return $this->price * $this->quantity;
    }

    /**
     * Returns the quantity.
     *
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }
}