<?php

/**
 * ConsuTrade - UserRepository
 *
 * Handles all user database operations (excluding authentication).
 *
 * @author     Kamogelo Phale
 * @module     ITECA3-12 Web Development and e-Commerce
 * @institution Eduvos
 * @version    2.0.0
 * @since      2026
 */

class UserRepository
{
    /** @var mysqli Database connection */
    private $db;

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
    //  BASIC CRUD
    // ============================================================

    /**
     * Get user by ID.
     *
     * @param int $userId User ID
     * @return array|null
     */
    public function getById(int $userId): ?array
    {
        $sql = "SELECT user_id, full_name, email, phone, profile_image, role, location, id_verified, created_at 
                FROM users 
                WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return $row;
        }

        $stmt->close();
        return null;
    }

    /**
     * Get user by email.
     *
     * @param string $email User email
     * @return array|null
     */
    public function getByEmail(string $email): ?array
    {
        $sql = "SELECT user_id, full_name, email, phone, profile_image, role, location, id_verified, password, created_at 
                FROM users 
                WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return $row;
        }

        $stmt->close();
        return null;
    }

    /**
     * Get user by phone number.
     *
     * @param string $phone Phone number
     * @return array|null
     */
    public function getByPhone(string $phone): ?array
    {
        $sql = "SELECT user_id, full_name, email, phone, profile_image, role, location, id_verified, created_at 
                FROM users 
                WHERE phone = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $phone);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return $row;
        }

        $stmt->close();
        return null;
    }

    /**
     * Create a new user.
     *
     * @param array $userData User data (full_name, email, phone, password, role)
     * @return int|false Insert ID or false on failure
     */
    public function createUser(array $userData)
    {
        $sql = "INSERT INTO users (full_name, email, phone, password, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'sssss',
            $userData['full_name'],
            $userData['email'],
            $userData['phone'],
            $userData['password'],
            $userData['role']
        );

        if ($stmt->execute()) {
            $userId = $stmt->insert_id;
            $stmt->close();
            return $userId;
        }

        $stmt->close();
        return false;
    }

    /**
     * Get all users with optional filters.
     *
     * @param string $filter Status filter (all, active, suspended, banned)
     * @param string $search Search term
     * @param int $limit Limit results
     * @param int $offset Pagination offset
     * @return array
     */
    public function getAll(string $filter = 'all', string $search = '', int $limit = 0, int $offset = 0): array
    {
        $sql = "SELECT user_id, full_name, email, phone, profile_image, role, location, id_verified, created_at 
                FROM users 
                WHERE 1=1";
        $params = [];
        $types = "";

        if ($filter !== 'all') {
            $sql .= " AND status = ?";
            $params[] = $filter;
            $types .= "s";
        }

        if (!empty($search)) {
            $sql .= " AND (full_name LIKE ? OR email LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types .= "ss";
        }

        $sql .= " ORDER BY created_at DESC";

        if ($limit > 0) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types .= "ii";
        }

        $stmt = $this->db->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        $stmt->close();

        return $users;
    }

    /**
     * Update user profile information.
     *
     * @param int $userId User ID
     * @param array $data Data to update (full_name, phone, location)
     * @return bool
     */
    public function updateProfile(int $userId, array $data): bool
    {
        $fields = [];
        $params = [];
        $types = "";

        if (isset($data['full_name'])) {
            $fields[] = "full_name = ?";
            $params[] = $data['full_name'];
            $types .= "s";
        }
        if (isset($data['phone'])) {
            $fields[] = "phone = ?";
            $params[] = $data['phone'];
            $types .= "s";
        }
        if (isset($data['location'])) {
            $fields[] = "location = ?";
            $params[] = $data['location'];
            $types .= "s";
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $userId;
        $types .= "i";

        $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    /**
     * Update user profile image.
     *
     * @param int $userId User ID
     * @param string $imagePath Path to profile image
     * @return bool
     */
    public function updateProfileImage(int $userId, string $imagePath): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET profile_image = ? WHERE user_id = ?");
        $stmt->bind_param('si', $imagePath, $userId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // ============================================================
    //  USER MANAGEMENT (Admin)
    // ============================================================

    /**
     * Suspend a user account.
     *
     * @param int $userId User ID
     * @return bool
     */
    public function suspendUser(int $userId): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET status = 'suspended' WHERE user_id = ?");
        $stmt->bind_param('i', $userId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Reinstate a suspended user account.
     *
     * @param int $userId User ID
     * @return bool
     */
    public function reinstateUser(int $userId): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET status = 'active' WHERE user_id = ?");
        $stmt->bind_param('i', $userId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Ban a user account.
     *
     * @param int $userId User ID
     * @return bool
     */
    public function banUser(int $userId): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET status = 'banned' WHERE user_id = ?");
        $stmt->bind_param('i', $userId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Upgrade a buyer to seller.
     *
     * @param int $userId User ID
     * @return bool
     */
    public function upgradeToSeller(int $userId): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET role = 'seller' WHERE user_id = ? AND role = 'buyer'");
        $stmt->bind_param('i', $userId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // ============================================================
    //  PUBLIC PROFILES
    // ============================================================

    /**
     * Get seller public profile information.
     *
     * @param int $sellerId Seller ID
     * @return array|null
     */
    public function getSellerPublicProfile(int $sellerId): ?array
    {
        $sql = "SELECT user_id, full_name, profile_image, location, id_verified, created_at 
                FROM users 
                WHERE user_id = ? AND role = 'seller'";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $sellerId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return $row;
        }

        $stmt->close();
        return null;
    }

    /**
     * Get users by role.
     *
     * @param string $role User role (buyer, seller, admin)
     * @return array
     */
    public function getUserByRole(string $role): array
    {
        $sql = "SELECT user_id, full_name, email, phone, profile_image, location, id_verified, created_at 
                FROM users 
                WHERE role = ? 
                ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $role);
        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        $stmt->close();

        return $users;
    }

    /**
     * Get pending seller verifications.
     *
     * @return array
     */
    public function getPendingVerifications(): array
    {
        $sql = "SELECT u.user_id, u.full_name, u.email, u.created_at, sv.document_path, sv.document_type, sv.submitted_at 
                FROM users u 
                JOIN seller_verification sv ON u.user_id = sv.seller_id 
                WHERE u.role = 'seller' AND sv.document_verified = 0 
                ORDER BY sv.submitted_at ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();

        $verifications = [];
        while ($row = $result->fetch_assoc()) {
            $verifications[] = $row;
        }
        $stmt->close();

        return $verifications;
    }

    /**
     * Search users by name or email.
     *
     * @param string $query Search query
     * @return array
     */
    public function searchUsers(string $query): array
    {
        $sql = "SELECT user_id, full_name, email, phone, role, id_verified, created_at 
                FROM users 
                WHERE full_name LIKE ? OR email LIKE ? 
                ORDER BY full_name ASC 
                LIMIT 20";
        $searchParam = "%$query%";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ss', $searchParam, $searchParam);
        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        $stmt->close();

        return $users;
    }

    /**
     * Get total number of users (optionally filtered by role).
     *
     * @param string|null $role Filter by role (optional)
     * @return int
     */
    public function getTotalUsers(?string $role = null): int
    {
        if ($role) {
            $sql = "SELECT COUNT(*) as total FROM users WHERE role = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('s', $role);
        } else {
            $sql = "SELECT COUNT(*) as total FROM users";
            $stmt = $this->db->prepare($sql);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $total = (int)($result->fetch_assoc()['total'] ?? 0);
        $stmt->close();

        return $total;
    }

    // ============================================================
    //  PENDING SELLER VERIFICATIONS (Admin)
    // ============================================================

    /**
     * Get pending seller verifications with pagination.
     *
     * @param int $limit Results per page
     * @param int $offset Pagination offset
     * @return array
     */
    public function getPendingVerificationsWithPagination(int $limit = 10, int $offset = 0): array
    {
        $sql = "SELECT u.user_id, u.full_name, u.email, u.phone, u.role, u.id_verified, 
                       DATE_FORMAT(u.created_at, '%d %b %Y') as created_at,
                       sv.document_path, sv.document_type, sv.submitted_at
                FROM users u
                INNER JOIN seller_verification sv ON u.user_id = sv.seller_id
                WHERE u.role = 'seller' AND sv.document_verified = 0
                ORDER BY sv.submitted_at ASC
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = [
                'user_id'      => (int) $row['user_id'],
                'full_name'    => $row['full_name'],
                'email'        => $row['email'],
                'phone'        => $row['phone'] ?? '-',
                'role'         => $row['role'],
                'is_verified'  => false,
                'created_at'   => $row['created_at'],
                'has_document' => true,
                'document_type' => $row['document_type']
            ];
        }
        $stmt->close();

        return $users;
    }

    /**
     * Get total count of pending seller verifications.
     *
     * @return int
     */
    public function getPendingVerificationsCount(): int
    {
        $sql = "SELECT COUNT(*) as total FROM users u 
                INNER JOIN seller_verification sv ON u.user_id = sv.seller_id 
                WHERE u.role = 'seller' AND sv.document_verified = 0";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $total = (int) ($result->fetch_assoc()['total'] ?? 0);
        $stmt->close();

        return $total;
    }

    // ============================================================
    //  ADVANCED USER QUERIES (Admin)
    // ============================================================

    /**
     * Get users by role with pagination and search.
     *
     * @param string $role User role (buyer, seller, admin)
     * @param string $search Search term
     * @param int $limit Results per page
     * @param int $offset Pagination offset
     * @return array
     */
    public function getUsersByRoleWithPagination(string $role, string $search = '', int $limit = 10, int $offset = 0): array
    {
        $sql = "SELECT user_id, full_name, email, phone, profile_image, role, location, id_verified, created_at 
                FROM users 
                WHERE role = ?";
        $params = [$role];
        $types = "s";

        if (!empty($search)) {
            $sql .= " AND (full_name LIKE ? OR email LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types .= "ss";
        }

        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        $stmt->close();

        return $users;
    }

    /**
     * Get count of users by role with search.
     *
     * @param string $role User role (buyer, seller, admin)
     * @param string $search Search term
     * @return int
     */
    public function countUsersByRole(string $role, string $search = ''): int
    {
        $sql = "SELECT COUNT(*) as total FROM users WHERE role = ?";
        $params = [$role];
        $types = "s";

        if (!empty($search)) {
            $sql .= " AND (full_name LIKE ? OR email LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types .= "ss";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $total = (int) ($result->fetch_assoc()['total'] ?? 0);
        $stmt->close();

        return $total;
    }

    /**
     * Get recent users for dashboard.
     *
     * @param int $limit Number of users to return
     * @return array
     */
    public function getRecentUsers(int $limit = 5): array
    {
        $sql = "SELECT user_id, full_name, email, role, id_verified, created_at 
                FROM users 
                ORDER BY created_at DESC 
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = [
                'user_id'     => (int) $row['user_id'],
                'full_name'   => $row['full_name'],
                'email'       => $row['email'],
                'role'        => $row['role'],
                'role_class'  => $row['role'] === 'admin' ? 'role-admin' : ($row['role'] === 'seller' ? 'role-seller' : 'role-buyer'),
                'is_verified' => (bool) $row['id_verified'],
                'created_at'  => date('d M Y', strtotime($row['created_at']))
            ];
        }
        $stmt->close();

        return $users;
    }
}
