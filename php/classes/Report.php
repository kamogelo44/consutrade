<?php

/**
 * ConsuTrade - Report
 *
 * Domain class representing a product report submitted by a buyer.
 *
 * @author Kamogelo Phale
 * @version 1.0.0
 * @since 2026
 */

class Report
{
    /** @var int Report ID */
    private int $reportId;

    /** @var int Product ID being reported */
    private int $productId;

    /** @var int User ID of the reporter (buyer) */
    private int $reporterId;

    /** @var string Report reason (fake_product, wrong_description, counterfeit, scam, other) */
    private string $reason;

    /** @var string|null Additional description */
    private ?string $description;

    /** @var string Report status (pending, reviewed, dismissed, action_taken) */
    private string $status;

    /** @var string|null Admin notes about the report */
    private ?string $adminNotes;

    /** @var string Creation timestamp */
    private string $createdAt;

    /** @var string|null Review timestamp */
    private ?string $reviewedAt;

    /** @var int|null Admin user ID who reviewed the report */
    private ?int $reviewedBy;

    /**
     * Constructor.
     *
     * @param array $data Associative array of report data from the database
     */
    public function __construct(array $data)
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

    /**
     * Returns the report ID.
     *
     * @return int
     */
    public function getReportId(): int
    {
        return $this->reportId;
    }

    /**
     * Returns the product ID.
     *
     * @return int
     */
    public function getProductId(): int
    {
        return $this->productId;
    }

    /**
     * Returns the reporter user ID.
     *
     * @return int
     */
    public function getReporterId(): int
    {
        return $this->reporterId;
    }

    /**
     * Returns the report reason.
     *
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * Returns the human-readable reason label.
     *
     * @return string
     */
    public function getReasonLabel(): string
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

    /**
     * Returns the report description.
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Returns the report status.
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Returns the human-readable status label.
     *
     * @return string
     */
    public function getStatusLabel(): string
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
    public function getStatusClass(): string
    {
        return match ($this->status) {
            'pending' => 'status-pending',
            'reviewed' => 'status-reviewed',
            'dismissed' => 'status-dismissed',
            'action_taken' => 'status-action-taken',
            default => ''
        };
    }

    /**
     * Returns admin notes.
     *
     * @return string|null
     */
    public function getAdminNotes(): ?string
    {
        return $this->adminNotes;
    }

    /**
     * Returns creation timestamp.
     *
     * @return string
     */
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /**
     * Returns formatted creation date.
     *
     * @param string $format Date format (default: 'd M Y, H:i')
     * @return string
     */
    public function getFormattedCreatedAt(string $format = 'd M Y, H:i'): string
    {
        return date($format, strtotime($this->createdAt));
    }

    /**
     * Returns review timestamp.
     *
     * @return string|null
     */
    public function getReviewedAt(): ?string
    {
        return $this->reviewedAt;
    }

    /**
     * Returns formatted review date.
     *
     * @param string $format Date format (default: 'd M Y, H:i')
     * @return string|null
     */
    public function getFormattedReviewedAt(string $format = 'd M Y, H:i'): ?string
    {
        return $this->reviewedAt ? date($format, strtotime($this->reviewedAt)) : null;
    }

    /**
     * Returns the admin user ID who reviewed the report.
     *
     * @return int|null
     */
    public function getReviewedBy(): ?int
    {
        return $this->reviewedBy;
    }

    // ============================================================
    //  SETTERS (for business logic)
    // ============================================================

    /**
     * Sets the report status.
     *
     * @param string $status New status
     * @return void
     */
    public function setStatus(string $status): void
    {
        $validStatuses = ['pending', 'reviewed', 'dismissed', 'action_taken'];
        if (in_array($status, $validStatuses)) {
            $this->status = $status;
        }
    }

    /**
     * Sets admin notes.
     *
     * @param string|null $notes Admin notes
     * @return void
     */
    public function setAdminNotes(?string $notes): void
    {
        $this->adminNotes = $notes;
    }

    /**
     * Sets the review timestamp and reviewer.
     *
     * @param int $adminId Admin user ID
     * @return void
     */
    public function markAsReviewed(int $adminId): void
    {
        $this->reviewedAt = date('Y-m-d H:i:s');
        $this->reviewedBy = $adminId;
    }

    // ============================================================
    //  BUSINESS LOGIC METHODS
    // ============================================================

    /**
     * Checks if the report is still pending.
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Checks if the report has been dismissed.
     *
     * @return bool
     */
    public function isDismissed(): bool
    {
        return $this->status === 'dismissed';
    }

    /**
     * Checks if action has been taken on this report.
     *
     * @return bool
     */
    public function isActionTaken(): bool
    {
        return $this->status === 'action_taken';
    }

    /**
     * Checks if the report has been reviewed.
     *
     * @return bool
     */
    public function isReviewed(): bool
    {
        return in_array($this->status, ['reviewed', 'dismissed', 'action_taken']);
    }

    /**
     * Returns whether the report is for a serious violation.
     * Serious violations = fake_product, counterfeit, scam
     *
     * @return bool
     */
    public function isSeriousViolation(): bool
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
    public function dismiss(int $adminId, ?string $notes = null): void
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
    public function markActionTaken(int $adminId, ?string $notes = null): void
    {
        $this->status = 'action_taken';
        $this->adminNotes = $notes;
        $this->markAsReviewed($adminId);
    }

    // ============================================================
    //  EXPORT METHODS
    // ============================================================

    /**
     * Exports report data as array for API responses.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
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
     * Exports report with related data (product, reporter, seller) for admin view.
     *
     * @param array $extraData Additional data like product_title, seller_name, etc.
     * @return array<string, mixed>
     */
    public function toAdminArray(array $extraData = []): array
    {
        return array_merge($this->toArray(), $extraData);
    }
}
