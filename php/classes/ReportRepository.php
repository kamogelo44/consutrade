<?php

/**
 * ConsuTrade - ReportRepository
 *
 * Handles all product report database operations.
 * Uses the Report domain class.
 *
 * @author Kamogelo Phale
 * @version 1.0.0
 * @since 2026
 */

class ReportRepository
{
    /** @var mysqli Database connection */
    private mysqli $db;

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
    //  HYDRATION
    // ============================================================

    /**
     * Hydrate a single database row into a Report object.
     *
     * @param array $data Database row
     * @return Report
     */
    private function hydrate(array $data): Report
    {
        return new Report($data);
    }

    /**
     * Hydrate multiple database rows into Report objects.
     *
     * @param array $rows Database rows
     * @return Report[]
     */
    private function hydrateMultiple(array $rows): array
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
     * @param Report $report Report object (without report_id)
     * @return int|false Insert ID or false on failure
     */
    public function createReport(Report $report)
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
    public function createReportFromData(int $productId, int $reporterId, string $reason, ?string $description = null)
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
     * @return Report|null Report object or null if not found
     */
    public function getReportById(int $reportId): ?Report
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
     * @return array|null Report with related data or null if not found
     */
    public function getReportWithDetails(int $reportId): ?array
    {
        $sql = "SELECT 
                    pr.*,
                    p.title as product_title, p.price as product_price, p.status as product_status,
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
            $report = $this->hydrate($row);
            return $report->toAdminArray([
                'product_title' => $row['product_title'],
                'product_price' => (float) $row['product_price'],
                'product_status' => $row['product_status'],
                'product_image' => $this->getProductImageUrl($row['product_image']),
                'seller_id' => (int) $row['seller_id'],
                'seller_name' => $row['seller_name'],
                'reporter_name' => $row['reporter_name'],
                'reporter_email' => $row['reporter_email']
            ]);
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
     * @return Report[]
     */
    public function getPendingReports(int $limit = 20, int $offset = 0): array
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
     * @return array Array of reports with related data
     */
    public function getPendingReportsWithDetails(int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT 
                    pr.*,
                    p.title as product_title, p.price as product_price, p.status as product_status,
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
            $report = $this->hydrate($row);
            $reports[] = $report->toAdminArray([
                'product_title' => $row['product_title'],
                'product_price' => (float) $row['product_price'],
                'product_status' => $row['product_status'],
                'product_image' => $this->getProductImageUrl($row['product_image']),
                'seller_id' => (int) $row['seller_id'],
                'seller_name' => $row['seller_name'],
                'reporter_name' => $row['reporter_name'],
                'reporter_email' => $row['reporter_email']
            ]);
        }
        $stmt->close();

        return $reports;
    }

    /**
     * Get all reports for a specific product as Report objects.
     *
     * @param int $productId Product ID
     * @return Report[]
     */
    public function getReportsByProduct(int $productId): array
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
     * @param string $status Status filter (all, pending, dismissed, action_taken)
     * @param int $limit Results per page
     * @param int $offset Pagination offset
     * @return array
     */
    public function getAllReportsWithDetails(string $status = 'all', int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT 
                    pr.*,
                    p.title as product_title, p.status as product_status,
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
            $report = $this->hydrate($row);
            $reports[] = $report->toAdminArray([
                'product_title' => $row['product_title'],
                'product_status' => $row['product_status'],
                'product_image' => $this->getProductImageUrl($row['product_image']),
                'seller_name' => $row['seller_name'],
                'reporter_name' => $row['reporter_name'],
                'reporter_email' => $row['reporter_email']
            ]);
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
     * @return int Number of pending reports
     */
    public function getPendingReportsCount(): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM product_reports WHERE status = 'pending'");
        $stmt->execute();
        $result = $stmt->get_result();
        $total = (int) ($result->fetch_assoc()['total'] ?? 0);
        $stmt->close();
        return $total;
    }

    /**
     * Get total count of reports with status filter.
     *
     * @param string $status Status filter (all, pending, dismissed, action_taken)
     * @return int
     */
    public function getReportsCount(string $status = 'all'): int
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
     * @return int Number of pending reports
     */
    public function getPendingReportCountForProduct(int $productId): int
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
     * Prevents duplicate/spam reports.
     *
     * @param int $userId User ID
     * @param int $productId Product ID
     * @return bool True if user has already reported, false otherwise
     */
    public function hasUserReportedProduct(int $userId, int $productId): bool
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
    public function hasPendingReports(int $productId): bool
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
     * @return bool True on success, false on failure
     */
    public function updateReport(Report $report): bool
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
     * @param string $status New status ('dismissed' or 'action_taken')
     * @param string|null $adminNotes Admin notes about the decision
     * @param int $adminId Admin user ID
     * @return bool True on success, false on failure
     */
    public function updateReportStatus(int $reportId, string $status, ?string $adminNotes = null, int $adminId = 0): bool
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
    public function dismissReport(int $reportId, int $adminId, ?string $notes = null): bool
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
    public function markActionTaken(int $reportId, int $adminId, ?string $notes = null): bool
    {
        return $this->updateReportStatus($reportId, 'action_taken', $notes, $adminId);
    }

    // ============================================================
    //  DELETE
    // ============================================================

    /**
     * Delete a report by ID (soft delete not needed, can hard delete if needed).
     *
     * @param int $reportId Report ID
     * @return bool
     */
    public function deleteReport(int $reportId): bool
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
    public function deleteReportsByProduct(int $productId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM product_reports WHERE product_id = ?");
        $stmt->bind_param('i', $productId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // ============================================================
    //  HELPER METHODS
    // ============================================================

    /**
     * Get product image URL.
     *
     * @param string|null $imagePath The stored image path
     * @return string Full URL to image
     */
    private function getProductImageUrl(?string $imagePath): string
    {
        $baseUrl = getBaseUrl();

        if (empty($imagePath)) {
            return $baseUrl . 'images/default-product.png';
        }

        if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
            return $imagePath;
        }

        return $baseUrl . $imagePath;
    }

    /**
     * Get human-readable label for report reason (static helper).
     *
     * @param string $reason The reason code
     * @return string Human-readable label
     */
    public static function getReasonLabel(string $reason): string
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
