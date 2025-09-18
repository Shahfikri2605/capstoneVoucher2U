<?php
session_start();
require_once '../../databaseConnection/db_config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unexpected error occurred.'];

// Check if user is logged in
if (!isset($_SESSION['Id'])) {
    $response['message'] = 'User not logged in.';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

$userId = $_SESSION['Id'];
$username = trim($_POST['Username'] ?? '');
$email = trim($_POST['Email'] ?? '');
$phoneNumber = trim($_POST['Phone_number'] ?? '');
$address = trim($_POST['Address'] ?? '');
$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Basic validation
if (empty($username) || empty($email)) {
    $response['message'] = 'Username and email are required.';
    echo json_encode($response);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Please enter a valid email.';
    echo json_encode($response);
    exit;
}

// Password validation if password change is requested
$passwordChangeRequested = !empty($newPassword);

if ($passwordChangeRequested) {
    if (empty($currentPassword)) {
        $response['message'] = 'Current password is required to set a new password.';
        echo json_encode($response);
        exit;
    }
    
    if ($newPassword !== $confirmPassword) {
        $response['message'] = 'New passwords do not match.';
        echo json_encode($response);
        exit;
    }
    
    if (strlen($newPassword) < 8) {
        $response['message'] = 'New password must be at least 8 characters long.';
        echo json_encode($response);
        exit;
    }
}

try {
    // Check if email is already taken by another user
    $stmt = $pdo->prepare("SELECT Id FROM User WHERE Email = ? AND Id != ?");
    $stmt->execute([$email, $userId]);
    if ($stmt->fetch()) {
        $response['message'] = 'This email is already taken by another user.';
        echo json_encode($response);
        exit;
    }

    // If password change is requested, verify current password first
    if ($passwordChangeRequested) {
        $stmt = $pdo->prepare("SELECT Password FROM User WHERE Id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $response['message'] = 'User not found.';
            echo json_encode($response);
            exit;
        }
        
        // Simple password verification (plain text comparison)
        if ($currentPassword !== $user['Password']) {
            $response['message'] = 'Current password is incorrect.';
            echo json_encode($response);
            exit;
        }
        
        // Update with new password (stored as plain text)
        $stmt = $pdo->prepare("
            UPDATE User 
            SET Username = ?, Email = ?, Phone_number = ?, Address = ?, Password = ?
            WHERE Id = ?
        ");
        $stmt->execute([$username, $email, $phoneNumber, $address, $newPassword, $userId]);
        
    } else {
        // Update without changing password
        $stmt = $pdo->prepare("
            UPDATE User 
            SET Username = ?, Email = ?, Phone_number = ?, Address = ?
            WHERE Id = ?
        ");
        $stmt->execute([$username, $email, $phoneNumber, $address, $userId]);
    }

    // Update session variables
    $_SESSION['userName'] = $username;
    $_SESSION['userEmail'] = $email;

    // Fetch updated user data to return
    $stmt = $pdo->prepare("
        SELECT 
            Id, 
            Username, 
            Email, 
            Phone_number, 
            Address, 
            Points, 
            Profile_image,
            About_me,
            Is_active
        FROM User 
        WHERE Id = ?
    ");
    $stmt->execute([$userId]);
    $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($updatedUser) {
        // Handle NULL values and add missing fields
        $updatedUser['Phone_number'] = $updatedUser['Phone_number'] ?? '';
        $updatedUser['Address'] = $updatedUser['Address'] ?? '';
        $updatedUser['Points'] = $updatedUser['Points'] ?? 0;
        $updatedUser['Profile_image'] = $updatedUser['Profile_image'] ?? '';
        $updatedUser['About_me'] = $updatedUser['About_me'] ?? '';
        
        // Add fields that don't exist in your table but are expected by the frontend
        $updatedUser['status'] = ($updatedUser['Is_active'] == 1) ? 'Active' : 'Inactive';
        $updatedUser['created_at'] = '2022-05-27'; // Default since you don't have this field
        
        // Remove Is_active since we converted it to status
        unset($updatedUser['Is_active']);
    }

    $successMessage = 'Profile updated successfully!';
    if ($passwordChangeRequested) {
        $successMessage = 'Profile and password updated successfully!';
    }

    $response['success'] = true;
    $response['message'] = $successMessage;
    $response['user'] = $updatedUser;

} catch (PDOException $e) {
    $response['message'] = 'Database error occurred. Please try again.';
    error_log('Profile update PDO Error: ' . $e->getMessage());
}

echo json_encode($response);
?>