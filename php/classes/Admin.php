<?php

/**
 * ConsuTrade - Admin
 *
 * Domain class representing an administrator user.
 *
 * @author Kamogelo Phale
 * @version 2.0.0
 */

class Admin extends User
{
    /** @var mysqli Database connection */
    private $db;

    /**
     * Constructor.
     *
     * @param array $data User data
     * @param mysqli $db Database connection
     */
    public function __construct($data, $db)
    {
        parent::__construct($data);
        $this->db = $db;
    }

    /**
     * Get the admin's display name.
     *
     * @return string
     */
    public function getDisplayName()
    {
        return $this->fullName . ' (Admin)';
    }

    /**
     * Get all users (for admin panel)
     *
     * @param string $filter Status filter
     * @param string $search Search term
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array
     */
    public function getAllUsers($filter = 'all', $search = '', $limit = 10, $offset = 0)
    {
        $userRepo = new UserRepository($this->db);
        return $userRepo->getAll($filter, $search, $limit, $offset);
    }

    /**
     * Get total user count
     *
     * @param string|null $role Optional role filter
     * @return int
     */
    public function getTotalUsers($role = null)
    {
        $userRepo = new UserRepository($this->db);
        return $userRepo->getTotalUsers($role);
    }

    /**
     * Get all orders (for admin panel)
     *
     * @return array
     */
    public function getAllOrders()
    {
        $orderRepo = new OrderRepository($this->db);
        return $orderRepo->getAllOrders();
    }

    /**
     * Get recent orders for dashboard
     *
     * @param int $limit Number of orders
     * @return array
     */
    public function getRecentOrders($limit = 5)
    {
        $orderRepo = new OrderRepository($this->db);
        return $orderRepo->getRecentOrders($limit);
    }

    /**
     * Get recent users for dashboard
     *
     * @param int $limit Number of users
     * @return array
     */
    public function getRecentUsers($limit = 5)
    {
        $userRepo = new UserRepository($this->db);
        return $userRepo->getRecentUsers($limit);
    }

    /**
     * Get all products (for admin panel)
     *
     * @param string $status Status filter
     * @param string $search Search term
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array
     */
    public function getAllProducts($status = 'all', $search = '', $limit = 12, $offset = 0)
    {
        $productRepo = new ProductRepository($this->db);
        return $productRepo->getAllProductsForAdmin($status, $search, $limit, $offset);
    }

    /**
     * Get total products count
     *
     * @param string $status Status filter
     * @param string $search Search term
     * @return int
     */
    public function getTotalProducts($status = 'all', $search = '')
    {
        $productRepo = new ProductRepository($this->db);
        return $productRepo->getProductsCountForAdmin($status, $search);
    }

    /**
     * Suspend a user account
     *
     * @param int $userId User ID
     * @return bool
     */
    public function suspendUser($userId)
    {
        $userRepo = new UserRepository($this->db);
        return $userRepo->suspendUser($userId);
    }

    /**
     * Reinstate a user account
     *
     * @param int $userId User ID
     * @return bool
     */
    public function reinstateUser($userId)
    {
        $userRepo = new UserRepository($this->db);
        return $userRepo->reinstateUser($userId);
    }

    /**
     * Ban a user account
     *
     * @param int $userId User ID
     * @return bool
     */
    public function banUser($userId)
    {
        $userRepo = new UserRepository($this->db);
        return $userRepo->banUser($userId);
    }

    /**
     * Suspend a product (admin action)
     *
     * @param int $productId Product ID
     * @param string $reason Suspension reason
     * @return array
     */
    public function suspendProduct($productId, $reason = '')
    {
        $productRepo = new ProductRepository($this->db);

        // Need to get seller_id first
        $product = $productRepo->getProductObject($productId);
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found'];
        }

        return $productRepo->updateProductStatus($productId, $product->getSellerId(), 'suspend', 'admin', $reason);
    }

    /**
     * Reactivate a suspended product (admin action)
     *
     * @param int $productId Product ID
     * @return array
     */
    public function reactivateProduct($productId)
    {
        $productRepo = new ProductRepository($this->db);

        $product = $productRepo->getProductObject($productId);
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found'];
        }

        return $productRepo->updateProductStatus($productId, $product->getSellerId(), 'activate', 'admin');
    }

    /**
     * Get pending seller verifications
     *
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array
     */
    public function getPendingVerifications($limit = 10, $offset = 0)
    {
        $userRepo = new UserRepository($this->db);
        return $userRepo->getPendingVerificationsWithPagination($limit, $offset);
    }

    /**
     * Get pending verifications count
     *
     * @return int
     */
    public function getPendingVerificationsCount()
    {
        $userRepo = new UserRepository($this->db);
        return $userRepo->getPendingVerificationsCount();
    }

    /**
     * Verify a seller's documents
     *
     * @param int $sellerId Seller ID
     * @param string $decision 'approve' or 'reject'
     * @param string|null $rejectionReason Reason for rejection
     * @return bool
     */
    public function verifySellerDocuments($sellerId, $decision, $rejectionReason = null)
    {
        if ($decision === 'approve') {
            // Mark user as verified
            $stmt = $this->db->prepare("UPDATE users SET id_verified = 1 WHERE user_id = ?");
            $stmt->bind_param('i', $sellerId);
            $stmt->execute();
            $stmt->close();

            // Update verification record
            $stmt2 = $this->db->prepare("UPDATE seller_verification SET document_verified = 1, reviewed_by = ?, reviewed_at = NOW() WHERE seller_id = ?");
            $stmt2->bind_param('ii', $this->userId, $sellerId);
            $result = $stmt2->execute();
            $stmt2->close();

            return $result;
        } else {
            // Reject - delete or mark as rejected
            $stmt = $this->db->prepare("UPDATE seller_verification SET document_verified = 0, rejection_reason = ?, reviewed_by = ?, reviewed_at = NOW() WHERE seller_id = ?");
            $stmt->bind_param('sii', $rejectionReason, $this->userId, $sellerId);
            $result = $stmt->execute();
            $stmt->close();

            return $result;
        }
    }

    /**
     * Get admin dashboard stats
     *
     * @return array
     */
    public function getDashboardStats()
    {
        $userRepo = new UserRepository($this->db);
        $orderRepo = new OrderRepository($this->db);
        $productRepo = new ProductRepository($this->db);
        $reportRepo = new ReportRepository($this->db);

        return [
            'total_users' => $userRepo->getTotalUsers(),
            'total_buyers' => $userRepo->getTotalUsers('buyer'),
            'total_sellers' => $userRepo->getTotalUsers('seller'),
            'total_orders' => count($orderRepo->getAllOrders()),
            'total_products' => $productRepo->getProductsCountForAdmin(),
            'pending_reports' => $reportRepo->getPendingReportsCount(),
            'pending_verifications' => $this->getPendingVerificationsCount()
        ];
    }

    /**
     * Get recent activity for dashboard
     *
     * @param int $limit Number of items
     * @return array
     */
    public function getRecentActivity($limit = 10)
    {
        $recentOrders = $this->getRecentOrders(5);
        $recentUsers = $this->getRecentUsers(5);

        // Combine and sort by date (simplified)
        $activity = [];

        foreach ($recentOrders as $order) {
            $activity[] = [
                'type' => 'order',
                'title' => 'New Order #' . $order['order_id'],
                'details' => 'Order from ' . $order['buyer_name'],
                'created_at' => $order['full_created_at'] ?? $order['created_at']
            ];
        }

        foreach ($recentUsers as $user) {
            $activity[] = [
                'type' => 'user',
                'title' => 'New User Registered',
                'details' => $user['full_name'] . ' (' . $user['role'] . ')',
                'created_at' => $user['created_at']
            ];
        }

        // Sort by date (most recent first)
        usort($activity, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return array_slice($activity, 0, $limit);
    }
}
