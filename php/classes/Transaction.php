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

class Transaction
{
    /** @var int */
    private $transactionId;

    /** @var int */
    private $orderId;

    /** @var string */
    private $payfastRef;

    /** @var float */
    private $amount;

    /** @var string */
    private $status;

    /** @var string */
    private $paidAt;

    /**
     * Constructor.
     *
     * @param array $data Associative array of transaction data from the database
     */
    public function __construct(array $data)
    {
        $this->transactionId = (int) ($data['transaction_id'] ?? 0);
        $this->orderId       = (int) ($data['order_id']       ?? 0);
        $this->payfastRef    = (string) ($data['payfast_ref']  ?? '');
        $this->amount        = (float) ($data['amount']        ?? 0.00);
        $this->status        = (string) ($data['status']       ?? 'pending');
        $this->paidAt        = (string) ($data['paid_at']      ?? '');
    }

    /**
     * Returns the transaction ID.
     *
     * @return int
     */
    public function getTransactionId(): int
    {
        return $this->transactionId;
    }

    /**
     * Returns the transaction status.
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Checks whether the payment has been completed.
     *
     * @return bool
     */
    public function isPaid(): bool
    {
        return $this->status === 'completed';
    }
}