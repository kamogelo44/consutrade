<?php
/*
 * ConsuTrade - User Login (Main Website)
 * Author: Kamogelo Phale
 */

require_once 'helpers.php'; 
require_once 'config.php';

// Only process if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    $errors = [];
    
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }
    
    if (empty($errors)) {
        // Simple query - just check buyer role
        $sql = "SELECT user_id, full_name, email, password, role FROM users WHERE email = ? AND role = 'buyer'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Start session (config already set)
                startSession('user');
                
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                
                // Save session
                session_write_close();
                
                $_SESSION['flash'] = 'Welcome back, ' . $user['full_name'] . '!';
                
                header('Location: /www/consutrade/index.php');
                exit;
            } else {
                $errors['general'] = 'Invalid email or password.';
            }
        } else {
            $errors['general'] = 'Invalid email or password.';
        }
        
        $stmt->close();
        $conn->close();
    }
    
    if (!empty($errors)) {
        startSession('user');
        $_SESSION['login_errors'] = $errors;
        $_SESSION['login_email'] = $email;
        session_write_close();
        header('Location: /www/consutrade/index.php');
        exit;
    }
}
?>