<?php
/**
 * ConsuTrade - Auth
 *
 * Central authentication handler — manages three separate named sessions
 * for buyers, sellers, and administrators.
 *
 * @author     Kamogelo Phale
 * @module     ITECA3-12 Web Development and e-Commerce
 * @institution Eduvos
 * @version    2.0.0
 * @since      2026
 *
 * References:
 * - Pressman, R.S. and Maxim, B.R., 2015. Software Engineering:
 *   A Practitioner's Approach. 8th ed. McGraw-Hill.
 * - Dennis, A., Wixom, B.H. and Tegarden, D., 2015. Systems Analysis
 *   and Design: An Object-Oriented Approach with UML. 6th ed.
 *   John Wiley and Sons.
 * - PHP Group, 2025. Classes and Objects. Available at:
 *   https://www.php.net/manual/en/language.oop5.php
 * - PHP-FIG, 2023. PSR-12: Extended Coding Style. Available at:
 *   https://www.php.fig.org/psr/psr-12/
 * - OWASP, 2021. OWASP Top Ten. Available at:
 *   https://owasp.org/www-project-top-ten
 */

class Auth
{
    /** @var mysqli Database connection */
    private $db;

    /**
     * Constructor.
     *
     * @param mysqli $db Database connection
     */
    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    // ============================================================
    //  SESSION INITIALIZATION
    // ============================================================

    /**
     * Start session with appropriate name based on context.
     *
     * @param string $context 'user', 'admin', or 'seller'
     * @return void
     */
    public function initAuth(string $context = 'user'): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            if ($context === 'admin') {
                session_name('CONSUTRADE_ADMIN_SESSION');
            } elseif ($context === 'seller') {
                session_name('CONSUTRADE_SELLER_SESSION');
            } else {
                session_name('CONSUTRADE_USER_SESSION');
            }
            session_start();
        }
    }

    /**
     * Start main website session (buyers).
     *
     * @return void
     */
    public function initUserAuth(): void
    {
        $this->initAuth('user');
    }

    /**
     * Start admin session.
     *
     * @return void
     */
    public function initAdminAuth(): void
    {
        $this->initAuth('admin');
    }

    /**
     * Start seller session.
     *
     * @return void
     */
    public function initSellerAuth(): void
    {
        $this->initAuth('seller');
    }

    /**
     * Auto-detect and initialize appropriate session based on page URL.
     *
     * @return array ['current_user' => array|null, 'is_logged_in' => bool, 'current_user_id' => int|null]
     */
    public function initAppSession(): array
    {
        $script_path = $_SERVER['SCRIPT_NAME'];

        // Admin login page — no session at all
        if (strpos($script_path, 'admin/login.php') !== false) {
            return ['current_user' => null, 'is_logged_in' => false, 'current_user_id' => null];
        }

        // Check if this is a seller page (BEFORE checking /admin/)
        $is_seller_page = (
            strpos($script_path, 'seller-dashboard.php') !== false ||
            strpos($script_path, 'seller-profile.php') !== false ||
            strpos($script_path, 'admin/my-orders.php') !== false ||
            strpos($script_path, 'my-products.php') !== false ||
            strpos($script_path, 'add-product.php') !== false ||
            strpos($script_path, 'edit-product.php') !== false ||
            strpos($script_path, 'get-seller-products.php') !== false ||
            strpos($script_path, 'get-seller-orders.php') !== false ||
            strpos($script_path, 'get-seller-recent-orders.php') !== false ||
            strpos($script_path, 'update-order-status.php') !== false ||
            strpos($script_path, 'delete-product.php') !== false
        );

        // Seller pages
        if ($is_seller_page) {
            if (session_status() === PHP_SESSION_NONE) {
                session_name('CONSUTRADE_SELLER_SESSION');
                session_start();
            }
            $current_user = $this->getCurrentSeller();
            return [
                'current_user'   => $current_user,
                'is_logged_in'   => $this->isSellerLoggedIn(),
                'current_user_id' => $current_user['user_id'] ?? null
            ];
        }

        // Admin pages (excluding login and seller pages)
        if (strpos($script_path, '/admin/') !== false) {
            if (session_status() === PHP_SESSION_NONE) {
                session_name('CONSUTRADE_ADMIN_SESSION');
                session_start();
            }
            $current_user = $this->getCurrentAdmin();
            return [
                'current_user'   => $current_user,
                'is_logged_in'   => $this->isAdminLoggedIn(),
                'current_user_id' => $current_user['user_id'] ?? null
            ];
        }

        // Main website — user session (buyers)
        if (session_status() === PHP_SESSION_NONE) {
            session_name('CONSUTRADE_USER_SESSION');
            session_start();
        }
        $current_user = $this->getCurrentUser();
        $is_logged_in = $this->isLoggedIn();
        $user_id = $this->getCurrentUserId();

        if ($is_logged_in) {
            $this->updateCartCount();
        }

        return [
            'current_user'   => $current_user,
            'is_logged_in'   => $is_logged_in,
            'current_user_id' => $user_id
        ];
    }

    // ============================================================
    //  LOGIN METHODS
    // ============================================================

    /**
     * Login user (main website — buyers only).
     *
     * @param int    $id   User ID
     * @param string $name Full name
     * @param string $email Email address
     * @param string $role User role (must be 'buyer')
     * @return bool
     */
    public function loginUser(int $id, string $name, string $email, string $role): bool
    {
        if ($role !== 'buyer') {
            return false;
        }

        $this->initUserAuth();
        session_regenerate_id(true);

        $_SESSION['user_id']   = $id;
        $_SESSION['full_name'] = $name;
        $_SESSION['email']     = $email;
        $_SESSION['role']      = $role;
        $_SESSION['logged_in'] = true;

        // Update cart count from database
        $stmt = $this->db->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $_SESSION['cart_count'] = (int)($row['total'] ?? 0);
        $stmt->close();

        return true;
    }

    /**
     * Login admin.
     *
     * @param int    $id   User ID
     * @param string $name Full name
     * @param string $email Email address
     * @param string $role User role (must be 'admin')
     * @return bool
     */
    public function loginAdmin(int $id, string $name, string $email, string $role): bool
    {
        // Destroy any existing session first
        if (session_status() !== PHP_SESSION_NONE) {
            session_destroy();
        }

        session_name('CONSUTRADE_ADMIN_SESSION');
        session_start();
        session_regenerate_id(true);

        $_SESSION['user_id']   = $id;
        $_SESSION['full_name'] = $name;
        $_SESSION['email']     = $email;
        $_SESSION['role']      = $role;
        $_SESSION['logged_in'] = true;

        return true;
    }

    /**
     * Login seller.
     *
     * @param int    $id   User ID
     * @param string $name Full name
     * @param string $email Email address
     * @param string $role User role (must be 'seller')
     * @return bool
     */
    public function loginSeller(int $id, string $name, string $email, string $role): bool
    {
        // Destroy any existing session first
        if (session_status() !== PHP_SESSION_NONE) {
            session_destroy();
        }

        session_name('CONSUTRADE_SELLER_SESSION');
        session_start();
        session_regenerate_id(true);

        $_SESSION['user_id']   = $id;
        $_SESSION['full_name'] = $name;
        $_SESSION['email']     = $email;
        $_SESSION['role']      = $role;
        $_SESSION['logged_in'] = true;

        return true;
    }

    // ============================================================
    //  LOGOUT
    // ============================================================

    /**
     * Logout user (any role).
     *
     * @return void
     */
    public function logoutUser(): void
    {
        $session_name = session_name();
        session_unset();

        if (isset($_COOKIE[$session_name])) {
            setcookie($session_name, '', time() - 3600, '/');
        }

        session_destroy();
    }

    // ============================================================
    //  LOGGED-IN CHECKS
    // ============================================================

    /**
     * Check if user is logged in (main website — buyers).
     *
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $current_session_name = session_name();
            if ($current_session_name === 'CONSUTRADE_ADMIN_SESSION' ||
                $current_session_name === 'CONSUTRADE_SELLER_SESSION') {
                return false;
            }
        }

        $user = $this->getCurrentUser();
        return $user !== null && $user['role'] === 'buyer';
    }

    /**
     * Check if admin is logged in.
     *
     * @return bool
     */
    public function isAdminLoggedIn(): bool
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $current_session_name = session_name();
            if ($current_session_name !== 'CONSUTRADE_ADMIN_SESSION') {
                return false;
            }
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_name('CONSUTRADE_ADMIN_SESSION');
            session_start();
        }

        return isset($_SESSION['logged_in']) &&
               $_SESSION['logged_in'] === true &&
               isset($_SESSION['role']) &&
               $_SESSION['role'] === 'admin';
    }

    /**
     * Check if seller is logged in.
     *
     * @return bool
     */
    public function isSellerLoggedIn(): bool
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $current_session_name = session_name();
            if ($current_session_name !== 'CONSUTRADE_SELLER_SESSION') {
                return false;
            }
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_name('CONSUTRADE_SELLER_SESSION');
            session_start();
        }

        return isset($_SESSION['logged_in']) &&
               $_SESSION['logged_in'] === true &&
               isset($_SESSION['role']) &&
               $_SESSION['role'] === 'seller';
    }

    // ============================================================
    //  CURRENT USER GETTERS
    // ============================================================

    /**
     * Get current authenticated user (main website — buyers).
     *
     * @return array|null
     */
    public function getCurrentUser(): ?array
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $current_session_name = session_name();
            if ($current_session_name === 'CONSUTRADE_ADMIN_SESSION' ||
                $current_session_name === 'CONSUTRADE_SELLER_SESSION') {
                return null;
            }
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_name('CONSUTRADE_USER_SESSION');
            session_start();
        }

        if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            return null;
        }

        if (isset($_SESSION['role']) && $_SESSION['role'] !== 'buyer') {
            return null;
        }

        return [
            'user_id'   => $_SESSION['user_id'],
            'full_name' => $_SESSION['full_name'] ?? null,
            'email'     => $_SESSION['email'] ?? null,
            'role'      => $_SESSION['role'] ?? null
        ];
    }

    /**
     * Get current admin.
     *
     * @return array|null
     */
    public function getCurrentAdmin(): ?array
    {
        if (!$this->isAdminLoggedIn()) {
            return null;
        }

        return [
            'user_id'   => $_SESSION['user_id'] ?? null,
            'full_name' => $_SESSION['full_name'] ?? null,
            'email'     => $_SESSION['email'] ?? null,
            'role'      => $_SESSION['role'] ?? null
        ];
    }

    /**
     * Get current seller.
     *
     * @return array|null
     */
    public function getCurrentSeller(): ?array
    {
        if (!$this->isSellerLoggedIn()) {
            return null;
        }

        return [
            'user_id'   => $_SESSION['user_id'] ?? null,
            'full_name' => $_SESSION['full_name'] ?? null,
            'email'     => $_SESSION['email'] ?? null,
            'role'      => $_SESSION['role'] ?? null
        ];
    }

    /**
     * Get current user ID (convenience, works for any session type).
     *
     * @return int|null
     */
    public function getCurrentUserId(): ?int
    {
        // Check all three session types
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (isset($_SESSION['user_id'], $_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
                return (int) $_SESSION['user_id'];
            }
        }

        // Try each session
        $user = $this->getCurrentUser();
        if ($user) {
            return (int) $user['user_id'];
        }

        $admin = $this->getCurrentAdmin();
        if ($admin) {
            return (int) $admin['user_id'];
        }

        $seller = $this->getCurrentSeller();
        if ($seller) {
            return (int) $seller['user_id'];
        }

        return null;
    }

    // ============================================================
    //  CART COUNT
    // ============================================================

    /**
     * Update cart count in session (main website only).
     *
     * @return void
     */
    public function updateCartCount(): void
    {
        $user_id = $this->getCurrentUserId();
        if ($user_id) {
            $stmt = $this->db->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $_SESSION['cart_count'] = (int)($row['total'] ?? 0);
            $stmt->close();
        }
    }
}