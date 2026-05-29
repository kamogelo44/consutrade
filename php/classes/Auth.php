<?php
/**
 * ConsuTrade - Auth Class
 * Author: Kamogelo Phale
 * 
 * Handles ALL authentication and session management.
 * This is the SINGLE SOURCE OF TRUTH for login/logout/session.
 */

class Auth
{
    /** @var mysqli Database connection */
    private $db;
    
    /** @var UserRepository */
    private $userRepo;

    /**
     * Constructor.
     *
     * @param mysqli $db Database connection
     */
    public function __construct(mysqli $db)
    {
        $this->db = $db;
        $this->userRepo = new UserRepository($db);
    }

    /**
     * Get user repository instance.
     *
     * @return UserRepository
     */
    public function getUserRepo(): UserRepository
    {
        return $this->userRepo;
    }

    /**
     * Login user (any role).
     *
     * @param string $email User email
     * @param string $password User password
     * @return array ['success' => bool, 'message' => string, 'role' => string|null]
     */
    public function login(string $email, string $password): array
    {
        $user = $this->userRepo->getByEmail($email);
        
        // Generic error message - same for both cases (security best practice)
        $genericError = 'Invalid email or password.';
        
        if (!$user) {
            return ['success' => false, 'message' => $genericError, 'role' => null];
        }
        
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => $genericError, 'role' => null];
        }
        
        // Check user status
        $status = $user['status'] ?? 'active';
        if ($status === 'suspended') {
            return ['success' => false, 'message' => 'Your account has been suspended. Please contact support.', 'role' => null];
        }
        if ($status === 'banned') {
            return ['success' => false, 'message' => 'Your account has been banned.', 'role' => null];
        }
        
        // Start session based on role
        $this->startSessionForRole($user['role']);
        
        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['profile_image'] = $user['profile_image'] ?? '';
        $_SESSION['logged_in'] = true;
        
        // Update cart count in session
        $this->updateCartCountInSession($user['user_id']);
        
        return ['success' => true, 'message' => 'Login successful', 'role' => $user['role']];
    }

    /**
     * Start appropriate session based on user role.
     *
     * @param string $role User role
     */
    private function startSessionForRole(string $role): void
    {
        // Close any existing session
        if (session_status() !== PHP_SESSION_NONE) {
            session_write_close();
        }
        
        // Set session name based on role
        if ($role === 'admin') {
            session_name('CONSUTRADE_ADMIN_SESSION');
        } elseif ($role === 'seller') {
            session_name('CONSUTRADE_SELLER_SESSION');
        } else {
            session_name('CONSUTRADE_USER_SESSION');
        }
        
        session_start();
    }

    /**
     * Update cart count in session.
     *
     * @param int $userId User ID
     */
    public function updateCartCountInSession(int $userId): void
    {
        $stmt = $this->db->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $_SESSION['cart_count'] = (int)($row['total'] ?? 0);
        $stmt->close();
    }

    /**
     * Update cart count (public method for endpoints).
     */
    public function updateCartCount(): void
    {
        if ($this->isLoggedIn() && isset($_SESSION['user_id'])) {
            $this->updateCartCountInSession($_SESSION['user_id']);
        }
    }

    /**
     * Get current user data from session.
     *
     * @return array|null
     */
    public function getCurrentUser(): ?array
    {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'user_id' => $_SESSION['user_id'] ?? null,
            'full_name' => $_SESSION['full_name'] ?? null,
            'email' => $_SESSION['email'] ?? null,
            'role' => $_SESSION['role'] ?? null,
            'profile_image' => $_SESSION['profile_image'] ?? null
        ];
    }

    /**
     * Check if any user is logged in.
     *
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Check if admin is logged in.
     *
     * @return bool
     */
    public function isAdminLoggedIn(): bool
    {
        return $this->isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
    }

    /**
     * Check if seller is logged in.
     *
     * @return bool
     */
    public function isSellerLoggedIn(): bool
    {
        return $this->isLoggedIn() && ($_SESSION['role'] ?? '') === 'seller';
    }

    /**
     * Check if buyer is logged in.
     *
     * @return bool
     */
    public function isBuyerLoggedIn(): bool
    {
        return $this->isLoggedIn() && ($_SESSION['role'] ?? '') === 'buyer';
    }

    /**
     * Logout user (any role).
     */
    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        session_unset();
        session_destroy();
        
        // Also clear session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
    }

    /**
     * Auto-detect and initialize appropriate session based on page.
     *
     * @return array Session data
     */
    public function initAppSession(): array
    {
        $script_path = $_SERVER['SCRIPT_NAME'];
        
        // Admin login page - no session
        if (strpos($script_path, 'admin/login.php') !== false) {
            return ['current_user' => null, 'is_logged_in' => false, 'current_user_id' => null];
        }
        
        // Check for seller pages
        $is_seller_page = (
            strpos($script_path, 'seller-dashboard.php') !== false ||
            strpos($script_path, 'seller-profile.php') !== false ||
            strpos($script_path, 'admin/my-orders.php') !== false ||
            strpos($script_path, 'my-products.php') !== false ||
            strpos($script_path, 'add-product.php') !== false ||
            strpos($script_path, 'edit-product.php') !== false
        );
        
        // Seller pages
        if ($is_seller_page) {
            if (session_status() === PHP_SESSION_NONE) {
                session_name('CONSUTRADE_SELLER_SESSION');
                session_start();
            }
            if ($this->isSellerLoggedIn()) {
                $user = $this->getCurrentUser();
                return [
                    'current_user' => $user,
                    'is_logged_in' => true,
                    'current_user_id' => $user['user_id'] ?? null
                ];
            }
            return ['current_user' => null, 'is_logged_in' => false, 'current_user_id' => null];
        }
        
        // Admin pages
        if (strpos($script_path, '/admin/') !== false) {
            if (session_status() === PHP_SESSION_NONE) {
                session_name('CONSUTRADE_ADMIN_SESSION');
                session_start();
            }
            if ($this->isAdminLoggedIn()) {
                $user = $this->getCurrentUser();
                return [
                    'current_user' => $user,
                    'is_logged_in' => true,
                    'current_user_id' => $user['user_id'] ?? null
                ];
            }
            return ['current_user' => null, 'is_logged_in' => false, 'current_user_id' => null];
        }
        
        // Main website - user session
        if (session_status() === PHP_SESSION_NONE) {
            session_name('CONSUTRADE_USER_SESSION');
            session_start();
        }
        
        $user = $this->getCurrentUser();
        $is_logged_in = $this->isLoggedIn();
        $user_id = $user['user_id'] ?? null;
        
        if ($is_logged_in && $user_id) {
            $this->updateCartCountInSession($user_id);
        }
        
        return [
            'current_user' => $user,
            'is_logged_in' => $is_logged_in,
            'current_user_id' => $user_id
        ];
    }
}