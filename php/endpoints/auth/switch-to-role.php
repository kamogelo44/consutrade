<?php
/*
 * ConsuTrade - Switch Role Endpoint
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 3) . '/init.php';

// Rate limit: 10 switches per minute
rateLimit('switch_role', 10, 60);

$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$is_ajax) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$role = $data['role'] ?? '';

if (empty($role)) {
    echo json_encode(['success' => false, 'message' => 'Role is required']);
    exit;
}

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if (!$auth->hasRole($role)) {
    echo json_encode(['success' => false, 'message' => 'Invalid role for this user']);
    exit;
}

if ($auth->switchRole($role)) {
    $redirect = match ($role) {
        'admin' => getBaseUrl() . 'admin/admin-dashboard.php',
        'seller' => getBaseUrl() . 'admin/seller-dashboard.php',
        default => getBaseUrl() . 'index.php'
    };
    echo json_encode(['success' => true, 'redirect' => $redirect]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to switch role']);
}
exit;
