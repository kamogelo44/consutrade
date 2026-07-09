<?php

/**
 * ConsuTrade - Auth Class
 * 
 * Handles ALL authentication, session management, rate limiting,
 * email verification, password reset, and login throttling.
 * 
 * Industry-standard security patterns:
 * - Bcrypt password hashing (cost 12)
 * - Session regeneration on login
 * - Account lockout after failed attempts
 * - Email verification tokens (32-byte random)
 * - Password reset tokens with expiry
 * - Session invalidation on password change
 * 
 * @author Kamogelo Phale
 * @version 4.0.0
 */

class Auth
{
    private mysqli $db;
    private UserRepository $userRepo;
    private int $maxLoginAttempts = 5;
    private int $lockoutMinutes = 15;

    public function __construct(mysqli $db, ?UserRepository $userRepo = null)
    {
        $this->db = $db;
        $this->userRepo = $userRepo;
    }

    // ============================================================
    // SESSION MANAGEMENT
    // ============================================================

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name('CONSUTRADE_SESSION');
        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'cookie_path' => '/',
            'cookie_secure' => isset($_SERVER['HTTPS']),
        ]);
    }

    // ============================================================
    // LOGIN
    // ============================================================

    public function login(string $email, string $password, string $context = 'main'): array
    {
        $email = strtolower(trim($email));

        // Check if account is locked
        if ($this->isAccountLocked($email)) {
            $unlockAt = $this->getAccountUnlockTime($email);
            return [
                'success' => false,
                'message' => 'Account temporarily locked due to too many failed attempts. Try again after ' . $unlockAt . '.'
            ];
        }

        $user = $this->userRepo->findByEmail($email);

        if (!$user || !password_verify($password, $user->getPassword())) {
            $this->recordFailedAttempt($email);
            $remaining = $this->getRemainingAttempts($email);

            if ($remaining <= 0) {
                return [
                    'success' => false,
                    'message' => 'Account locked for ' . $this->lockoutMinutes . ' minutes due to too many failed attempts.'
                ];
            }

            return [
                'success' => false,
                'message' => "Invalid email or password. {$remaining} attempt(s) remaining."
            ];
        }

        if (!$user->canLogin()) {
            $status = $user->getStatus();
            $message = $status === 'suspended'
                ? 'Your account has been suspended. Please contact support.'
                : 'Your account has been banned.';
            return ['success' => false, 'message' => $message];
        }

        // Check email verification
        if (!$user->isEmailVerified()) {
            return [
                'success' => false,
                'message' => 'Please verify your email address before logging in. Check your inbox.',
                'needs_verification' => true,
                'email' => $email
            ];
        }

        // Clear failed attempts
        $this->clearFailedAttempts($email);

        $roles = $user->getRoles();
        $activeRole = $this->determineRole($roles, $context);

        if ($activeRole === null) {
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }

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
        $_SESSION['login_time'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        // Record session
        $this->recordSession($user->getUserId());

        // Update cart count
        if (in_array('buyer', $roles)) {
            $this->updateCartCount($user->getUserId());
        }

        $redirect = $this->getRedirectByRole($activeRole, $context);

        return ['success' => true, 'redirect' => $redirect];
    }

    // ============================================================
    // REGISTRATION WITH EMAIL VERIFICATION
    // ============================================================

    public function register(array $data): array
    {
        $email = strtolower(trim($data['email']));

        // Check if email already exists
        $existing = $this->userRepo->findByEmail($email);
        if ($existing) {
            return ['success' => false, 'message' => 'An account with this email already exists.'];
        }

        // Hash password with bcrypt
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        $data['email'] = $email;

        // Generate verification token
        $token = bin2hex(random_bytes(32));
        $tokenExpiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $data['verification_token'] = $token;
        $data['verification_token_expiry'] = $tokenExpiry;
        $data['email_verified'] = 0;

        // Create user
        $userId = $this->userRepo->create($data);

        if (!$userId) {
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }

        // Send verification email
        $this->sendVerificationEmail($email, $data['full_name'], $token);

        return [
            'success' => true,
            'message' => 'Account created successfully. Please check your email to verify your account.',
            'user_id' => $userId
        ];
    }

    public function resendVerificationEmail(string $email): array
    {
        $user = $this->userRepo->findByEmail(strtolower(trim($email)));

        if (!$user) {
            return ['success' => false, 'message' => 'No account found with this email.'];
        }

        if ($user->isEmailVerified()) {
            return ['success' => false, 'message' => 'Email is already verified.'];
        }

        $token = bin2hex(random_bytes(32));
        $tokenExpiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $this->userRepo->updateVerificationToken($user->getUserId(), $token, $tokenExpiry);
        $this->sendVerificationEmail($email, $user->getFullName(), $token);

        return ['success' => true, 'message' => 'Verification email resent. Please check your inbox.'];
    }

    public function verifyEmail(string $token): array
    {
        $userId = $this->userRepo->findByVerificationToken($token);

        if (!$userId) {
            return ['success' => false, 'message' => 'Invalid or expired verification link. Please request a new one.'];
        }

        $this->userRepo->markEmailVerified($userId);
        $this->userRepo->clearVerificationToken($userId);

        return ['success' => true, 'message' => 'Email verified successfully. You can now log in.'];
    }

    // ============================================================
    // PASSWORD RESET
    // ============================================================

    public function sendPasswordReset(string $email): array
    {
        $user = $this->userRepo->findByEmail(strtolower(trim($email)));

        if (!$user) {
            // Don't reveal whether email exists (prevents enumeration)
            return ['success' => true, 'message' => 'If an account exists with this email, a reset link has been sent.'];
        }

        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $this->userRepo->storePasswordResetToken($user->getUserId(), $token, $expiry);
        $this->sendPasswordResetEmail($email, $user->getFullName(), $token);

        return ['success' => true, 'message' => 'If an account exists with this email, a reset link has been sent.'];
    }

    public function resetPassword(string $token, string $newPassword): array
    {
        if (strlen($newPassword) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters.'];
        }

        $userId = $this->userRepo->findByPasswordResetToken($token);

        if (!$userId) {
            return ['success' => false, 'message' => 'Invalid or expired reset link. Please request a new one.'];
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->userRepo->updatePassword($userId, $hashedPassword);
        $this->userRepo->clearPasswordResetToken($userId);

        // Security: invalidate all existing sessions
        $this->invalidateAllSessions($userId);

        return ['success' => true, 'message' => 'Password reset successfully. Please log in with your new password.'];
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword): array
    {
        if (strlen($newPassword) < 8) {
            return ['success' => false, 'message' => 'New password must be at least 8 characters.'];
        }

        $user = $this->userRepo->findById($userId);

        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        if (!password_verify($currentPassword, $user->getPassword())) {
            return ['success' => false, 'message' => 'Current password is incorrect.'];
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->userRepo->updatePassword($userId, $hashedPassword);

        return ['success' => true, 'message' => 'Password changed successfully.'];
    }

    // ============================================================
    // EMAIL METHODS
    // ============================================================

    private function sendVerificationEmail(string $email, string $name, string $token): void
    {
        $verificationLink = getBaseUrl() . 'verify-email.php?token=' . $token;

        $subject = 'Verify your ConsuTrade account';
        $message = "Hello $name,\n\n";
        $message .= "Welcome to ConsuTrade! Please verify your email address by clicking the link below:\n\n";
        $message .= "$verificationLink\n\n";
        $message .= "This link expires in 24 hours.\n\n";
        $message .= "If you did not create this account, please ignore this email.\n\n";
        $message .= "— The ConsuTrade Team";

        $headers = "From: ConsuTrade <noreply@consutrade.co.za>\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        @mail($email, $subject, $message, $headers);
    }

    private function sendPasswordResetEmail(string $email, string $name, string $token): void
    {
        $resetLink = getBaseUrl() . 'reset-password.php?token=' . $token;

        $subject = 'Reset your ConsuTrade password';
        $message = "Hello $name,\n\n";
        $message .= "You requested a password reset. Click the link below to set a new password:\n\n";
        $message .= "$resetLink\n\n";
        $message .= "This link expires in 1 hour.\n\n";
        $message .= "If you did not request this, please ignore this email.\n\n";
        $message .= "— The ConsuTrade Team";

        $headers = "From: ConsuTrade <noreply@consutrade.co.za>\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        @mail($email, $subject, $message, $headers);
    }

    // ============================================================
    // LOGIN THROTTLING
    // ============================================================

    private function isAccountLocked(string $email): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as attempts, MAX(attempted_at) as last_attempt 
             FROM login_attempts 
             WHERE email = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)"
        );
        $stmt->bind_param('si', $email, $this->lockoutMinutes);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if ($row['attempts'] >= $this->maxLoginAttempts) {
            $lastAttempt = strtotime($row['last_attempt']);
            $unlockTime = $lastAttempt + ($this->lockoutMinutes * 60);
            return time() < $unlockTime;
        }

        return false;
    }

    private function getAccountUnlockTime(string $email): string
    {
        $stmt = $this->db->prepare(
            "SELECT MAX(attempted_at) as last_attempt 
             FROM login_attempts 
             WHERE email = ?"
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        $lastAttempt = strtotime($row['last_attempt'] ?? 'now');
        $unlockTime = $lastAttempt + ($this->lockoutMinutes * 60);

        return date('H:i', $unlockTime);
    }

    private function recordFailedAttempt(string $email): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt = $this->db->prepare(
            "INSERT INTO login_attempts (email, ip_address, attempted_at) VALUES (?, ?, NOW())"
        );
        $stmt->bind_param('ss', $email, $ip);
        $stmt->execute();
        $stmt->close();

        // Cleanup old attempts
        $this->db->query("DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    }

    private function getRemainingAttempts(string $email): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as attempts 
             FROM login_attempts 
             WHERE email = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)"
        );
        $stmt->bind_param('si', $email, $this->lockoutMinutes);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return max(0, $this->maxLoginAttempts - (int)($row['attempts'] ?? 0));
    }

    private function clearFailedAttempts(string $email): void
    {
        $stmt = $this->db->prepare("DELETE FROM login_attempts WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->close();
    }

    // ============================================================
    // SESSION SECURITY
    // ============================================================

    private function recordSession(int $userId): void
    {
        $sessionId = session_id();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        $stmt = $this->db->prepare(
            "INSERT INTO user_sessions (session_id, user_id, ip_address, user_agent, created_at) 
             VALUES (?, ?, ?, ?, NOW())"
        );
        $stmt->bind_param('siss', $sessionId, $userId, $ip, $ua);
        $stmt->execute();
        $stmt->close();

        // Cleanup old sessions (keep last 30 days)
        $this->db->query("DELETE FROM user_sessions WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    }

    public function invalidateAllSessions(int $userId): void
    {
        $stmt = $this->db->prepare("DELETE FROM user_sessions WHERE user_id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
    }

    public function isSessionValid(): bool
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        $sessionId = session_id();
        $stmt = $this->db->prepare("SELECT user_id FROM user_sessions WHERE session_id = ?");
        $stmt->bind_param('s', $sessionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row !== null;
    }

    // ============================================================
    // ROLE MANAGEMENT
    // ============================================================

    private function determineRole(array $roles, string $context): ?string
    {
        if ($context === 'main') {
            if (in_array('buyer', $roles)) return 'buyer';
            return $roles[0] ?? null;
        }

        if ($context === 'admin') {
            if (in_array('admin', $roles)) return 'admin';
            if (in_array('seller', $roles)) return 'seller';
            return null;
        }

        if ($context === 'seller') {
            if (in_array('seller', $roles)) return 'seller';
            return null;
        }

        return $roles[0] ?? null;
    }

    private function getRedirectByRole(string $role, string $context): string
    {
        $baseUrl = getBaseUrl();

        if ($context === 'admin') {
            if ($role === 'admin') return $baseUrl . 'admin/admin-dashboard.php';
            if ($role === 'seller') return $baseUrl . 'admin/seller-dashboard.php';
        }

        if ($role === 'admin') return $baseUrl . 'admin/admin-dashboard.php';
        if ($role === 'seller') return $baseUrl . 'admin/seller-dashboard.php';
        return $baseUrl . 'index.php';
    }

    public function switchRole(string $role): bool
    {
        $this->startSession();

        if (!$this->isLoggedIn()) return false;

        $user = $this->getCurrentUser();
        if (!$user || !$user->hasRole($role)) return false;

        $_SESSION['active_role'] = $role;
        $_SESSION['role'] = $role;

        return true;
    }

    public function getActiveRole(): ?string
    {
        $this->startSession();
        return $_SESSION['active_role'] ?? $_SESSION['roles'][0] ?? null;
    }

    public function getAvailableRoles(): array
    {
        $this->startSession();
        if (!$this->isLoggedIn()) return [];
        return $_SESSION['roles'] ?? [];
    }

    // ============================================================
    // USER STATE
    // ============================================================

    public function getCurrentUser(): ?User
    {
        $this->startSession();

        if (!$this->isLoggedIn()) return null;

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

    public function getCurrentUserId(): int
    {
        $this->startSession();
        return $_SESSION['user_id'] ?? 0;
    }

    public function getCurrentUserRole(): ?string
    {
        return $this->getActiveRole();
    }

    public function isLoggedIn(): bool
    {
        $this->startSession();
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public function isAdmin(): bool
    {
        return $this->isLoggedIn() && $this->getActiveRole() === 'admin';
    }

    public function isSeller(): bool
    {
        return $this->isLoggedIn() && $this->getActiveRole() === 'seller';
    }

    public function isBuyer(): bool
    {
        return $this->isLoggedIn() && $this->getActiveRole() === 'buyer';
    }

    public function hasRole(string $role): bool
    {
        if (!$this->isLoggedIn()) return false;
        return in_array($role, $this->getAvailableRoles());
    }

    // ============================================================
    // CART
    // ============================================================

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

    // ============================================================
    // HTACCESS LOGIN
    // ============================================================

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
        $_SESSION['login_time'] = time();

        return true;
    }

    // ============================================================
    // LOGOUT
    // ============================================================

    public function logout(): void
    {
        $this->startSession();

        // Remove session from database
        $sessionId = session_id();
        $stmt = $this->db->prepare("DELETE FROM user_sessions WHERE session_id = ?");
        $stmt->bind_param('s', $sessionId);
        $stmt->execute();
        $stmt->close();

        $_SESSION = [];
        session_unset();
        session_destroy();

        if (isset($_COOKIE['CONSUTRADE_SESSION'])) {
            setcookie('CONSUTRADE_SESSION', '', time() - 3600, '/');
        }
    }
}
