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
$username = $_POST['Username'] ?? '';
$email = $_POST['Email'] ?? '';
$phoneNumber = $_POST['Phone_number'] ?? '';
$address = $_POST['Address'] ?? '';

// Simple validation
if (empty($username) || empty($email)) {
    $response['message'] = 'Name and email are required.';
    echo json_encode($response);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Please enter a valid email.';
    echo json_encode($response);
    exit;
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

    // Simple update query - just basic info
    $stmt = $pdo->prepare("
        UPDATE User 
        SET Username = ?, Email = ?, Phone_number = ?, Address = ?
        WHERE Id = ?
    ");
    $stmt->execute([$username, $email, $phoneNumber, $address, $userId]);

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
            Profile_image
        FROM User 
        WHERE Id = ?
    ");
    $stmt->execute([$userId]);
    $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($updatedUser) {
        // Add status and created_at manually since they're not in your database
        $updatedUser['status'] = 'Active';
        $updatedUser['created_at'] = '2022-05-27'; // Default date
    }

    $response['success'] = true;
    $response['message'] = 'Profile updated successfully!';
    $response['user'] = $updatedUser;

    // Log the activity if you want
    // logUserActivity($pdo, $userId, 'profile_update', 'Profile updated');

} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
    error_log('Profile update PDO Error: ' . $e->getMessage());
}

echo json_encode($response);

function logUserActivity($pdo, $userId, $activityType, $description) {
    try {
        // Check if activities table exists
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'user_activities'");
        $stmt->execute();
        $tableExists = $stmt->fetch();
        
        if ($tableExists) {
            $stmt = $pdo->prepare("
                INSERT INTO user_activities (user_id, activity_type, description, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$userId, $activityType, $description]);
        }
    } catch (PDOException $e) {
        // Silently fail if logging doesn't work
        error_log('Activity logging error: ' . $e->getMessage());
    }
}
?>