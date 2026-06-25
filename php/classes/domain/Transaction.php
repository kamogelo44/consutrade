<?php

/**
 * ConsuTrade - Transaction
 *
 * Domain class representing a PayFast payment transaction for an order.
 *
 * @author Kamogelo Phale
 * @version 2.0.0
 */

class Transaction
{
    private $transactionId;
    private $orderId;
    private $payfastRef;
    private $amount;
    private $status;
    private $paidAt;

    public function __construct($data)
    {
        $this->transactionId = (int) ($data['transaction_id'] ?? 0);
        $this->orderId = (int) ($data['order_id'] ?? 0);
        $this->payfastRef = (string) ($data['payfast_ref'] ?? '');
        $this->amount = (float) ($data['amount'] ?? 0.00);
        $this->status = (string) ($data['status'] ?? 'pending');
        $this->paidAt = isset($data['paid_at']) ? (string) $data['paid_at'] : null;
    }

    public function getTransactionId()
    {
        return $this->transactionId;
    }

    public function getOrderId()
    {
        return $this->orderId;
    }

    public function getPayfastRef()
    {
        return $this->payfastRef;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getPaidAt()
    {
        return $this->paidAt;
    }

    public function isPaid()
    {
        return $this->status === 'completed';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isFailed()
    {
        return $this->status === 'failed';
    }

    public function markAsCompleted($payfastRef)
    {
        $this->status = 'completed';
        $this->payfastRef = $payfastRef;
        $this->paidAt = date('Y-m-d H:i:s');
    }

    public function markAsFailed()
    {
        $this->status = 'failed';
    }

    /**
     * Get formatted amount with currency symbol.
     *
     * @return string
     */
    public function getFormattedAmount()
    {
        return 'R ' . number_format($this->amount, 2);
    }

    /**
     * Get formatted paid date.
     *
     * @param string $format
     * @return string|null
     */
    public function getFormattedPaidAt($format = 'd M Y, H:i')
    {
        return $this->paidAt ? date($format, strtotime($this->paidAt)) : null;
    }
}
