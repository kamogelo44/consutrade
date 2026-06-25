<?php

/**
 * ConsuTrade - SellerVerification
 *
 * Domain class representing a seller's verification document submission.
 *
 * @author Kamogelo Phale
 * @version 2.0.0
 */

class SellerVerification
{
    private $verificationId;
    private $sellerId;
    private $documentPath;
    private $documentType;
    private $documentVerified;
    private $rejectionReason;
    private $submittedAt;
    private $reviewedAt;
    private $reviewedBy;

    /**
     * Constructor.
     *
     * @param array $data Associative array of verification data from the database
     */
    public function __construct($data)
    {
        $this->verificationId = (int) ($data['verification_id'] ?? 0);
        $this->sellerId = (int) ($data['seller_id'] ?? 0);
        $this->documentPath = (string) ($data['document_path'] ?? '');
        $this->documentType = (string) ($data['document_type'] ?? 'id');
        $this->documentVerified = (int) ($data['document_verified'] ?? 0);
        $this->rejectionReason = isset($data['rejection_reason']) ? (string) $data['rejection_reason'] : null;
        $this->submittedAt = (string) ($data['submitted_at'] ?? '');
        $this->reviewedAt = isset($data['reviewed_at']) ? (string) $data['reviewed_at'] : null;
        $this->reviewedBy = isset($data['reviewed_by']) ? (int) $data['reviewed_by'] : null;
    }

    // ============================================================
    //  GETTERS
    // ============================================================

    public function getVerificationId()
    {
        return $this->verificationId;
    }

    public function getSellerId()
    {
        return $this->sellerId;
    }

    public function getDocumentPath()
    {
        return $this->documentPath;
    }

    public function getDocumentType()
    {
        return $this->documentType;
    }

    /**
     * Returns human-readable document type label.
     *
     * @return string
     */
    public function getDocumentTypeLabel()
    {
        $labels = [
            'id' => 'ID Document',
            'passport' => 'Passport',
            'business_license' => 'Business License',
            'tax_certificate' => 'Tax Certificate',
            'other' => 'Other Document'
        ];
        return $labels[$this->documentType] ?? ucfirst(str_replace('_', ' ', $this->documentType));
    }

    public function isVerified()
    {
        return $this->documentVerified == 1;
    }

    public function isPending()
    {
        return $this->documentVerified == 0 && $this->rejectionReason === null;
    }

    public function isRejected()
    {
        return $this->rejectionReason !== null;
    }

    public function getRejectionReason()
    {
        return $this->rejectionReason;
    }

    public function getSubmittedAt()
    {
        return $this->submittedAt;
    }

    /**
     * Returns formatted submission date.
     *
     * @param string $format
     * @return string
     */
    public function getFormattedSubmittedAt($format = 'd M Y, H:i')
    {
        return date($format, strtotime($this->submittedAt));
    }

    public function getReviewedAt()
    {
        return $this->reviewedAt;
    }

    /**
     * Returns formatted review date.
     *
     * @param string $format
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
    //  SETTERS (for admin actions)
    // ============================================================

    /**
     * Approve the verification.
     *
     * @param int $adminId Admin user ID
     * @return void
     */
    public function approve($adminId)
    {
        $this->documentVerified = 1;
        $this->rejectionReason = null;
        $this->reviewedAt = date('Y-m-d H:i:s');
        $this->reviewedBy = $adminId;
    }

    /**
     * Reject the verification.
     *
     * @param int $adminId Admin user ID
     * @param string|null $reason Rejection reason
     * @return void
     */
    public function reject($adminId, $reason = null)
    {
        $this->documentVerified = 0;
        $this->rejectionReason = $reason;
        $this->reviewedAt = date('Y-m-d H:i:s');
        $this->reviewedBy = $adminId;
    }

    /**
     * Get document URL for display.
     *
     * @return string
     */
    public function getDocumentUrl()
    {
        $baseUrl = getBaseUrl();

        if (empty($this->documentPath)) {
            return '';
        }

        if (str_starts_with($this->documentPath, 'http://') || str_starts_with($this->documentPath, 'https://')) {
            return $this->documentPath;
        }

        return $baseUrl . $this->documentPath;
    }

    /**
     * Get status badge class for UI.
     *
     * @return string
     */
    public function getStatusClass()
    {
        if ($this->isVerified()) {
            return 'status-verified';
        }
        if ($this->isRejected()) {
            return 'status-rejected';
        }
        return 'status-pending';
    }

    /**
     * Get status label for UI.
     *
     * @return string
     */
    public function getStatusLabel()
    {
        if ($this->isVerified()) {
            return 'Verified';
        }
        if ($this->isRejected()) {
            return 'Rejected';
        }
        return 'Pending Review';
    }

    /**
     * Exports verification data as array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'verification_id' => $this->verificationId,
            'seller_id' => $this->sellerId,
            'document_path' => $this->documentPath,
            'document_url' => $this->getDocumentUrl(),
            'document_type' => $this->documentType,
            'document_type_label' => $this->getDocumentTypeLabel(),
            'is_verified' => $this->isVerified(),
            'is_pending' => $this->isPending(),
            'is_rejected' => $this->isRejected(),
            'rejection_reason' => $this->rejectionReason,
            'submitted_at' => $this->submittedAt,
            'formatted_submitted_at' => $this->getFormattedSubmittedAt(),
            'reviewed_at' => $this->reviewedAt,
            'formatted_reviewed_at' => $this->getFormattedReviewedAt(),
            'reviewed_by' => $this->reviewedBy,
            'status_class' => $this->getStatusClass(),
            'status_label' => $this->getStatusLabel()
        ];
    }
}
