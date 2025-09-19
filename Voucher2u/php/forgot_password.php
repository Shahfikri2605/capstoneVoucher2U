<?php

require '../../vendor/autoload.php'; // Composer autoloader

use Dotenv\Dotenv;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

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

    // PHPMailer configuration
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['GMAIL_USERNAME']; // Your Gmail address
        $mail->Password   = $_ENV['GMAIL_APP_PASSWORD']; // Your generated App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Use SMTPS
        $mail->Port       = 465; // SMTPS port for Gmail

        // Recipients
        $mail->setFrom('noreply@optimabank.com', 'Optima Bank');
        $mail->addAddress($email, htmlspecialchars($user['Username']));
        $mail->addReplyTo('support@optimabank.com', 'Support');

        // Content
        $mail->isHTML(false); // Set email format to plain text
        $mail->Subject = "Password Reset Request - Optima Bank";
        $mail->Body    = "Hello " . htmlspecialchars($user['Username']) . ",\n\n";
        $mail->Body   .= "You have requested to reset your password for your Optima Bank account.\n\n";
        $mail->Body   .= "Click the following link to reset your password:\n";
        $mail->Body   .= $resetLink . "\n\n";
        $mail->Body   .= "This link will expire in 24 hours.\n\n";
        $mail->Body   .= "If you did not request this password reset, please ignore this email.\n\n";
        $mail->Body   .= "Best regards,\n";
        $mail->Body   .= "Optima Bank Team";

        $mail->send();
        
        $response['success'] = true;
        $response['message'] = 'A password reset link has been sent to your email address via Gmail.';
        error_log("Password reset email sent successfully via Gmail to: " . $email);
    } catch (Exception $e) {
        $response['message'] = "Failed to send email. Mailer Error: {$mail->ErrorInfo}";
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
    }

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