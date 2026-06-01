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
    private int $orderId;
    private int $buyerId;
    private int $sellerId;
    private float $totalPrice;
    private string $status;
    private string $paymentId;
    private string $createdAt;
    private array $items;

    /** @var array<string, string[]> Valid status transitions */
    private const VALID_TRANSITIONS = [
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
     * @param array $items Order items (OrderItem objects or arrays)
     */
    public function __construct(array $data, array $items = [])
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

    public function getOrderId(): int
    {
        return $this->orderId;
    }
    public function getBuyerId(): int
    {
        return $this->buyerId;
    }
    public function getSellerId(): int
    {
        return $this->sellerId;
    }
    public function getTotalPrice(): float
    {
        return $this->totalPrice;
    }
    public function getStatus(): string
    {
        return $this->status;
    }
    public function getPaymentId(): string
    {
        return $this->paymentId;
    }
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /** @return array<OrderItem|array> */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Checks if buyer can cancel this order
     * Business rule: Only pending orders can be cancelled by buyer
     *
     * @return bool
     */
    public function canBeCancelledByBuyer(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Checks if order can transition to new status
     *
     * @param string $newStatus Target status
     * @return bool
     */
    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::VALID_TRANSITIONS[$this->status] ?? []);
    }

    /**
     * Returns array of allowed next statuses
     *
     * @return string[]
     */
    public function getAllowedNextStatuses(): array
    {
        return self::VALID_TRANSITIONS[$this->status] ?? [];
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Calculates total quantity of items in this order
     *
     * @return int
     */
    public function getItemCount(): int
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
