<?php
/**
 * ConsuTrade - Admin
 *
 * Represents an administrator user. Extends User with platform management capabilities.
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

class Admin extends User
{
    /** @var mysqli Database connection for admin-specific queries */
    private $db;

    /**
     * Constructor.
     *
     * @param array $data User data from the database
     * @param mysqli $db  Database connection
     */
    public function __construct(array $data, mysqli $db)
    {
        parent::__construct($data);
        $this->db = $db;
    }

    /**
     * Returns the admin's display name with role prefix.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        return 'Admin: ' . $this->fullName;
    }

    // ============================================================
    //  USER MANAGEMENT
    // ============================================================

    /**
     * Gets all users in the system.
     *
     * @return array
     */
    public function manageUsers(): array
    {
        $sql = "SELECT user_id, full_name, email, phone, location, role,
                       id_verified, created_at
                FROM users
                ORDER BY created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        $stmt->close();

        return $users;
    }

    // ============================================================
    //  LISTING MANAGEMENT
    // ============================================================

    /**
     * Gets all product listings across the platform.
     *
     * @return array
     */
    public function manageListings(): array
    {
        $sql = "SELECT p.product_id, p.title, p.price, p.status, p.stock_quantity,
                       p.created_at, c.category_name,
                       u.full_name as seller_name, u.user_id as seller_id
                FROM products p
                JOIN categories c ON p.category_id = c.category_id
                JOIN users u ON p.seller_id = u.user_id
                WHERE p.status != 'deleted'
                ORDER BY p.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();

        $listings = [];
        while ($row = $result->fetch_assoc()) {
            $listings[] = $row;
        }
        $stmt->close();

        return $listings;
    }

    // ============================================================
    //  ORDER MANAGEMENT
    // ============================================================

    /**
     * Gets all orders across the platform.
     *
     * @return array
     */
    public function manageOrders(): array
    {
        $orderRepo = new OrderRepository($this->db);
        return $orderRepo->getAllOrders();
    }

    // ============================================================
    //  TRANSACTIONS
    // ============================================================

    /**
     * Gets all payment transactions.
     *
     * @return array
     */
    public function viewTransactions(): array
    {
        $sql = "SELECT t.transaction_id, t.order_id, t.payfast_ref, t.amount,
                       t.status, t.paid_at,
                       o.buyer_id, o.seller_id
                FROM transactions t
                JOIN orders o ON t.order_id = o.order_id
                ORDER BY t.transaction_id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();

        $transactions = [];
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
        $stmt->close();

        return $transactions;
    }

    // ============================================================
    //  REPORTS
    // ============================================================

    /**
     * Generates platform summary reports.
     *
     * @return array
     */
    public function generateReports(): array
    {
        $reports = [];

        // Total users by role
        $sql = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
        $result = $this->db->query($sql);
        $reports['users_by_role'] = [];
        while ($row = $result->fetch_assoc()) {
            $reports['users_by_role'][$row['role']] = (int) $row['count'];
        }

        // Total products by status
        $sql = "SELECT status, COUNT(*) as count FROM products GROUP BY status";
        $result = $this->db->query($sql);
        $reports['products_by_status'] = [];
        while ($row = $result->fetch_assoc()) {
            $reports['products_by_status'][$row['status']] = (int) $row['count'];
        }

        // Total orders by status
        $sql = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
        $result = $this->db->query($sql);
        $reports['orders_by_status'] = [];
        while ($row = $result->fetch_assoc()) {
            $reports['orders_by_status'][$row['status']] = (int) $row['count'];
        }

        // Revenue summary
        $sql = "SELECT SUM(amount) as total_revenue, COUNT(*) as total_transactions
                FROM transactions WHERE status = 'completed'";
        $result = $this->db->query($sql);
        $row = $result->fetch_assoc();
        $reports['revenue'] = [
            'total_revenue'      => (float) ($row['total_revenue'] ?? 0),
            'total_transactions' => (int) ($row['total_transactions'] ?? 0),
        ];

        return $reports;
    }

    // ============================================================
    //  SELLER VERIFICATION
    // ============================================================

    /**
     * Approves or rejects a seller's verification documents.
     *
     * @param int    $id       Seller ID
     * @param string $decision 'approve' or 'reject'
     * @return bool
     */
    public function verifySellerDocuments(int $id, string $decision): bool
    {
        if ($decision === 'approve') {
            $sql = "UPDATE seller_verification
                    SET document_verified = 1,
                        verified_at = NOW(),
                        verification_score = verification_score + 25
                    WHERE seller_id = ?";
        } elseif ($decision === 'reject') {
            $sql = "UPDATE seller_verification
                    SET document_verified = 0,
                        document_path = NULL,
                        document_type = NULL
                    WHERE seller_id = ?";
        } else {
            return false;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }
}