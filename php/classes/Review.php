<?php
/**
 * ConsuTrade - Review
 *
 * Domain class representing a buyer's review of a seller.
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

class Review
{
    /** @var int */
    private $reviewId;

    /** @var int */
    private $buyerId;

    /** @var int */
    private $sellerId;

    /** @var int */
    private $orderId;

    /** @var int */
    private $rating;

    /** @var string */
    private $comment;

    /** @var string */
    private $createdAt;

    /**
     * Constructor.
     *
     * @param array $data Associative array of review data from the database
     */
    public function __construct(array $data)
    {
        $this->reviewId  = (int) ($data['review_id']  ?? 0);
        $this->buyerId   = (int) ($data['buyer_id']   ?? 0);
        $this->sellerId  = (int) ($data['seller_id']  ?? 0);
        $this->orderId   = (int) ($data['order_id']   ?? 0);
        $this->rating    = (int) ($data['rating']     ?? 0);
        $this->comment   = (string) ($data['comment']  ?? '');
        $this->createdAt = (string) ($data['created_at'] ?? '');
    }

    /**
     * Returns the review ID.
     *
     * @return int
     */
    public function getReviewId(): int
    {
        return $this->reviewId;
    }

    /**
     * Returns the rating (1 to 5).
     *
     * @return int
     */
    public function getRating(): int
    {
        return $this->rating;
    }

    /**
     * Returns the review comment.
     *
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment;
    }
}