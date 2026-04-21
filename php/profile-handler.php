<?php
/*
 * ConsuTrade - Profile Handler
 * Author: Kamogelo Phale
 * 
 * Handles profile updates and image uploads
 */

session_start();
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = isset($_POST['action']) ? $_POST['action'] : '';

// Handle profile image upload
if ($action === 'upload_image' && isset($_FILES['profile_image'])) {
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = $_FILES['profile_image']['type'];
    $file_size = $_FILES['profile_image']['size'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    if (!in_array($file_type, $allowed_types)) {
        $response['message'] = 'Only JPG, PNG, GIF, and WEBP images are allowed.';
        echo json_encode($response);
        exit;
    }
    
    if ($file_size > $max_size) {
        $response['message'] = 'Image size must be less than 2MB.';
        echo json_encode($response);
        exit;
    }
    
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/www/consutrade/uploads/profiles/';
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
    $new_filename = 'user_' . $user_id . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;
    
    // Resize image
    $image_data = getimagesize($_FILES['profile_image']['tmp_name']);
    $width = $image_data[0];
    $height = $image_data[1];
    
    switch ($file_type) {
        case 'image/jpeg':
        case 'image/jpg':
            $src = imagecreatefromjpeg($_FILES['profile_image']['tmp_name']);
            break;
        case 'image/png':
            $src = imagecreatefrompng($_FILES['profile_image']['tmp_name']);
            break;
        case 'image/gif':
            $src = imagecreatefromgif($_FILES['profile_image']['tmp_name']);
            break;
        case 'image/webp':
            $src = imagecreatefromwebp($_FILES['profile_image']['tmp_name']);
            break;
        default:
            $src = imagecreatefromjpeg($_FILES['profile_image']['tmp_name']);
    }
    
    $new_width = 200;
    $new_height = 200;
    $thumb = imagecreatetruecolor($new_width, $new_height);
    
    if ($width > $height) {
        $crop_x = ($width - $height) / 2;
        $crop_y = 0;
        $crop_width = $height;
        $crop_height = $height;
    } else {
        $crop_x = 0;
        $crop_y = ($height - $width) / 2;
        $crop_width = $width;
        $crop_height = $width;
    }
    
    imagecopyresampled($thumb, $src, 0, 0, $crop_x, $crop_y, $new_width, $new_height, $crop_width, $crop_height);
    imagejpeg($thumb, $upload_path, 90);
    
    
    $image_path = 'uploads/profiles/' . $new_filename;
    $update_sql = "UPDATE users SET profile_image = ? WHERE user_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('si', $image_path, $user_id);
    
    if ($update_stmt->execute()) {
        $_SESSION['profile_image'] = $image_path;
        $response['success'] = true;
        $response['message'] = 'Profile picture updated successfully!';
        $response['image_path'] = $baseUrl . $image_path;
    } else {
        $response['message'] = 'Failed to update profile picture.';
    }
    $update_stmt->close();
    
    echo json_encode($response);
    exit;
}

// Handle profile update
if ($action === 'update_profile') {
    $full_name = trim($_POST['full_name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($full_name)) {
        $response['message'] = 'Full name is required.';
        echo json_encode($response);
        exit;
    }
    
    $update_sql = "UPDATE users SET full_name = ?, location = ?, phone = ? WHERE user_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('sssi', $full_name, $location, $phone, $user_id);
    
    if ($update_stmt->execute()) {
        $_SESSION['full_name'] = $full_name;
        $_SESSION['location'] = $location;
        $response['success'] = true;
        $response['message'] = 'Profile updated successfully!';
    } else {
        $response['message'] = 'Failed to update profile.';
    }
    $update_stmt->close();
    
    echo json_encode($response);
    exit;
}

$conn->close();
echo json_encode($response);
?>