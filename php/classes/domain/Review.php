<?php

/**
 * ConsuTrade - Review
 *
 * Domain class representing a buyer's review of a seller.
 *
 * @author Kamogelo Phale
 * @version 2.0.0
 */

class Review
{
    private $reviewId;
    private $buyerId;
    private $sellerId;
    private $orderId;
    private $rating;
    private $comment;
    private $createdAt;

    /**
     * Constructor.
     *
     * @param array $data Associative array of review data from the database
     */
    public function __construct($data)
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
    public function getReviewId()
    {
        return $this->reviewId;
    }

    /**
     * Returns the buyer ID.
     *
     * @return int
     */
    public function getBuyerId()
    {
        return $this->buyerId;
    }

    /**
     * Returns the seller ID.
     *
     * @return int
     */
    public function getSellerId()
    {
        return $this->sellerId;
    }

    /**
     * Returns the order ID.
     *
     * @return int
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * Returns the rating (1 to 5).
     *
     * @return int
     */
    public function getRating()
    {
        return $this->rating;
    }

    /**
     * Returns the review comment.
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Returns the creation timestamp.
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Returns formatted creation date.
     *
     * @param string $format
     * @return string
     */
    public function getFormattedCreatedAt($format = 'd M Y, H:i')
    {
        return date($format, strtotime($this->createdAt));
    }

    /**
     * Returns star rating as HTML.
     *
     * @return string
     */
    public function getStarRatingHtml()
    {
        $html = '';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $this->rating) {
                $html .= '<span class="star filled">★</span>';
            } else {
                $html .= '<span class="star">☆</span>';
            }
        }
        return $html;
    }

    /**
     * Returns rating as a percentage (for star width CSS).
     *
     * @return float
     */
    public function getRatingPercentage()
    {
        return ($this->rating / 5) * 100;
    }
}
