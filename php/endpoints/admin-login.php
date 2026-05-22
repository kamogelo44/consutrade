<?php
/*
 * ConsuTrade - Admin/Seller Login Handler
 * Author: Kamogelo Phale
 */

require_once __DIR__ . '/../init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . getBaseUrl() . 'admin/login.php');
    exit;
}

$email    = trim($_POST['email']);
$password = $_POST['password'];
$role_type = $_POST['role_type'] ?? 'admin';

if (empty($email) || empty($password)) {
    $_SESSION['login_error'] = 'Please enter both email and password';
    header('Location: ' . getBaseUrl() . 'admin/login.php');
    exit;
}

if ($role_type === 'admin') {
    $sql = "SELECT user_id, full_name, email, password, role FROM users WHERE email = ? AND role = 'admin'";
} else {
    $sql = "SELECT user_id, full_name, email, password, role FROM users WHERE email = ? AND role = 'seller'";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    
    if (password_verify($password, $user['password'])) {
        if ($role_type === 'admin') {
            $auth->loginAdmin($user['user_id'], $user['full_name'], $user['email'], $user['role']);
            header('Location: ' . getBaseUrl() . 'admin/admin-dashboard.php');
        } else {
            $auth->loginSeller($user['user_id'], $user['full_name'], $user['email'], $user['role']);
            header('Location: ' . getBaseUrl() . 'admin/seller-dashboard.php');
        }
        exit;
    }
}

$stmt->close();

$_SESSION['login_error'] = 'Invalid email or password.';
$_SESSION['login_email'] = $email;
header('Location: ' . getBaseUrl() . 'admin/login.php');
exit;