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
     * Login user - handles session, role validation, and returns result.
     *
     * @param string $email User email
     * @param string $password User password
     * @param string $role_type Requested role (buyer, seller, admin)
     * @return array ['success' => bool, 'message' => string, 'redirect' => string|null]
     */
    public function login(string $email, string $password, string $role_type): array
    {
        $user = $this->userRepo->getByEmail($email);
        $genericError = 'Invalid email or password.';

        if (!$user) {
            return ['success' => false, 'message' => $genericError, 'redirect' => null];
        }

        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => $genericError, 'redirect' => null];
        }

        // Check user status
        $status = $user['status'] ?? 'active';
        if ($status === 'suspended') {
            return ['success' => false, 'message' => 'Your account has been suspended. Please contact support.', 'redirect' => null];
        }
        if ($status === 'banned') {
            return ['success' => false, 'message' => 'Your account has been banned.', 'redirect' => null];
        }

        // Validate role matches request
        if ($role_type !== $user['role']) {
            return ['success' => false, 'message' => $genericError, 'redirect' => null];
        }

        // Close any existing session
        if (session_status() !== PHP_SESSION_NONE) {
            session_write_close();
        }

        // Set session name based on role
        if ($user['role'] === 'admin') {
            session_name('CONSUTRADE_ADMIN_SESSION');
        } elseif ($user['role'] === 'seller') {
            session_name('CONSUTRADE_SELLER_SESSION');
        } else {
            session_name('CONSUTRADE_USER_SESSION');
        }

        session_start();
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['profile_image'] = $user['profile_image'] ?? '';
        $_SESSION['logged_in'] = true;

        // Update cart count for buyers
        if ($user['role'] === 'buyer') {
            $this->updateCartCountInSession($user['user_id']);
        }

        session_write_close();

        // Determine redirect URL
        if ($user['role'] === 'admin') {
            $redirect = getBaseUrl() . 'admin/admin-dashboard.php';
        } elseif ($user['role'] === 'seller') {
            $redirect = getBaseUrl() . 'admin/seller-dashboard.php';
        } else {
            $redirect = getBaseUrl() . 'index.php';
        }

        return ['success' => true, 'message' => 'Login successful', 'redirect' => $redirect];
    }

    /**
     * Process login and return appropriate response for AJAX or form submission.
     *
     * @param string $email User email
     * @param string $password User password
     * @param string $role_type Requested role type (buyer, seller, admin)
     * @param bool $is_ajax Whether this is an AJAX request
     * @return array
     */
    public function processLogin(string $email, string $password, string $role_type, bool $is_ajax = false): array
    {
        $result = $this->login($email, $password, $role_type);

        if (!$result['success']) {
            if ($is_ajax) {
                return ['success' => false, 'message' => $result['message']];
            }
            $_SESSION['login_error'] = $result['message'];
            $_SESSION['login_email'] = $email;
            return ['success' => false, 'redirect' => $this->getLoginRedirectUrl($role_type)];
        }

        if ($is_ajax) {
            return ['success' => true, 'redirect' => $result['redirect']];
        }

        $_SESSION['flash'] = 'Welcome back, ' . ($_SESSION['full_name'] ?? 'User') . '!';
        header('Location: ' . $result['redirect']);
        exit;
    }

    private function getLoginRedirectUrl(string $role_type): string
    {
        if ($role_type === 'admin' || $role_type === 'seller') {
            return getBaseUrl() . 'admin/login.php';
        }
        return getBaseUrl() . 'index.php';
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
     * Get current user role without destroying session.
     * Tries each session name to find the active one.
     *
     * @return string|null
     */
    public function getCurrentUserRole(): ?string
    {
        $session_names = ['CONSUTRADE_ADMIN_SESSION', 'CONSUTRADE_SELLER_SESSION', 'CONSUTRADE_USER_SESSION'];
        $original_name = session_name();

        foreach ($session_names as $name) {
            // Close current session if open
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }

            session_name($name);
            session_start();

            if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
                $role = $_SESSION['role'] ?? null;
                session_write_close();

                // Restore original session name if needed
                if ($original_name && $original_name !== $name) {
                    session_name($original_name);
                }
                return $role;
            }
            session_write_close();
        }

        // Restore original session name
        if ($original_name) {
            session_name($original_name);
        }

        return null;
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

        // ========== MAIN WEBSITE PAGES ==========
        $is_main_website = (strpos($script_path, '/admin/') === false);

        if ($is_main_website) {
            // Only start user session - DO NOT destroy other sessions
            if (session_status() === PHP_SESSION_NONE) {
                session_name('CONSUTRADE_USER_SESSION');
                session_start();
            }

            $user = $this->getCurrentUser();
            $is_logged_in = $this->isLoggedIn();
            $user_id = $user['user_id'] ?? null;

            if ($is_logged_in && $user_id && ($user['role'] ?? '') === 'buyer') {
                $this->updateCartCountInSession($user_id);
            }

            return [
                'current_user' => $user,
                'is_logged_in' => $is_logged_in && ($user['role'] ?? '') === 'buyer',
                'current_user_id' => $user_id
            ];
        }

        // ========== SELLER PAGES ==========
        $is_seller_page = (
            strpos($script_path, 'seller-dashboard.php') !== false ||
            strpos($script_path, 'seller-profile.php') !== false ||
            strpos($script_path, 'admin/my-orders.php') !== false ||
            strpos($script_path, 'my-products.php') !== false ||
            strpos($script_path, 'add-product.php') !== false ||
            strpos($script_path, 'edit-product.php') !== false
        );

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

        // ========== ADMIN PAGES ==========
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

        // Fallback
        if (session_status() === PHP_SESSION_NONE) {
            session_name('CONSUTRADE_USER_SESSION');
            session_start();
        }

        $user = $this->getCurrentUser();
        $is_logged_in = $this->isLoggedIn();
        $user_id = $user['user_id'] ?? null;

        if ($is_logged_in && $user_id && ($user['role'] ?? '') === 'buyer') {
            $this->updateCartCountInSession($user_id);
        }

        return [
            'current_user' => $user,
            'is_logged_in' => $is_logged_in && ($user['role'] ?? '') === 'buyer',
            'current_user_id' => $user_id
        ];
    }
}
