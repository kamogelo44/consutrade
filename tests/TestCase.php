<?php

use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // 1. Tell config.php to switch targets to the test database
        if (!defined('PHPUNIT_TESTING')) {
            define('PHPUNIT_TESTING', true);
        }

        // 2. Mock basic session states so init.php doesn't send real headers
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        // 3. Bootstrap your whole application layer
        require_once __DIR__ . '/../init.php';

        // 4. Wipe any active user authentication context for clean test isolation
        $_SESSION = [];

        // 5. Start our clean rollback transaction boundaries
        $db = Database::getInstance();
        $db->beginTransaction();
    }

    protected function tearDown(): void
    {
        // Cancel all database writes made during the test execution
        $db = Database::getInstance();
        $db->rollBack();

        // Close any active session structures
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        parent::tearDown();
    }
}
