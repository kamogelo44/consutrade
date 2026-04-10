<?php
/*
 * ConsuTrade - User Registration
 * Author: Kamogelo Phale
 * 
 * What this file does:
 * 1. Takes data from the registration form
 * 2. Checks if email is valid and password is strong enough
 * 3. Makes sure email isn't already in the database
 * 4. Hashes password (I use password_hash() - learned about this from a security video)
 * 5. Creates the user account
 * 6. Logs them in automatically
 * 
 * Problems I ran into:
 * - Forgot to start session at the top, took 30 mins to figure out why variables weren't saving
 * - The prepared statement bind_param had wrong number of parameters (had 4, needed 5)
 * 
 */
// Start session to store error messages and form data
session_start();
require_once 'config.php';

// Only process if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Collect and sanitise form data
    $full_name = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['user_type'];
    
    //========================================
    // VALIDATION
    //========================================
    
    $errors = [];

    // Check full name is not empty
    if (empty($full_name)) {
        $errors['fullname'] = 'Full name is required';
    }

    // Check email is valid
    if (empty($email)) {
        $errors['email'] = 'Email address is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }

    // Check password strength
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    }

    // Check passwords match
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }

    // Validate role is either buyer or seller
    if (!in_array($role, ['buyer', 'seller'])) {
        $errors['role'] = 'Please select a valid account type';
    }
    
    //=======================================================
    // CHECK IF EMAIL ALREADY EXISTS IN DATABASE
    //=======================================================

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

    //================================================================
    // INSERT INTO DATABASE (NO ERRORS FOUND)
    //================================================================

    if (empty($errors)) {
        // Hash the password for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Prepare INSERT statement
        $sql = "INSERT INTO users (full_name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssss', $full_name, $email, $hashed_password, $role);
        
        if ($stmt->execute()) {
            // Get the new user's ID for the session
            $new_user_id = $conn->insert_id;

            // Start a session for the new user (auto-login after registration)
            $_SESSION['user_id'] = $new_user_id;
            $_SESSION['full_name'] = $full_name;
            $_SESSION['role'] = $role;
            $_SESSION['logged_in'] = true;

            // Redirect based on user role
            if ($role === 'seller') {
                header('Location: ../seller-dashboard.php');
            } else {
                header('Location: ../index.php?registered=success');
            }
            exit;
        } else {
            $errors['general'] = 'Something went wrong. Please try again';
        }
        $stmt->close();
    }

    //================================================================
    // IF ERRORS EXIST - STORE IN SESSION AND REDIRECT BACK
    //================================================================

    if (!empty($errors)) {
        // Store errors in session to display on the form page
        $_SESSION['register_errors'] = $errors;
        
        // Store form data to pre-fill the form
        $_SESSION['register_form_data'] = [
            'fullname' => $full_name,
            'email' => $email,
            'role' => $role
        ];
        
        // Redirect back to index page with parameter to open register modal
        $_SESSION['flash'] = 'Registration successful. Welcome to ConsuTrade.';
        header('Location: ../index.php');
        exit;
    }
    
    $conn->close();
}
?>