<?php
/*
 * ConsuTrade - Update Profile Handler
 * Author: Kamogelo Phale
 * 
 * Handles profile updates and profile image uploads for all user types using OOP
 */

require_once dirname(__DIR__, 2) . '/init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Check if user is logged in using Auth class
if (!$auth->isLoggedIn()) {
    $response['message'] = 'Please login.';
    echo json_encode($response);
    exit;
}

$current_user_id = $auth->getCurrentUser()['user_id'];
$action = $_POST['action'] ?? '';

// ------------------------------------------------------------------
// Upload profile image
// ------------------------------------------------------------------
if ($action === 'upload_image') {
    if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'No image uploaded.';
        echo json_encode($response);
        exit;
    }

    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/www/consutrade/uploads/profiles/';

    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $file = $_FILES['profile_image'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'user_' . $current_user_id . '_' . time() . '.' . $ext;
    $dest = $uploadDir . $filename;

    // Get current profile image using UserRepository
    $current_user_data = $userRepo->getById($current_user_id);
    
    if ($current_user_data && !empty($current_user_data['profile_image'])) {
        $oldPath = $_SERVER['DOCUMENT_ROOT'] . '/www/consutrade/' . $current_user_data['profile_image'];
        if (file_exists($oldPath) && basename($oldPath) !== 'default-avatar.png') {
            unlink($oldPath);
        }
    }

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        $imagePath = 'uploads/profiles/' . $filename;
        
        // Update using UserRepository
        $result = $userRepo->updateProfileImage($current_user_id, $imagePath);

        if ($result) {
            $_SESSION['profile_image'] = $imagePath;
            
            // Update the current user object in session if it exists
            if (isset($currentUser) && method_exists($currentUser, 'getProfileImage')) {
                // The user object will be recreated on next page load
            }
            
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

// ------------------------------------------------------------------
// Update profile info
// ------------------------------------------------------------------
if ($action === 'update_profile') {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? '');

    if (empty($fullName)) {
        $response['message'] = 'Name is required.';
        echo json_encode($response);
        exit;
    }

    // Clean phone number
    $cleanPhone = !empty($phone) ? preg_replace('/[^0-9]/', '', $phone) : '';
    if (!empty($cleanPhone) && !preg_match('/^0[0-9]{9,10}$/', $cleanPhone)) {
        $response['message'] = 'Please enter a valid phone number.';
        echo json_encode($response);
        exit;
    }

    // Update using UserRepository
    $updateData = ['full_name' => $fullName];
    if (!empty($cleanPhone)) {
        $updateData['phone'] = $cleanPhone;
    }
    if (!empty($location)) {
        $updateData['location'] = $location;
    }
    
    $result = $userRepo->updateProfile($current_user_id, $updateData);

    if ($result) {
        // Update session data
        $_SESSION['full_name'] = $fullName;
        if (!empty($cleanPhone)) {
            $_SESSION['phone'] = $cleanPhone;
        }
        if (!empty($location)) {
            $_SESSION['location'] = $location;
        }
        
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
?>