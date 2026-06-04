<?php

/**
 * ConsuTrade - Auth Class
 * 
 * Handles ALL authentication and session management.
 * 
 * @author Kamogelo Phale
 * @version 2.0.0
 */
class Auth
{
    /** @var mysqli Database connection */
    private mysqli $db;

    /** @var UserRepository User repository instance */
    private UserRepository $userRepo;

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
     * Start or resume session.
     * 
     * @return void
     */
    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // SINGLE session name for everything
        session_name('CONSUTRADE_SESSION');
        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'cookie_path' => '/'
        ]);
    }

    /**
     * Authenticate user.
     * 
     * @param string $email User's email address
     * @param string $password User's password
     * @param string $roleType Expected user role (admin, seller, buyer)
     * @return array Associative array with 'success' and 'redirect' or 'message'
     */
    public function login(string $email, string $password, string $roleType): array
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

        if ($roleType !== $user->getRole()) {
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }

        // Start session and store user data
        $this->startSession();
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user->getUserId();
        $_SESSION['full_name'] = $user->getFullName();
        $_SESSION['email'] = $user->getEmail();
        $_SESSION['role'] = $user->getRole();
        $_SESSION['profile_image'] = $user->getProfileImage();
        $_SESSION['logged_in'] = true;
        $_SESSION['user_object'] = serialize($user);

        if ($user->getRole() === 'buyer') {
            $this->updateCartCount($user->getUserId());
        }

        $redirect = match ($user->getRole()) {
            'admin' => getBaseUrl() . 'admin/admin-dashboard.php',
            'seller' => getBaseUrl() . 'admin/seller-dashboard.php',
            default => getBaseUrl() . 'index.php'
        };

        return ['success' => true, 'redirect' => $redirect];
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
     * 
     * @return User|null User object or null if not logged in
     */
    public function getCurrentUser(): ?User
    {
        $this->startSession();

        if (!$this->isLoggedIn()) {
            return null;
        }

        if (isset($_SESSION['user_object'])) {
            return unserialize($_SESSION['user_object']);
        }

        $userId = $_SESSION['user_id'] ?? 0;
        if ($userId > 0) {
            $user = $this->userRepo->findById($userId);
            if ($user) {
                $_SESSION['user_object'] = serialize($user);
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
     * Get current user role.
     * 
     * @return string|null User role or null if not logged in
     */
    public function getCurrentUserRole(): ?string
    {
        $this->startSession();
        return $_SESSION['role'] ?? null;
    }

    /**
     * Check if any user is logged in.
     * 
     * @return bool True if logged in, false otherwise
     */
    public function isLoggedIn(): bool
    {
        $this->startSession();
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Check if admin is logged in.
     * 
     * @return bool True if admin is logged in, false otherwise
     */
    public function isAdmin(): bool
    {
        return $this->isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
    }

    /**
     * Check if seller is logged in.
     * 
     * @return bool True if seller is logged in, false otherwise
     */
    public function isSeller(): bool
    {
        return $this->isLoggedIn() && ($_SESSION['role'] ?? '') === 'seller';
    }

    /**
     * Check if buyer is logged in.
     * 
     * @return bool True if buyer is logged in, false otherwise
     */
    public function isBuyer(): bool
    {
        return $this->isLoggedIn() && ($_SESSION['role'] ?? '') === 'buyer';
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

    /**
     * Refresh cart count in session for the current user.
     * 
     * @return void
     */
    public function refreshCartCount(): void
    {
        if ($this->isLoggedIn() && isset($_SESSION['user_id'])) {
            $this->updateCartCount($_SESSION['user_id']);
        }
    }
}
