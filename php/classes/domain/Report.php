<?php

/**
 * ConsuTrade - Report
 *
 * Domain class representing a product report submitted by a buyer.
 *
 * @author Kamogelo Phale
 * @version 1.0.0
 */

class Report
{
    private $reportId;
    private $productId;
    private $reporterId;
    private $reason;
    private $description;
    private $status;
    private $adminNotes;
    private $createdAt;
    private $reviewedAt;
    private $reviewedBy;

    /**
     * Constructor.
     *
     * @param array $data Associative array of report data from the database
     */
    public function __construct($data)
    {
        $this->reportId = (int) ($data['report_id'] ?? 0);
        $this->productId = (int) ($data['product_id'] ?? 0);
        $this->reporterId = (int) ($data['reporter_id'] ?? 0);
        $this->reason = (string) ($data['reason'] ?? 'other');
        $this->description = isset($data['description']) ? (string) $data['description'] : null;
        $this->status = (string) ($data['status'] ?? 'pending');
        $this->adminNotes = isset($data['admin_notes']) ? (string) $data['admin_notes'] : null;
        $this->createdAt = (string) ($data['created_at'] ?? '');
        $this->reviewedAt = isset($data['reviewed_at']) ? (string) $data['reviewed_at'] : null;
        $this->reviewedBy = isset($data['reviewed_by']) ? (int) $data['reviewed_by'] : null;
    }

    // ============================================================
    //  GETTERS
    // ============================================================

    public function getReportId()
    {
        return $this->reportId;
    }

    public function getProductId()
    {
        return $this->productId;
    }

    public function getReporterId()
    {
        return $this->reporterId;
    }

    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Returns the human-readable reason label.
     *
     * @return string
     */
    public function getReasonLabel()
    {
        $labels = [
            'fake_product' => 'Fake Product',
            'wrong_description' => 'Wrong Description',
            'counterfeit' => 'Counterfeit Item',
            'scam' => 'Potential Scam',
            'other' => 'Other Issue'
        ];
        return $labels[$this->reason] ?? ucfirst(str_replace('_', ' ', $this->reason));
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Returns the human-readable status label.
     *
     * @return string
     */
    public function getStatusLabel()
    {
        $labels = [
            'pending' => 'Pending Review',
            'reviewed' => 'Reviewed',
            'dismissed' => 'Dismissed',
            'action_taken' => 'Action Taken'
        ];
        return $labels[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Returns the status CSS class for badges.
     *
     * @return string
     */
    public function getStatusClass()
    {
        $classes = [
            'pending' => 'status-pending',
            'reviewed' => 'status-reviewed',
            'dismissed' => 'status-dismissed',
            'action_taken' => 'status-action-taken'
        ];
        return $classes[$this->status] ?? '';
    }

    public function getAdminNotes()
    {
        return $this->adminNotes;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Returns formatted creation date.
     *
     * @param string $format Date format
     * @return string
     */
    public function getFormattedCreatedAt($format = 'd M Y, H:i')
    {
        return date($format, strtotime($this->createdAt));
    }

    public function getReviewedAt()
    {
        return $this->reviewedAt;
    }

    /**
     * Returns formatted review date.
     *
     * @param string $format Date format
     * @return string|null
     */
    public function getFormattedReviewedAt($format = 'd M Y, H:i')
    {
        return $this->reviewedAt ? date($format, strtotime($this->reviewedAt)) : null;
    }

    public function getReviewedBy()
    {
        return $this->reviewedBy;
    }

    // ============================================================
    //  SETTERS
    // ============================================================

    public function setStatus($status)
    {
        $validStatuses = ['pending', 'reviewed', 'dismissed', 'action_taken'];
        if (in_array($status, $validStatuses)) {
            $this->status = $status;
        }
    }

    public function setAdminNotes($notes)
    {
        $this->adminNotes = $notes;
    }

    public function markAsReviewed($adminId)
    {
        $this->reviewedAt = date('Y-m-d H:i:s');
        $this->reviewedBy = $adminId;
    }

    // ============================================================
    //  BUSINESS LOGIC METHODS
    // ============================================================

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isDismissed()
    {
        return $this->status === 'dismissed';
    }

    public function isActionTaken()
    {
        return $this->status === 'action_taken';
    }

    public function isReviewed()
    {
        return in_array($this->status, ['reviewed', 'dismissed', 'action_taken']);
    }

    /**
     * Returns whether the report is for a serious violation.
     * Serious violations = fake_product, counterfeit, scam
     *
     * @return bool
     */
    public function isSeriousViolation()
    {
        return in_array($this->reason, ['fake_product', 'counterfeit', 'scam']);
    }

    /**
     * Dismiss the report (mark as dismissed without product action).
     *
     * @param int $adminId Admin user ID
     * @param string|null $notes Admin notes
     * @return void
     */
    public function dismiss($adminId, $notes = null)
    {
        $this->status = 'dismissed';
        $this->adminNotes = $notes;
        $this->markAsReviewed($adminId);
    }

    /**
     * Mark that action was taken (e.g., product suspended).
     *
     * @param int $adminId Admin user ID
     * @param string|null $notes Admin notes
     * @return void
     */
    public function markActionTaken($adminId, $notes = null)
    {
        $this->status = 'action_taken';
        $this->adminNotes = $notes;
        $this->markAsReviewed($adminId);
    }

    // ============================================================
    //  EXPORT METHODS
    // ============================================================

    /**
     * Exports report data as array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'report_id' => $this->reportId,
            'product_id' => $this->productId,
            'reporter_id' => $this->reporterId,
            'reason' => $this->reason,
            'reason_label' => $this->getReasonLabel(),
            'description' => $this->description,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'status_class' => $this->getStatusClass(),
            'admin_notes' => $this->adminNotes,
            'created_at' => $this->createdAt,
            'formatted_created_at' => $this->getFormattedCreatedAt(),
            'reviewed_at' => $this->reviewedAt,
            'formatted_reviewed_at' => $this->getFormattedReviewedAt(),
            'reviewed_by' => $this->reviewedBy,
            'is_pending' => $this->isPending(),
            'is_serious_violation' => $this->isSeriousViolation()
        ];
    }

    /**
     * Exports report with related data for admin view.
     *
     * @param array $extraData Additional data
     * @return array
     */
    public function toAdminArray($extraData = [])
    {
        return array_merge($this->toArray(), $extraData);
    }
}
