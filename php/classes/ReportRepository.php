<?php

/**
 * ConsuTrade - ReportRepository
 *
 * Handles all product report database operations.
 * Uses the Report domain class.
 *
 * @author Kamogelo Phale
 * @version 1.2.0
 */

class ReportRepository
{
    /** @var mysqli Database connection */
    private $db;

    /** @var ProductRepository Product repository for image URLs and product data */
    private $productRepo;

    /**
     * Constructor.
     *
     * @param mysqli $db Database connection
     * @param ProductRepository $productRepo Product repository instance
     */
    public function __construct($db, ProductRepository $productRepo)
    {
        $this->db = $db;
        $this->productRepo = $productRepo;
    }

    // ============================================================
    //  HYDRATION
    // ============================================================

    /**
     * Hydrate a single database row into a Report object.
     *
     * @param array $data Database row
     * @return Report
     */
    private function hydrate($data)
    {
        return new Report($data);
    }

    /**
     * Hydrate multiple database rows into Report objects.
     *
     * @param array $rows Database rows
     * @return array
     */
    private function hydrateMultiple($rows)
    {
        $reports = [];
        foreach ($rows as $row) {
            $reports[] = $this->hydrate($row);
        }
        return $reports;
    }

    // ============================================================
    //  CREATE
    // ============================================================

    /**
     * Create a new product report.
     *
     * @param Report $report Report object
     * @return int|false Insert ID or false on failure
     */
    public function createReport($report)
    {
        $productId = $report->getProductId();
        $reporterId = $report->getReporterId();
        $reason = $report->getReason();
        $description = $report->getDescription();

        $stmt = $this->db->prepare(
            "INSERT INTO product_reports (product_id, reporter_id, reason, description, created_at) 
             VALUES (?, ?, ?, ?, NOW())"
        );
        $stmt->bind_param('iiss', $productId, $reporterId, $reason, $description);

        if ($stmt->execute()) {
            $reportId = $stmt->insert_id;
            $stmt->close();
            return $reportId;
        }

        $stmt->close();
        return false;
    }

    /**
     * Create a new product report from raw data.
     *
     * @param int $productId Product ID
     * @param int $reporterId User ID of the person reporting
     * @param string $reason Report reason
     * @param string|null $description Additional description
     * @return int|false Insert ID or false on failure
     */
    public function createReportFromData($productId, $reporterId, $reason, $description = null)
    {
        $validReasons = ['fake_product', 'wrong_description', 'counterfeit', 'scam', 'other'];
        if (!in_array($reason, $validReasons)) {
            return false;
        }

        $report = new Report([
            'product_id' => $productId,
            'reporter_id' => $reporterId,
            'reason' => $reason,
            'description' => $description
        ]);

        return $this->createReport($report);
    }

    // ============================================================
    //  READ (Single)
    // ============================================================

    /**
     * Get a report by ID as a Report object.
     *
     * @param int $reportId Report ID
     * @return Report|null
     */
    public function getReportById($reportId)
    {
        $sql = "SELECT * FROM product_reports WHERE report_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $reportId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return $this->hydrate($row);
        }

        $stmt->close();
        return null;
    }

    /**
     * Get a report with related data (product, reporter, seller).
     *
     * @param int $reportId Report ID
     * @return array|null
     */
    public function getReportWithDetails($reportId)
    {
        $sql = "SELECT 
                    pr.*,
                    p.title as product_title, p.price as product_price, p.status as product_status,
                    p.image_url as product_image_url,
                    p.seller_id,
                    u_seller.full_name as seller_name,
                    u_reporter.full_name as reporter_name, u_reporter.email as reporter_email,
                    COALESCE(pi.image_url, p.image_url) AS product_image
                FROM product_reports pr
                JOIN products p ON pr.product_id = p.product_id
                JOIN users u_seller ON p.seller_id = u_seller.user_id
                JOIN users u_reporter ON pr.reporter_id = u_reporter.user_id
                LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                WHERE pr.report_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $reportId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            $productImage = $this->productRepo->getImageUrl($row['product_image'] ?? $row['product_image_url']);

            return [
                'report_id' => (int)$row['report_id'],
                'product_id' => (int)$row['product_id'],
                'product_title' => $row['product_title'],
                'product_price' => (float)$row['product_price'],
                'product_status' => $row['product_status'],
                'product_image' => $productImage,
                'seller_id' => (int)$row['seller_id'],
                'seller_name' => $row['seller_name'],
                'reporter_id' => (int)$row['reporter_id'],
                'reporter_name' => $row['reporter_name'],
                'reporter_email' => $row['reporter_email'],
                'reason' => $row['reason'],
                'reason_label' => self::getReasonLabel($row['reason']),
                'description' => $row['description'] ?? '',
                'status' => $row['status'],
                'created_at' => date('d M Y, H:i', strtotime($row['created_at'])),
                'admin_notes' => $row['admin_notes'] ?? null,
                'reviewed_at' => $row['reviewed_at'],
                'reviewed_by' => $row['reviewed_by']
            ];
        }

        $stmt->close();
        return null;
    }

    // ============================================================
    //  READ (Collections)
    // ============================================================

    /**
     * Get all pending reports as Report objects.
     *
     * @param int $limit Results per page
     * @param int $offset Pagination offset
     * @return array
     */
    public function getPendingReports($limit = 20, $offset = 0)
    {
        $sql = "SELECT * FROM product_reports 
                WHERE status = 'pending' 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();

        return $this->hydrateMultiple($rows);
    }

    /**
     * Get all pending reports with full details for admin display.
     *
     * @param int $limit Results per page
     * @param int $offset Pagination offset
     * @return array
     */
    public function getPendingReportsWithDetails($limit = 20, $offset = 0)
    {
        $sql = "SELECT 
                pr.*,
                p.title as product_title, p.price as product_price, p.status as product_status,
                p.image_url as product_image_url,
                p.seller_id,
                u_seller.full_name as seller_name,
                u_reporter.full_name as reporter_name, u_reporter.email as reporter_email,
                COALESCE(pi.image_url, p.image_url) AS product_image
            FROM product_reports pr
            JOIN products p ON pr.product_id = p.product_id
            JOIN users u_seller ON p.seller_id = u_seller.user_id
            JOIN users u_reporter ON pr.reporter_id = u_reporter.user_id
            LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
            WHERE pr.status = 'pending'
            ORDER BY pr.created_at DESC
            LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        $reports = [];
        while ($row = $result->fetch_assoc()) {
            // Get the raw image value - even for deleted products
            $rawImage = $row['product_image'] ?? $row['product_image_url'];

            // Pass to getImageUrl - it handles empty values and returns default
            $productImage = $this->productRepo->getImageUrl($rawImage);

            // product availability status message for admin
            $productAvailabilityMessage = '';
            $productAvailable = false;

            if ($row['product_status'] === 'deleted') {
                $productAvailabilityMessage = 'This product has been deleted by the seller and is no longer available.';
                $productAvailable = false;
            } elseif ($row['product_status'] === 'suspended') {
                $productAvailabilityMessage = 'This product has been suspended.';
                $productAvailable = false;
            } elseif ($row['product_status'] === 'active') {
                $productAvailable = true;
            }

            $reports[] = [
                'report_id' => (int)$row['report_id'],
                'product_id' => (int)$row['product_id'],
                'product_title' => $row['product_title'],
                'product_price' => (float)$row['product_price'],
                'product_status' => $row['product_status'],
                'product_image' => $productImage,
                'product_available' => $productAvailable,
                'product_availability_message' => $productAvailabilityMessage,
                'seller_id' => (int)$row['seller_id'],
                'seller_name' => $row['seller_name'],
                'reporter_id' => (int)$row['reporter_id'],
                'reporter_name' => $row['reporter_name'],
                'reporter_email' => $row['reporter_email'],
                'reason' => $row['reason'],
                'reason_label' => self::getReasonLabel($row['reason']),
                'description' => $row['description'] ?? '',
                'status' => $row['status'],
                'created_at' => date('d M Y, H:i', strtotime($row['created_at'])),
                'admin_notes' => $row['admin_notes'] ?? null,
                'reviewed_at' => $row['reviewed_at'],
                'reviewed_by' => $row['reviewed_by']
            ];
        }
        $stmt->close();

        return $reports;
    }

    /**
     * Get all reports for a specific product as Report objects.
     *
     * @param int $productId Product ID
     * @return array
     */
    public function getReportsByProduct($productId)
    {
        $sql = "SELECT * FROM product_reports 
                WHERE product_id = ? 
                ORDER BY created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();

        return $this->hydrateMultiple($rows);
    }

    /**
     * Get all reports for admin with filtering.
     *
     * @param string $status Status filter
     * @param int $limit Results per page
     * @param int $offset Pagination offset
     * @return array
     */
    public function getAllReportsWithDetails($status = 'all', $limit = 20, $offset = 0)
    {
        $sql = "SELECT 
                    pr.*,
                    p.title as product_title, p.status as product_status,
                    p.image_url as product_image_url,
                    u_seller.full_name as seller_name,
                    u_reporter.full_name as reporter_name, u_reporter.email as reporter_email,
                    COALESCE(pi.image_url, p.image_url) AS product_image
                FROM product_reports pr
                JOIN products p ON pr.product_id = p.product_id
                JOIN users u_seller ON p.seller_id = u_seller.user_id
                JOIN users u_reporter ON pr.reporter_id = u_reporter.user_id
                LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                WHERE 1=1";

        $params = [];
        $types = "";

        if ($status !== 'all') {
            $sql .= " AND pr.status = ?";
            $params[] = $status;
            $types .= "s";
        }

        $sql .= " ORDER BY pr.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $reports = [];
        while ($row = $result->fetch_assoc()) {
            $productImage = $this->productRepo->getImageUrl($row['product_image'] ?? $row['product_image_url']);

            $reports[] = [
                'report_id' => (int)$row['report_id'],
                'product_id' => (int)$row['product_id'],
                'product_title' => $row['product_title'],
                'product_status' => $row['product_status'],
                'product_image' => $productImage,
                'seller_name' => $row['seller_name'],
                'reporter_id' => (int)$row['reporter_id'],
                'reporter_name' => $row['reporter_name'],
                'reporter_email' => $row['reporter_email'],
                'reason' => $row['reason'],
                'reason_label' => self::getReasonLabel($row['reason']),
                'description' => $row['description'] ?? '',
                'status' => $row['status'],
                'created_at' => date('d M Y, H:i', strtotime($row['created_at'])),
                'admin_notes' => $row['admin_notes'] ?? null,
                'reviewed_at' => $row['reviewed_at'],
                'reviewed_by' => $row['reviewed_by']
            ];
        }
        $stmt->close();

        return $reports;
    }

    // ============================================================
    //  COUNTS
    // ============================================================

    /**
     * Get total count of pending reports.
     *
     * @return int
     */
    public function getPendingReportsCount()
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM product_reports WHERE status = 'pending'");
        $stmt->execute();
        $result = $stmt->get_result();
        $total = (int) ($result->fetch_assoc()['total'] ?? 0);
        $stmt->close();
        return $total;
    }

    /**
     * Count pending reports
     * Alias for getPendingReportsCount() for consistency with other repositories
     */
    public function countPending(): int
    {
        return $this->getPendingReportsCount();
    }

    /**
     * Get total count of reports with status filter.
     *
     * @param string $status Status filter
     * @return int
     */
    public function getReportsCount($status = 'all')
    {
        $sql = "SELECT COUNT(*) as total FROM product_reports";

        if ($status !== 'all') {
            $sql .= " WHERE status = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('s', $status);
        } else {
            $stmt = $this->db->prepare($sql);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $total = (int) ($result->fetch_assoc()['total'] ?? 0);
        $stmt->close();
        return $total;
    }

    /**
     * Get count of pending reports for a specific product.
     *
     * @param int $productId Product ID
     * @return int
     */
    public function getPendingReportCountForProduct($productId)
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as count FROM product_reports 
             WHERE product_id = ? AND status = 'pending'"
        );
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $count = (int) ($row['count'] ?? 0);
        $stmt->close();
        return $count;
    }

    // ============================================================
    //  CHECK / VALIDATION
    // ============================================================

    /**
     * Check if a user has already reported a specific product.
     *
     * @param int $userId User ID
     * @param int $productId Product ID
     * @return bool
     */
    public function hasUserReportedProduct($userId, $productId)
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as count FROM product_reports 
             WHERE reporter_id = ? AND product_id = ?"
        );
        $stmt->bind_param('ii', $userId, $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $count = (int) ($row['count'] ?? 0);
        $stmt->close();
        return $count > 0;
    }

    /**
     * Check if a product has any pending reports.
     *
     * @param int $productId Product ID
     * @return bool
     */
    public function hasPendingReports($productId)
    {
        return $this->getPendingReportCountForProduct($productId) > 0;
    }

    // ============================================================
    //  UPDATE
    // ============================================================

    /**
     * Update report status using a Report object.
     *
     * @param Report $report Report object (with updated status)
     * @return bool
     */
    public function updateReport($report)
    {
        $stmt = $this->db->prepare(
            "UPDATE product_reports 
             SET status = ?, admin_notes = ?, reviewed_at = ?, reviewed_by = ?
             WHERE report_id = ?"
        );

        $status = $report->getStatus();
        $adminNotes = $report->getAdminNotes();
        $reviewedAt = $report->getReviewedAt();
        $reviewedBy = $report->getReviewedBy();
        $reportId = $report->getReportId();

        $stmt->bind_param('sssii', $status, $adminNotes, $reviewedAt, $reviewedBy, $reportId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Update report status after admin action.
     *
     * @param int $reportId Report ID
     * @param string $status New status
     * @param string|null $adminNotes Admin notes
     * @param int $adminId Admin user ID
     * @return bool
     */
    public function updateReportStatus($reportId, $status, $adminNotes = null, $adminId = 0)
    {
        $validStatuses = ['dismissed', 'action_taken', 'reviewed'];
        if (!in_array($status, $validStatuses)) {
            return false;
        }

        $stmt = $this->db->prepare(
            "UPDATE product_reports 
             SET status = ?, admin_notes = ?, reviewed_at = NOW(), reviewed_by = ?
             WHERE report_id = ?"
        );
        $stmt->bind_param('ssii', $status, $adminNotes, $adminId, $reportId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Dismiss a report.
     *
     * @param int $reportId Report ID
     * @param int $adminId Admin user ID
     * @param string|null $notes Admin notes
     * @return bool
     */
    public function dismissReport($reportId, $adminId, $notes = null)
    {
        return $this->updateReportStatus($reportId, 'dismissed', $notes, $adminId);
    }

    /**
     * Mark that action was taken on a report.
     *
     * @param int $reportId Report ID
     * @param int $adminId Admin user ID
     * @param string|null $notes Admin notes
     * @return bool
     */
    public function markActionTaken($reportId, $adminId, $notes = null)
    {
        return $this->updateReportStatus($reportId, 'action_taken', $notes, $adminId);
    }

    // ============================================================
    //  DELETE
    // ============================================================

    /**
     * Delete a report by ID.
     *
     * @param int $reportId Report ID
     * @return bool
     */
    public function deleteReport($reportId)
    {
        $stmt = $this->db->prepare("DELETE FROM product_reports WHERE report_id = ?");
        $stmt->bind_param('i', $reportId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Delete all reports for a product.
     *
     * @param int $productId Product ID
     * @return bool
     */
    public function deleteReportsByProduct($productId)
    {
        $stmt = $this->db->prepare("DELETE FROM product_reports WHERE product_id = ?");
        $stmt->bind_param('i', $productId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // ============================================================
    //  STATIC HELPERS (No database needed)
    // ============================================================

    /**
     * Get human-readable label for report reason.
     *
     * @param string $reason The reason code
     * @return string
     */
    public static function getReasonLabel($reason)
    {
        $labels = [
            'fake_product' => 'Fake Product',
            'wrong_description' => 'Wrong Description',
            'counterfeit' => 'Counterfeit Item',
            'scam' => 'Potential Scam',
            'other' => 'Other Issue'
        ];
        return $labels[$reason] ?? ucfirst(str_replace('_', ' ', $reason));
    }
}
