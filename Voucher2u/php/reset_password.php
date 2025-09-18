<?php
require_once '../../databaseConnection/db_config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unexpected error occurred.'];

error_log("Reset password request method: " . $_SERVER['REQUEST_METHOD']);
error_log("Reset password POST data: " . print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

$token = trim($_POST['token'] ?? '');
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Input validation
if (empty($token)) {
    $response['message'] = 'Invalid or missing reset token.';
    echo json_encode($response);
    exit;
}

if (empty($newPassword) || empty($confirmPassword)) {
    $response['message'] = 'Please enter both password fields.';
    echo json_encode($response);
    exit;
}

// Password validation (simplified - matching your existing validation logic)
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

try {
    // Verify token and check if it's still valid
    $stmt = $pdo->prepare("
        SELECT user_id, expires_at, is_used 
        FROM forgot_passwords 
        WHERE token = ?
    ");
    $stmt->execute([$token]);
    $tokenData = $stmt->fetch();

    if (!$tokenData) {
        $response['message'] = 'Invalid reset token. Please request a new password reset.';
        echo json_encode($response);
        exit;
    }

    // Check if token has been used (proper BOOLEAN handling)
    if ($tokenData['is_used'] == 1 || $tokenData['is_used'] === true) {
        $response['message'] = 'This reset link has already been used. Please request a new password reset.';
        echo json_encode($response);
        exit;
    }

    // Check if token has expired
    $currentTime = date('Y-m-d H:i:s');
    if ($currentTime > $tokenData['expires_at']) {
        $response['message'] = 'This reset link has expired. Please request a new password reset.';
        echo json_encode($response);
        exit;
    }

    // Get user information for logging (adjust table/column names to match your database)
    $stmt = $pdo->prepare("SELECT Username, Email FROM user WHERE Id = ?");
    $stmt->execute([$tokenData['user_id']]);
    $user = $stmt->fetch();

    // Token is valid, update user password (adjust table/column names to match your database)
    $stmt = $pdo->prepare("UPDATE user SET Password = ? WHERE Id = ?");
    $stmt->execute([$newPassword, $tokenData['user_id']]);

    // Mark token as used (proper BOOLEAN syntax)
    $stmt = $pdo->prepare("UPDATE forgot_passwords SET is_used = TRUE WHERE token = ?");
    $stmt->execute([$token]);

    // Clean up old tokens for this user (optional, for security)
    $stmt = $pdo->prepare("
        DELETE FROM forgot_passwords 
        WHERE user_id = ? AND (is_used = TRUE OR expires_at < NOW()) AND token != ?
    ");
    $stmt->execute([$tokenData['user_id'], $token]);

    $response['success'] = true;
    $response['message'] = 'Your password has been successfully reset! Redirecting to login page...';

    // Log successful password reset
    error_log("Password successfully reset for user ID: " . $tokenData['user_id'] . 
              " (Username: " . ($user['Username'] ?? 'Unknown') . 
              ", Email: " . ($user['Email'] ?? 'Unknown') . ")");

} catch (PDOException $e) {
    $response['message'] = 'Database error occurred. Please try again later.';
    error_log('Reset Password PDO Error: ' . $e->getMessage());
    error_log('Token: ' . $token);
    error_log('User ID: ' . ($tokenData['user_id'] ?? 'N/A'));
} catch (Exception $e) {
    $response['message'] = 'An error occurred. Please try again later.';
    error_log('Reset Password Error: ' . $e->getMessage());
}

echo json_encode($response);
?>