<?php

/**
 * ConsuTrade - ReviewRepository
 *
 * Handles all review database operations.
 *
 * @author Kamogelo Phale
 * @version 2.0.0
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
    public function __construct($db)
    {
        $this->db = $db;
    }

    // ============================================================
    // CREATE
    // ============================================================

    /**
     * Submit a review for a completed order.
     *
     * @param int $orderId Order ID
     * @param int $sellerId Seller ID
     * @param int $buyerId Buyer ID
     * @param int $rating Rating (1-5)
     * @param string $comment Review comment
     * @return array
     */
    public function create(int $orderId, int $sellerId, int $buyerId, int $rating, string $comment): array
    {
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

        $dupSql = "SELECT review_id FROM reviews WHERE order_id = ? AND buyer_id = ?";
        $dupStmt = $this->db->prepare($dupSql);
        $dupStmt->bind_param('ii', $orderId, $buyerId);
        $dupStmt->execute();

        if ($dupStmt->get_result()->num_rows > 0) {
            $dupStmt->close();
            return ['success' => false, 'message' => 'You have already reviewed this order.'];
        }
        $dupStmt->close();

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
    // READ
    // ============================================================

    /**
     * Get a review by order ID and buyer ID.
     *
     * @param int $orderId Order ID
     * @param int $buyerId Buyer ID
     * @return array|null
     */
    public function findByOrderAndBuyer(int $orderId, int $buyerId): ?array
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
     * Get reviews for a seller with buyer details.
     *
     * @param int $sellerId Seller ID
     * @return array
     */
    public function findBySeller(int $sellerId): array
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
     * Get recent reviews for a seller (limited).
     *
     * @param int $sellerId Seller ID
     * @param int $limit Maximum number of reviews
     * @return array
     */
    public function findRecentBySeller(int $sellerId, int $limit = 5): array
    {
        $sql = "SELECT r.review_id, r.rating, r.comment, r.created_at,
                       u.full_name as buyer_name
                FROM reviews r
                JOIN users u ON r.buyer_id = u.user_id
                WHERE r.seller_id = ?
                ORDER BY r.created_at DESC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $sellerId, $limit);
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
            ];
        }
        $stmt->close();

        return $reviews;
    }

    /**
     * Get all reviews for admin (across all sellers).
     *
     * @param int $limit Results per page
     * @param int $offset Pagination offset
     * @return array
     */
    public function findAll(int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT r.review_id, r.rating, r.comment, r.created_at,
                       buyer.full_name as buyer_name,
                       seller.full_name as seller_name,
                       p.title as product_title
                FROM reviews r
                JOIN users buyer ON r.buyer_id = buyer.user_id
                JOIN users seller ON r.seller_id = seller.user_id
                LEFT JOIN orders o ON r.order_id = o.order_id
                LEFT JOIN order_items oi ON o.order_id = oi.order_id
                LEFT JOIN products p ON oi.product_id = p.product_id
                GROUP BY r.review_id
                ORDER BY r.created_at DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $limit, $offset);
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
                'seller_name' => $row['seller_name'],
                'product_title' => $row['product_title'] ?? 'Unknown Product'
            ];
        }
        $stmt->close();

        return $reviews;
    }

    /**
     * Get the average rating and review count for a seller.
     *
     * @param int $sellerId Seller ID
     * @return array
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
    public function countByBuyer(int $buyerId): int
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

    /**
     * Get total review count.
     *
     * @return int
     */
    public function countAll(): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM reviews");
        $stmt->execute();
        $result = $stmt->get_result();
        $total = (int) ($result->fetch_assoc()['total'] ?? 0);
        $stmt->close();
        return $total;
    }

    // ============================================================
    // UPDATE
    // ============================================================

    /**
     * Update an existing review.
     *
     * @param int $orderId Order ID
     * @param int $buyerId Buyer ID
     * @param int $rating New rating (1-5)
     * @param string $comment New comment
     * @return array
     */
    public function update(int $orderId, int $buyerId, int $rating, string $comment): array
    {
        $existing = $this->findByOrderAndBuyer($orderId, $buyerId);
        if (!$existing) {
            return ['success' => false, 'message' => 'Review not found'];
        }

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
    // DELETE
    // ============================================================

    /**
     * Delete a review (admin function).
     *
     * @param int $reviewId Review ID
     * @return bool
     */
    public function delete(int $reviewId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM reviews WHERE review_id = ?");
        $stmt->bind_param('i', $reviewId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Delete all reviews by a buyer.
     *
     * @param int $buyerId Buyer ID
     * @return bool
     */
    public function deleteByBuyer(int $buyerId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM reviews WHERE buyer_id = ?");
        $stmt->bind_param('i', $buyerId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Delete all reviews for a seller.
     *
     * @param int $sellerId Seller ID
     * @return bool
     */
    public function deleteBySeller(int $sellerId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM reviews WHERE seller_id = ?");
        $stmt->bind_param('i', $sellerId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
