<?php
/*
 * ConsuTrade - User Registration
 * Author: Kamogelo Phale
 * 
 * Handles new user registration for buyers and sellers using OOP
 */

require_once dirname(__DIR__, 2) . '/init.php';

// If user is already logged in, redirect to homepage
if ($auth->isLoggedIn()) {
    header('Location: ' . getBaseUrl() . 'index.php');
    exit;
}

// Only process if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $full_name        = trim($_POST['full_name'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $phone            = trim($_POST['phone'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role             = $_POST['role'] ?? 'buyer';
    
    $errors = [];
    
    // Prevent admin registration from main website
    if ($role === 'admin') {
        $errors['general'] = 'Invalid registration option.';
    }
    
    // Validation
    if (empty($full_name)) {
        $errors['full_name'] = 'Full name is required';
    } elseif (strlen($full_name) < 2) {
        $errors['full_name'] = 'Please enter your full name';
    } elseif (strlen($full_name) > 100) {
        $errors['full_name'] = 'Full name is too long';
    }
    
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    } elseif (strlen($email) > 255) {
        $errors['email'] = 'Email address is too long';
    }
    
    $clean_phone = '';
    if (empty($phone)) {
        $errors['phone'] = 'Phone number is required';
    } else {
        $clean_phone = preg_replace('/[^0-9]/', '', $phone);
        if (!preg_match('/^0[0-9]{9,10}$/', $clean_phone)) {
            $errors['phone'] = 'Please enter a valid South African phone number (e.g., 0712345678)';
        }
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters';
    } elseif (strlen($password) > 255) {
        $errors['password'] = 'Password is too long';
    }
    
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    // Check if email already exists using UserRepository
    if (empty($errors)) {
        $existing_user = $userRepo->getByEmail($email);
        
        if ($existing_user) {
            // Generic error for security
            $errors['email'] = 'Unable to register with this email. Please contact support if you believe this is an error.';
        }
    }
    
    // Check if phone already exists - using UserRepository method
    if (empty($errors) && !empty($clean_phone)) {
        // public function getByPhone(string $phone): ?array
        $existing_phone = $userRepo->getByPhone($clean_phone);
        
        if ($existing_phone) {
            $errors['phone'] = 'Unable to register with this phone number. Please contact support.';
        }
    }
    
    // If no errors, register the user
    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user using UserRepository
        $userData = [
            'full_name' => $full_name,
            'email' => $email,
            'phone' => $clean_phone,
            'password' => $hashed_password,
            'role' => $role
        ];
        
        $userId = $userRepo->createUser($userData);
        
        if ($userId) {
            // Login the user using Auth class
            $auth->login($email, $password);
            
            $_SESSION['flash'] = 'Welcome to ConsuTrade, ' . $full_name . '!';
            
            if ($role === 'seller') {
                header('Location: ' . getBaseUrl() . 'admin/seller-dashboard.php');
            } else {
                header('Location: ' . getBaseUrl() . 'index.php');
            }
            exit;
        } else {
            $errors['general'] = 'Registration failed. Please try again.';
        }
    }
    
    // If we have errors, store them and redirect back
    if (!empty($errors)) {
        $_SESSION['register_errors'] = $errors;
        $_SESSION['register_form_data'] = $_POST;
        header('Location: ' . getBaseUrl() . 'index.php');
        exit;
    }
}
?>