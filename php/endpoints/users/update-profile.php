<?php
/*
 * ConsuTrade - Update Profile Handler
 * Author: Kamogelo Phale
 * 
 * Handles profile updates and profile image uploads for all user types.
 * Controller only - business logic is in UserService/UserRepository.
 */

require_once dirname(__DIR__, 3) . '/init.php';

// Rate limit: 10 profile updates per minute
rateLimit('update_profile', 10, 60);

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!$auth->isLoggedIn()) {
    $response['message'] = 'Please login.';
    echo json_encode($response);
    exit;
}

$current_user_id = $currentUser->getUserId();
$action = $_POST['action'] ?? '';

// ============================================
// UPLOAD PROFILE IMAGE
// ============================================
if ($action === 'upload_image') {
    if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'No image uploaded.';
        echo json_encode($response);
        exit;
    }

    $file = $_FILES['profile_image'];

    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        $response['message'] = 'Invalid file type. Please upload JPG, PNG, GIF, or WebP.';
        echo json_encode($response);
        exit;
    }

    // Validate file size (max 2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        $response['message'] = 'File is too large. Maximum size is 2MB.';
        echo json_encode($response);
        exit;
    }

    $uploadDir = dirname(__DIR__, 3) . '/uploads/profiles/';

    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'user_' . $current_user_id . '_' . time() . '.' . $ext;
    $dest = $uploadDir . $filename;

    // Delete old profile image
    $current_profile_image = $currentUser->getProfileImage();
    if (!empty($current_profile_image)) {
        $oldPath = $_SERVER['DOCUMENT_ROOT'] . '/www/consutrade/' . $current_profile_image;
        if (file_exists($oldPath) && basename($oldPath) !== 'default-avatar.png') {
            unlink($oldPath);
        }
    }

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        $imagePath = 'uploads/profiles/' . $filename;
        $result = $userRepo->updateProfileImage($current_user_id, $imagePath);

        if ($result) {
            $_SESSION['profile_image'] = $imagePath;
            $updatedUser = $userRepo->findById($current_user_id);
            $_SESSION['user_object'] = serialize($updatedUser);

            $response['success'] = true;
            $response['message'] = 'Profile image updated.';
            $response['image_url'] = getBaseUrl() . $imagePath;
        } else {
            $response['message'] = 'Could not save image.';
        }
    } else {
        $response['message'] = 'Could not upload image.';
    }

    echo json_encode($response);
    exit;
}

// ============================================
// UPDATE PROFILE INFO
// ============================================
if ($action === 'update_profile') {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? '');

    if (empty($fullName)) {
        $response['message'] = 'Name is required.';
        echo json_encode($response);
        exit;
    }

    $cleanPhone = !empty($phone) ? preg_replace('/[^0-9]/', '', $phone) : '';
    if (!empty($cleanPhone) && !preg_match('/^0[0-9]{9,10}$/', $cleanPhone)) {
        $response['message'] = 'Please enter a valid phone number.';
        echo json_encode($response);
        exit;
    }

    $updateData = ['full_name' => $fullName];
    if (!empty($cleanPhone)) $updateData['phone'] = $cleanPhone;
    if (!empty($location)) $updateData['location'] = $location;

    $result = $userRepo->updateProfile($current_user_id, $updateData);

    if ($result) {
        $_SESSION['full_name'] = $fullName;
        if (!empty($cleanPhone)) $_SESSION['phone'] = $cleanPhone;
        if (!empty($location)) $_SESSION['location'] = $location;

        $updatedUser = $userRepo->findById($current_user_id);
        $_SESSION['user_object'] = serialize($updatedUser);

        $response['success'] = true;
        $response['message'] = 'Profile updated.';
    } else {
        $response['message'] = 'Could not update profile.';
    }

    echo json_encode($response);
    exit;
}

$response['message'] = 'Invalid action.';
echo json_encode($response);
