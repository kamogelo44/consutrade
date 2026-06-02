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
    private mysqli $db;
    private UserRepository $userRepo;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
        $this->userRepo = new UserRepository($db);
    }

    /**
     * Authenticate user and start session
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

        $this->startSession($user);

        $redirect = match ($user->getRole()) {
            'admin' => getBaseUrl() . 'admin/admin-dashboard.php',
            'seller' => getBaseUrl() . 'admin/seller-dashboard.php',
            default => getBaseUrl() . 'index.php'
        };

        return ['success' => true, 'redirect' => $redirect];
    }

    /**
     * Start session for a user
     */
    private function startSession(User $user): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            session_write_close();
        }

        $sessionName = match ($user->getRole()) {
            'admin' => 'CONSUTRADE_ADMIN_SESSION',
            'seller' => 'CONSUTRADE_SELLER_SESSION',
            default => 'CONSUTRADE_USER_SESSION'
        };

        session_name($sessionName);
        session_start();
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

        session_write_close();
    }

    /**
     * Update cart count in session
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
     * Get current logged in user as User object
     */
    public function getCurrentUser(): ?User
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        if (isset($_SESSION['user_object'])) {
            return unserialize($_SESSION['user_object']);
        }

        $userId = $_SESSION['user_id'] ?? 0;
        if ($userId > 0) {
            return $this->userRepo->findById($userId);
        }

        return null;
    }

    /**
     * Get current user ID
     */
    public function getCurrentUserId(): int
    {
        return $_SESSION['user_id'] ?? 0;
    }

    /**
     * Get current user role
     */
    public function getCurrentUserRole(): ?string
    {
        return $_SESSION['role'] ?? null;
    }

    /**
     * Check if any user is logged in
     */
    public function isLoggedIn(): bool
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Check if admin is logged in
     */
    public function isAdmin(): bool
    {
        return $this->isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
    }

    /**
     * Check if seller is logged in
     */
    public function isSeller(): bool
    {
        return $this->isLoggedIn() && ($_SESSION['role'] ?? '') === 'seller';
    }

    /**
     * Check if buyer is logged in
     */
    public function isBuyer(): bool
    {
        return $this->isLoggedIn() && ($_SESSION['role'] ?? '') === 'buyer';
    }

    /**
     * Logout current user
     */
    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];
        session_unset();
        session_destroy();

        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
    }

    /**
     * Initialize session based on current page
     */
    public function initSession(): array
    {
        $scriptPath = $_SERVER['SCRIPT_NAME'];

        if (strpos($scriptPath, 'admin/login.php') !== false) {
            return ['user' => null, 'is_logged_in' => false];
        }

        $isAdminPage = strpos($scriptPath, '/admin/') !== false &&
            strpos($scriptPath, 'seller-dashboard.php') === false &&
            strpos($scriptPath, 'seller-profile.php') === false &&
            strpos($scriptPath, 'my-products.php') === false &&
            strpos($scriptPath, 'add-product.php') === false &&
            strpos($scriptPath, 'edit-product.php') === false &&
            strpos($scriptPath, 'my-orders.php') === false;

        $isSellerPage = strpos($scriptPath, 'seller-dashboard.php') !== false ||
            strpos($scriptPath, 'seller-profile.php') !== false ||
            strpos($scriptPath, 'my-products.php') !== false ||
            strpos($scriptPath, 'add-product.php') !== false ||
            strpos($scriptPath, 'my-orders.php') !== false ||
            strpos($scriptPath, 'edit-product.php') !== false;

        // Set session name BEFORE any session start
        if ($isAdminPage) {
            $sessionName = 'CONSUTRADE_ADMIN_SESSION';
        } elseif ($isSellerPage) {
            $sessionName = 'CONSUTRADE_SELLER_SESSION';
        } else {
            $sessionName = 'CONSUTRADE_USER_SESSION';
        }

        session_name($sessionName);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $user = $this->getCurrentUser();
        $isLoggedIn = $user !== null;

        return ['user' => $user, 'is_logged_in' => $isLoggedIn];
    }

    /**
     * Refresh cart count in session
     */
    public function refreshCartCount(): void
    {
        if ($this->isLoggedIn() && isset($_SESSION['user_id'])) {
            $this->updateCartCount($_SESSION['user_id']);
        }
    }
}
