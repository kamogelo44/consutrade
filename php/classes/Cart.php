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
    private $quantity;

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
        $this->quantity  = (int) ($data['quantity']   ?? 1);
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
