<?php

/**
 * ConsuTrade - UserRepository
 *
 * Handles all user database operations.
 *
 * @author Kamogelo Phale
 * @version 2.0.0
 */
class UserRepository
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    /**
     * Find user by ID and return User object
     */
    public function findById(int $id): ?User
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->bind_param('i', $id);
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
     * Find user by email and return User object
     */
    public function findByEmail(string $email): ?User
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
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
     * Find user by phone and return User object
     */
    public function findByPhone(string $phone): ?User
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE phone = ?");
        $stmt->bind_param('s', $phone);
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
     * Hydrate database row into appropriate User subclass
     */
    private function hydrate(array $data): User
    {
        $role = $data['role'] ?? 'buyer';

        switch ($role) {
            case 'admin':
                return new Admin($data);
            case 'seller':
                $verification = $this->getSellerVerification($data['user_id']);
                $productRepo = new ProductRepository($this->db);
                $orderRepo = new OrderRepository($this->db);
                return new Seller($data, $productRepo, $orderRepo, $verification);
            default:
                $cartRepo = new CartRepository($this->db);
                $orderRepo = new OrderRepository($this->db);
                return new Buyer($data, $cartRepo, $orderRepo);
        }
    }

    /**
     * Get seller verification data
     */
    private function getSellerVerification(int $sellerId): ?SellerVerification
    {
        $stmt = $this->db->prepare("SELECT * FROM seller_verification WHERE seller_id = ?");
        $stmt->bind_param('i', $sellerId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return new SellerVerification($row);
        }

        $stmt->close();
        return null;
    }

    /**
     * Create a new user
     */
    public function createUser(array $userData): int|false
    {
        $stmt = $this->db->prepare(
            "INSERT INTO users (full_name, email, phone, password, role, created_at) 
             VALUES (?, ?, ?, ?, ?, NOW())"
        );

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
     * Update user profile
     */
    public function updateProfile(int $userId, array $data): bool
    {
        $fields = [];
        $params = [];
        $types = '';

        if (isset($data['full_name'])) {
            $fields[] = "full_name = ?";
            $params[] = $data['full_name'];
            $types .= 's';
        }
        if (isset($data['phone'])) {
            $fields[] = "phone = ?";
            $params[] = $data['phone'];
            $types .= 's';
        }
        if (isset($data['location'])) {
            $fields[] = "location = ?";
            $params[] = $data['location'];
            $types .= 's';
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $userId;
        $types .= 'i';

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    /**
     * Update user profile image
     */
    public function updateProfileImage(int $userId, string $imagePath): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET profile_image = ? WHERE user_id = ?");
        $stmt->bind_param('si', $imagePath, $userId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Update user password
     */
    public function updatePassword(int $userId, string $hashedPassword): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->bind_param('si', $hashedPassword, $userId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Update user status (active, suspended, banned)
     */
    public function updateStatus(int $userId, string $status): bool
    {
        $validStatuses = ['active', 'suspended', 'banned'];
        if (!in_array($status, $validStatuses)) {
            return false;
        }

        $stmt = $this->db->prepare("UPDATE users SET status = ? WHERE user_id = ?");
        $stmt->bind_param('si', $status, $userId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Get all users as array (for admin listing)
     */
    public function getAll(string $filter = 'all', string $search = '', int $limit = 0, int $offset = 0): array
    {
        $sql = "SELECT user_id, full_name, email, phone, profile_image, role, location, id_verified, created_at, status
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
     * Get users by role with pagination (for admin)
     */
    public function getUsersByRoleWithPagination(string $role, string $search = '', int $limit = 10, int $offset = 0): array
    {
        $sql = "SELECT user_id, full_name, email, phone, profile_image, role, location, id_verified, created_at, status
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
     * Count users by role (for admin pagination)
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
     * Get recent users for admin dashboard
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
                'user_id' => (int) $row['user_id'],
                'full_name' => $row['full_name'],
                'email' => $row['email'],
                'role' => $row['role'],
                'role_class' => $row['role'] === 'admin' ? 'role-admin' : ($row['role'] === 'seller' ? 'role-seller' : 'role-buyer'),
                'is_verified' => (bool) $row['id_verified'],
                'created_at' => date('d M Y', strtotime($row['created_at']))
            ];
        }
        $stmt->close();

        return $users;
    }

    /**
     * Get pending seller verifications with pagination
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
                'user_id' => (int) $row['user_id'],
                'full_name' => $row['full_name'],
                'email' => $row['email'],
                'phone' => $row['phone'] ?? '-',
                'role' => $row['role'],
                'is_verified' => false,
                'created_at' => $row['created_at'],
                'has_document' => true,
                'document_type' => $row['document_type']
            ];
        }
        $stmt->close();

        return $users;
    }

    /**
     * Get count of pending seller verifications
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

    /**
     * Get total user count (optionally by role)
     */
    public function getTotalUsers(?string $role = null): int
    {
        if ($role) {
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM users WHERE role = ?");
            $stmt->bind_param('s', $role);
        } else {
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM users");
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $total = (int) ($result->fetch_assoc()['total'] ?? 0);
        $stmt->close();

        return $total;
    }

    /**
     * Get seller public profile
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
     * Search users by name or email
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
     * Suspend a user account
     */
    public function suspendUser(int $userId): bool
    {
        return $this->updateStatus($userId, 'suspended');
    }

    /**
     * Reinstate a suspended user account
     */
    public function reinstateUser(int $userId): bool
    {
        return $this->updateStatus($userId, 'active');
    }

    /**
     * Ban a user account
     */
    public function banUser(int $userId): bool
    {
        return $this->updateStatus($userId, 'banned');
    }

    /**
     * Upgrade a buyer to seller
     */
    public function upgradeToSeller(int $userId): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET role = 'seller' WHERE user_id = ? AND role = 'buyer'");
        $stmt->bind_param('i', $userId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
