<?php

/**
 * ConsuTrade - Order
 *
 * Domain class representing a single order with its line items.
 *
 * @author Kamogelo Phale
 * @version 2.0.0
 */
class Order
{
    private $orderId;
    private $buyerId;
    private $sellerId;
    private $totalPrice;
    private $status;
    private $paymentId;
    private $createdAt;
    private $items;

    // Valid status transitions
    private $validTransitions = [
        'pending' => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => []
    ];

    /**
     * Order constructor.
     *
     * @param array $data Order data from database
     * @param array $items Order items
     */
    public function __construct($data, $items = [])
    {
        $this->orderId = (int) ($data['order_id'] ?? 0);
        $this->buyerId = (int) ($data['buyer_id'] ?? 0);
        $this->sellerId = (int) ($data['seller_id'] ?? 0);
        $this->totalPrice = (float) ($data['total_price'] ?? 0.00);
        $this->status = (string) ($data['status'] ?? 'pending');
        $this->paymentId = (string) ($data['payment_id'] ?? '');
        $this->createdAt = (string) ($data['created_at'] ?? '');
        $this->items = $items;
    }

    public function getOrderId()
    {
        return $this->orderId;
    }

    public function getBuyerId()
    {
        return $this->buyerId;
    }

    public function getSellerId()
    {
        return $this->sellerId;
    }

    public function getTotalPrice()
    {
        return $this->totalPrice;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getPaymentId()
    {
        return $this->paymentId;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getItems()
    {
        return $this->items;
    }

    /**
     * Checks if buyer can cancel this order
     *
     * @return bool
     */
    public function canBeCancelledByBuyer()
    {
        return $this->status === 'pending';
    }

    /**
     * Checks if order can transition to new status
     *
     * @param string $newStatus Target status
     * @return bool
     */
    public function canTransitionTo($newStatus)
    {
        return in_array($newStatus, $this->validTransitions[$this->status] ?? []);
    }

    /**
     * Returns array of allowed next statuses
     *
     * @return array
     */
    public function getAllowedNextStatuses()
    {
        return $this->validTransitions[$this->status] ?? [];
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Calculates total quantity of items in this order
     *
     * @return int
     */
    public function getItemCount()
    {
        $count = 0;
        foreach ($this->items as $item) {
            if ($item instanceof OrderItem) {
                $count += $item->getQuantity();
            } elseif (is_array($item)) {
                $count += $item['quantity'] ?? 1;
            }
        }
        return $count;
    }
}
