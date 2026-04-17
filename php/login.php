<?php
/*
 * ConsuTrade - User Login
 * Author: Kamogelo Phale
 *
 * What this does:
 * - Takes email and password from login form
 * - Checks if user exists in database
 * - Verifies password using password_verify()
 * - Starts session and redirects based on role (buyer/seller ONLY)
 * - Admin users are NOT allowed to login here (use admin/login.php)
 *
 */

session_start();
require_once 'config.php';

// Only process if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Get form data
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    $errors = [];
    
    // Basic validation
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }
    
    // If no validation errors, check database
    if (empty($errors)) {
        
        // Prepare statement to prevent SQL injection
        $sql = "SELECT user_id, full_name, email, password, role FROM users WHERE email = ? AND role != 'admin'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                
                // Password is correct - start session
                // Regenerate session ID to prevent fixation attacks (learned this from a security article)
                session_regenerate_id(true);
                
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                
                // Set flash message for success
                $_SESSION['flash'] = 'Welcome back, ' . $user['full_name'] . '!';
                
                // Redirect based on role (only buyer or seller)
                if ($user['role'] === 'seller') {
                    header('Location: /www/consutrade/admin/seller-dashboard.php');
                } else {
                    header('Location: /www/consutrade/index.php');
                }
                exit;
                
            } else {
                // Wrong password
                $errors['password'] = 'Incorrect password';
            }
        } else {
            // No user found with this email or user is admin
            $errors['email'] = 'Invalid email or password';
        }
        
        $stmt->close();
    }
    
    // If we got here, something went wrong - store errors and redirect back
    if (!empty($errors)) {
        $_SESSION['login_errors'] = $errors;
        header('Location: /www/consutrade/index.php');
        exit;
    }
    
    $conn->close();
}
?>