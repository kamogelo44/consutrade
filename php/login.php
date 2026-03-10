<?php
/*
 * ConsuTrade - User Login
 * Author: Kamogelo Phale
 *
 * This file will handle:
 * - Collecting login form data (email + password)
 * - Verifying password against hashed value using password_verify()
 * - Starting a secure session on successful login
 * - Redirecting user based on their role (buyer, seller, admin)
 *
 * Security: Session management and prepared statements
 */
?>
