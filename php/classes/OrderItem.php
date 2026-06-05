<?php

/**
 * ConsuTrade - OrderItem
 *
 * Domain class representing a single line item within an order.
 *
 * @author Kamogelo Phale
 * @version 2.0.0
 */

class OrderItem
{
    private $itemId;
    private $orderId;
    private $productId;
    private $quantity;
    private $price;

    /**
     * Constructor.
     *
     * @param array $data Associative array of order item data from the database
     */
    public function __construct($data)
    {
        $this->itemId    = (int) ($data['item_id']    ?? 0);
        $this->orderId   = (int) ($data['order_id']   ?? 0);
        $this->productId = (int) ($data['product_id'] ?? 0);
        $this->quantity  = (int) ($data['quantity']   ?? 1);
        $this->price     = (float) ($data['price']    ?? 0.00);
    }

    /**
     * Returns the item ID.
     *
     * @return int
     */
    public function getItemId()
    {
        return $this->itemId;
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
     * Returns the quantity ordered.
     *
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }
}
