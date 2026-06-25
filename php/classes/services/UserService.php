<?php

/**
 * ConsuTrade - UserService
 *
 * Handles user business logic including account deletion,
 * profile management, and user statistics.
 *
 * @author Kamogelo Phale
 * @version 1.0.0
 */

class UserService
{
    private mysqli $db;
    private UserRepository $userRepo;
    private CartRepository $cartRepo;
    private OrderRepository $orderRepo;
    private ProductRepository $productRepo;
    private ReviewRepository $reviewRepo;
    private ReportRepository $reportRepo;
    private Auth $auth;

    public function __construct(
        mysqli $db,
        UserRepository $userRepo,
        CartRepository $cartRepo,
        OrderRepository $orderRepo,
        ProductRepository $productRepo,
        ReviewRepository $reviewRepo,
        ReportRepository $reportRepo,
        Auth $auth
    ) {
        $this->db = $db;
        $this->userRepo = $userRepo;
        $this->cartRepo = $cartRepo;
        $this->orderRepo = $orderRepo;
        $this->productRepo = $productRepo;
        $this->reviewRepo = $reviewRepo;
        $this->reportRepo = $reportRepo;
        $this->auth = $auth;
    }

    /**
     * Delete user account and all associated data.
     */
    public function deleteAccount(int $userId, string $password): array
    {
        $user = $this->userRepo->findById($userId);

        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }

        if ($user->getRole() === 'admin') {
            return ['success' => false, 'message' => 'Admin accounts cannot be deleted'];
        }

        if (!password_verify($password, $user->getPassword())) {
            return ['success' => false, 'message' => 'Invalid password'];
        }

        $this->db->begin_transaction();

        try {
            // Delete cart items
            $this->cartRepo->deleteAllByUser($userId);

            // Delete orders where user is buyer
            $this->orderRepo->deleteByBuyer($userId);

            // For sellers, check active orders
            if ($user->getRole() === 'seller') {
                $activeOrders = $this->orderRepo->countActiveBySeller($userId);
                if ($activeOrders > 0) {
                    throw new Exception('Cannot delete account with active orders.');
                }
                $this->orderRepo->deleteBySeller($userId);
                $this->productRepo->deleteBySeller($userId);
            }

            // Delete reviews
            $this->reviewRepo->deleteByBuyer($userId);
            $this->reviewRepo->deleteBySeller($userId);

            // Delete profile image
            $this->deleteProfileImage($user->getProfileImage());

            // Delete verification if seller
            if ($user->getRole() === 'seller') {
                $this->userRepo->deleteVerification($userId);
            }

            // Delete user
            $this->userRepo->delete($userId);

            $this->db->commit();

            // Logout user
            $this->auth->logout();

            return ['success' => true, 'message' => 'Account deleted successfully'];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get admin dashboard statistics.
     */
    public function getAdminStats(): array
    {
        return [
            'total_users' => $this->userRepo->getTotalUsers(),
            'total_buyers' => $this->userRepo->countByRole('buyer'),
            'total_sellers' => $this->userRepo->countByRole('seller'),
            'pending_verifications' => $this->userRepo->getPendingVerificationsCount(),
            'total_orders' => $this->orderRepo->countAll(),
            'total_revenue' => $this->orderRepo->getTotalRevenue(),
            'pending_orders' => $this->orderRepo->countByStatus('pending'),
            'total_products' => $this->productRepo->countAll(),
            'pending_reports' => $this->reportRepo->getPendingReportsCount()
        ];
    }

    /**
     * Get seller statistics.
     */
    public function getSellerStats(int $sellerId): array
    {
        $totalProducts = $this->productRepo->countUserProducts($sellerId);
        $totalOrders = $this->orderRepo->countBySeller($sellerId);
        $completedOrders = $this->orderRepo->getSellerTotalOrders($sellerId);
        $totalRevenue = $this->orderRepo->getSellerTotalRevenue($sellerId);
        $ratingData = $this->reviewRepo->getSellerRating($sellerId);
        $pendingOrders = $this->orderRepo->countBySellerAndStatus($sellerId, 'pending');

        return [
            'total_products' => $totalProducts,
            'total_revenue' => $totalRevenue,
            'total_orders' => $totalOrders,
            'pending_orders' => $pendingOrders,
            'completed_orders' => $completedOrders,
            'avg_rating' => round($ratingData['avg_rating'] ?? 0, 1)
        ];
    }

    /**
     * Get buyer statistics.
     */
    public function getBuyerStats(int $buyerId): array
    {
        $orderStats = $this->orderRepo->findBuyerStats($buyerId);
        $reviewsCount = $this->reviewRepo->countByBuyer($buyerId);

        return [
            'total_orders' => $orderStats['total_orders'] ?? 0,
            'total_spent' => $orderStats['total_spent'] ?? 0,
            'reviews_written' => $reviewsCount
        ];
    }

    /**
     * Update user verification status.
     */
    public function updateVerification(int $sellerId, bool $verify): array
    {
        $user = $this->userRepo->findById($sellerId);
        if (!$user || $user->getRole() !== 'seller') {
            return ['success' => false, 'message' => 'Only sellers can be verified'];
        }

        // Use the existing verifySeller method
        if ($verify) {
            $result = $this->userRepo->verifySeller($sellerId);
        } else {
            $result = $this->userRepo->unverifySeller($sellerId);
        }

        return [
            'success' => $result,
            'message' => $verify ? 'Seller verified' : 'Verification removed'
        ];
    }

    /**
     * Delete profile image file.
     */
    private function deleteProfileImage(?string $profileImage): void
    {
        if (empty($profileImage)) {
            return;
        }

        $basePaths = [
            $_SERVER['DOCUMENT_ROOT'] . '/',
            $_SERVER['DOCUMENT_ROOT'] . '/www/consutrade/',
            dirname(__DIR__, 2) . '/',
        ];

        foreach ($basePaths as $basePath) {
            $fullPath = rtrim($basePath, '/') . '/' . ltrim($profileImage, '/');
            if (file_exists($fullPath)) {
                unlink($fullPath);
                break;
            }
        }
    }
}
