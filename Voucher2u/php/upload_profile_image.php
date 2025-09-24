<?php
session_start();
require_once '../../databaseConnection/db_config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unexpected error occurred.'];

if (!isset($_SESSION['Id'])) {
    $response['message'] = 'User not logged in.';
    echo json_encode($response);
    exit;
}

$userId = $_SESSION['Id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
    $response['message'] = 'No file uploaded or upload error.';
    echo json_encode($response);
    exit;
}

$file = $_FILES['profile_image'];
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowedTypes)) {
    $response['message'] = 'Unsupported file type. Allowed types: jpg, png, gif, webp.';
    echo json_encode($response);
    exit;
}

// Limit file size to 5MB
if ($file['size'] > 5 * 1024 * 1024) {
    $response['message'] = 'File too large. Max 5MB.';
    echo json_encode($response);
    exit;
}

$uploadsDir = __DIR__ . '/../uploads/profile_images';
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}

// Generate a safe filename
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'profile_' . $userId . '_' . time() . '.' . $ext;
$targetPath = $uploadsDir . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    $response['message'] = 'Failed to move uploaded file.';
    echo json_encode($response);
    exit;
}

// Save relative URL to DB (relative to this HTML location)
$imageUrl = '../uploads/profile_images/' . $filename;

try {
    $stmt = $pdo->prepare("UPDATE User SET Profile_image = ? WHERE Id = ?");
    $stmt->execute([$imageUrl, $userId]);

    // Optionally update session
    $_SESSION['Profile_image'] = $imageUrl;

    $response['success'] = true;
    $response['message'] = 'Profile image uploaded successfully.';
    $response['imageUrl'] = $imageUrl;
} catch (PDOException $e) {
    error_log('Profile image upload DB error: ' . $e->getMessage());
    $response['message'] = 'Database error while saving image.';
}

echo json_encode($response);
?>