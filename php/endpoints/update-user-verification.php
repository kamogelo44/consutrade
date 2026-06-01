<?php
/*
 * ConsuTrade - Update User Verification (AJAX)
 * Author: Kamogelo Phale
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!$auth->isAdmin()) {
    $response['message'] = 'Unauthorized.';
    echo json_encode($response);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = isset($data['user_id']) ? (int)$data['user_id'] : 0;
$verify = isset($data['verify']) ? (bool)$data['verify'] : false;

if ($user_id <= 0) {
    $response['message'] = 'Invalid user.';
    echo json_encode($response);
    exit;
}

$targetUser = $userRepo->findById($user_id);

if (!$targetUser || $targetUser->getRole() !== 'seller') {
    $response['message'] = 'Only sellers can be verified.';
    echo json_encode($response);
    exit;
}

$new_status = $verify ? 1 : 0;

$sql = "UPDATE users SET id_verified = ? WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $new_status, $user_id);

if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = $verify ? 'Seller verified.' : 'Verification removed.';

    if ($currentUser && $currentUser->getUserId() === $user_id) {
        $updatedUser = $userRepo->findById($user_id);
        $_SESSION['user_object'] = serialize($updatedUser);
    }
} else {
    $response['message'] = 'Could not update verification.';
}
$stmt->close();

echo json_encode($response);
