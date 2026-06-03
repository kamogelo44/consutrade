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
     * Authenticate user and start session.
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

        $this->startSession($user);

        $redirect = match ($user->getRole()) {
            'admin' => getBaseUrl() . 'admin/admin-dashboard.php',
            'seller' => getBaseUrl() . 'admin/seller-dashboard.php',
            default => getBaseUrl() . 'index.php'
        };

        return ['success' => true, 'redirect' => $redirect];
    }

    /**
     * Start session for a user.
     * 
     * @param User $user User object
     * @return void
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
     * Get current user ID.
     * 
     * @return int User ID or 0 if not logged in
     */
    public function getCurrentUserId(): int
    {
        return $_SESSION['user_id'] ?? 0;
    }

    /**
     * Get current user role.
     * 
     * @return string|null User role or null if not logged in
     */
    public function getCurrentUserRole(): ?string
    {
        return $_SESSION['role'] ?? null;
    }

    /**
     * Check if any user is logged in.
     * 
     * @return bool True if logged in, false otherwise
     */
    public function isLoggedIn(): bool
    {
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
     * Initialize session based on current page.
     * 
     * @return array Associative array with 'user' (User object or null) and 'is_logged_in' (bool)
     */
    public function initSession(): array
    {
        $scriptPath = $_SERVER['SCRIPT_NAME'];
        $fileName = basename($scriptPath);

        if ($fileName === 'login.php' && strpos($scriptPath, '/admin/') !== false) {
            return ['user' => null, 'is_logged_in' => false];
        }

        $adminPages = [
            'admin-dashboard.php',
            'users.php',
            'all-products.php',
            'all-orders.php',
            'admin-profile.php'
        ];

        $adminEndpoints = [
            'get-users.php',
            'get-all-products.php',
            'get-all-orders.php',
            'get-user-stats.php',
            'get-recent-users.php',
            'get-recent-orders.php',
            'update-user-verification.php',
            'delete-user.php',
            'update-product-status.php',
            'delete-product.php',
            'verify-seller.php',
            'get-order-details.php',
            'get-order-status.php'
        ];

        $sellerPages = [
            'seller-dashboard.php',
            'seller-profile.php',
            'my-products.php',
            'seller-orders.php',
            'add-product.php',
            'edit-product.php'
        ];

        $sellerEndpoints = [
            'get-seller-products.php',
            'get-seller-recent-orders.php',
            'delete-product.php',
            'add-product.php',
            'edit-product.php',
            'update-product-status.php',
            'remove-gallery-image.php',
            'set-primary-image.php',
            'upload-verification.php',
            'get-order-details.php',
            'get-order-status.php'
        ];

        $sharedEndpoints = [
            'update-profile.php',
            'change-password.php',
            'delete-account.php',
            'logout.php'
        ];

        if (in_array($fileName, $adminPages) || in_array($fileName, $adminEndpoints)) {
            $sessionName = 'CONSUTRADE_ADMIN_SESSION';
        } elseif (in_array($fileName, $sellerPages) || in_array($fileName, $sellerEndpoints)) {
            $sessionName = 'CONSUTRADE_SELLER_SESSION';
        } elseif (in_array($fileName, $sharedEndpoints)) {
            $sessionName = 'CONSUTRADE_USER_SESSION';
        } else {
            $sessionName = 'CONSUTRADE_USER_SESSION';
        }

        // Only set session name if session is not already active
        if (session_status() === PHP_SESSION_NONE) {
            session_name($sessionName);
            session_start();
        } else {
            // Session already active, just use it
            $sessionName = session_name();
        }

        $user = $this->getCurrentUser();
        $isLoggedIn = $user !== null;

        return ['user' => $user, 'is_logged_in' => $isLoggedIn];
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
