<?php

/**
 * ConsuTrade - UserRepository
 *
 * Handles all user database operations.
 * Returns domain models WITHOUT repositories inside them.
 *
 * @author Kamogelo Phale
 * @version 3.0.0
 */

class UserRepository
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    // ============================================================
    // CREATE
    // ============================================================

    /**
     * Create a new user.
     *
     * @param array $userData User data (full_name, email, phone, password, role)
     * @return int|false Insert ID or false on failure
     */
    public function create(array $userData): int|false
    {
        // Only insert into users table (role column removed)
        $stmt = $this->db->prepare(
            "INSERT INTO users (full_name, email, phone, password, status, created_at) 
             VALUES (?, ?, ?, ?, 'active', NOW())"
        );

        $stmt->bind_param(
            'ssss',
            $userData['full_name'],
            $userData['email'],
            $userData['phone'],
            $userData['password']
        );

        if ($stmt->execute()) {
            $userId = $stmt->insert_id;
            $stmt->close();

            // Add role to user_roles table
            if (isset($userData['role'])) {
                $this->addRole($userId, $userData['role']);
            }

            return $userId;
        }

        $stmt->close();
        return false;
    }

    /**
     * Add a role to a user.
     *
     * @param int $userId User ID
     * @param string $role Role (admin, seller, buyer)
     * @return bool
     */
    public function addRole(int $userId, string $role): bool
    {
        $stmt = $this->db->prepare("INSERT INTO user_roles (user_id, role) VALUES (?, ?)");
        $stmt->bind_param('is', $userId, $role);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Remove a role from a user.
     *
     * @param int $userId User ID
     * @param string $role Role to remove
     * @return bool
     */
    public function removeRole(int $userId, string $role): bool
    {
        $stmt = $this->db->prepare("DELETE FROM user_roles WHERE user_id = ? AND role = ?");
        $stmt->bind_param('is', $userId, $role);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Get all roles for a user.
     *
     * @param int $userId User ID
     * @return array
     */
    public function getUserRoles(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT role FROM user_roles WHERE user_id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $roles = [];
        while ($row = $result->fetch_assoc()) {
            $roles[] = $row['role'];
        }
        $stmt->close();

        return $roles;
    }

    // ============================================================
    // READ
    // ============================================================

    /**
     * Find user by ID and return User object.
     *
     * @param int $id User ID
     * @return User|null
     */
    public function findById(int $id): ?User
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            // Get roles
            $row['roles'] = $this->getUserRoles($id);
            return $this->hydrate($row);
        }

        $stmt->close();
        return null;
    }

    /**
     * Find user by email and return User object.
     *
     * @param string $email User email
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            $row['roles'] = $this->getUserRoles($row['user_id']);
            return $this->hydrate($row);
        }

        $stmt->close();
        return null;
    }

    /**
     * Find user by phone and return User object.
     *
     * @param string $phone User phone number
     * @return User|null
     */
    public function findByPhone(string $phone): ?User
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE phone = ?");
        $stmt->bind_param('s', $phone);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            $row['roles'] = $this->getUserRoles($row['user_id']);
            return $this->hydrate($row);
        }

        $stmt->close();
        return null;
    }

    /**
     * Find users by role.
     *
     * @param string $role User role (buyer, seller, admin)
     * @return array Array of User objects
     */
    public function findByRole(string $role): array
    {
        $stmt = $this->db->prepare(
            "SELECT u.* FROM users u 
             INNER JOIN user_roles ur ON u.user_id = ur.user_id 
             WHERE ur.role = ?"
        );
        $stmt->bind_param('s', $role);
        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $row['roles'] = $this->getUserRoles($row['user_id']);
            $users[] = $this->hydrate($row);
        }
        $stmt->close();

        return $users;
    }

    /**
     * Get user checkout info (name, email, phone).
     * Used during checkout flow.
     *
     * @param int $userId User ID
     * @return array|null
     */
    public function findCheckoutInfo(int $userId): ?array
    {
        $sql = "SELECT full_name, email, phone FROM users WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user;
    }

    /**
     * Get all users as array (for admin listing).
     *
     * @param string $filter Role filter
     * @param string $search Search term
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array
     */
    public function findAll(string $filter = 'all', string $search = '', int $limit = 0, int $offset = 0): array
    {
        $sql = "SELECT u.user_id, u.full_name, u.email, u.phone, u.profile_image, u.location, u.id_verified, u.status, u.created_at,
                       GROUP_CONCAT(ur.role) as roles
                FROM users u
                LEFT JOIN user_roles ur ON u.user_id = ur.user_id
                WHERE 1=1";
        $params = [];
        $types = "";

        if ($filter !== 'all') {
            $sql .= " AND ur.role = ?";
            $params[] = $filter;
            $types .= "s";
        }

        if (!empty($search)) {
            $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types .= "ss";
        }

        $sql .= " GROUP BY u.user_id ORDER BY u.created_at DESC";

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
            $row['roles'] = $row['roles'] ? explode(',', $row['roles']) : ['buyer'];
            $row['has_document'] = $this->userHasDocument($row);
            $users[] = $row;
        }
        $stmt->close();

        return $users;
    }

    /**
     * Get users by role with pagination (for admin).
     *
     * @param string $role User role
     * @param string $search Search term
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array
     */
    public function findByRoleWithPagination(string $role, string $search = '', int $limit = 10, int $offset = 0): array
    {
        $sql = "SELECT u.user_id, u.full_name, u.email, u.phone, u.profile_image, u.location, u.id_verified, u.status, u.created_at,
                       GROUP_CONCAT(ur.role) as roles
                FROM users u
                INNER JOIN user_roles ur ON u.user_id = ur.user_id
                WHERE ur.role = ?";
        $params = [$role];
        $types = "s";

        if (!empty($search)) {
            $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types .= "ss";
        }

        $sql .= " GROUP BY u.user_id ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $row['roles'] = $row['roles'] ? explode(',', $row['roles']) : ['buyer'];
            $row['has_document'] = $this->userHasDocument($row);
            $users[] = $row;
        }
        $stmt->close();

        return $users;
    }

    /**
     * Get pending seller verifications with pagination.
     *
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array
     */
    public function findPendingVerifications(int $limit = 10, int $offset = 0): array
    {
        $sql = "SELECT u.user_id, u.full_name, u.email, u.phone, u.id_verified, u.status,
                       DATE_FORMAT(u.created_at, '%d %b %Y') as created_at,
                       sv.document_path, sv.document_type,
                       GROUP_CONCAT(ur.role) as roles
                FROM users u
                INNER JOIN user_roles ur ON u.user_id = ur.user_id
                INNER JOIN seller_verification sv ON u.user_id = sv.seller_id
                WHERE ur.role = 'seller' AND sv.document_verified = 0
                GROUP BY u.user_id
                ORDER BY u.created_at ASC
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $row['roles'] = $row['roles'] ? explode(',', $row['roles']) : ['buyer'];
            $users[] = [
                'user_id' => (int) $row['user_id'],
                'full_name' => $row['full_name'],
                'email' => $row['email'],
                'phone' => $row['phone'] ?? '-',
                'roles' => $row['roles'],
                'is_verified' => false,
                'status' => $row['status'] ?? 'active',
                'created_at' => $row['created_at'],
                'has_document' => true,
                'document_type' => $row['document_type']
            ];
        }
        $stmt->close();

        return $users;
    }

    /**
     * Get recent users for admin dashboard.
     *
     * @param int $limit Number of users
     * @return array
     */
    public function findRecent(int $limit = 5): array
    {
        $sql = "SELECT u.user_id, u.full_name, u.email, u.id_verified, u.status, u.created_at,
                       GROUP_CONCAT(ur.role) as roles
                FROM users u
                LEFT JOIN user_roles ur ON u.user_id = ur.user_id
                GROUP BY u.user_id
                ORDER BY u.created_at DESC 
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $roles = $row['roles'] ? explode(',', $row['roles']) : ['buyer'];
            $primaryRole = $roles[0];
            $roleClass = match ($primaryRole) {
                'admin' => 'role-admin',
                'seller' => 'role-seller',
                default => 'role-buyer'
            };

            $users[] = [
                'user_id' => (int) $row['user_id'],
                'full_name' => $row['full_name'],
                'email' => $row['email'],
                'roles' => $roles,
                'role' => $primaryRole,
                'role_class' => $roleClass,
                'is_verified' => (bool) $row['id_verified'],
                'status' => $row['status'] ?? 'active',
                'created_at' => date('d M Y', strtotime($row['created_at']))
            ];
        }
        $stmt->close();

        return $users;
    }

    /**
     * Get seller public profile data (array, not object).
     *
     * @param int $sellerId Seller ID
     * @return array|null
     */
    public function getSellerPublicProfile(int $sellerId): ?array
    {
        $sql = "SELECT u.user_id, u.full_name, u.profile_image, u.location, u.id_verified, u.status, u.created_at,
                       GROUP_CONCAT(ur.role) as roles
                FROM users u
                INNER JOIN user_roles ur ON u.user_id = ur.user_id
                WHERE u.user_id = ? AND ur.role = 'seller'
                GROUP BY u.user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $sellerId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            $row['roles'] = $row['roles'] ? explode(',', $row['roles']) : ['buyer'];
            return $row;
        }

        $stmt->close();
        return null;
    }

    /**
     * Search users by name or email.
     *
     * @param string $query Search query
     * @return array
     */
    public function search(string $query): array
    {
        $sql = "SELECT u.user_id, u.full_name, u.email, u.phone, u.id_verified, u.status, u.created_at,
                       GROUP_CONCAT(ur.role) as roles
                FROM users u
                LEFT JOIN user_roles ur ON u.user_id = ur.user_id
                WHERE u.full_name LIKE ? OR u.email LIKE ? 
                GROUP BY u.user_id
                ORDER BY u.full_name ASC 
                LIMIT 20";
        $searchParam = "%$query%";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ss', $searchParam, $searchParam);
        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $row['roles'] = $row['roles'] ? explode(',', $row['roles']) : ['buyer'];
            $users[] = $row;
        }
        $stmt->close();

        return $users;
    }

    /**
     * Check if email already exists.
     *
     * @param string $email Email to check
     * @param int $excludeUserId Optional user ID to exclude
     * @return bool
     */
    public function emailExists(string $email, int $excludeUserId = 0): bool
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
     * Check if phone already exists.
     *
     * @param string $phone Phone to check
     * @param int $excludeUserId Optional user ID to exclude
     * @return bool
     */
    public function phoneExists(string $phone, int $excludeUserId = 0): bool
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

    // ============================================================
    // UPDATE
    // ============================================================

    /**
     * Update user profile.
     *
     * @param int $userId User ID
     * @param array $data Profile data (full_name, phone, location)
     * @return bool
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
     * Update user profile image.
     *
     * @param int $userId User ID
     * @param string $imagePath Image path
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

    /**
     * Update user password.
     *
     * @param int $userId User ID
     * @param string $hashedPassword New hashed password
     * @return bool
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
     * Update user status (active, suspended, banned).
     *
     * @param int $userId User ID
     * @param string $status New status
     * @return bool
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
     * Verify a seller's ID (mark as verified).
     *
     * @param int $sellerId Seller ID
     * @return bool
     */
    public function verifySeller(int $sellerId): bool
    {
        $this->db->begin_transaction();

        try {
            // 1. Update users table
            $stmt = $this->db->prepare("UPDATE users SET id_verified = 1 WHERE user_id = ?");
            $stmt->bind_param('i', $sellerId);
            if (!$stmt->execute()) {
                throw new Exception('Failed to update users table');
            }
            $stmt->close();

            // 2. Update seller_verification table
            $stmt2 = $this->db->prepare("UPDATE seller_verification 
                                     SET document_verified = 1, 
                                         verified_at = NOW() 
                                     WHERE seller_id = ?");
            $stmt2->bind_param('i', $sellerId);
            if (!$stmt2->execute()) {
                throw new Exception('Failed to update seller_verification table');
            }
            $stmt2->close();

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("verifySeller error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Unverify a seller's ID.
     *
     * @param int $sellerId Seller ID
     * @return bool
     */
    public function unverifySeller(int $sellerId): bool
    {
        $this->db->begin_transaction();

        try {
            // 1. Update users table
            $stmt = $this->db->prepare("UPDATE users SET id_verified = 0 WHERE user_id = ?");
            $stmt->bind_param('i', $sellerId);
            if (!$stmt->execute()) {
                throw new Exception('Failed to update users table');
            }
            $stmt->close();

            // 2. Update seller_verification table
            $stmt2 = $this->db->prepare("UPDATE seller_verification 
                                     SET document_verified = 0, 
                                         verified_at = NULL 
                                     WHERE seller_id = ?");
            $stmt2->bind_param('i', $sellerId);
            if (!$stmt2->execute()) {
                throw new Exception('Failed to update seller_verification table');
            }
            $stmt2->close();

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("unverifySeller error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Upgrade a buyer to seller (adds seller role).
     *
     * @param int $userId User ID
     * @return bool
     */
    public function upgradeToSeller(int $userId): bool
    {
        // Check if user already has seller role
        $roles = $this->getUserRoles($userId);
        if (in_array('seller', $roles)) {
            return true;
        }
        return $this->addRole($userId, 'seller');
    }

    // ============================================================
    // DELETE
    // ============================================================

    /**
     * Suspend a user account.
     *
     * @param int $userId User ID
     * @return bool
     */
    public function suspend(int $userId): bool
    {
        return $this->updateStatus($userId, 'suspended');
    }

    /**
     * Reinstate a suspended user account.
     *
     * @param int $userId User ID
     * @return bool
     */
    public function reinstate(int $userId): bool
    {
        return $this->updateStatus($userId, 'active');
    }

    /**
     * Ban a user account.
     *
     * @param int $userId User ID
     * @return bool
     */
    public function ban(int $userId): bool
    {
        return $this->updateStatus($userId, 'banned');
    }

    /**
     * Delete seller verification record.
     *
     * @param int $sellerId Seller ID
     * @return bool
     */
    public function deleteVerification(int $sellerId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM seller_verification WHERE seller_id = ?");
        $stmt->bind_param('i', $sellerId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Delete user account.
     *
     * @param int $userId User ID
     * @return bool
     */
    public function delete(int $userId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param('i', $userId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // ============================================================
    // COUNTS / STATISTICS
    // ============================================================

    /**
     * Get total user count (optionally by role).
     *
     * @param string|null $role Optional role filter
     * @return int
     */
    public function count(?string $role = null): int
    {
        if ($role) {
            $stmt = $this->db->prepare(
                "SELECT COUNT(DISTINCT u.user_id) as total 
                 FROM users u 
                 INNER JOIN user_roles ur ON u.user_id = ur.user_id 
                 WHERE ur.role = ?"
            );
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
     * Count users by role.
     *
     * @param string $role User role
     * @return int
     */
    public function countByRole(string $role): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(DISTINCT u.user_id) as count 
             FROM users u 
             INNER JOIN user_roles ur ON u.user_id = ur.user_id 
             WHERE ur.role = ? AND u.status = 'active'"
        );
        $stmt->bind_param('s', $role);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return (int)($row['count'] ?? 0);
    }

    /**
     * Count users by role (for admin pagination).
     *
     * @param string $role User role
     * @param string $search Search term
     * @return int
     */
    public function countUsersByRole(string $role, string $search = ''): int
    {
        if ($role === 'all') {
            $sql = "SELECT COUNT(DISTINCT u.user_id) as total FROM users u WHERE 1=1";
            $params = [];
            $types = "";

            if (!empty($search)) {
                $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $types .= "ss";
            }
        } else {
            $sql = "SELECT COUNT(DISTINCT u.user_id) as total 
                    FROM users u 
                    INNER JOIN user_roles ur ON u.user_id = ur.user_id 
                    WHERE ur.role = ?";
            $params = [$role];
            $types = "s";

            if (!empty($search)) {
                $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $types .= "ss";
            }
        }

        $stmt = $this->db->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $total = (int) ($result->fetch_assoc()['total'] ?? 0);
        $stmt->close();

        return $total;
    }

    /**
     * Get count of pending seller verifications.
     *
     * @return int
     */
    public function getPendingVerificationsCount(): int
    {
        $sql = "SELECT COUNT(DISTINCT u.user_id) as total 
                FROM users u 
                INNER JOIN user_roles ur ON u.user_id = ur.user_id 
                INNER JOIN seller_verification sv ON u.user_id = sv.seller_id 
                WHERE ur.role = 'seller' AND sv.document_verified = 0";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $total = (int) ($result->fetch_assoc()['total'] ?? 0);
        $stmt->close();

        return $total;
    }

    /**
     * Count pending verifications (alias).
     *
     * @return int
     */
    public function countPendingVerifications(): int
    {
        return $this->getPendingVerificationsCount();
    }

    /**
     * Get user statistics for admin dashboard.
     *
     * @return array
     */
    public function getStats(): array
    {
        return [
            'total_users' => $this->count(),
            'total_buyers' => $this->count('buyer'),
            'total_sellers' => $this->count('seller'),
            'pending_verifications' => $this->getPendingVerificationsCount()
        ];
    }

    /**
     * Get total user count (alias).
     *
     * @param string|null $role Optional role filter
     * @return int
     */
    public function getTotalUsers(?string $role = null): int
    {
        return $this->count($role);
    }

    // ============================================================
    // PRIVATE HELPERS
    // ============================================================

    /**
     * Check if a user has a verification document.
     * Only applies to sellers, returns false for other roles.
     *
     * @param array $row User row data
     * @return bool True if the user is a seller with a document
     */
    private function userHasDocument(array $row): bool
    {
        $roles = $row['roles'] ?? [];
        if (!in_array('seller', $roles)) {
            return false;
        }

        $stmt = $this->db->prepare("SELECT document_path FROM seller_verification WHERE seller_id = ?");
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('i', $row['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $doc = $result->fetch_assoc();
        $stmt->close();

        return $doc && !empty($doc['document_path']);
    }

    /**
     * Get seller verification as domain object.
     *
     * @param int $sellerId The seller's user ID
     * @return SellerVerification|null
     */
    public function findVerification(int $sellerId): ?SellerVerification
    {
        $stmt = $this->db->prepare("SELECT * FROM seller_verification WHERE seller_id = ?");
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $sellerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row ? new SellerVerification($row) : null;
    }

    /**
     * Hydrate database row into appropriate User subclass.
     * NO repositories are injected into domain models!
     *
     * @param array $data Database row with roles included
     * @return User
     */
    private function hydrate(array $data): User
    {
        $roles = $data['roles'] ?? ['buyer'];
        $primaryRole = $this->getPrimaryRole($roles);

        switch ($primaryRole) {
            case 'admin':
                return new Admin($data);

            case 'seller':
                $verification = $this->findVerification($data['user_id']);
                return new Seller($data, $verification);

            case 'buyer':
            default:
                return new Buyer($data);
        }
    }

    /**
     * Get primary role from roles array.
     * Priority: admin > seller > buyer
     *
     * @param array $roles
     * @return string
     */
    private function getPrimaryRole(array $roles): string
    {
        $priority = ['admin', 'seller', 'buyer'];
        foreach ($priority as $role) {
            if (in_array($role, $roles)) {
                return $role;
            }
        }
        return 'buyer';
    }
}
