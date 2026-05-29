<?php
/*
 * ConsuTrade - User Login (Main Website)
 * Author: Kamogelo Phale
 * 
 * Handles user authentication for buyers on the main website using Auth class
 */

require_once dirname(__DIR__, 2) . '/init.php';

// Only process if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $errors = [];
    
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }
    
    if (empty($errors)) {
        // Use Auth class to handle login
        $loginResult = $auth->login($email, $password);
        
        if ($loginResult['success']) {
            // Check if the user is a buyer (main website only accepts buyers)
            if ($loginResult['role'] !== 'buyer') {
                // Destroy the session that was just created
                $auth->logout();
                $errors['general'] = 'This login is for buyers only. Please use the seller or admin portal.';
            } else {
                // Successful buyer login - session already set by Auth::login()
                $_SESSION['flash'] = 'Welcome back, ' . ($_SESSION['full_name'] ?? 'User') . '!';
                header('Location: ' . getBaseUrl() . 'index.php');
                exit;
            }
        } else {
            $errors['general'] = $loginResult['message'];
        }
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

// If not POST, redirect to home
header('Location: ' . getBaseUrl() . 'index.php');
exit;