<?php

/**
 * ConsuTrade - Admin
 *
 * Domain class representing an administrator user.
 * Contains ONLY business logic, no database operations.
 *
 * @author Kamogelo Phale
 * @version 3.0.0
 */

class Admin extends User
{
    /**
     * Constructor.
     *
     * @param array $data User data from database
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }

    /**
     * Get the admin's display name.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->fullName . ' (Admin)';
    }

    /**
     * Check if admin can perform an action on a user.
     * Business logic: admin can manage any non-admin user.
     *
     * @param string $targetRole Role of the target user
     * @return bool
     */
    public function canManageUser(string $targetRole): bool
    {
        return $targetRole !== 'admin';
    }

    /**
     * Get admin dashboard stats from provided data.
     *
     * @param array $userStats User statistics from UserRepository
     * @param array $orderStats Order statistics from OrderRepository
     * @param array $productStats Product statistics from ProductRepository
     * @param array $reportStats Report statistics from ReportRepository
     * @return array
     */
    public function calculateDashboardStats(
        array $userStats,
        array $orderStats,
        array $productStats,
        array $reportStats
    ): array {
        return [
            'total_users' => $userStats['total_users'],
            'total_buyers' => $userStats['total_buyers'],
            'total_sellers' => $userStats['total_sellers'],
            'total_orders' => $orderStats['total_orders'],
            'total_revenue' => $orderStats['total_revenue'],
            'total_products' => $productStats['total_products'],
            'pending_reports' => $reportStats['pending_reports'],
            'pending_verifications' => $userStats['pending_verifications']
        ];
    }

    /**
     * Combine activity from different sources into sorted timeline.
     *
     * @param array $recentOrders Recent orders from OrderRepository
     * @param array $recentUsers Recent users from UserRepository
     * @param int $limit Maximum number of items to return
     * @return array
     */
    public function combineActivityTimeline(array $recentOrders, array $recentUsers, int $limit = 10): array
    {
        $activity = [];

        foreach ($recentOrders as $order) {
            $activity[] = [
                'type' => 'order',
                'title' => 'New Order #' . $order['order_id'],
                'details' => 'Order from ' . ($order['buyer_name'] ?? 'Customer'),
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

        usort($activity, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return array_slice($activity, 0, $limit);
    }

    /**
     * Check if decision is valid for seller verification.
     *
     * @param string $decision The decision (approve or reject)
     * @return bool
     */
    public function isValidVerificationDecision(string $decision): bool
    {
        return in_array($decision, ['approve', 'reject']);
    }
}
