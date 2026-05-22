<?php
/**
 * ConsuTrade - Order
 *
 * Domain class representing a single order with its line items.
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

class Order
{
    /** @var int */
    private $orderId;

    /** @var int */
    private $buyerId;

    /** @var int */
    private $sellerId;

    /** @var float */
    private $totalPrice;

    /** @var string */
    private $status;

    /** @var string */
    private $paymentId;

    /** @var string */
    private $createdAt;

    /** @var OrderItem[] */
    private $items;

    /**
     * Constructor.
     *
     * @param array $data  Associative array of order data from the database
     * @param array $items Array of OrderItem objects (optional)
     */
    public function __construct(array $data, array $items = [])
    {
        $this->orderId    = (int) ($data['order_id']    ?? 0);
        $this->buyerId    = (int) ($data['buyer_id']    ?? 0);
        $this->sellerId   = (int) ($data['seller_id']   ?? 0);
        $this->totalPrice = (float) ($data['total_price'] ?? 0.00);
        $this->status     = (string) ($data['status']    ?? 'pending');
        $this->paymentId  = (string) ($data['payment_id'] ?? '');
        $this->createdAt  = (string) ($data['created_at'] ?? '');
        $this->items      = $items;
    }

    /**
     * Returns the order ID.
     *
     * @return int
     */
    public function getOrderId(): int
    {
        return $this->orderId;
    }

    /**
     * Returns the order status.
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Returns the total price.
     *
     * @return float
     */
    public function getTotalPrice(): float
    {
        return $this->totalPrice;
    }

    /**
     * Returns the order items.
     *
     * @return OrderItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    // ============================================================
    //  HELPER METHODS (business logic)
    // ============================================================

    /**
     * Checks whether the order can be cancelled.
     * Only pending orders are cancellable.
     *
     * @return bool
     */
    public function isCancellable(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Checks whether the order is in a final state.
     *
     * @return bool
     */
    public function isFinal(): bool
    {
        return in_array($this->status, ['completed', 'cancelled'], true);
    }

    /**
     * Returns the number of items in this order.
     *
     * @return int
     */
    public function getItemCount(): int
    {
        $count = 0;
        foreach ($this->items as $item) {
            $count += $item->getQuantity();
        }
        return $count;
    }
}