<?php
/*
 * ConsuTrade - Profile Handler
 * Author: Kamogelo Phale
 * 
 * Handles profile updates and image uploads for user profiles
 */

require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = ['success' => false, 'message' => ''];

// Check if user is logged in using centralized auth
if (!$is_logged_in) {
    $response['message'] = 'Unauthorized. Please login to update your profile.';
    echo json_encode($response);
    exit;
}

$user_id = $current_user_id;
$action = isset($_POST['action']) ? $_POST['action'] : '';
$baseUrl = getBaseUrl();

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
    
    // Delete old profile image if exists
    $old_image_sql = "SELECT profile_image FROM users WHERE user_id = ?";
    $old_image_stmt = $conn->prepare($old_image_sql);
    $old_image_stmt->bind_param('i', $user_id);
    $old_image_stmt->execute();
    $old_image_result = $old_image_stmt->get_result();
    $old_image_row = $old_image_result->fetch_assoc();
    $old_image_stmt->close();
    
    if (!empty($old_image_row['profile_image'])) {
        $old_image_path = $_SERVER['DOCUMENT_ROOT'] . '/www/consutrade/' . $old_image_row['profile_image'];
        if (file_exists($old_image_path)) {
            unlink($old_image_path);
        }
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
            imagepalettetotruecolor($src);
            imagealphablending($src, true);
            imagesavealpha($src, true);
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
    
    if (!$src) {
        $response['message'] = 'Failed to process image.';
        echo json_encode($response);
        exit;
    }
    
    $new_width = 200;
    $new_height = 200;
    $thumb = imagecreatetruecolor($new_width, $new_height);
    
    // Preserve transparency for PNG
    if ($file_type === 'image/png') {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
        imagefilledrectangle($thumb, 0, 0, $new_width, $new_height, $transparent);
    }
    
    // Calculate crop to make square
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
    
    // Save as JPEG for consistency
    $image_path = 'uploads/profiles/' . $new_filename;
    $full_upload_path = $upload_dir . $new_filename;
    
    if ($file_type === 'image/png') {
        imagepng($thumb, $full_upload_path, 8);
    } else {
        imagejpeg($thumb, $full_upload_path, 90);
    }
    
    imagedestroy($src);
    imagedestroy($thumb);
    
    $update_sql = "UPDATE users SET profile_image = ? WHERE user_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('si', $image_path, $user_id);
    
    if ($update_stmt->execute()) {
        $_SESSION['profile_image'] = $image_path;
        $response['success'] = true;
        $response['message'] = 'Profile picture updated successfully!';
        $response['image_path'] = $baseUrl . $image_path;
        
        // Update current user data in session
        if (isset($_SESSION['user_id'])) {
            $_SESSION['profile_image_updated'] = time();
        }
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
    
    // Clean phone number
    $clean_phone = !empty($phone) ? preg_replace('/[^0-9]/', '', $phone) : '';
    if (!empty($clean_phone) && !preg_match('/^0[0-9]{9,10}$/', $clean_phone)) {
        $response['message'] = 'Please enter a valid South African phone number (e.g., 0712345678)';
        echo json_encode($response);
        exit;
    }
    
    if (empty($full_name)) {
        $response['message'] = 'Full name is required.';
        echo json_encode($response);
        exit;
    }
    
    if (strlen($full_name) > 100) {
        $response['message'] = 'Full name is too long.';
        echo json_encode($response);
        exit;
    }
    
    $update_sql = "UPDATE users SET full_name = ?, location = ?, phone = ? WHERE user_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('sssi', $full_name, $location, $clean_phone, $user_id);
    
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

$response['message'] = 'Invalid action.';
echo json_encode($response);
?>