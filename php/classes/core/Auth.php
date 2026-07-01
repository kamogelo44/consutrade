<?php

/**
 * ConsuTrade - Auth Class
 * 
 * Handles ALL authentication and session management.
 * 
 * @author Kamogelo Phale
 * @version 3.0.0
 */

class Auth
{
    /** @var mysqli Database connection */
    private mysqli $db;

    /** @var UserRepository User repository instance */
    private UserRepository $userRepo;

    /**
     * Constructor with Dependency Injection.
     * 
     * @param mysqli $db Database connection
     * @param UserRepository|null $userRepo user repository 
     */
    public function __construct(mysqli $db, ?UserRepository $userRepo = null)
    {
        $this->db = $db;
        $this->userRepo = $userRepo;
    }

    /**
     * Start or resume session.
     * 
     * @return void
     */
    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name('CONSUTRADE_SESSION');
        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'cookie_path' => '/'
        ]);
    }

    /**
     * Authenticate user with context.
     * 
     * @param string $email User's email address
     * @param string $password User's password
     * @param string $context Where login is happening (main, admin, seller)
     * @return array Associative array with 'success' and 'redirect' or 'message'
     */
    public function login(string $email, string $password, string $context = 'main'): array
    {
        $user = $this->userRepo->findByEmail($email);

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }

        if (!password_verify($password, $user->getPassword())) {
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }

        if (!$user->canLogin()) {
            $status = $user->getStatus();
            $message = $status === 'suspended'
                ? 'Your account has been suspended. Please contact support.'
                : 'Your account has been banned.';
            return ['success' => false, 'message' => $message];
        }

        $roles = $user->getRoles();

        // Determine which role to use based on context
        $activeRole = $this->determineRole($roles, $context);

        if ($activeRole === null) {
            if ($context === 'admin') {
                return ['success' => false, 'message' => 'You do not have admin or seller access.'];
            }
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }

        // Start session and store user data
        $this->startSession();
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user->getUserId();
        $_SESSION['full_name'] = $user->getFullName();
        $_SESSION['email'] = $user->getEmail();
        $_SESSION['roles'] = $roles;
        $_SESSION['logged_in'] = true;
        $_SESSION['user_object'] = serialize($user);
        $_SESSION['active_role'] = $activeRole;
        $_SESSION['role'] = $activeRole;

        // Update cart count if buyer role exists
        if (in_array('buyer', $roles)) {
            $this->updateCartCount($user->getUserId());
        }

        $redirect = $this->getRedirectByRole($activeRole, $context);

        return ['success' => true, 'redirect' => $redirect];
    }

    /**
     * Determine which role to use based on context.
     *
     * @param array $roles User's roles
     * @param string $context Login context (main, admin, seller)
     * @return string|null
     */
    private function determineRole(array $roles, string $context): ?string
    {
        if ($context === 'main') {
            if (in_array('buyer', $roles)) {
                return 'buyer';
            }
            return $roles[0] ?? null;
        }

        if ($context === 'admin') {
            if (in_array('admin', $roles)) {
                return 'admin';
            }
            if (in_array('seller', $roles)) {
                return 'seller';
            }
            return null;
        }

        if ($context === 'seller') {
            if (in_array('seller', $roles)) {
                return 'seller';
            }
            return null;
        }

        return $roles[0] ?? null;
    }

    /**
     * Get redirect URL based on role and context.
     *
     * @param string $role Active role
     * @param string $context Login context
     * @return string
     */
    private function getRedirectByRole(string $role, string $context): string
    {
        if ($context === 'admin') {
            if ($role === 'admin') {
                return getBaseUrl() . 'admin/admin-dashboard.php';
            }
            if ($role === 'seller') {
                return getBaseUrl() . 'admin/seller-dashboard.php';
            }
        }

        if ($role === 'admin') {
            return getBaseUrl() . 'admin/admin-dashboard.php';
        }
        if ($role === 'seller') {
            return getBaseUrl() . 'admin/seller-dashboard.php';
        }
        return getBaseUrl() . 'index.php';
    }

    /**
     * Switch user role for current session.
     *
     * @param string $role Role to switch to (admin, seller, buyer)
     * @return bool
     */
    public function switchRole(string $role): bool
    {
        $this->startSession();

        if (!$this->isLoggedIn()) {
            return false;
        }

        $user = $this->getCurrentUser();
        if (!$user || !$user->hasRole($role)) {
            return false;
        }

        $_SESSION['active_role'] = $role;
        $_SESSION['role'] = $role;

        return true;
    }

    /**
     * Get current active role.
     *
     * @return string|null
     */
    public function getActiveRole(): ?string
    {
        $this->startSession();
        return $_SESSION['active_role'] ?? $_SESSION['roles'][0] ?? null;
    }

    /**
     * Get available roles for current user.
     *
     * @return array
     */
    public function getAvailableRoles(): array
    {
        $this->startSession();

        if (!$this->isLoggedIn()) {
            return [];
        }

        return $_SESSION['roles'] ?? [];
    }

    /**
     * Update cart count in session for buyer users.
     * 
     * @param int $userId User ID
     * @return void
     */
    private function updateCartCount(int $userId): void
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
     * Get current logged in user as User object.
     * ALWAYS fetches fresh data from database to ensure verification status is up to date.
     * 
     * @return User|null User object or null if not logged in
     */
    public function getCurrentUser(): ?User
    {
        $this->startSession();

        if (!$this->isLoggedIn()) {
            return null;
        }

        $userId = $_SESSION['user_id'] ?? 0;
        if ($userId > 0) {
            $user = $this->userRepo->findById($userId);
            if ($user) {
                $_SESSION['user_object'] = serialize($user);
                $_SESSION['full_name'] = $user->getFullName();
                $_SESSION['roles'] = $user->getRoles();
            }
            return $user;
        }

        return null;
    }

    /**
     * Get current user ID.
     * 
     * @return int User ID or 0 if not logged in
     */
    public function getCurrentUserId(): int
    {
        $this->startSession();
        return $_SESSION['user_id'] ?? 0;
    }

    /**
     * Get current user role (legacy compatibility).
     * Returns active role.
     * 
     * @return string|null User role or null if not logged in
     */
    public function getCurrentUserRole(): ?string
    {
        $this->startSession();
        return $this->getActiveRole();
    }

    /**
     * Check if any user is logged in.
     * 
     * @return bool True if logged in, false otherwise
     */
    public function isLoggedIn()
    {
        $this->startSession();
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Check if admin is logged in (checks active role).
     * 
     * @return bool True if admin is logged in, false otherwise
     */
    public function isAdmin()
    {
        return $this->isLoggedIn() && $this->getActiveRole() === 'admin';
    }

    /**
     * Check if seller is logged in (checks active role).
     * 
     * @return bool True if seller is logged in, false otherwise
     */
    public function isSeller()
    {
        return $this->isLoggedIn() && $this->getActiveRole() === 'seller';
    }

    /**
     * Check if buyer is logged in (checks active role).
     * 
     * @return bool True if buyer is logged in, false otherwise
     */
    public function isBuyer()
    {
        return $this->isLoggedIn() && $this->getActiveRole() === 'buyer';
    }

    /**
     * Check if user has a specific role (checks all roles).
     *
     * @param string $role Role to check
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        if (!$this->isLoggedIn()) {
            return false;
        }
        $roles = $this->getAvailableRoles();
        return in_array($role, $roles);
    }

    /**
     * Login user authenticated via .htaccess
     * 
     * @param User $user User object
     * @return bool
     */
    public function loginWithHtaccessUser(User $user): bool
    {
        $this->startSession();
        session_regenerate_id(true);

        $roles = $user->getRoles();

        $_SESSION['user_id'] = $user->getUserId();
        $_SESSION['full_name'] = $user->getFullName();
        $_SESSION['email'] = $user->getEmail();
        $_SESSION['roles'] = $roles;
        $_SESSION['logged_in'] = true;
        $_SESSION['user_object'] = serialize($user);
        $_SESSION['active_role'] = $user->getPrimaryRole();
        $_SESSION['role'] = $user->getPrimaryRole();
        $_SESSION['auth_method'] = 'htaccess';

        return true;
    }

    /**
     * Logout current user and destroy session.
     * 
     * @return void
     */
    public function logout(): void
    {
        $this->startSession();
        $_SESSION = [];
        session_unset();
        session_destroy();

        if (isset($_COOKIE['CONSUTRADE_SESSION'])) {
            setcookie('CONSUTRADE_SESSION', '', time() - 3600, '/');
        }
    }
}
