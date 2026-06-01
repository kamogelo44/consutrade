<?php

/**
 * ConsuTrade - Transaction
 *
 * Domain class representing a PayFast payment transaction for an order.
 *
 * @author     Kamogelo Phale
 * @module     ITECA3-12 Web Development and e-Commerce
 * @institution Eduvos
 * @version    2.0.0
 * @since      2026
 */

class Transaction
{
    private int $transactionId;
    private int $orderId;
    private string $payfastRef;
    private float $amount;
    private string $status;
    private ?string $paidAt;

    public function __construct(array $data)
    {
        $this->transactionId = (int) ($data['transaction_id'] ?? 0);
        $this->orderId = (int) ($data['order_id'] ?? 0);
        $this->payfastRef = (string) ($data['payfast_ref'] ?? '');
        $this->amount = (float) ($data['amount'] ?? 0.00);
        $this->status = (string) ($data['status'] ?? 'pending');
        $this->paidAt = isset($data['paid_at']) ? (string) $data['paid_at'] : null;
    }

    public function getTransactionId(): int
    {
        return $this->transactionId;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function getPayfastRef(): string
    {
        return $this->payfastRef;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getPaidAt(): ?string
    {
        return $this->paidAt;
    }

    public function isPaid(): bool
    {
        return $this->status === 'completed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function markAsCompleted(string $payfastRef): void
    {
        $this->status = 'completed';
        $this->payfastRef = $payfastRef;
        $this->paidAt = date('Y-m-d H:i:s');
    }

    public function markAsFailed(): void
    {
        $this->status = 'failed';
    }
}
