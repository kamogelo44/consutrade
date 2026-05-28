<?php
/**
 * ConsuTrade - ReviewRepository
 *
 * Handles all review database operations.
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

class ReviewRepository
{
    /** @var mysqli Database connection */
    private $db;

    /**
     * Constructor.
     *
     * @param mysqli $db Database connection
     */
    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    // ============================================================
    //  SUBMIT
    // ============================================================

    /**
     * Submit a review for a completed order.
     *
     * @param int    $orderId  Order ID
     * @param int    $sellerId Seller ID
     * @param int    $buyerId  Buyer ID
     * @param int    $rating   Rating (1-5)
     * @param string $comment  Review comment
     * @return array           ['success' => bool, 'message' => string]
     */
    public function submitReview(
        int $orderId,
        int $sellerId,
        int $buyerId,
        int $rating,
        string $comment
    ): array {
        // Verify the order is completed and belongs to this buyer
        $checkSql = "SELECT order_id FROM orders
                     WHERE order_id = ? AND buyer_id = ? AND seller_id = ? AND status = 'completed'";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->bind_param('iii', $orderId, $buyerId, $sellerId);
        $checkStmt->execute();

        if ($checkStmt->get_result()->num_rows === 0) {
            $checkStmt->close();
            return ['success' => false, 'message' => 'You can only review completed orders.'];
        }
        $checkStmt->close();

        // Check for duplicate review
        $dupSql = "SELECT review_id FROM reviews WHERE order_id = ? AND buyer_id = ?";
        $dupStmt = $this->db->prepare($dupSql);
        $dupStmt->bind_param('ii', $orderId, $buyerId);
        $dupStmt->execute();

        if ($dupStmt->get_result()->num_rows > 0) {
            $dupStmt->close();
            return ['success' => false, 'message' => 'You have already reviewed this order.'];
        }
        $dupStmt->close();

        // Insert the review
        $insertSql = "INSERT INTO reviews (order_id, seller_id, buyer_id, rating, comment, created_at)
                      VALUES (?, ?, ?, ?, ?, NOW())";
        $insertStmt = $this->db->prepare($insertSql);
        $insertStmt->bind_param('iiiis', $orderId, $sellerId, $buyerId, $rating, $comment);

        if ($insertStmt->execute()) {
            $insertStmt->close();
            return ['success' => true, 'message' => 'Thank you for your review.'];
        }

        $insertStmt->close();
        return ['success' => false, 'message' => 'Could not submit review.'];
    }

    // ============================================================
    //  CHECK & UPDATE
    // ============================================================

    /**
     * Check if a review already exists for an order.
     *
     * @param int $orderId Order ID
     * @param int $buyerId Buyer ID
     * @return array|null Returns review data if exists, null otherwise
     */
    public function getReviewByOrderAndBuyer(int $orderId, int $buyerId): ?array
    {
        $sql = "SELECT review_id, rating, comment, created_at FROM reviews WHERE order_id = ? AND buyer_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $orderId, $buyerId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return [
                'review_id' => (int) $row['review_id'],
                'rating' => (int) $row['rating'],
                'comment' => $row['comment'],
                'created_at' => $row['created_at']
            ];
        }
        
        $stmt->close();
        return null;
    }

    /**
     * Update an existing review.
     *
     * @param int    $orderId Order ID
     * @param int    $buyerId Buyer ID
     * @param int    $rating  New rating (1-5)
     * @param string $comment New comment
     * @return array          ['success' => bool, 'message' => string]
     */
    public function updateReview(int $orderId, int $buyerId, int $rating, string $comment): array
    {
        // Check if review exists
        $existing = $this->getReviewByOrderAndBuyer($orderId, $buyerId);
        if (!$existing) {
            return ['success' => false, 'message' => 'Review not found'];
        }
        
        // Update the review
        $sql = "UPDATE reviews SET rating = ?, comment = ?, updated_at = NOW() WHERE order_id = ? AND buyer_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('isii', $rating, $comment, $orderId, $buyerId);
        
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Review updated successfully'];
        }
        
        $stmt->close();
        return ['success' => false, 'message' => 'Failed to update review'];
    }


    // ============================================================
    //  RETRIEVE
    // ============================================================

    /**
     * Get reviews for a seller with buyer details.
     *
     * @param int $sellerId Seller ID
     * @return array
     */
    public function getSellerReviews(int $sellerId): array
    {
        $sql = "SELECT r.review_id, r.rating, r.comment, r.created_at,
                       u.full_name as buyer_name, u.user_id as buyer_id,
                       r.order_id
                FROM reviews r
                JOIN users u ON r.buyer_id = u.user_id
                WHERE r.seller_id = ?
                ORDER BY r.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $sellerId);
        $stmt->execute();
        $result = $stmt->get_result();

        $reviews = [];
        while ($row = $result->fetch_assoc()) {
            $reviews[] = [
                'review_id'   => (int) $row['review_id'],
                'rating'      => (int) $row['rating'],
                'comment'     => $row['comment'],
                'created_at'  => $row['created_at'],
                'buyer_name'  => $row['buyer_name'],
                'buyer_id'    => (int) $row['buyer_id'],
                'order_id'    => (int) $row['order_id'],
            ];
        }
        $stmt->close();

        return $reviews;
    }

    /**
     * Get the average rating and review count for a seller.
     *
     * @param int $sellerId Seller ID
     * @return array         ['avg_rating' => float, 'review_count' => int]
     */
    public function getSellerRating(int $sellerId): array
    {
        $sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as review_count
                FROM reviews
                WHERE seller_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $sellerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return [
            'avg_rating'   => round((float) ($row['avg_rating'] ?? 0), 1),
            'review_count' => (int) ($row['review_count'] ?? 0)
        ];
    }
    
    /**
     * Count reviews written by a buyer.
     *
     * @param int $buyerId Buyer ID
     * @return int
     */
    public function countBuyerReviews(int $buyerId): int
    {
        $sql = "SELECT COUNT(*) as total FROM reviews WHERE buyer_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $buyerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = (int)($result->fetch_assoc()['total'] ?? 0);
        $stmt->close();
        return $count;
    }
}