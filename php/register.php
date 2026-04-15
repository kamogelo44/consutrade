<?php
/*
 * ConsuTrade - User Registration
 * Author: Kamogelo Phale
 * 
 * This file handles user registration and redirects based on role
 */

session_start();
require_once 'config.php';

$baseUrl = "/www/consutrade/";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $full_name = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['user_type'];
    $location = isset($_POST['location']) ? trim($_POST['location']) : '';
    
    $errors = [];

    // Validation
    if (empty($full_name)) {
        $errors['fullname'] = 'Full name is required';
    }

    if (empty($email)) {
        $errors['email'] = 'Email address is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    }

    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }

    if (!in_array($role, ['buyer', 'seller'])) {
        $errors['role'] = 'Please select a valid account type';
    }
    
    if (empty($location) && $role === 'seller') {
        $errors['location'] = 'Location is required for sellers';
    }
    
    // Check if email already exists
    if (empty($errors)) {
        $check_sql = "SELECT user_id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param('s', $email);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $errors['email'] = 'An account with this email already exists';
        }
        $check_stmt->close();
    }

    // Insert into database
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (full_name, email, password, role, location, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssss', $full_name, $email, $hashed_password, $role, $location);
        
        if ($stmt->execute()) {
            $new_user_id = $conn->insert_id;
            $_SESSION['user_id'] = $new_user_id;
            $_SESSION['full_name'] = $full_name;
            $_SESSION['role'] = $role;
            $_SESSION['location'] = $location;
            $_SESSION['logged_in'] = true;

            // Redirect based on role
            if ($role === 'seller') {
                header('Location: ' . $baseUrl . 'admin/seller-dashboard.php');
            } else {
                header('Location: ' . $baseUrl . 'index.php?registered=success');
            }
            exit;
        } else {
            $errors['general'] = 'Something went wrong. Please try again';
        }
        $stmt->close();
    }

    // If errors exist, store and redirect back
    if (!empty($errors)) {
        $_SESSION['register_errors'] = $errors;
        $_SESSION['register_form_data'] = [
            'fullname' => $full_name,
            'email' => $email,
            'role' => $role,
            'location' => $location
        ];
        header('Location: ' . $baseUrl . 'index.php?open=register');
        exit;
    }
    
    $conn->close();
}
?>