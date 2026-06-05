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
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Find user by ID and return User object
     *
     * @param int $id User ID
     * @return User|null
     */
    public function findById($id)
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
     *
     * @param string $email User email
     * @return User|null
     */
    public function findByEmail($email)
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
     *
     * @param string $phone User phone number
     * @return User|null
     */
    public function findByPhone($phone)
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
     * Find users by role
     *
     * @param string $role User role (buyer, seller, admin)
     * @return array
     */
    public function findByRole($role)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE role = ?");
        $stmt->bind_param('s', $role);
        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $this->hydrate($row);
        }
        $stmt->close();

        return $users;
    }

    /**
     * Hydrate database row into appropriate User subclass
     *
     * @param array $data Database row
     * @return User
     */
    private function hydrate($data)
    {
        $role = $data['role'] ?? 'buyer';

        switch ($role) {
            case 'admin':
                return new Admin($data, $this->db);
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
     *
     * @param int $sellerId Seller ID
     * @return SellerVerification|null
     */
    private function getSellerVerification($sellerId)
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
     *
     * @param array $userData User data
     * @return int|false
     */
    public function createUser($userData)
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
     *
     * @param int $userId User ID
     * @param array $data Profile data
     * @return bool
     */
    public function updateProfile($userId, $data)
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
     *
     * @param int $userId User ID
     * @param string $imagePath Image path
     * @return bool
     */
    public function updateProfileImage($userId, $imagePath)
    {
        $stmt = $this->db->prepare("UPDATE users SET profile_image = ? WHERE user_id = ?");
        $stmt->bind_param('si', $imagePath, $userId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Update user password
     *
     * @param int $userId User ID
     * @param string $hashedPassword New hashed password
     * @return bool
     */
    public function updatePassword($userId, $hashedPassword)
    {
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->bind_param('si', $hashedPassword, $userId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Update user status (active, suspended, banned)
     *
     * @param int $userId User ID
     * @param string $status New status
     * @return bool
     */
    public function updateStatus($userId, $status)
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
     *
     * @param string $filter Status filter
     * @param string $search Search term
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array
     */
    public function getAll($filter = 'all', $search = '', $limit = 0, $offset = 0)
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
     * Get users by role with pagination (for admin)
     *
     * @param string $role User role
     * @param string $search Search term
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array
     */
    public function getUsersByRoleWithPagination($role, $search = '', $limit = 10, $offset = 0)
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
     *
     * @param string $role User role
     * @param string $search Search term
     * @return int
     */
    public function countUsersByRole($role, $search = '')
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
     *
     * @param int $limit Number of users
     * @return array
     */
    public function getRecentUsers($limit = 5)
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
            $roleClass = $row['role'] === 'admin' ? 'role-admin' : ($row['role'] === 'seller' ? 'role-seller' : 'role-buyer');
            $users[] = [
                'user_id' => (int) $row['user_id'],
                'full_name' => $row['full_name'],
                'email' => $row['email'],
                'role' => $row['role'],
                'role_class' => $roleClass,
                'is_verified' => (bool) $row['id_verified'],
                'created_at' => date('d M Y', strtotime($row['created_at']))
            ];
        }
        $stmt->close();

        return $users;
    }

    /**
     * Get pending seller verifications with pagination
     *
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array
     */
    public function getPendingVerificationsWithPagination($limit = 10, $offset = 0)
    {
        $sql = "SELECT u.user_id, u.full_name, u.email, u.phone, u.role, u.id_verified, 
                   DATE_FORMAT(u.created_at, '%d %b %Y') as created_at,
                   sv.document_path, sv.document_type
            FROM users u
            INNER JOIN seller_verification sv ON u.user_id = sv.seller_id
            WHERE u.role = 'seller' AND sv.document_verified = 0
            ORDER BY u.created_at ASC
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
     *
     * @return int
     */
    public function getPendingVerificationsCount()
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
     *
     * @param string|null $role Optional role filter
     * @return int
     */
    public function getTotalUsers($role = null)
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
     *
     * @param int $sellerId Seller ID
     * @return array|null
     */
    public function getSellerPublicProfile($sellerId)
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
     *
     * @param string $query Search query
     * @return array
     */
    public function searchUsers($query)
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
     *
     * @param int $userId User ID
     * @return bool
     */
    public function suspendUser($userId)
    {
        return $this->updateStatus($userId, 'suspended');
    }

    /**
     * Reinstate a suspended user account
     *
     * @param int $userId User ID
     * @return bool
     */
    public function reinstateUser($userId)
    {
        return $this->updateStatus($userId, 'active');
    }

    /**
     * Ban a user account
     *
     * @param int $userId User ID
     * @return bool
     */
    public function banUser($userId)
    {
        return $this->updateStatus($userId, 'banned');
    }

    /**
     * Upgrade a buyer to seller
     *
     * @param int $userId User ID
     * @return bool
     */
    public function upgradeToSeller($userId)
    {
        $stmt = $this->db->prepare("UPDATE users SET role = 'seller' WHERE user_id = ? AND role = 'buyer'");
        $stmt->bind_param('i', $userId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Verify a seller's ID (mark as verified)
     *
     * @param int $sellerId Seller ID
     * @return bool
     */
    public function verifySeller($sellerId)
    {
        $stmt = $this->db->prepare("UPDATE users SET id_verified = 1 WHERE user_id = ? AND role = 'seller'");
        $stmt->bind_param('i', $sellerId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Unverify a seller's ID
     *
     * @param int $sellerId Seller ID
     * @return bool
     */
    public function unverifySeller($sellerId)
    {
        $stmt = $this->db->prepare("UPDATE users SET id_verified = 0 WHERE user_id = ? AND role = 'seller'");
        $stmt->bind_param('i', $sellerId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Check if email already exists
     *
     * @param string $email Email to check
     * @param int $excludeUserId Optional user ID to exclude
     * @return bool
     */
    public function emailExists($email, $excludeUserId = 0)
    {
        if ($excludeUserId > 0) {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM users WHERE email = ? AND user_id != ?");
            $stmt->bind_param('si', $email, $excludeUserId);
        } else {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM users WHERE email = ?");
            $stmt->bind_param('s', $email);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $count = (int) ($result->fetch_assoc()['count'] ?? 0);
        $stmt->close();
        return $count > 0;
    }

    /**
     * Check if phone already exists
     *
     * @param string $phone Phone to check
     * @param int $excludeUserId Optional user ID to exclude
     * @return bool
     */
    public function phoneExists($phone, $excludeUserId = 0)
    {
        if ($excludeUserId > 0) {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM users WHERE phone = ? AND user_id != ?");
            $stmt->bind_param('si', $phone, $excludeUserId);
        } else {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM users WHERE phone = ?");
            $stmt->bind_param('s', $phone);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $count = (int) ($result->fetch_assoc()['count'] ?? 0);
        $stmt->close();
        return $count > 0;
    }
}
