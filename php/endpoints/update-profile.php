<?php
/*
 * ConsuTrade - Update Profile Handler
 * Author: Kamogelo Phale
 * 
 * Handles profile updates and profile image uploads for all user types
 */

require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!$is_logged_in) {
    $response['message'] = 'Please login.';
    echo json_encode($response);
    exit;
}

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

    $file     = $_FILES['profile_image'];
    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'user_' . $current_user_id . '.' . $ext;
    $dest     = $uploadDir . $filename;

    // Remove old profile image if exists
    $oldSql = "SELECT profile_image FROM users WHERE user_id = ?";
    $oldStmt = $conn->prepare($oldSql);
    $oldStmt->bind_param('i', $current_user_id);
    $oldStmt->execute();
    $oldResult = $oldStmt->get_result();
    if ($oldRow = $oldResult->fetch_assoc()) {
        if (!empty($oldRow['profile_image'])) {
            $oldPath = $_SERVER['DOCUMENT_ROOT'] . '/www/consutrade/' . $oldRow['profile_image'];
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }
    }
    $oldStmt->close();

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        $imagePath = 'uploads/profiles/' . $filename;

        $updateSql = "UPDATE users SET profile_image = ? WHERE user_id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param('si', $imagePath, $current_user_id);

        if ($updateStmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Profile image updated.';
            $response['image_url'] = getBaseUrl() . $imagePath;
        } else {
            $response['message'] = 'Could not save image.';
        }
        $updateStmt->close();
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
    $phone    = trim($_POST['phone'] ?? '');
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

    $sql  = "UPDATE users SET full_name = ?, phone = ?, location = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssi', $fullName, $cleanPhone, $location, $current_user_id);

    if ($stmt->execute()) {
        $_SESSION['full_name'] = $fullName;
        $response['success'] = true;
        $response['message'] = 'Profile updated.';
    } else {
        $response['message'] = 'Could not update profile.';
    }
    $stmt->close();

    echo json_encode($response);
    exit;
}

$response['message'] = 'Invalid action.';
echo json_encode($response);