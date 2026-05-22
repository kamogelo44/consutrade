<?php
/*
 * ConsuTrade - User Login (Main Website)
 * Author: Kamogelo Phale
 * 
 * Handles user authentication for buyers on the main website
 */

require_once __DIR__ . '/../init.php';

// Only process if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    
    $errors = [];
    
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }
    
    if (empty($errors)) {
        // Verify credentials and role
        $sql = "SELECT user_id, full_name, email, password, role FROM users WHERE email = ? AND role = 'buyer'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Use Auth class to log in
                $auth->loginUser($user['user_id'], $user['full_name'], $user['email'], $user['role']);
                
                $_SESSION['flash'] = 'Welcome back, ' . $user['full_name'] . '!';
                
                header('Location: ' . getBaseUrl() . 'index.php');
                exit;
            } else {
                $errors['general'] = 'Invalid email or password.';
            }
        } else {
            $errors['general'] = 'Invalid email or password.';
        }
        
        $stmt->close();
    }
    
    // If we have errors, store them and redirect back
    if (!empty($errors)) {
        $_SESSION['login_errors'] = $errors;
        $_SESSION['login_email'] = $email;
        
        $redirect = $_SERVER['HTTP_REFERER'] ?? getBaseUrl() . 'index.php';
        header('Location: ' . $redirect);
        exit;
    }
}