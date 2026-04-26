<?php
/*
 * ConsuTrade - User Registration
 * Author: Kamogelo Phale
 */

require_once 'helpers.php';
require_once 'config.php';

// If user is already logged in, redirect to homepage
if (isUserLoggedIn()) {
    header('Location: /www/consutrade/index.php');
    exit;
}

// Only process if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'] ?? 'buyer';
    
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
    }
    
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }
    
    if (empty($phone)) {
        $errors['phone'] = 'Phone number is required';
    } else {
        // Clean phone number (remove spaces, dashes, etc.)
        $clean_phone = preg_replace('/[^0-9]/', '', $phone);
        // Check if it's a valid SA phone number (10-11 digits starting with 0)
        if (!preg_match('/^0[0-9]{9,10}$/', $clean_phone)) {
            $errors['phone'] = 'Please enter a valid South African phone number (e.g., 0712345678)';
        }
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters';
    }
    
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    // Check if email already exists
    if (empty($errors)) {
        $check_sql = "SELECT user_id, role FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param('s', $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $existing_user = $check_result->fetch_assoc();
            if ($existing_user['role'] === 'admin') {
                $errors['general'] = 'Cannot register with this email.';
            } else {
                $errors['email'] = 'Email already registered. Please login instead.';
            }
        }
        $check_stmt->close();
    }
    
    // Check if phone already exists (optional - depends on your business logic)
    if (empty($errors) && !empty($clean_phone)) {
        $phone_sql = "SELECT user_id FROM users WHERE phone = ?";
        $phone_stmt = $conn->prepare($phone_sql);
        $phone_stmt->bind_param('s', $clean_phone);
        $phone_stmt->execute();
        $phone_result = $phone_stmt->get_result();
        
        if ($phone_result->num_rows > 0) {
            $errors['phone'] = 'Phone number already registered.';
        }
        $phone_stmt->close();
    }
    
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (full_name, email, phone, password, role, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssss', $full_name, $email, $clean_phone, $hashed_password, $role);
        
        if ($stmt->execute()) {
            // Registration successful
            $_SESSION['flash'] = 'Registration successful! Please login.';
            header('Location: /www/consutrade/index.php');
            exit;
        } else {
            $errors['general'] = 'Registration failed. Please try again.';
        }
        $stmt->close();
    }
    
    $conn->close();
    
    if (!empty($errors)) {
        $_SESSION['register_errors'] = $errors;
        $_SESSION['register_form_data'] = $_POST;
        header('Location: /www/consutrade/index.php');
        exit;
    }
}
?>