<?php

/**
 * ConsuTrade - AdminService
 *
 * Handles admin-specific business logic including user management,
 * seller verification, and dashboard operations.
 *
 * @author Kamogelo Phale
 * @version 1.1.0
 */

class AdminService
{
    private UserService $userService;
    private UserRepository $userRepo;
    private mysqli $db;

    public function __construct(
        mysqli $db,
        UserRepository $userRepo,
        UserService $userService
    ) {
        $this->db = $db;
        $this->userRepo = $userRepo;
        $this->userService = $userService;
    }

    /**
     * Get users with filtering and pagination.
     */
    public function getUsers(string $roleFilter = 'all', string $search = '', int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;

        if ($roleFilter === 'pending') {
            $total = $this->userRepo->getPendingVerificationsCount();
            $users = $this->userRepo->findPendingVerifications($limit, $offset);

            return [
                'users' => $users,
                'total' => $total,
                'total_pages' => ceil($total / $limit),
                'current_page' => $page
            ];
        }

        if ($roleFilter !== 'all') {
            $total = $this->userRepo->countUsersByRole($roleFilter, $search);
            $users = $this->userRepo->findByRoleWithPagination($roleFilter, $search, $limit, $offset);
        } else {
            $users = $this->userRepo->findAll('all', $search, $limit, $offset);
            $total = $this->userRepo->countUsersByRole('all', $search);
        }

        return [
            'users' => $users,
            'total' => $total,
            'total_pages' => ceil($total / $limit),
            'current_page' => $page
        ];
    }

    /**
     * Get a seller's verification document data for admin review.
     *
     * @param int $sellerId The seller's user ID
     * @return array|null Returns document data or null if not found
     */
    public function getVerificationDocument(int $sellerId): ?array
    {
        $user = $this->userRepo->findById($sellerId);
        if (!$user || $user->getRole() !== 'seller') {
            return null;
        }

        $verification = $this->userRepo->findVerification($sellerId);
        if (!$verification || empty($verification->getDocumentPath())) {
            return null;
        }

        return [
            'document_path' => $verification->getDocumentPath(),
            'document_type' => $verification->getDocumentType(),
            'submitted_at' => $verification->getSubmittedAt()
        ];
    }

    /**
     * Verify seller with approval/rejection.
     * Updates both seller_verification and users tables.
     */
    public function verifySeller(int $sellerId, string $decision): array
    {
        if (!in_array($decision, ['approve', 'reject'])) {
            return ['success' => false, 'message' => 'Invalid decision'];
        }

        try {
            if ($decision === 'approve') {
                // Use UserRepository - it now updates BOTH tables
                $result = $this->userRepo->verifySeller($sellerId);

                if ($result) {
                    return ['success' => true, 'message' => 'Seller approved and verified.'];
                } else {
                    return ['success' => false, 'message' => 'Failed to verify seller.'];
                }
            } else {
                // Reject - clear verification from both tables
                $result = $this->userRepo->unverifySeller($sellerId);

                if ($result) {
                    return ['success' => true, 'message' => 'Seller document rejected.'];
                } else {
                    return ['success' => false, 'message' => 'Failed to reject seller document.'];
                }
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Upload verification document for seller.
     * 
     * @param int $sellerId The seller's user ID
     * @param array $file The uploaded file from $_FILES
     * @param string $docType The document type (id, proof_address)
     * @return array ['success' => bool, 'message' => string]
     */
    public function uploadVerification(int $sellerId, array $file, string $docType): array
    {
        // Validate document type - only allow id and proof_address
        $validTypes = ['id', 'proof_address'];
        if (!in_array($docType, $validTypes)) {
            return ['success' => false, 'message' => 'Invalid document type. Only ID and Proof of Address are accepted.'];
        }
        // Validate file size (5MB max)
        $maxSize = 5 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'File must be less than 5MB.'];
        }

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            return ['success' => false, 'message' => 'Only JPG, PNG, or PDF files are allowed.'];
        }

        // Get upload directory
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/verifications/';

        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                error_log("Failed to create upload directory: $uploadDir");
                return ['success' => false, 'message' => 'Could not create upload directory.'];
            }
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'seller_' . $sellerId . '_' . time() . '.' . $extension;
        $destination = $uploadDir . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            error_log("Failed to move uploaded file to: $destination");
            return ['success' => false, 'message' => 'Could not save the uploaded file.'];
        }

        // Set file permissions
        chmod($destination, 0644);

        // Store relative path in database
        $docPath = 'uploads/verifications/' . $filename;

        // Check if verification record exists
        $checkSql = "SELECT verification_id FROM seller_verification WHERE seller_id = ?";
        $checkStmt = $this->db->prepare($checkSql);
        if (!$checkStmt) {
            return ['success' => false, 'message' => 'Database error.'];
        }
        $checkStmt->bind_param('i', $sellerId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $exists = $result->num_rows > 0;
        $checkStmt->close();

        if ($exists) {
            // Update existing record
            $sql = "UPDATE seller_verification 
                SET document_path = ?, 
                    document_type = ?, 
                    document_verified = 0, 
                    submitted_at = NOW(),
                    last_check = NOW()
                WHERE seller_id = ?";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                return ['success' => false, 'message' => 'Database error.'];
            }
            $stmt->bind_param('ssi', $docPath, $docType, $sellerId);
        } else {
            // Insert new record
            $sql = "INSERT INTO seller_verification 
                (seller_id, document_path, document_type, submitted_at, last_check) 
                VALUES (?, ?, ?, NOW(), NOW())";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                return ['success' => false, 'message' => 'Database error.'];
            }
            $stmt->bind_param('iss', $sellerId, $docPath, $docType);
        }

        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Document uploaded successfully. Awaiting verification.'];
        }

        $stmt->close();
        // Delete the file if database insert failed
        if (file_exists($destination)) {
            unlink($destination);
        }
        return ['success' => false, 'message' => 'Could not save document record.'];
    }

    /**
     * Get upload directory path.
     */
    private function getUploadDir(): string
    {
        $paths = [
            dirname(__DIR__, 2) . '/uploads/verifications/',
            __DIR__ . '/../../uploads/verifications/',
            $_SERVER['DOCUMENT_ROOT'] . '/uploads/verifications/',
        ];

        foreach ($paths as $path) {
            if (is_dir(dirname($path)) || mkdir(dirname($path), 0777, true)) {
                if (!file_exists($path)) {
                    mkdir($path, 0777, true);
                }
                return $path;
            }
        }

        return __DIR__ . '/../../uploads/verifications/';
    }
}
