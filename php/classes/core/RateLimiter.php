<?php

/**
 * ConsuTrade - Rate Limiter
 * Token bucket algorithm with MySQL storage.
 * Industry standard: returns 429 with Retry-After header.
 * 
 * @author Kamogelo Phale
 * @version 1.0.0
 */

class RateLimiter
{
    private mysqli $db;
    private string $table = 'rate_limits';

    public function __construct(mysqli $db)
    {
        $this->db = $db;
        $this->ensureTableExists();
    }

    /**
     * Creates the rate_limits table if it doesn't exist.
     */
    private function ensureTableExists(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            identifier VARCHAR(128) NOT NULL,
            endpoint VARCHAR(64) NOT NULL,
            tokens INT NOT NULL DEFAULT 0,
            first_request TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_request TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_limit (identifier, endpoint),
            INDEX idx_cleanup (last_request)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $this->db->query($sql);
    }

    /**
     * Check if request is allowed.
     *
     * @param string $endpoint e.g. 'login', 'add_to_cart'
     * @param int $maxRequests Max allowed in time window
     * @param int $windowSeconds Time window in seconds
     * @return array ['allowed' => bool, 'retry_after' => int, 'remaining' => int]
     */
    public function check(string $endpoint, int $maxRequests = 10, int $windowSeconds = 60): array
    {
        $identifier = $this->getIdentifier();
        $this->cleanup();

        $stmt = $this->db->prepare(
            "SELECT tokens, first_request 
             FROM {$this->table} 
             WHERE identifier = ? AND endpoint = ?"
        );
        $stmt->bind_param('ss', $identifier, $endpoint);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        // First request from this identifier for this endpoint
        if (!$row) {
            $stmt = $this->db->prepare(
                "INSERT INTO {$this->table} (identifier, endpoint, tokens, first_request, last_request) 
                 VALUES (?, ?, 1, NOW(), NOW())"
            );
            $stmt->bind_param('ss', $identifier, $endpoint);
            $stmt->execute();
            $stmt->close();

            return [
                'allowed' => true,
                'retry_after' => 0,
                'remaining' => $maxRequests - 1
            ];
        }

        $elapsed = time() - strtotime($row['first_request']);
        $used = (int)$row['tokens'];

        // Window has expired, reset
        if ($elapsed >= $windowSeconds) {
            $stmt = $this->db->prepare(
                "UPDATE {$this->table} 
                 SET tokens = 1, first_request = NOW(), last_request = NOW() 
                 WHERE identifier = ? AND endpoint = ?"
            );
            $stmt->bind_param('ss', $identifier, $endpoint);
            $stmt->execute();
            $stmt->close();

            return [
                'allowed' => true,
                'retry_after' => 0,
                'remaining' => $maxRequests - 1
            ];
        }

        // Rate limit exceeded
        if ($used >= $maxRequests) {
            $retryAfter = $windowSeconds - $elapsed;
            return [
                'allowed' => false,
                'retry_after' => max(1, (int)$retryAfter),
                'remaining' => 0
            ];
        }

        // Increment token count
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET tokens = tokens + 1, last_request = NOW() 
             WHERE identifier = ? AND endpoint = ?"
        );
        $stmt->bind_param('ss', $identifier, $endpoint);
        $stmt->execute();
        $stmt->close();

        return [
            'allowed' => true,
            'retry_after' => 0,
            'remaining' => $maxRequests - $used - 1
        ];
    }

    /**
     * Send 429 Too Many Requests response and exit.
     *
     * @param int $retryAfter Seconds until next request allowed
     */
    public function sendRateLimitResponse(int $retryAfter): void
    {
        header('HTTP/1.1 429 Too Many Requests');
        header('Retry-After: ' . $retryAfter);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Too many requests. Please try again in ' . $retryAfter . ' seconds.',
            'retry_after' => $retryAfter
        ]);
    }

    /**
     * Get identifier for rate limiting.
     * Uses user ID if logged in, otherwise IP hash.
     *
     * @return string
     */
    private function getIdentifier(): string
    {
        if (isset($GLOBALS['isLoggedIn']) && $GLOBALS['isLoggedIn']) {
            $userId = $GLOBALS['currentUser']->getUserId();
            return 'user_' . $userId;
        }

        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ip = explode(',', $ip)[0];

        return 'ip_' . hash('sha256', trim($ip));
    }

    /**
     * Remove expired entries older than 1 hour.
     */
    private function cleanup(): void
    {
        $this->db->query(
            "DELETE FROM {$this->table} WHERE last_request < DATE_SUB(NOW(), INTERVAL 1 HOUR)"
        );
    }
}
