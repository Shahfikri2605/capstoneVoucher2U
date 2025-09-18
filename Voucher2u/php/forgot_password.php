<?php
require_once '../../databaseConnection/db_config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unexpected error occurred.'];

error_log("Forgot password request method: " . $_SERVER['REQUEST_METHOD']);
error_log("Forgot password POST data: " . print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

$email = trim($_POST['email'] ?? '');

// Input validation
if (empty($email)) {
    $response['message'] = 'Please enter your email address.';
    echo json_encode($response);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Please enter a valid email address.';
    echo json_encode($response);
    exit;
}

try {
    // Check if user exists with this email (adjust table/column names to match your database)
    $stmt = $pdo->prepare("SELECT Id, Username FROM User WHERE Email = ? AND Is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        $response['message'] = 'Email not found or account is inactive.';
        echo json_encode($response);
        exit;
    }

    // Generate a secure token
    $token = bin2hex(random_bytes(32)); // 64 character token
    
    // Set expiration time (24 hours from now)
    $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

    // Delete any existing tokens for this user (cleanup)
    $stmt = $pdo->prepare("DELETE FROM forgot_passwords WHERE user_id = ?");
    $stmt->execute([$user['Id']]);

    // Insert new token
    $stmt = $pdo->prepare("
        INSERT INTO forgot_passwords (user_id, token, expires_at) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$user['Id'], $token, $expiresAt]);

    // Create reset link
    $resetLink = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/../html/ResetPassword.html?token=" . $token;

    // Log the reset link for debugging
    error_log("Password reset link for " . $email . ": " . $resetLink);

    // Function to check if MailHog is running
    function isMailHogRunning() {
        $connection = @fsockopen('localhost', 1025, $errno, $errstr, 5);
        if ($connection) {
            fclose($connection);
            return true;
        }
        return false;
    }

    // Configure PHP mail settings for MailHog
    ini_set('SMTP', 'localhost');
    ini_set('smtp_port', '1025');
    ini_set('sendmail_from', 'noreply@optimabank.com');

    // Email content
    $subject = "Password Reset Request - Optima Bank";
    $emailBody = "Hello " . htmlspecialchars($user['Username']) . ",\n\n";
    $emailBody .= "You have requested to reset your password for your Optima Bank account.\n\n";
    $emailBody .= "Click the following link to reset your password:\n";
    $emailBody .= $resetLink . "\n\n";
    $emailBody .= "This link will expire in 24 hours.\n\n";
    $emailBody .= "If you did not request this password reset, please ignore this email.\n\n";
    $emailBody .= "Best regards,\n";
    $emailBody .= "Optima Bank Team";

    // Email headers
    $headers = "From: noreply@optimabank.com\r\n";
    $headers .= "Reply-To: support@optimabank.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // Check if MailHog is running
    if (isMailHogRunning()) {
        // Send email through MailHog
        $emailSent = @mail($email, $subject, $emailBody, $headers);

        if ($emailSent) {
            $response['success'] = true;
            $response['message'] = 'A password reset link has been sent to your email address. Check MailHog to view the email.';
            $response['mailhog_url'] = 'http://localhost:8025';
            $response['using_mailhog'] = true;
            
            error_log("Password reset email sent successfully to MailHog for: " . $email);
        } else {
            // MailHog running but mail() failed
            $response['message'] = 'Failed to send email through MailHog. Please check your configuration.';
            error_log("MailHog running but mail() failed for: " . $email);
        }
    } else {
        // MailHog not running - fallback to demo mode
        $response['success'] = true;
        $response['message'] = 'MailHog is not running. For demo purposes, use this reset link:';
        $response['reset_link'] = $resetLink;
        $response['demo_mode'] = true;
        $response['mailhog_note'] = 'Start MailHog (./mailhog) and refresh to test email functionality.';
        
        error_log("MailHog not available - Reset link for demo: " . $resetLink);
    }

    // Log successful password reset request
    error_log("Password reset requested for user ID: " . $user['Id'] . ", Email: " . $email);

} catch (PDOException $e) {
    $response['message'] = 'Database error occurred. Please try again later.';
    error_log('Forgot Password PDO Error: ' . $e->getMessage());
} catch (Exception $e) {
    $response['message'] = 'An error occurred. Please try again later.';
    error_log('Forgot Password Error: ' . $e->getMessage());
}

echo json_encode($response);
?>