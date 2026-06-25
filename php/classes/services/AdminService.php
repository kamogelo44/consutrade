<?php

/**
 * ConsuTrade - AdminService
 *
 * Handles admin-specific business logic including user management,
 * seller verification, and dashboard operations.
 *
 * @author Kamogelo Phale
 * @version 1.0.0
 */

class AdminService
{
    private UserRepository $userRepo;
    private mysqli $db;

    public function __construct(
        mysqli $db,
        UserRepository $userRepo
    ) {
        $this->db = $db;
        $this->userRepo = $userRepo;
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
     * Verify seller with approval/rejection.
     */
    public function verifySeller(int $sellerId, string $decision): array
    {
        if (!in_array($decision, ['approve', 'reject'])) {
            return ['success' => false, 'message' => 'Invalid decision'];
        }

        if ($decision === 'approve') {
            $sql = "UPDATE seller_verification
                    SET document_verified = 1,
                        verified_at = NOW(),
                        verification_score = verification_score + 25
                    WHERE seller_id = ?";
        } else {
            $sql = "UPDATE seller_verification
                    SET document_verified = 0,
                        document_path = NULL,
                        document_type = NULL
                    WHERE seller_id = ?";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $sellerId);

        if (!$stmt->execute()) {
            $stmt->close();
            return ['success' => false, 'message' => 'Could not update verification'];
        }
        $stmt->close();

        // Auto-verify if score >= 100
        if ($decision === 'approve') {
            $checkSql = "SELECT verification_score FROM seller_verification WHERE seller_id = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->bind_param('i', $sellerId);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $row = $result->fetch_assoc();
            $checkStmt->close();

            if ($row && $row['verification_score'] >= 100) {
                $this->userRepo->verifySeller($sellerId);
            }
        }

        return [
            'success' => true,
            'message' => $decision === 'approve' ? 'Seller document approved' : 'Seller document rejected'
        ];
    }

    /**
     * Upload verification document for seller.
     */
    public function uploadVerification(int $sellerId, array $file, string $docType): array
    {
        $maxSize = 5 * 1024 * 1024;
        $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];

        if (!in_array($file['type'], $allowed)) {
            return ['success' => false, 'message' => 'Only JPG, PNG, or PDF files are allowed'];
        }

        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'File must be less than 5MB'];
        }

        $uploadDir = $this->getUploadDir();
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'seller_' . $sellerId . '_' . time() . '.' . $ext;
        $dest = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['success' => false, 'message' => 'Could not upload file'];
        }

        $docPath = 'uploads/verifications/' . $filename;

        // Check if verification record exists
        $checkSql = "SELECT verification_id FROM seller_verification WHERE seller_id = ?";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->bind_param('i', $sellerId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows > 0) {
            $sql = "UPDATE seller_verification
                    SET document_path = ?, document_type = ?, document_verified = 0, last_check = NOW()
                    WHERE seller_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ssi', $docPath, $docType, $sellerId);
        } else {
            $sql = "INSERT INTO seller_verification (seller_id, document_path, document_type, last_check)
                    VALUES (?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('iss', $sellerId, $docPath, $docType);
        }
        $checkStmt->close();

        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Document uploaded. Awaiting verification.'];
        }

        $stmt->close();
        unlink($dest);
        return ['success' => false, 'message' => 'Could not save document'];
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
