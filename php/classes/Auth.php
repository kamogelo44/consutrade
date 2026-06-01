<?php

/**
 * ConsuTrade - Auth Class
 * 
 * Handles ALL authentication and session management.
 * This is the SINGLE SOURCE OF TRUTH for login/logout/session.
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
     * 
     * @param string $email User email
     * @param string $password User password
     * @param string $roleType Requested role (buyer, seller, admin)
     * @return array{success: bool, message: string, redirect: ?string}
     */
    public function login(string $email, string $password, string $roleType): array
    {
        $user = $this->userRepo->findByEmail($email);

        if (!$user) {
            return $this->loginFailed();
        }

        if (!password_verify($password, $user->getPassword())) {
            return $this->loginFailed();
        }

        if (!$user->canLogin()) {
            return $this->accountDisabled($user->getStatus());
        }

        if ($roleType !== $user->getRole()) {
            return $this->loginFailed();
        }

        $this->startSession($user);
        $redirect = $this->getRedirectUrl($user->getRole());

        return [
            'success' => true,
            'message' => 'Login successful',
            'redirect' => $redirect
        ];
    }

    private function loginFailed(): array
    {
        return [
            'success' => false,
            'message' => 'Invalid email or password.',
            'redirect' => null
        ];
    }

    private function accountDisabled(string $status): array
    {
        $message = $status === 'suspended'
            ? 'Your account has been suspended. Please contact support.'
            : 'Your account has been banned.';

        return [
            'success' => false,
            'message' => $message,
            'redirect' => null
        ];
    }

    private function startSession(User $user): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            session_write_close();
        }

        $sessionName = $this->getSessionName($user->getRole());
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

    private function getSessionName(string $role): string
    {
        return match ($role) {
            'admin' => 'CONSUTRADE_ADMIN_SESSION',
            'seller' => 'CONSUTRADE_SELLER_SESSION',
            default => 'CONSUTRADE_USER_SESSION'
        };
    }

    private function getRedirectUrl(string $role): string
    {
        return match ($role) {
            'admin' => getBaseUrl() . 'admin/admin-dashboard.php',
            'seller' => getBaseUrl() . 'admin/seller-dashboard.php',
            default => getBaseUrl() . 'index.php'
        };
    }

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
     * Get the currently logged in user as a User object
     * 
     * @return User|null
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
            $user = $this->userRepo->findById($userId);
            if ($user) {
                $_SESSION['user_object'] = serialize($user);
                return $user;
            }
        }

        return null;
    }

    public function getCurrentUserId(): int
    {
        return $_SESSION['user_id'] ?? 0;
    }

    public function getCurrentUserRole(): ?string
    {
        return $_SESSION['role'] ?? null;
    }

    public function isLoggedIn(): bool
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public function isAdmin(): bool
    {
        return $this->isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
    }

    public function isSeller(): bool
    {
        return $this->isLoggedIn() && ($_SESSION['role'] ?? '') === 'seller';
    }

    public function isBuyer(): bool
    {
        return $this->isLoggedIn() && ($_SESSION['role'] ?? '') === 'buyer';
    }

    /**
     * Require a specific role, redirect if not met
     */
    public function requireRole(string $role): void
    {
        $currentRole = $this->getCurrentUserRole();

        if ($currentRole !== $role) {
            $redirect = match ($role) {
                'admin' => getBaseUrl() . 'admin/login.php',
                'seller' => getBaseUrl() . 'admin/login.php',
                default => getBaseUrl() . 'index.php'
            };
            header('Location: ' . $redirect);
            exit;
        }
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

        $cookieParams = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 3600,
            $cookieParams['path'],
            $cookieParams['domain'],
            $cookieParams['secure'],
            $cookieParams['httponly']
        );
    }

    /**
     * Initialize session based on current page
     * 
     * @return array{user: User|null, is_logged_in: bool}
     */
    public function initSession(): array
    {
        $scriptPath = $_SERVER['SCRIPT_NAME'];

        // Admin login page - no session needed
        if (strpos($scriptPath, 'admin/login.php') !== false) {
            return ['user' => null, 'is_logged_in' => false];
        }

        // Determine session type from page
        $sessionName = $this->determineSessionName($scriptPath);

        if (session_status() === PHP_SESSION_NONE) {
            session_name($sessionName);
            session_start();
        }

        $user = $this->getCurrentUser();
        $isLoggedIn = $user !== null;

        // Validate session matches page type
        if ($isLoggedIn && !$this->sessionMatchesPage($user->getRole(), $scriptPath)) {
            $this->logout();
            $this->redirectToCorrectLogin($user->getRole());
            exit;
        }

        return ['user' => $user, 'is_logged_in' => $isLoggedIn];
    }

    private function determineSessionName(string $scriptPath): string
    {
        if (strpos($scriptPath, '/admin/') !== false && $this->isAdminPage($scriptPath)) {
            return 'CONSUTRADE_ADMIN_SESSION';
        }

        if ($this->isSellerPage($scriptPath)) {
            return 'CONSUTRADE_SELLER_SESSION';
        }

        return 'CONSUTRADE_USER_SESSION';
    }

    private function isAdminPage(string $scriptPath): bool
    {
        $adminPages = ['admin-dashboard.php', 'users.php', 'all-orders.php', 'all-products.php'];
        foreach ($adminPages as $page) {
            if (strpos($scriptPath, $page) !== false) {
                return true;
            }
        }
        return false;
    }

    private function isSellerPage(string $scriptPath): bool
    {
        $sellerPages = [
            'seller-dashboard.php',
            'seller-profile.php',
            'my-products.php',
            'add-product.php',
            'edit-product.php',
            'my-orders.php'
        ];
        foreach ($sellerPages as $page) {
            if (strpos($scriptPath, $page) !== false) {
                return true;
            }
        }
        return false;
    }

    private function sessionMatchesPage(string $userRole, string $scriptPath): bool
    {
        if ($userRole === 'admin' && !$this->isAdminPage($scriptPath)) {
            return false;
        }

        if ($userRole === 'seller' && !$this->isSellerPage($scriptPath)) {
            return false;
        }

        if ($userRole === 'buyer' && (strpos($scriptPath, '/admin/') !== false)) {
            return false;
        }

        return true;
    }

    private function redirectToCorrectLogin(string $role): void
    {
        if ($role === 'admin' || $role === 'seller') {
            header('Location: ' . getBaseUrl() . 'admin/login.php');
        } else {
            header('Location: ' . getBaseUrl() . 'index.php');
        }
        exit;
    }

    /**
     * Update cart count in session (public endpoint method)
     */
    public function refreshCartCount(): void
    {
        if ($this->isLoggedIn() && isset($_SESSION['user_id'])) {
            $this->updateCartCount($_SESSION['user_id']);
        }
    }
}
