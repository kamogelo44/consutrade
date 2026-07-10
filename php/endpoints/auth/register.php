<?php
/*
 * ConsuTrade - User Registration
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 3) . '/init.php';

// Rate limit: 3 registrations per hour
rateLimit('register', 3, 3600);

$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($auth->isLoggedIn()) {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'Already logged in']);
        exit;
    }
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $full_name        = trim($_POST['full_name'] ?? '');
    $email            = strtolower(trim($_POST['email'] ?? ''));
    $phone            = trim($_POST['phone'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role             = $_POST['role'] ?? 'buyer';

    $errors = [];

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
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    } elseif (strlen($password) > 255) {
        $errors['password'] = 'Password is too long';
    }

    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }

    // Check if user exists
    $existing_user = null;
    if (empty($errors)) {
        $existing_user = $userRepo->findByEmail($email);
    }

    // If user exists and is trying to become a seller
    if ($existing_user && $role === 'seller' && empty($errors)) {
        if ($existing_user->hasRole('seller')) {
            $auth->login($email, $password, 'admin');
            $_SESSION['flash'] = 'Welcome back! You already have a seller account.';

            $redirect_url = $baseUrl . 'admin/seller-dashboard.php';

            if ($is_ajax) {
                echo json_encode(['success' => true, 'redirect' => $redirect_url]);
                exit;
            }
            header('Location: ' . $redirect_url);
            exit;
        } else {
            $userId = $existing_user->getUserId();
            $result = $userRepo->addRole($userId, 'seller');

            if ($result) {
                $auth->login($email, $password, 'admin');
                $_SESSION['flash'] = 'Seller access added to your account!';

                $redirect_url = $baseUrl . 'admin/seller-dashboard.php';

                if ($is_ajax) {
                    echo json_encode(['success' => true, 'redirect' => $redirect_url]);
                    exit;
                }
                header('Location: ' . $redirect_url);
                exit;
            } else {
                $errors['general'] = 'Failed to add seller access. Please try again.';
            }
        }
    }

    if ($existing_user && $role === 'buyer' && empty($errors)) {
        $errors['email'] = 'An account with this email already exists. Please login instead.';
    }

    if (!$existing_user && empty($errors)) {
        if (!empty($clean_phone)) {
            $existing_phone = $userRepo->findByPhone($clean_phone);
            if ($existing_phone) {
                $errors['phone'] = 'Unable to register with this phone number. Please contact support.';
            }
        }
    }

    // Create new user via Auth class
    if (empty($errors) && !$existing_user) {
        $userData = [
            'full_name' => $full_name,
            'email' => $email,
            'phone' => $clean_phone,
            'password' => $password,
            'role' => $role
        ];

        $result = $auth->register($userData);

        if ($result['success']) {
            if ($is_ajax) {
                echo json_encode([
                    'success' => true,
                    'message' => $result['message'],
                    'redirect' => $baseUrl . 'index.php?verified=pending'
                ]);
                exit;
            }
            $_SESSION['flash'] = $result['message'];
            header('Location: ' . $baseUrl . 'index.php?verified=pending');
            exit;
        } else {
            $errors['general'] = $result['message'];
        }
    }

    if (!empty($errors)) {
        if ($is_ajax) {
            echo json_encode([
                'success' => false,
                'errors' => $errors,
                'form_data' => $_POST
            ]);
            exit;
        }
        $_SESSION['register_errors'] = $errors;
        $_SESSION['register_form_data'] = $_POST;
        header('Location: ' . $baseUrl . 'index.php');
        exit;
    }
}
