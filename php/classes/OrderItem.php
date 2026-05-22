<?php
/**
 * ConsuTrade - OrderItem
 *
 * Domain class representing a single line item within an order.
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

class OrderItem
{
    /** @var int */
    private $itemId;

    /** @var int */
    private $orderId;

    /** @var int */
    private $productId;

    /** @var int */
    private $quantity;

    /** @var float */
    private $price;

    /**
     * Constructor.
     *
     * @param array $data Associative array of order item data from the database
     */
    public function __construct(array $data)
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
    public function getItemId(): int
    {
        return $this->itemId;
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
     * Returns the quantity ordered.
     *
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }
}