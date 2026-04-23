<?php
/*
 * ConsuTrade - User Registration
 * Author: Kamogelo Phale
 */

session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'] ?? 'buyer';
    
    $errors = [];
    
    // Validation - Field specific errors only for empty/invalid fields
    if (empty($full_name)) {
        $errors['full_name'] = 'Full name is required';
    } elseif (strlen($full_name) < 2) {
        $errors['full_name'] = 'Full name must be at least 2 characters';
    }
    
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }
    
    if (empty($phone)) {
        $errors['phone'] = 'Phone number is required';
    } elseif (!preg_match('/^[0-9]{10}$/', preg_replace('/[^0-9]/', '', $phone))) {
        $errors['phone'] = 'Please enter a valid 10-digit phone number';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters';
    }
    
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    if (!in_array($role, ['buyer', 'seller'])) {
        $role = 'buyer';
    }
    
    // CHECK IF EMAIL ALREADY EXISTS - Use GENERAL error, not field-specific
    if (empty($errors)) {
        $check_sql = "SELECT user_id, role FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param('s', $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // GENERAL ERROR - Not field-specific
            $errors['general'] = 'Unable to create account. Please check your information and try again.';
        }
        $check_stmt->close();
    }
    
    // If no errors, create account
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (full_name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssss', $full_name, $email, $phone, $hashed_password, $role);
        
        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
            
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user_id;
            $_SESSION['full_name'] = $full_name;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = $role;
            $_SESSION['logged_in'] = true;
            
            $_SESSION['flash'] = 'Welcome to ConsuTrade, ' . $full_name . '!';
            
            if ($role === 'seller') {
                header('Location: /www/consutrade/admin/seller-dashboard.php');
            } else {
                header('Location: /www/consutrade/index.php');
            }
            exit;
        } else {
            $errors['general'] = 'Unable to create account. Please try again later.';
        }
        $stmt->close();
    }
    
    // If errors, redirect back
    if (!empty($errors)) {
        $_SESSION['register_errors'] = $errors;
        $_SESSION['register_form_data'] = [
            'full_name' => $full_name,
            'email' => $email,
            'phone' => $phone,
            'role' => $role
        ];
        header('Location: /www/consutrade/index.php');
        exit;
    }
    
    $conn->close();
}
?>