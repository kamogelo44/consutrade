<?php

/**
 * ConsuTrade - Cart
 *
 * Domain class representing a single item in a user's shopping cart.
 *
 * @author Kamogelo Phale
 * @version 2.0.0
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
    public function __construct($data)
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
    public function getCartId()
    {
        return $this->cartId;
    }

    /**
     * Returns the line subtotal (price × quantity).
     *
     * @return float
     */
    public function getSubtotal()
    {
        return $this->price * $this->quantity;
    }

    /**
     * Returns the quantity.
     *
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }
}
