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

$userId = $_SESSION['Id'];

try {
    // Fetch user profile data - matching your exact table structure
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
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Handle NULL values and add missing fields
        $user['Phone_number'] = $user['Phone_number'] ?? '';
        $user['Address'] = $user['Address'] ?? '';
        $user['Points'] = $user['Points'] ?? 0;
        $user['Profile_image'] = $user['Profile_image'] ?? '';
        $user['About_me'] = $user['About_me'] ?? '';
        
        // Add fields that don't exist in your table but are expected by the frontend
        $user['status'] = ($user['Is_active'] == 1) ? 'Active' : 'Inactive';
        $user['created_at'] = '2022-05-27'; // Default since you don't have this field
        
        // Remove Is_active since we converted it to status
        unset($user['Is_active']);
        
        $response['success'] = true;
        $response['message'] = 'Profile data retrieved successfully.';
        $response['user'] = $user;
    } else {
        $response['message'] = 'User not found.';
    }
    
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
    error_log('Profile fetch PDO Error: ' . $e->getMessage());
}

echo json_encode($response);
?>